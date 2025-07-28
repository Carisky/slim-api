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

        $session = Session::where('SES_Komputer', $pc)
            ->where('SES_Stop', 0)
            ->whereNotNull('SES_ADOSPID')
            ->first();

        if (!$session) {
            error_log("No active session found for PC: {$pc}");
            return ['status' => 404];
        }

        error_log("Found session: ID={$session->SES_SesjaID}, User={$session->SES_OpeIdent}, Modul={$session->SES_Modul}");

        $sessions = Session::where('SES_Stop', 0)
            ->whereNotNull('SES_ADOSPID')
            ->get(['SES_SesjaID', 'SES_ADOSPID', 'SES_OpeIdent', 'SES_Modul', 'SES_Start']);

        if ($sessions->count() <= $this->activeUsersCheck) {
            error_log("User count ({$sessions->count()}) below threshold ({$this->activeUsersCheck}) — OK");
            return ['status' => 200, 'user' => $session->SES_OpeIdent];
        }

        $sessionsArr = [];
        foreach ($sessions as $s) {
            $sessionsArr[] = [
                'Id' => $s->SES_SesjaID,
                'Spid' => $s->SES_ADOSPID,
                'UserName' => $s->SES_OpeIdent,
                'Module' => $s->SES_Modul ?? '',
                'Start' => (int)($s->SES_Start ?? 0)
            ];
        }

        $target = null;
        foreach ($sessionsArr as $sess) {
            if ($sess['Id'] === $session->SES_SesjaID) {
                $target = $sess;
                break;
            }
        }

        if (!$target) {
            error_log("Target session not found in session list");
            return ['status' => 200, 'user' => $session->SES_OpeIdent];
        }

        $module = $target['Module'];
        error_log("Target session: User={$target['UserName']}, Module={$module}");

        // Общий модульный лимит
        if (array_key_exists($module, $this->moduleLimits)) {
            $moduleSessions = array_filter($sessionsArr, fn($s) =>
                $s['Module'] === $module &&
                !in_array($s['UserName'], $this->exceptionUsers, true)
            );
            usort($moduleSessions, fn($a, $b) => $a['Start'] <=> $b['Start']);

            foreach (array_values($moduleSessions) as $i => $s) {
                if ($s['Id'] === $target['Id'] && $i >= $this->moduleLimits[$module]) {
                    error_log("Module limit exceeded: Module={$module}, Index={$i}, Limit={$this->moduleLimits[$module]}");
                    return ['status' => 429, 'reason' => 'ModuleLimit', 'user' => $session->SES_OpeIdent];
                }
            }
        }

        $userName = $target['UserName'];
        $group = $this->userGroups[$userName] ?? null;
        error_log("Group resolved for user={$userName}: " . ($group ?? 'null'));

        $max = null;
        if ($group) {
            $hour = intval(date('G'));
            $max = $this->getMaxForGroupModule($group, $module, $hour);
            error_log("Group limit lookup: Group={$group}, Module={$module}, Hour={$hour}, Max=" . var_export($max, true));

            if ($max === null) {
                $fallback = $this->getFallbackModule($module);
                error_log("Fallback for module {$module} is: " . var_export($fallback, true));

                if ($fallback) {
                    $max = $this->getMaxForGroupModule($group, $fallback, $hour);
                    error_log("Using fallback module {$fallback}, Max=" . var_export($max, true));
                }

                if ($max === null && array_key_exists($module, $this->moduleLimits)) {
                    $max = $this->moduleLimits[$module];
                    error_log("Using global module limit as fallback: {$max}");
                }
            }

            if ($max !== null) {
                error_log("Effective max license count: {$max}");

                $groupSessions = array_filter($sessionsArr, function ($s) use ($group, $module) {
                    $grp = $this->userGroups[$s['UserName']] ?? null;
                    return $grp === $group &&
                        $s['Module'] === $module &&
                        !in_array($s['UserName'], $this->exceptionUsers, true);
                });

                usort($groupSessions, fn($a, $b) => $a['Start'] <=> $b['Start']);
                foreach (array_values($groupSessions) as $i => $s) {
                    if ($s['Id'] === $target['Id'] && $i >= $max) {
                        error_log("Group limit exceeded: Group={$group}, Index={$i}, Max={$max}");
                        return ['status' => 429, 'reason' => 'ModuleGroupLimit', 'user' => $session->SES_OpeIdent];
                    }
                }
            } else {
                error_log("No max limit found at all for group {$group} and module {$module}");
            }
        } else {
            error_log("Group not found for user: {$userName}");
        }

        error_log("Session allowed: {$session->SES_OpeIdent}");
        return ['status' => 200, 'user' => $session->SES_OpeIdent];
    }
}
