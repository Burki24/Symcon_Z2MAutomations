# Projektkontext: Z2MAutomations

## Ziel

Automationen komfortabel in Symcon erstellen und verwalten. Eine vollständig
eigene externe Zigbee2MQTT-Erweiterung empfängt die erzeugte YAML, speichert
sie persistent und führt sie unabhängig von Symcon aus.

## Stand

Aktueller Abgleich: **06.11.2026**. Maßgeblich sind die
[Prüfliste](docs/PRUEFLISTE.md), die [Agenda](docs/AGENDA.md) und der
[Mehrsystemplan](docs/MULTISYSTEM_PLAN.md). Schritte 1–6 des Zentralenumbaus und
die fünf Designerpunkte sind implementiert. Die fünf Designerpunkte sind auf
beiden Testsystemen praktisch bestätigt; systemgebundene Browser-Betriebsbuttons,
Browserinstallation und Partnerfreigaben besitzen ebenfalls Praxisnachweise.
Partnerfreigaben wurden am 05.11. mit exakter Entwurfswiederherstellung geprüft,
ihre korrigierte Einzahl am 06.11. rein lesend bestätigt.

R1 ist am 06.11.2026 für drei echte Erweiterungsinstanzen synthetisch bestätigt:
getrennte Läufe A nach B/C, Zweigausfall und Wiederkehr ohne Nachholen, getrennte
Freigaben/Antworten, Doppelvermeidung und zugeordnete deutsche Debugmeldungen.
S3 nach S4 läuft über den mittleren synthetischen Broker auch bei gestoppter
mittlerer Erweiterung. Keine reale Broker- oder Geräteprüfung und keine Änderung
an Modul, Erweiterungsquellen oder erzeugtem Artefakt.

Offen bleiben R1 Acht-Erweiterungs-Mesh, R2 der begrenzte zweite native
Installations-Klicknachweis, R3 die zusammengeführte reale Kette Installation
mit automatischem Geräteabruf, R4 die gezielte Zentralen-Restabnahme und R5
weitere Versions-/Geräteabnahmen. Bereits bestätigte Teilprüfungen nicht erneut
als ungeprüft führen. Windows bleibt ein späteres separates Projekt.

## Historische Entwicklungsnotizen

Die folgenden Einträge halten den jeweiligen Implementierungsstand fest.
Aussagen wie „Praxisabnahme offen“ werden durch spätere Nachweise ergänzt;
die aktuelle Restliste steht oben und in PRUEFLISTE.md.

- Partnerfreigaben im Browserdesigner: eigener gerichteter Auswahl- und
  Bestätigungsdialog je Empfänger. Nur lokale mesh_peers ändern, unbekannte
  bestehende Freigaben erhalten, abhängige Regeln einschließlich Folgen vor
  Entzug nennen. Eigene permissions-Berechtigung, schmale GET-/POST-Routen,
  systemgebundene Wiederholungsquittungen und Schutz vor veralteter Vorschau.
  Kein MQTT, keine Live-Änderung und kein neues Erweiterungsartefakt.

- HTTP-LAN-Korrektur im Designer: identity.js erzeugt UUID-v4-Kennungen über
  getRandomValues; die Abhängigkeit von randomUUID entfällt in allen Aktionen
  einschließlich der lokalen Vorschau. Der bisherige requestIdentity-Export
  delegiert an denselben Weg. Neue Assetfreigabe und Regressionstests ohne
  randomUUID; keine Änderung an Live-System, Helpern oder Z2M-Erweiterung.

- Nach bestätigter zentraler Erweiterungsinstallation werden Gerätedaten desselben
  Systems automatisch über den vorhandenen Geräteabruf nachgeladen. Der Status
  bleibt bis zum Abschluss sichtbar; ein Gerätefehler lässt den Installationserfolg
  bestehen. Entwürfe, Freigaben und offene Eingaben bleiben erhalten. GETs senden
  kein MQTT, Modulupdates wiederholen keinen Upload oder Abruf. Native Formularbezüge
  folgen reinen Katalogänderungen ohne Neuaufbau; zwischenzeitliche Entwurfsänderungen
  werden nicht stillschweigend übergangen. Synthetische Prüfung, kein Live-Zugriff.

- Erweiterungsinstallation je System lokal integriert: nativer Zentralenbutton
  und eigener Bestätigungsdialog unter „Systeme und Diagnose“ im Designer.
  Health-Prüfung, festes Artefakt, neue Empfängersitzung und erwarteter Build;
  bekannte gespeicherte Revision/Prüfsumme werden mitgeprüft. Erstinstallation
  vor der Übernahme nur bei besitzerloser Verbindung. Dauerhafte Quittungen
  verhindern Neuversand nach unklarer Antwort oder Update. Erweiterungsupdate
  kann Abläufe und Aufzeichnungen unterbrechen; Z2Ms bestehender Wiederanlauf
  gilt unverändert. Kein Symcon-Aktivierungsauftrag, keine YAML-Übertragung.
  Synthetische Kernel-/Browserprüfung; reale Abnahme nach Modulupdate offen.
  Kein Live-Zugriff; Helper und Artefakt unverändert. ADR 0030 beschreibt den Ablauf.

