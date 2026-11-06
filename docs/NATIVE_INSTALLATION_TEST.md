# Nativen Installationsbutton verlässlich prüfen (R2)

## Bisheriger Befund

Der erhaltene Einzelbericht für das zweite System scheiterte an der Bedingung
„genau ein Callback nach Bestätigung“, mit null gezählten Treffern. Er hatte
zuvor bereits die neue Prüfphase und den erfolgreichen Installationsabschluss
abgewartet; der Screenshot zeigt diesen Abschluss ebenfalls. Beim ersten System
blieb derselbe Zähler ebenfalls null, das später geänderte Skript akzeptierte
dort zusätzlich die sichtbare Prüfphase. Der erhaltene Skriptstand ist jünger
als der fehlgeschlagene Bericht. Er ersetzt keinen unveränderten Originalstand.
Private Berichte und Bilder bleiben außerhalb des Repositorys.

Der alte Zähler suchte den PHP-Funktionsnamen in Seiten-POSTs und gesendeten
WebSocket-Frames. Die native Konsole delegiert ihre Aufrufe an einen Worker.
Mit einem echten SharedWorker im separaten lokalen Edge-Testbrowser reproduziert:
Der synthetische Server erhält einen Aufruf, die alten Seiten-Listener zählen
null. Das ist ein Fehler der Testinstrumentierung, kein belegter Modulfehler.

## Neue Erfassung

`tests/support/native-console-probe.mjs` beobachtet den öffentlichen
`ips-proxy.makeRequest`-Einstieg vor dem Worker-Transport. Die selbstständige
Funktion lässt sich per Playwright in den jeweiligen Testtab injizieren:

```javascript
const observer = await page.evaluateHandle(installNativeConsoleProbe, {
    instanceID: configuredTestInstanceID
});
const before = await observer.evaluate(probe => probe.snapshot());
// Erst im ausdrücklich beauftragten Praxislauf die nativen Buttons bedienen.
const after = await observer.evaluate(probe => probe.snapshot());
const restored = await observer.evaluate(probe => probe.restore());
```

Die Instanz-ID kommt aus der privaten Testkonfiguration. Keine reale ID, URL,
Buildkennung oder Auswahl ist im Probe-Code eingebettet. Die Erfassung ändert
weder Argumente, Empfänger, Rückgabewert noch Promise-Identität. Sie speichert
keine Skripttexte, Antworten, URLs, Parameter oder Exception-Texte. Ausgegeben
werden ausschließlich Zähler und feste Ergebnis-/Fehlerkategorien.

Erfasst werden die beiden regulären Skriptmethoden `IPS_RunScriptTextWait`
und `IPS_RunScriptTextWaitEx`, getrennt nach Installations- und Statusaufrufen.
Textwerte und Kommentare zählen nicht als Funktionsaufrufe. Fremde Instanzbindung,
unklare Skriptform, doppelte Instrumentierung, unerwartetes Ergebnis oder Überlauf
verhindern einen gültigen Nachweis. Die Aufzeichnung ist auf 64 passende
RPC-Übergaben und zwei MiB pro geprüftem Skript begrenzt. Sie ist kein allgemeiner
PHP-Parser; insbesondere Heredoc wird als nicht auswertbar gemeldet.

`installRequests` zählt RPC-Übergaben mit Installationscode, `installCalls`
die darin erkannten Aufrufstellen. Beide müssen nach einer Bestätigung genau
eins betragen. `pending`, `returned`, `feedback` und `rejected` unterscheiden
ausstehende Antwort, leere Rückgabe, Rückmeldung und Fehler. Ein zusätzlicher
Aufruf bleibt sichtbar; es gibt keine Entdopplung oder Ersatzbedingung anhand
einer angezeigten Prüfphase. `restore()` stellt die ursprüngliche Methode wieder
her und überschreibt keine zwischenzeitlich fremd geänderte Instrumentierung.

Eine RPC-Übergabe ist noch kein Beweis der PHP-Ausführung. Dafür die bestehenden
nativen Debugmeldungen der Zentrale heranziehen; keinen neuen Modul-Logkanal
oder eigenen Debugzähler einbauen.

