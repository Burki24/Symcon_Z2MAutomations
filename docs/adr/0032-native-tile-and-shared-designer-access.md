# ADR 0032: Native HTML-Kachel und gemeinsamer Designerzugang

- Datum: 06.11.2026
- Status: Implementiert; echte Kachel-Praxisprüfung noch offen

## Entscheidung

Die bestehende Automationszentrale stellt eine zusätzliche HTML-SDK-Kachel vom
Typ 1 bereit. Sie zeigt die zugeordneten Systeme und den vorhandenen Modulstatus
und öffnet den bestehenden Browserdesigner. Damit bleibt Symcon 9.0 das Ziel;
eine Vollbildvisualisierung vom Typ 2 ab 9.1 ist keine Voraussetzung.
Eine eigene IPSView-Ansicht folgt gegebenenfalls gesondert.

Die Kachelauswahl bleibt pro Ansicht lokal. Ein flüchtiger initialSystem-Wert
im Browserzugang bestimmt den ersten Lesekontext. Die Auswahl im nativen Formular,
offene Eingaben, YAML-Entwürfe, Besitzer und Z2M-Betrieb bleiben unverändert.
Der Browser verwendet anschließend die bestehenden gebundenen Systemaufträge.

## Zugriff und Einbindung

GetVisualizationTile liefert die eigene statische HTML-/CSS-/JS-Ausgabe ohne
Zugangstoken. Vorhandene kanonische Asset- und HTTP-Origin-Helper werden verwendet;
libs/helper bleibt unverändert. RequestAction erlaubt nur status und access mit
begrenzter Eingabe, Client- und Anfragekennung. Native Antworten werden nur bei
exakter Zuordnung angenommen; verspätete Antworten öffnen kein Fenster.
Die SDK-Grenze überträgt JSON-Text, kein PHP-Array. handleMessage dekodiert ihn
vor der bestehenden Zuordnungsprüfung. Synthetische Gegenstellen erzwingen
denselben Transportvertrag; beschädigte Texte werden ohne Zustandsänderung ignoriert.
Keine dynamische Funktionsauswahl, keine MQTT-Betriebsaktionen in der Kachel.

Ein Öffnungsbutton reserviert unmittelbar ein neues Fenster und navigiert es
erst nach bestätigtem SDK-Zugang. Bei Popup-Sperre bleibt ein ausdrücklicher Link.
Die Adresse ist auf den eigenen Hookpfad begrenzt. Tokens bleiben ausschließlich
im URL-Fragment; keine Speicherung im Browser-Storage, keine Token-/Link-Debuglogs.
Die bestehenden CSP-/Origin-Grenzen des Designers werden nicht gelockert.

Modulbutton und Kachel verwenden IssueDesignerAccess. Bestehende Regeln gelten
weiter: zufälliges 256-Bit-Token, nur Hash im Modul, maximal vier Zugänge,
15 Minuten Gültigkeit, Bindung an Besitzer und Verbindungen, Widerruf beim Update.

## Verlängerung

Der zusätzliche feste JSON-POST access-renew prüft Origin und aktuelle Capability.
Er verlängert ausschließlich deren Ablaufzeit, maximal acht Stunden ab Erstellung.
Token, Scope, Rechte und Wiederholungsquittungen werden nicht ersetzt. Eine alte
Capability kann keine abgelaufene oder widerrufene Berechtigung wiederherstellen.

access.js verlängert alle vier Minuten bei sichtbarem Dokument sowie beim erneuten
Sichtbarwerden oder auf ausdrücklichen Klick. Es lädt die Seite nicht neu,
überträgt keine Eingaben und wiederholt keine Editor-/Betriebsaktion. Fehler
bleiben sichtbar. Die Kachel kann einen neuen Öffnungslink ausstellen; bereits
offene Fenster werden dadurch nicht umgeschrieben.

## Nachweise und Grenzen

PHP-Prüfungen verwenden den öffentlichen HTML-/SDK-/Hook-Weg mit synthetischen
Verbindungen und acht Systemen. JavaScript-Prüfungen ergänzen Korrelation,
opake HTML-Hosts, Popup-Sperre, Ablauf und Eingabeerhalt. Ein separater lokaler
Testbrowser prüft die eigenen Ressourcen mit einer synthetischen SDK-Gegenstelle.
Das ersetzt nicht den späteren Nachweis im echten Symcon-Visualisierungsclient.
Es werden weder Z2M-Erweiterung noch Live-Installation geändert.

## Schnittstellenreferenz

- [Symcon HTML-SDK](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/)
- [GetVisualizationTile](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getvisualizationtile/)
- [UpdateVisualizationValue](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/updatevisualizationvalue/)
- [SetVisualizationType](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setvisualizationtype/)