- Agenda Punkt 5 lokal umgesetzt: „Systeme und Diagnose“ im Browserdesigner mit
  bis zu acht Systemen, bestehendem Systemwechsel, MQTT-/Z2M-Status, deutschem
  Gesundheitszustand und kanonischem Buildvergleich. Feste Diagnose-POSTs rufen
  ausschließlich bestehende Health-, Status- und RuntimeDebug-Einstiege der
  jeweiligen Verbindung auf; GET bleibt ohne MQTT. Befristete Aufzeichnung mit
  absoluter Endzeit und Stopp auch während einer Statusprüfung. Browser zeigt
  den Status und die zuständige native Debuginstanz, keinen zweiten Logspeicher.
  Neue diagnostics-Berechtigung, System-/Generations-/Kontextbindung und begrenzte
  flüchtige Quittungen; Dokumente und Betrieb bleiben erhalten. Lokal mit
  synthetischen Daten geprüft; reale Abnahme nach Modulupdate offen.
  Erweiterungsartefakt und zentrale Helper unverändert.

- Agenda Punkt 4 lokal umgesetzt: Symcon-Ereignisimport im nativen
  Zentralenformular und Browserdesigner. Vorhandener Konverter verwendet
  systemgebundene Speicher, Kataloge und native Variablenbindungen. Quellen
  gehören exakt zum ausgewählten MQTT-Parent/Topic; erlaubte entfernte Ziele
  sind ausdrücklich systemgebunden. Eigene imports-Browserberechtigung,
  Vorschau, erneute Prüfung und Wiederholungsquittung. Neue Regeln bleiben aus;
  keine Ereignisausführung, MQTT-Ausgabe oder automatische Originalabschaltung.
  Lokale Kernel- und Browsernachweise siehe docs/DEVELOPMENT.md. Kein Live-Zugriff;
  Praxisabnahme nach Modulupdate offen. Erweiterungsartefakt unverändert.

- Agenda Punkt 3 lokal umgesetzt: gemeinsamer vollständiger Browsereditor für
  neue und vorhandene Regeln. Gesamte Regel wird in einem lokalen Commit
  validiert; neue Regeln bleiben ausgeschaltet. Drag & Drop und Pfeilbuttons
  verschieben ganze Aktionsschritte samt Wartezeit, auch in Aktionsfolgen.
  Systembindung, Rechte, Quittungen und Partnerfreigaben bleiben erhalten.
  Offline- und synthetische Browserprüfung siehe docs/DEVELOPMENT.md.
  Keine Test-/Live-Installation geändert; reale Abnahme nach Modulupdate offen.
  Erweiterungsupdate nicht erforderlich.

- Agenda Punkt 2 lokal ergänzt: Gerätedaten je System direkt im Browserdesigner
  aktualisieren, mit Ladephasen, Abschlusszeit, Zählern und lesender Statusabfrage.
  Eigene catalog-Berechtigung, befristete flüchtige Jobs und exakte Korrelation.
  Bestehende Z2MConnection-Health-/Katalogpfade und lokale Katalogübernahme werden
  wiederverwendet. Dokumente, offene Eingaben, Freigaben und Z2M-Betrieb bleiben
  erhalten. Keine Änderung an Test-/Live-System; reale Abnahme nach Modulupdate
  noch offen. Erweiterungsupdate nicht erforderlich.

- Agenda Punkt 1 ergänzt lokal die Browser-Regelverwaltung: ausgeschaltete
  Kopien, bestätigtes Löschen von Regeln und unbenutzten Aktionsfolgen sowie
  Statusfilter je System. Verwendete Folgen bleiben geschützt. Eigene
  manage-Berechtigung, Kontext-/Systembindung und Wiederholungsquittungen;
  keine MQTT-Ausgabe. Offline- und isolierte Browserprüfung siehe
  docs/DEVELOPMENT.md. Reale Testabnahme nach Modulupdate bleibt offen.
- Die fünf Punkte der Designeragenda sind lokal umgesetzt. Reale Abnahme der
  neuen Systemdiagnose nach Modulupdate bleibt offen.

- Parallele öffentliche Eintritte lokal korrigiert: belegte Instanzsperre meldet
  instance_busy mit verständlichem Hinweis auf die nicht ausgeführte Aktion,
  echte Besitzersperren bleiben ownership_conflict. HTTP und native Formulare
  unterscheiden beide Fälle; deutsche Debugausgaben nennen Aufruf und Grund.
  Keine zusätzliche Wartefreigabe oder automatische Wiederholung. Regression
  mit synthetischen Zentralen, Browseraufträgen, Timern und unverändertem Zustand.
  Keine Test-/Live-Installation geändert; Praxisabnahme nach Modulupdate offen.
  Erweiterungsartefakt und zentrale Helper unverändert.

