# Z2MAutomations

[![Symcon PHPModul](https://img.shields.io/badge/Symcon-PHPModul-blue.svg)](https://www.symcon.de/)
[![Symcon Version](https://img.shields.io/badge/Symcon-9.0%2B-blue.svg)](library.json)
[![License](https://img.shields.io/badge/License-PolyForm%20Noncommercial%201.0.0-blue.svg)](LICENSE)
[![Check Style](https://github.com/Burki24/Z2MAutomations/actions/workflows/style.yml/badge.svg?branch=main)](https://github.com/Burki24/Z2MAutomations/actions/workflows/style.yml?query=branch%3Amain)
[![Tests](https://github.com/Burki24/Z2MAutomations/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Burki24/Z2MAutomations/actions/workflows/tests.yml?query=branch%3Amain)

Z2MAutomations erstellt und verwaltet Automationen für bis zu acht
Zigbee2MQTT-Systeme in einer gemeinsamen Symcon-Zentrale. Geräte, Auslöser,
Bedingungen und Aktionen lassen sich im Browserdesigner oder in der nativen
Symcon-Kachel auswählen. Programmier- oder YAML-Kenntnisse sind dafür nicht nötig.

Die Regeln werden an eine eigene Zigbee2MQTT-Erweiterung übertragen.
Nach dem Speichern und Aktivieren führt Zigbee2MQTT sie selbstständig aus;
Symcon muss dafür nicht weiterlaufen.

## Funktionen

- **Geräteauslöser:** auf Taster, Bewegungsmelder und andere Gerätezustände
  reagieren. Mehrere Auslöser können dieselbe Automation unabhängig starten.
- **Zeit- und Sonnensteuerung:** mehrere Uhrzeiten, Wochentage sowie
  Sonnenauf- und Sonnenuntergang mit Zeitversatz verwenden.
- **Bedingungen:** beispielsweise Helligkeit, Temperatur oder Zeitfenster
  berücksichtigen.
- **Aktionen:** Geräte und Gruppen schalten, Helligkeit und Farbe setzen,
  vorhandene Z2M-Szenen aufrufen und Wartezeiten zwischen Schritten festlegen.
- **Aktionsfolgen:** Abläufe einmal anlegen und in mehreren Automationen verwenden.
- **Mehrere Z2M-Systeme:** getrennte Entwürfe zentral bearbeiten und Aktionen
  zwischen freigegebenen Systemen ausführen.
- **Vorhandene Regeln:** Z2M-Automationen einlesen und unterstützte
  Symcon-Ereignisse nach Vorschau als ausgeschaltete Entwürfe importieren.
- **Betrieb:** Regeln aktivieren und stoppen, einzelne Abläufe einmal starten
  sowie den automatischen Wiederanlauf nach einem Z2M-Neustart einstellen.

## Welche Module werden benötigt?

| Modul | Anzahl | Aufgabe |
| --- | --- | --- |
| [Zigbee2MQTT Automationszentrale](Z2MAutomations/README.md) | Eine Zentrale | Automationen erstellen, bearbeiten, prüfen, übertragen und steuern. Enthält den Browserdesigner und die native Symcon-Kachel. |
| [Zigbee2MQTT Verbindung](Z2MConnection/README.md) | Eine Verbindung je Z2M-System, höchstens acht | Verbindet die Zentrale über eine vorhandene Symcon-MQTT-Anbindung mit genau einem Z2M-System. |

Die Zentrale benötigt keine eigene MQTT-Schnittstelle. Jede Z2M-Verbindung
verwendet den MQTT Client oder MQTT Server des zugehörigen Brokers.
Die Z2M-Erweiterung wird je System aus der Zentrale installiert und aktualisiert.

## Voraussetzungen

- IP-Symcon ab Version **9.0** mit PHP **8.5**.
- Ein erreichbares Zigbee2MQTT-System und eine passende Symcon-MQTT-Anbindung.
- Für die Installation der Erweiterung muss externes JavaScript in Z2M bereits
  freigeschaltet sein. Die Library ändert diese Einstellung nicht.

Praxisnachweise liegen für Symcon 9.0 und 9.1 sowie Z2M 2.14.2 und 2.14.2-dev
vor. Umfang und Grenzen stehen in der [Kompatibilitätsübersicht](docs/COMPATIBILITY.md).

## Installation und erste Einrichtung

1. Die Library über die Symcon-Modulverwaltung hinzufügen.
2. Je Z2M-System eine Instanz **Zigbee2MQTT Verbindung** anlegen, mit der passenden
   MQTT-Anbindung verbinden und das Basistopic eintragen. Anschließend die
   MQTT-Anbindung im Modul aktivieren und die Verbindung prüfen.
   [Einrichtung der Verbindung](Z2MConnection/README.md#voraussetzungen-und-einrichtung).
3. Eine **Zigbee2MQTT Automationszentrale** anlegen und dort die gewünschten
   Z2M-Verbindungen auswählen.
   [Zentrale einrichten](Z2MAutomations/README.md#automationszentrale-anlegen).
4. Für jedes System die Erweiterung installieren und die Gerätedaten laden.
   Vorhandene Z2M-Automationen lassen sich über die
   [direkte Einrichtung](Z2MAutomations/README.md#direkt-aus-z2m-einrichten) einlesen.
5. Den Designer öffnen, das gewünschte System wählen und eine Automation erstellen.
   Änderungen zunächst in den lokalen Entwurf übernehmen.
6. Den Entwurf in Z2M prüfen, anschließend speichern und die gewünschten Regeln
   aktivieren. Neue Automationen beginnen ausgeschaltet.

Die [Modulanleitung der Zentrale](Z2MAutomations/README.md) beschreibt die
Bedienelemente und den Ablauf vom Entwurf bis zur aktiven Automation.

## Mehrere Z2M-Systeme verbinden

Mehrere Systeme können denselben MQTT-Broker verwenden, wenn sie unterschiedliche
Basistopics besitzen. Bei getrennten Brokern wird eine MQTT-Bridge für die
systemübergreifenden Aktionen benötigt. Die vollständige
[Schritt-für-Schritt-Anleitung für bis zu acht Mosquitto-Broker](Z2MConnection/README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden)
steht in der Anleitung des Verbindungsmoduls.

Zusätzlich wird im empfangenden Z2M-System eine
[Partnerfreigabe](Z2MAutomations/README.md#partnerfreigaben-im-browserdesigner)
für das sendende System eingerichtet. Die Freigabe der Gegenrichtung erfolgt separat.

## Library und Erweiterungen aktualisieren

Nach einem Library-Update den Erweiterungsstand je System prüfen. Bei einem
Updatehinweis die mitgelieferte Erweiterung über die Zentrale installieren.
Nach erfolgreicher Installation werden die Gerätedaten automatisch nachgeladen;
vorhandene Entwürfe bleiben erhalten. Ein Erweiterungsupdate kann laufende
Abläufe kurz unterbrechen.

## Lizenz und Änderungen

PolyForm Noncommercial License 1.0.0, siehe [LICENSE](LICENSE).
Änderungen stehen im [Changelog](CHANGELOG.md).
