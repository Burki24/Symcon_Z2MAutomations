<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

require_once __DIR__ . '/SystemContract.php';
require_once __DIR__ . '/ExtensionContract.php';
require_once __DIR__ . '/ValidationFeedback.php';

/** Shared checks for the existing validate/store protocol, without kernel access. */
final class DraftTransferProtocol
{
    public static function RequestValid(mixed $request): bool
    {
        return is_array($request) && count($request) === 11 && is_int($request['owner'] ?? null)
            && $request['owner'] >= 10000 && $request['owner'] <= 59999
            && self::Operation($request['operation'] ?? null) && self::Operation($request['epoch'] ?? null)
            && is_array($request['binding'] ?? null) && SystemContract::RegistryError([$request['binding']]) === ''
            && $request['binding']['ownerInstance'] === $request['owner']
            && in_array($request['kind'] ?? null, ['validate', 'store'], true)
            && ExtensionContract::Checksum($request['checksum'] ?? null)
            && ExtensionContract::Revision($request['revision'] ?? null) && $request['revision'] > 0
            && is_int($request['automationCount'] ?? null) && $request['automationCount'] >= 0 && $request['automationCount'] <= 100
            && ExtensionContract::Revision($request['baseRevision'] ?? null)
            && (array_key_exists('baseChecksum', $request) && ($request['baseChecksum'] === null || ExtensionContract::Checksum($request['baseChecksum'])))
            && ($request['kind'] === 'validate' ? ($request['validatedRequest'] ?? null) === '' : self::Operation($request['validatedRequest'] ?? null));
    }

    public static function Operation(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{32}\z/D', $value) === 1;
    }

    public static function DetailsValid(mixed $details): bool
    {
        return $details === null || (is_array($details) && ValidationFeedback::Normalize(json_decode(json_encode($details, JSON_THROW_ON_ERROR), false, 16, JSON_THROW_ON_ERROR)) === $details);
    }

    public static function ReceiptMatches(array $receipt, array $metadata): bool
    {
        return $receipt['session'] === $metadata['session'] && $receipt['expectedRevision'] === $metadata['storedRevision']
            && $receipt['previousChecksum'] === $metadata['checksum'];
    }

    public static function BaseMatches(array $request, array $metadata): bool
    {
        return ($request['baseRevision'] === $metadata['storedRevision'] && $request['baseChecksum'] === $metadata['checksum'])
            || ($request['revision'] === $metadata['storedRevision'] && $request['checksum'] === $metadata['checksum']);
    }

    public static function Validation(stdClass $data, string $checksum, array $target, ?array $current): ?array
    {
        if (($data->valid ?? null) !== true || !ExtensionContract::Revision($data->revision ?? null) || $data->revision < 1
            || ($data->checksum ?? null) !== $checksum || !is_int($data->automation_count ?? null)
            || $data->automation_count < 0 || $data->automation_count > 100 || ($data->executing ?? null) !== false || $target !== $current) return null;
        return ['checksum' => $checksum, 'revision' => $data->revision, 'automationCount' => $data->automation_count,
            'session'      => $target['session'], 'expectedRevision' => $target['storedRevision'], 'previousChecksum' => $target['checksum']];
    }

    public static function Storage(stdClass $data, ?array $receipt, string $checksum): ?array
    {
        $metadata = ExtensionContract::Storage($data);
        return $metadata !== null && $receipt !== null && $metadata['storedRevision'] === $receipt['revision']
            && $metadata['checksum'] === $checksum && $metadata['automationCount'] === $receipt['automationCount']
            && $metadata['storage'] === 'ok' && is_bool($data->unchanged ?? null) ? $metadata : null;
    }

    public static function StoredStatusError(array $metadata, array $stored): string
    {
        return match (true) {
            $metadata['session'] !== $stored['session'] => 'session_mismatch',
            $metadata['storedRevision'] !== $stored['storedRevision'] || $metadata['checksum'] !== $stored['checksum']
                || $metadata['automationCount'] !== $stored['automationCount'] => 'revision_conflict',
            $metadata['storage'] !== 'ok'                                      => 'storage_unavailable',
            $metadata['runtimeVersion'] !== 1                                  => 'runtime_unavailable',
            $metadata['executing'] !== $stored['executing']                    => 'runtime_mismatch',
            default                                                            => ''
        };
    }
}
