# ADR 0037: Automatische lesbare Erweiterungsversion

## Entscheidung

Ergänzt ADR 0016 auf Benutzerauftrag vom 08.11.2026. Die Erweiterung beginnt
eine eigene Versionsfolge mit 0.2.0. Der bestehende Builder speichert Version und
SHA-256 des unversionierten Gesamtinhalts in `libs/z2m/version.json`.
Geänderter Inhalt erhöht die Patchnummer genau einmal pro Buildstand.
Unveränderte Builds und das Wiederherstellen einer fehlenden Upload-Datei
behalten die Version. Library-Releases erhöhen sie nicht automatisch.

Die konkrete Version wird vor der Buildhash-Berechnung in das Artefakt eingesetzt.
Die laufende Erweiterung meldet ihre geladene Version; PHP liest die mitgelieferte
Version aus dem Artefaktheader. Beide werden mit der Buildkennung angezeigt.
MQTT-Protokoll, Schema und einzelne Fähigkeiten werden weiterhin separat geprüft;
eine neue Releaseversion ändert diese Verträge nicht. Ältere Empfänger mit 0.1.0
bleiben für einen ausdrücklich beauftragten Updatevorgang lesbar.

## Grenzen

`--check` schreibt und erhöht nichts. Fehlende oder ungültige Metadaten sind
ein Fehler, kein Anlass zum Neustart des Zählers. Größere Versionssprünge brauchen
eine bewusste Entscheidung. Zeitstempel und Git-Metadaten gehen nicht in den
Erweiterungsstand ein. Es gibt keine automatische Installation oder Aktivierung.
Die frühere Version 0.1.0 bleibt nur zusammen mit ihrem Build unterscheidbar.
