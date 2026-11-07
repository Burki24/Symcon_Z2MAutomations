# Repository-Umbenennung vorbereiten

Stand: 07.11.2026. **Nur lokal vorbereitet; noch keine Umbenennung auf GitHub.**

| Bestandteil | Bestätigter Name |
| --- | --- |
| Bestehendes Symcon-Repository | `Burki24/Symcon_Z2MAutomations` |
| Eigenständige App und neues Repository | `Z2M-Automations` |
| Plattformangaben der App | Z2M-Automations für Windows / Z2M-Automations für Linux |

Das bestehende GitHub-Repository wird umbenannt, nicht neu angelegt. Seine
Repository-ID ist `1435382046`; der Online-Abgleich meldet noch
`Burki24/Z2MAutomations`, Standardbranch `main` und keine GitHub-Pages-Seite.
Die beiden gewünschten Zielnamen waren bei der lesenden Abfrage nicht
erreichbar (404). Das reserviert keinen Namen; bei der Durchführung erneut prüfen.

## Betroffene Verweise und vorbereitete Änderungen

| Stelle | Vorbereitung / Auswirkung |
| --- | --- |
| `library.json` | Repository-URL auf `https://github.com/Burki24/Symcon_Z2MAutomations` gesetzt. |
| `Z2MAutomations/module.json`, `Z2MConnection/module.json` | Dokumentationslinks auf den neuen Repositorynamen gesetzt; Dokumentationsbranch weiterhin `dev`. |
| `README.md` | Beide Actions-Badges samt Zielseiten und Installationslink angepasst; Badgebranch weiterhin `main`. |
| `docs/BETA_ABNAHME.md` | Vorbereitete Store-Git-URL und Links zu den bestehenden CI-Läufen angepasst; Lauf-IDs und Nachweise erhalten. |
| `PROJECT_CONTEXT.md`, `docs/DESKTOP_PLAN.md`, ADR 0034 | Bestätigte Produktaufteilung und App-Name übernommen. Vorgeschlagener neuer App-Arbeitsordner: `E:\git\Z2M-Automations`. |
| `Symcon_ModuleHelper/.github/helper-consumers.json` | Genau den bestehenden Consumer auf `Burki24/Symcon_Z2MAutomations` geändert, Zielbranch weiterhin `dev`. |
| `Symcon_ModuleHelper/HELPER_SYNC_SETUP.md` | Consumername und Reihenfolge der Freigabe ergänzt. |
| Lokales Git-Remote `origin` | Noch unverändert auf `https://github.com/Burki24/Z2MAutomations.git`; erst nach der GitHub-Umbenennung umstellen. |
| Symcon und Module Store | Installierte Repositoryeinträge und gegebenenfalls vorhandene Store-Git-URL nach der Umbenennung prüfen. In diesem Auftrag weder ausgelesen noch geändert. Keine Library oder Instanz neu anlegen. |
| GitHub App und Automationen | App-Zugriff, Versionsautomatik, Helper-Verteilung und Schutzregeln nach der Umbenennung online nachweisen. Keine Secrets, Berechtigungen oder Workflows ausgelöst/geändert. |

Geprüft wurden die getrackten eigenen Projektdateien einschließlich versteckter
CI-/Sync-Dateien sowie die zugehörigen lokalen Repositories Symcon_ModuleHelper,
Symcon_ModuleCI, SymconDevelopment, SymconiCloud und OpenCalendar. Ergänzend
wurde die GitHub-Codesuche in den drei zentralen Repositories abgeglichen.
In ModuleCI betreffen die Treffer den Modulnamen in Texten beziehungsweise
synthetische Modulpfade, keine umzubiegenden Repositoryziele. Keine weitere
namegebundene URL in den übrigen geprüften Repositories gefunden.

## Was unverändert bleibt

- Libraryname `Z2MAutomations`, Library-/Modul-GUIDs, Modulordner,
  Klassen/Namespaces und öffentliche Präfixe `Z2MA` und `Z2MC`.
- Instanzen, Entwürfe, MQTT-Topics, Hooks, Sitzungen, gespeicherte Regeln und
  Laufzeitverhalten. Keine Konfigurationsmigration und kein Z2M-Upload.
- Lizenz, Library-Version, Build und Datum; die bestehende automatische
  Versionierung wird bei späteren Benutzer-Pushes regulär weiterarbeiten.
- `dev` als Arbeitsbranch, `main` als Hauptbranch. Die Workflows verwenden
  fremde Actions aus Symcon_ModuleCI und keine eigene Action unter dem alten Namen.
- `.helper-sync.json`, unverändertes `libs/helper` samt Manifest, `.gitmodules`
  und die fremden StylePHP-/SymconStubs-Quellen. Der private npm-Paketname ist
  keine Repositoryadresse und wird nicht für die App umgedeutet.
