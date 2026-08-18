# Stock Market Trigger

Extra-app Nextcloud pour surveiller des actions à partir de leur code ISIN, avec les cours de [Finnhub](https://finnhub.io/).

## Fonctionnement

- Saisir sa clé API Finnhub dans l’écran de l’application.
- Ajouter les ISIN dans la grille, avec un seuil bas et/ou un seuil haut.
- Finnhub résout automatiquement l’ISIN en symbole quand c’est possible. Le symbole peut aussi être saisi manuellement.
- Le navigateur vérifie les cours toutes les cinq minutes lorsque l’application est ouverte.
- Une notification native est déclenchée uniquement lors du franchissement du seuil, après activation par le bouton prévu dans l’interface.

Le suivi actif dépend donc d’un onglet Nextcloud ouvert. Un vrai suivi en arrière-plan nécessiterait un canal de notifications Nextcloud ou un service externe, car un navigateur ne peut pas garantir l’exécution de JavaScript après fermeture de l’application.

## Installation

Copier le dossier dans `custom_apps/stockmarket_trigger`, puis activer **Stock Market Trigger** depuis les applications Nextcloud. La table de suivi est créée par la migration de schéma lors de l’activation.

L’API Finnhub doit autoriser les requêtes depuis le serveur Nextcloud et son offre doit couvrir la fréquence de vérification choisie.