<?php

namespace App\Services;

use App\Models\Session;
use App\Models\UserGroup;
use App\Models\ModuleLimit;
use App\Models\GroupModuleLimit;
use App\Models\LinkedModule;

class LimitChecker
{
    private array $exceptionUsers = [
        'ADMIN',
        'Zarząd',
        'Biuro Księgowe',
        'TSL SILESIA SP. Z O.O.'
    ];

    private array $userGroups = []; // [UserName => Group]
    private array $groupModuleLimits = [];
    private array $moduleLimits = [];
    private array $linkedModules = [];
    private int $activeUsersCheck = 0;

    public function __construct()
    {
        $base = dirname(__DIR__, 2) . '/ComarchBlock/';
        $cfgPath = $base . 'config.xml';

        if (file_exists($cfgPath)) {
            $xml = simplexml_load_file($cfgPath);
            $this->activeUsersCheck = intval($xml->ActiveUsersCheck ?? 0);
        }

        $this->userGroups = UserGroup::all()
            ->mapWithKeys(fn($u) => [$u->UserName => $u->Group])
            ->toArray();

        foreach (GroupModuleLimit::all() as $g) {
            $this->groupModuleLimits[$g->GroupCode][$g->Module][$g->Hour] = $g->MaxLicenses;
        }

        $this->moduleLimits = ModuleLimit::all()->pluck('MaxLicenses', 'Module')->toArray();

        foreach (LinkedModule::all() as $l) {
            if ($l->GroupCode && $l->ModuleName) {
                $this->linkedModules[$l->ModuleName][] = $l->GroupCode;
            }
        }


        error_log("LimitChecker initialized. ActiveUsersCheck = {$this->activeUsersCheck}");
        error_log("Linked modules map: " . json_encode($this->linkedModules));
    }
    private function normalizePc(string $pc): string
    {
        return explode(':', $pc)[0];
    }

    private function getMaxForGroupModule(string $group, string $module, int $hour): mixed
    {
        return array_key_exists($group, $this->groupModuleLimits)
            && array_key_exists($module, $this->groupModuleLimits[$group])
            && array_key_exists($hour, $this->groupModuleLimits[$group][$module])
            ? $this->groupModuleLimits[$group][$module][$hour]
            : null;
    }

    private function getFallbackModule(string $module): ?string
    {
        return $this->linkedModules[$module][0] ?? null;
    }


public function check(string $pc): array
{
    error_log("== CHECK STARTED for PC: {$pc}");

    $sessions = Session::where('SES_Stop', 0)->get();

    foreach ($sessions as $s) {
        error_log("DEBUG SES: ID={$s->SES_SesjaID}, PC={$s->SES_Komputer}, ADOSPID=" . ($s->SES_ADOSPID ?? 'null'));
    }

    $pcNorm = $this->normalizePc($pc);

    // Поиск сессии по нормализованному имени ПК
    $session = $sessions->first(function ($s) use ($pcNorm) {
        return $this->normalizePc($s->SES_Komputer) === $pcNorm;
    });

    if (!$session) {
        error_log("No active session found for PC: {$pc}");
        return ['status' => 200, 'code' => '3'];
    }

    error_log("Found session: ID={$session->SES_SesjaID}, User={$session->SES_OpeIdent}, Modul={$session->SES_Modul}");

    // Повторно забираем минимальный набор колонок
    $sessions = Session::where('SES_Stop', 0)
        ->whereNotNull('SES_ADOSPID')
        ->get(['SES_SesjaID', 'SES_ADOSPID', 'SES_OpeIdent', 'SES_Modul', 'SES_Start']);
    if ($sessions->count() <= $this->activeUsersCheck) {
        error_log("User count ({$sessions->count()}) below threshold — OK");
        return ['status' => 200, 'code' => '2'];
    }

    $sessionsArr = $sessions->map(fn($s) => [
        'Id' => $s->SES_SesjaID,
        'Spid' => $s->SES_ADOSPID,
        'UserName' => $s->SES_OpeIdent,
        'Module' => $s->SES_Modul ?? '',
        'Start' => (int)($s->SES_Start ?? 0)
    ])->toArray();

    $target = collect($sessionsArr)->firstWhere('Id', $session->SES_SesjaID);

    if (!$target) {
        error_log("Target session not found in session list");
        return ['status' => 200, 'code' => '2'];
    }

    $module = $target['Module'];
    $userName = $target['UserName'];
    error_log("Target session: User={$userName}, Module={$module}");

    // Проверка общего модуля
    if (array_key_exists($module, $this->moduleLimits)) {
        $moduleSessions = array_values(array_filter(
            $sessionsArr,
            fn($s) =>
                $s['Module'] === $module &&
                !in_array($s['UserName'], $this->exceptionUsers, true)
        ));
        usort($moduleSessions, fn($a, $b) => $a['Start'] <=> $b['Start']);

        foreach ($moduleSessions as $i => $s) {
            if ($s['Id'] === $target['Id'] && $i >= $this->moduleLimits[$module]) {
                error_log("Module limit exceeded");
                return ['status' => 200, 'code' => '1'];
            }
        }
    }

    $group = $this->userGroups[$userName] ?? null;
    error_log("Group resolved for user={$userName}: " . ($group ?? 'null'));

    $max = null;
    if ($group) {
        $hour = intval(date('G'));
        $max = $this->getMaxForGroupModule($group, $module, $hour);

        if ($max === null) {
            $fallback = $this->getFallbackModule($module);
            if ($fallback) {
                $max = $this->getMaxForGroupModule($group, $fallback, $hour);
            }
            if ($max === null && array_key_exists($module, $this->moduleLimits)) {
                $max = $this->moduleLimits[$module];
            }
        }

        if ($max !== null) {
            // ВРЕМЕННЫЕ ПРЕДУПРЕЖДЕНИЯ
            $usedModule = $module;
            if (!isset($this->groupModuleLimits[$group][$usedModule]) && $fallback) {
                $usedModule = $fallback;
            }

            $allowedHours = [];
            if (isset($this->groupModuleLimits[$group][$usedModule])) {
                foreach ($this->groupModuleLimits[$group][$usedModule] as $h => $v) {
                    if ((int)$v > 0) {
                        $allowedHours[] = (int)$h;
                    }
                }
            }

            if (!empty($allowedHours)) {
                $lastAllowedHour = max($allowedHours);
                $now = new \DateTime();
                $minute = (int)$now->format('i');
                $currentHour = (int)$now->format('G');

                if ($currentHour === $lastAllowedHour && $minute >= 45 && $minute <= 47) {
                    return ['status' => 200, 'code' => '4'];
                }

                if ($currentHour === $lastAllowedHour && $minute >= 55 && $minute <= 57) {
                    return ['status' => 200, 'code' => '5'];
                }
            }

            // Проверка группового лимита
            $groupSessions = array_filter($sessionsArr, function ($s) use ($group, $module) {
                $grp = $this->userGroups[$s['UserName']] ?? null;
                return $grp === $group &&
                    $s['Module'] === $module &&
                    !in_array($s['UserName'], $this->exceptionUsers, true);
            });

            usort($groupSessions, fn($a, $b) => $a['Start'] <=> $b['Start']);
            foreach (array_values($groupSessions) as $i => $s) {
                if ($s['Id'] === $target['Id'] && $i >= $max) {
                    return ['status' => 200, 'code' => '1'];
                }
            }
        }
    }

    return ['status' => 200, 'code' => '2'];
}

}
