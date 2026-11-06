# 0033 – Vollständiger Designer als optionale IPSView-Ausgabe

Status: lokal implementiert; echte IPSView-Clientabnahme offen.

## Entscheidung

Die Automationszentrale stellt über den regulär verteilten IPSViewHTMLPageHelper
eine ausdrücklich einschaltbare WebContent-Stringvariable bereit. Ihr HTML ist
ein kleiner äußerer Loader für denselben HTTP-Designer. Geräte, getrennte
Systementwürfe, Prüfungen und alle bestehenden Aufträge werden nicht dupliziert.
Der kanonische Interaktions- und HTTP-Origin-Helper bleiben unverändert.
EnableIPSView und IPSViewServerURL liegen im persistenten elements-Bereich des
nativen Formulars gemäß der [offiziellen Formularbeschreibung](https://www.symcon.de/de/llms/developer/sdk-tools/sdk-php/configuration-forms.md).

IPSView-HTMLBoxen können data-Dokumente mit opaker Origin sein. Nur die besondere
ipsview.html-Shell verzichtet bei eingeschalteter Ausgabe auf frame-ancestors
self. Die normale index.html-Shell bleibt unverändert geschützt. Es gibt keine
CORS-Freigabe. Der HTTP-Child besitzt seine eigene Server-Origin; alle API-Aufrufe
und JSON-Schreibprüfungen erfolgen weiterhin innerhalb dieser Origin.

## Zugang

Ein zufälliger Einstiegszugang steht ausschließlich in der ausdrücklich
freigegebenen HTML-Variable und dem URL-Fragment ihres Childs. Serverseitig werden
nur Hash und Bindungsscope gespeichert. Er ist selbst keine Lese- oder
Schreibberechtigung für Entwürfe. Der feste ipsview-access-POST tauscht ihn gegen
eine normale, getrennte Designersitzung; Origin, Inhaltstyp und leerer Auftrag
werden geprüft. Anschließend ersetzt der Child sein Fragment durch die
Sitzungskennung, bevor er den bestehenden Reader startet.

Eine Sitzung hat dieselben Rechte, Quittungen und Fristen wie der übrige Designer:
15 Minuten, bei sichtbarer Ansicht verlängerbar bis acht Stunden, höchstens vier
gültige Sitzungen insgesamt. Verlängern ändert weder Kennung noch Eingaben.
Der Einstiegszugang bleibt für spätere Öffnungen der HTMLBox bestehen. Bewusstes
Neuerzeugen, Ausschalten, Modulupdate oder Einstellungsübernahme widerruft ihn.
Die Sitzungsprüfung berücksichtigt zusätzlich diesen Widerruf; ein bestehender
IPSView-Tab kann ihn nicht durch Verlängern umgehen. Änderungen an Besitzer,
Systembindung oder Serveradresse entziehen die bisherige Berechtigung ebenfalls.

Der direkte IPSView-Parent darf ausschließlich eine Verlängerung anfordern.
Antworten an opake Parents enthalten nur Bereitschaft beziehungsweise Erfolg,
keine Kennungen, Systemdaten oder Tokens. Native Farb-/Großansichtssteuerung
bleibt dem eigenen same-origin Symcon-Loader vorbehalten.

## Folgen und Abnahme

Die HTML-Variable ist ein Verwaltungszugang und muss entsprechend berechtigt
werden. Deaktivieren erhält vorhandene Variablen und Platzierungen; endgültiges
Löschen bleibt eine bestätigte Helper-Aktion. Es gibt keine neue MQTT-Verbindung.
Synthetische Vertragsprüfungen ersetzen keine Abnahme im nativen IPSView-Client.
Mausrad, Touch, Größenanpassung und Darstellung dort gesondert prüfen.
