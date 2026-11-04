<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** Deterministic competing entry; central stubs remain unchanged. */
final class DesignerLockScenario
{
    public static string $lock = '';
    public static bool $release = true;
    public static array $waits = [];
}

function IPS_SemaphoreEnter(string $Name, int $Milliseconds): bool
{
    if ($Name === DesignerLockScenario::$lock) {
        DesignerLockScenario::$waits[] = $Milliseconds;
        if ($Milliseconds === 0 || !DesignerLockScenario::$release) return false;
        DesignerLockScenario::$lock = '';
    }
    return \IPS_SemaphoreEnter($Name, $Milliseconds);
}
