# ADR 0029: Systemdiagnose im Browserdesigner

- Status: angenommen
- Datum: 2026-11-05
- Ergänzt ADR 0026

## Anlass

Die gemeinsame Bearbeitung benötigt eine verständliche Übersicht über die
zugeordneten Verbindungen und Erweiterungsstände. Eine zweite Debug-Lease in
der Zentrale würde Statusabfragen, Endzeiten und Stopps schwer nachvollziehbar
machen und die bestehende native Aufzeichnung duplizieren.

## Entscheidung

Die Systemdiagnose verwendet dieselben Verbindungsbausteine und den bestehenden
Buildvergleich. Eine neue lesende GetConnectionDiagnostics-Ansicht gibt nur
Buildinformationen und eine bereinigte Lease-Ansicht aus. Empfängersitzung,
Client und Transaktion bleiben privat. Die zentralen Diagnose-POSTs erlauben
ausschließlich Health, Erweiterungsstatus sowie Start und Stopp der vorhandenen
befristeten Aufzeichnung. Kein Erweiterungsupload und keine Automationssteuerung.

System-ID, Generation, Dokumentfingerabdruck und Lease-Kontext begleiten jeden
Auftrag. Begrenzte flüchtige Quittungen verhindern Neuversand und Verlängerung
bei wiederholtem Auftrag. Die ursprünglichen Verbindungs-Timer übernehmen
Bestätigungsfrist und automatischen Ablauf. Diagnose-GETs senden keine MQTT-
Anfragen und ändern keine Dokumente. Der Browser erzeugt keine eigenen Logs;
er erklärt, welches native Verbindungs-Debugfenster zu öffnen ist.

## Folgen und Nachweis

Native Konsole und Browser bedienen bewusst dieselbe Lease je Verbindung.
Mehrere Systeme können unabhängig aufzeichnen. Ein neuer Diagnosestand kann
während einer offenen Regelbearbeitung gelesen werden, ohne deren Eingaben
zu ersetzen; der Systemwechsel selbst nutzt weiterhin die vorhandene Auswahl.
Der Backendtest deckt acht Systeme an zwei Brokern, ausbleibende und verspätete
Antworten, Wiederholungen, Ablauf, parallele Statusprüfung und HTTP-Grenzen ab.
Die Browserprüfung verwendet ausschließlich lokale synthetische Daten.
Die reale Symcon-Abnahme erfolgt nach Modulupdate separat.
