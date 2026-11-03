# Projektkontext: Z2MAutomations

## Ziel

Automationen komfortabel in Symcon erstellen und verwalten. Eine vollständig
eigene externe Zigbee2MQTT-Erweiterung empfängt die erzeugte YAML, speichert
sie persistent und führt sie unabhängig von Symcon aus.

## Stand

- Agenda Punkt 3 lokal implementiert (ADR 0017): Klick-Editor mit einer bis acht
  verschiedenen Uhrzeiten in gemeinsamer Zeitzone. Bestehende einzelne und
  mehrere time-Auslöser werden vollständig vorbelegt. Unabhängige Auswahl von
  Wochentagen für Geräte-, Zeit- und Astro-Regeln; neue Schema-1-Bedingung weekday
  prüft den aktuellen Tag einer expliziten Zeitzone, auch vor verzögerten Aktionen
  und beim Einzelstart. Bisherige Nachtfenster behalten ihre Starttagssemantik.
  Eingabeschutz, Aktionsrechte, 8-Auslöser-/16-Bedingungsgrenzen und Metadaten-
  Bearbeitung nicht vollständig darstellbarer Regeln bleiben erhalten.
  Deutsche bereinigte Debugmeldungen, Z2M-Diagnoseereignis über bestehenden Kanal.
  Modul und generiertes Erweiterungsartefakt gemeinsam aktualisieren. Keine
  Test-/Produktivinstallation in der lokalen Implementierungsphase verändert.
  Anschließend am 02.11.2026 per Test-Symcon geprüft, nach ausdrücklicher Freigabe
  am gemeinsam genutzten Z2M: neuen Erweiterungsstand bestätigt; fehlende native
  Listenoptionen add/delete korrigiert und nur das Testformular nachgezogen.
  Zwei Uhrzeiten und unabhängige Tage nativ erstellt, deaktiviert geprüft und
  gespeichert; geänderte Zeiten, Tage und getrennte Zeitzonen erneut gespeichert
  und korrekt vorbelegt. Bearbeitung der gespeicherten Regel über öffentliche
  Modulmethoden, sichtbare Vorbelegung zusätzlich in der nativen Konsole bestätigt.
  Doppelte Zeiten und leere Tage lokal abgewiesen; Z2M benennt bei leerer Tagesliste
  die genaue Bedingung. Testregel entfernt, ursprünglicher gespeicherter Inhalt
  bis auf fortlaufende Revision bytegleich wiederhergestellt. Bisherige aktive
  Regel, Partnerfreigabe und Wiederanlauf bestätigt. Originaler Symcon-Entwurf
  bytegleich; Prüfkopie und eigene Aufzeichnung bereinigt. Keine Testausführung
  oder Schaltbefehle. Nachweise und Grenzen stehen in docs/DEVELOPMENT.md.

- Agenda Punkt 2 implementiert: zehn sichtbare Einträge je Regelbereich,
  ausgerichtete Spalten mit genau einer flexiblen Auslöserspalte, Suche einschließlich referenzierter
  Geräte und kombinierter Filter des Regelschalters. Übersicht und Auswahlliste
  werden gemeinsam eingegrenzt; aktuelle Bearbeitung bleibt bei abweichendem
  Filter gekennzeichnet verfügbar. Suchtexte bleiben im flüchtigen, an den
  MQTT-Kontext gebundenen Zustand und erscheinen nicht im Debug. Eigene Kopier-
  Aktion mit neuer ID, unterscheidbarem UTF-8-Namen, ausgeschaltetem Regelschalter
  und unmittelbarer Vorbelegung. Ablauf und gemeinsame Folgenverweise bleiben
  erhalten; bestehende Regeln und Z2M-Betrieb unverändert. Offene Eingaben,
  veraltete Aufrufe und die bestehende Grenze von 100 Regeln werden geprüft.
  Synthetische native Skripte mit 50 Regeln und Grenztest mit 100 Regeln.
  Native Testkonsole am 02.11.2026: Suche, kombinierter Filter, Erhalt offener
  Eingaben, Kopiersperre sowie Geräte- und Zeitkopie erfolgreich geprüft.
  Mehrere auto-Spalten verursachten verschobene Zeilen; auf eine auto-Spalte
  und begrenzte Pixelspalten korrigiert und erneut visuell geprüft. Nur dieses
  Formular in Test-Symcon nachgezogen. Prüfkopie und Debugdateiaufzeichnung
  entfernt bzw. beendet; Originalentwurf bytegleich und Z2M-Betrieb unverändert.
  Erweiterung unverändert. Details und Grenzen in docs/DEVELOPMENT.md.

- Bildschirmbedienung auf Wunsch des Anwenders vor jedem Zugriff ausdrücklich
  ankündigen. Lokale Entwicklung und Offline-Prüfungen benötigen keine Bedienung
  seiner Konsole. Praxisprüfungen möglichst bündeln.

