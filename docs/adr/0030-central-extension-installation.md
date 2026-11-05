# ADR 0030: Systemgebundene Erweiterungsinstallation

- Status: angenommen
- Datum: 2026-11-05
- Ergänzt ADR 0029

## Entscheidung

Zentrale und Designer bedienen denselben ausdrücklichen Installationsauftrag.
Die eigene installation-Browserberechtigung schützt feste GET-/POST-Routen;
der Browser kann weder Quellcode noch Dateiname, MQTT-Topic oder Befehl liefern.
Ein eigener HTML-Dialog bindet die angezeigte Systemidentität und mitgelieferte
Datei. Der native Callback enthält denselben festen Bestätigungskontext.

WorkspaceInstallation prüft Bindung, Generation, Dokumentfingerabdruck und
Besitzerzustand. Verwaltete Systeme verlangen ihre zuständige Zentrale. Bei
besitzerlosen Verbindungen ist die Erstinstallation vor der Übernahme möglich;
der Auftrag verändert dabei weder Besitzer noch Entwurf. Nach frischer Health-
Bestätigung wird genau das freigegebene Artefakt an ConnectionInstallation
delegiert. Buildkennung und SHA-256 der tatsächlichen Dateibytes sind getrennt
gebunden. Die Verbindung verwendet den bestehenden Installer mit festem
extension/save-Auftrag und anschließender Statusprüfung.

Erfolg verlangt eine neue Sitzung, erwarteten Build und, soweit vorher bestätigt,
dieselbe gespeicherte Revision und Prüfsumme. Uploadbestätigung allein genügt
nicht. Statusabfragen und GETs starten keine Installation. Ein Timer führt den
ausdrücklich angenommenen Auftrag weiter; dauerhafte begrenzte Quittungen
verhindern Neuversand. Nach Modulupdate oder verlorener Transportantwort darf
nur nachgelesen werden. Es gibt keinen automatischen Wiederholungsversuch.

Kurze zentrale Schreibabschnitte nutzen die bestehende Workspace-Sperre.
MQTT und verbindungsübergreifende Aufrufe liegen außerhalb dieser Sperre.
Der reine Authority-Callback liest nur lokale Daten und fordert keinen zweiten
Besitzerlock an. Der lokale Übernahmerückweg wird gesperrt, sobald ein Upload
für die betreffende Übernahme möglicherweise begonnen hat.

Der bestätigte Upload erhält einen getrennten Geräteabruf als Folgeschritt.
WorkspaceInstallationDevices delegiert an WorkspaceCatalogRefresh statt einen
zweiten MQTT-Ablauf einzuführen. Vor dem Aufruf wird der Vorgangsbezug dauerhaft
festgehalten; ein bereits laufender Abruf derselben Bindung wird mitbenutzt.
Nur der Timer startet ihn, GETs beobachten. Sitzung, Bindung und bestätigter
Katalog begrenzen die Zuordnung. Gerätefehler widerrufen keinen Installationserfolg
und starten keinen erneuten Upload. Modulupdate beendet den Folgeschritt;
ein neuer manueller Geräteabruf bleibt möglich. Historische Abschlussaufträge
ohne Folgeschritt werden nicht rückwirkend ausgeführt.

Katalogänderungen dürfen weder Entwürfe noch offene Formulareingaben ersetzen.
Native Fingerabdruckfelder werden deshalb nur bei gleicher Bindung und identischem
Dokument aktualisiert; kein automatischer Formularneuaufbau. Die schon bestehende
Prüfung veralteter Bearbeitungsstände bleibt bei parallelen Entwurfsänderungen aktiv.

## Folgen und Prüfung

Die Bestätigung erklärt mögliche Unterbrechungen von Abläufen und Aufzeichnungen.
Z2Ms bestehender Wiederanlauf gilt unverändert; Symcon sendet weder YAML noch
Aktivierungsauftrag. Native Anzeige und Designer zeigen Ergebnis, Build und
bestätigten Betriebsstand. Deutsche native Debugmeldungen enthalten keinen
Quellcode, YAML, Anfragekörper oder Zugangsdaten.

Offline-Nachweise verwenden acht synthetische Systeme an zwei Brokern,
Bestätigung, Erstinstallation, Update, falsche Sitzung/Build, veränderten
Speicherstand, Ablehnung, Zeitüberschreitung, Modulupdate und verlorenen Workflow.
HTTP-/Browserprüfungen sichern feste Routen, Abbruch, Zielbindung und fehlenden
Neuversand. Reale Abnahme erfolgt nach Modulupdate separat.
