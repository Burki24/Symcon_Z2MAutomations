# ADR 0025: Wiederverwendbare Aktionsfolgen im Browser

- Status: angenommen
- Datum: 04.11.2026
- Erweitert: ADR 0023; unveränderter Z2M-Vertrag

## Anlass

Vorhandene Folgen lassen sich im Browser anzeigen und als Regelaktion auswählen.
Auch ihre Definition soll ohne Wechsel zum nativen Editor bearbeitbar sein.

## Entscheidung

Der bestehende Aktionseditor erhält einen Folgenmodus. Er verwendet dieselben
Geräte-, Kanal-, Wert-, Szenen- und Wartezeitfelder sowie die vorhandenen
Schaltrechte. Folgen enthalten ausschließlich set, toggle und scene; keine
verschachtelten Folgenaufrufe. Die Oberfläche nennt die Anzahl verwendender
Automationen. Es gibt keinen eigenen Aktivierungsschalter für Definitionen.

Eine feste POST-sequence-Route akzeptiert requestId, context, sequenceId und
changes. changes enthält ausschließlich name und actions. Leere sequenceId
erstellt eine serverseitig benannte Identität, eine vorhandene Identität wird
beibehalten. Unbekannte Identitäten führen zum Konflikt, nicht zur Neuanlage.
Neue Definitionen benötigen beide Felder. Eine unveränderte Definition wird
nicht erneut geschrieben. Keine Regel, Freigabe oder Revision kann frei gesetzt werden.

Neue 15-Minuten-Sitzungen erhalten eine eigene sequences-Berechtigung. Alte
Lese-/Regelzugänge werden nicht aufgewertet. Herkunfts- und Medientypprüfung,
64-KiB-Grenze, Kontextvergleich und Transportlock gelten wie bei der Regelbearbeitung.
Begrenzte Quittungen verhindern doppelte Anlage nach verlorener Antwort.
Zwischenzeitlich geänderte Entwürfe oder offene native Eingaben werden erhalten.

Geänderte Schritte durchlaufen die aktuelle native Aktionsprüfung. Ganze nicht
mehr bearbeitbare Bestandsbereiche bleiben geschützt; Namensänderungen erhalten
sie unverändert. Vor CommitEditorChange prüft SequencePlan den gesamten Entwurf,
insbesondere die expandierte Schrittgrenze und kombinierten Wartezeiten aller
Verwender. Höchstens 32 Definitionen mit jeweils einer bis 16 Aktionen.
CommitEditorChange bleibt der einzige Übernahmepfad. Automationsidentitäten,
Regelschalter und Partnerfreigaben bleiben erhalten. Kein MQTT, keine Aktivierung.

Debug nennt Ergebnis, Neuanlage, Schrittzahl und Anzahl verwendender Regeln.
Keine Namen, Gerätekennungen, Werte oder Zugangstoken werden protokolliert.

## Nachweise und Grenzen

Hooktests prüfen Rechte, Formen, Grenzen, Wiederholungen, Konflikte, Schaltrechte
und Erhalt vorhandener Entwurfsdaten. Die lokale Browserprüfung verwendet
synthetische Geräte mit demselben Modul-Hook. Eine echte Testinstallation ist
nach Modulupdate separat zu prüfen. Die Z2M-Erweiterung bleibt unverändert.
