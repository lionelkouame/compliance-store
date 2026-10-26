# 2. Stack Docker : Mercure et FrankenPHP

## 2.1 Symptôme

Après `make up`, le conteneur `compliance-store-php-1` affichait `PHP app ready!` puis s'arrêtait et redémarrait toutes les 25 secondes (`RestartCount` croissant, état `unhealthy`). Aucune requête HTTP n'aboutissait (`curl` retournait le code 000, connexion refusée). Les logs ne montraient que trois lignes `INFO` de Caddy, sans erreur visible.

## 2.2 Diagnostic

La commande de validation de la configuration Caddy, lancée dans le conteneur, a donné l'erreur réelle :

```
docker compose exec php frankenphp validate --config /etc/frankenphp/Caddyfile
```

Trois erreurs successives sont apparues, chacune révélée après correction de la précédente :

1. `unknown mercure directive "demo"` : `compose.override.yaml` passait `MERCURE_EXTRA_DIRECTIVES: demo`, directive supprimée des versions récentes de Mercure.
2. `the "publisher_jwt", "subscriber_jwt" ... directives work only in compatibility mode ... move them into an "issuer" block for modern mode` : les clés JWT déclarées à la racine du bloc `mercure` ne sont plus acceptées sans mode de compatibilité.
3. `only one unnamed Mercure hub is allowed per configuration; set a name for each additional hub` : la `Caddyfile.dev` importe le snippet `app_site` deux fois (site HTTPS `{$SERVER_NAME}` et site HTTP `http://compliance-store.loc`), donc deux hubs sans nom.

Une quatrième contrainte s'ajoute : la directive `hot_reload` de FrankenPHP (variable `FRANKENPHP_SITE_CONFIG`) exige un hub Mercure dans **chaque** site. Retirer Mercure du site HTTP n'était donc pas une option (`unable to enable hot reloading: no Mercure hub configured`).

**Cause racine.** Le `Dockerfile` part de `dunglas/frankenphp:1-php8.5`, un tag flottant. L'image actuelle (FrankenPHP 1.13.3, Caddy 2.11.7) embarque un module Mercure 0.21 ou supérieur, plus strict que celui avec lequel la `Caddyfile.dev` avait été écrite. Le poste du propriétaire fonctionne vraisemblablement avec une image plus ancienne en cache.

## 2.3 Correction

Les deux fichiers ont été alignés sur la version actuelle du template amont `dunglas/symfony-docker`, qui a rencontré le même problème.

### `frankenphp/Caddyfile.dev`

Le bloc `mercure` du snippet `app_site` est réécrit avec un hub **nommé** et un bloc **`issuer`** :

```caddyfile
mercure {
	# Hub nommé : Mercure >= 0.21 n'accepte qu'un seul hub sans nom par configuration, et ce
	# snippet est importé deux fois (site HTTPS + site HTTP). Un hub nommé peut être partagé.
	name default
	# Émetteur de confiance : le claim iss des jetons doit correspondre (mode moderne, remplace
	# publisher_jwt/subscriber_jwt au niveau racine qui ne sont plus acceptés qu'en mode compat).
	issuer {$MERCURE_TRUSTED_ISSUERS:https://localhost} {
		publisher {
			jwt {env.MERCURE_PUBLISHER_JWT_KEY} {env.MERCURE_PUBLISHER_JWT_ALG}
		}
		subscriber {
			jwt {env.MERCURE_SUBSCRIBER_JWT_KEY} {env.MERCURE_SUBSCRIBER_JWT_ALG}
		}
	}
	anonymous
	subscriptions
	{$MERCURE_EXTRA_DIRECTIVES}
}
```

Un hub nommé peut être partagé entre plusieurs serveurs Caddy, ce qui satisfait à la fois la règle « un seul hub anonyme » et l'exigence de `hot_reload` sur les deux sites.

### `compose.override.yaml`

```yaml
# "demo" a été retiré du module Mercure embarqué dans FrankenPHP >= 1.13 : la directive fait planter Caddy au démarrage.
MERCURE_EXTRA_DIRECTIVES: "${MERCURE_EXTRA_DIRECTIVES:-}"
```

La valeur est désormais interpolée et vide par défaut, donc surchargeable depuis l'environnement si besoin.

### Fichiers non modifiés

`frankenphp/Caddyfile` (production) déclare un seul site et n'a pas été touché lors de cette intervention. Il recevait la même erreur « compatibility mode » dès que l'image de production était reconstruite sur le tag flottant.

**Mise à jour (audit, document 7).** Le bloc `issuer` a depuis été reporté dans le Caddyfile de production, les directives de développement `tls internal` et `auto_https disable_redirects` en ont été retirées, et l'image est épinglée sur `dunglas/frankenphp:1.13.3-php8.5`. Les deux Caddyfiles sont validés par `frankenphp validate`.

## 2.4 Vérification

```
docker compose exec php frankenphp validate --config /etc/frankenphp/Caddyfile
# Valid configuration

curl -s -o /dev/null -w "%{http_code}\n" http://compliance-store.loc/api      # 200
curl -sk -o /dev/null -w "%{http_code}\n" https://compliance-store.loc/api    # 200
curl -s http://compliance-store.loc/api/v1/health
# {"@context":"/api/contexts/HealthCheck","@id":"/api/v1/health","@type":"HealthCheck","status":"OK",...}

docker inspect compliance-store-php-1 --format 'RestartCount={{.RestartCount}} Health={{.State.Health.Status}}'
# RestartCount=0 Health=healthy
```

Le projet n'utilise pas Mercure côté PHP (pas de `symfony/mercure-bundle` dans `composer.json`) : le hub ne sert qu'au `hot_reload` de FrankenPHP en développement.
