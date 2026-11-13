# ADR 0039: Gemeinsamer geführter Arbeitsplatz

Datum: 13.11.2026. Status: lokal umgesetzt; native Abnahme nach Modulupdate offen.

## Entscheidung

Browserdesigner und native Kachel verwenden den nummerierten Arbeitsplatz der
mit App 0.99.0 praktisch abgenommenen Bedienführung. Navigation, Arbeitsflächen,
Automationsformular und Fehlererklärungen kommen aus dem kanonischen Designer-
Paket. Die Kachel bettet weiterhin denselben Designer mit den vorhandenen
SDK-, Farb-, Zugangs- und Interaktionshelpern ein. Es entsteht keine zweite
bearbeitbare Oberflächenkopie.

Eine Symcon-Zentrale verwendet ihre vorhandenen MQTT-Parents und lokalen
Systementwürfe. Portable App-Projekte und deren Zugangsdatenablage werden nicht
übernommen. Vorhandene Verbindungen lassen sich über einen gesondert berechtigten
Designerzugang ausdrücklich zuordnen. Kandidatenstand, Workspace-Fingerprint,
Besitzer, Obergrenze und unveränderte bestehende Entwürfe werden serverseitig
geprüft. Nur der ausführende Zugang erhält nach einer geänderten Zuordnung den
neuen Scope; andere Zugänge müssen erneut geöffnet werden.

Schritt 7 prüft den Entwurf mit den bestehenden nativen Prüfungen. Strukturierte
Rückmeldungen zeigen Regel, Abschnitt und Feld; der Korrekturbutton öffnet die
betroffene Stelle im gemeinsamen Editor. Schritt 8 bleibt eine eigene Aktion
mit ausdrücklicher Bestätigung. Der Ablauf liest den Betriebsstatus, stoppt
gegebenenfalls Betrieb, Wiederanlauf und Einzelläufe, lädt den Modulstand neu,
prüft erneut und speichert einmal. Unbestätigte Ergebnisse sperren den nächsten
Schritt. Begrenztes Cache-Polling liest nur vorhandene Aufträge. Kein erneutes
Senden, keine automatische Aktivierung.

Native Freigabeschalter bleiben beim Speichern erhalten. Die vorhandene
Erweiterung sichert den vorherigen Stand auf dem Z2M-Host. Der private Desktop-
Dateidialog und dessen Abschaltpolitik werden nicht auf Symcon übertragen.
Direkte Häkchen im Betriebsbereich verwenden ausschließlich den vorhandenen
systemgebundenen Edit-Endpunkt. Sie ändern lokal nur enabled; Prüfung und
Übermittlung folgen getrennt. Freigabezahlen werden nur bei frisch bestätigtem,
mit dem lokalen Dokument übereinstimmendem Stand als gespeichert angezeigt.

## Prüfung und Grenzen

Synthetische Kernel-/MQTT-Prüfungen schützen Besitzer, Fingerprints, Entwürfe und
Zugangsscope. Ein echter unsichtbarer Browser mit eigenem Loopback-Server prüft
Browser und Kachel, acht Schritte, DE/EN, offene Eingaben, 360–3840 Pixel,
Hell/Dunkel, tatsächliche Mausradbewegung, genaue Korrekturstellen, lokale Häkchen
und bestätigtes Stoppen/Prüfen/Speichern. Er erzeugt keine Bildschirmbilder.
Die regulären App-Prüfungen sichern die Extraktion gegen Rückschritte ab.

Keine Änderung an Schema, Erweiterung oder zentralen Helpern. Kein Update einer
Test- oder Produktivinstallation und kein realer Gerätebefehl. Eine neue native
Symcon-Praxisabnahme wird aus den lokalen Prüfungen nicht abgeleitet.
