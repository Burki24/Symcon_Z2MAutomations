# Lokale Sicherung, Übernahme und Rückweg

Schritt 4 von [ADR 0026](adr/0026-central-manager-and-system-connections.md).
Die Abläufe sind als explizite Modulfunktionen implementiert und offline geprüft.
Ein Modulupdate allein übernimmt nichts. Gemeinsame native und Browserbedienung
folgen in Schritt 5, neue systemgebundene Z2M-Schreibaufträge in Schritt 6.
Die bestehende Einzelinstanz bleibt ohne Besitzerwechsel wie bisher bedienbar.

## Was erhalten bleibt

Für eine Übernahme wird eine eigene Z2MAutomations-Verwaltungsinstanz ohne
Einzelentwurf verwendet. Eine bis acht bisherige Automationsinstanzen werden
ausdrücklich jeweils einer bereits vorhandenen Z2MConnection zugeordnet.
Parent und Basistopic müssen exakt passen. Bei einem gemeinsamen MQTT-Client
bleiben die Topics verschieden. Doppelte Systemkennungen, Quellen, Verbindungen
oder Mesh-Topics werden nicht zusammengeführt.

Die Übernahme erhält die YAML-Bytes, Editordefinition, Basisrevision und
Prüfsummen, lokale Regel-/Folgenkennungen, Empfangsfreigaben, Auswahl und saubere
Formulareingaben. Auch die Abwesenheit von `mesh_peers` bleibt erhalten.
Offene Eingaben, beschädigte Daten oder voneinander abweichende YAML- und
Editorstände blockieren die Übernahme. Lesbare UTF-8-Quellstände lassen sich
unabhängig von ihrer Übernahmefähigkeit privat sichern; unlesbare Daten werden
nicht durch Standardwerte ersetzt.
Die bisherige Instanz und ihre ursprünglichen Attribute werden nicht gelöscht.
Geräte, Variablen, Idents, Actions und Archivbezüge werden weder verschoben noch
ersetzt. Native Bindungen werden anhand von Parent und Topic neu ermittelt;
fehlende oder inzwischen gesperrte Variablen erhalten dadurch kein Schaltrecht.

Die alte `PeerModuleID` wird im Ergebnis auf die neue SystemID abgebildet.
Nicht ausgewählte Partner bleiben als `unresolvedPeerInstance` sichtbar.
Eine solche lokale Übernahme erteilt keine neue Empfangsfreigabe. Ungelöste
Partner müssen vor einer späteren Z2M-Prüfung und Übertragung geklärt werden.

Frühere Einstellungen (`Active`, `ResumeAfterRestart`, `RequestTimeout`, YAML-
Anzeige), Importbezüge und historische Statuswerte bleiben im Quellarchiv.
Die Optionen stehen zusätzlich im Bericht. Verbindungseinstellungen werden
nicht still überschrieben; ihre Verwendung durch zentrale Betriebsaufträge
gehört zu Schritt 6. MQTT-Passwörter und Socketkonfigurationen werden nicht gelesen.

## Sicherungen und Journal

Die Ablage liegt außerhalb der Library unter
`IPS_GetKernelDir()/Z2MAutomations-private/migrations/<Verwaltungsinstanz>/`.
Die Pfade sind intern festgelegt; Aufrufer können keinen Dateipfad angeben.
Unter Unix werden Verzeichnisse mit 0700 und Dateien mit 0600 angelegt. Unter
Windows gelten zusätzlich die geerbten Dateisystem-Zugriffsrechte.

Pro Vorgang entstehen getrennte Quellsicherungen, ein Manifest, ein Journal
und beim Rückweg eine zusätzliche Kopie des aktuellen zentralen Arbeitsstands.
Jede Datei enthält Formatkennung, Version und SHA-256-Prüfsumme. Vor Veröffentlichung
wird über eine temporäre Datei geschrieben, geflusht und synchronisiert, dann
atomar umbenannt und erneut gelesen. Prüfsummen erkennen beschädigte Dateien;
sie sind keine Signatur gegen absichtliche Manipulation durch Administratoren.
Quellsicherungen und Manifest sind unveränderlich. Sicherungen werden nicht
automatisch gelöscht oder als Diagnoseprotokoll veröffentlicht.

