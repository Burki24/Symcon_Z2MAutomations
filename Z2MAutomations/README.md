# Z2MAutomations

Mit diesem Modul erstellen und bearbeiten Sie Automationen per Klick. Symcon
überträgt die Regeln an die eigene Z2M-Erweiterung. Nach dem Speichern und
Aktivieren führt Zigbee2MQTT sie auch bei ausgeschaltetem Symcon aus.

Für den Einstieg:

1. [MQTT-Verbindung einrichten](#voraussetzungen-und-einrichtung).
2. [Erweiterung installieren](#erweiterung-installieren).
3. [Erste Automation erstellen](#einfache-automation-per-klick-erstellen).
4. [Änderungen übernehmen, prüfen, speichern und starten](#entwurf-speichern-und-starten).

Für vorhandene Regeln: [Suchen und Kopieren](#viele-automationen-finden-und-kopieren),
[Bearbeiten und Löschen](#vorhandene-automationen-bearbeiten-oder-löschen).
Weitere Abläufe: [Zeit und Wochentage](#mehrere-uhrzeiten-und-erlaubte-wochentage),
[Sonnenzeiten](#automationen-bei-sonnenauf--oder-sonnenuntergang),
[gemeinsame Gerätebefehle und Farben](#mehrere-eigenschaften-gemeinsam-setzen-und-farben-auswählen),
[Szenen](#vorhandene-szenen-aufrufen), [Aktionsfolgen](#eine-aktionsfolge-mehrfach-verwenden),
[zweites Z2M-System](#ein-gerät-auf-dem-zweiten-z2m-system-schalten) und
[Symcon-Ereignisse importieren](#vorhandene-symcon-ereignisse-importieren).

## Automationsdesigner im Browser: zunächst nur ansehen

Im nativen Modul steht „Nur Gerätedaten aktualisieren“ bereit. Damit lesen Sie
Geräte, Kanäle, Gruppen und Szenen aus Z2M neu ein. Symcon ergänzt die Zuordnung
zu seinen Variablen: Sensoren bleiben für Auslöser und Bedingungen verfügbar;
Aktionen setzen eine tatsächlich schaltbare Symcon-Variable voraus. Entwürfe,
offene Eingaben und laufende Automationen bleiben erhalten. Danach im Browser
„Ansicht aktualisieren“ wählen. Die letzte erfolgreiche Geräteaktualisierung
wird im Modul und im Browser angezeigt; sie gilt für diese Z2M-Verbindung.
Bei einem zweiten System dessen Gerätedaten im zugehörigen Modul aktualisieren.
Für diese Funktion auch die mitgelieferte Z2M-Erweiterung aktualisieren.

„Geräte und gespeicherte Automationen laden“ bleibt ein eigener Vorgang:
Dieser lädt zusätzlich die gespeicherten Regeln und ersetzt nach Bestätigung
den lokalen Entwurf. Verwenden Sie ihn nicht nur zum Aktualisieren der Geräte.

„Automationsdesigner öffnen (nur lesen)“ öffnet die übersichtliche Browseransicht
dieser Modulinstanz. Links Regeln, Zeitautomationen, Aktionsfolgen oder
„Geräte & Kanäle“ auswählen. In der Mitte einen Baustein anklicken; rechts
erscheinen seine Angaben. Auch Bedingungen, Wartezeiten, Szenen, gemeinsame
Gerätebefehle und Ziele des zweiten Systems werden dargestellt.

Diese erste Anbindung zeigt ausschließlich bereits im Modul vorhandene Daten.
„Ansicht aktualisieren“ liest sie erneut aus dem Modul. Es fragt keine Geräte
oder Z2M ab und ersetzt keine Entwürfe. Der angezeigte Betrieb ist der zuletzt
bestätigte Stand; ein eingeschalteter Regelschalter im Entwurf bedeutet nicht
automatisch, dass diese Fassung bereits läuft.

Fehlen Daten für die aktuelle Verbindung, erscheint ein Hinweis. Ein noch aus
einer anderen Verbindung stammender Entwurf wird nicht als aktueller Stand
angezeigt oder überschrieben. Bitte den vorhandenen Entwurf zuerst im nativen
Formular prüfen. Noch nicht übernommene Formulareingaben bleiben dort erhalten
und werden in der Browseransicht lediglich als ausstehend gemeldet.

Bearbeiten, Speichern, Aktivieren und Stoppen erfolgen weiterhin im nativen
Formular. Die Browseransicht bietet dafür noch keine Bedienelemente. Nach 15
Minuten den Designer im Modul erneut öffnen; Aktualisieren verlängert den Zugang
nicht. Der Browser muss den Symcon-Webserver derselben Installation erreichen
können. Ein Modulupdate genügt, die Z2M-Erweiterung bleibt unverändert.

Die [Offline-Designer-Vorschau](../docs/prototypes/automation-designer/README.md)
bleibt zusätzlich mit erfundenen Beispielen zum Ausprobieren verfügbar. Ihre
simulierten Speicher- und Betriebsbuttons sind vom echten lesenden Designer
getrennt.

## Voraussetzungen und Einrichtung

IP-Symcon ab 9.0 mit PHP 8.5 sowie eine konfigurierte MQTT-Client- oder
MQTT-Server-Instanz, die mit derselben Z2M-Installation verbunden ist.

1. Instanz anlegen und als übergeordnete Verbindung den vorhandenen MQTT Client
   oder MQTT Server auswählen. Gemeinsame Nutzung mit anderen Modulen ist möglich.
2. Das Basistopic der Z2M-Installation eintragen.
3. Bei MQTT Client das Basistopic mit /bridge/# und /symcon/automations/#
   abonnieren. Ein umfassenderes Abonnement wie # genügt. Die Gateway-
   Einstellungen werden nicht geändert.
4. „MQTT-Anbindung aktivieren“ einschalten und Änderungen übernehmen.
5. Bei Bedarf „Zigbee2MQTT-Verbindung prüfen“ betätigen. Das Ergebnis erscheint
   als Popup und in der Statusvariable „Verbindungsprüfung“.

Broker, Zugangsdaten und TLS werden ausschließlich im vorhandenen Gateway und
seiner Socket-Verbindung eingerichtet. Die Library benötigt keine eigene Kopie.

## MQTT-Gateway und Suchbereich

Für zwei Z2M-Systeme mit unterschiedlichen Basistopics werden zwei getrennte
Automationsinstanzen verwendet, auch bei einem gemeinsamen MQTT-Broker oder
Symcon-MQTT-Client.
Jede Instanz nutzt ausschließlich ihre eigene übergeordnete MQTT-Verbindung
und das unter **Zigbee2MQTT-Basistopic** konfigurierte Topic (Groß-/Kleinschreibung
beachten). Gleichlautende Topics, IEEE-Kennungen oder Gruppen-IDs an anderen
Gateways werden nicht vermischt.

Unter **An diesem MQTT-Gateway gefundene Basistopics** lassen sich Topics aus
vorhandenen Z2M-Geräte-, Gruppen- und Bridge-Instanzen auswählen. Die Auswahl
befüllt nur das Basistopic-Feld; eine manuelle Eingabe bleibt möglich. Danach
**Änderungen übernehmen** und für den Import den Z2M-Katalog neu laden.
Die Auswahl ändert weder das Gateway noch Konfigurationen anderer Instanzen.
Die Vorschläge werden beim Öffnen des Formulars aus der lokalen Installation
ermittelt; es wird kein MQTT-Netzwerkscan durchgeführt.

Nach **Gateway ändern** wird die Topic-Auswahl im geöffneten Formular erneuert.
Dabei wird das Formular neu geladen. Übernehmen Sie noch offene Formulareingaben
vor dem Gateway-Wechsel; bereits übernommene Regelentwürfe bleiben erhalten.
Das bisher gespeicherte Basistopic bleibt erhalten. Steht dort **hier nicht
gefunden**, wählen Sie das passende Topic aus der Liste oder tragen es manuell
ein und übernehmen die Einstellungen. Bei mehreren Z2M-Systemen am selben
Gateway muss das gewünschte System ausdrücklich ausgewählt werden. Ein
Gateway-Wechsel allein ändert keine Regelentwürfe. Passt das bisher gespeicherte
Topic nach dem Wechsel nicht zu den erkannten Topics, wartet die automatische
Statusprüfung auf die Übernahme der Einstellungen. Auch ein bewusst manuell
eingetragenes Topic lässt sich übernehmen und prüfen.

**Vorhandene Ereignisse prüfen** findet passende Symcon-Ereignisse anhand ihrer
Quellvariablen unabhängig vom Ablageort im Objektbaum. Auch ohne geladenen
Z2M-Katalog werden diese Ereignisse angezeigt. Ob sie importierbar sind, wird
anschließend gegen den Katalog desselben Gateways und Topics geprüft. Nach einem
Gateway- oder Topic-Wechsel werden bisherige Suchergebnisse und Freigaben nicht
weiter angeboten. Es sind keine konkreten Instanz-IDs im Modul hinterlegt.

## Verbindung prüfen und Rückmeldung erhalten

Mit **Zigbee2MQTT-Verbindung prüfen** starten Sie eine Prüfung über das zugeordnete
MQTT-Gateway. Sobald eine passende Antwort vorliegt, erscheint ein Popup mit dem
Ergebnis. Bei Erfolg bestätigt es, dass Zigbee2MQTT erreichbar ist und einen
fehlerfreien Zustand meldet. Meldet Z2M ein Problem, fehlt die Verbindung oder
bleibt die Antwort aus, nennt das Popup den nächsten Prüfschritt.

Der Versand allein wird noch nicht als Erfolg angezeigt. Mehrere Klicks während
derselben Prüfung erzeugen keine weiteren Anfragen. Automatische Prüfungen beim
Öffnen des Formulars, nach einem Modulupdate oder nach Wiederverbindung bleiben
ohne Popup. Die Prüfung schaltet keine Geräte und verändert keine Automationen.

## Erweiterung installieren

Z2M muss erreichbar sein und externe JavaScript-Erweiterungen erlauben.
Ist diese Funktion in Z2M ausgeschaltet, lehnt das Modul die Installation ab.
Fehlt dazu noch eine Statusinformation, prüft die offizielle Z2M-Schnittstelle
den Installationsauftrag. Das Modul schaltet diese Funktion nicht selbst ein.
Die bisher praktisch belegte Z2M-Version ist **2.14.2-dev**. Z2M 2.14.2 ist
das Schnittstellenziel; für die unveränderte stabile Ausgabe liegt keine eigene
Praxisabnahme vor. Siehe [Versionsübersicht und Grenzen](../docs/COMPATIBILITY.md).

„Mitgelieferte Erweiterung installieren oder aktualisieren“ lädt ausschließlich
die mitgelieferte z2m-automations.mjs über MQTT hoch. Die Bestätigung weist auf
das Ersetzen einer bereits vorhandenen Datei dieses Namens hin. Nach der
Z2M-Upload-Antwort fragt das Modul den Empfänger aktiv ab. Erst dessen passende
Antwort bestätigt die erfolgreiche Installation. Eine vorhandene Datei oder
ein gespeicherter MQTT-Status allein genügen nicht.

Die Z2M-API schreibt die JavaScript-Datei vor dem Laden. Ein Ladefehler ist
deshalb kein Nachweis einer unveränderten früheren Datei. Das Modul führt
keine automatische Rücknahme auf eine vorherige JavaScript-Version aus.

Der Installationsbutton zeigt nach Abschluss ein Popup mit Erfolg oder einer
Fehlerbeschreibung. Erfolgreicher Upload und bestätigte Bereitschaft der
Erweiterung sind beide erforderlich. Ein bloßer Dateieintrag reicht nicht.
Z2M lädt die Erweiterung beim Speichern über seine Schnittstelle neu; ein
zusätzlicher manueller Neustart ist dafür nicht nötig.

Bei bereits installierter Erweiterung genügt „Erweiterungsstatus abfragen“.
Das Modul aktiviert kein externes JavaScript und löst keinen Z2M-Neustart aus.

## Erweiterungsstand und Updatehinweis

Unter **Einrichtung und Diagnose** stehen der **installierte Erweiterungsstand**
und der **mitgelieferte Erweiterungsstand**, jeweils mit Version und kurzer
Buildkennung. Die Kennung wird automatisch aus dem Erweiterungscode erzeugt.
Sie erkennt Änderungen auch dann, wenn die Versionsnummer gleich bleibt.

**Aktuell** bedeutet, dass beide Stände übereinstimmen. **Aktualisierung verfügbar**
bedeutet, dass sie sich unterscheiden. Mit **Mitgelieferte Erweiterung installieren
oder aktualisieren** übernehmen Sie den Stand aus dem Modul. Die Kennung selbst
ist kein Datum und sagt nicht, welcher Stand jünger ist.

Beim Öffnen des Formulars und nach einem Modulupdate erfolgt die vorhandene
automatische Statusprüfung. **Erweiterungsstatus abfragen** aktualisiert die
Anzeige auch manuell. Nach bestätigter Installation wird der Stand erneut geprüft.
Die Anzeige installiert oder aktiviert selbst keine Erweiterung oder Automation.

Bei **Noch nicht geprüft** fehlt eine bestätigte Statusantwort. Meldet eine ältere
Erweiterung **ohne Buildkennung**, lässt sich ihr Stand noch nicht vergleichen.
Installieren beziehungsweise aktualisieren Sie dann die mitgelieferte Erweiterung
und fragen Sie deren Status erneut ab. Für die neue Anzeige zunächst das Modul
aktualisieren; für den vollständigen Vergleich auch die Erweiterung aktualisieren.

## Einfache Automation per Klick erstellen

1. Neue mitgelieferte Erweiterung installieren bzw. aktualisieren und den
   Erweiterungsstatus abfragen.
2. „Geräte und gespeicherte Automationen laden“ bestätigen. Damit wird der lokale
   Entwurf neu geladen; die in Z2M gespeicherten Regeln werden noch nicht geändert.
3. Den Bereich „Durch ein Gerät ausgelöste Automationen“ öffnen und in der Auswahl
   „Neue Automation erstellen“ wählen und „Automation bearbeiten“ aufklappen. Die Bearbeitung öffnet sich direkt in diesem Bereich.
   Namen eingeben und unter „Wenn ein Gerät ein Ereignis meldet“ in der
   Auslöserliste ein Gerät und Ereignis hinzufügen, beispielsweise
   „Tasterereignis: Einfach drücken“. Weitere Auslöser sind optional.
   Für eine Uhrzeitregel stattdessen im Bereich „Zeitgesteuerte Automationen“
   „Neue Automation erstellen“ wählen, die Bearbeitung aufklappen und
   Uhrzeit/Zeitzone einstellen.
4. Bei Bedarf „Nur wenn … (optional)“ öffnen und Bedingungen hinzufügen.
   Unter „Dann diese Aktionen ausführen“ einen Schritt hinzufügen, Gerät und Aktion auswählen,
   beispielsweise „Zustand: Ein“, und die Wartezeit vor diesem Schritt eingeben.
5. „Automation zum Entwurf hinzufügen“ betätigen. Weitere einfache Regeln können
   vor der Übertragung ergänzt werden. Vorhandene gespeicherte Regeln bleiben
   im Entwurf vollständig erhalten.
6. „Entwurf in Z2M prüfen“ und anschließend „Geprüften Entwurf in Z2M speichern“
   bestätigen. Die YAML wird automatisch erzeugt; sie muss weder angezeigt noch
   bearbeitet werden. Die Liste zeigt Namen, erste Quelle, erstes Ziel und den
   gewünschten Aktivierungszustand der Regeln im Entwurf.
7. „Gespeicherte Automationen in Z2M aktivieren“ bestätigen.

Der Editor bietet Geräteereignisse, Zustände und Messwerte als Auslöser sowie
Geräte- und Gruppenaktionen, Szenen, Zahlenwerte und Farben an, soweit das Gerät
und seine Symcon-Variablen sie unterstützen. Gerät, Eigenschaft bzw. Kanal und
Wert werden getrennt gewählt. Reine Anzeigevariablen stehen für Auslöser und
Bedingungen bereit, für Aktionen nur schaltbare Variablen mit Z2M-Schreibrecht.

Der Gerätekatalog ist begrenzt: höchstens 256 Geräte und insgesamt 1024
Auswahlwerte. Grundlegende Schaltzustände erhalten Vorrang vor Sonderoptionen.
Nicht unterstützte Eigenschaften werden ausgelassen. Die IEEE-Adresse neben
dem Gerätenamen unterscheidet gleichnamige Geräte. Gemischte Geräte-/Zeit-
auslöser und andere nicht darstellbare Strukturen bleiben der erweiterten
YAML-Ansicht vorbehalten. Solche Regeln lassen sich im Formular umbenennen,
ein-/ausschalten oder löschen; ihre übrigen Angaben bleiben erhalten.

## Bearbeitungsstand erkennen und Änderungen übernehmen

Der Bereich **Bearbeitungsstand und Z2M-Betrieb** erklärt den aktuellen Stand:

- **Formular:** Haben Sie Felder geändert, sind diese Angaben zunächst noch
  nicht im Entwurf. Klicken Sie im jeweiligen Bearbeitungsbereich auf den Button
  zum Übernehmen in den Entwurf. Für diese Bearbeitung erscheint kein allgemeiner
  orangefarbener Übernehmen-Button von Symcon.
- **Entwurf:** Die Änderungen liegen anschließend in Symcon. Die Anzeige sagt,
  ob der Entwurf noch geprüft werden muss, erfolgreich geprüft oder bereits in
  Z2M gespeichert ist. Zum Übertragen verwenden Sie die gemeinsamen Buttons
  **Entwurf in Z2M prüfen** und **Geprüften Entwurf in Z2M speichern**.
- **Z2M-Betrieb:** Diese Angabe zeigt den bestätigten Ausführungszustand.
  Bereits laufende Automationen verwenden weiterhin den gespeicherten Stand,
  solange Änderungen nur im Formular oder im Entwurf stehen. Nach dem Speichern
  aktivieren Sie die gespeicherten Automationen wie bisher.

Beim Wechsel zu einer anderen Automation, zwischen geräteausgelöster und
zeitgesteuerter Bearbeitung oder zu einer anderen Aktionsfolge fragt das Modul
bei offenen Änderungen nach. **Weiter bearbeiten** erhält Ihre Eingaben und die
bisherige Auswahl. Nur **Änderungen verwerfen und wechseln** verwirft die
Formulareingaben. Der vorhandene Entwurf und die in Z2M gespeicherten Regeln
werden dabei nicht geändert.

Prüfung, Laden gespeicherter Regeln, Import, Löschen, Speicherung und Aktivierung
weisen bei offenen Formulareingaben auf die nötige Übernahme hin. Statusabfragen
und Stopp können Sie weiterhin verwenden. Regelbearbeitung und Aktionsfolgen
werden getrennt verfolgt; das Übernehmen einer Regel verwirft keine offene
Bearbeitung einer Aktionsfolge. Die Änderung benötigt nur ein Modulupdate.

## Entwurf, Speichern und Starten

Ein **Entwurf** sind die Automationen, die Sie gerade in Symcon bearbeiten.
**Prüfen** kontrolliert, ob Z2M diesen Entwurf verwenden kann. **Speichern** sendet
ihn an Z2M und ersetzt dort die bisher gespeicherten Automationen. Bei geändertem
Inhalt wird die bisherige Ausführung gestoppt. Erst **Aktivieren** startet alle
in diesem gespeicherten Stand eingeschalteten Regeln. **Stoppen** beendet die
Ausführung, ohne die gespeicherten Regeln zu löschen.

Die Meldungen zeigen, ob ein Vorgang erfolgreich, fehlgeschlagen oder noch offen
ist. Wenn eine Aktion erforderlich ist, nennt die Meldung den nächsten Schritt.
„Noch nicht geprüft“ oder „Noch nicht bestätigt“ bedeutet nicht automatisch,
dass die Verbindung gestört ist. Mit **Erweiterungsstatus abfragen** lässt sich
der aktuelle Stand aus Z2M laden.

Nach dem Speichern bestätigt das Modul den aktuellen Z2M-Status automatisch.
Währenddessen steht dort **Die Automationen sind in Z2M gespeichert. Der aktuelle
Laufzeitstatus wird automatisch abgefragt. Bitte kurz warten.** Sobald die passende
Antwort vorliegt, wird der Aktivierungsbutton freigegeben. Dies aktiviert noch
keine Regel. Ein bereits gespeicherter unveränderter Entwurf wird als gespeichert
angezeigt; erst neue Änderungen benötigen wieder eine Prüfung und Speicherung.

Fehlt die anschließende Statusantwort, bleibt die Speicherung trotzdem bestätigt.
Die Meldung fordert dann **Erweiterungsstatus abfragen**. Wegen dieser Meldung
nicht erneut speichern. Die Aktivierung bleibt bis zur Statusbestätigung gesperrt.

Lehnt **Entwurf in Z2M prüfen** eine Datei ab, zeigt die Fehlerliste die Nummer
der Automation und die betroffene Angabe, zum Beispiel **Automation 12 → Aktion 2
→ Wartezeit vor dem Schritt: Der Wert muss mindestens 0 sein**. Bei Entwürfen aus
dem Klick-Editor steht zusätzlich der Name der Automation dabei. Die Nummern
beziehen sich auf die Reihenfolge aller Automationen in der geprüften Datei,
einschließlich ausgeschalteter Regeln. Bei selbst geschriebener YAML helfen die
Nummer sowie bei Syntaxfehlern nach Möglichkeit Zeile und Spalte.

Es erscheinen bis zu 20 Fehler gleichzeitig. Nach der Korrektur die Änderung
zunächst in den Entwurf übernehmen und erneut prüfen. Die Fehlerliste gehört zur
letzten Prüfung und bleibt auch bei automatischen und manuellen Statusabfragen
erhalten. Erst beim Start der nächsten Entwurfsprüfung wird sie durch deren
Ergebnis ersetzt. Meldet ein anderer Vorgang einen Fehler, erscheint dieser
zusätzlich unter der Fehlerliste. Für diese ausführlichen Rückmeldungen müssen
sowohl das Modul als auch die mitgelieferte Z2M-Erweiterung aktualisiert sein.

## Ausführung aktivieren und stoppen

Die Aktivierung bezieht sich auf die **gespeicherte** Revision, nicht auf einen
unveröffentlichten Entwurf. Das Modul fragt den aktuellen Zustand und die frische
Einmalkennung ab, sendet den Aktivierungsauftrag und prüft danach den tatsächlichen
Status erneut. Erst dann werden aktive Revision und Regelanzahl angezeigt.
Eine ältere Erweiterung wird mit einer verständlichen Update-Meldung erkannt.

„Automationen in Z2M stoppen“ verwirft ausstehende Auslöser und Verzögerungen.
Ein Stopp erhält Vorrang vor einem noch laufenden Modulauftrag. Bei aktiver
MQTT-Anbindung darf er auch bei noch unbekanntem Bridge-Onlinezustand versucht
werden. Ein Timeout bestätigt keinen Stopp: Dann bleibt der Ausführungsstatus
unbekannt und muss erneut abgefragt werden. Bereits gesendete Gerätebefehle lassen
sich nicht zurückrufen.

**Symcon ist nach erfolgreicher Aktivierung für die Ausführung nicht erforderlich.**
Geräteaktionen übergibt die Erweiterung direkt an Z2Ms interne Befehlsverarbeitung.
Die Ausführung benötigt keine Rückzustellung eigener MQTT-Veröffentlichungen.
Der Ausführungsstatus bestätigt die aktive Regel, nicht die physische Wirkung
jeder Aktion. Spätere Converter-/Zigbee-Fehler sind im Z2M-Protokoll nachzusehen.
Auch das Deaktivieren der MQTT-Anbindung oder eine Unterbrechung der Symcon-
Verbindung stoppt die Regeln in Z2M nicht automatisch. Erst der ausdrücklich an
Z2M gesendete Stopp beendet die Ausführung. Nach einem Z2M-/Erweiterungsneustart gilt die gespeicherte Wiederanlauf-Einstellung.
Nach dem Speichern geänderter Regeln ist erneut ausdrücklich zu aktivieren.

## Nach einem Z2M-Neustart automatisch weiterarbeiten

Unter **Betrieb der Automationen** ist **Aktivierte Automationen nach einem
Z2M-Neustart automatisch fortsetzen** standardmäßig eingeschaltet. Einstellungen
übernehmen und anschließend **Gespeicherte Automationen in Z2M aktivieren** wählen:
Erst diese Aktivierung überträgt die Option und speichert den Betriebszustand in Z2M.
Eine reine Änderung im Symcon-Formular ändert den bereits gespeicherten Z2M-Zustand nicht.

Danach aktiviert Z2M denselben geprüften Regelstand nach einem Neustart selbstständig,
auch wenn Symcon nicht läuft. **Stoppen** wird ebenfalls dauerhaft gespeichert;
bewusst gestoppte Automationen bleiben aus. Das bloße Speichern geänderter Regeln
berechtigt nicht zum Wiederanlauf. Ohne eingeschaltete Option ist nach einem
Z2M-Neustart eine manuelle Aktivierung erforderlich. Die Statuszeile zeigt das
von Z2M bestätigte Verhalten und einen gegebenenfalls fehlgeschlagenen Wiederanlauf.

Verpasste Schaltzeiten werden nicht nachgeholt, unterbrochene Aktionsfolgen und
Wartezeiten werden verworfen. Gerätebedingungen warten auf neue Sensorwerte.
Fehlende Geräte, beschädigte Dateien oder ein veränderter Regelstand verhindern
den Wiederanlauf. Bei Speicherfehlern wird Aktivierung oder Stopp nicht als
dauerhaft bestätigt; Fehler beheben und den Auftrag erneut ausführen.

Nach erstmaliger Installation dieser Erweiterung einmal ausdrücklich aktivieren,
um die Wiederanlauf-Freigabe anzulegen. Ein Update allein aktiviert keine Regeln.
Auch ein Neuladen der Erweiterung verwendet anschließend den gespeicherten
Betriebszustand. Beide Partner einer systemübergreifenden Automation benötigen
jeweils ihre eigene Aktivierung und Wiederanlauf-Freigabe.

## Übersicht und Bearbeitung

Geräteausgelöste und zeitgesteuerte Automationen besitzen jeweils eine eigene
Übersicht und die Auswahl „Automation bearbeiten“. Die Auswahl lädt die Regel
in die Bearbeitung direkt im jeweiligen Bereich. Dort erscheinen nur die
passenden Auslöserfelder; bestehende Bedingungen werden aufgeklappt. Jeder
Bereich besitzt eigene Eingabefelder, eine Aktionsliste und Übernehmen-/Löschen-
Buttons. Die Auslöserart ergibt sich aus dem Bereich und wird nicht zusätzlich
ausgewählt. Es wird jeweils eine Regel bearbeitet; vor dem Wechsel Änderungen
in den lokalen Entwurf übernehmen. Beide Bearbeitungsüberschriften bleiben
sichtbar; das Öffnen aktiviert den jeweiligen Bereich. Ein zusätzlicher Neu-Button
ist nicht erforderlich.
Regeln mit gemischten oder anderen nicht darstellbaren Auslösern bleiben mit ihrer sicheren
Metadatenbearbeitung unter „Weitere Automationen“ erreichbar. Unterstützte
Bearbeitung und der Schutz erweiterter Regeln gelten weiter.

Die Spalte „Im Entwurf eingeschaltet“ zeigt den gewünschten Regelschalter,
nicht den aktuellen Z2M-Betrieb. Der Schalter in einer neuen, noch nicht
übernommenen Bearbeitung ändert keine vorhandene Listenzeile.

„Prüfen und an Z2M übertragen“ enthält Prüfung, Speicherung und Aktivierung.
Aktivieren und Stoppen beziehen sich auf die gesamte gespeicherte Konfiguration.
Ausführungsstatus und Stopp stehen oben. MQTT-Einstellungen, Einrichtung/Diagnose
und der erweiterte YAML-Editor sind normalerweise eingeklappt. Die Bearbeitung
ändert zuerst den lokalen Entwurf; Z2M ändert sich erst beim Speichern.

## Viele Automationen finden und kopieren

Die Übersichten zeigen jeweils zehn Regeln auf einmal. Name, Auslöser und
Ziel teilen sich die verfügbare Breite. Weitere Einträge erreichen Sie über
die Bildlaufleiste der jeweiligen Liste.

Mit **Nach Automations- oder Gerätenamen suchen** grenzen Sie die Übersicht
und die Auswahl zum Bearbeiten ein. Die Suche berücksichtigt auch Geräte in
Auslösern, Bedingungen und Aktionsfolgen. Groß- und Kleinschreibung spielen
keine Rolle. Mehrere Suchwörter müssen alle gefunden werden. Die Anzeige nennt
die Zahl der Treffer. Unter **Regel aktiviert im Entwurf** wählen Sie zusätzlich
alle, eingeschaltete oder ausgeschaltete Regeln. Dieser Filter bezieht sich
auf den Regelschalter im Entwurf und verändert den Z2M-Betrieb nicht.

Jeder Bereich hat eigene Filter. Leeren Sie die Suche und wählen Sie **Alle
Regeln**, um wieder alles zu sehen. Eine bereits geöffnete Automation bleibt
mit einem Hinweis in der Bearbeitungsauswahl, auch wenn sie nicht zum Filter
passt. Ihre Eingaben bleiben erhalten. Auch eine leere Trefferliste löscht
keine Automation. Die Suchtexte werden nicht im Debug ausgegeben.

Wählen Sie eine bestehende Automation und klicken Sie auf **Ausgewählte
Automation kopieren**. Offene Eingaben müssen Sie zuerst in den Entwurf
übernehmen. Die Kopie erhält eine eigene Kennung, einen ergänzten Namen und
wird direkt zum Bearbeiten geöffnet. Der Filter dieses Bereichs wird dafür
zurückgesetzt. Der ursprüngliche Regelinhalt bleibt erhalten.

Kopien sind zunächst **ausgeschaltet**. Auslöser, Bedingungen, Szenen,
Zeitversätze, Wartezeiten und Ziele im zweiten Z2M-System bleiben erhalten.
Verweise auf wiederverwendbare Aktionsfolgen bleiben mit denselben Folgen
verbunden; deren Definitionen werden nicht mitkopiert. Die Kopie liegt zuerst
nur im Symcon-Entwurf. Für den späteren Betrieb müssen Sie sie nach Wunsch
bearbeiten, den Regelschalter setzen und den Entwurf wie bisher prüfen und
in Z2M speichern. Insgesamt sind weiterhin höchstens 100 Regeln erlaubt.
Für diese Erweiterung genügt ein Modulupdate.

## Vorhandene Automationen bearbeiten oder löschen

1. Geräte und gespeicherte Automationen laden. Unter „Automation bearbeiten“
   die gewünschte Regel auswählen. Die Nummer neben dem Namen unterscheidet
   gleichnamige Regeln; intern bleibt jede Regel über ihre feste ID zugeordnet.
2. Bei unterstützten Regeln werden Name, Aktivierung, Auslöser, Bedingungen und
   sämtliche Aktionsschritte vorbelegt. Änderungen mit „Änderungen in den Entwurf übernehmen“
   anwenden. „Regel aktiviert“ legt fest, ob die Regel bei der nächsten Aktivierung
   der gespeicherten Konfiguration mitläuft; es schaltet nicht sofort ein Gerät.
3. Alternativ „Ausgewählte Automation aus dem Entwurf löschen“ betätigen und
   bestätigen. Nur die ausgewählte Regel wird entfernt. Auch die letzte Regel
   darf gelöscht werden; es entsteht eine gültige leere Konfiguration.
4. Den geänderten Entwurf in Z2M prüfen und anschließend speichern. Erst diese
   Übertragung verändert Z2M. Eine geänderte Konfiguration ist danach gestoppt
   und muss bei Bedarf erneut ausdrücklich aktiviert werden.

Weitere lokale Änderungen können vor dem Übertragen gesammelt werden. Nach
einer erfolgreichen Übertragung den Editor neu laden, bevor weitere Regeln
bearbeitet werden. Einfache Regeln behalten beim Bearbeiten ihre ID und Position;
andere Regeln bleiben unverändert. Bei erweiterten Regeln oder inzwischen
fehlenden Auswahlwerten werden Geräte-/Aktionsfelder gesperrt. Name und
Aktivierungszustand bleiben änderbar; vorhandene Bedingungen und Schritte bleiben
vollständig erhalten. Löschen ist auch für diese Regeln möglich.

Zum erneuten Laden wird das Verwerfen des lokalen Entwurfs
ausdrücklich bestätigt. Manuelle YAML-Änderungen oder eine inzwischen geänderte
Speicherrevision werden nicht unbemerkt überschrieben. Eine veraltete Auswahl,
auch aus einem zweiten geöffneten Formular, wird abgewiesen. In diesem Fall
die Regel erneut auswählen bzw. den Editor nach Speicherung neu laden.

## Mehrkanaltaster und Schaltkanäle

Einzelne Kanäle erscheinen bei Auslösern und Aktionen als „Zustand (Kanal L1)“,
„Zustand (Kanal L2)“ usw., jeweils mit Ein/Aus. Den passenden Kanal ausdrücklich
wählen. Der allgemeine Zustand bleibt davon getrennt; er ersetzt keinen Kanal.
Beim erneuten Auswählen einer Automation werden Kanal, Zustand und Mindestdauer
bzw. Wartezeit wieder geladen. Nach diesem Update die mitgelieferte Erweiterung
aktualisieren und anschließend Geräte und gespeicherte Automationen neu laden.

## Mehrere Geräteauslöser (ODER)

Im Gerätebereich enthält die Auslöserliste ein bis acht Einträge. Jeden Eintrag
über Hinzufügen anlegen und Gerät mit Ereignis/Zustand wählen. Bewegungsmelder,
Taster und andere angebotene Geräteereignisse können kombiniert werden. Sobald
einer der Einträge zutrifft, kann dieselbe Aktionsfolge starten. Die zusätzlichen
Bedingungen gelten für jeden Auslöser gemeinsam. Eine UND-Verknüpfung mehrerer
Ereignisse ist hier nicht vorgesehen.

„Mindestdauer des Zustands“ wird je Eintrag eingestellt: 0 reagiert sofort,
1 bis 86400 fordert einen entsprechend lange anliegenden Zustand. Für einen
kurzen Tastendruck normalerweise 0 verwenden. Einträge sind bearbeitbar und
löschbar; ihre Reihenfolge ist für ODER ohne Bedeutung. Vorhandene Einzelregeln
erscheinen als eine Zeile, unterstützte Mehrfachregeln mit allen Einträgen.

Danach in den Entwurf übernehmen, gemeinsam prüfen und speichern. Die Engine
verhindert parallele Läufe derselben Regel und besitzt einen Schutzabstand von
einer Sekunde zwischen Regelstarts. Bedingungen und Wartezeiten verändern diese
Regeln nicht. Gemischte Zeit-/Geräteauslöser und nicht darstellbare Vergleiche
bleiben in der erweiterten YAML bzw. der begrenzten Metadatenbearbeitung erhalten.

## Messwertauslöser und mehrere Bedingungen

Beim Hinzufügen oder Bearbeiten eines Geräteauslösers stehen Gerät,
Eigenschaft/Kanal, Vergleich, Wert und Mindestdauer getrennt bereit.
Für Messwerte sind „Ist gleich“, „Ist ungleich“, „Ist größer als“,
„Ist mindestens“, „Ist kleiner als“ und „Ist höchstens“ möglich. Für Zustände
und Ereignisse stehen Gleichheit und Ungleichheit zur Verfügung.
Beispiel: Helligkeit ist seit 30 Sekunden kleiner als 50 Lux.
Mehrere Auslöser sind weiterhin ODER-verknüpft.

Unter „Nur wenn …“ können in beiden Automationsbereichen mehrere Geräte- und
Messwertbedingungen hinzugefügt, bearbeitet und gelöscht werden. Alle Zeilen
und das optionale Zeitfenster müssen gemeinsam erfüllt sein (UND). Insgesamt
sind höchstens 16 Bedingungen einschließlich Wochentagen und Zeitfenster möglich. Eine leere
Gerätebedingungsliste fügt keine Einschränkung hinzu. Das Zeitfenster kann
unabhängig davon eingeschaltet bleiben.

Vorhandene Regeln laden ihre Vergleiche und sämtliche unterstützten Bedingungen
wieder in die Zeilen. Änderungen werden erst in den lokalen Entwurf übernommen,
anschließend geprüft und nach Bestätigung gespeichert. Nicht darstellbare
Regelstrukturen bleiben gegen ein versehentliches Ersetzen geschützt.
Unbekannte Gerätewerte erfüllen eine Bedingung nicht; Z2M verwendet die seit der
Aktivierung beobachteten Werte. Gerätewerte müssen weiterhin lesbar sein;
Aktionsziele benötigen unverändert eine schaltbare Symcon-Variable und Z2M-Schreibrecht.
Ein numerischer Vergleichswert darf zwischen Meldeschritten oder außerhalb des
Sensorbereichs liegen. Das erweitert nicht die erlaubten Werte einer Aktion.

## Zeitsteuerungen und Bedingungen per Klick

Zuerst die aktualisierte mitgelieferte Erweiterung installieren und die Geräte
erneut laden. Ihr Katalog liefert auch lesbare Messgrößen für Zahlenbedingungen.

- Für Geräteereignisse den Gerätebereich öffnen. Für feste Uhrzeiten im Bereich
  „Zeitgesteuerte Automationen“ eine bis acht Startzeiten als HH:MM oder HH:MM:SS
  und die gemeinsame Zeitzone auswählen.
  Ohne Bedingungen läuft die Regel täglich. Verpasste Zeitpunkte werden nicht
  nachgeholt; bei der Zeitumstellung wird ein Zeitpunkt höchstens einmal am Tag
  ausgelöst. Geräteereignisse können eine Mindestdauer in Sekunden erhalten.
- „Nur in einem Zeitfenster“ erlaubt eine zusätzliche Begrenzung nach Uhrzeit,
  Zeitzone und Wochentagen. Der Beginn zählt mit, das Ende nicht. Ein Fenster
  Montag 22:00 bis 06:00 gilt bis Dienstagmorgen; maßgeblich ist der Starttag.
  Mindestens ein Tag muss gewählt sein. Gleiche Start-/Endzeiten sind ungültig.
- In der Liste „Gerätebedingungen“ lassen sich mehrere Zustände oder Messwerte
  hinzufügen. Je Zeile Gerät, Eigenschaft bzw. Kanal, Vergleich und Wert auswählen.
  Zustände unterstützen gleich/ungleich, Messwerte zusätzlich größer, kleiner,
  mindestens und höchstens. Die Einheit steht neben der Messgröße. Beispiel:
  täglich 07:15 Uhr, nur wenn die Temperatur kleiner als 18,5 °C ist.
- Bei Bedarf eine Wartezeit vor der Aktion angeben. Alle eingeschalteten
  Bedingungen müssen zum Start erfüllt sein. Ohne „Nur beim Start“ werden sie
  zusätzlich vor jeder Aktion erneut geprüft.

Bei einer Gerätebedingung können Sie **Nur beim Start** einschalten. Das ist
zum Beispiel für eine Helligkeitsgrenze sinnvoll: Das Licht soll bei Bewegung
nur im Dunkeln angehen, nach der Wartezeit aber auch dann ausgehen, wenn es
inzwischen heller geworden ist. Weitere Bewegung darf die laufende Wartezeit
ebenfalls verlängern. Ein vollständig neuer Ablauf und **Aktionsfolge von vorn
beginnen** prüfen die Helligkeit wieder. Ohne Häkchen gilt die Bedingung weiterhin
auch für spätere Aktionen und das Verlängern. Zeitfenster und Wochentage werden
immer erneut geprüft. Für die neue Auswahl Modul und Z2M-Erweiterung aktualisieren.

Zeitfenster, unabhängige Wochentage und Gerätebedingungen können gemeinsam verwendet
werden. Insgesamt sind höchstens 16 Bedingungen erlaubt. Eine
Gerätebedingung verwendet nur Werte, die Z2M seit der Aktivierung beobachtet hat.
Ein unbekannter Wert erfüllt auch „ungleich“ nicht. Es werden keine zusätzlichen
Geräteabfragen erzeugt.

Neue Regeln zum Entwurf hinzufügen, bestehende Regeln auswählen und Änderungen
in den Entwurf übernehmen. Zeiten, Wochentage, Bedingungen und Verzögerungen werden
beim Bearbeiten wieder vorbelegt. Danach wie gewohnt prüfen, speichern und bei
Bedarf aktivieren. Erstellen oder Bearbeiten führt noch keine Aktion aus.

## Mehrere Uhrzeiten und erlaubte Wochentage

Im Zeitbereich wählen Sie **Feste Uhrzeit** und tragen in **Startzeiten** eine
bis acht Uhrzeiten ein. Mit **Hinzufügen** kommt eine weitere Uhrzeit hinzu;
vorhandene Einträge können Sie bearbeiten oder löschen. Zum Beispiel startet
eine Regel um 07:00 und um 18:30 jeweils dieselbe Aktionsfolge. Die Uhrzeiten
verwenden die gemeinsam ausgewählte Zeitzone. Leere, ungültige und doppelte
Einträge lassen sich nicht in den Entwurf übernehmen.
Läuft die Aktionsfolge noch, gilt für eine weitere Uhrzeit die Auswahl zum
erneuten Auslösen. Ein zweiter gleichzeitiger Ablauf derselben Regel entsteht
dabei nicht.

Unter **Nur wenn …** können Sie **Nur an ausgewählten Wochentagen** einschalten.
Wählen Sie mindestens einen Tag und die Zeitzone dafür. Das funktioniert auch
ohne Zeitfenster und ebenso bei Geräte- und Sonnenauf-/untergangsregeln.
Ohne diese Einschränkung sind alle Wochentage erlaubt. Beispiel: Montag bis
Freitag um 07:00 und 18:30, ohne zusätzlich ein Zeitfenster einzurichten.

Ein zusätzliches Zeitfenster schränkt die Ausführung weiter ein. Seine Felder
**Zeitfenster beginnt am Montag** usw. bestimmen weiterhin die Starttage des
Fensters. Montag 22:00 bis 06:00 gilt bis Dienstagmorgen. Die unabhängige
Wochentagsauswahl prüft dagegen immer den aktuellen Kalendertag. Sollen auch
die Stunden am Dienstagmorgen erlaubt sein, muss dort zusätzlich Dienstag
ausgewählt sein. Beide Einschränkungen und alle Gerätebedingungen müssen passen.

Die Angaben werden beim Auswählen und Laden vorhandener Regeln vorbelegt.
Übernehmen Sie Änderungen zuerst in den lokalen Entwurf, prüfen und speichern
Sie ihn anschließend in Z2M. Verpasste Uhrzeiten werden nicht nachgeholt. Bei
der Zeitumstellung läuft jede eingetragene Uhrzeit in einer doppelt vorkommenden
Stunde nur einmal. Bedingungen ohne „Nur beim Start“ werden vor verzögerten Aktionen erneut geprüft.

Für diese Funktion müssen **Modul und mitgelieferte Z2M-Erweiterung** aktualisiert
werden. Ein älterer Empfänger lehnt die neue Wochentagsbedingung ab; es erfolgt
keine automatische Umdeutung. Regeln mit unterschiedlichen Zeitzonen je
Einzelauslöser bleiben erhalten und lassen sich im Klick-Editor nur umbenennen
oder ein-/ausschalten, bis ihre Zeiten in YAML angepasst werden.

## Automationen bei Sonnenauf- oder Sonnenuntergang

Im Bereich „Zeitgesteuerte Automationen“ unter „Startzeitpunkt“ Sonnenaufgang
oder Sonnenuntergang auswählen. Breitengrad und Längengrad des gewünschten
Standorts eintragen; Dezimalpunkt und Dezimalkomma werden akzeptiert. Ein leerer
Standort wird nicht stillschweigend durch 0/0 ersetzt. Die Zeitzone bestimmt den
örtlichen Kalendertag des Sonnenereignisses.

Der Zeitversatz wird in ganzen Minuten eingegeben: −30 startet eine halbe Stunde
vor dem Ereignis, +15 eine Viertelstunde danach. Möglich sind −720 bis +720
Minuten. Der Start darf dadurch auf den vorherigen oder folgenden Tag fallen.
Bedingungen gelten zum tatsächlichen Start; ohne „Nur beim Start“ auch vor den weiteren Aktionsschritten.

Die Regel wie gewohnt in den Entwurf übernehmen, prüfen, speichern und aktivieren.
Z2M berechnet die Zeiten täglich selbst, auch wenn Symcon ausgeschaltet ist.
Die Näherungszeiten werden auf Minuten gerundet; Gelände, Höhe und aktuelle
Wetterbedingungen fließen nicht ein. An Tagen ohne das gewählte Sonnenereignis
(zum Beispiel Polartag) startet die Regel nicht. Verpasste Zeiten werden nach
einem Neustart nicht nachgeholt.

Beim Bearbeiten bleiben Ereignis, Standort, Versatz, Zeitzone und Aktionsfolge
vorbelegt. Zurückwechseln auf „Feste Uhrzeit“ ersetzt den Sonnenauslöser vollständig.
Modul und mitgelieferte Z2M-Erweiterung müssen für diese Funktion aktualisiert
werden. Standortdaten werden mit der YAML im eigenen Z2M gespeichert, aber nicht
im Debug ausgegeben.

## Mehrere Aktionen per Klick

Die Liste „Aktionsfolge“ enthält bis zu 16 Schritte. Über „Hinzufügen“ Gerät und
Aktion auswählen; vorhandene Schritte über die Bearbeitung ändern oder löschen.
Die Reihenfolge durch Ziehen der Zeilen ändern. Sie wird von oben nach unten
ausgeführt. Geräte dürfen in mehreren Schritten vorkommen.

„Wartezeit vor diesem Schritt“ ist eine ganze Sekundenzahl von 0 bis 86400.
0 bedeutet sofort; bei späteren Schritten beginnt die Wartezeit nach der Übergabe
der vorherigen Aktion an Z2M. Beispiel: Licht ein mit 0 Sekunden, Licht aus mit
30 Sekunden. Eine Übergabe bestätigt noch keine tatsächliche Gerätewirkung.
Auch vor dem ersten Schritt darf eine Wartezeit stehen.

Danach „Automation zum Entwurf hinzufügen“ bzw. „Änderungen in den Entwurf
übernehmen“ betätigen und den Entwurf prüfen und speichern. Mindestens ein
gültiger Schritt ist erforderlich. Änderungen an der Liste alleine ändern weder
den Regelentwurf noch Z2M. Bei Auswahl einer vorhandenen Automation werden alle
unterstützten Schritte einschließlich ihrer Wartezeiten geladen. Einzelaktionen
erscheinen als eine Zeile. Die Wartezeit gehört zum Schritt und wandert beim
Umsortieren mit.

Alle Bedingungen ohne „Nur beim Start“ werden vor jedem Schritt erneut geprüft. Ist eine solche Bedingung
nicht mehr erfüllt, endet die verbleibende Folge. „Automationen in Z2M stoppen“
verwirft ausstehende Wartezeiten; bereits übergebene Aktionen werden nicht rückgängig
gemacht. Die Ausführung der gespeicherten, aktivierten Folge benötigt kein Symcon.

## Mehrere Eigenschaften gemeinsam setzen und Farben auswählen

Ein Aktionsschritt kann mehrere Eigenschaften **desselben Geräts** gemeinsam
setzen, zum Beispiel Einschalten, Helligkeit und Farbe. Wählen Sie im
Schrittdialog **Mehrere Eigenschaften gemeinsam setzen** und das Gerät.
Wählen Sie dann jeweils eine Eigenschaft bzw. einen Kanal und den gewünschten
Wert. Mit **Eigenschaft hinzufügen oder ersetzen** nehmen Sie diesen Wert in
die darunter angezeigte Zusammenstellung auf. Wiederholen Sie das für weitere
Eigenschaften. Die Zusammenstellung zeigt, was tatsächlich übernommen wird;
das bloße Ändern eines Wertefelds verändert sie noch nicht.

Zum Ändern wählen Sie eine bereits enthaltene Eigenschaft: Ihr bisheriger Wert
wird vorbelegt. Ändern Sie ihn und klicken Sie erneut auf **Eigenschaft hinzufügen
oder ersetzen**. Mit **Ausgewählte Eigenschaft entfernen** entfernen Sie genau
diese Eigenschaft. Bestätigen Sie anschließend den Schrittdialog und übernehmen
Sie die Automation in den Entwurf. Danach wie gewohnt prüfen und in Z2M speichern.
Die Wartezeit gilt vor dem gesamten Schritt. Der gemeinsame Befehl wird einmal
an Z2M übergeben; daraus folgt keine Garantie, dass das Gerät alle Eigenschaften
physisch gleichzeitig ändert.

Bei geeigneten Farbleuchten erscheint für **Farbe** die native Farbauswahl.
Sie können auch einen eigenen Schritt nur für die Farbe verwenden. Es werden
nur Eigenschaften angeboten, die Z2M zum Schreiben bereitstellt und für die
in Symcon eine schaltbare Variable vorhanden ist. Reine Anzeigevariablen
bleiben für Aktionen ausgeschlossen. Fehlt eine Farbeigenschaft, laden Sie
nach dem Erweiterungsupdate die Gerätedaten erneut. Das Gerät und sein
Symcon-Modul müssen diese Farbeigenschaft unterstützen.

Ein gemeinsamer Schritt enthält höchstens acht Eigenschaften. Farbe und
Farbtemperatur desselben Kanals gehören in getrennte Schritte. **Umschalten**
und Szenenaufrufe bleiben eigene Aktionen. Die Funktion steht auch in
wiederverwendbaren Aktionsfolgen und für Geräte des ausgewählten zweiten
Z2M-Systems zur Verfügung. Gruppen erhalten keine zusätzlich angenommenen
Farbfähigkeiten; bei ihnen werden weiterhin nur die bekannten Eigenschaften
angeboten. Komplexere, im Editor nicht vollständig darstellbare YAML-Befehle
bleiben erhalten und können dort nur umbenannt oder ein-/ausgeschaltet werden.

Für die Farbauswahl sind **Modul- und Erweiterungsupdate** sowie das erneute
Laden der Gerätedaten nötig. Native Bearbeitung und Speicherung sind in Symcon
9.1 geprüft. Ein gemeinsamer Befehl für Einschalten, Helligkeit und Farbe ist
zusätzlich an einer Farbleuchte real bestätigt. Die Farbausführung am jeweiligen
Gerät hängt von dessen Unterstützung durch Z2M und den Geräte-Converter ab.

## Vorhandene Szenen aufrufen

Mit **Geräte und gespeicherte Automationen laden** werden auch die in Z2M
bekannten Szenen geladen. Fügen Sie in der Aktionsfolge einen Schritt hinzu,
wählen Sie das Gerät oder die Gruppe und anschließend **Szene aufrufen**.
Bei Geräten wird der zugehörige Kanal angezeigt. Wählen Sie die Szene anhand
ihres Namens aus und stellen Sie bei Bedarf eine Wartezeit vor diesem Schritt ein.
Die Auswahl wird beim Bearbeiten einer vorhandenen Automation wieder vorbelegt.

Angeboten werden Szenen, deren zugehöriger Schaltkanal in Symcon schaltbar ist.
Für das zweite Z2M-System wählen Sie ein Ziel mit dessen Basistopic vor dem Namen;
zuvor müssen die Gerätedaten auch im Partnermodul geladen sein. Die Freigaben und
die Aktivierung beider Systeme gelten genauso wie bei anderen entfernten Aktionen.

Diese Funktion ruft vorhandene Szenen auf. Sie legt keine Szene an und verändert
keine gespeicherten Szeneneinstellungen. Fehlt eine Szene, laden Sie die Gerätedaten
neu und wählen Sie eine vorhandene Szene. Lokale Szenen werden auch bei der
Entwurfsprüfung kontrolliert; Szenen des zweiten Systems prüft dessen Erweiterung
unmittelbar vor der Ausführung. Eine erfolgreiche Befehlsübergabe bestätigt noch
nicht die tatsächliche Gerätewirkung.

## Eine Aktionsfolge mehrfach verwenden

Im Bereich **Wiederverwendbare Aktionsfolgen** wählen Sie **Neue Aktionsfolge
erstellen**, vergeben einen Namen und fügen die gewünschten Schritte hinzu.
Gerät, Eigenschaft beziehungsweise Kanal und Wert wählen Sie wie bei einer
Automation. Vorhandene Szenen und Ziele des zweiten Z2M-Systems sind ebenfalls
möglich. Ordnen Sie die Schritte und stellen Sie die Wartezeit vor jedem Schritt
ein. Mit **Aktionsfolge in den Entwurf übernehmen** speichern Sie die Bearbeitung
zunächst in Symcon.

Fügen Sie anschließend in einer geräteausgelösten oder zeitgesteuerten Automation
einen Aktionsschritt hinzu. Wählen Sie als Aktionsart **Wiederverwendbare
Aktionsfolge** und anschließend die gewünschte Folge. So können beispielsweise
ein Taster und eine Zeitregel denselben Ablauf verwenden. Auch das Bearbeiten
einer vorhandenen Automation füllt diese Auswahl und die Wartezeit wieder aus.

Die Wartezeit am Aufruf gilt zusätzlich zur Wartezeit vor dem ersten Schritt
der Folge. Beispiel: fünf Sekunden am Aufruf und zwei Sekunden am ersten Schritt
ergeben sieben Sekunden bis zur ersten Aktion. Pro Automation sind insgesamt
höchstens 16 tatsächlich ausgeführte Schritte erlaubt, einschließlich aller
aufgerufenen Folgen. Eine Folge kann selbst keine weitere Folge aufrufen.

Wählen Sie eine vorhandene Folge zum Bearbeiten aus, werden Name und Schritte
vorbelegt. Änderungen gelten für alle Automationen, die sie verwenden. Die
Übersicht zeigt die Anzahl dieser Automationen. Solange eine Folge verwendet
wird, ist ihr Löschen gesperrt. Entfernen Sie zuvor die entsprechenden Aufrufe.

Danach den gemeinsamen Entwurf **prüfen**, **in Z2M speichern** und **aktivieren**.
Die Bedingungen der jeweiligen Automation gelten auch innerhalb der Folge.
**Diesen Ablauf stoppen** beendet deren laufenden Aufruf einschließlich der
verbleibenden Wartezeiten und Schritte. Andere Automationen bleiben unabhängig.
Z2M führt die gespeicherten Folgen anschließend ohne laufendes Symcon aus.
Für diese Funktion Modul und Erweiterung aktualisieren.

## Ein Gerät auf dem zweiten Z2M-System schalten

Jedes Z2M-System erhält sein eigenes Automationsmodul mit eigenem Basistopic.
Die Systeme dürfen einen Broker und denselben Symcon-MQTT-Client verwenden.
Bei einem gemeinsamen Broker wird keine Broker-Bridge benötigt; beide Z2M-Benutzer
brauchen Zugriff auf den gemeinsamen Bereich `z2ma/mesh/#`.
Unter **Aktionen zwischen zwei Z2M-Systemen** wählen Sie in beiden Modulen das
jeweils andere Modul. Die Basistopics müssen verschieden sein. Anschließend
die Gerätedaten in beiden Modulen laden und das Formular erneut öffnen.

Bei den Aktionen erscheinen die schaltbaren Geräte und Gruppen des zweiten
Systems mit dessen Basistopic vor dem Namen. Gerät, Kanal und Wert werden wie
bei lokalen Aktionen ausgewählt. Vorhandene Regeln lassen sich damit bearbeiten
und passende Symcon-Ereignisse als deaktivierte Entwürfe importieren. Ein
Geräteauslöser und seine Bedingungen gehören weiterhin zum eigenen System.

Beide Entwürfe prüfen, speichern und aktivieren. Die empfangende Seite muss
auch dann aktiviert werden, wenn sie keine eigene eingeschaltete Regel besitzt.
Die Statusvariable **Aktionen vom zweiten Z2M-System freigegeben** zeigt die
bestätigte Empfangsbereitschaft nach einer Statusabfrage. **Stoppen** auf der
empfangenden Seite sperrt auch Aktionen des anderen Systems. Bereits gesendete
Gerätebefehle lassen sich dadurch nicht zurückholen.

Bei getrennten Brokern ist eine eingerichtete Broker-Verbindung für `z2ma/mesh/#`
Voraussetzung.
Der Benutzer, mit dem sich **Z2M selbst** anmeldet, benötigt auf seinem Broker
zusätzlich Lese- und Schreibrecht für diesen Bereich. Der Bridge-Benutzer allein
genügt nicht. Das Modul ändert keine Brokerberechtigungen und benötigt keine
Brokerpasswörter. Details für die Einrichtung stehen in [MQTT](../docs/MQTT.md).

Die Regel läuft nach erfolgreicher Einrichtung ohne Symcon. Bei fehlender
Gegenstelle wird die Aktionsfolge abgebrochen und nicht automatisch nachgeholt.
Eine Bestätigung bedeutet, dass das Ziel-Z2M den Gerätebefehl übernommen hat;
die tatsächliche Gerätewirkung ist damit noch nicht bestätigt.

## Vorhandene Symcon-Ereignisse importieren

Die Prüfung unterscheidet zwischen dem Gerät und der einzelnen Variable:
Ein Gerät kann richtig verbunden sein, obwohl eine seiner Variablen noch nicht
importiert werden kann. In diesem Fall nennt die Meldung die betroffene Variable.
Die **auslösende Variable** wird überwacht; die **zu schaltende Variable** ist das
Ziel der Aktion. Das Ereignis kann im Objektbaum beim Ziel liegen und trotzdem
eine Variable eines anderen Geräts überwachen.

Ein leerer Ereignisname verhindert den Import nicht. Das Modul vergibt dann
„Importierte Automation“ mit der Ereignisnummer als Namen. Dieser lässt sich
anschließend im Entwurf ändern; das ursprüngliche Ereignis bleibt unverändert.

Die Übersicht zeigt Ereignisname, **auslösendes Gerät** und dessen
**Symcon-Instanz-ID**. Die Liste bietet Platz für 15 sichtbare Einträge;
weitere Einträge sind durch Scrollen erreichbar. Auch in der Ereignisauswahl stehen diese Angaben, damit
sich gleichnamige Ereignisse unterscheiden lassen.

Die Importprüfung nennt das Ergebnis ausdrücklich: **Importierbar** oder
**Nicht importierbar**, jeweils mit einer Erklärung.

Fehlt die Gerätezuordnung aus Z2M für die gewählte Verbindung, steht in der
Übersicht **Noch nicht prüfbar: Gerätedaten fehlen**. Die Meldung darunter verweist direkt
auf **Gerätedaten für den Import laden**. Dieser Button lädt Geräte, Gruppen und
gespeicherte Regeln über die Erweiterung. Nach der Antwort wird die Übersicht
aktualisiert und das gewählte Ereignis erneut geprüft. Dafür ist kein erneutes
Öffnen des Formulars notwendig. Ein unterstütztes Ereignis lässt sich danach
übernehmen; andernfalls erscheint der konkrete Ablehnungsgrund.

Beim Nachladen über **Gerätedaten für den Import laden** bleiben vorhandene
Entwürfe in Symcon erhalten. Erst wenn es noch keinen Entwurf gibt, werden die
in Z2M gespeicherten Regeln als Ausgangspunkt geladen. Der separate Button
**Geräte und gespeicherte Automationen laden** ersetzt weiterhin den Entwurf
nach Bestätigung. Bei zwischenzeitlichen Änderungen in Z2M oder unübernommenen
Änderungen im YAML-Editor wird das Nachladen abgebrochen und der Entwurf erhalten.
Während der Antwort aus Z2M bleiben Laden und Übernahme gesperrt. Ladefehler
werden verständlich angezeigt und bleiben beim erneuten Öffnen sichtbar.


1. Modul und mitgelieferte Z2M-Erweiterung auf denselben aktuellen Stand bringen.
2. **Geräte und gespeicherte Automationen laden**. Das ersetzt den lokalen Entwurf;
   vorherige Änderungen deshalb zuerst übernehmen bzw. speichern.
3. **Symcon-Ereignisse importieren** öffnen und **Vorhandene Ereignisse prüfen** wählen.
4. Ein Ereignis auswählen und die Vorschau mit Auslöser, Ziel und Aktion prüfen.
5. **Ereignis in den Entwurf übernehmen** wählen. Die deaktivierte Regel öffnet
   sich vorbefüllt im Bereich der geräteausgelösten Automationen und lässt sich
   dort ohne YAML bearbeiten. Anschließend gemeinsam prüfen und speichern.

Der Import unterstützt ausgelöste Ereignisse **bei bestimmtem Wert** mit oder
ohne **mehrmalige Auslösung bei wiederholt erfüllter Bedingung**, ohne zusätzliche
Bedingungen oder Ausführungsbegrenzung. Unterstützt sind native feste boolesche
bzw. unmittelbar zuordenbare Text-/Auswahlwerte sowie **Wert umschalten** für
Ein-/Aus-Zustände. Zahlenwerte mit möglicher Symcon-Skalierung, Prozent-/Farbaktionen,
PHP-Code, Ablaufpläne und weitere Aktionsarten werden mit Begründung abgewiesen.
Es wird keine teilweise vereinfachte Regel erzeugt.

Aktionen direkt unter einer Variablen verwenden diese als Ziel. Auch die native
Instanzaktion **Statusvariable schalten** wird unterstützt: Die Statusvariable
wird über ihren Ident ausgewählt und ihre enthaltene feste Wert-/Umschaltaktion
übernommen. Das Ereignis muss kein ausdrücklich gespeichertes TARGET enthalten.

Ist die wiederholte Ausführung in Symcon ausgeschaltet, erhält der Auslöser im
Klick-Editor die Einstellung **Nur bei Wertänderung**. Eine gleiche passende
Meldung startet dann keinen zweiten Lauf. Die Einstellung lässt sich im
Auslöserdialog ändern. Beim Aktivieren verwendet Z2M seinen vorhandenen
Quellzustand als Ausgangspunkt. Für nicht gespeicherte Ereigniswerte (etwa
Tastendrücke) zählt die erste frische Meldung als Änderung; danach wird der
letzte beobachtete Wert verglichen. Ein-/Ausführung und Sperrzeit gelten weiter.

Die Zuordnung verwendet die referenzierten Variablen-IDs, den Instanztyp, die
Gateway-Verbindung, das konfigurierte Basistopic und die Z2M-Geräte-/Gruppenkennung. Namen sind nur Anzeige.
Aktionsziele benötigen eine native schaltbare Variable ohne eigene Aktionsskripte
und eine passende schreibbare Katalogeigenschaft. Sensoren bleiben lesbare Quellen.
Gruppen sind als Aktionsziele für ihren Ein-/Aus-Zustand verfügbar.

**Wert umschalten** verwendet unmittelbar vor der Aktion den in Z2M bekannten
Zustand der Gruppe bzw. des Gerätekanals und sendet den entgegengesetzten
Ein-/Aus-Wert. Bei unbekanntem Zustand wird der Schritt mit Fehler abgebrochen.
Es wird kein möglicherweise veralteter Symcon-Wert übernommen und kein pauschaler
TOGGLE-Befehl an alle Gruppenmitglieder gesendet.

Das ursprüngliche Symcon-Ereignis wird weder geändert noch deaktiviert.
**Vor Aktivierung der importierten Regel in Z2M das Originalereignis deaktivieren**,
damit beide Systeme die Aktion nicht doppelt ausführen. Die allgemeinen
Laufzeitgrenzen gelten weiter, insbesondere eine Sekunde Sperrzeit pro Regel und
keine parallelen Läufe derselben Regel. Das ist keine Garantie identischer
Ausführungshäufigkeit wie in Symcon.

Die Vorschau wird vor der Übernahme erneut geprüft. Geänderte Ereignisse,
Zuordnungen oder ungespeicherte YAML-Änderungen werden nicht stillschweigend
übernommen. Ein bereits importiertes Ereignis erzeugt keine zweite Regel.
Die Suche berücksichtigt höchstens 4096 Ereignisse und zeigt maximal 256 passende
Quellen aus dem geladenen Katalog; eine erreichte Grenze wird angezeigt.

## Eine gespeicherte Automation einmal ausführen

Im Bereich **Gespeicherte Automation einmal ausführen** wählen Sie eine Regel
und klicken auf **Jetzt einmal ausführen**. Nach der Bestätigung führt Z2M
nur diese gespeicherte Regel einmal aus. Das funktioniert auch bei deaktivierten
Regeln und bei gestopptem Automatikbetrieb. Änderungen im Entwurf müssen zuvor
geprüft und gespeichert werden. Alternativ laden Sie den gespeicherten Stand neu.

Die Bedingungen gelten auch beim einmaligen Start. Bedingungen ohne „Nur beim
Start“ werden vor jeder Aktion erneut geprüft. Unbekannte Sensorwerte erlauben keine Ausführung. Für die erste
Prüfung verwendet Z2M seine zuletzt bekannten Sensorwerte; deshalb muss ein
Sensor nicht erst erneut auslösen. Uhrzeitbedingungen gelten zum Ausführungszeitpunkt.
Die Statusanzeige erklärt, ob der Ablauf läuft, abgeschlossen, wegen einer
Bedingung ausgelassen, fehlgeschlagen oder gestoppt ist. Während der ausgewählte
Ablauf läuft, wird sein Status automatisch abgefragt. Mit **Status des einzelnen
Ablaufs aktualisieren** können Sie ihn auch selbst abfragen.

**Diesen Ablauf stoppen** beendet ausschließlich den aktuell angezeigten Lauf.
Noch ausstehende Schritte und Wartezeiten werden verworfen. Bereits gesendete
Befehle lassen sich nicht zurücknehmen. Normale Auslöser, andere Automationen,
die Option **Regel aktiviert** und der automatische Wiederanlauf bleiben unverändert.
Ein späterer regulärer Auslöser darf die Regel erneut starten. Ein einmaliger
Testlauf selbst wird nach einem Z2M-Neustart nicht fortgesetzt.

Vorhandene Szenen und Aktionen im zweiten Z2M-System funktionieren ebenfalls.
Dessen Empfangsfreigabe muss wie bisher aktiv sein. Ein abgeschlossener Ablauf
bestätigt die Befehlsübergabe; die tatsächliche Gerätewirkung müssen Sie prüfen.
Für detaillierte Ablaufmeldungen öffnen Sie das Instanzdebug und starten die
zeitlich begrenzte Z2M-Aufzeichnung. Modul und Erweiterung gemeinsam aktualisieren.

## Wenn eine laufende Automation erneut ausgelöst wird

Unter **Wenn die laufende Automation erneut ausgelöst wird** wählen Sie:

- **Neue Auslösung ignorieren:** Die aktuelle Folge läuft unverändert weiter.
  Das ist auch das bisherige Verhalten vorhandener Regeln.
- **Aktionsfolge von vorn beginnen:** Der noch offene Rest wird beendet und
  die Folge startet wieder bei der ersten Aktion. Eine gerade laufende
  Befehlsübergabe wird vorher abgewartet. Bei einer fehlgeschlagenen oder nicht
  rechtzeitig bestätigten Übergabe wird der vorgemerkte Neustart verworfen.
- **Laufende Wartezeit zurücksetzen:** Nur die aktuelle Wartezeit beginnt
  wieder mit ihrer vollen Dauer. Bereits erledigte Schritte werden nicht
  wiederholt. Ohne laufende Wartezeit bleibt die neue Auslösung ohne Wirkung.

Beispiel: Ein Bewegungsmelder schaltet das Licht ein, wartet fünf Minuten und
schaltet es aus. Mit **Laufende Wartezeit zurücksetzen** beginnt die Wartezeit
bei jeder passenden neuen Bewegung erneut. Die Einschaltaktion wird dabei
nicht wiederholt. Meldet ein Auslöser nur Wertänderungen, muss zunächst wieder
eine solche Änderung vorliegen; nicht jede unveränderte Sensormeldung zählt.

Die Auswahl gilt auch für weitere Geräteauslöser derselben Regel sowie für
Uhrzeit- und Astro-Auslöser. Bedingungen ohne „Nur beim Start“ müssen auch beim
Verlängern erneut passen; ein Neuanfang prüft alle Bedingungen. Die
Sperrzeit von einer Sekunde bleibt bestehen. Beim Neuanfang können bereits
erledigte Aktionen nochmals ausgeführt werden, auch ein Umschaltbefehl.
Einmalige manuelle Starts ersetzen einen laufenden Ablauf nicht. Stoppen und
Neustarts von Z2M setzen unterbrochene Schritte nicht später fort.

Übernehmen Sie die Änderung wie gewohnt in den Entwurf, prüfen und speichern
Sie ihn in Z2M. Für diese Erweiterung sind Modul- und Erweiterungsupdate nötig.

## Erweiterte YAML-Ansicht

Den Bereich „Erweiterter YAML-Editor“ aufklappen, um die YAML direkt anzuzeigen
oder zu bearbeiten. Für die grafische Erstellung muss dieser Bereich nicht
geöffnet werden.

1. YAML in „YAML-Entwurf der Automationen“ einfügen. Ein
   [synthetisches Beispiel](../libs/z2m/examples/automations.yaml) und der
   [YAML-Vertrag](../docs/YAML_SCHEMA.md) beschreiben das Format.
2. Für eine neue Fassung die nächste Revision verwenden. Eine leere Installation
   beginnt mit Revision 1. Eine unveränderte Kopie der bereits gespeicherten
   Fassung kann nach erneuter Prüfung idempotent bestätigt werden.
3. „Entwurf in Z2M prüfen“ starten. Das Modul fragt zuerst den aktuellen Empfänger-
   und Speicherzustand ab und sendet dann die YAML zur dortigen Schema-Prüfung.
4. In der Vorschau Revision, Anzahl der Automationen, bisher gespeicherte
   Revision und SHA-256 kontrollieren.
5. „Geprüften Entwurf in Z2M speichern“ bestätigen. Das Modul prüft Sitzung und
   Revision erneut, bevor genau die geprüften Bytes übertragen werden.

Der zuletzt zur Prüfung oder Speicherung eingereichte Entwurf bleibt beim
erneuten Öffnen des Formulars erhalten. Reines Tippen ohne Bedienaktion wird
nicht gespeichert. Der Entwurf ist kein PHP-Skript und wird niemals ausgeführt.
Es wird auch keine bereits in Z2M gespeicherte YAML automatisch in den Editor geladen.

Textänderungen, Topic-/Gateway-Wechsel, ein Erweiterungsneustart oder eine
geänderte gespeicherte Revision machen die bisherige Prüffreigabe ungültig.
Bei Konflikten wird keine neue Revision automatisch erzeugt und nichts blind
überschrieben. Ein Empfängerspeicher in Fehler- oder Sicherungslesemodus blockiert
die Übertragung. Fehlermeldungen stehen im Formular und in „Letzter Erweiterungsfehler“.

Eine Zeitüberschreitung beweist nicht, dass Z2M nichts gespeichert hat. Vor erneutem
Übertragen den Erweiterungsstatus abfragen, Revision prüfen und den Entwurf bei
Bedarf erneut validieren. Das Modul wiederholt Installationen oder Speicheraufträge
nicht automatisch. „YAML gespeichert“ bestätigt Persistenz, keine Ausführung.

## Automatische Statusprüfung

Nach Anwenden der Einstellungen bzw. einem Modulupdate und beim Öffnen des
Formulars prüft das aktive Modul einmalig die Erreichbarkeit von Zigbee2MQTT.
Eine passende positive Health-Antwort startet anschließend die Abfrage des
Erweiterungsstatus. Die Statusanzeige aktualisiert sich anhand der Antworten.

Die Prüfung startet verzögert nach etwa einer Sekunde und benötigt eine
verbundene MQTT-Schnittstelle. Aufrufe innerhalb von zehn Sekunden werden
zusammengefasst. Laufende Aufträge haben Vorrang; Fehler oder Zeitüberschreitungen
führen nicht zu einer automatischen Wiederholung. Entwürfe werden nicht verändert;
Installation, Übertragung und Aktivierung bleiben ausdrückliche Bedienaktionen.
Die manuellen Prüfbuttons stehen weiterhin zur Verfügung.

## Einstellungen

| Feld | Standard | Bedeutung |
| --- | --- | --- |
| MQTT-Anbindung aktivieren (Active, Boolean) | Aus | Empfang sowie automatische Statusprüfung und manuelle Anfragen erlauben |
| Zigbee2MQTT-Basistopic (BaseTopic, String) | zigbee2mqtt | Exaktes Topic der Installation, auch mehrstufig |
| Wartezeit auf eine Antwort (RequestTimeout, Integer) | 10 Sekunden | Wartezeit auf eine Antwort; 1 bis 120 Sekunden |
| Automationsmodul des zweiten Z2M-Systems (PeerModuleID, Integer) | 0: kein Partner | ID des anderen Automationsmoduls; eigenes Modul und gleiches Basistopic sind nicht erlaubt. Auswahl auf beiden Seiten, Gerätedaten laden und beide Stände speichern/aktivieren |
| Aktivierte Automationen nach einem Z2M-Neustart automatisch fortsetzen (ResumeAfterRestart, Boolean) | Ein | Wird erst bei der nächsten Aktivierung in Z2M wirksam; bewusstes Stoppen bleibt dauerhaft wirksam |

Die Liste gefundener Basistopics ist eine Auswahlhilfe für BaseTopic, keine
weitere Verbindungseinstellung. Suchfelder, Zeilendialoge und die Auswahl einer
Automation sind Bearbeitungshilfen und keine dauerhaft gespeicherten Properties.

Basistopics dürfen keine MQTT-Platzhalter +/#, Steuerzeichen oder leeren
Pfadabschnitte enthalten. Führende und abschließende Schrägstriche sind ungültig.
Ein Wechsel des Topics oder MQTT-Gateways verwirft den bisherigen Statuscache
und eine noch offene Anfrage.

## Statusvariablen

Die Erweiterung kann ausdrücklich aktivierte Speicherrevisionen ausführen;
[Ausführungsregeln und Aktivierungsbefehle](../docs/RUNTIME.md). Speichern aktiviert nichts; eine
geänderte erfolgreich gespeicherte Revision stoppt die bisherige Ausführung.
`GetTransportState` übernimmt den gemeldeten Boolean `extension.executing`,
ohne damit eine konkrete Gerätewirkung zu bestätigen.

Alle Variablen sind ausschließlich lesbar und besitzen keine Schaltaktion.

| Ident | Typ | Bedeutung |
| --- | --- | --- |
| MQTTConnected | Boolean | Anbindung aktiviert, Konfiguration gültig und MQTT-Gateway aktiv |
| BridgeOnline | Boolean | Zusätzlich hat Z2M den Zustand online gemeldet |
| Z2MVersion | String | Gemeldete Z2M-Version; leer bis zur ersten gültigen Information |
| ExtensionInstalled | Boolean | z2m-automations.mjs in der empfangenen Erweiterungsliste gefunden |
| HealthStatus | String | Noch nicht geprüft, Antwort ausstehend, funktionsfähig, nicht funktionsfähig, Fehler, Zeitüberschreitung oder abgebrochen |
| ExtensionReady | Boolean | Empfängerprotokoll und Version durch eine frische passende Antwort bestätigt |
| StoredRevision | Integer | Zuletzt gemeldete Speicherrevision; 0 auch bei noch unbekanntem Zustand |
| OperationStatus | String | Fortschritt und Ergebnis der letzten Erweiterungsaktion |
| LastError | String | Feste verständliche Meldung zum letzten Erweiterungsfehler |
| ExecutionActive | Boolean | Ausführung durch einen frischen Antwortablauf bestätigt; false kann auch unbekannt bedeuten |
| ActiveRevision | Integer | Bestätigte aktive Revision; 0 bei gestopptem oder unbekanntem Zustand |
| EnabledAutomations | Integer | Bestätigte Anzahl aktivierter Regeln |
| ExecutionStatus | String | Maßgebliche Unterscheidung zwischen aktiv, gestoppt und unbekannt |
| RuntimeError | String | Feste Erklärung des zuletzt gemeldeten Engine-Fehlers |
| MeshReady | Boolean | Bestätigte Freigabe für Aktionen des zweiten Z2M-Systems; false auch bei unbekanntem Zustand |

Eine erkannte Erweiterungsdatei bestätigt noch nicht deren Betriebsbereitschaft.
Die beiden Online-Anzeigen unterscheiden die MQTT-Verbindung vom Zustand der
Z2M-Bridge. Nach Verbindungsabbruch ist eine neue Bridge-Statusmeldung nötig.
Eine Gesundheitsantwort setzt den Bridge-Onlinezustand nicht eigenständig.
StoredRevision allein bestätigt keinen eigenen Speicherauftrag: Auch eine
Statusmeldung kann diesen Wert aktualisieren. Maßgeblich ist das bestätigte
Ergebnis in OperationStatus.

## Instanzstatus

| Code | Bedeutung |
| --- | --- |
| 102 | MQTT verbunden und Z2M meldet online |
| 104 | Anbindung deaktiviert |
| 200 | Ungültiges Topic oder Antwortzeitlimit |
| 201 | Keine aktive kompatible MQTT-Verbindung |
| 202 | MQTT verbunden, Z2M offline oder noch kein Bridge-Status empfangen |

## Öffentliche Funktionen

Für eigene Skripte stehen die folgenden Aufrufe zur Verfügung. Die Bedienung
per Formular benötigt keinen Programmcode. InstanceID ist immer die ID Ihrer
Automationsinstanz. Alle Parameter müssen beim nativen Symcon-Aufruf ausdrücklich
angegeben werden, auch wenn die PHP-Methode einen Standardwert besitzt.

| Aufruf nach dem Präfix Z2MA_ | Rückgabe und Bedeutung |
| --- | --- |
| CheckConnection(int InstanceID) | String: startet die Verbindungsprüfung mit Ergebnis-Popup; entspricht dem Formularbutton |
| CheckHealth(int InstanceID, bool RefreshExtension) | String: Gesundheitsanfrage ohne Popup; true fragt nach positiver Antwort zusätzlich den Erweiterungsstatus ab |
| GetTransportState(int InstanceID) | String: JSON mit zuletzt bekanntem Verbindungs-, Speicher-, Prüf- und Ausführungszustand |
| InstallExtension(int InstanceID, bool Notify) | String: mitgelieferte Erweiterung installieren/ersetzen und Bereitschaft prüfen; true zeigt das Ergebnis-Popup |
| RequestExtensionStatus(int InstanceID) | String: aktuellen Empfänger-/Speicher-/Betriebszustand anfragen |
| LoadAutomationEditor(int InstanceID) | String: Geräte und gespeicherte Regeln laden; ersetzt nach erfolgreicher Antwort den lokalen Entwurf. Vorher eigene Änderungen sichern |
| ValidateAutomationDraft(int InstanceID) | String: aktuellen lokalen Entwurf in Z2M prüfen |
| StoreAutomationDraft(int InstanceID) | String: genau den erfolgreich geprüften lokalen Entwurf in Z2M speichern |
| ValidateYaml(int InstanceID, string Yaml) | String: übergebenen UTF-8-Text in Z2M prüfen; höchstens 128 KiB |
| StoreYaml(int InstanceID, string Yaml) | String: genau die zuvor erfolgreich geprüften Bytes speichern |
| ActivateAutomations(int InstanceID) | String: gespeicherten Stand aktivieren; eingeschaltete Regeln und Empfangsfreigaben werden wirksam |
| StopAutomations(int InstanceID) | String: gesamten Automatikbetrieb und Empfangsfreigaben stoppen |
| RunAutomationOnce(int InstanceID, string RuleID, string CurrentDraft, bool CheckDraft) | String: gespeicherte Regel einmal ausführen, auch bei ausgeschaltetem Regelschalter; Bedingungen gelten weiter |
| StopAutomationRun(int InstanceID, string RuleID) | String: den zugehörigen laufenden Ablauf stoppen, andere Auslöser und Regeln bleiben erhalten |
| RefreshExecutionStatus(int InstanceID) | Keine Rückgabe: Zustände einzelner Läufe anfragen |
| StartRuntimeDebug(int InstanceID, int Seconds) | Boolean: zeitlich begrenzte Ablaufaufzeichnung anfragen; 1 bis 900 Sekunden |
| StopRuntimeDebug(int InstanceID) | Boolean: nur die eigene Aufzeichnung beenden |

Die String-Rückgabe eines Auftrags ist dessen Anfragekennung oder ein leerer
String, wenn nichts gestartet wurde. Sie bestätigt noch keinen Erfolg.
Die Boolean-Rückgaben der Aufzeichnung bestätigen ebenfalls nur den angefragten
Versand. Maßgeblich sind die nachfolgende passende Antwort, OperationStatus und
der bestätigte Laufzeitstatus. Gleichartige Doppelklicks erzeugen keinen zweiten
Auftrag; andere Aufträge können währenddessen als beschäftigt abgewiesen werden.

GetTransportState liefert unter anderem mqttConnected, bridgeState
(unknown/online/offline), version, health, extension, extensionConfirmed,
runtimeConfirmed, operation, validation, validationDetails, execution und
lastError. Fehlende oder alte Statusdaten sind kein bestätigter aktueller Betrieb.
Interne Sitzungskennungen und vollständige YAML werden nicht ausgegeben.

RuleID ist die feste id einer Automation im gespeicherten Dokument, nicht ihr
Name, ihre Listennummer oder eine Symcon-Objekt-ID. Einmalige Ausführung erfordert,
dass der lokale Entwurf zum gespeicherten Stand passt. Mit CurrentDraft und
CheckDraft=true wird zusätzlich der übergebene Text gegen den lokalen Entwurf
verglichen. Für einen Aufruf ohne diesen Zusatzvergleich ausdrücklich leeren
String und false übergeben. Die normale Prüfung des gespeicherten Stands bleibt
wirksam. Ein Start kann echte Geräte schalten; Stopp nimmt gesendete Befehle
nicht zurück.

Beispiel für eine reine Verbindungsprüfung mit einer synthetischen Instanz-ID:

```php
$instanceID = 12345; // Durch die ID Ihrer Automationsinstanz ersetzen.
$request = Z2MA_CheckHealth($instanceID, false);
echo $request === '' ? 'Keine Anfrage gestartet.' : 'Anfrage gestartet; Antwort abwarten.';
```

Nach der Antwort lässt sich der Zustand separat lesen:

```php
$state = json_decode(Z2MA_GetTransportState($instanceID), true, 32, JSON_THROW_ON_ERROR);
echo $state['health'];
```

Weitere öffentliche Methoden dienen Formularcallbacks und Timern. Sie werden
durch das Modul aufgerufen und sind nicht für die manuelle Skriptbedienung nötig.
Hinweise zur begrenzten Ablaufaufzeichnung stehen in
[Debug und MQTT-Diagnose](../docs/DEBUG.md).
