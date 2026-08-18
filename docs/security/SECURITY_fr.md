# Politique de sécurité

## Choisir une langue

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/SECURITY.md) | [English](./SECURITY_en.md) | [Español](./SECURITY_es.md) | [中文](./SECURITY_zh.md) | **Français** | [Deutsch](./SECURITY_de.md) |

## Versions prises en charge

Les correctifs de sécurité sont examinés pour l’état actuel de `master` et la dernière version publiée.

| Version | Prise en charge |
|---|---|
| `master` | Oui |
| Dernière version publiée | Oui |

## Ce qui constitue une vulnérabilité

Les problèmes de sécurité comprennent notamment :

- le contournement de l’authentification, de `AccessControl` ou des restrictions des destructive actions ;
- le contournement de la protection CSRF ;
- la gestion non sûre des fichiers uploadés, des noms de fichiers ou des chemins ;
- une SQL injection ou le contournement de la validation côté serveur ;
- l’exposition d’API keys, mots de passe, cookies, session data ou autre configuration privée ;
- la fuite de données sensibles via les logs, messages d’erreur ou l’intégration SMSPilot ;
- l’accès non autorisé aux données d’un autre utilisateur ou à une action protégée.

Les bugs ordinaires, questions d’utilisation et demandes d’amélioration peuvent être publiés dans GitHub Issues s’ils ne contiennent pas de données sensibles.

## Comment signaler une vulnérabilité

Privilégiez GitHub Private Vulnerability Reporting lorsqu’il est disponible :

1. Ouvrez l’onglet **Security** du dépôt.
2. Accédez à **Advisories**.
3. Choisissez **Report a vulnerability**.
4. Envoyez le rapport sans publier de détails sensibles dans une Issue normale.

Si Private Vulnerability Reporting n’est pas disponible, créez une Issue publique minimale sans détails techniques de la vulnérabilité et demandez un canal de contact privé.

Ne publiez pas avant la sortie d’un correctif :

- des API keys ou mots de passe ;
- des cookies, session data ou CSRF tokens ;
- des données personnelles réelles ;
- des logs de production complets ;
- un exploit fonctionnel ou des détails inutiles permettant de reproduire l’attaque.

## Que fournir dans le rapport

Si possible, indiquez :

- la release, branch ou commit affecté ;
- l’impact ;
- les étapes minimales de reproduction ;
- le comportement attendu et observé ;
- des fragments request/response/log nettoyés si utiles ;
- une correction possible si elle est connue.

Utilisez uniquement des données synthétiques ou anonymisées.

## Traitement du rapport

Les rapports sont examinés selon les disponibilités ; aucun SLA fixe n’est promis.

Merci de coordonner la divulgation avec le mainteneur avant de publier les détails. Après confirmation d’une vulnérabilité, le correctif et les informations sur les versions affectées sont publiés selon un processus de coordinated disclosure.

Le projet n’annonce pas de programme de récompense pour les vulnérabilités.
