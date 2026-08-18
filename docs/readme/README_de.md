# Yii2-Buchkatalog

[![Source Code](https://img.shields.io/badge/source-yaleksandr89%2Fyii2--book--catalog-blue.svg?style=flat-square)](https://github.com/yaleksandr89/yii2-book-catalog)
[![CI](https://img.shields.io/github/actions/workflow/status/yaleksandr89/yii2-book-catalog/ci.yml?style=flat-square&label=CI)](https://github.com/yaleksandr89/yii2-book-catalog/actions/workflows/ci.yml)
[![Codecov](https://codecov.io/gh/yaleksandr89/yii2-book-catalog/graph/badge.svg)](https://codecov.io/gh/yaleksandr89/yii2-book-catalog)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Yii](https://img.shields.io/badge/Yii-2.0.55-40B3D8.svg?style=flat-square)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1.svg?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](../../LICENSE)

<p align="center">
  <img
    src="../assets/yii2-book-catalog-readme-cover.png"
    alt="Yii2 Book Catalog — web catalog with authors, subscriptions, Top-10 report and SMSPilot"
    width="100%"
  >
</p>

## Sprache auswählen

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../README.md) | [English](./README_en.md) | [Español](./README_es.md) | [中文](./README_zh.md) | [Français](./README_fr.md) | **Ausgewählt** |

Eine Test-Webanwendung mit Yii2 und MySQL: ein Katalog für Bücher und Autoren mit Cover-Upload, Viele-zu-viele-Beziehung, öffentlichem Autoren-Ranking, Telefon-Abonnements und einer SMSPilot-Testintegration.

Die Implementierung legt den Schwerpunkt auf klare Verantwortlichkeiten: Controller bearbeiten HTTP-Anfragen und Zugriffsprüfungen, Eingaben werden serverseitig validiert, zusammengesetzte Buchoperationen an einen eigenen Service delegiert und der Bericht mit einer einzigen Aggregatabfrage erstellt. Das Projekt läuft in Docker und benötigt weder PHP noch Composer auf dem Host.

## Funktionen

- öffentlicher Katalog für Bücher und Autoren;
- Bücher und Autoren nach der Anmeldung erstellen, bearbeiten und löschen;
- Hauptbild eines Buchs hochladen;
- mehrere Autoren pro Buch und mehrere Bücher pro Autor;
- öffentliches Top-10-Ranking der Autoren nach Buchanzahl für ein ausgewähltes Jahr;
- Gast-Abonnement per Telefonnummer für einen bestimmten Autor;
- Test-SMS-Benachrichtigungen über neue Bücher via SMSPilot.

## Schnellstart

```bash
make init
make build
make up
make composer-install
make migrate
```

Nach dem Start ist die Anwendung unter [http://localhost:8080](http://localhost:8080) erreichbar.

Einen Benutzer für die Anmeldung können Sie mit folgendem Konsolenbefehl erstellen:

```bash
make yii CMD="user/create <username> <password>"
```

Mit `make demo-data` lässt sich der Katalog mit Demo-Daten füllen. Weitere Befehle für Umgebung, Testdatenbank und Prüfungen stehen im [Entwicklungsleitfaden](../development.md).

## Zugriff

| Benutzer | Möglichkeiten |
| --- | --- |
| Gast | Bücher und Autoren ansehen, Top-10 für ein gewähltes Jahr anzeigen, einen Autor per Telefon abonnieren |
| Angemeldeter Benutzer | Alles, was Gästen zur Verfügung steht, zusätzlich Bücher und Autoren erstellen, bearbeiten und löschen |

## Aufbau der Anwendung

```text
HTTP-Anfrage
    ↓
Controller
    ↓
Form Model
    ↓
Service / ActiveRecord / eigene Report-Abfrage
    ↓
MySQL
```

Controller bleiben klein und bearbeiten hauptsächlich den Web-Ablauf: Anfrage entgegennehmen, Zugriff prüfen, Validierung ausführen und die Arbeit weitergeben. [`BookForm`](../../models/BookForm.php) validiert die Buchdaten; [`BookService`](../../services/BookService.php) speichert das Buch, seine Autorenbeziehungen und das Bild.

Für die Top-10 gibt es eine eigene [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php): Die Zählung erfolgt direkt in der Datenbank mit einer einzigen Abfrage und wird nicht in PHP aus geladenen Modellen zusammengesetzt.

Diese Entscheidungen, die Bildverarbeitung und die Verantwortungsgrenzen werden im [Architekturleitfaden](../architecture.md) ausführlich beschrieben.

## SMSPilot

Nach erfolgreichem Erstellen eines Buchs sucht die Anwendung die Abonnenten seiner Autoren und sendet Benachrichtigungen über SMSPilot im Testmodus. Der Versand beginnt erst, nachdem Buch und Beziehungen in der Datenbank gespeichert wurden; ein Fehler des externen Dienstes macht ein bereits erstelltes Buch daher nicht rückgängig. Ist dieselbe Telefonnummer für mehrere Autoren des neuen Buchs abonniert, wird für diese Nummer nur ein Sendeversuch ausgeführt.

Bei der manuellen Prüfung zeigte sich, dass eine Nachricht mit einem langen kyrillischen Buchtitel vom Emulator als teurere mehrteilige SMS berechnet wurde: `19.74` gegenüber `9.87` nach dem Kürzen des Texts. Daher wurde der Buchtitel aus der Benachrichtigung entfernt und der Text auf zwei kurze Varianten für einen oder mehrere passende Autoren begrenzt.

SMSPilot-Antworten, Versandreihenfolge und Fehlerbehandlung sind im [Integrationsleitfaden](../smspilot.md) dokumentiert.

## Was bewusst einfach gehalten wird

- SMS werden nach dem Speichern des Buchs synchron versendet. Bei einer Anwendung mit nennenswerter Last würde diese Arbeit normalerweise aus der HTTP-Anfrage in eine Hintergrundwarteschlange verschoben, zum Beispiel mit [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Für diese Testaufgabe wurden kein separater Queue-Worker und keine zusätzliche Infrastruktur hinzugefügt.
- ActiveRecord wird direkt verwendet, wenn Yii für gewöhnliche Lese- und Schreiboperationen bereits genügend Funktionen bietet. Eine Repository-Schicht um jedes Modell wurde nicht eingeführt, weil sie bei einer Anwendung dieser Größe hauptsächlich die bestehende Datenzugriffsschicht duplizieren würde.
- Es wurden weder eine separate REST API noch eine clientseitige SPA hinzugefügt: Die Anwendung ist als normale serverseitig gerenderte Yii2-Webanwendung umgesetzt.