- Zentrale Betriebssteuerung lokal ergänzt: Aktivierung mit wählbarem Wiederanlauf,
  globaler Stopp, bedingungsgeprüfter Einzelstart und Stopp genau eines Laufs je
  System im nativen Formular und Browserdesigner. Eigene dauerhafte Aufträge,
  frische Statusbestätigung, Sitzungs-/Revisionsbezug, keine automatischen
  Wiederholungen. Ausschalten bleibt bei offenen Eingaben möglich. Regeln,
  Partnerfreigaben und Entwürfe werden nicht geändert. Offline geprüft mit
  1/2/8 Systemen und dem tatsächlich erzeugten JavaScript an einem synthetischen
  Z2M-Peer. Keine Test-/Live-Installation geändert; Praxisabnahme nach Modulupdate
  offen. Erweiterungsartefakt unverändert. Details: ADR 0028 und docs/WORKSPACE.md.

- Formularneuaufbau nach direkter Übernahme lokal korrigiert: Die sofortige
  Besitzersperre blockierte den getrennten nativen Formularabruf aus ReloadForm.
  Zentrale Formularaktualisierungen werden jetzt im laufenden Eintritt
  zusammengefasst und erst nach dessen Sperrfreigabe übergeben. Bereits laufende
  Formularaufbauten benötigen keinen weiteren Reload. Fehler der Anzeige ändern
  kein erfolgreiches Übernahmeergebnis. Regression mit getrennten Stubobjekten,
  synchroner/asynchroner Bestätigung, lokalem Rückweg und Commitfortsetzung im
  Formular; erneute native Abnahme nach Modulupdate offen. Keine Test-/Live-
  Änderung und kein Erweiterungsupdate.

- Direkte Einrichtung lokal implementiert: native Vorschau aus vorhandenem
  Z2M-editor/status ohne alte Einzelinstanzen, private geprüfte Sicherung,
  ausdrückliche Bestätigung und exklusiver lokaler Verbindungsclaim. YAML, IDs,
  Flags und Partnerfreigaben bleiben erhalten; leeres Z2M ergibt nur einen neuen
  lokalen Entwurf. Vorhandene Entwürfe/alte Einzelquellen/fremde Besitzer werden
  geschützt. Nach Abbruch lokaler Commit und Rückweg fortsetzbar, keine erneuten
  MQTT-Aufträge. Direkt eingerichtete Systeme nutzen die bestehende Prüf-/Store-
  API. Native Bedienung, Timeout-, Konflikt- und Abbruchfälle synthetisch geprüft.
  Keine Test-/Live-Änderung; reale Abnahme nach Modulupdate und neue Registrierung
  der öffentlichen Methoden offen. Erweiterungsartefakt unverändert.

- Schritt 6 teilweise lokal umgesetzt: Prüfung und Speicherung je System in
  Formular und Browserdesigner. Verbindung benötigt abgeschlossenen exklusiven
  Besitzerwechsel; keine implizite Freigabe durch Zuordnung. Eigene Auftrags-
  und Ergebnisjournale, aktuelle Session/Revisionsbasis, native Aktionsrechte,
  frische Speicherstatusbestätigung und keine automatischen Wiederholungen.
  Detaillierte Ablehnungen bleiben bis zur nächsten Prüfung erhalten. Möglicher
  externer Schreibvorgang sperrt den rein lokalen Übernahme-Rückweg. Keine
  implizite Aktivierung durch Speichern. Die inzwischen ergänzte ausdrückliche
  Betriebssteuerung ist oben beschrieben. Kein Test-/Live-Update und kein
  Erweiterungsbuild geändert. Native/Browser-Praxisabnahme nach Update offen.

- Browserstart korrigiert: Die sofortige Besitzersperre wies gleichzeitig
  geladene JavaScript-Dateien mit HTTP 409 ab. Nur feste statische GET-Dateien
  warten nun begrenzt bis 5000 ms; alle Besitzerprüfungen bleiben erhalten.
  startup.js lädt reader.js mit sichtbarer Fehlerbehandlung und ausdrücklichem
  Neuladen. Kein Rohfehler/Token in der Meldung. Regression prüft Konkurrenz,
  Fristablauf, unveränderte Schreibsperren und Loader-Erfolg/-Fehler.
  Auf der Testinstallation korrigiert und im echten Browser sichtbar geprüft:
  beide Systemkataloge, vorbelegte ausgeschaltete Regeln und getrennte lokale
  Änderungen nach Hin-/Zurückwechsel. Testentwürfe und Auswahl wiederhergestellt;
  bestätigte Z2M-Revisionen und Laufzustände unverändert. Live nicht geändert.

