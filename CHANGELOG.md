# Unveröffentlicht

## Neu

- Fünf zusätzliche synthetische Mesh-Prüfungen mit acht echten
  Erweiterungsinstanzen: alle 56 Zielrichtungen trotz gleicher Geräte-,
  Gruppen- und Regelkennungen, getrennte Freigaben, Zweigausfall/Wiederkehr,
  Antwortzuordnung und deutsche Debugmeldungen. Sieben äußere Systeme
  kommunizieren auch bei gestoppter Erweiterung am mittleren Broker.
  Die bisherigen Drei-System-Fälle bleiben erhalten; keine Laufzeitänderung.

- Sechs synthetische Mesh-Prüfungen mit drei echten Erweiterungsinstanzen:
  getrennte Regelläufe und Freigaben, Zweigausfall und Wiederkehr, verspätete
  oder falsch zugeordnete Antworten, Doppelvermeidung und deutsche Debugmeldungen.
  Äußere Systeme kommunizieren über den mittleren Broker auch ohne dessen
  laufende Erweiterung. Keine Änderung am Laufzeitcode oder Live-System.

- Schritt-für-Schritt-Anleitung zum Verbinden vorhandener Mosquitto-Broker,
  ausgehend von zwei Brokern bis zur Sternstruktur mit acht. Mit Sicherung,
  getrennten Benutzerrechten, beidseitigem Diagnoseempfang und Rückweg.

## Fixes

- Native Installationsprüfung erfasst Konsolenaufrufe vor dem Worker-Transport
  statt nach PHP-Namen in Seitenanfragen zu suchen. Testaufzeichnung mit
  getrennten Installations-/Statuszählern, unveränderter Weitergabe, Grenzen
  und Wiederherstellung; lokal mit SharedWorker und Debug-Abgleich an zwei
  synthetischen Systemen geprüft. Der echte zweite native Klicknachweis ist
  am 06.11.2026 an Test-Symcon 9.1 mit genau einem RPC/Debug-Paar/Upload
  bestätigt; Entwürfe und bisheriger Betrieb erhalten, Aufzeichnungen beendet.

- Planung und Prüfliste an implementierte Zentralen-/Designerfunktionen und
  bestätigte Praxis angepasst; verbleibende Nachweise einzeln benannt.

- Die Änderungsübersicht der Partnerfreigaben verwendet bei einem Treffer
  „1 lokale Automation verwendet diese Verbindung“. Auch genau eine weitere,
  nicht einzeln angezeigte Automation wird korrekt in der Einzahl beschrieben.

## Partnerfreigaben im Browserdesigner verwalten

- Beim empfangenden System per Häkchen auswählen, welche anderen zugeordneten
  Systeme Befehle senden dürfen. Richtung, Basistopics und Änderungen vor der
  Übernahme anzeigen; die Gegenrichtung wird nicht automatisch freigegeben.
- Vor dem Entzug betroffene lokale Automationen nennen, einschließlich
  ausgeschalteter Regeln und Aufrufen über Aktionsfolgen. Bisherige Freigaben für
  nicht zugeordnete Systeme sichtbar erhalten und nur ausdrücklich entfernen.
- Ausschließlich den lokalen Empfangsentwurf ändern. Andere Regeln, Folgen,
  Auswahl und Entwürfe bleiben erhalten. Veraltete Bestätigungen und offene
  Eingaben abweisen; deutsche Debugmeldungen erklären Lesen, Übernahme und
  wiedererkannte Aufträge. Kein Z2M- oder MQTT-Auftrag und kein Erweiterungsupdate.

## Designer über HTTP im LAN bedienen

- Systemwechsel und weitere Designeraktionen brechen über HTTP-Adressen im LAN
  nicht mehr an der fehlenden Browserfunktion randomUUID ab. Alle Kennungen
  entstehen gemeinsam aus sicheren Zufallswerten mit getRandomValues.
- Auch Erstellen, Bearbeiten und die lokale Vorschau verwenden denselben Weg.
  Fehlen sichere Zufallswerte, erscheint eine deutsche Meldung; der Auftrag wird
  nicht versendet. Keine Änderung an Entwürfen, Zugriffsrechten oder Z2M nötig.

## Gerätedaten nach Erweiterungsinstallation automatisch aktualisieren

- Nach bestätigter Installation die Geräte, Kanäle, Gruppen und Szenen desselben
  Systems automatisch nachladen. Native Zentrale und Designer zeigen den
  Ladezustand, Erfolg oder einen getrennten Fehler des Geräteabrufs.
- Entwürfe, Partnerfreigaben und offene Eingaben erhalten. Ein bereits laufender
  Geräteabruf wird mitbenutzt. Bei Zeitüberschreitung oder Modulupdate erfolgt
  weder ein zweiter Upload noch eine automatische Wiederholung.
- Deutsche Debugmeldungen erklären den Ablauf. Kein zusätzlicher Speicher-,
  Aktivierungs- oder Schaltbefehl; kein Update der Z2M-Erweiterung erforderlich.

## Erweiterung je System installieren und aktualisieren

- Mitgelieferte Erweiterung im nativen Zentralenformular und unter „Systeme und
  Diagnose“ im Designer installieren oder aktualisieren. Bestätigung mit System,
  Build und Hinweis auf mögliche Unterbrechungen von Abläufen und Aufzeichnungen.
- Frische Verbindungsprüfung, Upload der festen mitgelieferten Datei und
  Bestätigung einer neuen Erweiterungssitzung mit erwartetem Build.
  Bekannte Revision und Prüfsumme der Automationsdatei werden zusätzlich geprüft.
- Erstinstallation bei besitzerlosen Verbindungen ohne vorzeitige Übernahme;
  verwaltete Verbindungen akzeptieren nur ihre zuständige Zentrale.
- Getrennte Status- und deutsche Debugmeldungen. Verlorene Antworten, wiederholte
  Aufrufe und Modulupdates führen zu keinem automatischen zweiten Upload.
  Entwürfe und Automationsdateien werden nicht übertragen oder aktiviert.

## Systeme und Diagnose im Browserdesigner

- Alle zugeordneten Z2M-Systeme mit MQTT-Zuordnung, Verbindungsprüfung,
  Erweiterungsstand und laufender Aufzeichnung gemeinsam anzeigen. Systemwechsel
  verwendet den bestehenden abgesicherten Auswahlweg.
- Installierten und mitgelieferten Build samt Updatehinweis aus der vorhandenen
  Verbindungsdiagnose übernehmen. Unbestätigte Stände bleiben ausdrücklich offen.
- Verbindung und Erweiterung auf Klick prüfen; Ablaufaufzeichnung je Verbindung
  für 1, 5, 10 oder 15 Minuten starten und gezielt stoppen. Die absolute Endzeit
  bleibt bei Statusabfragen erhalten. Meldungen erscheinen im nativen Debugfenster
  der benannten Z2M-Verbindung.
- Diagnoseöffnung und Nachladen lesen nur vorhandene Daten. Aufträge sind system-
  und kontextgebunden; Wiederholungen senden nicht erneut. Entwürfe, offene
  Eingaben und Automatikbetrieb bleiben erhalten. Kein Erweiterungsupdate nötig.

## Ereignisimport in Zentrale und Browserdesigner

