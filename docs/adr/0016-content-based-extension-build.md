# ADR 0016: Erweiterungsstand anhand des erzeugten Inhalts vergleichen

## Status

Angenommen und lokal implementiert. Offline geprüft; kein Update einer
Test- oder Produktivinstallation in diesem Arbeitspaket.

## Entscheidung

Der bestehende Generator bildet SHA-256 über das vollständig zusammengesetzte
eigene Erweiterungsartefakt mit festem Platzhalter für die Kennung. Anschließend
ersetzt er genau diesen Platzhalter durch die 64-stellige Kennung. Enthalten
sind Runtime, Mesh, Szenen, Folgen, Astro, Debugkatalog und kanonisches Schema.
Weder Uhrzeit noch Pfad, Git-Zustand oder Rechnername beeinflussen die Kennung.
Identischer erzeugter Inhalt erhält denselben Stand. Es entsteht kein weiteres
hochzuladendes Artefakt und keine zusätzliche Laufzeitabhängigkeit.

Die geladene Erweiterung meldet `extension_build` additiv im Status. Der Wert
beschreibt den geladenen Code und wird nicht durch Lesen einer möglicherweise
inzwischen ersetzten Datei bestimmt. Symcon liest die mitgelieferte Kennung aus
dem Header seiner eigenen Upload-Datei. Änderungen der Versionsmetadaten sind
dafür nicht erforderlich. `check:extension` prüft weiterhin die gesamte Datei.

## Darstellung und Grenzen

Der Bereich Einrichtung und Diagnose zeigt installierte und mitgelieferte
Version mit jeweils zwölf sichtbaren Zeichen der Buildkennung. Verglichen
werden alle 64 Zeichen. Gleichheit bedeutet aktuell; Abweichung bedeutet,
dass der mitgelieferte Stand zur Installation angeboten wird. Eine Inhaltskennung
hat keine zeitliche Ordnung und behauptet nicht, welcher Stand jünger ist.

Eine fehlende Kennung bei älteren Erweiterungen verhindert keine bisherige
Statusverarbeitung. Die Oberfläche erklärt, dass der Vergleich noch nicht
möglich ist, und empfiehlt Installation des mitgelieferten Stands. Vorliegende
ungültige Kennungen werden am Protokollrand abgewiesen. Ohne passende bestätigte
Statusantwort, nach Kontextwechsel oder bei unlesbarem lokalen Artefakt wird
keine Gleichheit behauptet. Unbelegte beziehungsweise retained Antworten ändern
den Vergleich nicht.

Vorhandene Statusworkflows aktualisieren die Anzeige, auch nach Installation
und bei automatischer Prüfung. Der Formularaufbau selbst sendet kein MQTT.
Es gibt keine automatische Installation, Aktivierung oder Änderung des YAML.
Der kanonische DebugFlowHelper protokolliert geänderte Vergleichsergebnisse mit
festen deutschen Texten und kurzen Kennungen; identische Wiederholungen entfallen.
Die Funktion bleibt librarylokal; zentrale Helper bleiben unverändert.
