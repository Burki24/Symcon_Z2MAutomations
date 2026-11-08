# Automationen ausführen

## Wartende Läufe nach Z2M-Neustart fortsetzen

restart_policy je Regel ist optional: abort (Standard), resume (Restzeit warten;
bei bereits abgelaufener Frist abbrechen), resume_expired (Restzeit warten oder
ausstehenden Schritt nach erneuter Bedingungsprüfung nachholen). Zusätzlich
bleibt der bestätigte systemweite automatische Wiederanlauf erforderlich.

RunJournal speichert wartende Automatikläufe in
data/z2m-automations/running-automations.json. Das begrenzte, atomar geschriebene
Dokument bindet Regelkennung, Laufkennung, expandierten Aktionsschritt und
absolute Frist an Basistopic, Revision und Konfigurationsprüfsumme. Die Ausfallzeit
zählt mit. Vor der Geräte-/Mesh-Übergabe wird der Wartepunkt dauerhaft entfernt;
unbestätigte Übergaben werden nicht wiederholt. Nachfolgende Wartezeiten beginnen
normal. Ein Absturz zwischen Entnahme und Übergabe kann den Schritt verlieren;
exakt einmalige Gerätewirkung wird nicht zugesichert.

Geordnetes Beenden friert Wartepunkte ein. Bestätigter globaler/gezielter Stopp
und geänderte Speicherung entfernen die betroffenen Fortsetzungen. Falsche
Bindung, beschädigte/überlange Datei, ungültige Schritte oder zurückgestellte
Uhr verhindern die Wiederaufnahme. Einzelstarts und Mindestdauern von Auslösern
werden nicht fortgesetzt. Gerätebedingungen benötigen frische Beobachtungen;
start_only bleibt auf den ursprünglichen Start begrenzt. Wiederverwendbare
Folgen behalten ihre expandierte Schrittnummer.

Entscheidung: [ADR 0036](adr/0036-resume-waiting-runs-after-restart.md).

## Bedingungen nur beim Start

`device_value.start_only: true` beschränkt eine Gerätebedingung auf die
Zulassung eines neuen Laufs. Fehlend oder false erhält das bisherige Verhalten.
Automatische und einmalige Starts prüfen alle Bedingungen; unbekannte Werte
berechtigen nicht zum Start. Vor konkreten Aktionen und beim Verlängern wird
eine ausdrücklich so markierte Bedingung nicht erneut geprüft. Andere
Gerätebedingungen, Zeitfenster und Wochentage gelten weiterhin. Ein angeforderter
Neustart prüft alle Bedingungen sowohl beim Vormerken als auch beim tatsächlichen
Neuanfang. Damit kann eine Helligkeitsgrenze den Start begrenzen, ohne den
verzögerten Ausschaltbefehl zu verhindern. Gilt ebenso für aufgelöste Folgen und
Mesh-Aktionen. Stopp und Wiederanlauf behalten ihre bisherigen Grenzen.
Siehe [ADR 0020](adr/0020-start-only-device-conditions.md).

## Erneute Auslösung während eines Laufs

`retrigger` ist eine optionale Schema-1-Regelangabe. Fehlend oder `ignore`
erhält den bisherigen Lauf. `extend` setzt ausschließlich den Timer der gerade
laufenden Schrittwartezeit zurück; ohne diesen Timer keine Änderung. `restart`
wartet laufende Übergaben ab, beendet den Rest und startet mit neuer Laufkennung.
Höchstens ein Neustart ist vorgemerkt; keine parallele Ausführung derselben
Regel. Unsichere oder fehlgeschlagene Übergaben verwerfen den Neustart.

Bedingungen ohne start_only und die bestehende Sekundensperre gelten auch für Erneuerungen.
Eine abgewiesene Erneuerung verändert den vorhandenen Lauf nicht. Bedingungen
werden beim tatsächlichen Neustart vollständig und vor Aktionen ohne start_only nochmals geprüft.
Einzelstarts bleiben bei einer laufenden Regel gesperrt und werden selbst
nicht automatisch erneuert. Stopp, geändertes Speichern und Wiederanlauf
behalten ihre Abbruchsemantik. Details einschließlich Folgenwartezeiten:
[ADR 0018](adr/0018-retrigger-running-automations.md).

## Mehrere Uhrzeiten und unabhängige Wochentage