- Symcon-Ereignisse je Z2M-System automatisch anhand von MQTT-Verbindung,
  Basistopic und Variablen-ID finden, prüfen und als ausgeschaltete Regeln
  übernehmen. Auch erlaubte Ziele anderer Systeme werden korrekt zugeordnet.
- Im Designer nach Ereignis, Gerät oder ID suchen, importierbare Einträge
  filtern und eine ausdrückliche Vorschau vor der Übernahme prüfen. Gerät und
  Instanz-ID, Ablehnungsgründe sowie Such- und Ladezustand sind sichtbar.
- Bestehende Konvertierung wiederverwenden. Geänderte Ereignisse, entzogene
  Rechte, doppelte Importe, veraltete Zugänge und offene Eingaben werden
  abgefangen. Originalereignisse, andere Entwürfe und Freigaben bleiben erhalten.
- Deutsche Debugmeldungen erklären Suche, Prüfung und lokale Übernahme ohne
  Ereignisnamen, Aktionswerte, Skripte oder Vorschautokens. Kein Erweiterungsupdate.

## Vollständiger Regeleditor im Browserdesigner

- Neue und vorhandene Automationen im selben Dialog vollständig erstellen und
  bearbeiten: mehrere Geräte-, Uhrzeit- und Sonnenauslöser, Bedingungen sowie
  mehrere Aktionen einschließlich Szenen, Aktionsfolgen und Wartezeiten.
  Neue Regeln beginnen immer ausgeschaltet.
- Aktionsschritte am Griff per Drag & Drop oder über die Pfeilbuttons anordnen,
  auch in wiederverwendbaren Folgen. Werte und Wartezeiten bleiben beim Schritt.
  Erst das Übernehmen ändert den lokalen Entwurf.
- Gemeinsame Validierung mit vollständiger Ablehnung fehlerhafter Eingaben.
  Vorbelegung, Systemtrennung und Partnerfreigaben bleiben erhalten. Deutsche
  Debugmeldungen erklären Übernahme und neue Reihenfolge ohne Aktionswerte.
  Kein Erweiterungsupdate erforderlich.

## Gerätedaten im Browserdesigner

- Gerätedaten je Z2M-System direkt im Designer aktualisieren. Ladeanzeige,
  Verbindungsschritt, Abschlusszeit und Anzahl der Geräte, Gruppen und Szenen
  machen den Fortschritt sichtbar. „Ladestatus prüfen“ fragt nur die Rückmeldung ab.
- Ausschließlich aktuelle, passend bestätigte Kataloge übernehmen. Fehler,
  Zeitüberschreitung und geänderte Verbindungen erhalten den bisherigen Bestand;
  kein automatischer Neuversand nach Modulupdate oder fehlender Bestätigung.
- Entwürfe, Partnerfreigaben, Auswahl und offene Eingaben bleiben erhalten.
  Deutsche Debugmeldungen erklären die Schritte. Keine Speicherung oder
  Aktivierung von Automationen und kein Erweiterungsupdate erforderlich.

## Regelverwaltung im Browserdesigner

- Automationen als ausgeschaltete Kopie anlegen und aus dem lokalen Entwurf
  löschen. Ein eigener Bestätigungsdialog nennt die Wirkung; vorhandene
  Z2M-Dateien und laufende Regeln werden dabei nicht verändert.
- Unbenutzte Aktionsfolgen löschen. Bei verwendeten Folgen werden die
  betroffenen Automationen angezeigt und das Löschen abgewiesen.
- Nach aktiviertem oder deaktiviertem Regelschalter im Entwurf filtern; Suche
  und Filter werden je System getrennt beibehalten. Deutsche Debugmeldungen
  erklären Übernahme, Ablehnung und wiederholt bestätigte Aufträge.

## Zentrale Betriebssteuerung

- Gespeicherte Automationen je Z2M-System im nativen Formular und Browserdesigner
  aktivieren oder stoppen. Ein Wiederanlauf nach Z2M-Neustart ist wählbar.
  Aktivierung und Stopp werden erst nach einer zusätzlichen Statusantwort als
  bestätigt angezeigt. Speichern startet weiterhin keine Automation.
- Einzelne gespeicherte Regeln einmal mit Bedingungsprüfung starten und genau
  ihren aktuellen Lauf stoppen. Entwürfe, Regelfreigaben und andere Systeme
  bleiben erhalten. Auch ausgeschaltete Regeln lassen sich einmal testen.
- Deutsche Status- und Debugmeldungen erklären Ziel, ausstehende Bestätigung,
  Bedingungsablehnung und Abbruch. Nach Zeitüberschreitung oder Neustart wird
  kein Auftrag wiederholt. Ein möglicher Betriebsauftrag sperrt den rein lokalen
  Übernahme-Rückweg. Reale Testabnahme nach Modulupdate noch offen;
  Erweiterungsupdate nicht erforderlich.

## Korrigiert

- Parallele Aufrufe melden verständlich, wenn das Modul gerade beschäftigt ist.
  Die irreführende Meldung über eine fremde Verwaltung entfällt in diesem Fall.
  Abgewiesene Aktionen werden nicht ausgeführt oder automatisch wiederholt;
  tatsächliche Besitzerkonflikte bleiben geschützt.

- Nach der lokalen Übernahme einer Z2M-Verbindung wird das Formular erst nach
  Abschluss des laufenden Aufrufs neu aufgebaut. Dadurch erscheint keine falsche
  Sperrmeldung mehr. Dieselbe Aktualisierung gilt für Rückweg, Systemauswahl und
  Prüfstatus. Deutsche Debugmeldungen erklären Vormerkung und Freigabe; ein
  Anzeigefehler macht eine erfolgreiche Übernahme nicht rückgängig.

- Der Browserdesigner bleibt beim parallelen Laden seiner Dateien nicht mehr
  im leeren Startzustand stehen. Nur ausdrücklich erlaubte Dateiaufrufe warten
  bis zu fünf Sekunden auf einen laufenden Aufruf; Besitzer- und Schreibschutz
  bleiben erhalten. Bei einem fehlgeschlagenen Start erscheint eine verständliche
  Meldung mit „Designer neu laden“. Debug erklärt Wartezeit und Freigabe.

- Die neue Automationszentrale lässt sich ohne eigenen MQTT-Client oder -Server
  anlegen. Der native Instanzassistent erhält dafür eine ausdrückliche
  SDK-Angabe ohne Parent-Anforderung. Die Auswahl heißt nun eindeutig
  „Zigbee2MQTT Automationszentrale“. Vorhandene Verbindungen und Entwürfe werden
  dadurch nicht umgestellt; Z2M-Verbindungsinstanzen behalten ihre MQTT-Anbindung.

## Planung

- Zielarchitektur und Übernahmeplan für eine zentrale Automationsinstanz mit
  bis zu acht Z2M-Verbindungsmodulen dokumentiert. Enthält Sicherung, Erhalt
  vorhandener Entwürfe und Zuordnungen, Konfliktbehandlung, Rückweg und
  Prüfmatrix. Noch keine Änderung am Modulverhalten oder an einer Installation.

## Neu

- Direkte Einrichtung der Zentrale ohne alte Automationsinstanzen: vorhandene
  Z2M-Regeln und Gerätedaten sicher lesen, Vorschau und private Sicherung prüfen,
  anschließend die Verbindung ausdrücklich übernehmen. Gespeicherte YAML und
  Partnerfreigaben bleiben erhalten. Vorhandene lokale Entwürfe und fremde
  Besitzer werden geschützt; leere Systeme erhalten nur einen lokalen Entwurf.
  Verständliche Status-/Debugmeldungen, fortsetzbarer lokaler Besitzerwechsel
  und gesicherter Rückweg. Keine Speicherung, Aktivierung oder Schaltung in Z2M.

