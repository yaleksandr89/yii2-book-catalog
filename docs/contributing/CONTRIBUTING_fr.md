# Contribuer

## Choisir une langue

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/CONTRIBUTING.md) | [English](./CONTRIBUTING_en.md) | [Español](./CONTRIBUTING_es.md) | [中文](./CONTRIBUTING_zh.md) | **Français** | [Deutsch](./CONTRIBUTING_de.md) |

Merci de votre intérêt pour Yii2 Book Catalog. Il s’agit d’une petite application web Yii2 ; les changements doivent donc rester limités, reproductibles et faciles à relire.

## Avant de commencer

- Signalez un bug reproductible via une GitHub Issue.
- Pour une amélioration, décrivez le problème, le cas d’usage et le comportement attendu.
- Pour un problème de sécurité, suivez la [politique de sécurité](../../.github/SECURITY.md) et ne publiez pas de détails sensibles.
- Avant un changement important, vérifiez qu’il correspond à l’objectif du projet et n’élargit pas le scope sans raison claire.

## Contrat de l’application

- Le projet est une application web Yii2, pas une REST API, une SPA ni une plateforme de production.
- `Book` et `Author` sont liés en plusieurs-à-plusieurs.
- Les visiteurs peuvent parcourir le catalogue, utiliser le Top-10 public et s’abonner par téléphone à un auteur précis.
- Les utilisateurs authentifiés peuvent en plus gérer les livres et les auteurs.
- Le flux principal est `Controller → Form Model / DTO → application service → ActiveRecord / focused query → DB`.
- `BookService` est injecté dans `BookController` via Yii DI ; le contrôleur ne construit pas lui-même les services applicatifs ni les dépendances externes provider/client.
- Les changements de schéma de base de données passent uniquement par des migrations.
- SMSPilot est utilisé uniquement en mode emulator/test.
- Les secrets, API keys et valeurs locales d’environnement sont fournis via environment/config et ne sont pas commités.

## Branches

Utilisez un nom court qui reflète l’objectif du changement, par exemple :

```text
fix/book-validation
docs/update-development-guide
chore/update-ci
```

## Commits

Conventional Commits est recommandé. Exemples :

```text
fix: correct book validation
docs: clarify local startup
test: cover subscription regression
chore: update CI configuration
```

## Vérifications locales

Le runtime du projet utilise des Make targets adossés à Docker. N’exécutez pas PHP, Composer, Yii CLI, PHPUnit, PHPStan ou PHPCS sur l’hôte.

Les instructions de premier démarrage se trouvent dans le [guide de développement](../development.md).

Avant une Pull Request, exécutez :

```shell
make check
```

Si le comportement de l’application change, exécutez aussi :

```shell
make test
```

La couverture est un diagnostic séparé et ne se lance que lorsqu’elle est utile :

```shell
make coverage
```

Pour un changement de schéma, ajoutez une migration et vérifiez le flux correspondant. N’utilisez pas de `chmod` ou `chown` larges ni de suppression du generated state pour masquer un problème d’environnement.

## Pull Request

Dans la description de la Pull Request, indiquez :

- le problème et la modification ;
- les vérifications exécutées ;
- les tests ajoutés ou mis à jour si le comportement change ;
- l’impact sur la base et les détails de migration si le schéma change ;
- l’impact sur la documentation, l’UI, les uploads ou les intégrations externes.

Avant l’envoi, vérifiez que :

- aucun secret, API key, cookie, session data ou `.env*` local n’est inclus ;
- `vendor/`, `runtime/`, les assets générés, les uploads et les résultats de coverage ne sont pas commités ;
- le changement est limité à une seule tâche cohérente ;
- aucun formatting ou refactoring sans rapport n’est inclus ;
- la documentation correspond au comportement vérifié.
