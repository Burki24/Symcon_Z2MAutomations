# ADR 0027: Direkte Einrichtung aus gespeichertem Z2M-Stand

- Status: angenommen
- Datum: 2026-11-04
- Ergänzt ADR 0026, ersetzt keine bestehende Datenübernahme

## Anlass

Eine neue Zentrale kann Verbindungen zuordnen, besitzt aber weder alte
Einzelinstanzen noch deren Entwürfe. Der bisherige Übernahmevertrag verlangt
genau diese Quellen. Dadurch bleiben die systemgebundenen Prüf-/Speicheraufträge
gesperrt. Künstliche Einzelinstanzen oder fingierte Besitzer sind kein
Einrichtungsweg für den Anwender.

## Entscheidung

Ein zusätzlicher ausdrücklicher Ablauf liest je zugeordnetem Z2M-System über die
vorhandene editor/status-Schnittstelle. Er benötigt keine alte Quellinstanz und
erhält sourceInstance 0. Reines Zuordnen oder Öffnen des Formulars erteilt weiterhin
keine Schreibrechte. Der native Bereich trennt Einlesen und Vorschau von der
Bestätigung des lokalen Besitzerwechsels.

Die Erweiterungsantwort muss zu System, Phase, Transaktion, Sitzung, Revision,
Prüfsumme und erwarteter Definition passen. Gespeicherte YAML bleibt bytegleich.
Nur ein bestätigt leerer Z2M-Speicher ergibt einen neu erzeugten lokalen Entwurf;
beschädigte oder gesperrte Speicher erhalten keinen Ersatzstand. Vorhandene lokale
Dokumente, offene Eingaben, zuständige Einzelinstanzen und fremde Besitzer werden
nicht überschrieben. Einzelsysteme werden nacheinander eingerichtet; bereits
eingerichtete Systeme behalten ihren Stand.

Ein privates atomar geprüftes Archiv sichert Kandidat und vorherigen lokalen
Zustand. Nach ausdrücklicher Bestätigung folgt eine frische Statusprüfung. Erst
danach werden Claim, zentraler Besitzer und Dokument veröffentlicht. Ein
persistentes Commitstadium sperrt andere Änderungen während eines unterbrochenen
Übergangs. Lokale Schritte sind idempotent fortsetzbar. Verlorene MQTT-Aufträge
werden niemals automatisch erneut gesendet.

Die bisherige Claimprüfung wird gemeinsam verwendet. Eine eigene eng begrenzte
lesende Setup-Autorität prüft den aktuellen Journalauftrag; sie ist kein
allgemeiner Schreibzugang. Sie darf wie die bestehende Migrationsautorität
innerhalb synchroner Antwortketten ohne Rückgriff auf die Besitzersperre gelesen
werden. Besitzer beziehen sich auf diese Symcon-Installation, nicht auf einen
globalen MQTT-Brokerbesitzer.

Der lokale Rückweg sichert zwischenzeitliche Änderungen zusätzlich und stellt
ausschließlich den betroffenen Systemplatz wieder her, bevor er den Claim
freigibt. Nach möglicher Z2M-Speicherung bleibt dieser rein lokale Rückweg
gesperrt. Alte Migrationsrückwege dürfen zusätzliche direkt eingerichtete
Systeme nicht still verlieren.

## Grenzen und Nachweis

Der Ablauf sendet ausschließlich status und editor. Er schreibt keine Z2M-Datei,
startet oder stoppt keine Automation und verändert keine MQTT- oder Geräte-
Zuordnung. Danach gelten die bestehenden systemgebundenen Validierungs- und
Speicherregeln; eine Übernahme ist keine Speicher- oder Aktivierungsfreigabe.

Tests verwenden synthetische 1/2/8-System-Installationen, gemeinsame/getrennte
Broker, identische Regel-/Gerätekennungen, leeren Speicher, geänderte Antworten,
Zeitablauf und unterbrochene lokale Commit-/Rückgabeschritte. Native Callback-
quellen werden tatsächlich ausgeführt. Eine echte Symcon-Abnahme nach Update
bleibt separat; keine Test- oder Live-Installation wird für die Implementierung
verändert. Der bestehende Erweiterungsvertrag benötigt keinen neuen Build.
