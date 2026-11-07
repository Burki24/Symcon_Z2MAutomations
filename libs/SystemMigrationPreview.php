<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** A bounded preview is neither an archive nor a permission to migrate. */
final class SystemMigrationPreview
{
    public const MESSAGES = [
        'owned'              => 'Diese Instanz gehört bereits zu einer Übernahme. Bitte den bestehenden Vorgang fortsetzen oder dessen Rückweg verwenden.',
        'invalid_selection'  => 'Bitte eine bis acht unterschiedliche Automationsinstanzen als Liste ihrer Instanz-IDs auswählen.',
        'source_unavailable' => 'Diese Quelle ist keine erreichbare Automationsinstanz mit unterstützter Bestandsprüfung.',
        'invalid_source'     => 'Bestandsdaten fehlen, sind beschädigt oder haben einen nicht unterstützten Aufbau. Es wird nichts ersetzt.',
        'invalid_binding'    => 'MQTT-Verbindung oder Basistopic fehlen oder werden für die Übernahme nicht unterstützt.',
        'scope_mismatch'     => 'Der Entwurf gehört zu einer anderen Verbindung. Bitte die Zuordnung im bisherigen Modul klären.',
        'draft_mismatch'     => 'YAML-Eingabe und Editorentwurf weichen voneinander ab. Beide Fassungen bleiben erhalten; bitte im bisherigen Modul klären.',
        'open_input'         => 'Im bisherigen Formular gibt es offene Änderungen. Bitte dort übernehmen oder ausdrücklich verwerfen.',
        'busy'               => 'Im bisherigen Modul läuft noch ein Auftrag. Bitte dessen Abschluss abwarten.',
        'duplicate_topic'    => 'Dieses Basistopic ist mehrfach ausgewählt. Bitte die Quellenzuordnung klären; Systeme werden nicht zusammengeführt.',
        'peer_missing'       => 'Ein bisher zugeordnetes Partnermodul ist nicht in dieser Auswahl. Sein Bezug bleibt erhalten und muss vor der Übernahme geklärt werden.',
        'catalog_missing'    => 'Passende Gerätedaten fehlen. Entwürfe bleiben erhalten; Schaltrechte müssen vor der Übernahme neu geprüft werden.'
    ];

    public static function Issue(string $code): array
    {
        return ['code' => $code, 'message' => self::MESSAGES[$code]];
    }

    public static function Build(array $sources): array
    {
        $result = ['contractVersion' => SystemContract::VERSION, 'status' => 'blocked', 'canApply' => false, 'systems' => [], 'issues' => [],
            'notice'                 => 'Nur Bestandsvorschau. Es wurden keine Systeme angelegt, Daten übernommen oder Aufträge an Z2M gesendet.'];
        if (!array_is_list($sources) || count($sources) < 1 || count($sources) > SystemContract::MAX_SYSTEMS) {
            $result['issues'][] = self::Issue('invalid_selection');
            return $result;
        }
        $topics = array_count_values(array_filter(array_column($sources, 'baseTopic'), [SystemContract::class, 'Topic']));
        $ids = array_column($sources, 'sourceInstance');
        foreach ($sources as $source) {
            $issues = $source['issues'];
            if (($topics[$source['baseTopic']] ?? 0) > 1) $issues[] = self::Issue('duplicate_topic');
            if ($source['peerInstance'] > 0 && !in_array($source['peerInstance'], $ids, true)) $issues[] = self::Issue('peer_missing');
            $source['issues'] = $issues;
            $source['status'] = $issues === [] ? 'ready_for_review' : 'blocked';
            $result['systems'][] = $source;
        }
        if (array_filter($result['systems'], static fn (array $s): bool => $s['status'] === 'blocked') === []) $result['status'] = 'ready_for_review';
        return $result;
    }
}
