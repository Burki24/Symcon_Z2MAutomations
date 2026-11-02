# Automationsschema Version 1

## Aktionen im zweiten System

Optionales Dokumentfeld `mesh_peers`: bis zu acht eindeutige Basistopics,
jeweils 1–128 Zeichen aus Buchstaben, Ziffern, Unterstrich und Bindestrich,
getrennt durch einzelne `/`. Der Klick-Editor verwaltet einen ausgewählten
Partner. Alle neuen Felder sind optional; lokale Dokumente bleiben gültig.

Ein Geräte- oder Gruppenziel kann zusätzlich `system` mit dem Basistopic des
Zielsystems enthalten, beispielsweise `{type: group, id: 7, system: synthetic/b}`.
Ohne `system` bleibt es ein lokales Ziel. Bei eingeschalteten Regeln muss das
Zielsystem in `mesh_peers` enthalten sein. Das empfangende Dokument muss
umgekehrt die Quelle freigeben und ausdrücklich aktiviert sein. Ein deaktivierter
Entwurf darf entfernte Referenzen behalten, ohne eine Empfangsfreigabe zu erteilen.
Auslöser und Bedingungen bleiben lokal. Siehe [ADR 0009](adr/0009-cross-system-actions-over-mqtt-bridge.md).

Die kanonische maschinenlesbare Definition steht in
[automations.schema.json](../libs/z2m/automations.schema.json).
Sie wird unverändert in das eigene Einzeldatei-Artefakt eingebettet.
Die Erweiterung validiert die gespeicherten Daten erneut beim Start.

Empfang und Speicherung aktivieren keine Ausführung, auch wenn eine Regel
enabled: true enthält. Die eigene Engine führt ausdrücklich aktivierte Revisionen
aus; [Laufzeitvertrag](RUNTIME.md). Quellen und Ziele müssen beim Aktivieren
vorhanden sein. Zulässige Schaltwerte setzt der native Z2M-Converter um;
eine vollständige Prüfung aller Exposes erfolgt nicht.

## Dokument

```yaml
schema_version: 1
revision: 1
automations: []
```

Alle drei Felder sind erforderlich. revision ist eine positive sichere Ganzzahl
bis 9007199254740991. Die leere Liste ist eine gültige Konfiguration.
Ein [vollständiges synthetisches Beispiel](../libs/z2m/examples/automations.yaml)
zeigt Geräte- und Zeit-Auslöser, Zeitfenster und Geräte-/Gruppenaktionen.

Unbekannte Felder sind in allen strukturierten Objekten unzulässig. Keine
Skripte, Code-Ausdrücke, Dateipfade, frei wählbaren MQTT-Topics oder Referenzen
auf fremde Szenen. Der Vertrag ist eigenständig und kein Importformat des
Referenzprojekts Luligu/zigbee2mqtt-automations.

## Automation

| Feld | Inhalt |
| --- | --- |
| id | Eindeutiger stabiler Schlüssel, 1–64 Zeichen; beginnt mit a–z, danach a–z, 0–9, _ oder - |
| name | Anzeigename, 1–120 Zeichen; Namen dürfen sich wiederholen |
| enabled | Regel wird nach ausdrücklicher Aktivierung der Revision berücksichtigt |
| triggers | 1–8 Auslöser |
| conditions | 0–16 Bedingungen; die leere Liste ist ausdrücklich anzugeben |
| actions | 1–16 Aktionen |

Maximal 100 Automationen pro Dokument. IDs dürfen nicht mehrfach vorkommen.
Die Engine unterstützt alternative Auslöser, gemeinsam zu erfüllende Bedingungen
und geordnete Aktionen. Zeit-, Verzögerungs-, Neustart- und Fehlerverhalten
sind in [RUNTIME.md](RUNTIME.md) festgelegt.

## Auslöser

- type: device mit device, property, operator und value. device ist eine
  kleingeschriebene IEEE-Adresse als String nach 0x plus 16 Hex-Ziffern.
  operator ist eq, ne, gt, gte, lt oder lte. value ist String, Zahl, Boolean oder
  null; bei Größenvergleichen ist eine Zahl erforderlich.
  Optional for_seconds: Ganzzahl 0–86400 für eine durchgehende Mindestdauer.
- type: time mit at im Format HH:MM:SS und expliziter timezone.
  Mehrere time-Einträge derselben Regel sind alternative Startzeiten (ODER).
  Der Klick-Editor unterstützt bis zu acht verschiedene Uhrzeiten mit gemeinsamer
  Zeitzone; es bleibt bei einzelnen time-Auslösern statt einer zweiten Zeitstruktur.