- Die Systemeinrichtung findet Z2MConnection-Instanzen jetzt automatisch.
  Native Liste mit Name/ID, Topic und MQTT-Parent/ID; Checkboxauswahl ersetzt den
  Objektpicker. Öffnen schreibt keine Zuordnung. Übernehmen prüft Auswahl,
  aktuellen Verbindungsstand, Besitzer, acht-Systeme-Grenze und bisherige Daten.
  Vorhandene Systeme bleiben angehakt, fremde/ungültige Zeilen sind gesperrt.
  Ein eigener Auswahldigest verhindert Übernahmen nach einer Verbindungsänderung.
  Herkunft migrierter Einträge bleibt bei erneuter Zuordnung erhalten.
  Keine Test-/Live-Aktualisierung oder Änderung des Z2M-Artefakts.

- Anlegeweg der Zentrale korrigiert: Alias „Zigbee2MQTT Automationszentrale“
  und explizites SDK-GetCompatibleParents mit {}. Der native Instanzassistent
  darf keine eigene MQTT-Schnittstelle fordern. Datenflussmetadaten bleiben
  für bestehende Quellverbindungen erhalten; der Getter ändert keine Zuordnung.
  Neue Instanzen öffnen direkt Z2M-Systeme. Verbindungsmodul unverändert.
  Nur lokal geprüft; native Assistentenabnahme nach Modulupdate steht aus.

- Schritt 5 lokal ergänzt: gemeinsame Systemauswahl im nativen Formular und
  Browserdesigner, lokale Bearbeitung je System, Vorbelegung, persistente offene
  Eingaben und expliziter Schutz vor falschem Speicherziel. Keine zusätzliche
  Rückwärtskompatibilitätsschicht. Native dynamische Listen und Browser-CRUD
  nutzen denselben Editor; Aktionsrechte und Partnerfreigaben bleiben erhalten.
  Große Browserkataloge werden seitenweise gelesen. Zentrale systemgebundene
  Prüfung, Speicherung und Laufzeitsteuerung folgen erst in Schritt 6.
  Kein Test-/Live-Update und keine Übernahme oder MQTT-Aktion in diesem Auftrag.

- Selbstsperre der Übernahmevorschau lokal korrigiert: Die eigene ausgewählte
  Quelle wird direkt im laufenden Aufruf gelesen. Andere Quellen verwenden
  weiterhin die geschützte Modulschnittstelle. Regression bildet deren getrennten
  Eintrittskontext nach und prüft 1/2/8 Quellen, Reihenfolge, konkurrierende
  Sperren, Daten-/Besitzererhalt, null MQTT und ausgeglichene deutsche Debugabläufe.
  Native erneute Abnahme nach Modulupdate steht aus; Test und Live unverändert.

- Schritt 4 lokal implementiert: private Quellsicherungen, Manifest und
  persistentes Journal für Sicherung, Übernahme und Rückweg. Explizite Zuordnung
  von 1–8 Quellen zu vorhandenen Verbindungen, Quell-/Zielvergleich, ein lokaler
  schreibender Besitzer und generationstreue Eintrittssperren. Neue
  ownershipEpoch schützt gegen alte Ansichten nach Rückweg und erneuter Übernahme.
  Native Objekte, YAML-Bytes und Freigaben bleiben erhalten; spätere zentrale
  Änderungen werden vor dem Rückweg zusätzlich gesichert. Alte Zugänge und
  Empfängerfreigaben werden nicht übertragen. Synthetische Abbruch-/Wiederanlauf-
  und Konfliktprüfungen; keine Test-/Live-Änderung und keine MQTT-Ausgabe.
  Gemeinsame Bedienoberfläche ist inzwischen lokal ergänzt; danach folgen
  zentrale systemgebundene Betriebsaufträge. Siehe docs/MIGRATION.md.

- Schritt 3 lokal implementiert: versioniertes WorkspaceState und SystemWorkspace
  in Z2MAutomations, eigenes persistentes Attribut. Explizite Verbindungsliste,
  getrennte Roh-YAML/Editorzustände, Auswahl, offene Eingaben und Kataloge je UUID.
  Eigene Systemfingerabdrücke und Generation schützen Schreiben; 32 persistente
  Quittungen sichern Wiederholungen. Aktionsziele prüfen native Variablen-IDs und
  gerichtete lokale Empfangsfreigaben; gewöhnliche Änderungen schützen mesh_peers.
  Native Identitäts-/Rechteadapter und Eigenschaftenauswahl gemeinsam mit dem
  Bestandseditor verwendet. 1/2/8-System-Integration und große Kataloge lokal
  geprüft, inklusive mehr als 512 fremden nativen Instanzen. Keine Migration,
  exklusive Verbindungseigentümerschaft, neue Bedienoberfläche oder Z2M-Ausgabe.
  Test und Live unverändert. Darauf baut inzwischen Schritt 4 auf; die gemeinsame
  Oberfläche folgt noch. Details: docs/WORKSPACE.md und docs/MIGRATION.md.

