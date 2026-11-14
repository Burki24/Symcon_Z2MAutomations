# Verbindung und Zentrale: interne Schnittstellen

Diese Aufrufe dienen der Abstimmung zwischen Z2MConnection und Z2MAutomations.
Sie sind keine zusätzlichen Bedienwege für Anwenderskripte. Die Benutzeranleitung
steht unter [Z2MConnection](../Z2MConnection/README.md).

## Entwürfe aus der zuständigen Zentrale

Nach abgeschlossener gesicherter Übernahme nimmt diese Verbindung Prüf- und
Speicheraufträge ausschließlich von ihrem eingetragenen Besitzer an. Die
Zentrale nennt jeweils genau dieses System und den zugehörigen Entwurfsstand.
Andere Verbindungen sind von einem Fehler oder einer ausbleibenden Antwort
nicht betroffen. Die Verbindung legt keine zweite bearbeitbare Entwurfskopie an.

Prüfung und Speicherung verwenden die bestehende Z2M-Erweiterung. Vor dem
Speichern muss die Prüfung noch zur aktuellen Empfängersitzung und Revision
passen. Eine neue Statusabfrage bestätigt anschließend die gespeicherte Datei.
Bei einem Abbruch wird der Auftrag nicht automatisch erneut gesendet. Es wird
keine Automation aktiviert; bei geänderter Datei stoppt die bestehende Ausführung.

Die Funktionen `Z2MC_RequestDraftTransfer(InstanceID, Owner, OperationID, Yaml)`
und `Z2MC_GetDraftTransferResult(InstanceID, Owner, OperationID)` sind für den
internen Aufruf der zuständigen Zentrale bestimmt. Der erste Auftrag benötigt
deren aktuelle systemgebundene Freigabe. Ein bloß übergebener Besitzerwert ist
keine Berechtigung. Der zweite Aufruf liest nur das vorhandene Ergebnis.

## Lesen für die direkte Einrichtung

Die Zentrale kann über diese Verbindung zuerst eine reine Vorschau gespeicherter
Z2M-Automationen samt Gerätedaten abrufen. Vor dem ausdrücklichen Besitzerwechsel
wird der gespeicherte Stand durch eine frische Statusantwort erneut geprüft.
Ohne passende Freigabe und Zuordnung wird kein Auftrag angenommen. Der Abruf
verwendet ausschließlich die vorhandenen Z2M-Befehle status und editor; keine
Speicherung, Aktivierung, Deaktivierung oder Geräteaktion.

`RequestWorkspaceSnapshot`, `GetWorkspaceSnapshotResult`, `CancelWorkspaceSnapshot`
und `ClaimWorkspaceConnection` mit Präfix Z2MC dienen der internen Abstimmung mit
der Zentrale. Der begrenzte private Rückgabestand ist kein zweiter bearbeitbarer
Entwurf. Sitzungsdaten, YAML und Kataloginhalte werden nicht ins Debug geschrieben.
Die vorhandene Anbindung und ihre Passwörter werden durch die Einrichtung nicht
umgestellt.

## Entwurf aus Z2M aktualisieren

`RequestWorkspaceDraftSnapshot` verwendet denselben begrenzten status/editor-
Abruf mit eigenem, festem Zweck und einer passenden Freigabe über
`GetWorkspaceDraftSyncAuthority`. Die bisherigen Signaturen des Einrichtungs-
APIs bleiben erhalten. Besitzer, System, Generation, Topic, Sitzung und Auftrag
werden vor jedem Folgeschritt geprüft. Eine erneute Statusantwort bestätigt den
gelesenen Stand vor der lokalen Übernahme. Es werden keine Schreib- oder
Betriebsbefehle gesendet und keine fremden Verbindungen übernommen.

Die Zentrale vergleicht `baseRevision` und `baseChecksum` des lokalen Entwurfs mit
dem frisch gelesenen Stand. Der lokale Revisionszähler ist davon unabhängig.
Nur ein unveränderter Entwurf oder ein inhaltsgleicher Stand wird automatisch
aktualisiert. Abweichende lokale Änderungen und unterschiedliche Prüfsummen
bei gleicher Basisrevision ergeben eine Vorschau zur ausdrücklichen Ersetzung.
Eine ältere Z2M-Basis wird nicht automatisch übernommen. Der gesamte bisherige
lokale Datensatz wird vor einer Ersetzung im privaten `MigrationArchive` gesichert.
Wiederherstellung ist nur möglich, solange der übernommene Entwurf unverändert ist.

`draft-sync` ist eine feste, durch Bearbeitungsrecht, Origin, JSON-Form,
Systemgeneration und Fingerprint begrenzte POST-Route. `draft-sync-status` fragt
nur den begonnenen Vorgang ab. Lesen und erneute Bestätigung sind begrenzt;
Zeitüberschreitungen und Änderungen am Entwurf, Besitzer oder Remote-Stand
brechen ab. Es gibt keinen automatischen Neuversand. Der gerätebezogene Abruf
in Schritt 4 bleibt davon getrennt und ersetzt weiterhin keinen Entwurf.

## Betriebsaufträge der Zentrale

Die zuständige Zentrale kann gespeicherte Automationen aktivieren oder stoppen
und einzelne Regeln einmal starten oder gezielt stoppen. Die Verbindung führt
die bestehenden Z2M-Befehle aus; sie bearbeitet keinen eigenen Entwurf.
Aktivierung und globaler Stopp benötigen nach der Antwort eine weitere frische
Statusbestätigung. Einzelne Laufmeldungen bleiben an System und Laufkennung
gebunden. Bei verlorenen Antworten wird kein Befehl wiederholt.

`Z2MC_RequestRuntimeControl(InstanceID, Owner, OperationID)` benötigt einen
vorbereiteten Auftrag der tatsächlich zuständigen Zentrale.
`Z2MC_GetRuntimeControlResult(InstanceID, Owner, OperationID)` liest nur dessen
dauerhaftes Ergebnis. Der Besitzerwert allein erteilt keine Berechtigung.
`GetConnectionInfo` liefert den Sitzungsbezug als SHA-256-Vergleichswert,
keinen Aktivierungstoken. Deutsche Status- und Debugmeldungen erklären die
Bestätigung und Fehler; Namen, YAML und Aktionswerte erscheinen nicht im Debug.

## Erweiterungsinstallation der Zentrale

RequestOwnedInstallation und GetOwnedInstallationResult mit Präfix Z2MC benötigen
einen ausdrücklich bestätigten Auftrag. System, Verbindung, Zuordnung, Build
und tatsächliche Dateibytes sind fest gebunden. Ein beliebiger Besitzerwert
erteilt keine Berechtigung. Vor der ersten Übernahme ist die Installation nur
möglich, solange die Verbindung noch keinen Besitzer hat.

Die Verbindung nutzt ihren bestehenden Installer und die offizielle
bridge/request/extension/save-Schnittstelle. Eine neue Sitzung mit erwartetem
Build bestätigt das Ergebnis; ein bloßes Upload-OK genügt nicht. Dauerhafte
Quittungen verhindern Doppelversand. Statusabfragen senden keine Datei erneut.
Der direkte InstallExtension-Einstieg bleibt bei verwalteten Verbindungen
gesperrt. Es werden keine Automationsdateien gespeichert oder Startbefehle
gesendet. Z2Ms vorhandene Wiederanlaufeinstellung gilt weiterhin.
