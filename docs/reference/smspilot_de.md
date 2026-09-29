# SMSPilot

## Sprache wählen

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./smspilot.md) | [English](./smspilot_en.md) | [Español](./smspilot_es.md) | [中文](./smspilot_zh.md) | [Français](./smspilot_fr.md) | **Ausgewählt** |

Beim Erstellen eines neuen Buchs benachrichtigt die Anwendung Gäste per SMS, die einen seiner Autoren abonniert haben. Die Integration läuft ausschließlich im SMSPilot-Testmodus: Anfragen gehen durch den Dienst, werden aber nicht tatsächlich an einen Mobilfunkanbieter zugestellt.

## Ablauf des Versands

```text
BookService
    ↓
SmsSenderInterface
    ↓
SmsPilotSender
    ↓
SMSPilot
```

[`BookService`](../../services/BookService.php) hängt von der kleinen Schnittstelle [`SmsSenderInterface`](../../integrations/SmsSenderInterface.php) ab. Die Anbindung an den konkreten externen Dienst liegt in [`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php).

[`SmsPilotSendResponse`](../../integrations/smspilot/SmsPilotSendResponse.php) prüft, ob eine erfolgreiche SMSPilot-Antwort die erwartete Struktur hat. Der Dienstschlüssel wird über die Anwendungskonfiguration übergeben und nicht im Quellcode gespeichert.

`SmsPilotSender` setzt bei jeder Anfrage `test=1`. Außerdem sind ein kurzer Netzwerk-Timeout gesetzt und die Protokollierung des HTTP-Antwortinhalts abgeschaltet.

## Zeitpunkt des SMS-Versands

Der Versand gehört nicht zur Transaktion, die das Buch speichert.

Die Reihenfolge ist:

1. das Bild wird gespeichert;
2. das Buch und seine Autorenbeziehungen werden in die Datenbank geschrieben;
3. die Transaktion wird erfolgreich abgeschlossen;
4. erst danach werden Abonnenten ausgewählt und Anfragen an SMSPilot gestartet.

Daher kann ein Ausfall von SMSPilot ein bereits erstelltes Buch nicht rückgängig machen.

Liefert der externe Dienst für eine Nummer einen Fehler, protokolliert [`BookService`](../../services/BookService.php) eine Warnung und verarbeitet die übrigen Empfänger weiter.

## Auswahl der Empfänger

Die Empfänger werden mit einer einzigen Aggregatabfrage über Telefonnummern ausgewählt.

Ist eine Nummer für mehrere Autoren des neuen Buchs abonniert, erscheint sie trotzdem nur einmal im Ergebnis und erhält höchstens einen Sendeversuch. Das verhindert doppelte Benachrichtigungen und vermeidet eine separate Datenbankabfrage pro Autor.

Eine Sortierung der Nummern sorgt für eine vorhersehbare Verarbeitungsreihenfolge.

## Warum die Nachricht gekürzt wurde

Die erste funktionsfähige Version enthielt den Buchtitel. Sie funktionierte: Bei einer manuellen Prüfung gab SMSPilot HTTP 200 und den erfolgreichen Teststatus `0` zurück. Die Emulatorantwort enthielt unter anderem folgende Werte:

```text
server_id = 10000
status    = 0
price     = 19.74
cost      = 19.74
balance   = 60.89
```

Eine echte Zustellung fand nicht statt: Die Anfrage verwendete `test=1`.

Dabei zeigte sich, dass ein langer kyrillischer Buchtitel den Text zu einer mehrteiligen SMS machte. Da Benutzer die Titellänge bestimmen, konnten die Segmentzahl und die vom Emulator berechneten Kosten mit ihr steigen.

Der Titel wurde deshalb aus der Benachrichtigung entfernt. Es blieben zwei begrenzte Varianten:

```text
Новая книга у автора: <имя автора>.
```

Wenn mehrere Autoren passen:

```text
Новая книга у авторов из ваших подписок.
```

Eine zweite manuelle Prüfung ergab:

| Szenario | Sendeversuche pro Nummer | SMSPilot-Status | `price` | `cost` |
| --- | ---: | ---: | ---: | ---: |
| Ein passender Autor | 1 | 0 | 9.87 | 9.87 |
| Zwei passende Autoren, beide abonniert | 1 | 0 | 9.87 | 9.87 |

Im geprüften Szenario sanken die vom Emulator berechneten Kosten von `19.74` auf `9.87`, also genau auf die Hälfte. Das ist das Ergebnis dieser konkreten Prüfung im Testmodus und keine Aussage über SMSPilot-Tarife oder die Kosten einer echten Zustellung.

Die zweite Prüfung bestätigte auch die Deduplizierung: Selbst wenn eine Nummer zwei Autoren des neuen Buchs abonniert hatte, gab es nur einen Sendeversuch.

## Fehlerbehandlung

[`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) wandelt Netzwerkfehler, erfolglose HTTP-Antworten, ungültiges JSON und eine Ablehnung durch SMSPilot in eine `RuntimeException` mit sicherer Fehlermeldung um.

[`BookService`](../../services/BookService.php) fängt diesen Fehler nach dem Speichern des Buchs ab. Im Log erscheint eine kurze Warnung ohne API-Schlüssel und ohne die rohe Anbieterantwort; danach werden weitere Empfänger verarbeitet.

Ein Benachrichtigungsfehler bleibt somit ein Fehler der externen Integration und beeinträchtigt den Katalogzustand nicht.

## Wenn das Versandvolumen wächst

Anfragen an SMSPilot laufen derzeit nacheinander in derselben HTTP-Anfrage, die das Buch erstellt. Für eine kleine Testanwendung vermeidet das zusätzliche Infrastruktur.

Bei höherer Last wäre eine Hintergrundwarteschlange besser. In Yii2 kann dafür beispielsweise [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue) verwendet werden. Falls auch erneute Zustellversuche garantiert werden sollen, sollte der Status ausgehender Benachrichtigungen getrennt in der Datenbank liegen und der Versand über einen unabhängigen Worker erfolgen.

Bei vielen Empfängern kommt außerdem ein Batch-Versand beim SMS-Anbieter in Betracht, um die Anzahl einzelner HTTP-Anfragen zu verringern.