Jeder `time`-Auslöser einer Regel besitzt wie bisher seine eigene Tagesmarke.
Mehrere Uhrzeiten lösen unabhängig dieselbe Regel aus. Eine doppelte lokale
Stunde bei der Zeitumstellung wird je Auslöser nicht wiederholt; verpasste
Uhrzeiten werden nicht nachgeholt. Parallelitäts- und Sperrzeitregeln bleiben
unverändert. Eine weitere Uhrzeit verwendet die gewählte Erneuerungsart,
ohne einen zweiten gleichzeitigen Lauf derselben Regel anzulegen.

Die `weekday`-Bedingung verwendet den aktuellen Kalendertag ihrer expliziten
Zeitzone. Sie benötigt weder einen Zeit-Auslöser noch ein Zeitfenster. Sie wird
beim Auslöser, Einzelstart und vor jedem konkreten Aktionsschritt erneut geprüft.
Damit kann ein Tageswechsel während einer Verzögerung die restlichen Aktionen
verhindern. `time_window.weekdays` verwendet weiterhin den Starttag eines
Nachtfensters. Beides sind eigenständige UND-Bedingungen; keine automatische
Umdeutung vorhandener Regeln. Details: [ADR 0017](adr/0017-independent-weekdays-and-clock-list.md).

## Wiederverwendbare Aktionsfolgen

Ein `sequence`-Schritt löst eine Definition aus demselben gespeicherten Stand
innerhalb des aufrufenden Regellaufs auf. Maximal 16 konkrete Schritte je Regel,
keine Verschachtelung. Die Wartezeit am Aufruf wird zur ersten enthaltenen
Wartezeit addiert. Bedingungen gelten vor jedem konkreten Schritt, einschließlich
entfernter Aktionen und Szenen. Laufzeit-, Parallelitäts- und Befehlslimits
bleiben unverändert. Einzelstart verwendet ebenfalls diese Auflösung.

Ein gezielter Stopp beendet nur den adressierten Lauf einschließlich seiner
noch ausstehenden enthaltenen Schritte. Andere Regeln dürfen dieselbe Definition
weiter verwenden. Neustart setzt keine unterbrochenen Schritte fort. Der
Laufstatus zählt konkrete Schritte nach Auflösung; Debug ergänzt Definitions-
und enthaltene Schrittposition. Details: [ADR 0015](adr/0015-reusable-action-sequences.md).

## Vorhandene Szenen

Eine `scene`-Aktion wird über dieselbe native Befehlsübergabe wie Set-Aktionen
ausgeführt. Die Erweiterung erzeugt ausschließlich `{scene_recall: scene_id}`;
bei Geräten wird der gespeicherte numerische Endpoint im Zielpfad verwendet.
Lokale Prüfung/Speicherung, Aktivierung und jede tatsächliche Übergabe prüfen die
vorhandene Zuordnung neu. Das Zielsystem prüft entfernte Szenen. Eine gelöschte
Szene beendet die verbleibende Folge mit `scene_unavailable`; kein Retry und
keine Ersatzszene. Verzögerung, Stopp, Begrenzungen und Wiederanlauf bleiben gültig.

## Systemübergreifende Aktionen

`target.system` delegiert Set-/Toggle-Aktionen an das ausgewählte zweite Z2M.
Beide Engines müssen aktiviert sein und sich über `mesh_peers` freigeben.
Die empfangende Engine benötigt dafür keine eingeschaltete eigene Regel.
Lokale Auslöser, Bedingungen und Verzögerungen bleiben auf dem Quellsystem.
Ein Stopp verwirft ausstehende Anfragen und Empfangsfreigaben. Die Folge wartet
vor ihrem nächsten Schritt auf die Bestätigung der nativen Befehlsübergabe.
Fehlende oder verspätete Antworten sind ein Fehler, keine Gerätebestätigung.
Keine automatische Wiederholung unterbrochener Aktionen. Ein Wiederanlauf
aktiviert ausschließlich den freigegebenen Regelstand für neue Auslöser.
Siehe [ADR 0009](adr/0009-cross-system-actions-over-mqtt-bridge.md).