- Statusanzeigen der neuen Verbindung lokal korrigiert: Der gemeinsame
  MQTT-Transport erkennt einen bestätigten bereiten Empfänger als installiert,
  auch ohne Erweiterungsliste. Bereits bestätigter Cache wird beim Lesen lokal
  berichtigt; öffentliche Statusantwort und Variablen stimmen überein. Fehlende
  deutsche Prüfzustände ergänzt, insbesondere Healthy → Funktionsfähig. Native
  Debugmeldungen erklären Korrektur und Prüfergebnis. Neue Regression prüft auch
  Retain, falsche Transaktion, ungültiges Protokoll, Entfernung und Topicwechsel.
  Kein Erweiterungsupdate und keine Änderung an Test-/Live-Installationen.

- Schritt 2 lokal implementiert: Z2MConnection mit Präfix Z2MC verwendet ein
  vorhandenes natives MQTT-Gateway je System. Gemeinsame Traits MQTTTransport,
  ExtensionTransport und DeviceCatalog statt zweitem Transport. Health samt
  Popup, Status/Buildvergleich, offizielle Erweiterungsinstallation mit frischer
  Bestätigung, Katalogabruf und begrenzte Ablaufaufzeichnung. Eigene stabile
  SystemID und Generation begrenzen Antworten und Cache; keine Passwörter im
  Verbindungsmodul. Acht synthetische Verbindungen mit gemeinsamem und getrennten
  Parents geprüft. Bestehender Editor bleibt eigenständig; keine Übernahme oder
  Besitzerregistrierung, keine Test-/Live-Änderung. Der lokale Arbeitsbereich
  und die Systemliste bauen inzwischen auf diesem Verbindungsbaustein auf.

- Schritt 1 des Mehrsystemumbaus lokal begonnen: SystemContract v1 mit stabilen
  System-/Entitätsschlüsseln, höchstens acht Einträgen, explizitem Besitzer und
  Generation. Rollen werden nur klassifiziert, keine Marker gesetzt. Neue
  Verbindungsmodulidentität und Präfix Z2MC festgelegt.
  GetSystemMigrationSource und PreviewSystemMigration lesen ausschließlich
  Metadaten ausgewählter Bestandsinstanzen. Keine Migration/Archive, Registrierung,
  Besitzerübergabe, neue Oberfläche oder MQTT-Ausgabe. Synthetische 1/2/8-System-
  Prüfungen einschließlich gemeinsamer/getrennter Parents, Konflikte und Erhalt
  aller Quellbytes bestehen. Z2MConnection baut inzwischen auf diesen Verträgen auf.

- Zielarchitektur für die nächste Arbeit festgelegt (ADR 0026,
  docs/MULTISYSTEM_PLAN.md): eine zentrale Automationsverwaltung, bis zu acht
  Z2M-Verbindungsmodule mit vorhandenen nativen MQTT-Parents. Getrennte Dokumente,
  Betriebsfreigaben und Laufzeiten je System; keine Zusammenführung der YAML.
  Übernahme mit Vorschau, bytegleicher Sicherung, Erhalt von IDs/Bindungen/
  Partnerfreigaben, Konfliktschutz, einem schreibenden Besitzer und Rückweg.
  Gesamtumbau noch nicht abgeschlossen; lokale Vertrags-/Vorschaugrundlage und
  Verbindungsbaustein vorhanden. Keine automatische Datenübernahme.

- Wiederverwendbare Aktionsfolgen im Browser erstellen und bearbeiten (ADR 0025):
  gemeinsame Aktionsfelder mit Vorbelegung, Geräte-/Kanal-/Wertsuche, Szenen,
  Reihenfolge und Wartezeiten. Eigene begrenzte POST-sequence-Berechtigung;
  aktuelle Schaltrechte und expandierte Grenzen aller Verwender werden geprüft.
  Vorhandene Regeln und Partnerfreigaben bleiben erhalten. Lokale synthetische
  Browserprüfung einschließlich Übernahme, erneutem Öffnen, Verweis einer
  deaktivierten Regel und Mausradbedienung erfolgreich. Keine Test-/Live- oder
  Z2M-Dateien geändert. Nach Modulupdate echte Testabnahme noch offen;
  Erweiterungsupdate nicht erforderlich.

