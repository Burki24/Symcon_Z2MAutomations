# ADR 0024: Uhrzeit- und Sonnenregeln im Browser erstellen

- Status: angenommen
- Datum: 04.11.2026
- Erweitert: ADR 0022 und 0023; kein neuer Z2M-Vertrag

## Anlass

Die neue Browserbearbeitung unterstützt Zeit- und Sonnenauslöser bereits.
Auch deren erstmaliges Anlegen soll ohne Wechsel in den nativen Editor möglich
sein. Bestehende Entwürfe, Aktionsrechte und laufende Z2M-Regeln bleiben geschützt.

## Entscheidung

Der vorhandene Erstellungsdialog bietet Geräteereignis, Uhrzeit und Sonnenzeit.
Nur die gewählte Quelle ist sichtbar und aktiviert. Ausgeblendete Pflichtfelder
blockieren keine andere Auslöserart. Das Wechseln der Art und das Aktualisieren
der Ansicht erhalten die noch nicht übernommenen Werte. Neue Dialoge beginnen
mit Geräteereignis und Europe/Berlin; Standortfelder bleiben bewusst leer.

Uhrzeitquellen enthalten eine bis acht unterschiedliche times sowie timezone.
Sonnenquellen verwenden type sun, event, latitude, longitude, offset_minutes
und timezone. Der Standort ist eine ausdrückliche Eingabe, kein Dienstaufruf.
Versatz: ganze Minuten zwischen -720 und 720; negativ früher, positiv später.
Die Ausführung und die tägliche Berechnung bleiben in Z2M.

Die vorhandene feste POST-create-Route mit 16-KiB-Grenze akzeptiert zusätzlich
nur diese begrenzten Quellformen. Ursprung, Zugang, Kontext, offene native
Eingaben, Höchstzahl der Regeln und Wiederholungsquittungen gelten unverändert.
Die Gerätequelle behält ihre vorhandene feste Feldmenge. Es gibt keinen
allgemeinen YAML-, Regel- oder Methodenaufruf über den Erstellendpunkt.

ClockEditorRows normalisiert Zeiten einschließlich Sekunden und verhindert
Dubletten. SolarEditorFields prüft Ereignis, numerische Koordinaten, Zeitzone
und ganzzahligen Versatz. Aktionsziele verwenden weiterhin aktuelle native
Schaltrechte und BuildEditorAction, auch bei Gruppen, Szenen und Partnerzielen.
NewEditorRule teilt den Regelrahmen mit der nativen einfachen Erstellung.
CommitEditorChange bleibt der einzige Übernahmepfad. Es entsteht eine neue
Regel mit enabled false, ohne Bedingungen und mit einer Aktion. Der bestehende
Bearbeitungsdialog ergänzt später weitere Auslöser, Bedingungen und Schritte.
Andere Regeln, Folgen und vorhandene Partnerfreigaben bleiben erhalten.

Der lesende Datenexport nennt die vom PHP-System akzeptierten Zeitzonen; er
startet weiterhin keine MQTT-Abfrage. Debug nennt Typ, Anzahl und bei Sonnenzeit
Ereignis und Minutenversatz. Keine Koordinaten, genauen Uhrzeiten oder Nutzdaten.

## Nachweise und Grenzen

Synthetische Tests durch den echten Modul-Hook prüfen erfolgreiche Übernahme,
Typen, Grenzen, Dubletten, ungültige Quellen, Schaltrechte, Kontext und den Erhalt
vorhandener Daten. UI-Prüfung erfolgt lokal gegen dasselbe Modul mit Stubs.
Eine echte Symcon-Abnahme nach Modulupdate ist separat erforderlich. Änderungen
an Z2M und seiner Erweiterung sind für diese Funktion nicht nötig; Speichern,
Aktivieren und Laufzeitsteuerung bleiben im nativen Formular.
