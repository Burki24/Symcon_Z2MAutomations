# Skriptreferenz des zentralen Arbeitsbereichs

Für die normale Einrichtung und Regelverwaltung ist kein eigener Programmcode
nötig. Die Bedienwege stehen in der
[Anleitung der Automationszentrale](../Z2MAutomations/README.md).
Die nachfolgende Referenz erläutert Datenformate und zusätzliche Parameter
für eigene Skripte.

## Lokale Bearbeitung und Übertragung

Die folgenden Funktionen verwalten den zentralen Arbeitsbereich. Alle Rückgaben sind
JSON mit `version`, `ok`, `error` und einer verständlichen `message`.

| Funktion nach `Z2MA_` | Weitere Parameter nach `int InstanceID` | Rückgabe / Wirkung |
| --- | --- | --- |
| GetWorkspaceOverview | keine | Kleine Systemübersicht und ihr `fingerprint`; keine vollständigen Kataloge oder YAML. |
| ConfigureWorkspace | `string Connections, string Fingerprint, string OperationID` | Eine JSON-Liste von 1 bis 8 Verbindungsinstanz-IDs zuordnen. Keine Migration; bestehende Systeme werden nicht entfernt. |
| GetWorkspaceSystem | `string SystemID` | Eigenes Dokument, Auswahl, offene Eingaben, Generation und Bearbeitungsfingerabdruck dieses Systems. |
| UpdateWorkspaceSystem | `string SystemID, int Generation, string Fingerprint, string Data, string OperationID, bool ChangePermissions` | Vollständige lokale Bearbeitungsdaten als JSON übernehmen. `ChangePermissions: false` schützt bestehende Freigaben; Änderungen daran müssen ausdrücklich mit true bestätigt werden. Keine Übertragung an Z2M. |
| SelectWorkspaceSystem | `string SystemID, string Fingerprint, string OperationID` | Auswahl ändern; offene nicht übernommene Eingaben blockieren den Wechsel. Der Bestandseditor wird nicht umgebunden. |
| RefreshWorkspaceCatalog | `string SystemID, int Generation, string Fingerprint, string OperationID` | Bereits bestätigten Katalog aus der Verbindung lokal einlesen. Keine neue MQTT-Anfrage und keine Entwurfsänderung. |
| RequestWorkspaceTransfer | `string SystemID, int Generation, string Fingerprint, string Action, string OperationID, string TransferContext` | Ausschließlich `validate` oder `store` für den exakt angezeigten Entwurf. `TransferContext` aus dem vorherigen Transferstatus. Speicherung verlangt eine aktuelle erfolgreiche Prüfung und eine abgeschlossene Übernahme. |
| GetWorkspaceTransferStatus | `string SystemID` | Lokales Auftragsergebnis unter `transfer`, einschließlich Freigabe der Buttons, Meldung und Fehlerdetails; keine MQTT-Anfrage. |
| RefreshWorkspaceTransfers | keine | Lokale Ergebnisse laufender Systemaufträge einlesen und Anzeige aktualisieren; keine erneute Übertragung. Rückgabe void. |
| GetWorkspaceEntities | `string SourceSystemID, string TargetSystemID, string Use, int Offset, int Limit` | Geräte-/Gruppen-/Szeneneigenschaften für `trigger`, `condition` oder `action`; 1 bis 100 Einträge je Seite. Rückgabe nennt total und hasMore. |

Bis auf RefreshWorkspaceTransfers liefern diese Funktionen einen String. SystemID und Generation aus der Übersicht
oder den Systemdetails verwenden. Es sind keine frei gewählten Namen oder
Symcon-Objekt-IDs. Bei ConfigureWorkspace und SelectWorkspaceSystem stammt der
Fingerabdruck aus der Übersicht; bei UpdateWorkspaceSystem und
RefreshWorkspaceCatalog aus den Details des betroffenen Systems. Eine Änderung
eines anderen Systems entwertet diesen eigenen Bearbeitungskontext nicht.