- Die Automationszentrale prüft und überträgt Entwürfe getrennt je Z2M-System,
  im nativen Formular und Browserdesigner. Status und genaue Ablehnungen bleiben
  beim richtigen System sichtbar; andere Entwürfe bleiben erhalten. Speicherung
  benötigt aktuelle Prüfung und Schaltrechte sowie abschließende Bestätigung.
  Keine automatische Aktivierung oder Wiederholung nach einem Abbruch.
  Verständliche Debugmeldungen erklären die einzelnen Schritte. Voraussetzung
  ist die abgeschlossene gesicherte Übernahme. Nach möglichem Schreiben in Z2M
  wird der rein lokale Übernahme-Rückweg gesperrt, um keine Wiederherstellung
  des externen Betriebs vorzutäuschen. Keine Änderung am Erweiterungsprotokoll.

- Vorhandene Z2M-Verbindungen werden in der Zentrale automatisch mit Name,
  Instanz-ID, Basistopic und MQTT-Zuordnung angezeigt und per Häkchen ausgewählt.
  Die Auswahl wird erst ausdrücklich übernommen. Bereits zugeordnete Systeme
  bleiben geschützt angehakt; fremd zugeordnete oder unvollständige Verbindungen
  sind mit verständlichem Hinweis gesperrt. Veraltete Auswahlen werden abgewiesen.
  Deutsche Debug-Ausgaben erklären Erkennung und Übernahme ohne MQTT-Anfrage.
  Erneutes Bestätigen migrierter Systeme erhält deren Herkunft und Entwürfe.

- Gemeinsame Systemauswahl im nativen Formular und Browserdesigner für bis zu
  acht Z2M-Systeme. Regeln und Aktionsfolgen werden im jeweiligen lokalen Entwurf
  erstellt und bearbeitet. Vorbelegung, offene Eingaben und Partnerfreigaben
  bleiben getrennt erhalten. Veraltete Fenster können kein anderes System
  überschreiben. Große Browser-Gerätelisten werden seitenweise gelesen;
  verständliche Debug-Ausgaben erklären Systemwahl, Änderungen und Abbrüche.
  Zentrale Speicher- und Laufzeitaufträge folgen im nächsten Ausbauschritt.

- Lokale Sicherung und Übernahme vorhandener Automationsinstanzen mit geprüftem
  Rückweg: private Archive, dauerhaftes Journal, Quellen-/Zielvergleich und
  exklusiver Besitzerwechsel für bis zu acht Systeme. Alte Editoren und Zugänge
  werden vor Freigabe der zentralen Kopien gesperrt. Abbrüche sind fortsetzbar;
  spätere zentrale Änderungen bleiben beim Rückweg zusätzlich gesichert.
  Verständliche deutsche Ablauf- und Fehlermeldungen. Keine automatische
  Migration, MQTT-Ausgabe oder Änderung laufender Z2M-Automationen. Die gemeinsame
  Bedienoberfläche ist inzwischen für die lokale Bearbeitung ergänzt.

- Lokaler zentraler Arbeitsbereich als nächster Baustein der Mehrsystemverwaltung:
  bis zu acht Verbindungen ausdrücklich zuordnen und Dokumente, Auswahl und offene
  Eingaben getrennt halten. Aktuelle native Schaltrechte und gerichtete Freigaben
  begrenzen die Zielauswahl. Veraltete Bearbeitungsstände werden abgewiesen;
  wiederholte Aufträge erhalten eine Quittung. Vorhandene Freigaben sind bei
  gewöhnlichen Änderungen geschützt. Geräteauswahl ist seitenweise begrenzt.
  Deutsche Debugmeldungen erklären die lokalen Entscheidungen. Die Funktionen
  übertragen nichts an Z2M; der gemeinsame Editor folgt separat. Die ausdrückliche
  Übernahme ist inzwischen als eigener lokaler Ablauf implementiert.

- Z2M-Verbindung als eigener Baustein für die künftige zentrale Verwaltung:
  vorhandenes MQTT-Gateway verwenden, Verbindung und Erweiterungsstand prüfen,
  Gerätedaten laden und Ablaufmeldungen zeitlich begrenzt aufzeichnen. Antworten,
  Kataloge und Zeitlimits bleiben je System getrennt. Verständliche deutsche
  Status-, Popup- und Debugmeldungen. Bestehende Automationsinstanzen und Entwürfe
  werden noch nicht übernommen; gemeinsame MQTT- und Kataloglogik weiterverwendet.

- Erste Grundlage der zentralen Verwaltung: versionierter Systemvertrag für
  bis zu acht Systeme und lesende Bestandsvorschau über ausgewählte vorhandene
  Automationsinstanzen. Konflikte, offene Eingaben und abweichende Entwürfe
  werden verständlich gemeldet. Keine Anlage, Übernahme, Verbindungsänderung
  oder MQTT-Ausgabe; native Debugmeldungen erklären die Prüfung ohne Nutzdaten.

- Browserdesigner: Wiederverwendbare Aktionsfolgen erstellen und bearbeiten.
  Schritte mit Geräte-, Kanal-, Wert- und Szenenauswahl werden vorbelegt;
  Wartezeiten, Hinzufügen, Entfernen und Umsortieren sind direkt bearbeitbar.
  Der Hinweis auf verwendende Automationen und die gemeinsame Schrittprüfung
  schützen bestehende Regeln. Die Übernahme erhält Partnerfreigaben und ändert
  nur den lokalen Entwurf. Bereinigte deutsche Debugmeldungen erklären das Ergebnis.

- Browserdesigner: „Neue Automation“ bietet jetzt auch bis zu acht Uhrzeiten
  sowie Sonnenaufgang oder Sonnenuntergang mit Standort, Zeitzone und Zeitversatz.
  Doppelte bzw. ungültige Zeiten werden abgewiesen. Alle Regeln beginnen
  ausgeschaltet im lokalen Entwurf; Debugmeldungen erklären Auslöserart und
  Sonnenzeitversatz, ohne Standortkoordinaten auszugeben.

- Browserdesigner: Vorhandene Automationen direkt bearbeiten. Geräte-, Zeit-
  und Astroauslöser, Bedingungen, Aktionsschritte und Wartezeiten werden
  vorbelegt. Aktuelle Rechte und zwischenzeitliche Änderungen werden geprüft;
  nicht vollständig darstellbare Bereiche bleiben geschützt erhalten. Die
  Übernahme ändert nur den lokalen Entwurf und erhält andere Regeln,
  Partnerfreigaben und Aktionsfolgen. Verständliche deutsche Debug-Ausgaben.

- Beim Hinzufügen und Bearbeiten von Automationen vorhandene Partnerfreigaben
  erhalten, wenn kein verfügbares Partnermodul ausgewählt ist. Die bisherige
  Freigabe wird nicht mehr nebenbei durch eine leere Liste ersetzt. Der Debug
  erklärt ausdrücklich, dass sie im Entwurf erhalten bleibt und Z2M unverändert ist.

