# Z2MAutomations

[![Symcon PHPModul](https://img.shields.io/badge/Symcon-PHPModul-blue.svg)](https://www.symcon.de/)
[![Symcon Version](https://img.shields.io/badge/Symcon-9.0%2B-blue.svg)](library.json)
[![License](https://img.shields.io/badge/License-PolyForm%20Noncommercial%201.0.0-blue.svg)](LICENSE)
[![Check Style](https://github.com/Burki24/Z2MAutomations/actions/workflows/style.yml/badge.svg?branch=main)](https://github.com/Burki24/Z2MAutomations/actions/workflows/style.yml?query=branch%3Amain)
[![Tests](https://github.com/Burki24/Z2MAutomations/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Burki24/Z2MAutomations/actions/workflows/tests.yml?query=branch%3Amain)

Erstellt und verwaltet Automationen für Zigbee2MQTT: Geräteauslöser, Uhrzeiten,
Sonnenzeiten, Bedingungen, Szenen und Aktionsfolgen. Eine Automationszentrale
verwaltet bis zu acht Z2M-Systeme. Die Bedienung erfolgt im Browserdesigner oder
in der nativen Symcon-Kachel, ohne Programmier- oder YAML-Kenntnisse.

Die Regeln werden an eine eigene Zigbee2MQTT-Erweiterung übertragen und dort
nach dem Aktivieren selbstständig ausgeführt. Symcon muss dafür nicht weiterlaufen.

## Inhaltsverzeichnis

- [1. Voraussetzungen](#1-voraussetzungen)
- [2. Funktionsumfang](#2-funktionsumfang)
- [3. Enthaltene Module](#3-enthaltene-module)
- [4. Installation](#4-installation)
- [5. Konfiguration in IP-Symcon](#5-konfiguration-in-ip-symcon)
- [6. Änderungen](#6-änderungen)
- [7. Lizenz](#7-lizenz)

## 1. Voraussetzungen

- IP-Symcon ab Version **9.0** mit PHP **8.5**.
- Ein erreichbares Zigbee2MQTT-System und eine passende Symcon-Instanz vom Typ
  **MQTT Client** oder **MQTT Server**.
- Für die Installation der Erweiterung muss externes JavaScript in Z2M bereits
  freigeschaltet sein. Die Library ändert diese Einstellung nicht.
- Für Aktionen zwischen getrennten Brokern eine MQTT-Bridge und passende
  Partnerfreigaben der beteiligten Z2M-Systeme.
- Die native Großansicht der Kachel ist ab Symcon 9.1 verfügbar; unter 9.0
  steht der Designer innerhalb der normalen Kachel zur Verfügung.

Praxisnachweise liegen für Symcon 9.0 und 9.1 sowie Z2M 2.14.2 und 2.14.2-dev
vor. Umfang und Grenzen stehen in der [Kompatibilitätsübersicht](docs/COMPATIBILITY.md).

## 2. Funktionsumfang

- Geräteautomationen für Taster, Bewegungsmelder und andere Gerätezustände;
  mehrere Auslöser können dieselbe Regel unabhängig starten.
- Zeitautomationen mit mehreren Uhrzeiten, Wochentagen sowie Sonnenauf- und
  Sonnenuntergang mit Zeitversatz.
- Bedingungen für beispielsweise Helligkeit, Temperatur und Zeitfenster.
- Geräte und Gruppen schalten, Helligkeit und Farbe setzen und vorhandene
  Z2M-Szenen aufrufen.
- Mehrere Aktionen mit Wartezeiten ausführen und Aktionsfolgen in verschiedenen
  Automationen wiederverwenden.
- Bis zu acht Z2M-Systeme mit getrennten Entwürfen in einer Zentrale verwalten;
  freigegebene Geräte und Gruppen anderer Systeme als Aktionsziele verwenden.
- Vorhandene Z2M-Automationen einlesen und unterstützte Symcon-Ereignisse nach
  Vorschau als ausgeschaltete Entwürfe importieren.
- Automationen erstellen, bearbeiten, kopieren und löschen; gespeicherte Regeln
  aktivieren und stoppen sowie einzelne Abläufe einmal starten oder beenden.
- Bei erneuter Auslösung ignorieren, neu starten oder die Wartezeit zurücksetzen;
  automatischen Wiederanlauf nach einem Z2M-Neustart einstellen.

Die Bedienung der einzelnen Funktionen steht in der README des jeweiligen Moduls.

## 3. Enthaltene Module

- [Zigbee2MQTT Automationszentrale](Z2MAutomations/README.md)
  - Eine zentrale Instanz zum Erstellen, Bearbeiten, Prüfen und Übertragen der
    Automationen für bis zu acht Z2M-Systeme.
  - Enthält den Browserdesigner und die native Symcon-Kachel. Die Zentrale
    benötigt keine eigene MQTT-Schnittstelle.
- [Zigbee2MQTT Verbindung](Z2MConnection/README.md)
  - Eine Verbindungsinstanz je Z2M-System, verbunden mit dessen vorhandenem
    MQTT Client oder MQTT Server.
  - Stellt die Verbindung zum jeweiligen Z2M-System sowie dessen Erweiterungsstand
    und Gerätedaten für die Zentrale bereit.
  - Die Modulanleitung enthält auch die
    [Einrichtung mehrerer Mosquitto-Broker](Z2MConnection/README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden).

## 4. Installation

Die Library über die Symcon-Modulverwaltung aus dem Repository
[Burki24/Z2MAutomations](https://github.com/Burki24/Z2MAutomations) hinzufügen.
Als Zweig **main** verwenden.

## 5. Konfiguration in IP-Symcon

1. Je Z2M-System eine Instanz **Zigbee2MQTT Verbindung** anlegen und dem passenden
   MQTT Client oder MQTT Server zuordnen. Das Z2M-Basistopic eintragen, die
   MQTT-Anbindung im Modul aktivieren und die Verbindung prüfen.
2. Eine **Zigbee2MQTT Automationszentrale** anlegen. Im Bereich **Z2M-Systeme**
   die gewünschten Verbindungen anhaken und **Systemzuordnung übernehmen** wählen.
3. Für jedes System die Erweiterung installieren und die Gerätedaten laden.
   Vorhandene Z2M-Automationen lassen sich über die direkte Einrichtung einlesen.
4. Den Designer öffnen, das gewünschte System auswählen und die Automation
   mit Auslösern, optionalen Bedingungen und Aktionen zusammenstellen.
5. Die Änderungen in den lokalen Entwurf übernehmen. Neue Regeln beginnen
   ausgeschaltet; vor der gewünschten Nutzung den Regelschalter einschalten.
6. Den Entwurf in Z2M prüfen, speichern und die gespeicherten Automationen
   aktivieren. Erst dann führt Z2M die eingeschalteten Regeln aus.

Eine Zentrale genügt für bis zu acht Z2M-Systeme. Mehrere Systeme können denselben
MQTT-Broker verwenden, wenn sie unterschiedliche Basistopics besitzen. Für getrennte
Broker beschreibt die [Modulanleitung der Verbindung](Z2MConnection/README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden)
die Einrichtung. Empfangende Systeme benötigen zusätzlich eine gespeicherte
[Partnerfreigabe](Z2MAutomations/README.md#partnerfreigaben-im-browserdesigner)
für das sendende System; die Gegenrichtung wird separat erlaubt.

Nach einem Library-Update den Erweiterungsstand je System prüfen und bei einem
Updatehinweis die mitgelieferte Erweiterung über die Zentrale installieren.
Die Gerätedaten werden anschließend automatisch nachgeladen; Entwürfe bleiben
erhalten. Ein Erweiterungsupdate kann laufende Abläufe kurz unterbrechen.

Die weitere Konfiguration und Bedienung stehen in der README des jeweiligen Moduls.

## 6. Änderungen

Neue Funktionen und Fehlerkorrekturen stehen im [Changelog](CHANGELOG.md).

## 7. Lizenz

[PolyForm Noncommercial License 1.0.0](LICENSE).
