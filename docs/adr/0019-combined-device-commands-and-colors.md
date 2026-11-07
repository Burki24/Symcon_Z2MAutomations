# ADR 0019: Gemeinsame Gerätebefehle und native Farbauswahl

- Status: implementiert; native Editor-/Speicherprüfung und reale Ausführung an einer Farbleuchte bestätigt
- Datum: 03.11.2026

## Kontext

Agenda 5 soll mehrere schaltbare Geräteeigenschaften und Farben ohne YAML
zugänglich machen. set.values unterstützt bereits mehrere und verschachtelte
Werte, sowohl lokal als auch über Mesh. Bisher kann die native Zeile nur einen
Wert darstellen. Exposes-Composites dürfen nicht durch Herauslösen ihrer Kinder
in falsche Top-Level-Befehle verwandelt werden. Anzeigevariablen dürfen nicht
zu Aktionen werden. Vorhandene zentrale Formular-/Debug-Helper erfüllen ihre
Verträge unverändert; der IPSViewColorPaletteHelper ist kein Gerätebefehlspfad.
Es gibt keinen passenden zentralen Helper für diese Library-Fachlogik.

## Entscheidung

Ein zusätzlicher Aktionsmodus verwaltet bis zu acht Werte desselben Geräts.
Explizite Buttons fügen eine Eigenschaft hinzu, ersetzen ihren Wert oder
entfernen sie; eine nicht editierbare Zusammenstellung zeigt den übernommenen
Inhalt. Kein verschachtelter editierbarer Listen-Löschcallback. Die native
Zeile behält Choice/Delay. Eine kanonische, sortierte command-Kodierung enthält
höchstens 1200 UTF-8-Bytes JSON. Sie wird bei jeder Verwendung vollständig gegen
Katalog, Variablen-ID, Gateway/Topic und aktuelle Schaltrechte geprüft. Keine
Teilübernahme bei unzulässiger Eigenschaft. Einzelwert-API darf bestehende
Mehrfachwerte nicht verkürzen. Ausgefallene oder zu große Darstellungen bleiben
verlustfrei in der vorhandenen erweiterten Metadatenbearbeitung.

Der Katalog erkennt nur bekannte schreibbare color_xy/color_hs-Composites mit
je zwei passenden schreibbaren numerischen Kindern. Fehlender Parent-access
wird gemäß Exposes-Vertrag durch die Kinder bestimmt. Andere Composites werden
nicht als flache Schaltwerte veröffentlicht. Ein kompaktes color-Metadatum
beschreibt die Elterneigenschaft. Die native SelectColor-Ganzzahl von 0 bis
0xFFFFFF wird verlustfrei in {hex: "#RRGGBB"} umgesetzt. Transparenz, fremde
Farbobjekte und automatische Farbraumumrechnungen werden nicht eingeführt.

Farbe und Farbtemperatur desselben Kanals werden nicht kombiniert. Szenen
und binäres TOGGLE bleiben gesonderte Aktionen. Gruppenkataloge erhalten keine
erfundenen Farb-/Helligkeitsfähigkeiten. Wiederverwendbare Folgen und explizit
gewählte entfernte Geräte verwenden dieselben Auswahl-/Rechtepfade. Schema 1,
Runtime und Mesh bleiben unverändert: ein Schritt übergibt genau einen
vollständigen set-Befehl, ohne physische Atomarität zu versprechen.

Alle nativen Callbacks liefern die vollständige öffentliche Methodensignatur,
auch für Auslöser/Bedingungen ohne gemeinsamen Befehl. Das vermeidet Fehler
durch von Symcon erzeugte Wrapper ohne wirksame PHP-Standardparameter.

## Folgen und Grenzen

Modul und eigene Z2M-Erweiterung müssen aktualisiert und Gerätedaten neu geladen
werden. Keine Änderung an Fremdquellen oder zentralen Helpern. Keine neue
Z2M-Laufzeitfunktion, MQTT-Steuerschnittstelle oder Symcon-Abhängigkeit bei der
Ausführung. Öffentliche Zeilendialogmethoden erhalten optionale Kontextargumente;
ChangeEditorCommand ergänzt den lokalen Editor. Nutzwerte und Kodierung werden
nicht ins Debug geschrieben. Synthetische Prüfungen ersetzen keine native
Symcon-9.0-Bedienung oder Prüfung konkreter Leuchten/Converter.

## Schnittstellenquellen

- [Z2M Exposes](https://www.zigbee2mqtt.io/guide/usage/exposes.html):
  Composite-Struktur und Zugriffsbits.
- [Z2M set-Befehle](https://www.zigbee2mqtt.io/guide/usage/mqtt_topics_and_messages.html#zigbee2mqttfriendly_nameset):
  mehrere Eigenschaften in einer Nachricht.
- [Offizielles Farbgerätebeispiel](https://www.zigbee2mqtt.io/devices/CL-L02D.html):
  verschachtelte hex-Farben und endpointbezogene Farbeigenschaften.
- [Symcon SelectColor](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/configuration-forms/selectcolor/):
  native Ganzzahl und Transparenzoption.

Nur Schnittstellen als Grundlage, keine fremde Automationsimplementierung.
