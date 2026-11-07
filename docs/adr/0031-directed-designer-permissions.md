# ADR 0031: Gerichtete Partnerfreigaben im Browserdesigner

- Status: angenommen
- Datum: 2026-11-05
- Ergänzt ADR 0026

## Entscheidung

Der Anwender bearbeitet die Empfangsfreigaben des ausgewählten Systems über
einen eigenen Designerbereich. Jede Freigabe beschreibt Sender → Empfänger;
eine Gegenfreigabe wird nicht automatisch erstellt. Bestehende Freigaben für
nicht zugeordnete Systeme bleiben sichtbar und ausgewählt. Neue Freigaben
werden ausschließlich für andere zugeordnete Systeme angeboten.

Die eigene permissions-Sitzungsberechtigung und zwei feste schmale HTTP-Routen
erweitern den vorhandenen Workspace. Der Browser liefert keine Dokumente,
Quelltexte, Gerätebefehle oder MQTT-Ziele. Die Bestätigung bindet den aktuellen
Workspace und Besitzerzustand, während der Commit unter der vorhandenen kurzen
Sperre ausschließlich mesh_peers und die zugehörige Entwurfsrevision aktualisiert.
Unveränderte Auswahl bewahrt die exakten YAML-Bytes. Vorhandene Quittungen
erlauben eine sichere ausdrückliche Wiederholung nach verlorener Rückmeldung.

Vor dem Entzug werden über SequencePlan abhängige lokale Regeln ermittelt.
Ausgeschaltete Regeln zählen mit. Gesamtzahlen bleiben vollständig, angezeigte
Namen sind auf 20 je Sender begrenzt. Externe unbekannte Nutzer werden nicht
als geprüft dargestellt. Übertragung und Aktivierung bleiben separate bewusste
Schritte; die Verwaltung der Freigaben selbst sendet niemals MQTT.

## Folgen und Grenzen

Es entsteht kein zweiter Editor oder Berechtigungsweg für normale Regeländerungen.
Diese dürfen vorhandene Freigaben weiterhin nicht verändern. Native offene
Eingaben und veraltete Systembindungen sperren die Übernahme. Für eine neue
Designerberechtigung muss eine vor dem Modulupdate geöffnete Sitzung neu
geöffnet werden. Eine Verbindung zwischen Brokern ist weiterhin separat nötig;
der Dialog richtet keine Bridge ein und beweist keine Erreichbarkeit.

Kerneltests mit acht synthetischen Systemen prüfen Richtung, Entwurfserhalt,
Abhängigkeiten, Begrenzung, Wiederholung und veraltete Bestätigungen. Browser-
Vertragstests und ein eigener synthetischer Headless-Browser prüfen den Dialog.
Eine reale Praxisprüfung nach Modulupdate bleibt ein eigener Auftrag.