Die Quellsicherung enthält alle Editorvarianten, offene Eingaben und deren
ursprüngliche Bindung, native Objekt-/Variablenzuordnungen, Importbezüge,
Konfiguration und Herkunft einschließlich Library-Version. Sie ist privat.
Historische Inhalte und Tokens aus diesen Daten werden niemals als Berechtigung
wiederhergestellt. Sicherungsinhalte, rohe Ausnahmen und Browserzugänge erscheinen
nicht im Debug. Das Journal enthält den Bearbeitungsstand und bindet Quelle,
Quellfingerabdruck, Ziel, Manifest und Operationskennung.

## Öffentliche Modulfunktionen

Alle Ergebnisse sind JSON. Native Skripte müssen die angegebenen Parameter
vollständig übergeben. Diese Schnittstellen sind noch keine Browserrouten.

| Funktion, jeweils mit `Z2MA_` und erster Instanz-ID | Aufgabe |
| --- | --- |
| `BackupSystemMigration(ID, SourceInstanceIDs, OperationID)` | JSON-Liste von 1–8 Quellen vollständig sichern, auch bei offenen Eingaben. Kein Besitzerwechsel. |
| `PrepareSystemMigration(ID, Mapping, WorkspaceFingerprint, OperationID)` | Quellen prüfen, privat sichern und Zielstände vorbereiten. Bisherige Quellen bleiben bedienbar. |
| `ApplySystemMigration(ID, OperationID)` | Denselben vorbereiteten Vorgang übernehmen oder nach Abbruch fortsetzen. |
| `GetSystemMigration(ID, OperationID)` | Kleinen Ergebnisbericht mit Phase, Zuordnungen, Partnern und nächstem Schritt lesen. |
| `RollbackSystemMigration(ID, OperationID)` | Zentrale Bearbeitung sperren, zusätzliche Sicherung erstellen und bisherige Zuständigkeit zurückgeben. |
| `GetSystemOwnership(ID)` | Versionierten lokalen Besitzer-/Vorgangsmarker lesen. Bei Verbindungen lautet das Präfix `Z2MC_`. |

`OperationID` besteht aus 32 kleinen Hexadezimalzeichen, für jeden neuen Vorgang
neu erzeugt. Gleiche Kennung mit anderem Auftrag wird abgewiesen. Ein bereits
abgeschlossener Vorgang wird nur erneut bestätigt und nicht neu ausgeführt.
Insbesondere überschreibt eine erneute Übernahme keine später bearbeiteten Ziele.

`Mapping` ist eine JSON-Liste mit genau diesen Feldern je Zeile:

```json
[
    {
        "sourceInstance": 12000,
        "connectionInstance": 13000,
        "fingerprint": "<Quellfingerabdruck aus der aktuellen Vorschau>"
    }
]
```

Die IDs sind synthetische Beispiele. Der `WorkspaceFingerprint` stammt aus
`GetWorkspaceOverview`. Der bisherige Vorschauaufruf bleibt rein lesend und
stellt selbst keine Übernahmefreigabe aus. Der Vorbereitungsschritt vergleicht
alle ausgewählten Quellen und Zuordnungen erneut.

Die internen öffentlichen Kernelbrücken `ExportSystemMigration`,
`GetSystemMigrationAuthority`, `FenceSystemMigration` und
`Z2MC_ClaimSystemConnection` dienen ausschließlich dem koordinierten lokalen
Ablauf. Besitzänderungen verlangen einen passenden geprüften Journalstand und
die exakte Quellen-/Verbindungszuordnung. Sie sind nicht als Bedienaktionen
oder allgemeine Publish-Schnittstelle vorgesehen. Der Rohdatenexport ist privat
und darf nicht als Browserantwort oder Log verwendet werden.

## Umschalten, Abbruch und Wiederholung

1. `prepared`: Alle benötigten Quelldateien und das Manifest sind geprüft.
   Der zentrale Arbeitsbereich ist noch unverändert, die Quellen bleiben bedienbar.
2. `applying`: Alte öffentliche Bedien-, Editor-, Browser- und MQTT-Einstiege
   werden je Quelle vor dem Ziel gesperrt. Bereits laufende lokale Aufrufe
   verhindern die Sperrübernahme; offene MQTT-Aufträge verhindern die Freigabe.
   Nach Beginn der Übergabe werden keine neuen Aufträge aus der Quelle angenommen.
3. Erst nach Sperre aller Quellen und exklusiver Verbindungseigentümerschaft
   wird der vollständig geprüfte Zielstand veröffentlicht und `committed` gesetzt.