Die Erweiterung kann gespeicherte Schema-1-Automationen ausführen. Sie lädt die
YAML beim Start. Ohne gültige gespeicherte Wiederanlauf-Freigabe bleibt sie inaktiv. Speichern, Validieren und
Aktivieren sind getrennte Vorgänge. Die Symcon-Oberfläche bietet dafür eigene
Aktivierungs-/Stoppbuttons mit erneuter Statusbestätigung sowie einen ersten
Klick-Editor; siehe [Bedienung](../Z2MAutomations/README.md). Die folgenden
Erweiterungsbefehle beschreiben den dabei verwendeten Transportvertrag.

## Aktivieren und stoppen

1. YAML über das Modul prüfen und speichern.
2. Eine frische `request/status`-Anfrage senden. Die korrelierte Antwort enthält
   `session`, `stored_revision`, `checksum` und `activation_token`.
3. Innerhalb von 30 Sekunden an `<Basistopic>/symcon/automations/request/activate`
   folgendes JSON ohne Retain senden; die Platzhalter sind aus genau dieser
   Statusantwort zu übernehmen:

```json
{
    "protocol_version": 1,
    "transaction": "synthetic-activate-1",
    "session": "CURRENT_SESSION",
    "expected_revision": 1,
    "checksum": "EXACT_STORED_SHA256",
    "activation_token": "FRESH_ONE_USE_TOKEN"
}
```

Die passende `response/activate` enthält `status: ok`, `executing: true`,
`active_revision` und `active_checksum`. Es werden nur Regeln mit `enabled: true`
berücksichtigt. Quellen, Geräteziele und Gruppen müssen in Z2M vorhanden sein.
Aktivierung selbst sendet keinen Schaltbefehl und wertet keinen alten Zustand aus.
Auch eine leere Liste kann aktiviert sein; `enabled_count` zeigt die Regelanzahl.

`request/deactivate` verlangt nur `protocol_version`, `transaction` und `session`.
Die passende Antwort bestätigt `executing: false`. Die Sitzung wird geprüft;
explizites `retain: true` wird abgelehnt. Der native Z2M-Eventbus reicht das
Retain-Bit nicht allgemein weiter. Deshalb erfordert Aktivierung zusätzlich die
frische Einmalkennung; alte retained Statusmeldungen enthalten sie nicht.
Jede aktive Statusabfrage ersetzt die vorherige Kennung, jeder Aktivierungsversuch
verbraucht sie. Nach verlorener Antwort zuerst den Zustand abfragen statt blind
denselben Auftrag zu wiederholen. Die Kennung ist keine Zugriffskontrolle.

Zusätzliche Fehler: `activation_expired`, `checksum_mismatch`, `unknown_entity`,
`runtime_unavailable`. Sie ändern keine YAML-Datei. Beschädigte Dateien und
`recovered_read_only` sperren die Aktivierung.

## Ausführungsregeln

- Trigger einer Regel sind alternativ (ODER), Bedingungen gemeinsam (UND).
- Geräte-Trigger verwenden nur die im Ereignis enthaltene `payload`-Eigenschaft
  eines bekannten Geräts. Ein Wert nur im zusammengeführten `message` löst nichts
  aus. Gruppenereignisse, `publishCached` und `lastSeenChanged` sind keine Trigger.
- `eq`/`ne` vergleichen streng typgleich; keine Umwandlung von `"true"` oder
  numerischen Strings. Größenvergleiche verlangen endliche Zahlen. Fehlende
  oder komplexe Vergleichswerte erfüllen auch `ne` nicht.
- Ohne `for_seconds` ist jeder frische passende Bericht ein Ereignis. Wiederholte
  Tastendrücke mit demselben Aktionswert bleiben damit möglich. Pro Regel gilt
  mindestens eine Sekunde Abstand. Während einer laufenden Folge entscheidet
  `retrigger`; nur bei `restart` wird höchstens ein neuer Lauf vorgemerkt.
- `for_seconds` misst eine durchgehend passende Bedingung ab dem ersten neuen
  Bericht. Weitere passende Berichte setzen den Timer nicht zurück. Ein falscher
  Wert verwirft ihn. Pro zusammenhängender Wahrphase wird höchstens einmal
  ausgelöst; zum erneuten Scharfschalten ist ein falscher Bericht erforderlich.
- Gerätebedingungen verwenden nur seit Aktivierung beobachtete skalare Werte.
  Ein unbekannter Zustand erfüllt die Bedingung nicht. Es gibt keinen heimlichen
  `/get`-Auftrag, keine erfundene Verfügbarkeit und keine zugesicherte Wertfrische.
