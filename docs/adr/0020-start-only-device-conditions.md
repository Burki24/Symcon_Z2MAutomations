# ADR 0020: Gerätebedingungen nur beim Start prüfen

- Status: implementiert; Modul-/MQTT-/Speicherpraxis und reale Sensor-/Lichtprüfung mit Verlängerung bestätigt
- Datum: 03.11.2026

## Kontext

Eine Bewegungsmelderregel mit verzögertem Ausschalten darf ihre Helligkeitsgrenze
nicht vor jedem Schritt verlangen: Wird es nach dem Einschalten heller, könnte
sonst der Ausschaltbefehl entfallen. Auch weitere Bewegung soll die laufende
Wartezeit unabhängig von dieser Startvoraussetzung verlängern können.
Zentrale Formular- und Debug-Helper wurden geprüft; die fachliche Unterscheidung
gehört zur lokalen Automationslogik, nicht zu einem neuen gemeinsamen Helper.

## Entscheidung

Eine device_value-Bedingung darf das optionale boolesche Feld start_only tragen.
Fehlend oder false bleibt ihre bisherige fortlaufende Prüfung erhalten. Die
native Bedingungszeile bietet „Nur beim Start“ als getrenntes Häkchen. Es ist
bei neuen Bedingungen aus, wird beim Bearbeiten vorbelegt und gehört zur
geschützten Formulareingabe. Ein unvollständiger alter Speicheraufruf darf
vorhandene Startbedingungen nicht stillschweigend entfernen.

Jeder neue automatische oder manuelle Lauf prüft alle Bedingungen. Unbekannte
Werte bleiben ablehnend. Konkrete Folgeschritte und extend prüfen nur weiterhin
geltende Bedingungen. restart prüft vollständig, sowohl vor seiner Vormerkung
als auch beim eigentlichen neuen Lauf. Zeitfenster und Wochentage unterstützen
diese Ausnahme nicht. Keine zweite Regel, unbedingter Ersatzbefehl oder eigene
Timerlogik; Limits, Stopp, Mesh und Wiederanlauf bleiben erhalten.

Schema 1 erhält eine optionale Angabe. Modul und Erweiterung müssen gemeinsam
aktualisiert werden; ältere Erweiterungen lehnen das unbekannte Feld ab. Keine
Migration oder automatische Änderung gespeicherter Regeln. Der feste Debugkatalog
erklärt das gezielte Auslassen im laufenden Ablauf ohne Werte offenzulegen.

## Nachweis

Synthetische Laufzeittests prüfen unbekannte und zu helle Startwerte, Verlängern
bei steigender Helligkeit, sichere Ausschaltung, erneute Prüfung nach Abschluss,
abgelehnten Neustart, Einzelstart und gemischte fortlaufende Bedingungen. Die
PHP-/JavaScript-Integration prüft native Formularserialisierung, Vorbelegung und
Speicherung einschließlich Geräte- und Zeitbereichen sowie ungültige Flags.
Die folgenden Praxisnachweise ergänzen diese synthetischen Prüfungen.

Nach gemeinsamem Update am selben Tag die Modul-API, reales Formular-JSON und
das tatsächliche Zeilendialog-Skript geprüft. Auswahl und Vorbelegung einschließlich
StartOnly korrekt; Empfänger validiert und speichert die neue Regel. Wiederladen
ist bytegleich, bisherige Regeln unverändert. Dauerhafte Aktivierung einschließlich
gespeicherter Wiederanlaufabsicht bestätigt. Keine neue Neustart- oder reale
Sensor-/Lichtprüfung: Der Anwender verschiebt diese bis zur passenden Helligkeit.

Später am selben Tag bei erfüllter Helligkeitsbedingung mit tatsächlichen
Sensormeldungen geprüft: Einschaltung, erneute Auslösung im selben Lauf und
Ausschaltung nach voller erneuerter Minute. Das Licht bleibt über die alte
Ausschaltfrist hinaus an; kein erneuter Einschaltbefehl und kein Laufzeitfehler.
Startbedingung wird laut Debug vor dem Ausschalten nicht erneut geprüft.
Regel bleibt aktiv, eigene Aufzeichnungen beendet. Ein Helligkeitsanstieg über
die Schwelle während des Laufs wurde real nicht herbeigeführt; diese Grenze ist
weiterhin durch synthetische Tests, nicht durch diesen Geräteversuch abgedeckt.