OperationID ist eine neue 32-stellige kleingeschriebene Hexkennung, etwa aus
`bin2hex(random_bytes(16))`. Bei Wiederholung desselben Auftrags dieselbe Kennung
und exakt dieselben Parameter verwenden. Die letzten 32 erfolgreichen Aufträge
liefern ihre ursprüngliche Quittung ohne zweite Änderung. Andere Angaben mit
derselben Kennung werden abgewiesen. Quittungen sind keine aktuelle Betriebsfreigabe.

Beispiel mit **synthetischen Instanz-IDs**, vor Nutzung durch eigene IDs ersetzen:

```php
$automationID = 12345;
$connections = [23456, 34567];
$overview = json_decode(Z2MA_GetWorkspaceOverview($automationID), true);
$result = json_decode(Z2MA_ConfigureWorkspace(
    $automationID,
    json_encode($connections),
    $overview['fingerprint'],
    bin2hex(random_bytes(16))
), true);
```

`Data` enthält genau `document`, `selection` und `work`. Der anfängliche Wert ist:

```json
{
    "document": null,
    "selection": {"rule": "", "sequence": ""},
    "work": {"rule": null, "sequence": null}
}
```

Ein Dokument enthält `yaml` als exakten UTF-8-Text und `editorDraft` mit
`configuration`, `baseRevision`, `baseChecksum` und `draftChecksum`.
draftChecksum ist der SHA-256 der YAML-Bytes; bei baseRevision 0 ist baseChecksum
null, ansonsten die bekannte SHA-256-Prüfsumme. Auswahlwerte sind vorhandene
Regel-/Folgenkennungen. Offene Bearbeitungen enthalten je `dirty` als Boolean und
`input` als JSON-Objekt oder -Liste. Dokumente bleiben ausdrücklich **ungeprüft**;
die lokale Strukturprüfung ersetzt keine spätere Prüfung in Z2M.

Pro System gelten weiterhin 128 KiB YAML, 100 Regeln und 32 Aktionsfolgen.
Die gesamten Bearbeitungsdaten sind auf 512 KiB, offene Eingaben auf je 64 KiB
und eine einzelne Antwort auf 2 MiB begrenzt. Auslöser und Bedingungen dürfen
keine Sensoren eines fremden Systems verwenden. Fehlende Rechte oder ein
geänderter Verbindungskontext sperren die betroffene Auswahl und erhalten den
Entwurf. Bereits zugeordnete Systeme werden durch diese Aufrufe nicht entfernt.

## Bestandsprüfung vorhandener Einzelinstanzen

Die lesende Bestandsvorschau prüft ausdrücklich benannte bisherige Einzelinstanzen,
ohne sie zu übernehmen. Sie ist vom Einrichtungsablauf der Zentrale getrennt.
Den tatsächlichen Besitzerwechsel und seinen Rückweg beschreibt
[MIGRATION.md](MIGRATION.md).

- `Z2MA_PreviewSystemMigration(InstanceID, SourceInstanceIDs)` prüft eine
  ausdrücklich ausgewählte Liste von einer bis acht bisherigen Automationsinstanzen.
  Der zweite Parameter ist eine JSON-Liste von Instanz-IDs.
  Sie darf auch die aufrufende Instanz selbst enthalten. Die Prüfung liest
  deren Bestand, ohne eine Übernahme oder einen Besitzerwechsel auszulösen.
- Der Bericht nennt pro Quelle Verbindung, Basistopic, Regel-/Folgenanzahl,
  lokale Änderungen und Konflikte. Offene Formulareingaben oder eine Abweichung
  zwischen YAML und Editorstand werden erhalten und als Problem angezeigt.
- „Für weitere Prüfung bereit“ bedeutet keine Übernahme- oder Betriebsfreigabe.
  `canApply` bleibt immer `false`. Es wird nichts angelegt, umgehängt, gespeichert
  oder aktiviert und keine MQTT-Anfrage ausgelöst.

`Z2MA_GetSystemMigrationSource(InstanceID)` liefert dafür nur begrenzte Metadaten
und Prüfsummen. Es exportiert keine YAML, offenen Eingaben oder Sitzungskennungen
und ersetzt keine Sicherung.