IEEE-Adressen sollten in YAML in Anführungszeichen stehen, damit sie Strings
bleiben. Namen werden nicht als technische Geräteidentität verwendet.

## Bedingungen

- type: device_value mit denselben Vergleichsfeldern wie beim Geräte-Auslöser,
  ohne for_seconds.
- type: time_window mit after, before im Format HH:MM:SS und timezone.
  after und before müssen verschieden sein; Fenster über Mitternacht sind
  zulässig. Optional weekdays: eindeutige Liste mit ISO-Wochentagen 1–7,
  Montag ist 1. Ohne weekdays gilt kein Wochentagsfilter.
- type: weekday mit verpflichtenden weekdays und timezone. weekdays enthält
  1–7 eindeutige ganzzahlige ISO-Wochentage (Montag = 1, Sonntag = 7).
  Die Bedingung prüft den aktuellen lokalen Kalendertag, unabhängig von einem
  Zeitfenster. Sie zählt zur gemeinsamen Grenze von 16 Bedingungen. Auch
  Geräte- und Astro-Auslöser sowie Einzelstarts verwenden diese Bedingung.

Bei time_window beziehen sich dessen optionale weekdays weiterhin auf den
Starttag des Fensters. Eine zusätzliche weekday-Klausel prüft dagegen den
aktuellen Tag; beide müssen erfüllt sein. Bestehende Nachtfenster werden nicht
umgedeutet. Beispiel für zwei Startzeiten nur Montag bis Freitag:

```yaml
triggers:
  - type: time
    at: "07:00:00"
    timezone: Europe/Berlin
  - type: time
    at: "18:30:00"
    timezone: Europe/Berlin
conditions:
  - type: weekday
    weekdays: [1, 2, 3, 4, 5]
    timezone: Europe/Berlin
```

Zeitzonen müssen von Intl.DateTimeFormat des Z2M-Node-Runtimes akzeptiert werden,
beispielsweise Europe/Berlin oder UTC. Es wird nicht implizit die Zeitzone des
Symcon- oder Z2M-Rechners verwendet.

## Aktionen

### Wiederverwendbare Aktionsfolgen

Das optionale Dokumentfeld `sequences` ist eine Liste mit höchstens 32 Einträgen.
Jeder Eintrag enthält genau `id`, `name` und `actions`. ID und Name verwenden
dieselben Grenzen wie Automationen; IDs müssen innerhalb der Definitionen
eindeutig sein. `actions` enthält 1–16 konkrete Set-, Toggle- oder Szenenaktionen.
Aufrufe anderer Folgen sind in einer Definition unzulässig.

Eine Regel ruft eine Definition mit `type: sequence`, `sequence_id` und optional
`delay_seconds` auf. Fehlende Referenzen werden auch in deaktivierten Regeln
abgewiesen. Nach Auflösung aller Aufrufe sind höchstens 16 konkrete Schritte
pro Automation zulässig. Die Wartezeit am Aufruf addiert sich zur ersten
enthaltenen Wartezeit; die Summe darf 86400 Sekunden nicht überschreiten.
Definitionen und Verweise gehören zum selben gespeicherten Dokument.

```yaml
sequences:
  - id: synthetic_lighting
    name: Beispiel-Lichtfolge
    actions:
      - type: set
        target: {type: group, id: 7}
        values: {state: ON}
      - type: set
        target: {type: group, id: 7}
        values: {state: OFF}
        delay_seconds: 5
```

Der entsprechende Eintrag in `automations[].actions` lautet beispielsweise
`{type: sequence, sequence_id: synthetic_lighting, delay_seconds: 2}`.
Die Erweiterung ergänzt ihren Status um `sequence_version: 1`.

### Konkrete Aktionen

`type: scene` ruft eine vorhandene Z2M-Szene auf. Erforderlich sind `target`
wie bei Set-Aktionen und `scene_id` als Ganzzahl von 0 bis 255. Geräteziele
benötigen zusätzlich `endpoint` als Ganzzahl von 1 bis 240; bei Gruppen ist
dieses Feld verboten. Optional ist `delay_seconds` wie bei anderen Aktionen.
`target.system` erlaubt den Aufruf auf dem ausgewählten Partnersystem.
Die Szenen-ID gehört immer zum Ziel und gegebenenfalls Geräteendpoint.
Anzeigenamen sind keine Identität und werden nicht in der Aktion gespeichert.