- Uhrzeit-/Sonnenerstellung am 04.11.2026 im ursprünglichen Testmodul geprüft:
  zwei Uhrzeiten, Sonnenaufgang mit negativem und Sonnenuntergang mit positivem
  Versatz ausgeschaltet angelegt und erneut vorgefüllt geöffnet. Validierungs-
  grenzen und Debug bestätigt; Z2M hat den Prüfentwurf nur validiert. Danach
  Originalentwurf bytegleich hergestellt, Regeln/Freigaben und gespeicherter/
  aktiver Z2M-Stand erhalten. Keine Codekorrektur oder weitere Aktualisierung nötig.

- Zeit- und Sonnenregeln direkt im Browser erstellen (ADR 0024): bis zu acht
  Uhrzeiten oder Sonnenauf-/untergang mit Standort, Zeitzone und ganzzahligem
  Versatz. Native Validierung, Aktionsrechte und gemeinsamer Regel-/Commitpfad;
  ausgeschaltete Übernahme erhält Bestandsregeln, Folgen und Partnerfreigaben.
  Lokale synthetische Browserprüfung einschließlich erneuter Vorbelegung grün;
  Projektlauf einschließlich 136 JavaScript-Tests bestanden. Keine installierten
  Modul- oder Z2M-Dateien geändert. Reale Abnahme nach Modulupdate steht aus;
  ein Erweiterungsupdate ist hierfür nicht nötig.

- Browserbearbeitung nach Modulupdate am 04.11.2026 im ursprünglichen Testmodul
  praktisch geprüft: Geräte- und Zeitregel vorgefüllt, lokal geändert und wieder
  geöffnet; ungültiges Zeitfenster ohne Entwurfsänderung abgewiesen. Deutsche
  Debugmeldungen bestätigt. Ausgangsentwurf danach bytegleich hergestellt;
  alle Regeln, Partnerfreigaben und gespeicherter/aktiver Z2M-Stand erhalten.
  Keine Z2M-Speicherung, Aktivierung oder Geräteaktion. Details in DEVELOPMENT.md.

- Vorhandene Regeln im Browser bearbeiten (ADR 0023): Geräte/Zeiten/Astro,
  Bedingungen und Aktionen mit typisierter Vorbelegung und geschützten Bereichen.
  Feste POST-edit-Route über den nativen Commitpfad; Kontext-, Rechte- und
  Wiederholungsprüfung. Keine Z2M-Übertragung oder Aktivierung. Lokale synthetische
  Browserprüfung und 134 JavaScript-Tests bestanden; reale Symcon-Abnahme nach
  Modulupdate bleibt offen. Erweiterungsquellen und Live-System unverändert.

- Freigabeverlust beim lokalen Hinzufügen von Regeln korrigiert: Ohne ein
  verfügbares Partnermodul erhält SaveEditorDraft vorhandene Partnerfreigaben
  unverändert. Explizite gültige Partnerauswahl und Aktionsrechte unverändert.
  Browser-Hook-Regression zuerst rot, danach grün; zusätzliche native Erstellung
  und fehlende/leere Freigaben abgedeckt. Deutsche Debugmeldung ohne Topics.
  Nur lokale Quellen geändert; Modulupdate und erneute reale Browserabnahme
  stehen aus. Erweiterung und installierte Entwürfe unverändert.
  Alle Projektprüfungen einschließlich 131 JavaScript-Tests und Stilprüfungen grün.

- Ursprünglichen Testentwurf auf Anwenderauftrag verlustfrei mit gespeichertem
  Z2M-Stand abgeglichen. Alle bisherigen Regeln identisch enthalten, zusätzliche
  gespeicherte Regel übernommen; beide Fassungen extern gesichert. Ladepfad
  korrigiert: Partnerfreigaben und YAML-Bytes bleiben auch ohne lokale
  Partnerauswahl erhalten. Regression zuerst rot, danach alle Projektprüfungen
  grün. Nur PHP-Trait auf Test-Symcon aktualisiert; Originalmodul jetzt bytegleich
  zum gespeicherten Stand, Designerfreigabe bestätigt. Keine Z2M-Speicherung,
  Aktivierung oder Geräteaktion; Betrieb unverändert, Prüfkopie bereinigt.

- Browser-Regelerstellung auf dem realen Test-Symcon geprüft und einen zuvor
  verdeckten HTTP-415-Fehler korrigiert: HTTP_CONTENT_TYPE von Symcon und der
  Standardheader werden nun streng als JSON geprüft. Nur diese lokale PHP-Datei
  im Testmodul aktualisiert; keine Produktions-/Z2M-Datei geändert. Auswahl und
  ausgeschaltete Übernahme über den Browser erfolgreich; Bestandsregeln,
  ursprünglicher Testentwurf und Z2M-Betrieb erhalten. Temporäre Instanz entfernt,
  Debug beendet. Einzelheiten und verbliebener alter Testentwurf in DEVELOPMENT.md.