- Agenda Punkt 1 am 02.11.2026 in der nativen Testkonsole erfolgreich geprüft.
  Korrigierte Regelauswahl mit Vorbelegung; offene Änderungen bleiben nach
  Statusabfrage, Einklappen und erneutem Formularöffnen erhalten. Weiterbearbeiten
  erhält Eingabe und Auswahl. Ausdrückliches Verwerfen wechselt korrekt zur
  Zeitregel und verändert keinen Entwurf. Prüfung mit offenen Eingaben zeigt
  einen Hinweis und sendet keine Validierungsanfrage. Lokale Übernahme nur in
  der Prüfkopie bestätigt; danach keine offene Bearbeitung und unveränderte
  übrige Regelangaben. Popup-Felder wurden bisher beim dynamischen Aufbau
  ausgelassen, weshalb der Verwerf-Button nur seinen Platzhalter verwendete.
  Traversierung und Wiederherstellung des Bestätigungswerts korrigiert;
  zusätzliche deutsche Ablehnungsgründe ohne Eingaben oder Tokenwerte.
  Tatsächliches Popup-Skript regressionsgeprüft und korrigierter Modulstand
  auf Test-Symcon bestätigt. Prüfkopie entfernt, eigene Aufzeichnungen beendet;
  ursprünglicher lokaler Entwurf bytegleich und gespeicherter/aktiver Z2M-Stand
  einschließlich Wiederanlauf unverändert. Keine Geräteaktion oder Speicherung
  in Z2M. Alle Offline-Prüfungen einschließlich 106 JavaScript-Tests erfolgreich.

- Native Praxisprüfung von Agenda Punkt 1 am 02.11.2026: aktualisierten Modulstand
  dateigenau auf Test-Symcon bestätigt. Alte offene Konsole zeigte noch den alten
  Aufbau; frische Prüfkopie zeigte die neuen Statusanzeigen. Regelauswahl scheiterte
  vor dem Moduleinstieg an der im separaten Formularskript nicht verfügbaren
  EditorWorkInput-Klasse. Lokal korrigiert: eigenständige Erfassung mit PHP-
  Grundfunktionen, anschließende Normalisierung ausschließlich im Modul.
  Regression im separaten PHP-Prozess zuerst mit demselben Fehler, danach grün.
  Korrigierter Stand benötigt ein weiteres Modulupdate und erneute native Prüfung.
  Prüfkopie entfernt; ursprünglicher lokaler Entwurf bytegleich, gespeicherter
  und aktiver Z2M-Stand samt Wiederanlauf unverändert. Keine Geräteaktion,
  Speicherung, Aktivierung, Erweiterungsinstallation oder Produktiv-MCP-Änderung.

- Neue verbindliche Reihenfolge: [Entwicklungsagenda](docs/AGENDA.md).
  Punkt 1 lokal umgesetzt: getrennte Angaben zu noch nicht übernommenen
  Formulareingaben, lokalem/geprüftem/gespeichertem Entwurf und bestätigtem
  Z2M-Betrieb. Native Regel-/Bereichs-/Folgenwechsel fragen vor dem Verwerfen;
  Prüfung, Übertragung und Aktivierung verlangen die vorherige Übernahme.
  Statusabfragen und Stopp bleiben verfügbar. Kontext- und Auswahlbindungen
  verhindern veraltete Bestätigungen. Synthetische native Callback-Prüfungen;
  Praxisprüfung in der nativen Konsole steht aus. Kein Test- oder Live-System
  verändert; ein Modulupdate genügt, Erweiterung unverändert.

- Manuelle Verbindungsprüfung lokal um ein natives Ergebnis-Popup ergänzt:
  eigener öffentlicher CheckConnection-Einstieg, gemeinsame Health-Anfrage,
  Bestätigung erst nach passender frischer Antwort, klare Fehler-/Timeout- und
  Abbruchhinweise. Automatische Prüfungen und bestehende CheckHealth-Aufrufe
  bleiben ohne Popup. Wiederholte Klicks teilen eine ausstehende Anfrage.
  Synthetisch einschließlich synchroner Antworten, Abbruch und UI-Fehler geprüft.
  Native Praxisprüfung steht aus; kein Test- oder Live-System verändert.

- Erweiterungsstand lokal ergänzt (ADR 0016): installierte und mitgelieferte
  Version mit automatisch generierter Inhaltskennung unter Einrichtung und
  Diagnose. Vergleich über bestätigte Statusantworten, gleiche Kennung aktuell,
  abweichende Kennung mit Installationshinweis. Ältere Empfänger ohne Kennung
  bleiben lesbar und erhalten eine Erklärung. Automatische und manuelle Abfrage
  sowie Installation verwenden denselben Anzeigepfad. Feste deutsche Debugtexte
  nur bei geänderten Vergleichsergebnissen. Keine Live- oder Testsystemänderung;
  native Praxisprüfung steht aus.

