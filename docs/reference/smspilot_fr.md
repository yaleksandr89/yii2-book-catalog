# SMSPilot

## Choisir la langue

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./smspilot.md) | [English](./smspilot_en.md) | [Español](./smspilot_es.md) | [中文](./smspilot_zh.md) | **Sélectionné** | [Deutsch](./smspilot_de.md) |

Lorsqu’un nouveau livre est créé, l’application envoie un SMS aux visiteurs abonnés à l’un de ses auteurs. L’intégration fonctionne uniquement en mode test de SMSPilot : les requêtes passent par le service, mais aucune livraison réelle à un opérateur mobile n’a lieu.

## Fonctionnement de l’envoi

```text
BookService
    ↓
SmsSenderInterface
    ↓
SmsPilotSender
    ↓
SMSPilot
```

[`BookService`](../../services/BookService.php) dépend de la petite interface [`SmsSenderInterface`](../../integrations/SmsSenderInterface.php), tandis que [`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) gère le service externe concret.

[`SmsPilotSendResponse`](../../integrations/smspilot/SmsPilotSendResponse.php) vérifie qu’une réponse positive de SMSPilot a la structure attendue. La clé du service est fournie par la configuration de l’application et n’est pas conservée dans le code source.

`SmsPilotSender` impose `test=1` pour chaque requête. Un délai d’attente réseau court est également défini et la journalisation du contenu de la réponse HTTP est désactivée.

## Moment de l’envoi du SMS

L’envoi ne fait pas partie de la transaction qui enregistre le livre.

Les opérations se déroulent ainsi :

1. l’image est enregistrée ;
2. le livre et ses relations avec les auteurs sont écrits dans la base de données ;
3. la transaction se termine avec succès ;
4. les abonnés sont alors sélectionnés et les requêtes à SMSPilot commencent.

Ainsi, une indisponibilité de SMSPilot ne peut pas annuler un livre déjà créé.

Si le service externe renvoie une erreur pour un numéro, [`BookService`](../../services/BookService.php) journalise un avertissement et poursuit le traitement des autres destinataires.

## Sélection des destinataires

Les destinataires sont sélectionnés en une seule requête d’agrégation sur les numéros de téléphone.

Si un numéro est abonné à plusieurs auteurs du nouveau livre, il n’apparaît qu’une fois dans le résultat et ne fait l’objet que d’une tentative d’envoi au maximum. Cela évite les notifications en double sans lancer une requête distincte par auteur.

Les numéros sont triés, ce qui rend l’ordre de traitement prévisible.

## Pourquoi le message a été raccourci

La première version fonctionnelle envoyait le titre du livre. Elle fonctionnait : lors d’une vérification manuelle, SMSPilot a renvoyé HTTP 200 et le statut de test positif `0`. La réponse de l’émulateur contenait notamment ces valeurs :

```text
server_id = 10000
status    = 0
price     = 19.74
cost      = 19.74
balance   = 60.89
```

Aucune livraison réelle n’a eu lieu : la requête utilisait `test=1`.

Cette vérification a montré qu’un long titre en cyrillique transformait le texte en SMS multipart. La longueur du titre étant fixée par l’utilisateur, le nombre de segments et le coût calculé par l’émulateur pouvaient augmenter avec elle.

Le titre a donc été retiré de la notification, qui conserve deux variantes limitées :

```text
Новая книга у автора: <имя автора>.
```

Si plusieurs auteurs correspondent :

```text
Новая книга у авторов из ваших подписок.
```

Une seconde vérification manuelle a donné les résultats suivants :

| Scénario | Tentatives d’envoi par numéro | Statut SMSPilot | `price` | `cost` |
| --- | ---: | ---: | ---: | ---: |
| Un auteur correspondant | 1 | 0 | 9.87 | 9.87 |
| Deux auteurs correspondants, avec deux abonnements | 1 | 0 | 9.87 | 9.87 |

Dans ce scénario vérifié, le coût calculé par l’émulateur est passé de `19.74` à `9.87`, soit une réduction de moitié. Il s’agit du résultat d’une vérification précise en mode test, et non d’une affirmation sur les tarifs de SMSPilot ou le coût d’une livraison réelle.

La seconde vérification a également confirmé la déduplication : même lorsqu’un numéro était abonné à deux auteurs du nouveau livre, une seule tentative d’envoi avait lieu.

## Gestion des erreurs

[`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) transforme les erreurs réseau, les réponses HTTP infructueuses, le JSON invalide et les refus de SMSPilot en `RuntimeException` avec un message sûr.

[`BookService`](../../services/BookService.php) intercepte cette erreur après l’enregistrement du livre. Le journal reçoit un bref avertissement sans clé API ni réponse brute du fournisseur ; le traitement des destinataires suivants continue.

Un échec de notification reste donc une erreur d’intégration externe et ne détériore pas l’état du catalogue.

## Si le volume d’envoi augmente

Les requêtes à SMSPilot sont actuellement envoyées l’une après l’autre dans la même requête HTTP qui crée le livre. Pour une petite application de test, cela évite une infrastructure distincte.

Avec une charge plus élevée, il vaudrait mieux déplacer l’envoi dans une file de tâches en arrière-plan. Yii2 permet, par exemple, d’utiliser [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Si des garanties de nouvelle tentative sont aussi nécessaires, l’état des notifications sortantes devrait être stocké séparément en base et l’envoi confié à un worker indépendant.

Pour un grand nombre de destinataires, un envoi par lots chez le fournisseur SMS peut aussi réduire le nombre de requêtes HTTP individuelles.