- Designer Schritt 2 lokal umgesetzt (ADR 0022): „Neue Automation“ mit
  getrennt durchsuchbarer Geräte-/Kanal-/Wertauswahl für Auslöser und Aktion.
  Begrenztes POST ergänzt nur eine ausgeschaltete Regel im bestehenden Entwurf.
  Native Guards, aktuelle Aktionsrechte, Kontextkonflikte und Wiederholschutz;
  keine MQTT-/Live-Änderung. Browserprüfung gegen synthetisches PHP-Modul mit
  50 Geräten erfolgreich. Weitere Bearbeitung und Übertragung bleiben nativ.

- Getrennter Geräteabruf lokal ergänzt: RefreshDeviceCatalog fragt status/catalog
  korreliert ab, übernimmt nur geprüfte Geräte/Gruppen/Szenen und loadedAt.
  Native Eingaben, Entwürfe und Laufzeit bleiben erhalten. Neuer Modulbutton,
  Zeitstempel im lesenden Designer, Updatehinweis bei älterer Erweiterung.
  Modul und Erweiterung dafür gemeinsam aktualisieren; Praxisprüfung offen.

- Designer Schritt 1 lesend angebunden (ADR 0021): Formularbutton öffnet den
  instanzbezogenen Browserzugang. Vorhandene Regeln/Folgen und Katalogdaten aus
  passendem TransportScope, keine MQTT-/Speicher-/Geräteaktion. Kurzlebige,
  gehashte Zugänge, feste GET-Routen, zentrale Asset-/HTTP-Helper unverändert
  als Bundle. HTML/CSS und Offline-Beispiele jetzt kanonisch im Modulordner;
  echte Ansicht lädt nur reader.js. Früh eintreffenden MQTT-Empfang beim
  Modulneuladen abgesichert. Praxis- und Prüfnachweise in DEVELOPMENT.md.

- Erster klickbarer Automationsdesigner unter docs/prototypes/automation-designer
  lokal umgesetzt und im Browser geprüft: Navigation, Wenn/Nur wenn/Dann,
  Eigenschaften, Drag & Drop mit Schrittwartezeiten und simulierte Statusführung.
  Ausschließlich synthetische Beispiele, keine Symcon-/MQTT-/Geräteanbindung
  und keine Live-Änderung. Kein Kachelprojekt. Anschließend über einen
  instanzbezogenen Browserlink lesend angebunden; ein eigener Konsolentab
  ist weiterhin nicht als öffentliche Schnittstelle bestätigt. Bearbeitung
  und Übertragung im neuen Designer bleiben ein Folgeschritt.
  Geprüfte Bedienwege und Grenzen stehen im Prototyp-README und DEVELOPMENT.md.

- Gerätebedingungen mit „Nur beim Start“ lokal ergänzt (ADR 0020). Neues
  optionales start_only verhindert, dass etwa zunehmende Helligkeit spätere
  Ausschaltaktionen oder Verlängerungen blockiert. Neue Läufe und Neustarts
  prüfen vollständig; andere Bedingungen bleiben fortlaufend. Native
  Bedingungszeile einschließlich Vorbelegung und Eingabeschutz, fester
  Debugkatalog und Schema ergänzt; Artefakt neu gebaut. Keine installierten
  Modul-/Erweiterungsdateien geändert. Getrennter ausgeschalteter
  Bewegungsmelder-Entwurf über Test-Symcon vorbereitet; ältere Originalbearbeitung
  unverändert. Anwender wünscht eine verlängerbare Nachlaufzeit und korrigiert
  die externe Helligkeitsgrenze; konkrete Werte bleiben außerhalb des Repositorys.
  Endgültiger Entwurf außerhalb des Repositorys gesichert. Anschließend nach
  Anwenderupdate Modul-API, reales Formular-JSON und tatsächliches Zeilendialog-
  Skript geprüft: StartOnly samt Zahlenvergleich vollständig vorbelegt,
  Z2M-Validierung/Speicherung und bytegleiches Wiederladen erfolgreich.
  Neue Bewegungsmelderregel auf ausdrücklichen Anwenderwunsch dauerhaft aktiv;
  bisherige sieben Regeln und Partnerfreigabe unverändert. Gespeicherter und
  aktiver Stand identisch, Wiederanlauf armed, kein Laufzeitfehler. Original-
  ereignisse bleiben aus. Vorbereitungsinstanzen entfernt, eigene Aufzeichnungen
  beendet, ursprünglicher lokaler Testentwurf unverändert. Später bei erfüllter
  Helligkeitsbedingung realen Sensor-/Lichttest durchgeführt: erneute Bewegung
  setzt die volle Minute im selben Lauf zurück. Licht bleibt über die alte Frist
  hinaus an und meldet erst zur verlängerten Frist Aus; kein erneuter Einschalt-
  befehl und kein Fehler. Regel unverändert dauerhaft aktiv, Originalereignisse
  aus und eigene Aufzeichnungen beendet. Kein Helligkeitsanstieg über die
  Schwelle während des realen Laufs und kein weiterer Neustart geprüft.