- Wiederverwendbare Aktionsfolgen lokal implementiert (ADR 0015): eigener
  nativer Verwaltungsbereich, Auswahl in Geräte-/Zeitregeln, stabile Verweise,
  Wartezeiten, Vorbelegung und Schutz verwendeter Folgen vor dem Löschen.
  Definitionen und Regeln teilen YAML, Prüfung, Speicherung und Ausführung.
  Nach Auflösung höchstens 16 Schritte pro Regel; keine Verschachtelung.
  Bedingungen, Einzelstart, gezielter Stopp, Szenen, Wiederanlauf und Mesh
  offline geprüft. Debug ergänzt numerische Folge-/Schrittbezüge. Am 02.11.2026
  über Test-Symcon praktisch geprüft: Erstellung, Bearbeitung, Vorbelegung,
  zwei deaktivierte Verwender, Löschsperre, Prüfung, Speicherung und Wiederladen.
  Freigegebene Gerätefolge Ein, fünf Sekunden warten, Aus erfolgreich;
  gezielter Stopp vor der ersten Befehlsübergabe bestätigt. Testdaten entfernt,
  ursprünglicher Regelinhalt und Betrieb wiederhergestellt. Anschließend dieselbe
  Gerätefolge auch systemübergreifend real geprüft: zwei bestätigte Übergaben am
  Ziel, gleiche Lauf-, Folgen-, Schritt- und Quellrevisionsbezüge auf beiden Seiten.
  Testdaten entfernt; ursprüngliche Quellregel mit neuer Revision wiederhergestellt,
  gespeicherter Zielstand und lokale Entwürfe unverändert.
  Parallel- und Neustartprüfung anschließend ebenfalls praktisch bestätigt:
  zwei überlappende Verwender, Stopp eines Laufs ohne Einfluss auf den anderen;
  nach Z2M-Neustart automatischer Betrieb wieder bereit und wartender Einzelablauf
  verworfen. Testdaten entfernt, ursprünglicher Inhalt mit neuer Revision
  wiederhergestellt; lokale Entwürfe und Ereigniszustände erhalten.

- Reale systemübergreifende MQTT-Debugprüfung am 02.11.2026 abgeschlossen:
  zwei vom Benutzer ausgelöste Tasterbetätigungen mit jeweils eigenem Lauf,
  passendem Schritt und Quellrevision auf beiden Systemen erfasst. Ziel meldete
  je eine native Befehlsübergabe; Benutzer bestätigte die Lichtwirkung. Danach
  ausschließlich eigene Aufzeichnungen beendet. Dauerhaft aktive Regel und
  deaktiviertes Originalereignis sowie gespeicherte Stände unverändert.

- Einzelne gespeicherte Regeln einmal ausführen und konkrete Läufe stoppen:
  eigener gemeinsamer Formularbereich, Bedingungen vor Start und Aktionen,
  native deutsche Debugmeldungen, Status mit Schrittposition, begrenzte
  MQTT-Laufzustände und unveränderte globale Aktivierung/Wiederanlaufabsicht.
  Auch deaktivierte Regeln und erlaubte Partneraktionen möglich. Keine
  Schemaänderung; lokale Umsetzung gemäß ADR 0014. Neue Offline-Nachweise
  für Laufzeit, Mesh, Statusvertrag und native PHP-/JS-Formularskripte.
  Anschließend über Test-Symcon am ausdrücklich freigegebenen gemeinsamen
  Z2M geprüft: Bedingung verhindert Start, erfüllte Bedingung startet Wartezeit,
  gezielter Stopp und automatische Statusabfragen bestätigt. Keine Testaktion
  an Geräte übergeben; Testregeln entfernt, ursprüngliche Regeln und Betrieb
  wiederhergestellt, lokaler Entwurf bytegleich. Beim Laden aufgedeckte reine
  YAML-Neuformatierung nach gesonderter Freigabe korrigiert und regressionsgeprüft.

- Rücksetzung der Ablaufaufzeichnung beim Speichern lokal korrigiert:
  vorhandene Aufzeichnung bleibt an MQTT-Kontext und Z2M-Sitzung gebunden,
  unabhängig von ausstehender Bestätigung des gespeicherten Regelstands.
  Client und Endzeit bleiben erhalten; eigener Stopp auch während der Wartephase,
  neuer Start erst nach Bestätigung. Echte PHP-/JS-Regressionsprüfung sowie
  Zeitablauf, Trennung und Sitzungswechsel geprüft. Korrigierter Stand in der
  Testinstallation dateigenau bestätigt und mit verzögerter echter Z2M-Antwort
  praktisch geprüft: ursprüngliche Endzeit, Formularstatus und eigener Stopp
  während der Statusprüfung korrekt. YAML, Revision, Betrieb und vorhandener
  Entwurf unverändert; keine Schaltbefehle oder Erweiterungsänderungen.

- Sonnenauf-/untergang mit Zeitversatz lokal ergänzt: native Auswahl im
  Zeitbereich, expliziter Standort, Vorbelegung, Bearbeitung, Prüfung und
  Speicherung. Eigene tägliche Berechnung in Z2M ohne Symcon oder Internetdienst.
  Polartage, Mitternacht, Rücksprung, verpasste Zeiten und Wiederanlauf geprüft;
  vorhandener Aktions-/Mesh-Pfad und bereinigte Debugmeldungen. Astro-Stand in
  Test-Symcon und gemeinsam genutztem Z2M dateigenau bestätigt; native Erstellung,
  Bearbeitung, Vorbelegung, Ablehnung ungültiger Koordinaten, Z2M-Prüfung,
  Speicherung und Wiederladen mit ausgeschalteter Testregel praktisch geprüft.
  Ursprüngliche Regeln und Betrieb wiederhergestellt, Formularentwurf erhalten;
  keine Schaltbefehle bei der Editorprüfung. Anschließend auf eigenen Auftrag
  reale Astro-Ausführung mit negativem Versatz und kurzer Geräteaktionsfolge
  bestätigt: ein Lauf, native Ablaufmeldungen, Symcon-Zustandswechsel und
  Leistungsrückmeldung. Testregel entfernt, Regeln und Lichtzustand wiederhergestellt.
  Rücksetzung der lokalen Debugaufzeichnung beim Speichern als Nebenbefund
  dokumentiert und anschließend lokal korrigiert, siehe oben. Siehe ADR 0013
  und DEVELOPMENT.md.

