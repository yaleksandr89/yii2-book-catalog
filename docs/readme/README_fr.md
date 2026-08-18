# Catalogue de livres Yii2

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

## Choisir une langue

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../README.md) | [English](./README_en.md) | [Español](./README_es.md) | [中文](./README_zh.md) | **Sélectionné** | [Deutsch](./README_de.md) |

Application web de test basée sur Yii2 et MySQL : catalogue de livres et d’auteurs avec téléversement de couvertures, relation plusieurs-à-plusieurs, classement public des auteurs, abonnements par téléphone et intégration de test SMSPilot.

L’implémentation met l’accent sur la séparation des responsabilités : les contrôleurs gèrent les requêtes HTTP et les contrôles d’accès, les données d’entrée sont validées côté serveur, les opérations composées sur les livres sont déléguées à un service dédié et le rapport est construit avec une seule requête d’agrégation. Le projet s’exécute dans Docker et ne nécessite ni PHP ni Composer sur l’hôte.

## Fonctionnalités

- catalogue public de livres et d’auteurs ;
- création, modification et suppression de livres et d’auteurs après connexion ;
- téléversement de l’image principale d’un livre ;
- plusieurs auteurs par livre et plusieurs livres par auteur ;
- Top-10 public des auteurs par nombre de livres pour une année sélectionnée ;
- abonnement d’un visiteur par numéro de téléphone à un auteur précis ;
- notifications SMS de test sur les nouveaux livres via SMSPilot.

## Démarrage rapide

```bash
make init
make build
make up
make composer-install
make migrate
```

Après le démarrage, l’application est disponible sur [http://localhost:8080](http://localhost:8080).

Vous pouvez créer un utilisateur pour la connexion avec la commande console :

```bash
make yii CMD="user/create <username> <password>"
```

Utilisez `make demo-data` pour remplir le catalogue avec des données de démonstration. Les autres commandes pour l’environnement, la base de test et les vérifications sont regroupées dans le [guide de développement](../development.md).

## Accès

| Utilisateur | Possibilités |
| --- | --- |
| Visiteur | Parcourir les livres et auteurs, consulter le Top-10 pour une année choisie, s’abonner par téléphone à un auteur |
| Utilisateur connecté | Tout ce qui est disponible au visiteur, plus la création, la modification et la suppression de livres et d’auteurs |

## Structure de l’application

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

Les contrôleurs restent petits et gèrent principalement le scénario Web : recevoir la requête, vérifier l’accès, lancer la validation et déléguer le travail. Les données du livre sont validées par [`BookForm`](../../models/BookForm.php), tandis que [`BookService`](../../services/BookService.php) enregistre le livre, ses relations avec les auteurs et l’image.

Le Top-10 utilise un [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php) dédié : le calcul est effectué directement dans la base avec une seule requête au lieu d’être assemblé en PHP à partir de modèles chargés.

Ces décisions, la gestion des images et les limites de responsabilité sont détaillées dans le [guide d’architecture](../architecture.md).

## SMSPilot

Après la création réussie d’un livre, l’application recherche les abonnés de ses auteurs et envoie des notifications via SMSPilot en mode test. L’envoi commence uniquement après l’enregistrement du livre et de ses relations en base ; une erreur du service externe n’annule donc pas un livre déjà créé. Si un même numéro est abonné à plusieurs auteurs du nouveau livre, une seule tentative d’envoi est effectuée pour ce numéro.

La vérification manuelle a montré qu’un message contenant un long titre en cyrillique était facturé par l’émulateur comme un SMS multipart plus coûteux : `19.74` contre `9.87` après réduction du texte. Le titre du livre a donc été retiré de la notification et le message limité à deux variantes courtes pour un ou plusieurs auteurs correspondants.

Les réponses SMSPilot, l’ordre d’envoi et la gestion des erreurs sont décrits dans le [guide d’intégration](../smspilot.md).

## Ce qui reste volontairement simple

- Les SMS sont envoyés de manière synchrone après l’enregistrement du livre. Pour une application avec une charge significative, ce travail serait normalement déplacé hors de la requête HTTP vers une file de tâches en arrière-plan, par exemple [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Aucun worker de file ni infrastructure supplémentaire n’a été ajouté pour ce test.
- ActiveRecord est utilisé directement lorsque Yii fournit déjà suffisamment de fonctionnalités pour les lectures et écritures ordinaires. Aucune couche repository n’a été ajoutée autour de chaque modèle car, à cette échelle, elle dupliquerait principalement la couche d’accès aux données existante.
- Aucune REST API séparée ni SPA cliente n’a été ajoutée : l’application est implémentée comme une application Web Yii2 classique rendue côté serveur.
