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

    private array $userGroups = [];
    private array $groupModuleLimits = [];
    private array $moduleLimits = [];
    private array $linkedModules = [];
    private int $activeUsersCheck = 0;

    public function __construct()
    {
        $base = dirname(__DIR__, 2) . '/ComarchBlock/';

        // Load threshold from the original config file if present
        $cfgPath = $base . 'config.xml';
        if (file_exists($cfgPath)) {
            $xml = simplexml_load_file($cfgPath);
            $this->activeUsersCheck = intval($xml->ActiveUsersCheck ?? 0);
        }

        // Pull configuration data from the database
        $this->userGroups = UserGroup::all()
            ->mapWithKeys(function ($u) {
                return [$u->UserName => ['Group' => $u->Group, 'WindowsUser' => $u->WindowsUser]];
            })->toArray();

        foreach (GroupModuleLimit::all() as $g) {
            $this->groupModuleLimits[$g->GroupCode][$g->Module][$g->Hour] = $g->MaxLicenses;
        }

        $this->moduleLimits = ModuleLimit::all()->pluck('MaxLicenses', 'Module')->toArray();

        foreach (LinkedModule::all() as $l) {
            $this->linkedModules[$l->ModuleKey][] = $l->LinkedModule;
        }
    }

    private function getMaxForGroupModule(string $group, string $module, int $hour): ?int
    {
        return $this->groupModuleLimits[$group][$module][$hour] ?? null;
    }

    private function getFallbackModule(string $module): ?string
    {
        foreach ($this->linkedModules as $key => $list) {
            if (in_array($module, $list, true)) {
                return $key;
            }
        }
        return null;
    }

    public function check(string $pc): array
    {
        $session = Session::where('SES_Komputer', $pc)
            ->where('SES_Stop', 0)
            ->whereNotNull('SES_ADOSPID')
            ->first();
        if (!$session) {
            return ['status' => 404];
        }

        $sessions = Session::where('SES_Stop', 0)
            ->whereNotNull('SES_ADOSPID')
            ->get(['SES_SesjaID', 'SES_ADOSPID', 'SES_OpeIdent', 'SES_Modul', 'SES_Start']);

        if ($sessions->count() <= $this->activeUsersCheck) {
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
            return ['status' => 200, 'user' => $session->SES_OpeIdent];
        }

        $module = $target['Module'];
        if (isset($this->moduleLimits[$module])) {
            $moduleSessions = array_filter($sessionsArr, fn($s) => $s['Module'] === $module && !in_array($s['UserName'], $this->exceptionUsers, true));
            usort($moduleSessions, fn($a, $b) => $a['Start'] <=> $b['Start']);
            foreach (array_values($moduleSessions) as $i => $s) {
                if ($s['Id'] === $target['Id'] && $i >= $this->moduleLimits[$module]) {
                    return ['status' => 429, 'reason' => 'ModuleLimit', 'user' => $session->SES_OpeIdent];
                }
            }
        }

        $userName = $target['UserName'];
        $group = $this->userGroups[$userName]['Group'] ?? null;
        if ($group) {
            $hour = intval(date('G'));
            $max = $this->getMaxForGroupModule($group, $module, $hour);
            if ($max === null) {
                $fallback = $this->getFallbackModule($module);
                if ($fallback) {
                    $max = $this->getMaxForGroupModule($group, $fallback, $hour);
                }
                if ($max === null) {
                    $max = $this->moduleLimits[$module] ?? null;
                }
            }
            if ($max !== null) {
                $groupSessions = array_filter($sessionsArr, function ($s) use ($group, $module) {
                    $grp = $this->userGroups[$s['UserName']]['Group'] ?? null;
                    return $grp === $group && $s['Module'] === $module && !in_array($s['UserName'], $this->exceptionUsers, true);
                });
                usort($groupSessions, fn($a, $b) => $a['Start'] <=> $b['Start']);
                foreach (array_values($groupSessions) as $i => $s) {
                    if ($s['Id'] === $target['Id'] && $i >= $max) {
                        return ['status' => 429, 'reason' => 'ModuleGroupLimit', 'user' => $session->SES_OpeIdent];
                    }
                }
            }
        }

        return ['status' => 200, 'user' => $session->SES_OpeIdent];
    }
}