4. Bis dahin bleiben die vorbereiteten zentralen Kopien gesperrt. Bei einem
   Abbruch denselben Aufruf wiederholen oder den Rückweg wählen. Ein Update
   entsperrt oder konvertiert die Instanzen nicht automatisch.

Persistente Marker verwenden Vertragsversion 1 und die Rollen `legacy`,
`manager` und `connection`. Ein gesetzter Legacy-Marker hält die bisherige Quelle
für den betreffenden Vorgang gesperrt. Unmarkierte Bestandsinstanzen behalten
ihren bisherigen Modus. Je Instanz schützt eine nicht wartende Eintrittssperre
den Besitzerwechsel gegen gleichzeitig laufende Bedienaufrufe. MQTT-Antworten
bleiben dabei empfangbar und prüfen den Besitzer zusätzlich unter der vorhandenen
Transportsperre. Noch an den Parent übergebene Erweiterungsframes verhindern den
Besitzerwechsel auch dann, wenn eine Antwort bereits abgeschlossen ist. Die
begrenzte flüchtige Liste solcher Übergaben ist keine Befehlswarteschlange und
wird nicht auf einen neuen Besitzer übertragen. Kurze installationsweite
Sperren schützen Verbindungsclaims und das abschließende lokale Schreiben.
Dabei werden keine MQTT-Aufträge ausgeführt. Eine weitere Verwaltung kann weder
dieselbe Verbindung noch über einen zweiten Verbindungseintrag dasselbe Mesh-Topic
schreibend übernehmen. Eine systemweise `ownershipEpoch` im zentralen Datensatz
verhindert alte Editor-Fingerabdrücke auch nach Rückweg und erneuter Übernahme.
Die Routinggeneration der Verbindung wird nicht still geändert.

Alte öffentliche Bedienaufrufe werden nicht entfernt und nicht auf die gerade
ausgewählte Systemzeile umgeleitet. Nach einer Übernahme werden sie verständlich
abgewiesen; das Quellformular nennt die Verwaltungsinstanz. Der rein lesende
Aktionskatalog bleibt für noch nicht übernommene Partnermodule verfügbar und
erteilt keine neue Schreibberechtigung. Die neue gemeinsame Oberfläche und
zielgebundene Betriebsaufrufe stehen noch aus.

Neue Empfängerbestätigungen und Kataloge werden nicht aus Quellpuffern abgeleitet.
Transportpuffer, Browserzugänge, Debug-Leases sowie Aktivierungs-/Prüftoken werden
nicht übernommen. Eine alte Debug-Aufzeichnung wird nicht verlängert; ihre
Z2M-seitige Restlaufzeit läuft selbstständig aus. Statusvariablen der Quelle bleiben
als historische Werte erhalten. Nach dem Umschalten sind Status und Gerätedaten
an den neuen Verbindungen ausdrücklich erneut zu bestätigen.

## Rückweg ohne Datenverlust

Der Rückweg setzt zuerst `rolling_back` und sperrt neue zentrale Bearbeitungen.
Er sichert auch seit der Übernahme entstandene zentrale Entwürfe. Diese Kopie
bleibt erhalten, selbst wenn der Rückweg anschließend an einem Konflikt scheitert.
Die Quellfingerabdrücke werden geprüft. Veränderte noch gesperrte Quellen werden
nicht überschrieben; beide Seiten bleiben bis zur Klärung gesichert und gesperrt.

Vor Freigabe der ersten Quelle wird der exakte ursprüngliche zentrale Arbeitsstand
wiederhergestellt. Anschließend werden nur die passenden Verbindungsclaims und
Quellsperren freigegeben. Schon freigegebene Quellen werden bei einer Wiederholung
nicht zurückgesetzt; dort seitdem entstandene Änderungen bleiben erhalten.
Die neue Verbindung bleibt als Benutzerobjekt erhalten, ihre Konfiguration wird
nicht überschrieben. Ehemalige native Auswahltoken und Browserzugänge bleiben
ungültig. Die bisherigen Auswahl-IDs und Entwürfe bleiben vorhanden.

Der Rückweg ist ausschließlich lokal. Er sendet weder `store` noch `activate`,
`deactivate`, Neustarts oder Gerätebefehle. Er ist keine Wiederherstellung eines
inzwischen extern veränderten Z2M-Betriebs. Nach späteren Z2M-Schreibaufträgen muss
ein betrieblicher Rückweg gesondert unter Revisionskontrolle geplant werden.
Alte Library-Versionen dürfen die neuen Besitzerattribute nicht ignorieren:
vor einem Code-Rollback zuerst den Datenrückweg mit dem aktuellen Code abschließen.

