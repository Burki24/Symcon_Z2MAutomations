# ADR 0014: Einzelne gespeicherte Automationen einmal ausführen und stoppen

## Status

Angenommen und implementiert. Anschließend über Test-Symcon am ausdrücklich
freigegebenen realen Z2M schaltfrei geprüft; Nachweise und Grenzen stehen in
DEVELOPMENT.md. Keine tatsächliche Gerätewirkung im Einzelstart-Praxistest.

## Entscheidung

Ein einmaliger Start verwendet die gespeicherte, auf Datenträger erneut geprüfte
Revision. Die ausdrückliche Bedienaktion darf auch eine deaktivierte Regel und
eine Regel bei gestopptem Automatikbetrieb ausführen. Sie ändert weder das YAML
noch Aktivierungskennzeichen, normale Auslöser, globale Empfangsfreigaben oder
den persistenten Wiederanlaufwunsch. Die bestehende Engine verwaltet manuelle
und automatische Läufe gemeinsam. Sperrzeit, Parallelitäts- und Befehlslimit
gelten für beide. Es gibt keinen zweiten Laufzeitbesitzer.

Bedingungen gelten vor dem Start und vor jeder Aktion. Ein manueller Start
nimmt anfangs die zuletzt bekannten skalaren Werte aus Z2Ms Zustandsverwaltung;
fehlende Werte geben keine Aktion frei. Frische Geräteberichte aktualisieren
die bedingungsrelevanten Werte des manuellen Laufs. Der Automatikbetrieb behält
seine Anforderung frischer Beobachtungen nach Aktivierung. Zeitbedingungen
werden mit der aktuellen Uhrzeit und Zeitzone geprüft.

Ein gezielter Stopp adressiert Regel-ID und konkrete Lauf-UUID. Er verwirft nur
deren Wartezeiten und noch wartende Mesh-Anfragen. Ein inzwischen neuer Lauf
wird durch einen veralteten Stopp nicht getroffen. Die Regel bleibt für spätere
normale Auslöser freigegeben. Bereits übergebene Befehle sind nicht rücknehmbar.
Globaler Stopp, geänderte Speicherung und Neustart beenden auch Einzelläufe;
es gibt keine Fortsetzung oder Wiederholung eines solchen Testlaufs.

## Schnittstellen und Zustände

run_once benötigt eine frische einmalige Aktivierungsfreigabe, Sitzung,
Regel-ID sowie erwartete Revision und Prüfsumme. stop_rule verwendet dieselbe
Snapshot-Bindung und die UUID des konkreten Laufs. Beide Operationen verwenden
die bestehenden korrelierten MQTT-Workflows ohne automatische Wiederholung.
execution_version: 1 erkennt die Fähigkeit ausdrücklich; Modul und Erweiterung
müssen gemeinsam aktualisiert werden. Das YAML-Schema bleibt unverändert.

Der nicht-retained Kanal runs, Kontrollantworten und frische Statusantworten
liefern maximal 100 letzte Regellaufzustände, keine Historie oder Rohwerte.
Sitzung, Revision, Prüfsumme und steigende Zustandssequenz verhindern die
Übernahme alter Antworten. Pro Regel bleibt nur der letzte Lauf mit Zustand
running/completed/skipped/failed/stopped, fester Ursache und Schrittposition.
Der Empfänger verwirft fremde Felder und beliebige Fehlertexte. Der Versand
fasst Änderungen bei belegtem Transport zusammen und blockiert keine Aktionen.

Der gemeinsame native Formularbereich wählt eine gespeicherte Regel, startet
nach Bestätigung und stoppt ihren zuletzt bestätigten Lauf. Abweichende lokale
Entwürfe verhindern einen Start. Während der ausgewählte Lauf läuft, ergänzt
eine lesende Statusabfrage alle fünf Sekunden die MQTT-Meldungen. Ein Stopp
hat vor anderen Modulaufträgen Vorrang. Globale Laufzeitvariablen bezeichnen
weiterhin den Automatikbetrieb, nicht einen einzelnen Testlauf.

## Mesh und Diagnose

Der manuelle Ursprung darf für seinen Lauf ausdrücklich gespeicherte Partner
verwenden. Das Ziel benötigt weiterhin eine aktive gespeicherte Empfangsfreigabe.
Die vorhandenen Einmal-Challenges, Prüfsummen, Ablaufzeiten und Replay-Sperren
bleiben maßgeblich. Ein manueller Ursprung mit gestoppter Automatik erhält durch
seinen Einzelstart keine eingehende Freigabe. Keine automatische Wiederholung.

Öffentliche PHP-Einstiege verwenden den unveränderten DebugFlowHelper. Neue feste
deutsche Ablaufmeldungen liegen im gemeinsamen Debugkatalog; die bestehende
begrenzte Aufzeichnung liefert Lauf- und Schrittbezüge über beide Systeme.
Regeltexte, Namen, Nutzdaten und Freigabetokens bleiben geschützt.

Vorhandene zentrale Debug-, Formular-, Datenfluss- und Elternverbindungshelper
wurden geprüft und weiterverwendet. Ausführungszustände und Z2M-Kommandos bleiben
librarylokal; keine Helper-Änderungen oder neuen Abhängigkeiten.