type: set mit target und values. target ist entweder type: device mit einer
IEEE-Adresse in id oder type: group mit einer numerischen id von 1 bis 65535.
values enthält 1–32 Eigenschaften als JSON-kompatible Werte; verschachtelte
Objekte und Listen sind begrenzt zulässig. Optional delay_seconds: 0–86400.

Eigenschaftsnamen beginnen mit einem Buchstaben, enthalten nur Buchstaben,
Ziffern und Unterstriche und sind maximal 64 Zeichen lang. Die Schlüssel
__proto__, constructor, prototype und YAML-Merge-Schlüssel sind ausgeschlossen.
Strings in Vergleichswerten und Aktionswerten sind maximal 256 Zeichen lang.
Verschachtelte Werteobjekte und Listen haben jeweils maximal 32 Einträge.

## YAML-Grenzen

- Maximal 128 KiB UTF-8 pro YAML-Text; ungültige UTF-8-/Surrogatwerte werden abgewiesen.
- Genau ein Dokument, höchstens 16 Collection-Ebenen und 20000 Parser-Ereignisse.
- Nur JSON-kompatible Typen; keine expliziten Tags oder Direktiven.
- Keine Anker, Aliase, Merge-Keys, doppelten Mapping-Schlüssel oder nicht-textuellen Schlüssel.
- Keine automatische Typkonvertierung durch den Schema-Validator.

Syntax-, Schema- und ergänzende Semantikprüfungen sind getrennte Fehlerklassen.
Fehlerantworten enthalten feste Codes und Meldungen, keine Roh-YAML oder
Dateisystempfade. Eine neue inkompatible Datenstruktur benötigt eine neue
schema_version und eine ausdrücklich geplante Migration.

## Sonnenereignis als Auslöser

Additive Schema-1-Variante, alle sechs Felder erforderlich. Beispiel mit
bewusst synthetischem Standort:

```yaml
triggers:
  - type: sun
    event: sunset
    latitude: 0.125
    longitude: -0.25
    timezone: UTC
    offset_minutes: -30
```

event ist sunrise oder sunset, latitude −90 bis +90 und longitude −180 bis
+180 (endliche Zahlen), offset_minutes eine Ganzzahl von −720 bis +720.
Die IANA-Zeitzone ordnet das Sonnenereignis einem lokalen Kalendertag zu.
Danach wird der Versatz auf die berechnete UTC-Zeit addiert. Rundung,
fehlende Ereignisse und Neustartverhalten stehen in RUNTIME.md.
Der Klick-Editor unterstützt einen Sonnenauslöser je zeitgesteuerter Regel;
weitere Mischungen bleiben über YAML möglich und gegen versehentliche
Vereinfachung im Formular geschützt.

## Einzelsteuerung ohne Änderung des YAML-Schemas

Das Fähigkeitsmerkmal execution_version: 1 und die folgenden Befehle gehören
zum vorhandenen MQTT-Steuerprotokoll, nicht zum Automationsdokument. Pfadpräfix:
<Basistopic>/symcon/automations/. Alle Aufträge enthalten protocol_version: 1
und eine frische transaction; Antworten liegen unter response/<Befehl>.

- request/run_once: session, rule_id, expected_revision, checksum und der nur
  über eine frische korrelierte Statusantwort vergebene activation_token.
  Token wird einmalig verbraucht; Datei und ausgewählte Regel werden geprüft.
- request/stop_rule: session, rule_id, expected_revision, checksum und run
  (UUID des konkreten noch laufenden Ablaufs). Veraltete UUIDs stoppen keinen
  inzwischen neu gestarteten Lauf. Keine persistente Deaktivierung der Regel.
- Frische status-Antworten enthalten zusätzlich execution. Dasselbe begrenzte
  Objekt wird bei Änderungen ohne Retain unter runs und als Kontrollantwort
  geliefert: protocol_version, execution_version, session, revision, checksum,
  sequence und runs. sequence steigt innerhalb derselben Sitzung.
- runs enthält höchstens 100 Einträge mit rule_id, run, revision, checksum,
  state, error und step (0 bis 16). Zustände: running/completed/skipped/failed/
  stopped. error enthält ausschließlich feste bekannte Ursachen, einschließlich
  conditions_not_met. Keine Namen, Werte, Tokens oder frei formulierten Fehler.

run_once funktioniert auch für deaktivierte gespeicherte Regeln. enabled und
resume_after_restart ändern sich dadurch nicht. Ungültige oder retained
Kontrollaufträge, fremde Sitzungen und abweichende Speicherstände werden
abgewiesen. Empfangende Partner benötigen weiterhin aktive Mesh-Freigaben.
