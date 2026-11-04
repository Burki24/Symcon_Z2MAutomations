<?php

declare(strict_types=1);

namespace Z2MAutomations;

require_once __DIR__ . '/DraftTransferProtocol.php';
require_once __DIR__ . '/ExecutionContract.php';
require_once __DIR__ . '/RuntimeDebugContract.php';

/** Existing runtime wire contracts, without kernel calls or a second execution engine. */
final class RuntimeControlProtocol
{
    public const KINDS = ['status', 'activate', 'deactivate', 'run_once', 'stop_rule'];
    public const STATES = ['prepared', 'pending', 'status_received', 'activated', 'stopped', 'run_started', 'run_stopped', 'error', 'cancelled', 'timeout', 'interrupted'];
    public const ERRORS = [...ExtensionContract::ERROR_CODES, ...ExtensionContract::RUNTIME_ERRORS, 'conditions_not_met',
        'not_connected', 'bridge_offline', 'timeout', 'send_failed', 'cancelled', 'invalid_response', 'protocol_mismatch', 'runtime_mismatch', 'execution_unavailable'];
    public const MESSAGES = [
        'control_unavailable' => 'Die Betriebssteuerung ist momentan nicht verfügbar. Bitte Verbindung und Status prüfen. Es wird nichts automatisch erneut gesendet.',
        'runtime_pending'     => 'Für dieses System steht noch eine Antwort aus. Bitte den Status aktualisieren. Stoppen bleibt möglich.',
        'runtime_draft'       => 'Der Entwurf entspricht nicht dem gespeicherten Z2M-Stand oder enthält offene Änderungen. Bitte Änderungen zuerst übernehmen, prüfen und speichern.',
        'runtime_changed'     => 'Der Z2M-Stand hat sich inzwischen geändert. Bitte den Betriebsstatus aktualisieren und den Auftrag erneut auswählen.',
        'runtime_interrupted' => 'Der Auftrag wurde unterbrochen. Eine bereits gesendete Aktion könnte ausgeführt worden sein. Bitte zuerst den Betriebsstatus aktualisieren; es wird nichts wiederholt.',
        'runtime_selection'   => 'Bitte eine gespeicherte Automation auswählen. Zum Stoppen muss genau ihr aktueller Lauf ausgewählt sein.'
    ];

    public static function Rule(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $value) === 1;
    }

    public static function RequestValid(mixed $r): bool
    {
        return is_array($r) && count($r) === 12 && is_int($r['owner'] ?? null) && $r['owner'] >= 10000 && $r['owner'] <= 59999
            && DraftTransferProtocol::Operation($r['operation'] ?? null) && DraftTransferProtocol::Operation($r['epoch'] ?? null)
            && is_array($r['binding'] ?? null) && SystemContract::RegistryError([$r['binding']]) === '' && $r['binding']['ownerInstance'] === $r['owner']
            && in_array($r['kind'] ?? null, self::KINDS, true) && ExtensionContract::Revision($r['revision'] ?? null)
            && array_key_exists('checksum', $r) && ($r['checksum'] === null || ExtensionContract::Checksum($r['checksum']))
            && (($r['session'] ?? null) === '' || ExtensionContract::Checksum($r['session'] ?? null)) && is_bool($r['resume'] ?? null)
            && (in_array($r['kind'], ['run_once', 'stop_rule'], true) ? self::Rule($r['rule'] ?? null) : ($r['rule'] ?? null) === '')
            && ($r['kind'] === 'stop_rule' ? RuntimeDebugContract::UUID($r['run'] ?? null) : ($r['run'] ?? null) === '')
            && is_string($r['scopeProof'] ?? null) && ExtensionContract::Checksum($r['scopeProof']);
    }

    public static function SameStored(array $metadata, array $request): bool
    {
        return hash('sha256', $metadata['session']) === $request['session'] && $metadata['storedRevision'] === $request['revision'] && $metadata['checksum'] === $request['checksum'];
    }

    public static function RuntimeMatches(array $metadata, array $target, bool $active): bool
    {
        return $metadata['ready'] && $metadata['session'] === $target['session'] && $metadata['runtimeVersion'] === 1 && $metadata['executing'] === $active
            && $metadata['storedRevision'] === $target['storedRevision'] && $metadata['checksum'] === $target['checksum']
            && (!$active || ($metadata['activeRevision'] === $target['storedRevision'] && $metadata['activeChecksum'] === $target['checksum']));
    }

    public static function RunText(?array $run): string
    {
        return match ($run['state'] ?? '') {
            'running'   => 'Läuft. Aktionsschritt: ' . $run['step'] . '.',
            'completed' => 'Abgeschlossen. Die Gerätewirkung wurde damit nicht gesondert bestätigt.',
            'skipped'   => 'Nicht ausgeführt: Eine Bedingung ist nicht erfüllt oder ihr Wert ist unbekannt.',
            'stopped'   => 'Dieser Lauf wurde gestoppt. Bereits gesendete Befehle können nicht zurückgenommen werden.',
            'failed'    => 'Fehlgeschlagen. Weitere Aktionen dieses Laufs wurden abgebrochen.',
            default     => 'Für diese Automation wurde in der aktuellen Z2M-Sitzung noch kein Lauf gemeldet.'
        };
    }
}
