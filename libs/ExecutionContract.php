<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

final class ExecutionContract
{
    public static function Snapshot(mixed $data): ?array
    {
        if (!$data instanceof stdClass || count(get_object_vars($data)) !== 7
            || ($data->protocol_version ?? null) !== 1 || ($data->execution_version ?? null) !== 1
            || !RuntimeDebugContract::UUID($data->session ?? null) || !ExtensionContract::Revision($data->revision ?? null)
            || !ExtensionContract::Revision($data->sequence ?? null) || !property_exists($data, 'checksum')
            || ($data->revision === 0 ? $data->checksum !== null : !ExtensionContract::Checksum($data->checksum))
            || !is_array($data->runs ?? null) || count($data->runs) > 100) {
            return null;
        }
        $runs = [];
        foreach ($data->runs as $run) {
            if (!$run instanceof stdClass || count(get_object_vars($run)) !== 7
                || !is_string($run->rule_id ?? null) || preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $run->rule_id) !== 1
                || isset($runs[$run->rule_id]) || !RuntimeDebugContract::UUID($run->run ?? null)
                || ($run->revision ?? null) !== $data->revision || ($run->checksum ?? null) !== $data->checksum || $data->revision === 0
                || !in_array($run->state ?? null, ['running', 'completed', 'skipped', 'failed', 'stopped'], true)
                || !in_array($run->error ?? null, [...ExtensionContract::RUNTIME_ERRORS, 'conditions_not_met'], true)
                || !is_int($run->step ?? null) || $run->step < 0 || $run->step > 16) {
                return null;
            }
            $runs[$run->rule_id] = (array) $run;
        }
        return ['session' => $data->session, 'revision' => $data->revision, 'checksum' => $data->checksum,
            'sequence'    => $data->sequence, 'runs' => $runs];
    }
}