- Gespeicherte Automationen beim Laden vollständig übernehmen, einschließlich
  Partnerfreigaben, Revision und unverändertem YAML-Text. Ein im Symcon-Modul
  nicht ausgewählter Partner verändert den geladenen Stand nicht mehr nebenbei.

- JSON-Anfragen bei der Browser-Regelerstellung auch mit Symcons
  `HTTP_CONTENT_TYPE` erkennen. Dadurch wird die Übernahme nicht mehr fälschlich
  mit HTTP 415 abgewiesen. Standardheader, optionale Zeichensatzangabe und
  widersprüchliche oder fremde Inhaltstypen werden ebenfalls geprüft. Neue
  Debugmeldung nennt nur Header-Verfügbarkeit und JSON-Erkennung.

- Browserdesigner: „Neue Automation“ ergänzt eine ausgeschaltete Geräte-Regel
  im bestehenden Symcon-Entwurf. Auslöser und Zielgerät getrennt durchsuchen,
  dann Eigenschaft/Kanal und Wert wählen. Aktuelle Schaltrechte, offene native
  Eingaben und zwischenzeitliche Entwurfsänderungen werden beim Übernehmen
  geprüft; wiederholtes Senden legt keine zweite Regel an. Deutsche Debugmeldungen
  erklären Ergebnis und Ablehnungen. Keine Übertragung oder Aktivierung in Z2M.

- Gerätedaten getrennt von gespeicherten Automationen aktualisieren: Der neue
  Modulbutton liest Geräte, Kanäle, Gruppen und Szenen aus Z2M, ohne Entwürfe,
  offene Formulareingaben oder den Automatikbetrieb zu ersetzen. Native Anzeige
  und Browserdesigner nennen die letzte erfolgreiche Geräteaktualisierung.
  Aktionsrechte werden weiterhin anhand der Symcon-Variablen geprüft. Dafür
  müssen Modul und mitgelieferte Z2M-Erweiterung aktualisiert werden.

- Erster lesender Automationsdesigner aus dem Modul: vorhandene Regeln,
  Aktionsfolgen, Geräte, Kanäle und Szenen im Browser ansehen. Bedingungen,
  Wartezeiten und systemübergreifende Ziele werden mit den vorhandenen Daten
  angezeigt; aktuelle Symcon-Schaltrechte sind erkennbar. Kurzlebiger Lesezugang,
  verständliche Hinweise bei fehlenden Daten und deutsche native Debugausgaben.
  Kein Speichern oder Schalten, keine Änderung der Z2M-Erweiterung. Zusätzlich
  frühe MQTT-Nachrichten beim Modulneuladen bis zur vollständigen Registrierung
  der Einstellungen abweisen. Die Offline-Vorschau bleibt getrennt erhalten.

- Klickbare Offline-Vorschau eines eigenen Automationsdesigners: Navigation,
  Ablauf „Wenn / Nur wenn / Dann“, Eigenschaften und Aktionen per Drag & Drop.
  Ausschließlich erfundene Beispieldaten und Speicherung im geöffneten Fenster;
  noch keine Modul-, MQTT- oder Geräteanbindung. Einstieg und Prüfgrenzen unter
  docs/prototypes/automation-designer/README.md. Bestehende Formulare und die
  Z2M-Erweiterung bleiben unverändert.

- Gerätebedingungen erhalten die Auswahl „Nur beim Start“. Damit kann etwa
  Helligkeit das Einschalten begrenzen, ohne später das Ausschalten oder die
  Verlängerung einer laufenden Wartezeit zu verhindern. Ein neuer Start und
  „Von vorn beginnen“ prüfen die Bedingung erneut. Vorbelegung, Eingabeschutz,
  Validierung und deutsche Debugmeldungen berücksichtigen die Auswahl.
  Modul und Z2M-Erweiterung müssen gemeinsam aktualisiert werden.

- Die Spalte „Im Entwurf eingeschaltet“ erhält mehr Platz, da der vollständige
  Titel in der nativen Konsole mit der bisherigen Breite abgeschnitten wurde.
  Für die angepasste Breite genügt ein Modulupdate.

- Benutzeranleitung nach Einrichtung und Arbeitsablauf geordnet. Veraltete
  Hinweise zu automatischer Statusprüfung, Editorumfang und Wiederanlauf
  korrigiert; Einstellungen, Statusvariablen und Skriptaufrufe ergänzt.
  Die Regelübersicht nennt ausdrücklich den Schalter im Entwurf; der Löschdialog
  erklärt, dass erst das spätere Speichern den Z2M-Betrieb ändert.
  Versionsübersicht unterscheidet den praktisch belegten Stand 2.14.2-dev
  von der noch nicht separat abgenommenen stabilen Ausgabe 2.14.2.

- Gemeinsame Gerätebefehle im Klick-Editor: bis zu acht schaltbare Eigenschaften
  eines Geräts in einem Schritt hinzufügen, ersetzen und entfernen. Vorbelegung
  zeigt alle enthaltenen Werte; Farbleuchten erhalten eine native Farbauswahl.
  Schaltrechte, Zahlenbereiche, Schrittweiten und Farbtemperatur-Konflikte werden
  erneut geprüft. Funktioniert auch in wiederverwendbaren Folgen und mit Zielen
  im zweiten System. Unbekannte Composite-Kinder werden nicht mehr fälschlich
  als einzelne Schaltwerte angeboten. Verständliche deutsche Debug-Ausgaben
  ohne Nutzwerte. Modul und Erweiterung aktualisieren und Gerätedaten neu laden.
  Zunächst lokal mit synthetischen Daten geprüft. Anschließend native Erstellung,
  RGB-Farbwahl, Vorbelegung, Ersetzen und Entfernen in Test-Symcon bestätigt;
  Prüfung, Speicherung und Wiederladen einschließlich wiederverwendbarer Folge
  erfolgreich. Partnerziele über Modulmethoden geprüft. Ausgeschaltete Testdaten
  entfernt und ursprünglichen Betrieb wiederhergestellt. Anschließend Einschalten,
  Helligkeit und Farbe an einer Farbleuchte in einem einzigen Schritt real geprüft;
  frische Gerätewerte, Symcon-Variablen und Debug bestätigen das Ergebnis.
  Ursprünglichen Lichtzustand und Automatikbetrieb wiederhergestellt.

- Verhalten bei erneuter Auslösung im Klick-Editor: neue Auslösung ignorieren,
  die Aktionsfolge von vorn beginnen oder die laufende Wartezeit zurücksetzen.
  Vorbelegung, geschützte Formulareingaben, Kopieren, Prüfung und Speicherung
  enthalten die Auswahl. Laufzeit prüft Bedingungen und Sperrzeit erneut,
  wartet laufende Befehlsübergaben ab und verwirft vorgemerkte Neustarts bei
  Fehlern oder Stopp. Feste deutsche Ablaufmeldungen über den vorhandenen
  Debugkanal. Modul und Z2M-Erweiterung gemeinsam aktualisieren; zunächst nur
  lokal mit synthetischen Daten geprüft, keine laufende Installation verändert.

