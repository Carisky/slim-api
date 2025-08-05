<?php

namespace App\Services;

use App\Models\Session;
use App\Models\UserGroup;
use App\Models\ModuleLimit;
use App\Models\GroupModuleLimit;
use App\Models\LinkedModule;
use App\Models\ExceptionUser;

class LimitChecker
{
    private array $exceptionUsers = [];
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

        $this->exceptionUsers = ExceptionUser::pluck('UserName')->toArray();

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
    /**
     * Calculate remaining minutes for a given user and module based on
     * configured group module limits.
     * Returns null when no limits defined.
     */
    private function calculateTimeLeft(string $userName, string $module): ?int
    {
        $group = $this->userGroups[$userName] ?? null;
        if (!$group) {
            return null;
        }

        $usedModule = $module;
        $fallback = $this->getFallbackModule($module);
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

        if (empty($allowedHours)) {
            return null;
        }

        $now = new \DateTime();
        $currentHour = (int)$now->format('G');
        $minute = (int)$now->format('i');

        $lastHour = max($allowedHours);

        if ($currentHour > $lastHour) {
            return 0;
        }

        // ⬇️ Главное отличие: считаем до конца последнего разрешённого часа
        $end = clone $now;
        $end->setTime($lastHour + 1, 0, 0);

        $interval = $now->diff($end);
        $minutes = ($interval->h * 60) + $interval->i;

        return $minutes;
    }
    /**
     * Get remaining time information for all active users.
     *
     * @return array<int, array{user:string,pc:string,time_left:?int}>
     */
    public function getUsersTimeLeft(): array
    {
        $sessions = Session::where('SES_Stop', 0)
            ->whereNotNull('SES_ADOSPID')
            ->get(['SES_OpeIdent', 'SES_Modul', 'SES_Komputer']);

        $info = [];
        foreach ($sessions as $s) {
            $user = $s->SES_OpeIdent;
            $info[] = [
                'user' => $user,
                'pc' => $s->SES_Komputer,
                'time_left' => $this->calculateTimeLeft($user, $s->SES_Modul ?? ''),
                'exception_group' => in_array($user, $this->exceptionUsers, true),
            ];
        }

        return $info;
    }

    /**
     * Get schedule of allowed hours for all modules for a given user.
     *
     * @param string $userName
     * @return array<string, array<int>>
     */
    public function getUserSchedule(string $userName): array
    {
        $group = $this->userGroups[$userName] ?? null;
        if (!$group || !isset($this->groupModuleLimits[$group])) {
            return [];
        }

        $schedule = [];
        foreach ($this->groupModuleLimits[$group] as $module => $hours) {
            $allowed = [];
            foreach ($hours as $h => $max) {
                if ((int)$max > 0) {
                    $allowed[] = (int)$h;
                }
            }
            sort($allowed);
            if (!empty($allowed)) {
                $schedule[$module] = $allowed;
            }
        }

        return $schedule;
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
    public function stopSession(string $user): bool
    {
        $session = Session::where('SES_OpeIdent', $user)
            ->where('SES_Stop', 0)
            ->orderByDesc('SES_Start')
            ->first();

        if (!$session) {
            error_log("stopSession: No active session found for user: $user");
            return false;
        }

        $session->SES_Stop = time(); // timestamp окончания
        $saved = $session->save();

        error_log("stopSession: Session for user {$user} stopped. Result: " . ($saved ? "OK" : "FAIL"));

        return $saved;
    }
    public function deleteSession(string $user): bool
    {
        $session = Session::where('SES_OpeIdent', $user)
            ->where('SES_Stop', 0)
            ->orderByDesc('SES_Start')
            ->first();

        if (!$session) {
            error_log("deleteSession: No active session found for user: $user");
            return false;
        }

        $deleted = $session->delete();
        error_log("deleteSession: Session for user {$user} deleted. Result: " . ($deleted ? "OK" : "FAIL"));
        return $deleted;
    }
}