- Eigener zeitlich begrenzter MQTT-Debugkanal lokal implementiert: flüchtige
  unabhängige Clientfreigaben, Formularbedienung, feste deutsche Ablaufmeldungen,
  Regel-/Schritt-/Laufzuordnung über Mesh und unveränderter DebugFlowHelper.
  Begrenzter nicht blockierender Versand, Verlusthinweise, strikter Empfang
  und automatische lokale Beendigung. Testmodul dateigenau bestätigt und beide
  gemeinsam genutzten Z2M-Erweiterungen per Test-Symcon aktualisiert. Echte
  Starts, nativer Timerablauf, Remote-Frist, Stopp, unabhängige Clients und
  native deutsche Debugmeldungen praktisch bestätigt; Entwürfe und bestehender
  Betrieb erhalten, Testmonitor entfernt. Gemeinsamer Laufbezug einer realen
  systemübergreifenden Geräteaktion inzwischen separat bestätigt, siehe oben.
  Details und Testgrenzen in DEVELOPMENT.md; ADR 0012.

- Vorhandene Z2M-Szenen lokal als eigene `scene`-Aktionen integriert: lesender
  Katalog mit Geräteendpoint und Gruppenidentität, benannte Auswahl im Geräte-
  und Zeiteditor, vollständige Vorbelegung, Symcon-Schaltrechte und Partnerziele.
  Lokale Prüfung/Speicherung und ausführendes Z2M erkennen fehlende Szenen.
  Native Szenenübergabe und Mesh verwenden die bestehende Laufzeit einschließlich
  Verzögerungen, Stopp und Wiederanlauf. Deutsche Diagnose ergänzt; das Modul
  legt selbst keine Szenen an oder ändert sie. Auf ausdrücklichen Praxisauftrag
  temporäre Geräteszene angelegt: native Auswahl, Vorbelegung, Z2M-Prüfung und
  Speicherung einer ausgeschalteten Regel bestätigt; kein Szenenaufruf.
  Testregel und Szene entfernt, ursprüngliche Regeln und Laufzeit wiederhergestellt.
  Dabei fehlenden Parameter in allen Aktionsdialog-Callbacks korrigiert und
  gegen Symcons verpflichtende Wrapper-Argumentzahl geprüft. Testmodul aktualisiert;
  Produktiv-Symcon unverändert. Siehe ADR 0011 und docs/DEVELOPMENT.md.

- Reale Szenenausführung anschließend auf ausdrücklichen Auftrag geprüft:
  Zeitregel ruft eine temporäre Geräteszene nach zwei Sekunden Wartezeit auf.
  Z2M bestätigt den Aufruf; frische Geräte- und Symcon-Rückmeldungen bestätigen
  Ein-Zustand und Szenenhelligkeit, Laufzeitfehler leer. Modul-/Bridge-Debug
  geprüft; interne Engine-Debugmeldungen wurden nicht über MQTT geliefert.
  Testregel und Szene entfernt, vorherige Regeln bis auf Revision unverändert,
  Ausführung, Mesh und Wiederanlauf wiederhergestellt. Lichtzustand und
  Protokollstufe zurückgesetzt. Keine Funktionsänderungen, Details in DEVELOPMENT.md.

- Automatische Statusbestätigung nach Speicherung lokal ergänzt: passender
  vollständiger Status bestätigt den gespeicherten Snapshot und gibt den
  Aktivierungsbutton frei. Fehlende/abweichende Bestätigung meldet
  `stored_unconfirmed` ohne erneuten Schreibversuch. Exakt gespeicherte Entwürfe
  werden nicht mehr als „noch nicht geprüft“ angezeigt. Synthetische Negativfälle
  und echter PHP-/JS-Ablauf geprüft; Erweiterung unverändert, noch nicht installiert.

- Löschung der detaillierten Prüfungsfehler durch automatische Statusabfragen
  lokal korrigiert. Prüfungsbericht und letzter Vorgangsfehler sind unabhängig:
  Statusabfragen erhalten den Bericht, zusätzliche Fehler bleiben sichtbar;
  erst die nächste Entwurfsprüfung löst ihn ab. Echten automatischen Timerpfad,
  manuelle Abfragen einschließlich Warte-/Fehlerfall, Formular/Statusvariable
  und deutsche Debug-Ausgaben synthetisch geprüft. Nur Moduländerung, keine
  Erweiterungsänderung und noch keine Installation der Korrektur.

- Detaillierte Z2M-Prüfungsfehler lokal implementiert: betroffene Automation,
  Schritt, Feld und Ursache; Namen aus der geprüften Klick-Editor-Anfrage,
  Syntaxpositionen nach Möglichkeit. Bis zu 20 Fehler mit erkennbarem Überlauf,
  sichere Kurzmeldung bei alten/ungültigen Antworten. Native Debug-Ausgaben
  enthalten nur Position und feste Ursachen. Synthetische 50-Regel-Prüfung,
  PHP-/JS-Datenfluss und Geheimnisschutz ergänzt. Noch nicht installiert oder
  in der nativen Live-Konsole geprüft; keine Live-Änderungen.

