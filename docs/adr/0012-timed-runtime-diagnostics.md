# ADR 0012: Zeitlich begrenzter eigener MQTT-Debugkanal

Status: angenommen, implementiert und am 02.11.2026 über Test-Symcon installiert.
Start, Ablauf, Stopp und Systemzuordnung praktisch geprüft; gemeinsamer Laufbezug
einer realen systemübergreifenden Geräteaktion noch offen.

## Anlass

Die native Übergabe und Ausführung in Z2M müssen auch aus Symcon nachvollziehbar
sein. Allgemeine Z2M-Logs enthalten ungebundene Gerätedaten und hängen vom
globalen Protokolllevel ab. Mehrere Symcon-Installationen dürfen sich bei der
Diagnose derselben Erweiterung nicht gegenseitig abschalten.

## Entscheidung

Ein eigener nicht persistierter Aufzeichnungsauftrag mit zufälliger Client-ID
und maximal 900 Sekunden aktiviert die Ausgabe nur für diese Frist. Höchstens
acht Clients können unabhängig aufzeichnen. Korrelierte Antworten bestätigen
Beginn und Ende; Sitzung und Transportkontext begrenzen die lokale Annahme.
Der Timer beendet die lokale Anzeige auch ohne neue MQTT-Meldung. Nach
Neustart oder Änderung wird keine Aufzeichnung automatisch erneuert.

Ein eigener fester Meldungskatalog mit sicheren Positions- und Laufdaten bildet
das Protokoll. PHP übernimmt keine fremden Meldungstexte. Ein Laufbezug bleibt
bei Mesh-Aktionen über die bestehende kurzlebige Challenge erhalten, verändert
aber weder Aktionsprüfung noch Freigabe. Beide Seiten zeichnen unabhängig auf.

Die Ausgabe nutzt unverändert den kanonisch eingebundenen DebugFlowHelper.
Z2M-Laufbezüge ergänzen den lokalen Vorgang und ersetzen dessen Zähler nicht.
Keine neuen gemeinsamen Helper: der gesamte Vertrag ist libraryspezifisch.
Die vorhandenen zentralen Datenfluss-, Formular- und Debug-Helper genügen.

Ein begrenzter Puffer mit einem zeitlich begrenzten Publish entkoppelt Diagnose
und Schaltpfad. QoS 0, kein Retain, keine Wiederholung, kein Debug in Dateien,
keine Rohwerte oder YAML und kein Warten des Geräteschaltpfads auf Debug.
Bekannte Verluste werden bei der nächsten Ausgabe erkennbar gemacht.

## Folgen und Nachweise

Modul und Erweiterung benötigen diesen neuen Stand. Ohne bestätigtes
`debug_version: 1` startet keine Aufzeichnung. Eine ausbleibende Bestätigung
kann eine bereits gestartete Remote-Frist nicht widerrufen; sie endet in Z2M
trotzdem automatisch. Gleiches gilt bei Trennung oder Einstellungsübernahme.
Beenden wirkt nur auf die eigene Client-ID; keine globale Logsteuerung.

Offline-Tests prüfen lokale Folgen, Bedingungen, Mindestdauer, Fehlerschritte,
Mesh-Zuordnung und Duplikate, unabhängige Clients, Zeitablauf, Neustart,
defekte/hängende Ausgabe, Überlast, strikte Empfangsprüfung, Formularstatus
und den echten PHP-/JavaScript-Datenfluss über das generierte Artefakt.
Installation und Praxisprüfung erfolgten auf gesonderten Auftrag; Umfang und
verbleibende Hardware-Testgrenze stehen in DEVELOPMENT.md. Der Debugkanal
bestätigt keine physische Gerätewirkung.