- Zeittrigger vergleichen einmal pro Sekunde die explizite IANA-Zeitzone.
  Pro lokalem Datum wird jeder Zeittrigger höchstens einmal ausgelöst. Eine bei
  der Herbstumstellung doppelte Uhrzeit läuft einmal; übersprungene Uhrzeiten,
  verpasste Sekunden und Zeiträume eines Stillstands werden nicht nachgeholt.
- Zeitfenster schließen `after` ein und `before` aus. Bei Fenstern über Mitternacht
  bezieht sich `weekdays` auf den Tag, an dem das Fenster begann.
- Aktionen laufen in YAML-Reihenfolge. `delay_seconds` ist die Wartezeit vor der
  jeweiligen Aktion, relativ zum Ende der vorigen Übergabe. Bedingungen werden
  unmittelbar vor jeder Aktion erneut geprüft. Ein fehlendes Ziel oder ein
  Fehler bricht die restliche Folge ab; es gibt keinen automatischen Retry.
- Identität bleibt IEEE-Adresse oder numerische Gruppen-ID. Umbenannte Ziele
  werden zum Sendezeitpunkt neu aufgelöst. Gruppen werden nicht neu angelegt.
  Eigenschaftswerte setzt der native Z2M-Converter um; eine vollständige Prüfung
  gegen alle Geräte-Exposes ist nicht Teil der Engine.
- Set-Aktionen gehen über `eventBus.emitMQTTMessage` direkt an Z2Ms native
  Befehlsverarbeitung, mit vollständigem Basistopic und regulärer `/set`-Nachricht.
  Es wird kein Set-Befehl über den Broker zurückgeschleift. Fehlt diese Host-API,
  wird die Aktivierung mit `runtime_unavailable` abgelehnt.

## Status, Grenzen und Neustart

Der Status ergänzt `runtime_version: 1`, `executing`, `active_revision`,
`active_checksum`, `enabled_count` und `runtime_error`. Fehler einer Folge halten
andere Regeln nicht an. `runtime_error` enthält den letzten Laufzeitfehler bis
zur nächsten erfolgreichen neuen Aktivierung. `action_failed`, `action_timeout`
und `rate_limited` beschreiben Übergabefehler, keine Gerätebestätigung.

Es gelten maximal 16 gleichzeitig laufende Regeln, 20 Set-Übergaben pro Sekunde
über alle Regeln und fünf Sekunden Wartezeit auf die Rückgabe der Befehlsübergabe.
Überzählige Läufe oder Aktionen werden verworfen. Die native EventBus-Übergabe
kehrt unmittelbar zurück; sie wartet nicht auf Converter oder Zigbee-Antworten.
Spätere Converter-/Gerätefehler stehen im Z2M-Protokoll und werden nicht als
bestätigte `runtime_error`-Gerätefehler ausgegeben. Der Übergabe-Timeout bedeutet
nicht, dass ein bereits gestarteter Auftrag zurückgenommen wurde.
Ein Stopp verhindert weitere Übergaben, kann aber keinen schon gesendeten
Zigbee-Befehl zurückrufen. Trigger, Timer und Wiederholungen werden nicht auf Platte
geschrieben. Nach Neustart oder Erweiterungsupdate kann derselbe freigegebene Regelstand
automatisch starten; siehe Wiederanlaufvertrag unten. Geänderte gespeicherte YAML
entzieht diese Freigabe und benötigt eine ausdrückliche neue Aktivierung.

Die Engine schreibt sichere deutsche Ereignismeldungen in Z2Ms Debugkanal:
Aktivierung, Stopp, Beginn/Ende einer Folge, Übergabe und feste Fehlercodes.
Keine YAML, Aktionswerte, Sitzungs-/Aktivierungskennungen oder fremden
Exception-Texte. Ein defekter Logger unterbricht die Ausführung nicht.

## Nachweise

`tests/runtime.test.mjs` prüft das erzeugte Einzeldatei-Artefakt mit synthetischen
Geräten, nativer Eventbus-Form, virtueller Zeit und echtem temporärem YAML-Speicher.
`tests/extension.test.mjs` und die PHP-/JavaScript-Integration bleiben erhalten.
Installation, Speicherung und reale Geräteausführung sind inzwischen praktisch
belegt. Einzelheiten und Grenzen stehen in [DEVELOPMENT.md](DEVELOPMENT.md) und
der [Versionsübersicht](COMPATIBILITY.md).