- Wiederanlauf lokal implementiert: ResumeAfterRestart ist standardmäßig true
  und wird bei ausdrücklicher Aktivierung übertragen. Z2M persistiert System,
  aktiven Betriebswunsch, Option und exakte Revision/Prüfsumme getrennt von YAML.
  Bestätigter Stopp und geändertes Speichern disarmieren den Wiederanlauf.
  Alte Installationen ohne Freigabe bleiben inaktiv. Status-/Formularmeldungen
  und Debug ergänzt; Tests für echte neue Instanzen, Mesh, Stoppvorrang,
  Bedingungen, verpasste Zeiten und fehlerhafte Speicherung. Noch nicht installiert
  oder nativ praktisch geprüft. Siehe ADR 0010 und RUNTIME.md.

- Verbliebene rote Topic-Warnung beim Gateway-Wechsel korrigiert und nativ auf
  der Testinstallation geprüft: MultiSelect-Optionen werden nicht live ersetzt.
  Bei geändertem Gateway-/Topic-Kontext baut ReloadForm die Auswahl neu auf;
  Routine-Statusmeldungen laden nicht neu. Auswahl und Übernahme funktionieren,
  ursprüngliche Zuordnung wiederhergestellt, YAML bytegleich, Laufzeit gestoppt.
  Noch nicht übernommene Formulareingaben vor dem Gateway-Wechsel übernehmen.

- Native MultiSelect-Optionen besitzen durchgehend dieselbe BaseTopic-Zuweisung;
  kein leerer []-Platzhalter und kein expliziter Auswahlwert. Bei erkanntem
  Topic-Konflikt wartet die automatische Prüfung auf Übernahme. Die früheren
  reinen Live-Updates waren trotz gültiger Inhalte nativ fehlerhaft und wurden
  durch den oben beschriebenen kontextgebundenen Neuaufbau ersetzt.

- Systemübergreifende Aktionen lokal implementiert: pro Modul ein ausdrücklich
  gewählter Partner mit anderem Basistopic; ein gemeinsamer Broker und derselbe
  MQTT-Client sind zulässig. Gateway und Topic werden zusammen geprüft, auch bei
  gleichen Geräte-/Gruppenkennungen. Dessen schaltbare Geräte/Gruppen werden
  aus dem lokalen Partnerkatalog angeboten. Quellen und Bedingungen bleiben lokal.
  Auswahl, Import deaktivierter Regeln, Vorbelegung und Speicherung sind offline
  geprüft. `mesh_peers` und `target.system` erweitern Schema 1 additiv.
  Zwei aktivierte Z2M-Engines kommunizieren über `z2ma/mesh/#` mit kurzlebiger,
  aktionsgebundener Einmalfreigabe und Bestätigung der nativen Übergabe.
  Stopp sperrt auch Empfang; keine Wiederholung und kein Start nach Neustart.
  Neue Statusvariable und sichere deutsche Debug-Ausgaben; siehe ADR 0009.
  Dieser Stand ist noch nicht installiert oder real geschaltet. Vor der späteren
  Praxisprüfung benötigen auch die Z2M-Benutzer Mesh-Rechte; die Bridge-Rechte
  allein genügen nicht. Keine Live-Konfiguration durch diese Implementierung.

- Reale Ereignisstrukturen auf ausdrücklich nur lesend freigegebener Produktion
  geprüft. Zwei Implementierungsfehler lokal korrigiert: natives Aktionsziel aus
  Event-Elternobjekt statt zwingendem TARGET; Statusvariable-schalten-Wrapper
  mit IDENT und verschachtelter JSON-Aktion. Keine produktiven Änderungen.
  Auslöser mit TriggerSubsequentExecution=false werden als `on_change: true`
  importiert. Der Klick-Editor erhält „Nur bei Wertänderung“ mit Vorbelegung und
  verlustfreier Übernahme. Schema/Runtime unterstützen den Vergleich gegen den
  Z2M-Ausgangszustand bzw. den letzten frischen Bericht; Diagnose erklärt
  unveränderte Meldungen. Quellen und Tests bleiben vollständig synthetisch.
  Neuer Modul-/Erweiterungsstand noch nicht installiert oder live importiert.

- Importformular verbessert: Ereignisse nennen das auslösende Gerät und auf
  ausdrücklichen Anwenderwunsch seine Symcon-Instanz-ID (auch in der Auswahl).
  Fehlende Z2M-Daten verweisen auf einen direkten, bestätigten Ladebutton.
  `LoadImportDevices` nutzt den bestehenden Editor-Workflow; dessen Abschluss
  aktualisiert Suche und ausgewählte Vorschau automatisch. Fehler werden
  kontextgebunden angezeigt, ohne Importfreigabe; lokaler Entwurf wird erst
  durch den bestätigten Ladevorgang wie beim regulären Editor-Laden ersetzt.
  Verzögerte Antworten, Timeout, Retry und vollständige Übernahme offline geprüft.
  Neuer Stand noch nicht in der Testinstallation geprüft.

- Ereignisimport korrigiert: fremde Variablen/Eltern werden vor Instanz- und
  Konfigurationszugriffen klassifiziert. Kein konkreter Instanz-ID-Bezug im Code.
  Native Z2M-Instanzen werden über Modultyp, eigene MQTT-ConnectionID und
  Basistopic entdeckt. Zwei MQTT-Server bleiben zwei Automationsinstanzen.
  Lokale Topic-Vorschläge befüllen das bestehende BaseTopic-Feld; manuelle Eingabe
  bleibt möglich. Ereignissuche läuft auch ohne Katalog, Import nur mit passendem
  Katalog. Such-/Vorschaucaches sind an Gateway/Topic gebunden.
  Offline gegen Kategorien, fremde Module und getrennte Gateways geprüft;
  neuer Modulstand noch nicht auf der Testinstallation geprüft.

