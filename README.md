# Z2MAutomations

[![Symcon PHPModul](https://img.shields.io/badge/Symcon-PHPModul-blue.svg)](https://www.symcon.de/)
[![Symcon Version](https://img.shields.io/badge/Symcon-9.0%2B-blue.svg)](library.json)
[![License](https://img.shields.io/badge/License-PolyForm%20Noncommercial%201.0.0-blue.svg)](LICENSE)
[![Check Style](https://github.com/Burki24/Z2MAutomations/actions/workflows/style.yml/badge.svg?branch=dev)](https://github.com/Burki24/Z2MAutomations/actions/workflows/style.yml?query=branch%3Adev)
[![Tests](https://github.com/Burki24/Z2MAutomations/actions/workflows/tests.yml/badge.svg?branch=dev)](https://github.com/Burki24/Z2MAutomations/actions/workflows/tests.yml?query=branch%3Adev)

Z2MAutomations vereinfacht die Einrichtung von Zigbee2MQTT-Automationen in Symcon.
Symcon erstellt die Regeln und überträgt sie als YAML an eine
eigene Erweiterung. Die Automationen werden direkt in Zigbee2MQTT ausgeführt.

Bei erneuter Auslösung einer laufenden Automation lässt sich wählen, ob die
Auslösung ignoriert, die Folge von vorn begonnen oder nur die aktuelle Wartezeit
zurückgesetzt wird. Siehe [Bedienung](Z2MAutomations/README.md#wenn-eine-laufende-automation-erneut-ausgelöst-wird).

Wiederverwendbare Aktionsfolgen lassen sich einmal anlegen und in mehreren
Automationen auswählen. Änderungen an der Folge gelten nach Übertragung für
alle verwendenden Regeln. Siehe [Bedienung](Z2MAutomations/README.md#eine-aktionsfolge-mehrfach-verwenden).

Zeitgesteuerte Regeln unterstützen auch Sonnenauf- oder Sonnenuntergang mit
Standort und Zeitversatz. Die eigene tägliche Berechnung findet in Z2M statt;
siehe [Bedienung](Z2MAutomations/README.md#automationen-bei-sonnenauf--oder-sonnenuntergang).

Gespeicherte Automationen lassen sich zusätzlich gezielt einmal ausführen und
laufende Abläufe einzeln stoppen. Bedingungen gelten weiterhin; andere Regeln
und der automatische Wiederanlauf bleiben unverändert. Siehe
[Einzelsteuerung](Z2MAutomations/README.md#eine-gespeicherte-automation-einmal-ausführen).

Die MQTT-Anbindung empfängt den Z2M-Status, Versionsinformationen und die Liste
externer Erweiterungen. Eine Verbindungsprüfung sendet eine Gesundheitsanfrage
und zeigt das Ergebnis nach der passenden Antwort an.

Die eigene Z2M-Erweiterung lässt sich aus dem Modul über MQTT installieren.
Das Modul prüft YAML über den Empfänger und überträgt sie nach Vorschau und
Bestätigung in dessen persistenten Speicher. Die Erweiterung kann eine gespeicherte
Revision nach ausdrücklicher Aktivierung ausführen. Aktivierung und Stopp werden
im Modul bedient und von Z2M bestätigt. Der Klick-Editor lädt Geräte aus
Z2M und erstellt Geräte- oder Zeitautomationen mit Bedingungen und bis zu 16
geordneten Geräteaktionen. Die Wartezeit ist pro Schritt einstellbar;
YAML-Kenntnisse sind dafür nicht erforderlich. Unterstützte ausgelöste
Symcon-Ereignisse lassen sich nach Prüfung und Vorschau als deaktivierte Regeln
importieren. Gruppen und zustandsabhängiges Umschalten sind als Aktionsziele verfügbar.
Mit zwei Automationsmodulen und einem gemeinsamen Broker oder einer MQTT-Bridge lassen sich
auch Geräte und Gruppen des jeweils zweiten Z2M-Systems als Aktionsziele wählen.
Beide Systeme benötigen dafür gespeicherte und ausdrücklich aktivierte Freigaben.
[Bedienung](Z2MAutomations/README.md), [Ausführung und Grenzen](docs/RUNTIME.md).
Ein [synthetisches YAML-Beispiel](libs/z2m/examples/automations.yaml) zeigt das
eigene Format; der [YAML-Vertrag](docs/YAML_SCHEMA.md) beschreibt die Felder.

Weitere, im Klick-Editor nicht darstellbare Regelstrukturen sind über die zusätzliche
YAML-Ansicht möglich. Die Ausführung aktivierter Regeln findet in Z2M statt und benötigt
kein laufendes Symcon. Auf Wunsch startet Z2M den zuletzt aktivierten Regelstand
nach einem Neustart selbstständig wieder; bestätigtes Stoppen bleibt wirksam.
Die standardmäßig eingeschaltete Option wird beim Aktivieren an Z2M übertragen.

## Voraussetzungen

- IP-Symcon ab Version 9.0 mit PHP 8.5.
- Zigbee2MQTT und eine konfigurierte Symcon-Instanz vom Typ MQTT Client oder
  MQTT Server, über die dieselbe Z2M-Installation erreichbar ist.
- Beim MQTT Client müssen die Z2M-Bridge- und Erweiterungstopics abonniert sein,
  beispielsweise zigbee2mqtt/bridge/# und zigbee2mqtt/symcon/automations/#.
  Ein vorhandenes Abonnement # genügt ebenfalls.
- Für die Erweiterungsinstallation muss externes JavaScript in Z2M bereits
  freigeschaltet sein. Das Modul ändert diese Einstellung nicht und startet Z2M nicht neu.
- Praktisch belegter Z2M-Stand: **2.14.2-dev**. Die stabile Ausgabe 2.14.2 ist
  das Schnittstellenziel und besitzt noch keine eigene Praxisabnahme. Die genaue
  Git-Revision des geprüften Entwicklungsstands ist nicht dokumentiert.
  [Versionsübersicht und Grenzen](docs/COMPATIBILITY.md).

## Module

- [Vorschau des Automationsdesigners](docs/prototypes/automation-designer/README.md):
  separat öffnbarer Oberflächenentwurf mit erfundenen Beispielen. Noch keine
  Anbindung an das Modul oder an Geräte.
- [Z2MAutomations](Z2MAutomations/README.md): Einrichtung, Klick-Editor, Import,
  Prüfung, Übertragung und Steuerung der Automationen.
- [Eigene Z2M-Erweiterung](libs/z2m/README.md): Empfänger mit Schema-Prüfung,
  persistentem Konfigurationsspeicher und Sicherung der vorherigen Fassung.

## Installation

Es gibt noch keine für den Live-Betrieb freigegebene Version.
In einer Entwicklungsinstallation die Instanz mit dem vorhandenen MQTT-Gateway
verbinden, das Basistopic einstellen und die MQTT-Anbindung aktivieren.
Details stehen in der [Modulanleitung](Z2MAutomations/README.md).

Die Anbindung ist zunächst deaktiviert. Nach dem Einschalten und Übernehmen der
MQTT-Einstellungen sowie beim Öffnen des aktiven Formulars wird der Status
einmalig automatisch geprüft. Installation, Übertragung und Aktivierung erfolgen
über die jeweiligen Schaltflächen. Änderungen im Klick-Editor zuerst mit dessen
Übernehmen-Button in den Entwurf übernehmen, danach prüfen, speichern und aktivieren.
Der normale orangefarbene Symcon-Button gilt für die Moduleinstellungen.

## Lizenz und Änderungen

PolyForm Noncommercial License 1.0.0, siehe [LICENSE](LICENSE).
Änderungen stehen im [Changelog](CHANGELOG.md).

Im Klick-Editor werden Gerät, Eigenschaft beziehungsweise Kanal und Wert getrennt
gewählt. Aktionen bieten nur in Symcon schaltbare Variablen mit Z2M-Schreibrecht
an; lesbare Sensoren bleiben als Auslöser und Bedingungen verfügbar. Die Ausführung
bleibt vollständig in Z2M. Nach Aktualisierung der Library auch die mitgelieferte
Erweiterung aktualisieren und den Gerätekatalog erneut laden.