Für weitere eigene Praxisversuche kann
[simple-device.yaml](../libs/z2m/examples/simple-device.yaml) als synthetische
Vorlage dienen. Geräte gezielt auswählen, Ausgangsbetrieb sichern, prüfen,
speichern und erst anschließend ausdrücklich aktivieren.

Geprüfte native Schnittstellen als Informationsquelle, ohne Übernahme von Code:
[Z2M EventBus](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/eventBus.ts),
[Controller-Ereignis](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/controller.ts),
[native Set-Verarbeitung](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/extension/publish.ts).

## Umschalten

Eine `toggle`-Aktion liest unmittelbar vor ihrer Übergabe den aktuellen
Z2M-Cachezustand ihrer `property` (state bzw. einzelner state-Kanal).
ON wird zu OFF, OFF zu ON. Das gilt auch für den Aggregatzustand einer Gruppe;
alle Mitglieder erhalten denselben Zielwert. Ohne bekannten Zustand endet die
Aktionsfolge mit `state_unavailable`. Die Laufzeit wartet weiterhin nicht auf
eine physische Gerätebestätigung. Ein abweichender oder veralteter Symcon-Wert
wird nicht zur Berechnung verwendet.

## Nur bei Wertänderung

Geräteauslöser können `on_change: true` enthalten. Ein passender Bericht startet
nur dann einen Lauf, wenn sich der Wert gegenüber dem zuvor bekannten Wert
unterscheidet. Der Z2M-Cache initialisiert diese Beobachtung bei Aktivierung;
fehlt dort eine Eigenschaft (z. B. nicht gespeicherte Tastendrücke), zählt ihr
erster frischer Bericht als Änderung. Cacheveröffentlichungen ändern diesen
Vergleich nicht. Stopp löscht die Beobachtung. Bedingungen verwenden weiterhin
ihr eigenes, aus frischen Berichten aufgebautes Wertebild.

## Dauerhafter Betriebszustand und Wiederanlauf

Aktivierung akzeptiert zusätzlich `resume_after_restart` als Boolean (Default true).
Das Modul überträgt seine standardmäßig eingeschaltete Property `ResumeAfterRestart`
bei ausdrücklicher Aktivierung. Nur die empfangene Option gilt für Z2M; eine lokale
Formularänderung allein verändert keine laufende Engine.

`execution-state.json` liegt neben der YAML im Erweiterungsverzeichnis. Version 1
speichert ausschließlich System-Basistopic, Freigabe enabled, Betriebswunsch active,
Revision und Prüfsumme. Keine Timer, Sensorwerte, Tokens oder MQTT-Aufträge werden
persistiert. Atomarer Dateiaustausch mit vorherigem Dateisync verwendet denselben
eigenen Speichercode wie YAML. Ein neuer Regelstand disarmiert die Freigabe vor
seiner Speicherung. Identisches Speichern erhält die aktive Freigabe.

Aktivieren disarmiert zunächst den alten Betriebswunsch, validiert die Laufzeit
und bestätigt erst nach dauerhafter Speicherung die aktive Freigabe. Stoppen
beendet die Laufzeit unmittelbar und bestätigt den dauerhaften Stopp erst nach
Speicherung. Ein Schreibfehler wird als storage_error gemeldet; der bisherige
Dateistand kann dann nach einem Neustart weiterhin gelten. Es gibt keine falsche
Erfolgsmeldung und keinen automatischen Schreib-Retry.

Beim Start werden YAML und die begrenzte Zustandsdatei neu gelesen und geprüft.
Nur enabled=true, active=true, dasselbe System und exakt dieselbe Revision/Prüfsumme
bei storage=ok erlauben die normale native Aktivierung einschließlich Mesh-Prüfung.
Fehlende, beschädigte oder fremde Freigaben, wiederhergestellte YAML-Backups und
fehlende Ziele starten nichts. Ohne Freigabedatei erfolgt keine Migration eines
vermuteten alten Betriebswunsches. Neue Sitzungen und Mesh-Freigaben bleiben frisch;
Startbefehle stehen in derselben Warteschlange wie nachfolgende Bedienaufträge.
Ein expliziter Stopp während des Starts hat damit Vorrang vor späteren Auslösern.