- Mehrere Startzeiten im Klick-Editor: eine bis acht unterschiedliche Uhrzeiten
  mit gemeinsamer Zeitzone starten dieselbe Automation. Einträge lassen sich
  hinzufügen, bearbeiten und löschen; vorhandene Regeln werden vorbelegt.
  Wochentage können unabhängig vom Zeitfenster ausgewählt werden, auch für
  Geräte- und Astro-Regeln. Die neue Wochentagsbedingung verwendet den aktuellen
  Kalendertag; bisherige Zeitfenster behalten ihre Starttage über Mitternacht.
  Prüfung weist leere, doppelte oder ungültige Angaben ohne Entwurfsänderung ab.
  Offene Eingaben und erweiterte YAML-Regeln bleiben geschützt. Deutsche
  Debugmeldungen erklären Bearbeitung und Wochentagsprüfung. Modul und
  Z2M-Erweiterung müssen gemeinsam aktualisiert werden; lokale Tests mit
  synthetischen Daten, noch keine Änderungen an laufenden Installationen.
  Anschließende native Prüfung: fehlende Hinzufügen-/Löschen-Optionen der
  Uhrzeitenlisten korrigiert. Erstellen sowie Prüfen, Speichern, Bearbeiten und
  Wiederladen am freigegebenen Empfänger bestätigt; Testregel ausgeschaltet,
  nach Abschluss entfernt und vorheriger Betrieb wiederhergestellt.

- Größere Automationsübersichten mit zehn sichtbaren Regeln und flexiblen
  Spalten. Suche nach Automations- und Gerätenamen sowie Filter für eingeschaltete
  oder ausgeschaltete Regeln je Bereich. Übersicht und Bearbeitungsauswahl
  zeigen dieselben Treffer; eine laufende Bearbeitung bleibt verfügbar.
  Ausgewählte Automationen lassen sich als ausgeschaltete Kopien mit neuer
  Kennung und eigenem Namen in den Entwurf übernehmen. Auslöser, Bedingungen,
  Aktionen und Folgenverweise bleiben erhalten. Offene Eingaben und veraltete
  Aufrufe sind geschützt. Deutsche Debug-Ausgaben enthalten keine Suchtexte
  oder Regelinhalte. Native Sichtprüfung: Namen, Auslöser, Ziel und Regelschalter
  verwenden genau eine flexible Spalte, damit Überschrift und Zeilen gleich
  ausgerichtet bleiben. Ein Modulupdate genügt; Erweiterung unverändert.

- Der Klick-Editor zeigt noch nicht übernommene Formulareingaben, den Stand des
  Entwurfs und den bestätigten Z2M-Betrieb getrennt an. Beim Wechsel zu einer
  anderen Automation, zum anderen Regelbereich oder zu einer anderen Aktionsfolge
  bleiben Änderungen erhalten, bis der Anwender ausdrücklich verwirft. Prüfung,
  Laden, Import, Löschen, Speicherung und Aktivierung erinnern bei offenen Eingaben
  an die Übernahme in den Entwurf. Statusabfragen und Stopp bleiben verfügbar.
  Verständliche deutsche Debug-Ausgaben protokollieren den Ablauf ohne Eingaben.
  Ein Modulupdate genügt; die Z2M-Erweiterung bleibt unverändert.

- Die manuelle Zigbee2MQTT-Verbindungsprüfung bestätigt das Ergebnis in einem
  Popup. Erfolg erst nach passender Antwort; Fehler, Abbruch und fehlende Antwort
  erhalten verständliche Hinweise. Automatische Prüfungen bleiben ohne Popup.
  Ein Modulupdate genügt; die Z2M-Erweiterung bleibt unverändert.

- Einrichtung und Diagnose zeigt installierten und mitgelieferten Erweiterungsstand
  mit automatisch erzeugter Buildkennung und Updatehinweis. Der Vergleich wird
  durch die vorhandenen Statusabfragen aktualisiert. Ältere Erweiterungen ohne
  Kennung erhalten eine verständliche Erklärung. Deutsche Debugmeldungen nennen
  das Ergebnis ohne wiederholte identische Ausgaben. Für den Vergleich Modul und
  mitgelieferte Erweiterung aktualisieren; keine automatische Installation.

- Wiederverwendbare Aktionsfolgen im Klick-Editor: einmal anlegen, bearbeiten
  und in mehreren Geräte- oder Zeitautomationen auswählen. Name, Schritte und
  Wartezeiten werden vorbelegt; die Übersicht zeigt die Verwendung. Verwendete
  Folgen sind vor dem Löschen geschützt. Bedingungen, Einzelstart, gezielter
  Stopp, Wiederanlauf und Aktionen im zweiten Z2M-System bleiben nutzbar.
  Insgesamt höchstens 16 ausgeführte Schritte je Automation. Deutsche Debug-
  und Fehlermeldungen ordnen enthaltene Schritte zu. Modul und Erweiterung
  gemeinsam aktualisieren. Lokal geprüft; native Praxisprüfung steht aus.

## Fehlerkorrekturen

- Z2M-Verbindung: Ein bestätigter bereiter Empfänger wird auch ohne zuvor
  empfangene Erweiterungsliste als „Installiert“ angezeigt. Vorhandene bestätigte
  Zustände berichtigen die Anzeige ohne neue MQTT-Anfrage. Die Verbindungsprüfung
  zeigt „Funktionsfähig“ und die übrigen Prüfzustände auf Deutsch. Native
  Debugmeldungen erklären Bestätigung, Anzeigekorrektur und fehlgeschlagene Prüfungen.
  Ein Modulupdate genügt; kein Erweiterungsupdate erforderlich.

- „Änderungen verwerfen und wechseln“ funktioniert auch im nativen Popup.
  Dessen Felder werden jetzt beim Formularaufbau berücksichtigt, der Button
  erhält die vollständige Erfassung und der Bestätigungswert bleibt beim
  Neuaufbau erhalten. Veraltete Bestätigungen nennen im Debug einen verständlichen
  Grund ohne Eingaben oder Tokenwerte. Native Prüfung einschließlich
  Weiterbearbeiten, Bereichswechsel, Prüfungsschutz und lokaler Übernahme
  erfolgreich. Ein Modulupdate genügt; geöffnete Formulare erneut öffnen.

- Regelauswahl und Änderungsverfolgung im nativen Formular funktionieren ohne
  direkten Zugriff auf PHP-Hilfsklassen des Moduls. Damit entfällt der Fehler
  „EditorWorkInput not found“, der vor dem Moduleinstieg und dessen Debug-Ausgaben
  auftrat. Die Erfassung bleibt auf Bearbeitungsfelder begrenzt. Ein zusätzlicher
  Test prüft das tatsächliche Formularskript in einem getrennten PHP-Prozess.
  Ein Modulupdate genügt; geöffnete Formulare anschließend schließen und neu öffnen.

- „Zigbee2MQTT-Verbindung prüfen“ funktioniert wieder ohne Parameterfehler.
  Der Button übergibt die vollständige Argumentliste an Symcon; Prüfung und
  zugehörige Debug-Ausgaben werden dadurch wieder gestartet.

- Geräte, Eigenschaften und Werte lassen sich im Aktionsdialog wieder auswählen,
  ohne dass die Konsole wegen eines fehlenden Funktionsparameters abbricht.

- Konfigurationsformulare mit großen Geräte- und Aktionskatalogen bleiben unter
  der Symcon-Ausgabegrenze. Listen übertragen nur verwendete Einträge; Geräte,
  Eigenschaften, Kanäle und Szenen bleiben im Zeilendialog vollständig auswählbar.
  Vorbelegung und noch nicht übernommene Zeilen bleiben dabei erhalten.

## Features