- Erster Import ausgelöster nativer Symcon-Ereignisse implementiert: lesende Suche,
  Vorschau, erneute Prüfung und deaktivierte lokale Übernahme, ohne Originaländerung.
  Bestimmter Wert mit oder ohne Wiederholung, native boolesche/Textwerte und Umschalten;
  Bedingungen, Begrenzung, Skripte und ungesicherte Zahlenskalierung werden abgewiesen.
  Gruppen werden mit der Editorantwort geladen und sind Aktionsziele mit nativer
  schaltbarer Variable. Neue Schema-1-Aktion `toggle` kehrt den Z2M-Cachezustand um;
  fehlender Zustand führt zu `state_unavailable`. Siehe ADR 0008.
  Neuer Modul- und Erweiterungsstand noch nicht live installiert.

- Messwertvergleiche im Geräteauslöser-Dialog und mehrere UND-verknüpfte
  Gerätebedingungen im Geräte- und Zeiteditor implementiert. Vergleich,
  Schwellenwert, Mindestdauer und sämtliche Bedingungen werden vorbelegt.
  Ein optionales Zeitfenster bleibt separat; insgesamt maximal 16 Bedingungen.
  Offline mit echten Formularskripten und dem JavaScript-Empfänger geprüft,
  einschließlich Persistenz und synthetischer Ausführung bei wahren/falschen
  Bedingungen. Dieser neue Stand ist noch nicht live installiert.

- Mehrkanal-Katalog korrigiert: explizite state_l1/state_l2/state_l3-Exposes werden
  wie allgemeine Zustände priorisiert, ihre Eigenschaften unverändert übernommen.
  Kanalbeschriftungen und sichere Kanalanzahl im Debug ergänzt. Modul und neue
  Erweiterung auf Testinstallation aktualisiert; native Auswahl/Vorbelegung aller
  drei Kanäle und einer Kanalaktion sowie Z2M-Validierung bestätigt. Original-YAML
  unverändert, Revision 18 weiterhin gestoppt; keine Geräte geschaltet.

- Reale ODER-Prüfung: Anwender löste Radar und Mehrkanaltaster physisch aus;
  beide starteten dieselbe Ein-/Warte-/Aus-Folge am Testlicht. Beim Weg zum
  Taster entstand ein zusätzlicher Radar-Lauf, separat im Debug erkennbar.
  Anschließend gestoppt, ursprüngliche Regeln inhaltlich erhalten (Revision 18),
  Testlicht aus, Laufzeitfehler leer. Der Einzelkanal des Mehrkanaltasters fehlte
  beim damaligen Test im Klick-Katalog; die oben beschriebene Korrektur behebt dies.
  Siehe docs/DEVELOPMENT.md auch zu zurückgesetzten Diagnoseoptionen.

- Geräteeditor unterstützt ein bis acht ODER-Auslöser mit eigener Mindestdauer.
  Alle unterstützten Einträge werden wieder geladen. Alte Einzelaufrufe können
  Mehrfachregeln nicht verkürzen; gemischte Zeit-/Geräteregeln bleiben geschützt.
  Schema und Z2M-Erweiterung bleiben unverändert. Native List-Skripte, Validierung,
  Persistenz und alternative Ausführung durch zwei synthetische Geräte sind geprüft.
  Testinstallation aktualisiert; native Erstellung/Wiederladen/Bearbeiten/Löschen
  mit Bewegungsmelder und Taster sowie Z2M-Validierung bestätigt. Original-YAML
  wiederhergestellt; keine Live-Speicherung/Aktivierung, Z2M weiterhin gestoppt.

- Auswahl und Vorbelegung eines gültigen lokalen Entwurfs benötigen keine
  erneute Z2M-Bestätigung nach Verlust des flüchtigen Transportcaches. Bekannte
  Empfängerkonflikte, Scope-/YAML-Konflikte sowie jede Änderung/Übertragung bleiben
  geschützt. Live-Feldwerte und sichtbarer Zeitbereich sind im Regressionstest
  geprüft; Korrektur noch nicht auf der Testinstallation installiert.

- Beide Bearbeitungsüberschriften bleiben sichtbar. Öffnen aktiviert den passenden
  Bereich; Auf-/Zuklappen des aktiven Editors erhält ungespeicherte Eingaben.
  Die redundanten Neu-Buttons entfallen; die Auswahl enthält „Neue Automation
  erstellen“ und vorhandene Regeln. Native Panel-onClick-Skripte sind offline
  geprüft; visuelle Live-Abnahme dieses Korrekturstands steht aus.

- Geräte- und Zeitbereiche enthalten jetzt jeweils die vollständige Bearbeitung
  mit eigenen Feldnamen und festgelegter Auslöserart. Eine Auswahl ist jeweils
  aktiv; Wechsel setzen die Bearbeitung wie bisher zurück. Öffentliche Methoden
  besitzen optionale Bereichsparameter für Schutz vor verspäteten Formularaufrufen.
  Gemeinsame Validierung, Übertragung und Aktivierungs-/Stoppsteuerung bleiben
  erhalten. Echte Formularskripte sind offline auf Erstellen/Bearbeiten/Löschen
  beider Typen, Bedingungen, Aktionsfolgen und bereinigtes Debug geprüft. Die
  Live-Installation und visuelle schmale/breite Konsolenabnahme stehen aus.

