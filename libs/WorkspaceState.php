<?php

declare(strict_types=1);

namespace Z2MAutomations;

require_once __DIR__ . '/SystemContract.php';
require_once __DIR__ . '/ExtensionContract.php';
require_once __DIR__ . '/SequencePlan.php';

/** Local documents only. No MQTT, kernel calls, role conversion or ownership transfer. */
final class WorkspaceState
{
    public const VERSION = 1;
    public const MAX_BYTES = 20971520;
    public const MAX_RESPONSE = 2097152;
    public const MAX_DATA = 524288;
    public const JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;
    public const MESSAGES = [
        ''                             => '',
        'invalid_request'              => 'Die Angaben sind ungültig oder zu umfangreich. Bitte die Auswahl neu laden.',
        'workspace_corrupt'            => 'Der gespeicherte Arbeitsbereich konnte nicht gelesen werden. Er wurde nicht ersetzt. Bitte die Sicherung und Modulversion prüfen.',
        'workspace_version'            => 'Diese Version des Arbeitsbereichs wird noch nicht unterstützt. Die vorhandenen Daten bleiben erhalten.',
        'stale_workspace'              => 'Der Arbeitsbereich wurde inzwischen geändert. Bitte die Übersicht neu laden; Ihre Eingaben wurden nicht übernommen.',
        'stale_system'                 => 'Dieses System wurde inzwischen geändert. Bitte seinen aktuellen Entwurf neu laden; Ihre Eingaben wurden nicht übernommen.',
        'connection_unavailable'       => 'Eine ausgewählte Z2M-Verbindung fehlt oder ist nicht eindeutig eingerichtet. Bitte Verbindung, Gateway und Basistopic prüfen.',
        'binding_changed'              => 'Die Verbindung oder ihre Zuordnung hat sich geändert. Der Entwurf bleibt erhalten. Bitte die Systemzuordnung ausdrücklich mit einer neuen Generation bestätigen.',
        'system_missing'               => 'Das ausgewählte System gehört nicht zu diesem Arbeitsbereich.',
        'system_limit'                 => 'Es können höchstens acht Z2M-Systeme eingerichtet werden. Der Arbeitsbereich bleibt unverändert.',
        'duplicate_systemId'           => 'Mehrere Verbindungen verwenden dieselbe Systemkennung. Bitte die Zuordnung prüfen.',
        'duplicate_baseTopic'          => 'Mehrere Systeme verwenden dasselbe Basistopic. Im gemeinsamen Automationsverbund benötigt jedes System ein eigenes Topic.',
        'duplicate_connectionInstance' => 'Eine Z2M-Verbindung wurde mehrfach ausgewählt.',
        'remove_blocked'               => 'Ein vorhandenes System wird hier nicht entfernt. Dafür sind eine Sicherung und die Prüfung seiner Verweise erforderlich.',
        'open_input'                   => 'Es gibt noch nicht übernommene Eingaben. Bitte zuerst im bisherigen System weiterbearbeiten oder die Eingaben ausdrücklich verwerfen.',
        'invalid_data'                 => 'Der lokale Entwurf oder seine Bearbeitungsdaten sind ungültig. Es wurde nichts ersetzt.',
        'catalog_unavailable'          => 'Für diese Verbindung fehlen bestätigte Gerätedaten. Bitte dort die Gerätedaten aktualisieren. Vorhandene Entwürfe bleiben erhalten.',
        'catalog_changed'              => 'Die Gerätedaten oder die Verbindung haben sich geändert. Bitte den Gerätekatalog dieses Arbeitsbereichs erneut einlesen.',
        'remote_source'                => 'Auslöser und Sensorbedingungen müssen zum ausführenden Z2M-System gehören.',
        'permission_missing'           => 'Das Zielsystem erlaubt noch keine Aktionen aus diesem System. Die Empfangsfreigabe muss ausdrücklich im Zielentwurf eingetragen werden.',
        'response_limit'               => 'Diese Antwort ist zu groß. Bitte weniger Einträge auf einmal laden.',
        'operation_conflict'           => 'Diese Auftragskennung wurde bereits mit anderen Angaben verwendet. Bitte den aktuellen Stand neu laden.',
        'permission_changed'           => 'Die Empfangsfreigaben weichen vom bisherigen Entwurf ab. Bitte ihre Änderung ausdrücklich bestätigen; der vorhandene Entwurf bleibt erhalten.'
    ];

    public static function Empty(): array
    {
        return ['version' => self::VERSION, 'selectedSystem' => '', 'systems' => [], 'receipts' => []];
    }

    public static function Data(): array
    {
        return ['document' => null, 'selection' => ['rule' => '', 'sequence' => ''], 'work' => ['rule' => null, 'sequence' => null]];
    }

    public static function Record(array $binding, string $name): array
    {
        return ['binding' => $binding, 'name' => $name, 'data' => self::Data(), 'catalog' => null, 'nativeBindings' => []];
    }

    public static function Fingerprint(array $value): string
    {
        if (isset($value['systems'], $value['receipts'])) unset($value['receipts']);
        return hash('sha256', json_encode($value, self::JSON_FLAGS));
    }

    public static function Dirty(array $record): bool
    {
        return ($record['data']['work']['rule']['dirty'] ?? false) || ($record['data']['work']['sequence']['dirty'] ?? false);
    }