- Agenda Punkt 4 lokal umgesetzt (ADR 0018): Geräte-/Zeit-/Astro-Regeln mit
  Auswahl zum Ignorieren, Neustarten oder Zurücksetzen einer laufenden Wartezeit.
  Optionale Schema-1-Regelangabe retrigger; fehlend bleibt ignore. Vorbelegung,
  geschützte Formulareingaben, deaktivierte Kopien und fehlende Option älterer
  API-Aufrufe erhalten die Auswahl. Erneute Bedingungs-/Sperrzeitprüfung,
  maximal ein serialisierter Neustart nach laufender Übergabe, kein Neustart
  nach unbestätigtem Ergebnis. Einzelstart und Stopp behalten ihre Grenzen.
  Feste deutsche Debugereignisse im bestehenden MQTT-Kanal; bei Wartezeit-
  erneuerung bleibt der Lauf-/Schrittbezug erhalten. Offline-Nachweise für
  native Callbacks, PHP-/JS-Speicherung, Laufzeit, Mesh und Wiederanlauf.
  Modul und Erweiterung gemeinsam aktualisieren. Kein Zugriff auf Test-/Live-
  Systeme oder Bildschirm in der Implementierungsphase, keine Geräteaktionen.
  Anschließend am 03.11.2026 nach ausdrücklicher Freigabe über Test-Symcon am
  gemeinsamen Z2M geprüft: aktuelle Builds bestätigt, ausgeschaltete Zeitkopie,
  Neustart-/Wartezeitoptionen nativ gewählt und übernommen. Alle drei Werte
  geprüft, gespeichert, wieder geladen und korrekt vorbelegt; vorhandene
  Geräte-Vorbelegung ohne Option bleibt ignore. Originalregeln und Partner-
  freigaben erhalten. Testregel entfernt, Originalinhalt bis auf Revision
  bytegleich wiederhergestellt, bisheriger Betrieb samt Wiederanlauf bestätigt.
  Originaler lokaler Entwurf unverändert, Prüfkopie und Aufzeichnung bereinigt.
  Ungültiger Wert wird mit betroffener Regel abgewiesen, ohne Speicheränderung.
  Keine Testausführung oder Schaltanforderung. Echte erneute Laufzeitauslösung
  hier nicht geprüft; genaue Nachweise und Grenzen in docs/DEVELOPMENT.md.
  Im anschließenden ausdrücklich beauftragten Lauf alle drei Varianten mit
  echten Uhrzeitstarts und realem Licht geprüft. Fünf Sekunden zwischen Starts,
  zwölf Sekunden Schrittwartezeit: Ignorieren schaltet zur ursprünglichen Frist
  aus, Neustart wiederholt Schritt 1 in einem neuen Lauf, Zurücksetzen verlängert
  nur die Wartezeit im bestehenden Lauf. Eine erste abweichende Rückmeldung bei
  Zurücksetzen führte zur Diagnose einer konkurrierenden Bewegungsmeldersteuerung:
  Beide Sensorzweige schalten dasselbe Licht um. Nach Deaktivierung durch den
  Anwender isolierte Nachprüfung erfolgreich. Diese Konkurrenz ist die
  wahrscheinlichste Ursache; der konkrete historische Auftrag ist nicht belegt.
  Testregeln/Prüfmodule entfernt, Originalregeln und lokale Bearbeitung erhalten,
  ursprünglicher Lichtzustand und Betrieb samt Mesh/Wiederanlauf bestätigt.
  Die vom Anwender ausgeschalteten Bewegungsmelder-Ereignisse bleiben aus.

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

1. R1 aus der [Prüfliste](docs/PRUEFLISTE.md) offline auf acht kommunizierende
   Erweiterungen ausweiten. Drei Instanzen mit getrennten Fehlerzweigen sind
   synthetisch bestätigt; echte Mosquitto-Bridges sind dadurch nicht abgenommen.
2. R2/R3 auf die fehlenden Belege eingrenzen; vorhandene bestandene Browser-,
   Installations-, Katalog- und Statusprüfungen erhalten. Operative Ergänzungen
   nur nach eigenem Praxisauftrag.
3. R4/R5 gezielt abschließen: durchgängiger Zentralen-Abnahmebericht und
   zusätzliche Symcon-/Z2M-/Gerätefälle, ohne pauschale Neuprüfung.
4. Erst danach über Veröffentlichung und später ein eigenes Windows-Projekt
   entscheiden. Es gibt weiterhin keine allgemeine Live-Freigabe.

## Dokumentation

- [Versionsübersicht und Grenzen](docs/COMPATIBILITY.md)
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