- Der zusätzliche YAML-Anzeigeschalter entfällt. YAML ist direkt im eingeklappten
  Expertenbereich verfügbar; Formularaktionen berücksichtigen immer dessen Inhalt.
  Der bestehende ShowYamlEditor-Aufruf bedient kompatibel den Panelzustand.

- Native Formularbereiche trennen geräteausgelöste und zeitgesteuerte Regeln.
  Jede Übersicht bietet eine gefilterte Auswahl für neue/vorhandene Regeln; mehrere
  oder andere Auslöser bleiben unter weiteren Automationen erreichbar. Bearbeitung
  ist in Wenn/Nur wenn/Dann/Übernehmen gegliedert, Einrichtung und YAML eingeklappt.
  Dynamische Feldbefüllung berücksichtigt verschachtelte Bereiche. Offline wurden
  die echten Formularskripte, Zuordnung, Persistenz und Debug geprüft;
  Installation und schmale/breite Konsolenabnahme dieses Formularstands stehen aus.

- Entwicklung auf dev; kein Release. Installation und Schaltprüfung nur im
  ausdrücklich freigegebenen Testsystem durchgeführt.
- IPSModuleStrict-Verwaltungsmodul mit optional aktivierter MQTT-Anbindung.
- Native MQTT Client und MQTT Server werden als gemeinsam nutzbare Eltern
  angeboten; Gateway-Einstellungen und Subscriptions bleiben unverändert.
- Konfigurierbares Basistopic, lesbare Statusvariablen, normalisierter Bridge-Cache
  und asynchroner health_check mit Transaktion und Zeitlimit implementiert.
- Standardmäßig deaktiviert; keine automatischen Anfragen oder Schaltaktionen.
- Eigene JavaScript-Erweiterung und YAML-Schema Version 1 implementiert.
- Erweiterungsbefehle validate/store/get/status mit Protokollversion 1,
  Sitzungskennung, Revisionskontrolle, SHA-256 und serialisierter Verarbeitung.
- Persistente YAML mit Vorversion und temporärem, gespültem Schreiben vor rename.
  Beschädigte Hauptdateien führen mit gültiger Sicherung zu recovered_read_only.
- Installation über die offizielle MQTT-API mit anschließender korrelierter
  Empfängerprüfung im Modul integriert.
- YAML-Editor, Prüfung, Vorschau und bestätigte Speicherung aus dem Formular
  implementiert. Vor Store erneute Prüfung von Sitzung und Revision; kein
  blindes Überschreiben, keine automatischen Wiederholungen oder Revisionsänderungen.
- Zusätzliche lesbare Statusvariablen für Empfängerbereitschaft, Speicherrevision,
  Aktionsstatus und Fehler. Eigene Ausführungsengine mit ausdrücklicher
  MQTT-Aktivierung einer gespeicherten Revision implementiert; siehe docs/RUNTIME.md.
  Das Modul integriert Aktivierungs-/Stoppbuttons, Einmalkennung und zusätzliche
  frische Statusbestätigung. Ausführungsstatus, aktive Revision, Regelanzahl und
  Engine-Fehler besitzen eigene lesbare Statusvariablen.
- Erster Klick-Editor für diskrete Geräteereignisse und Set-Aktionen aus Z2M-Exposes.
  Vorhandene Regeln werden beim Ergänzen erhalten; YAML bleibt optional sichtbar.
  Gruppen benötigen weiterhin die erweiterte YAML-Ansicht.
- Native Aktionsliste für bis zu 16 geordnete Geräteaktionen implementiert:
  hinzufügen, bearbeiten, löschen und durch Ziehen umordnen; pro Schritt eine
  Wartezeit vor der Aktion von 0 bis 86400 Sekunden. Gespeicherte Folgen werden
  vollständig wieder geladen. Alte Einzelaktionsaufrufe dürfen sie nicht verkürzen.
  Offline-Integration prüft die echten Formularskripte, Validierung, Persistenz,
  verzögerte Ausführung für zwei synthetische Geräte und bereinigte deutsche
  Debug-Ausgaben. Schema 1 bleibt unverändert. Die Konsolenprüfung
  bestätigt Erstellen, Bearbeiten, Wiederladen und Löschen sowie Prüfung und
  Speicherung. Umsortieren wurde durch den Anwender manuell ausgeführt und durch
  den Agenten einschließlich Persistenz geprüft. Die unmittelbare Löschdiagnose
  kennzeichnet den nativen Vorbestand nun ausdrücklich und listet gelöschte
  Schritte nicht als verbleibende Folge auf. Regressionstest und erneute native
  Löschprüfung (2 auf 1 sowie 1 auf 0 Formularzeilen) bestätigen die Korrektur.
  Ursprüngliche Regeln wurden wiederhergestellt, die Engine blieb gestoppt.
  Eine per Klick erstellte Folge wurde real geprüft: Testlicht einschalten,
  fünf Sekunden warten, ausschalten. Frische Geräte-Rückmeldungen und native
  Z2M-Debug-Ausgaben bestätigten den Ablauf; anschließend gestoppt und bereinigt.
