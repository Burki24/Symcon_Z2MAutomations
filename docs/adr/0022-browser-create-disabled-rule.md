# ADR 0022: Ausgeschaltete Regeln im Browser ergänzen

- Status: angenommen
- Datum: 03.11.2026
- Erweitert: ADR 0021 für lokale Regelerstellung; GET bleibt lesend

## Anlass

Der Browserdesigner soll eine echte, einfache Geräteautomation erstellen können:
Gerät suchen, Eigenschaft oder Kanal wählen, auslösenden und gewünschten Wert
festlegen. Bestehende Entwürfe und laufendes Z2M dürfen dabei nicht ersetzt werden.

## Entscheidung

Eine eigene creator.js ergänzt die bestehende Modulansicht um einen Dialog.
Auslöser sind lokale lesbare Gerätewerte. Ziele verwenden die aktuellen
Symcon-Schaltrechte und können vorhandene Partnerziele, Gruppen oder Szenen
enthalten. Enum-/Binärwerte bleiben typisiert, Zahlen und Farben nutzen die
vorhandenen Katalogprüfungen. Ein Auslöser und eine Aktion bilden zunächst
eine ausgeschaltete Regel; weitergehende Bearbeitung bleibt im nativen Formular.

Nur die feste Route create akzeptiert POST. Sie verlangt einen neu ausgestellten
instanzbezogenen Zugang mit ausdrücklicher create-Berechtigung, eine passende
Origin/Host-Zuordnung, same-origin (wenn Sec-Fetch-Site vorhanden ist) und JSON.
Kein CORS. Ein alter Lesezugang erhält keine Schreibberechtigung. Der Token bleibt
im Fragment/Header und wird weder ausgegeben noch protokolliert. Der Körper ist
auf 16 KiB und eine feste Feldmenge begrenzt. Keine generische Modulmethodenwahl,
YAML-Übernahme, Speicherung in Z2M oder Runtime-Aktion über diesen Endpunkt.
Die JSON-Erkennung berücksichtigt Symcons HTTP_CONTENT_TYPE und den üblichen
CONTENT_TYPE-Header. Sind beide mit unterschiedlichen Medientypen vorhanden,
wird der Auftrag abgewiesen. Headerwerte werden nicht im Debug offengelegt.

Unter dem bestehenden Transportlock werden der Gateway-/Topic-Kontext,
Entwurfsbytes, Katalog und native Bearbeitung geprüft. Eine zusammengesetzte
Inhaltskennung erkennt Änderungen seit dem Lesen. Offene native Eingaben und
laufende Modulaufträge blockieren die Übernahme. Die Auswahl wird serverseitig
gegen den aktuellen Katalog und aktuelle Symcon-Aktionen validiert. Fehlende oder
fremde Entwürfe werden niemals automatisch ersetzt. SimpleEditorRule wird mit
dem nativen Erstellungsweg geteilt; CommitEditorChange übernimmt den lokalen
Entwurf einschließlich vorhandener Regeln und Folgen.

Pro Zugang bleiben höchstens acht kleine Auftragsbestätigungen im flüchtigen
Sitzungspuffer. Eine Anfragekennung mit derselben Regelabsicht bestätigt nur den
ursprünglichen Erfolg, auch nach verlorener Antwort und Ansichtsaktualisierung.
Geänderte Angaben unter derselben Kennung werden abgewiesen. Zugangsdauer wird
nicht verlängert; Modulupdate und Kontextwechsel entwerten ihn weiterhin.

## Nachweise und Grenzen

PHP-Hooktests prüfen Rechte, Herkunft, Grenzen, veraltete Ansichten, offene
Formulare, typisierte Auswahl, Dublettenschutz und fehlende MQTT-Ausgaben.
Browserprüfung verwendet ein ausschließlich synthetisches PHP-Modul und dieselben
Hook-/Entwurfspfade. Geräteaktionen und Änderungen an Installationen sind nicht
Teil dieses Schritts. Zentral eingebundene Helper bleiben unverändert.