- Sonnenauf- und Sonnenuntergang im Klick-Editor mit Standort, Zeitzone und
  Zeitversatz von −720 bis +720 Minuten. Vollständige Vorbelegung, Prüfung und
  Speicherung; eigene tägliche Berechnung direkt in Z2M ohne laufendes Symcon
  oder Internetdienst. Fehlende Ereignisse werden übersprungen, verpasste Zeiten
  nicht nachgeholt. Deutsche Debugmeldungen enthalten keine Koordinaten.
  Modul und Erweiterung gemeinsam aktualisieren. Auswahl, Bearbeitung,
  Vorbelegung, Prüfung und Speicherung im nativen Formular mit ausgeschalteter
  Testregel bestätigt. Tatsächlicher Astro-Start mit negativem Zeitversatz und
  kurzer Ein-/Aus-Folge an einem Gerät einschließlich Debug und Rückmeldungen
  praktisch geprüft; Testregel anschließend entfernt.

- Zeitlich begrenzte Z2M-Ablaufaufzeichnung im nativen Symcon-Debug: Start für
  5, 10 oder 15 Minuten, eigene Bestätigung und Stopp. Meldungen erklären
  Auslöser, Bedingungen, Wartezeiten, Schritte und Fehler; gemeinsame
  Laufbezüge verbinden auch Aktionen zwischen zwei Z2M-Systemen. Unabhängige
  Aufzeichnungen mehrerer Instanzen, bereinigte Inhalte und begrenzter Versand
  ohne Warten der Automationen auf die Diagnose. Modul und Erweiterung müssen
  gemeinsam aktualisiert werden. Über Test-Symcon installiert und Start,
  Zeitablauf, Stopp sowie unabhängige Aufzeichnungen praktisch geprüft. Der
  gemeinsame Laufbezug einer realen systemübergreifenden Geräteaktion wurde
  inzwischen auf beiden Seiten bestätigt; bestehender Betrieb blieb erhalten.

- Vorhandene Z2M-Szenen als Aktionen im Klick-Editor: Auswahl mit Szenennamen,
  Gerätekanal und Wartezeit, vollständige Vorbelegung sowie Aufruf im ausgewählten
  zweiten Z2M-System. Schaltrechte werden weiterhin geprüft. Fehlende Szenen
  werden bei lokaler Prüfung, Aktivierung und Ausführung verständlich gemeldet;
  entfernte Szenen prüft das Zielsystem. Keine Szenenerstellung oder -änderung.

- Automatischer Wiederanlauf nach Z2M-Neustart oder Erweiterungsneuladen für den
  zuvor ausdrücklich aktivierten Regelstand. Formularoption standardmäßig an;
  Übertragung beim Aktivieren. Bestätigtes Stoppen bleibt dauerhaft wirksam.
  Eigener Status und deutsche Debug-Ausgaben; keine Fortsetzung alter Folgen oder
  Nachholung verpasster Zeiten. Speicher- und Prüfungsfehler werden sichtbar gemeldet.

- Aktionen können Geräte und Gruppen eines ausdrücklich ausgewählten zweiten
  Z2M-Systems schalten. Klick-Editor, Vorbelegung und Ereignisimport erhalten
  systemgebundene Ziele und prüfen weiterhin native Symcon-Schaltrechte.
- Eigener Mesh-Transport über eine vorhandene MQTT-Bridge mit kurzlebigen
  Einmalfreigaben, Rückmeldung der Befehlsübergabe und Schutz gegen doppelte
  oder alte Ausführung. Beide Seiten benötigen gespeicherte, ausdrücklich
  aktivierte Freigaben. Stopp sperrt auch eingehende Aktionen.
- Zusätzliche Statusvariable für freigegebene eingehende Aktionen und
  verständliche Debug-/Fehlermeldungen. Noch keine Installation dieses Stands.

## Fixes

- Die Übernahmevorschau kann auch die aufrufende Instanz prüfen, ohne sie
  fälschlich als nicht erreichbar zu melden. Entwürfe und Besitzer bleiben
  unverändert; Zugriffssperren anderer Aufrufe gelten weiterhin. Deutsche
  Debugmeldungen erklären die Prüfung. Ein Modulupdate genügt.

- Gespeicherte Automationen bleiben nach dem Laden unverändert und lassen sich
  ohne erneutes Speichern einmalig ausführen. Bei unveränderten Partnerfreigaben
  behält das Modul den ursprünglichen YAML-Text samt Prüfsumme bei.

- Eine laufende Z2M-Ablaufaufzeichnung bleibt beim Speichern von Automationen
  erhalten und endet weiterhin zur ursprünglich gewählten Zeit. Während der
  anschließenden Statusbestätigung lässt sie sich beenden. Verbindungs- und
  Sitzungswechsel beenden die lokale Aufzeichnung weiterhin zuverlässig.

- Nach bestätigter Speicherung fragt das Modul automatisch den vollständigen
  Z2M-Laufzeitstatus ab. Erst eine frische passende Antwort gibt die Aktivierung
  frei; fehlende oder abweichende Bestätigung führt nicht zu erneuter Speicherung.
  Bereits gespeicherte unveränderte Entwürfe werden als gespeichert angezeigt,
  statt als „noch nicht geprüft“. Deutsche Status- und Debug-Meldungen ergänzt.

- Detaillierte Prüfungsfehler bleiben bei automatischen und manuellen
  Statusabfragen erhalten, bis die nächste Entwurfsprüfung beginnt. Fehler eines
  anderen Vorgangs werden zusätzlich angezeigt. Debug erklärt Erhalt und Ablösung
  der Diagnose; keine Änderung an der Z2M-Erweiterung.

- Abgelehnte Z2M-Prüfungen nennen die betroffene Automation mit Nummer und bei
  Klick-Editor-Entwürfen mit Namen, Auslöser/Bedingung/Aktionsschritt und konkreter
  Ursache. Syntaxfehler nennen nach Möglichkeit Zeile und Spalte. Mehrere Fehler
  werden gemeinsam angezeigt, bis zu 20 pro Prüfung. Debug bleibt ohne Namen,
  YAML-Inhalte oder Schaltwerte; alte Erweiterungen behalten die sichere Kurzmeldung.

- Die rote Topic-Warnung nach einem Gateway-Wechsel entfällt: Bei geändertem
  Gateway, Basistopic oder Topic-Angebot wird das native Formular neu aufgebaut.
  Statusmeldungen bei unverändertem Kontext laden das Formular nicht neu.
  Gespeicherte Einstellungen und YAML-Entwürfe bleiben erhalten; noch nicht
  übernommene Formulareingaben vor einem Gateway-Wechsel übernehmen.

- Die Topic-Auswahl erhält keinen direkt gesetzten oder JSON-codierten
  Auswahlwert mehr. Die native Mehrfachauswahl ermittelt ihren Zustand aus
  dem gebundenen Basistopic; JSON-Text wird nicht als auswählbares Topic behandelt.

- Die Basistopic-Auswahl enthält keinen ungültigen leeren Mehrfachwert mehr.
  Gespeicherte und manuell eingegebene Topics bleiben gültig darstellbar, auch
  wenn sie am aktuellen Gateway nicht gefunden werden. Ein Gateway-Wechsel
  aktualisiert Auswahl und Hinweis sofort, ohne Entwürfe oder Basistopic zu
  überschreiben. Automatische Prüfungen warten bei erkennbar unpassendem Topic.

