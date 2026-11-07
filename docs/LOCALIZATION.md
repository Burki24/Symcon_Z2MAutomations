# Mehrsprachiger Browserdesigner

Der gemeinsame Designer verwendet lokale JSON-Kataloge für Deutsch und Englisch.
Die Sprachwahl ist oben und in offenen HTML-Dialogen erreichbar. Die erste
Auswahl folgt der Browsersprache, eine gespeicherte manuelle Wahl hat Vorrang;
für andere Browsersprachen gilt Englisch.

Quellen: Z2MAutomations/visualization/locales/de.json und en.json.
Keine Übersetzungsbibliothek, kein Onlinedienst und kein zusätzlicher
Laufzeitabruf. Der Build erzeugt locales.js und locales/messages.json.
Beide Artefakte werden mit den Quellen eingecheckt:

~~~sh
npm run build:locales
npm run check:locales
~~~

Die Prüfung kontrolliert gleiche Schlüssel/Platzhalter, Texte ohne HTML,
referenzierte Schlüssel und aktuelle Artefakte. Sie ist in php tests/run.php
integriert. Die Sprachwahl aktualisiert Textknoten und Darstellungsattribute,
ohne Eingaben, Auswahl, Gerätefilter, Reihenfolge oder Scrollposition zu ersetzen.
Intl formatiert Zahlen, Daten und Mehrzahl. Technische Werte, Zeitzonen,
eigene Namen und von Geräten gelieferte Texte bleiben Daten.

Der Adapter ergänzt bekannte Anzeigefelder um begrenzte Meldungskennungen und
Parameter; native Texte bleiben erhalten. Unbekannte Providertexte erscheinen
unverändert. Detaillierte Ablehnungen kommen in beiden Sprachen aus dem
bestehenden ValidationFeedback-Formatierer. Auftrag/Kontext müssen zum Status
passen; Namen werden nicht übersetzt.

Die feste POST-Route language benötigt Designerzugang, dieselbe Origin und JSON.
Erlaubt sind nur Sprachkennungen und ein Dialog-Flag. Die deutsche Debugmeldung
erklärt den Wechsel, erhält keine Eingaben und sendet weder MQTT noch
Entwurfsaufträge. Bei vollständig fehlenden Sprachressourcen hat der kleine
Startfehler-Dialog einen sicheren englischen Rückfalltext.

## Lokale Nachweise

- php tests/designer.php: Hookverarbeitung mit Stubs, feste Assets, Debug,
  Origin/Capability/Payload-Grenzen, Entwurfserhalt und null MQTT.
- tests/designer-localization.test.mjs: Katalogfehler, Platzhalter, englischer
  Rückfall, Intl und geschützte Daten.
- tests/designer-localization.browser.mjs: eigener Loopback-Server und
  synthetische Systeme im separaten Edge-Testprozess. Offene Bearbeitung,
  Gerätesuche, Kanäle/Werte, Wochentage, Reihenfolge, Wartezeit und nachfolgender
  lokaler Test-Commit, Systemwechsel, gespeicherte Wahl, echtes Mausrad und
  Breiten 360/1920/3840. Z2MA_PLAYWRIGHT_MODULE verweist auf eine vorhandene
  Prüfinstallation; keine neue Produktabhängigkeit.

Keine Änderung oder Installation im Live-System. Eine Prüfung nach tatsächlichem
Modulupdate in Symcon/Kachel bleibt separat. Windows-/Linux-App-Builds und deren
Abnahme gehören zum folgenden Desktop-Arbeitspaket D1.
