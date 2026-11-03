# Eigene Zigbee2MQTT-Erweiterung

Die optionale Regelangabe `retrigger` steuert erneute Auslösungen während einer
laufenden Folge: ignorieren, serialisiert von vorn beginnen oder nur die aktuelle
Wartezeit zurücksetzen. Laufende Übergaben werden abgewartet; unsichere Ergebnisse
werden nicht automatisch wiederholt. Details und Updategrenzen stehen in
[ADR 0018](../../docs/adr/0018-retrigger-running-automations.md).

Aktionen können über einen gemeinsamen Broker oder eine vorhandene Broker-Bridge
an ein zweites Z2M-System übergeben werden. Der eigene Mesh-Transport nutzt ausschließlich `z2ma/mesh/#`
und verlangt eine aktivierte Empfangsfreigabe auf der Gegenseite. Kurzlebige
Einmalfreigaben verhindern zusätzliche Ausführung durch alte oder doppelte
Execute-Nachrichten. Reale systemübergreifende Abläufe sind dokumentiert;
siehe [ADR 0009](../../docs/adr/0009-cross-system-actions-over-mqtt-bridge.md).

Die eigene Erweiterung empfängt Automationen über MQTT, validiert YAML und
Schema und speichert die letzte gültige Fassung im persistenten Z2M-Datenordner.
Eine gespeicherte Revision kann anschließend ausdrücklich aktiviert werden.
Die Engine unterstützt das bestehende Schema mit Geräte-/Zeittriggern,
Bedingungen und geordneten Set-/Umschaltaktionen. Speichern allein aktiviert nichts.
Das Symcon-Modul bietet Aktivierungs-/Stoppbuttons und den Klick-Editor.
Die Erweiterung stellt dafür einen begrenzten Gerätekatalog aus Z2M-Eigenschaften
und die bereits gespeicherte Konfiguration bereit. Nach der Aktivierung laufen
Regeln ohne Symcon weiter. Mit gespeicherter Wiederanlauf-Freigabe aktiviert
Z2M denselben Regelstand auch nach einem Neustart automatisch. Ohne Freigabe
oder nach bestätigtem Stoppen bleibt die Engine aus; siehe [Laufzeitvertrag](../../docs/RUNTIME.md).

- Hochladbare Einzeldatei: z2m-automations.mjs.
- Automatische Inhaltskennung: `extension_build` in Statusantworten; Generator
  und Prüfung berücksichtigen den gesamten eigenen Code einschließlich Schema
  und Debugkatalog. Details: [ADR 0016](../../docs/adr/0016-content-based-extension-build.md).
- Eigener Quellcode: src/extension.mjs, src/runtime.mjs, src/sequences.mjs,
  src/mesh.mjs und src/scenes.mjs.
- Kanonisches Schema: automations.schema.json, JSON Schema Draft 7.
- Synthetisches Beispiel: examples/automations.yaml.

Das Einzeldatei-Artefakt enthält unser Schema und unseren Code. Ajv und js-yaml
werden aus den vorhandenen Z2M-Abhängigkeiten geladen. Ein separates Hochladen
von Schema- oder Hilfsdateien ist nicht erforderlich. Das Artefakt wird mit
npm run build:extension erstellt und durch npm run check:extension geprüft.

Schnittstellenziel ist Z2M 2.14.2. Praktisch belegt ist 2.14.2-dev; eine eigene
Abnahme der stabilen Ausgabe liegt nicht vor. Die genaue Entwicklungsrevision
ist nicht dokumentiert. Die lokale Testbasis verwendet Node ab 22.2,
js-yaml 5.4.2 und Ajv 8.20.0. Installation, Übertragung und reale Ausführung sind
im jeweils beschriebenen Umfang bestätigt. Siehe
[Versionsübersicht und Grenzen](../../docs/COMPATIBILITY.md).

Der eigene Constructor nutzt die offiziell dokumentierten zehn Z2M-Argumente;
ein optionales elftes Argument ersetzt ausschließlich im Test Dateisystem und
Datenverzeichnis sowie Uhr/Timer. In Z2M leitet sich der Speicherort aus der tatsächlichen Lage
der .mjs unter data/external_extensions ab, auch bei temporären Importkopien.

Details: [YAML-Vertrag](../../docs/YAML_SCHEMA.md),
[MQTT- und Speicherprotokoll](../../docs/EXTENSION_PROTOCOL.md),
[Ausführung und Aktivierung](../../docs/RUNTIME.md).

Verträge und Zuständigkeiten stehen in [Architektur](../../docs/ARCHITECTURE.md).
Keine Implementierung aus fremden Automationsprojekten übernehmen.

## Gruppen und Umschalten

Vorhandene Geräte- und Gruppenszenen werden lesend aus den Endpoint-Metadaten
bereitgestellt. Die eigene `scene`-Aktion verwendet Szenen-ID und bei Geräten
den numerischen Endpoint. Vor jedem nativen `scene_recall` wird die Zuordnung
erneut geprüft, auch auf einem Mesh-Ziel. `scene_unavailable` erklärt eine
inzwischen fehlende Szene. Es werden keine Szenen angelegt oder verändert.
Für diese Aktion müssen Modul und ausführende Erweiterungen aktualisiert sein.

Editorantworten enthalten zusätzlich bis zu 256 Gruppen mit stabiler ID und
Anzeigename. Symcon prüft die zugehörige native Aktionsvariable und bietet
Ein-/Aus-Aktionen an. Die neue Schema-1-Variante `{type: toggle, target, property}`
verwendet `state.get(entity)` des Z2M-Hosts. Ein bekannter ON/OFF-Zustand wird
invertiert und als normaler Set-Wert übergeben. Ohne Cachewert endet die Folge mit
`state_unavailable`; ohne Host-Schnittstelle wird die Aktivierung abgewiesen.
Gruppen werden anhand ihres Aggregatzustands einheitlich ein-/ausgeschaltet.
`toggle` besitzt keine `values`, `set` keine `property`.

## Begrenzte Ablaufaufzeichnung

Die eigene `request/debug`-Operation und `debug_version: 1` erlauben flüchtige,
zeitlich begrenzte MQTT-Aufzeichnungen ohne Änderungen am globalen Z2M-Loglevel.
`src/debug.mjs` und `debug-events.json` werden zusammen mit der Runtime in das
Uploadartefakt eingebettet. Keine zusätzlichen Host-Abhängigkeiten oder Dateien
installieren. Das zugehörige Symcon-Modul empfängt und übersetzt nur diese feste
Diagnose, keine allgemeinen Z2M-Logtexte. Protokoll: [EXTENSION_PROTOCOL.md](../../docs/EXTENSION_PROTOCOL.md).
