# ADR 0011: Vorhandene Z2M-Szenen aufrufen

Status: lokal implementiert; native Praxisprüfung offen.

## Entscheidung

Vorhandene Z2M-Szenen werden als eigene `scene`-Aktion angeboten. Identität sind
System, Geräte-IEEE bzw. Gruppen-ID, Szenen-ID und bei Geräten numerischer
Endpoint. Namen dienen nur der Anzeige. Bestehende Regeln bleiben unverändert.
Szenen erstellen, ändern, löschen oder durch Ermittlung physisch abfragen gehört
nicht zu dieser Ausbaustufe.

Die eigene Metadatenlesung verwendet die Z2M-Schnittstellen `zh.endpoints`,
`zh.members`, Endpoint-ID und `meta.scenes` mit Schlüsseln aus Szenen- und
Gruppen-ID. Geräteszenen gehören zur Gruppe 0. Gruppenszenen stammen aus den
Metadaten ihrer Mitglieder mit passender Gruppen-ID. Nur der zum Geräteendpoint
passende schaltbare state-Kanal erlaubt eine Editoraktion; Gruppenszenen verwenden
die vorhandene schaltbare Gruppenvariable. Anzeigen bleiben ausgeschlossen.
Die vorhandenen Formular-, Datenfluss- und DebugFlow-Helper werden unverändert
verwendet. Die Z2M-spezifische Zuordnung bleibt librarylokal.

Die Erweiterung prüft lokale Szenen vor Speicherung und Aktivierung sowie
unmittelbar vor Ausführung. Entfernte Szenen werden mit der vorhandenen
Mesh-Einmalfreigabe an das Zielsystem übertragen und dort neu geprüft. Es werden
keine frei wählbaren Topics, Namen oder Code übertragen. Der native Aufruf nutzt
`scene_recall`; bei Geräten bleibt der Endpoint im Pfad ausdrücklich erhalten.
Eine fehlende Szene bricht die Folge verständlich ab. Keine automatische
Wiederholung und keine Gerätebestätigung durch bloße Befehlsübergabe.

## Quellen und Nachweise

Schnittstellen geprüft am 01.11.2026:

- [Offizielle Z2M-Szenen-API](https://www.zigbee2mqtt.io/guide/usage/scenes.html)
- [Z2M-Metadatenveröffentlichung](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/extension/bridge.ts)
- [Endpoint- und Gruppen-Szenenmetadaten](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/util/utils.ts)
- [Native Endpoint-Befehlsübergabe](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/extension/publish.ts)

Eigene Implementierung ohne Übernahme fremder Automationslogik. Synthetische
Tests prüfen Auswahl, Vorbelegung, Rechteentzug, Schema, Speicherung, lokale und
entfernte Ausführung, Verzögerungen sowie gelöschte Szenen. Kein Live-Zugriff.
