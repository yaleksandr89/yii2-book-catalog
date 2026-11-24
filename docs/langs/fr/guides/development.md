# Développement

## Choisir la langue

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../ru/guides/development.md) | [English](../../en/guides/development.md) | [Español](../../es/guides/development.md) | [中文](../../zh/guides/development.md) | **Sélectionné** | [Deutsch](../../de/guides/development.md) |

## Prérequis

Sur l’hôte, il faut :

- Git ;
- Make ;
- Docker avec la prise en charge de Compose ;
- un éditeur et les utilitaires système courants.

PHP, Composer, Yii CLI, le client MySQL, PHPUnit, PHPStan et PHP CS Fixer s’exécutent dans des conteneurs au moyen des commandes du [`Makefile`](../../../../Makefile). Il n’est pas nécessaire de les installer séparément sur l’hôte.

## Configuration initiale

Pour le premier démarrage :

```bash
make init
make build
make up
make composer-install
make migrate
```

`make init` crée `.env.docker` à partir de [`.env.docker.example`](../../../../.env.docker.example) et prépare les répertoires locaux dans lesquels l’application écrit pendant son exécution.

Après le démarrage, l’application est accessible à `http://localhost:8080`.

Pour arrêter les conteneurs :

```bash
make down
```

Pour créer un utilisateur qui pourra se connecter :

```bash
make yii CMD="user/create <username> <password>"
```

## Variables d’environnement

Les principaux réglages se trouvent dans `.env.docker` :

- `HOST_UID`, `HOST_GID` — identifiants de l’utilisateur de l’hôte ;
- `APP_PORT` — port par lequel Nginx est accessible depuis l’hôte ;
- `MYSQL_DATABASE` — base de données principale ;
- `MYSQL_TEST_DATABASE` — base de données de test distincte ;
- `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` — paramètres MySQL ;
- `DB_HOST`, `DB_PORT`, `DB_FORWARD_PORT` — paramètres de connexion à MySQL ;
- `COOKIE_VALIDATION_KEY` — clé de validation des cookies ;
- `SMSPILOT_API_KEY` — clé SMSPilot.

Les vrais secrets ne doivent pas être ajoutés à Git. Remplacez localement la valeur d’exemple de `COOKIE_VALIDATION_KEY` ; le projet propose `make cookie-key` pour générer une valeur aléatoire. `SMSPILOT_API_KEY` peut rester vide si l’intégration SMSPilot n’est pas testée.

## Conteneurs

Docker Compose lance trois services :

- `php` — PHP 8.5 FPM et tous les outils PHP du projet ;
- `nginx` — serveur web ;
- `mysql` — MySQL 8.4.

Le conteneur PHP s’exécute sous l’utilisateur `app`. Ses UID et GID correspondent à ceux de l’utilisateur de l’hôte, ce qui évite que les fichiers créés par l’application et les outils de développement appartiennent à `root`.

Commandes utiles :

```bash
make ps
make restart php
make log nginx
make in php
```

## Base de données et données de démonstration

Le schéma de la base est créé uniquement par les [migrations Yii](../../../../migrations/) :

```bash
make migrate
```

Les tests utilisent une base distincte :

```bash
make test-db-init
make test-db-migrate
```

Avant `make check`, `make test` ou la couverture, préparez la base de test distincte à l’aide des commandes ci-dessus. Les tests suppriment les données de ses tables. Utilisez uniquement une base locale isolée dont le nom diffère de `MYSQL_DATABASE`.

Exécutez les commandes de test via Make : elles vérifient le point de connexion local `mysql:3306` et le nom distinct de la base de test avant les migrations et les tests. Ne lancez pas PHPUnit pour les tests d’intégration directement via Composer ou `vendor/bin/phpunit` en contournant ce contrôle.

Pour ajouter des livres et auteurs de démonstration :

```bash
make demo-data
```

Cette commande est réservée au développement local et aux vérifications manuelles reproductibles de l’interface.

## Tests et vérifications

Commandes principales :

```bash
make test
make test-dox
make check
```

- `make test` exécute PHPUnit ;
- `make test-dox` exécute les mêmes tests avec des descriptions de scénarios lisibles ;
- `make check` exécute Composer (`validate --strict --no-check-publish` et `audit --locked`), la vérification de la syntaxe PHP, PHP CS Fixer, PHPStan et PHPUnit sur la base de test distincte.

Chaque vérification peut aussi être lancée séparément si nécessaire :

```bash
make composer-validate
make composer-audit
make php-lint
make cs-check
make phpstan-check
```

`make cs-fix` formate le code PHP et modifie les fichiers sources.

La couverture est vérifiée par une commande distincte et ne ralentit pas le cycle de développement habituel :

```bash
make coverage
```

Elle crée un rapport Clover dans `runtime/coverage.xml` ; une version HTML peut être obtenue avec `make coverage-html` dans `runtime/coverage`.

## Vérifications automatiques dans GitHub Actions

Le workflow [`.github/workflows/ci.yml`](../../../../.github/workflows/ci.yml) répète les principales vérifications du projet dans un environnement propre :

1. prépare et démarre les conteneurs Docker ;
2. installe les dépendances depuis `composer.lock` et applique les migrations ;
3. crée une base de test distincte ;
4. exécute les vérifications du code et PHPUnit ;
5. génère un rapport de couverture à des fins de diagnostic dans la CI ;
6. vérifie la connexion MySQL et la réponse HTTP de l’application via Nginx ;
7. arrête les conteneurs quel que soit le résultat des étapes précédentes.

Ce workflow ne déploie pas l’application.

## Propriété des fichiers

Les commandes susceptibles de créer des fichiers dans le projet s’exécutent dans le conteneur PHP sous l’utilisateur `app`, associé aux UID/GID de l’utilisateur de l’hôte. Cela concerne notamment `vendor`, `runtime`, `web/assets` et `web/uploads`.

Les commandes Make existantes suffisent au travail courant. Des opérations massives de `chmod` ou `chown`, ou la suppression de répertoires, ne sont pas nécessaires pour corriger les droits.
