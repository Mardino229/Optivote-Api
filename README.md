# Optivote API

## Description
Cette application est une API développée avec Laravel pour gérer les élections présidentielles au Bénin, des candidats, des votes, et leurs résultats. Elle offre des fonctionnalités de création, de mise à jour et de suppression pour chaque entité ainsi qu'une gestion des dates et des validations strictes.

## Fonctionnalités principales
- Création, affichage, mise à jour et suppression d'élections.
- Gestion des votes avec validation des périodes d'élection.
- Calcul automatique des résultats et vérification du gagnant.
- Gestion des fichiers: l'upload et la récupération des photos des candidats.
- API répondant avec des messages JSON structurés pour une meilleure intégration.

## Installation
1. Clonez le dépôt :
   ```bash
   git clone https://github.com/Mardino229/Optivote-Api.git
    ```
2. Installer les dépendances :
    ```bash
   composer install
    ```
3. Configurez votre fichier .env :
   ```bash
   cp .env.example .env
    ```
4. Générer une clé d'application :
   ```bash
    php artisan key:generate
    ```
5. Effectuez les migrations de la base de données :
   ```bash
    php artisan migrate
    ```
6. Démarrez le serveur local :
    ```bash
    php artisan serve
    ```
7. Générer la documentation de l'api 
    ```bash
    php artisan scribe:generate
    ```

Accédez à la documentation de l'api à l'addresse : http://127.0.0.1:8000/docs