Status enthält resume_version=1, resume_enabled, resume_status und resume_error.
Zustände: unconfigured, stopped, disabled, armed, resumed, blocked. blocked trägt
einen festen Fehlercode, die übrigen einen leeren Fehlertext. Symcon wertet diese
Felder aus und zeigt die bestätigte Einstellung bzw. den Abbruchgrund an.

Neustart und Neuladen der Erweiterung sind gleich behandelt. Es gibt keine
Fortsetzung unterbrochener Folgen, kein Nachholen verpasster Schaltzeiten und keine
Initialauslösung durch alte Cachewerte. Bedingungen warten weiterhin auf frische
Beobachtungen; Tages-/Timerhistorien werden nicht über Neustarts hinweg gespeichert.

## Sonnenereignisse

Die eigene Berechnung läuft im Z2M-Prozess ohne HTTP, Fremdbibliothek oder
Symcon-Nachlieferung. Grundlage sind die veröffentlichten
[NOAA-Gleichungen](https://gml.noaa.gov/grad/solcalc/solareqns.PDF).
Sie ergeben Näherungen bei einem Apparenthorizont von 90,833 Grad, ohne
Höhen-/Gelände-/Wetterkorrektur. Die Ausgabe wird auf volle Minuten gerundet;
keine Zusage sekundengenauer physischer Beobachtung, besonders an polaren Übergängen.

Je Auslöser bleibt nur das begrenzte Berechnungsfenster für den aktuellen
Ereignistag und Nachbartage im Speicher. Das deckt Versätze über Mitternacht ab.
Ein fehlender Auf-/Untergang wird ohne Ersatzzeit übersprungen. Keine Nachholung
nach Aktivierung oder Uhrsprung. Auslösung erfolgt an der berechneten absoluten
Sekunde, höchstens einmal pro zugeordnetem Ereignistag und Auslöser. Der
Ereignistag bezeichnet den lokalen Tag vor Anwendung des Versatzes.
Uhr-Rückstellung wiederholt keinen bereits bearbeiteten Tag derselben Aktivierung.
Neustart stellt nur den gespeicherten Betriebswunsch her und wartet auf neue
Zeitpunkte. Bedingungen und Aktionspfad einschließlich Mesh sind unverändert.

## Einmalige Läufe und gezielter Stopp

Die ausgewählte gespeicherte Regel darf unabhängig von enabled und dem globalen
Automatikbetrieb einmal laufen. Bedingungen gelten vor dem Start und erneut
vor jedem Aktionsschritt. Anfangswerte manueller Läufe stammen aus Z2Ms bekanntem
Gerätezustand; unbekannte oder nicht skalare Werte sperren die Aktion. Frische
Berichte aktualisieren die eigenen Bedingungswerte, ohne die Frischeanforderung
der automatischen Regeln zu verändern. Zeitbedingungen gelten zum jeweiligen
Ausführungszeitpunkt. Die normalen Trigger werden durch den Einzelstart nicht
aktiviert oder simuliert. Szenen, Verzögerungen und Partneraktionen laufen über
denselben Aktionspfad und dieselben Limits.

stop_rule verlangt die genaue Lauf-UUID und verwirft nur dessen Wartezeiten
und Mesh-Wartevorgänge. Bei bereits gesendeten Befehlen ist keine Rücknahme
möglich. Spätere normale Auslöser dürfen die Regel erneut starten. Geänderte
Speicherung, globaler Stopp oder Neustart beenden auch manuelle Läufe. Ein
identisches Speichern erhält den vorhandenen Betrieb wie bisher. Ein manueller
Lauf selbst wird nie persistent fortgesetzt und ändert keinen Betriebswunsch.

Pro gespeicherter Regel bleibt nur der letzte Zustand mit Schrittposition:
running, completed, skipped, failed oder stopped. completed bestätigt die
Übergaben, keine physische Gerätewirkung. skipped kann nach bereits ausgeführten
Schritten entstehen, wenn eine Bedingung vor dem nächsten Schritt nicht mehr
erfüllt ist. Ein vor dem Start unbekannter Bedingungswert erzeugt keine Aktion.
Die globale ExecutionActive-Variable bezeichnet weiterhin den Automatikbetrieb.
Details: ADR 0014 und Bedienung in der Modul-README.