- Lokaler Ordner `E:\git\Z2MAutomations` und Projektzuordnung. Eine spätere
  Ordnerumbenennung ist optional und getrennt von der GitHub-Umbenennung zu behandeln.

## Durchführung in dieser Reihenfolge

1. Diese lokalen Änderungen prüfen. Bis zur GitHub-Umbenennung nicht verteilen:
   neue Dokumentationslinks und das neue Helper-Ziel sind vorher noch nicht erreichbar.
   Laufende Helper-/Metadatenläufe vor der Umbenennung abschließen lassen.
2. Auf GitHub beim **bestehenden** Repository unter **Settings → General →
   Repository name** den Namen `Symcon_Z2MAutomations` setzen. Danach dieselbe
   Repository-ID, `main`/`dev` und die bestehenden Inhalte bestätigen. Unter dem
   alten Namen `Z2MAutomations` kein neues Repository anlegen, damit die
   Weiterleitung bestehen bleibt. Die App bekommt den anderen Namen `Z2M-Automations`.
3. In GitHub Desktop die Remote-URL des vorhandenen lokalen Repositorys auf
   `https://github.com/Burki24/Symcon_Z2MAutomations.git` setzen und Fetch prüfen.
   Arbeitsordner und Arbeitsbranch `dev` beibehalten; keinen Ersatzclone benötigen.
4. Die vorbereiteten Library-Änderungen über GitHub Desktop auf `dev` committen
   und pushen. Tests, Style und automatischen Versionscommit im umbenannten
   Repository prüfen; Metadatencommit regulär zurückholen. Freigabe nach `main`
   und Rücksynchronisierung wie bisher durch den Benutzer durchführen.
5. App-Zugriff auf das umbenannte Repository sowie die vorhandenen Variablen/
   Secrets für die Versionsautomatik prüfen, ohne Geheimnisse auszugeben.
   Erst dann den vorbereiteten Consumer-Eintrag im Helper-Repository über dessen
   `dev`/`main`-Verfahren freigeben. Verteilung nicht vor der Umbenennung starten.
6. Beim nächsten ausdrücklich beauftragten Helper-Sync prüfen: neues Repository
   im App-Token, Subscription aus `dev`, richtige PR-Basis und identisches
   `head.repo.full_name`. Ein unverändertes Paket darf korrekt übersprungen werden;
   es muss dafür kein künstliches Helper-Update erzeugt werden.
7. Badges, Modul-Dokumentationslinks und Installationslink auf dem jeweiligen
   Branch öffnen. Anschließend einen Symcon-Repositoryabruf zunächst im Testsystem
   prüfen und die bestehende Repositoryzuordnung gegebenenfalls gezielt ändern.
   Produktion und Store erst mit gesondertem Auftrag prüfen/anpassen.

GitHub beschreibt Weiterleitungen für Web- und Git-Zugriffe, empfiehlt aber,
lokale Remotes zu aktualisieren. Daraus folgt kein Nachweis für den Symcon-
Modullader oder namegebundene Automationen. Der Helper-Workflow fordert App-Tokens
für die Namen aus seiner Consumerliste an und vergleicht die PR-Herkunft exakt;
deshalb wird dessen Konfiguration ausdrücklich angepasst.

## Rückweg und noch offene Nachweise

Vor der Durchführung bleiben GitHub, Remotes und Systeme unverändert. Falls die
Umbenennung scheitert, die vorbereiteten neuen URL-/Consumerwerte nicht verteilen.
Ein Rückweg nach erfolgter Umbenennung muss GitHub-Name, Remote, Dokumentations-/
Metadatenlinks und Helper-Ziel gemeinsam auf den bisherigen Namen zurücksetzen;
keine Instanzen, Entwürfe oder Helper-Schutzprüfungen löschen/lockern.

Noch offen sind die tatsächliche GitHub-Umbenennung, Remoteumstellung, CI- und
Versionslauf unter dem neuen Namen, der Online-Nachweis der Helper-Verteilung
und ein Symcon-Repositoryabruf. Lokale Tests belegen diese externen Schritte nicht.
Commit, Push und Freigaben bleiben beim Benutzer; kein Releaseauftrag.

Lokal bestanden unter PHP 8.5.10: `php tests/run.php` einschließlich 221
JavaScript-Tests und Kodierungsprüfung, `php tests/validate_structure.php`,
offizieller StylePHP-Check der 18 eigenen JSON-Dateien in isolierter Kopie und
PHP CS Fixer 3.95.27 im Prüfmodus (0/152). Zusätzlich die zehn vorhandenen
Helper-Verteilungstests und der tatsächliche Workflow-Namensresolver offline
geprüft. Vergleich gegen Git-HEAD bestätigt unveränderte Identitäten und
Versionswerte; keine getrackten Helper-/Erweiterungsdateien verändert.

Quelle: [GitHub – Renaming a repository](https://docs.github.com/en/repositories/creating-and-managing-repositories/renaming-a-repository).