## Bereits lokal ausgeführte Prüfungen

- Sieben Regressionstests in `tests/native-console-probe.test.mjs`, eingebunden
  in `npm test` und `php tests/run.php`: unveränderte Weitergabe, Abbruch ohne
  Aufruf, Status getrennt, mehrfacher Aufruf, Fehler, Datenschutz, Grenzen,
  Wiederherstellung und selbstständige Browserinjektion.
- `tests/native-console-probe.browser.mjs`: echter SharedWorker und eigener
  Loopback-Server in einem unsichtbaren Edge-Prozess. Nein sendet nichts, Ja
  sendet genau einen synthetischen Auftrag, Status sendet keinen weiteren
  Installationsauftrag. Alter Zähler null, neue Erfassung eins; nach Wiederherstellung
  wird nicht weitergezählt. Keine Symcon-Verbindung, kein PHP- oder MQTT-Auftrag.
- `tests/workspace-installation.php`: tatsächlich erzeugte native Callbacks
  auf zwei synthetischen Systemen. Je ein kanonisches Debug-START/ENDE-Paar
  für `RequestWorkspaceInstallation`, genau ein synthetischer Upload und kein
  weiterer Installationsaufruf durch den Statusbutton. Vorhandene Entwürfe und
  absichtlich offene Eingaben bleiben erhalten. Die Auswahl für den Formularaufbau
  wird ausschließlich in der Fixture gesetzt; die öffentliche Navigation wird
  wegen dieser offenen Eingaben bewusst nicht verwendet.

Den optionalen Browsernachweis mit bereits außerhalb des Repositorys
bereitgestelltem Playwright ausführen:

```powershell
$env:Z2MA_PLAYWRIGHT_MODULE = 'file:///C:/path/to/playwright/index.mjs'
node tests/native-console-probe.browser.mjs
```

Der Browsernachweis bleibt vollständig synthetisch und gehört nicht zum
CI-Pflichtlauf. Es wird keine neue Playwright-Abhängigkeit installiert oder
in die Library aufgenommen. Der normale Node-Test benötigt keinen Browser.

## Noch fehlender begrenzter Praxisnachweis

R2 bleibt offen, bis nach eigenem Praxisauftrag auf dem zweiten System die
folgende zusammenhängende Kette mit frischem Zustand bestätigt ist:

| Schritt | RPC-Nachweis | Nativer Debug-/Betriebsnachweis |
| --- | --- | --- |
| Vorbereiten | Probe aktiv/gültig, Ausgangszähler bekannt; Zielinstanz und Panel eindeutig | Bestehenden Entwurf, Sitzung, Build und Betrieb gesichert erfassen |
| Nein | Kein zusätzlicher Installationsaufruf | Kein START für `RequestWorkspaceInstallation`, kein Upload |
| Ja | Genau eine RPC-Übergabe und eine Aufrufstelle, leere Rückgabe; keine ausstehende oder abgelehnte Antwort | Genau ein START/ENDE-Paar derselben Instanz mit gleichem Vorgangs-/Aufrufbezug; genau ein zugeordneter Upload |
| Abschluss | Keine weitere Installation durch Beobachten | Neue Erweiterungssitzung und erwarteter Build bestätigt, gespeicherte Regeln erhalten |
| Statusbutton | Genau ein zusätzlicher Statusaufruf, Installationszähler unverändert | Kein weiterer Installationseinstieg oder Upload |
| Aufräumen | Ursprüngliche Methode wiederhergestellt | Aufzeichnungen beenden, ursprünglichen Betrieb prüfen/wiederherstellen |

Die neue Erfassung auf eine sichtbare Prüfphase zurückfallen zu lassen würde
den ursprünglichen Nachweisfehler wieder verdecken. Bisherige erfolgreiche
Installation und spätere Statusprüfung bleiben gültige Teilbelege; sie werden
nicht als Beleg der exakten Aufrufanzahl umgedeutet. Dieser Ablauf erteilt keinen
Installations-, Neustart- oder Schaltauftrag. Die Testinstallation kann dieselben
produktiven Z2M-Systeme verwenden; daher keine native Bestätigung automatisieren,
bevor der konkrete Praxislauf beauftragt ist.
