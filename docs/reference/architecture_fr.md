# Architecture

## Choisir la langue

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./architecture.md) | [English](./architecture_en.md) | [Español](./architecture_es.md) | [中文](./architecture_zh.md) | **Sélectionné** | [Deutsch](./architecture_de.md) |

Le projet s’appuie sur les fonctions standard de Yii2 sans ajouter des couches pour le seul principe. ActiveRecord assure l’accès ordinaire aux données, les modèles de formulaire valident les entrées et les opérations composées sont isolées lorsqu’une frontière de responsabilité distincte est nécessaire.

## Vue d’ensemble

```text
requête HTTP
    ↓
contrôleur
    ↓
modèle de formulaire
    ↓
service / ActiveRecord / requête dédiée au rapport
    ↓
MySQL
```

Par exemple, [`BookController`](../../controllers/BookController.php) reçoit la requête, vérifie les droits d’accès, récupère le fichier téléversé et lance la validation. [`BookForm`](../../models/BookForm.php) valide les champs du livre, les auteurs choisis et l’image. Si la validation réussit, le contrôleur transmet la modification à [`BookService`](../../services/BookService.php).

Le contrôleur ne gère donc ni les transactions, ni le système de fichiers, ni l’envoi des SMS.

## Données et relations

Les principaux modèles sont :

- [`Book`](../../models/Book.php) — livre ;
- [`Author`](../../models/Author.php) — auteur ;
- [`Subscription`](../../models/Subscription.php) — abonnement d’un numéro de téléphone à un auteur ;
- [`User`](../../models/User.php) — utilisateur autorisé à modifier le catalogue.

Un livre peut avoir plusieurs auteurs, et un auteur plusieurs livres. La relation est stockée dans la table `book_author`.

La structure de la base est définie par les [migrations](../../migrations/). Les clés étrangères protègent les relations entre les tables, un index unique empêche d’ajouter deux fois une même paire livre–auteur et un index distinct sur l’année de publication du livre sert au rapport.

Dans la liste des livres, les auteurs sont chargés à l’avance avec `with('authors')` ; la base n’est donc pas interrogée séparément pour chaque livre.

## Enregistrement d’un livre

[`BookService`](../../services/BookService.php) est nécessaire parce qu’une modification de livre comprend plusieurs actions : enregistrer le livre, actualiser ses auteurs, traiter l’image et, lors de la création, prévenir les abonnés.

Lors de la création :

1. une nouvelle image est enregistrée sous un nom généré ;
2. le livre et ses relations avec les auteurs sont écrits dans une même transaction ;
3. si la transaction échoue, elle est annulée et le nouveau fichier supprimé ;
4. l’envoi des notifications commence après la réussite de la transaction.

Lors d’une mise à jour, l’ancienne image est conservée si aucun nouveau fichier n’est téléversé. Si l’image est remplacée, l’ancien fichier n’est supprimé qu’après l’enregistrement réussi du nouvel état du livre.

Lors d’une suppression, l’effacement de l’enregistrement dans la base est d’abord confirmé, puis le fichier image est supprimé.

Ainsi, une image valide ne doit pas disparaître si la modification du livre n’a pas été enregistrée dans la base.

## Rapport Top-10

[`TopAuthorsReportForm`](../../models/TopAuthorsReportForm.php) valide l’année choisie et [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php) construit le rapport.

MySQL effectue le calcul en une seule requête d’agrégation : les livres sont filtrés par année, regroupés par auteur, puis les dix premiers résultats sont sélectionnés. Lorsque plusieurs auteurs ont le même nombre de livres, un tri supplémentaire par nom et identifiant assure un ordre prévisible.

La requête est séparée du contrôleur, car l’agrégation est une opération de lecture spécifique, et non une recherche ordinaire de modèle ActiveRecord.

## Validation et accès

Les entrées utilisateur sont validées côté serveur. Par exemple, [`BookForm`](../../models/BookForm.php) vérifie les champs obligatoires, l’année de publication, l’existence des auteurs choisis et l’image téléversée : extensions autorisées, type MIME et taille maximale de 5 Mio.

Les contrôleurs utilisent `AccessControl` pour les actions qui modifient le catalogue ; `VerbFilter` limite la suppression à POST. La protection CSRF de Yii s’applique aux formulaires web ordinaires.

Les requêtes sont construites avec ActiveRecord, Query Builder et des conditions paramétrées de Yii ; les valeurs utilisateur ne sont pas concaténées dans le SQL.

Les secrets, dont `SMSPILOT_API_KEY`, proviennent de l’environnement et de la configuration de l’application.

## Choix volontairement simples

ActiveRecord n’est pas enveloppé dans un repository distinct pour chaque modèle. Dans cette application, une telle couche reproduirait surtout les fonctions existantes de Yii sans isolation utile.

Les SMS sont envoyés de manière synchrone après l’enregistrement du livre. En cas de charge importante, il serait préférable de déplacer cet envoi dans une file de tâches en arrière-plan ; Yii2 propose pour cela l’extension distincte [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Cette application de test n’ajoute ni file, ni worker, ni infrastructure associée.

L’application reste une Yii2 Web rendue côté serveur. Aucune REST API distincte ni SPA n’a été créée, car la tâche ne les exige pas.