    public static function ID(mixed $id): bool
    {
        return is_string($id) && preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $id) === 1;
    }

    public static function DataError(mixed $data): string
    {
        if (!is_array($data) || !self::Keys($data, ['document', 'selection', 'work'])
            || strlen(json_encode($data, self::JSON_FLAGS)) > self::MAX_DATA) return 'invalid_data';
        $document = $data['document'];
        $ids = ['rule' => [], 'sequence' => []];
        if ($document !== null) {
            if (!is_array($document) || !self::Keys($document, ['yaml', 'editorDraft']) || !is_string($document['yaml'])
                || !ExtensionContract::ValidYamlText($document['yaml']) || !is_array($document['editorDraft'])) return 'invalid_data';
            $editor = $document['editorDraft'];
            if (!self::Keys($editor, ['configuration', 'baseRevision', 'baseChecksum', 'draftChecksum'])
                || !ExtensionContract::Revision($editor['baseRevision']) || !ExtensionContract::Checksum($editor['draftChecksum'])
                || $editor['draftChecksum'] !== hash('sha256', $document['yaml'])
                || ($editor['baseRevision'] === 0 ? $editor['baseChecksum'] !== null : !ExtensionContract::Checksum($editor['baseChecksum']))
                || !is_array($editor['configuration'])) return 'invalid_data';
            $configuration = $editor['configuration'];
            if (($configuration['schema_version'] ?? null) !== 1 || !ExtensionContract::Revision($configuration['revision'] ?? null) || $configuration['revision'] < 1
                || !is_array($configuration['automations'] ?? null) || !array_is_list($configuration['automations']) || count($configuration['automations']) > 100
                || strlen(json_encode($configuration, self::JSON_FLAGS)) > ExtensionContract::MAX_YAML_BYTES || SequencePlan::Error($configuration) !== '') return 'invalid_data';
            if (array_key_exists('mesh_peers', $configuration)) {
                $peers = $configuration['mesh_peers'];
                if (!is_array($peers) || !array_is_list($peers) || count($peers) > 8) return 'invalid_data';
                foreach ($peers as $peer) if (!SystemContract::Topic($peer)) return 'invalid_data';
                if (count(array_unique($peers)) !== count($peers)) return 'invalid_data';
            }
            foreach (['automations' => 'rule', 'sequences' => 'sequence'] as $field => $kind) foreach ($configuration[$field] ?? [] as $entry) {
                if (!is_array($entry) || !self::ID($entry['id'] ?? null) || isset($ids[$kind][$entry['id']])) return 'invalid_data';
                $ids[$kind][$entry['id']] = true;
            }
        }
        foreach (['selection', 'work'] as $field) if (!is_array($data[$field]) || !self::Keys($data[$field], ['rule', 'sequence'])) return 'invalid_data';
        foreach (['rule', 'sequence'] as $kind) {
            $selection = $data['selection'][$kind];
            if (!is_string($selection) || ($selection !== '' && !isset($ids[$kind][$selection]))) return 'invalid_data';
            $work = $data['work'][$kind];
            if ($work !== null && (!is_array($work) || !self::Keys($work, ['dirty', 'input']) || !is_bool($work['dirty'])
                || !is_array($work['input']) || strlen(json_encode($work, self::JSON_FLAGS)) > 65536)) return 'invalid_data';
        }
        return '';
    }

    public static function Error(mixed $state): string
    {
        if (!is_array($state) || !self::Keys($state, ['version', 'selectedSystem', 'systems', 'receipts'])) return 'workspace_corrupt';
        if ($state['version'] !== self::VERSION) return 'workspace_version';
        if (!is_string($state['selectedSystem']) || !is_array($state['systems'])) return 'workspace_corrupt';
        if (!is_array($state['receipts']) || count($state['receipts']) > 32) return 'workspace_corrupt';
        foreach ($state['receipts'] as $key => $receipt) {
            if (!ExtensionContract::Checksum($key) || !is_array($receipt) || !self::Keys($receipt, ['request', 'response'])
                || !ExtensionContract::Checksum($receipt['request']) || !is_string($receipt['response']) || strlen($receipt['response']) > 16384) return 'workspace_corrupt';
            $response = json_decode($receipt['response'], true, 16);
            if (!is_array($response) || ($response['version'] ?? null) !== self::VERSION || ($response['ok'] ?? null) !== true || ($response['error'] ?? null) !== '') return 'workspace_corrupt';
        }
        $bindings = [];
        foreach ($state['systems'] as $id => $record) {
            if (!SystemContract::SystemID($id) || !is_array($record) || !self::Keys($record, ['binding', 'name', 'data', 'catalog', 'nativeBindings'])
                || !is_array($record['binding']) || ($record['binding']['systemId'] ?? null) !== $id
                || !is_string($record['name']) || strlen($record['name']) > 480 || preg_match('/[\x00-\x1f\x7f]/', $record['name'])
                || self::DataError($record['data']) !== '' || !is_array($record['nativeBindings'])
                || ($record['catalog'] !== null && !is_array($record['catalog']))) return 'workspace_corrupt';
            $bindings[] = $record['binding'];
        }
        $error = SystemContract::RegistryError($bindings);
        if ($error !== '') return $error;
        return ($state['selectedSystem'] === '' ? $state['systems'] === [] : isset($state['systems'][$state['selectedSystem']])) ? '' : 'workspace_corrupt';
    }

    public static function Allows(array $source, array $destination): bool
    {
        $peers = $destination['data']['document']['editorDraft']['configuration']['mesh_peers'] ?? [];
        return is_array($peers) && in_array($source['binding']['baseTopic'], $peers, true);
    }

    public static function Permissions(array $data): array
    {
        return array_intersect_key($data['document']['editorDraft']['configuration'] ?? [], ['mesh_peers' => true]);
    }

    private static function Keys(array $data, array $keys): bool
    {
        return count($data) === count($keys) && array_diff(array_keys($data), $keys) === [];
    }
}
