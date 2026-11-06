# ADR 0032: Native HTML-Kachel und gemeinsamer Designerzugang

- Datum: 06.11.2026
- Status: Implementiert; native Praxisprüfung auf Symcon 9.1 bestätigt

## Entscheidung

Die bestehende Automationszentrale zeigt denselben vollständigen Browserdesigner
in einem eigenen iframe innerhalb der nativen HTML-SDK-Kachel. Ab Symcon 9.1
wird Typ 2 registriert, damit die native Großansicht ebenfalls den HTML-Designer
anzeigt. Unter Symcon 9.0 bleibt Typ 1 für die normale Kachel erhalten.
IPSView wird nicht zur Administration verwendet; siehe die zurückgenommene
Entscheidung in ADR 0033.

Die Auswahl der äußeren Schale bleibt pro Ansicht lokal; die vollständige
Systemauswahl des vorhandenen Designers wird weiterverwendet. Ein flüchtiger initialSystem-Wert
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

Der Loader fordert beim Öffnen einen korrelierten SDK-Zugang an und navigiert
nur seinen eigenen Child-Frame zur festen Hookadresse. Keine neue Browserseite,
keine Absenkung der bestehenden CSP-/Origin-Grenzen. Die eigene direkte Origin
und contentWindow werden vor jeder Nachricht geprüft. Die unveränderte zentrale
Interaktionsressource ist auf beiden Seiten eingebunden. Native Farben und
Grundschriftgröße werden ausschließlich an den eigenen Child übermittelt.
Die Großansicht verwendet openObject auf derselben Instanz und wird bei einem
offenen Bearbeitungsdialog gesperrt, um dessen Eingaben nicht zu verlieren.

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
bleiben sichtbar. Die Kachel fordert die Verlängerung beim eingebetteten Designer an; dessen
Dokument wird dabei nicht neu geladen. Ein neuer Zugang wird beim erneuten
Öffnen der Kachel erstellt.

## Nachweise und Grenzen

PHP-Prüfungen verwenden den öffentlichen HTML-/SDK-/Hook-Weg mit synthetischen
Verbindungen und acht Systemen. JavaScript-Prüfungen ergänzen Korrelation,
opake HTML-Hosts, Popup-Sperre, Ablauf und Eingabeerhalt. Ein separater lokaler
Testbrowser prüft die eigenen Ressourcen mit einer synthetischen SDK-Gegenstelle.
Zusätzlich wurde der echte native Symcon-9.1-Host mit zwei Systemen geprüft:
Systemwechsel, bestätigter Status, Verlängerung bei offenen Eingaben, Farben,
Großansicht, verschiedene Breiten und reales Mausradscrollen.
Es werden weder Z2M-Erweiterung noch Live-Installation geändert.

## Schnittstellenreferenz

- [Symcon HTML-SDK](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/)
- [GetVisualizationTile](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/getvisualizationtile/)
- [UpdateVisualizationValue](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/updatevisualizationvalue/)
- [SetVisualizationType](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/setvisualizationtype/)