## Nachweise und noch offene Abnahme

`tests/migration.php` verwendet ausschließlich synthetische Instanzen und echte
temporäre Archivdateien. Geprüft sind 1/2/8 Systeme, bytegleiche Quellen,
Partnerfreigaben, wiederholte IDs, native Variablenzuordnung und unveränderte
Actions, konkurrierende Verwaltung und Topic-Aliase, alte Editoraufrufe,
Quelländerungen, beschädigte Archive sowie zusätzliche Sicherung zentraler Änderungen.
Gezielte Abbrüche prüfen Vorbereitung, jede Quellen-/Verbindungsübergabe,
Zielveröffentlichung und jeden Rückgabeschritt. Neue Stubobjekte mit übernommenen
persistenten Attributen und verlorenen Puffern prüfen den Wiederanlauf.
Alle diese Abläufe senden null MQTT-Pakete. Debug darf keine privaten Testwerte
oder Sicherungsinhalte enthalten.

Dies ist kein Nachweis eines echten Symcon-Kernelneustarts oder eines Stromausfalls.
Vor einer praktischen Umstellung bleiben Modulupdate ohne automatische Migration,
native Parent-/Objekterhaltung, Dateirechte, Öffnen alter Tabs, zentraler Editor,
Wiederanlauf und Daten-/Code-Rückweg auf der ausdrücklich freigegebenen
Testinstallation zu prüfen. Am Live-System wurde für diese Umsetzung nichts geändert.

## Sperre nach zentraler Übertragung

Die zentrale Prüfung (`validate`) verändert keine Z2M-Datei und sperrt den
lokalen Rückweg nicht. Ein vorbereiteter oder ausstehender Speicherauftrag wird
dagegen vorsichtshalber als möglicher externer Schreibvorgang behandelt. Die
Verbindung vermerkt den möglichen Versand dauerhaft vor dem MQTT-Senden.
Sobald dieser Versand beobachtet wurde, bleibt die betroffene Übernahmeepoch
auch nach späteren Aufträgen als extern beschrieben markiert.

`RollbackSystemMigration` weist den lokalen Rückweg dann mit
`z2m_write_started` ab, bevor Quellen, Besitzer oder Dokumente verändert werden.
Dies gilt inzwischen auch nach Annahme eines zentralen Start-/Stoppauftrags:
Seine mögliche Änderung des Betriebs oder Wiederanlaufwunsches lässt sich nicht
lokal zurücknehmen. Eine reine Betriebsstatusabfrage sperrt den Rückweg nicht.
Das gilt auch bei verlorener Speicherantwort. Ein lokaler Rollback würde den
geänderten Empfängerstand nicht zurücksetzen. Zunächst den tatsächlichen
Z2M-Stand sichern und einen eigenen betrieblichen Rückweg planen. Ein eindeutig
vor jedem Versand abgewiesener Speicherauftrag erlaubt weiterhin den bisherigen
lokalen Rückweg. Es wird weder eine alte YAML blind eingespielt noch aktiviert.

## Neue Zentralen ohne alte Einzelinstanzen

Für diesen Fall steht inzwischen die direkte Einrichtung über WorkspaceSetup
bereit. Sie liest den vorhandenen Z2M-Stand statt eines alten Modularchivs und
behält sourceInstance 0. Vorschau und lokale Übernahme sind getrennte, ausdrücklich
bediente Schritte. Die Verbindung prüft vor dem Claim erneut eine aktuelle
Einrichtungsfreigabe. Es gibt keinen künstlichen Legacy-Besitzer.

Derselbe private Archivspeicher hält pro Vorgang *-direct und beim Rückweg
*-direct-return. WorkspaceSetup verwaltet das zugehörige begrenzte Journal im
Instanzattribut. Der letzte lokale Vorgang ist fortsetzbar und vor möglichen
Z2M-Speicheraufträgen zurücknehmbar. Der Rückweg betrifft nur dessen Systemplatz;
spätere lokale Änderungen bleiben zusätzlich privat gesichert. Bereits bestehende
Dokumente werden bei der Einrichtung abgewiesen. Details und Funktionen stehen
in WORKSPACE.md und ADR 0027.
