# 0010: Ausdrücklichen Betriebswunsch in Z2M speichern

Status: angenommen, 01.11.2026.

## Entscheidung

Aktivierte Regeln sollen nach Z2M-Neustart ohne Symcon weiterlaufen. Bewusstes
Stoppen bleibt wirksam. Die standardmäßig eingeschaltete Moduloption wird beim
Aktivieren übertragen; keine automatische Aktivierung durch ApplyChanges.

Eine eigene begrenzte JSON-Datei neben der YAML enthält die Wiederanlauf-Freigabe
mit System, Option, Betriebswunsch, Revision und Prüfsumme. Sie benutzt den
vorhandenen atomaren Speichercode der eigenen Erweiterung. Keine Zuständigkeit
für zentrale Helper, da dies librarylokale Laufzeitlogik ist.

Nur derselbe validierte Regelstand darf wieder anlaufen. Geändertes Speichern
entzieht die Freigabe vor dem Dateiaustausch. Stoppen bestätigt dauerhaften Erfolg
nur nach erfolgreicher Speicherung. Alte Installationen ohne Datei bleiben aus;
kein Rückschluss aus enabled-Regeln oder retained MQTT-Nachrichten.

## Folgen

Kein laufendes Symcon beim Neustart erforderlich. Auch Erweiterungsupdates laden
den freigegebenen Stand wieder. Token, Wartezeiten, Sensorwerte und angefangene
Folgen bleiben flüchtig. Fehler verhindern einen bestätigten Wiederanlauf und
werden mit festen Codes angezeigt. Bei fehlgeschlagenem dauerhaftem Stopp kann
der vorherige Dateistand weiterhin gelten; dies wird ausdrücklich gemeldet.

## Nachweise

Offline-Tests erzeugen neue Erweiterungsinstanzen über echten temporären Dateien,
prüfen Mesh-Wiederanlauf, frische Bedingungen, verpasste Zeiten, Stopp während des
Starts sowie beschädigte oder fremde Zustandsdateien. PHP-/JS-Integration prüft
beide Werte der Moduloption. Native Installation und reale Neustartprüfung offen.