- Zwei Z2M-Systeme mit unterschiedlichen Basistopics dürfen denselben
  Symcon-MQTT-Client verwenden. Partnerwahl, Gerätezuordnung und Ereignisimport
  prüfen Verbindung und Topic gemeinsam; lokale und fremde Ziele bleiben auch
  bei identischen IEEE-Adressen oder Gruppen-IDs getrennt. Bei einem gemeinsamen
  Broker ist keine Bridge nötig. Formularhilfe und Debug-Ausgaben erklären dies.

- Gerätedaten für den Ereignisimport lassen sich nachladen, ohne vorhandene
  Entwürfe zu ersetzen. Abweichende gespeicherte Z2M-Stände und unübernommene
  YAML-Änderungen werden erkannt; es erfolgt kein stilles Überschreiben.
- Der begrenzte Gerätekatalog berücksichtigt nach Ein-/Aus-Kanälen zuerst
  Tasterereignisse und Auf-/Zu-/Stopp-Befehle. Umfangreiche Sonderoptionen
  anderer Geräte verdrängen diese Grundfunktionen nicht mehr.

- Die Importprüfung unterscheidet nicht unterstützte Variablen, fehlende
  Gerätedaten, andere MQTT-Verbindungen und fehlende Schaltrechte. Liste und
  Vorschau nennen die tatsächlich geprüfte Variable. Ein passendes Gateway wird
  nicht mehr fälschlich als Zuordnungsproblem dargestellt.
- Namenlose Symcon-Ereignisse erhalten beim Import einen bearbeitbaren Namen.
  Das ursprüngliche Ereignis wird nicht umbenannt; neue Regeln bleiben deaktiviert.

- Deutsche Status-, Fehler- und Hilfetexte für Einrichtung, Bearbeitung,
  Prüfung, Speicherung, Ausführung und Ereignisimport verständlicher formuliert.
  Meldungen nennen Ergebnis und nächsten Schritt; längere Hinweise nutzen die
  verfügbare Formularbreite. Technische Kennungen und Abläufe bleiben erhalten.

- Die Importprüfung nennt zuerst „Importierbar“, „Nicht importierbar“ oder
  „Noch nicht prüfbar“ und erklärt anschließend den Grund bzw. nächsten Schritt.

- Die Liste „Geprüfte Ereignisse“ zeigt 15 statt 5 Zeilen gleichzeitig,
  damit sich größere Ereignisbestände übersichtlicher durchsuchen lassen.

- Der Import löst native Ereignisziele aus dem Elternobjekt auf und unterstützt
  Instanzaktionen mit einer über IDENT ausgewählten Statusvariable und eingebetteter
  nativer Aktion. Ein gespeichertes TARGET ist dafür nicht erforderlich.
- Auslöser bei einem bestimmten Wert werden mit und ohne wiederholte Ausführung
  übernommen. „Nur bei Wertänderung“ bleibt im Klick-Editor bearbeitbar und wird
  in Z2M ausgeführt; unveränderte Meldungen werden nachvollziehbar verworfen.

- Die Ereignisübersicht und Auswahl zeigen das auslösende Gerät mit Namen und
  Symcon-Instanz-ID. Fehlende Gerätedaten werden mit verständlicher Handlungsanweisung
  erklärt. Ein direkter Ladebutton aktualisiert nach Bestätigung Übersicht und
  ausgewählte Vorschau; Fehler bleiben sichtbar und erteilen keine Importfreigabe.

- Die Ereignissuche prüft fremde Variablen und Elternobjekte vor Instanzzugriffen;
  Kategorien und fremde Module erzeugen keine ungültigen Instanz-/Property-Abfragen.
- Geräte, Gruppen und Ereignisse werden nach dem eigenen MQTT-Gateway und dem
  gewählten Basistopic getrennt. Zwei Server verwenden getrennte Automationsmodule.
  Erkannte Topics am eigenen Gateway lassen sich in den MQTT-Einstellungen auswählen.
  Die Ereignissuche benötigt keinen geladenen Z2M-Katalog; die Importprüfung schon.

- Unterstützte ausgelöste Symcon-Ereignisse lassen sich mit Prüfung und Vorschau
  als deaktivierte Regeln in den Klick-Editor importieren. Originalereignisse
  bleiben unverändert; geänderte Vorschauen und doppelte Imports werden abgewiesen.
- Gruppen stehen als schaltbare Aktionsziele bereit. Umschaltaktionen verwenden
  den aktuellen Z2M-Zustand und melden unbekannte Zustände verständlich als Fehler.

- Modulupdate, Formularöffnung und MQTT-Wiederverbindung lösen eine verzögerte
  einmalige Prüfung von Zigbee2MQTT und Erweiterungsstatus aus. Laufende Aufträge
  haben Vorrang; deutsche Debug-Ausgaben erklären Ablauf und Fehler.

- Listen in den Automationsformularen verwenden eine flexible Hauptspalte
  und kompakte Zeitspalten. Prozentbreiten der Spalten entfallen, damit
  Beschriftungen und Bedienknöpfe in der sichtbaren Formularbreite bleiben.

- Geräteauslöser im Klick-Editor unterstützen Vergleichsoperatoren für
  Messwerte sowie Gleichheit/Ungleichheit für Zustände und Ereignisse.
- Geräte- und Zeitautomationen bieten eine Liste mehrerer UND-verknüpfter
  Bedingungen mit vollständiger Vorbelegung, Bearbeitung und Löschung.
  Das optionale Zeitfenster gilt zusätzlich; verständliche Debug-Ausgaben
  begleiten Auswahl, Prüfung und Übernahme.

- Die Kodierungsprüfung verwendet jetzt die gemeinsame Implementierung aus
  Symcon_ModuleCI. Lokal ist dessen Checkout erforderlich; Fremdquellen bleiben
  ausgeschlossen.

- Projektprüfungen erkennen beschädigte Umlaute und ungültiges UTF-8 frühzeitig.
  Eingebundene Fremdquellen bleiben von dieser Prüfung ausgenommen.

- Einzelne Schaltkanäle von Mehrkanalgeräten erhalten im begrenzten Katalog
  denselben Vorrang wie der allgemeine Zustand. Sonderoptionen verdrängen sie
  dadurch nicht mehr. Auswahl zeigt beispielsweise „Zustand (Kanal L3)“;
  Kanalzustände werden im bereinigten Katalog-Debug gezählt.

- Gespeicherte lokale Regeln lassen sich nach einem Library-Neuladen auch ohne
  erneute Empfängerbestätigung zur Ansicht auswählen. Name, Uhrzeit, Bedingungen
  und Aktionen werden vorbelegt; Änderungen und Übertragung behalten die
  Empfängerprüfung. Interne Editor-Sichtbarkeit wird nicht mehr irrtümlich
  an den sichtbaren Bereich weitergereicht.

- Bearbeitungsüberschriften bleiben für Geräte- und Zeitautomationen sichtbar.
  Aufklappen aktiviert den passenden Editor; erneutes Auf-/Zuklappen im aktiven
  Bereich setzt keine Eingaben zurück. Redundante Neu-Buttons entfernt; neue und
  vorhandene Regeln werden ausschließlich über die Auswahl bestimmt.

- Zusätzlichen YAML-Anzeigeschalter entfernt. Die YAML ist direkt im aufklappbaren
  Expertenbereich verfügbar. Prüfung und Speicherung verwenden den Editorinhalt;
  manuelle Änderungen bleiben beim grafischen Bearbeiten geschützt.