- Der begrenzte Gerätekatalog priorisiert jetzt grundlegende binäre state-Werte
  über alle berücksichtigten Geräte vor deren Sonderoptionen. Ein späteres
  Testlicht war zuvor wegen ausgeschöpften Auswahlbudgets nicht auswählbar.
  Regressionstests und die aktualisierte Testinstallation bestätigen die Korrektur;
  globale Größenlimits bleiben unverändert. Erweiterung aktualisieren und Katalog
  neu laden, um das korrigierte Verhalten zu verwenden.
- Uhrzeit-Auslöser, Zeitfenster mit Wochentagen sowie eine zusätzliche Geräte-
  oder Zahlenbedingung sind grafisch erstell- und bearbeitbar. Mindestdauer und
  Verzögerung werden übernommen. Die Erweiterung liefert dafür lesbare numerische
  Exposes. Dieser Stand wurde offline und in der nativen Testkonsole geprüft:
  Zeitregel mit Nachtfenster, Wochentagen und numerischer Bedingung erstellen,
  speichern, neu laden und bearbeiten; ungültige Uhrzeit wird sicher abgewiesen.
  Die Testregel bleibt deaktiviert, die Engine gestoppt. Keine Schaltprüfung
  der Zeitregel in diesem Bedienprüfungslauf.
- Bearbeiten einfacher Regeln und bestätigtes Löschen beliebiger vorhandener
  Regeln im lokalen Entwurf implementiert. Bei erweiterten Regeln sind Name und
  Aktivierung änderbar, ohne die Bedingungen oder Schritte zu ersetzen. Die neue
  Bedienung ist offline und in der nativen Testkonsole geprüft: Name/Aktivierung
  ändern, speichern, erneut laden sowie Löschen abbrechen/bestätigen und die leere
  Konfiguration speichern. Keine Geräteaktionen in diesem Prüflauf.
- DebugFlowHelper und dessen DebugHelper-Abhängigkeit sind kanonisch eingebunden.
  Die bestehende Fachinstrumentierung delegiert ihre Abläufe an den zentralen Helper.
- Einzeldatei-Artefakt reproduzierbar aus eigenen Quellen und Schema erzeugt.
  Host-Abhängigkeiten js-yaml 5.4.2 und Ajv 8.20.0 lokal mit Lockfile geprüft;
  Referenzvertrag Z2M 2.14.2; einzelner Geräteablauf mit 2.14.2-dev praktisch geprüft.
- StylePHP und SymconStubs sind unveränderte offizielle Git-Submodule.
- DataFlowHelper, ParentConnectionHelper und VariablePresentationHelper sind
  unverändert über das kanonische Bundle-Verfahren manifestiert eingebunden.
  .helper-sync.json deklariert die Consumer-Subscription. Zentrale Freischaltung
  des Consumers und App-Berechtigungen für automatischen Sync sind noch zu prüfen;
  andere Repositories wurden für diese Implementierung nicht geändert.
- Das dynamische Formular verwendet den kanonisch eingebundenen
  ConfigurationFormHelper. Öffnen plant eine verzögerte einmalige Statusprüfung;
  der Formularaufbau selbst sendet kein MQTT. Nach ApplyChanges und Wiederverbindung
  gilt dieselbe Prüfung mit Vorrang laufender Aufträge und zehn Sekunden Abstand.
- GitHub-Identität in Metadaten und Badges: Burki24/Z2MAutomations.
- Lizenz gemäß Projektkonvention: PolyForm Noncommercial License 1.0.0.
- Initiale Metadaten 0.1 / Build 0 / Datum 0 bezeichnen keinen Release.

## Nächste Schritte

1. Punkte 1 und 2 der [Entwicklungsagenda](docs/AGENDA.md) sind im beschriebenen
   Umfang praktisch bestätigt, ebenso Punkt 3 mit den in DEVELOPMENT.md genannten
   Grenzen. Als Nächstes: Punkt 4, Verhalten bei erneuter Auslösung.
2. Weitere Z2M-Versionen und Geräte-/Gruppen-/Zeitkombinationen prüfen;
   einzelne erfolgreiche Abläufe sind keine vollständige Kompatibilitätsabnahme.

## Dokumentation

- [Architektur](docs/ARCHITECTURE.md)
- [ADR 0001](docs/adr/0001-execute-automations-in-z2m.md)
- [ADR 0002](docs/adr/0002-use-native-symcon-mqtt.md)
- [MQTT-Vertrag und Testgrenzen](docs/MQTT.md)
- [YAML-Schema](docs/YAML_SCHEMA.md)
- [Erweiterungsprotokoll und Persistenz](docs/EXTENSION_PROTOCOL.md)
- [ADR 0003](docs/adr/0003-versioned-yaml-storage.md)
- [ADR 0004](docs/adr/0004-correlated-module-workflows.md)
- [Entwicklung und Prüfungen](docs/DEVELOPMENT.md)

Installation, Klick-Editor, YAML-Prüfung und Speicherung sowie Aktivierungs- und
Stoppbuttons wurden in der freigegebenen Testinstallation praktisch bestätigt.
Die gespeicherte Geräteautomation wurde real ausgelöst und ausgeführt; nach
Stopp blieb die erneute Geräteaktion unbeeinflusst. Die Engine übergibt Aktionen
direkt an Z2Ms native Befehlsverarbeitung (ADR 0007). Beim anschließenden
Bearbeiten-/Löschen-Test wurde die Testregel entfernt; die bestätigte leere
Konfiguration ist gestoppt. Siehe den aktuellen Nachweis in docs/DEVELOPMENT.md.
