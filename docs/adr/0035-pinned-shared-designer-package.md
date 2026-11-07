# ADR 0035: Festgeschriebenes gemeinsames Designer-Paket

Datum: 07.11.2026. Status: für den Beta-Kandidaten angenommen.

## Entscheidung

Die gemeinsame Oberfläche wird in Burki24/Z2M-Automations unter
packages/designer/src gepflegt. Symcon übernimmt ausdrücklich ein geprüftes
Paket und liefert dessen Dateien vollständig aus dem eigenen Repository aus.
Ein Benutzer benötigt weder die Desktop-App noch deren Repository oder Dienste.
Die Symcon-Beta hängt nicht von einem Windows-/Ubuntu-Release ab.

Der hier geprüfte Paketstand ist @z2ma/designer 0.2.0 mit Inhaltskennung
7909e0e7638c49a1fd4f6c58d35a0347005a1568fddcaa9fa9725c11e0d39007.
Der erzeugende Desktop-Quellcommit ist
dbcd466c7a0259cbe2d2bc71caa9703e453917f5; der ursprüngliche Symcon-Quellstand
ist cd5cfc33c921365c69814078238444c3ea7d5ac5. Die PolyForm-Lizenz und die
vorhandenen Hinweise sind unverändert unter libs/designer-LICENSE erhalten.
Das Manifest nennt SHA-256 für jede Datei; dies ist keine Herausgebersignatur.

## Abgrenzung und Prüfung

transport.js verwendet unter Symcon weiterhin die bestehenden HTTP-Routen.
Origin, Zugang, Systembindung, Besitzer, Revisionen und Bestätigungen bleiben
serverseitig geprüft. Der ausschließlich in der Desktop-App installierte
Offline-Adapter erteilt keine Symcon-Berechtigung. Dateien werden weder zur
Laufzeit von GitHub geladen noch nach einem Modulupdate aus Nachbarrepos gebaut.

Native Kachel und zentraler Interaktionshelper bleiben in ihrer bisherigen
Zuständigkeit. Der lokale Paketimport sperrt ihre reservierten Dateinamen;
ein Regressionstest weist diese Grenze nach. Schema, Erweiterungsartefakt,
gespeicherte Entwürfe und MQTT-Protokoll werden nicht verändert.

Paket- und Sprachprüfungen funktionieren aus einer eigenständigen Kopie ohne
Desktop-Checkout. PHP-Hookprüfungen und der echte isolierte Browser prüfen die
ausgelieferten Dateien. Bestehende Praxisnachweise bleiben abgegrenzt erhalten;
ein neuer Test am Live-System wird daraus nicht behauptet.

## Rückweg

Der letzte Stand vor der Paketübernahme bleibt als Symcon-Commit cd5cfc33
erreichbar. Ein Quellcode-Rückweg benötigt zusammenpassende Assets, feste
HTTP-Assetliste und zugehörige Prüfskripte aus demselben Stand. Er verändert
keine YAML in Z2M und stellt keinen früheren Automatikbetrieb wieder her.
Die bereits vorhandenen lokalen Einrichtungs-/Entwurfsrückwege bleiben erhalten.