- Grundlegende Ein-/Aus-Auswahlwerte werden im begrenzten Gerätekatalog vor
  Sonderoptionen berücksichtigt. Viele Optionen früher Geräte verdrängen dadurch
  nicht mehr die normalen Schaltaktionen späterer Geräte. Erweiterung aktualisieren
  und Geräte neu laden; die bisherigen Größenlimits bleiben erhalten.

- Debug beim Löschen eines Aktionsschritts kennzeichnet die vom nativen Formular
  übergebene Liste als Vorbestand. Gelöschte Schritte erscheinen nicht mehr als
  vermeintlich verbleibende Folge; diese wird beim Übernehmen geprüft.

## Neu

- Gespeicherte Automationen einmalig ausführen und einzelne laufende Abläufe
  stoppen. Bedingungen gelten vor dem Start und vor jeder Aktion; Status und
  deutsche Debugmeldungen erklären Ergebnis und Abbruch. Auch deaktivierte
  Regeln, Szenen und freigegebene Partneraktionen sind möglich. Automatikbetrieb,
  andere Regeln und Wiederanlauf bleiben unverändert. Modul und Erweiterung
  gemeinsam aktualisieren; noch nicht auf einer Symcon-Installation geprüft.

- Gerätebereich um eine Auslöserliste mit ein bis acht Geräteereignissen ergänzt.
  Jeder Auslöser startet dieselbe Aktionsfolge (ODER), mit gemeinsamer Bedingung
  und optionaler Mindestdauer je Eintrag. Bearbeiten/Löschen und Wiederladen
  unterstützen Mehrfachregeln; alte Einzelaufrufe dürfen sie nicht verkürzen.

- Bearbeitung direkt in Geräte- und Zeitbereiche integriert: eigene Eingaben,
  Bedingungen, Aktionsfolgen und Übernehmen-/Löschen-Buttons. Die Auslöserart
  ist durch den Bereich festgelegt. Prüfung, Speicherung und Ausführungssteuerung
  bleiben gemeinsam. Veraltete bereichsfremde Formularaufrufe werden mit einer
  verständlichen deutschen Debug-Meldung abgewiesen.

- Natives Formular in geräteausgelöste und zeitgesteuerte Automationen aufgeteilt,
  jeweils mit eigener Übersicht, Regelauswahl und Neu-Button. Gemeinsame Bearbeitung
  führt durch Auslöser, optionale Bedingungen, Aktionen und Entwurfsübernahme.
  Einrichtung und YAML sind eingeklappt; Status und Stopp bleiben erreichbar.
  Deutsche Debug-Ausgaben erklären Bereichsauswahl und Ablehnungen.

- Klick-Editor um Aktionsfolgen mit bis zu 16 Schritten erweitert. Schritte lassen
  sich hinzufügen, bearbeiten, löschen und durch Ziehen umsortieren. Jeder Schritt
  besitzt eine Wartezeit; gespeicherte Folgen werden vollständig wieder geladen.
  Deutsche Debug-Ausgaben erklären Änderungen, Schrittreihenfolge und Wartezeiten.

- Klick-Editor um tägliche Uhrzeit-Auslöser, Zeitzonen, Zeitfenster mit Wochentagen
  und eine zusätzliche Zustands- oder Zahlenbedingung erweitert. Mindestdauer und
  Aktionsverzögerung sind auswählbar; Regeln werden zum Bearbeiten wieder vorbelegt.
  Der begrenzte Z2M-Katalog liefert lesbare Messgrößen und Einheiten. Neue Abläufe
  verwenden bereinigte deutsche Debug-Ausgaben.

- Klick-Editor um Auswahl, Bearbeitung und bestätigtes Löschen bestehender Regeln
  erweitert. Einfache Regeln behalten ihre ID; andere Regeln bleiben unverändert.
  Name und Aktivierung erweiterter Regeln können ohne Verlust ihrer Schritte
  geändert werden. Veraltete Formularauswahlen werden abgewiesen.

- Dynamische Geräte-/Aktionsauswahl und Entwurfsliste werden für die native
  Konsole JSON-codiert aktualisiert. Regressionstest prüft diesen SDK-Vertrag.
- Set-Aktionen werden direkt an Z2Ms native Befehlsverarbeitung übergeben.
  Ihre Ausführung benötigt keine MQTT-Rückzustellung eigener Veröffentlichungen.

- Aktivierungs- und Stoppbuttons mit frischer Statusbestätigung, aktiver Revision,
  Regelanzahl und verständlichen Ausführungsfehlern im Symcon-Modul ergänzt.
- Erster Klick-Editor lädt Geräte aus Z2M und erstellt einfache Regeln durch
  Auswahl von Quelle, Ereignis, Ziel und Aktion. Bestehende Regeln bleiben erhalten;
  YAML ist nur noch eine zusätzliche erweiterte Ansicht.
- Debug-Abläufe verwenden den zentralen DebugFlowHelper. Tests belegen die
  Z2M-Ausführung auch bei getrenntem Symcon-Datenfluss.

- Eigene Ausführungsengine für gespeicherte YAML mit Geräte-/Zeittriggern,
  Bedingungen, Mindestdauer und geordneten verzögerten Set-Aktionen ergänzt.
- Ausführung wird getrennt per MQTT aktiviert und gestoppt. Aktivierung prüft
  Sitzung, Revision, Prüfsumme und eine frische Einmalkennung. Neustart oder
  geänderte Speicherung starten keine Regeln automatisch.

- MQTT-Empfangsfilter auf vollständige JSON-Umschläge angepasst; der Filter
  für deaktivierte oder ungültige Konfigurationen benötigt keine Lookarounds.

- Durchgängiges deutsches Moduldebug für Aufrufe, MQTT-Datenfluss, Status und
  Fehler ergänzt; sensible Nutzdaten bleiben ausgeschlossen.
- Passende frische Health-Antworten bestätigen jetzt auch ohne vorherigen
  Empfang von bridge/state die Z2M-Erreichbarkeit.

- Die eigene Z2M-Erweiterung lässt sich jetzt aus dem Modul installieren und
  aktualisieren. Der Empfänger wird nach dem Upload ausdrücklich geprüft.
- YAML kann im Formular eingegeben, in Z2M geprüft und nach Vorschau und
  Bestätigung gespeichert werden. Sitzung, Revision und Prüfsumme verhindern
  die Übertragung eines geänderten oder veralteten Entwurfs.
- Erweiterungsstatus, Speicherrevision und verständliche Fehler werden im
  Formular und in zusätzlichen lesbaren Statusvariablen angezeigt.
- Eigene Zigbee2MQTT-Erweiterung mit versioniertem YAML-Schema, Validierung und
  persistentem Speicher ergänzt. Ungültige Übertragungen ersetzen keine gültige
  Konfiguration; die vorherige Fassung wird gesichert. Noch keine Ausführung.
- Vorhandene Symcon-MQTT-Client- und MQTT-Server-Verbindungen können für den
  Empfang von Zigbee2MQTT-Status, Version und Erweiterungsliste genutzt werden.
- Manuelle Verbindungsprüfung mit zugeordneter Antwort, Zeitlimit und lesbaren
  Statusvariablen ergänzt. Die Anbindung ist standardmäßig deaktiviert.
- Grundgerüst für Z2MAutomations mit einem inaktiven Verwaltungsmodul angelegt.
