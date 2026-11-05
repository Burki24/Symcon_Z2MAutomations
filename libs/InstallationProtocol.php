<?php

declare(strict_types=1);

namespace Z2MAutomations;

final class InstallationProtocol
{
    public const PENDING = ['checking', 'prepared', 'pending'];
    public const STATES = [...self::PENDING, 'installed', 'error', 'interrupted'];
    public const MESSAGES = [
        'installation_failed'           => 'Zigbee2MQTT hat die Installation der Erweiterung abgelehnt. Bitte die Freigabe für externe Erweiterungen und das Z2M-Protokoll prüfen.',
        'external_js_disabled'          => 'Externe Erweiterungen sind in Zigbee2MQTT ausgeschaltet. Bitte diese zuerst in Z2M freigeben und danach erneut prüfen.',
        'artifact_unavailable'          => 'Die mitgelieferte Erweiterungsdatei fehlt oder passt nicht zum bestätigten Stand. Bitte das Modul aktualisieren und die Ansicht neu laden.',
        'timeout'                       => 'Zigbee2MQTT hat nicht rechtzeitig geantwortet. Bitte den Erweiterungsstatus und das Z2M-Protokoll prüfen. Es wird nichts automatisch erneut übertragen.',
        'installation_confirmation'     => 'Installation nicht gestartet. Bitte das angezeigte Z2M-System prüfen und die Installation ausdrücklich bestätigen.',
        'installation_changed'          => 'Systemzuordnung, Erweiterungsstand oder Moduldatei hat sich geändert. Bitte den aktuellen Stand laden und die Installation erneut bestätigen.',
        'installation_pending'          => 'Für dieses System läuft bereits eine Installation. Bitte die Rückmeldung abwarten; es wird keine zweite Datei gesendet.',
        'installation_superseded'       => 'Inzwischen gibt es einen neueren Installationsauftrag für dieses System. Bitte den aktuellen Status laden; der ältere Auftrag wird nicht erneut gesendet.',
        'installation_unavailable'      => 'Die Installationsrückmeldung ist derzeit nicht verfügbar. Bitte den Status prüfen. Es wird nichts automatisch erneut gesendet.',
        'installation_interrupted'      => 'Die Installation wurde unterbrochen. Die Datei könnte bereits übertragen sein. Bitte zuerst den Erweiterungsstatus prüfen; es wird nichts erneut hochgeladen.',
        'installation_build_mismatch'   => 'Z2M hat noch nicht den erwarteten neuen Erweiterungsstand bestätigt. Bitte den Erweiterungsstatus und das Z2M-Protokoll prüfen.',
        'installation_session_mismatch' => 'Z2M hat noch keine neue Erweiterungssitzung bestätigt. Bitte den Erweiterungsstatus und das Z2M-Protokoll prüfen. Ein gegebenenfalls notwendiger Z2M-Neustart muss gesondert durchgeführt werden.',
        'installation_storage_changed'  => 'Der Erweiterungsstand ist geladen, aber die gespeicherten Automationen weichen vom vorher bestätigten Stand ab. Bitte die gespeicherten Regeln prüfen; es wurde keine Automationsdatei übertragen.'
    ];

    public static function Valid(mixed $r): bool
    {
        if (!is_array($r) || count($r) !== 10 || !is_bool($r['owned'] ?? null) || !is_int($r['owner'] ?? null) || $r['owner'] < 10000 || $r['owner'] > 59999
            || !DraftTransferProtocol::Operation($r['operation'] ?? null) || !DraftTransferProtocol::Operation($r['epoch'] ?? null)
            || !is_array($r['binding'] ?? null) || SystemContract::RegistryError([$r['binding']]) !== '' || $r['binding']['ownerInstance'] !== $r['owner']
            || !ExtensionContract::Checksum($r['build'] ?? null) || !ExtensionContract::Checksum($r['artifact'] ?? null) || !ExtensionContract::Checksum($r['fingerprint'] ?? null)
            || !isset($r['health']) || ($r['health'] !== '' && !DraftTransferProtocol::Operation($r['health'])) || !array_key_exists('before', $r)) return false;
        $b = $r['before'];
        return $b === null || (is_array($b) && count($b) === 5 && ExtensionContract::Checksum($b['session'] ?? null)
            && ExtensionContract::Revision($b['revision'] ?? null) && array_key_exists('checksum', $b)
            && ($b['checksum'] === null || ExtensionContract::Checksum($b['checksum'])) && is_bool($b['executing'] ?? null) && is_bool($b['resume'] ?? null));
    }

    public static function Before(array $state): ?array
    {
        $m = $state['extension'] ?? null;
        return ($state['extensionConfirmed'] ?? false) && is_array($m) ? ['session' => $m['session'], 'revision' => $m['storedRevision'],
            'checksum'                                                              => $m['checksum'], 'executing' => $m['executing'], 'resume' => $m['resumeEnabled'] ?? false] : null;
    }
}
