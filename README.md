# Module Sites2 pour Dolibarr

Version: 2.3.6

## Description
Module de gestion des sites clients des tiers pour Dolibarr. Il permet de gérer les emplacements physiques des tiers (clients, fournisseurs, etc.) tels que des entrepôts, des magasins, des bureaux, ou des résidences.

## Fonctionnalités
- Création et gestion de sites avec adresses complètes
- Géocodage automatique des adresses (latitude/longitude) via OpenStreetMap
- Affichage des sites sur une carte interactive
- Calcul automatique de la distance en kilomètres depuis le siège social du tiers
- Estimation du temps de trajet depuis le siège social
- Gestion des contacts associés au site (uniquement les contacts du tiers lié)
- Interface complète d'administration des sites
- Export des données au format CSV et Excel
- Intégration dans la recherche globale de Dolibarr

## Prérequis
- Dolibarr 11.0 ou supérieur
- PHP 7.0 ou supérieur

## Installation
1. Téléchargez le module et décompressez-le dans le répertoire `/custom` de votre installation Dolibarr
2. Allez dans "Configuration" > "Modules/Applications" et activez le module "Sites2"
3. Les permissions sont automatiquement créées. Assurez-vous de les attribuer aux utilisateurs appropriés

## Utilisation
Après l'activation, vous pouvez :
1. Accéder à la liste des sites depuis le menu principal
2. Créer un nouveau site en associant un tiers existant
3. Visualiser les sites sur une carte
4. Gérer les contacts spécifiques à chaque site

## Particularités
- La distance et le temps de trajet sont automatiquement calculés lorsqu'un site est créé ou modifié
- Seuls les contacts appartenant au tiers associé au site sont disponibles dans l'onglet "Contacts"

## Améliorations futures
- Intégration avec le module Expédition
- Calcul d'itinéraires optimisés
- Alertes de distance pour les interventions
- Statistiques sur les déplacements
- Ajout de fonctionnalités pour les notes, documents et événements

## Licence
Ce module est distribué sous licence GNU General Public License v3 ou supérieure (GPL v3+).
Cette licence est compatible avec la licence GPL v3+ de Dolibarr.

Voir le fichier LICENSE pour plus de détails ou consulter <https://www.gnu.org/licenses/>.

## Configuration et Administration

### Accès aux pages d'administration

Pour accéder à la configuration du module :

1. Connectez-vous à Dolibarr avec un compte administrateur
2. Allez dans "Accueil" > "Configuration" > "Modules/Applications"
3. Trouvez le module "Sites Clients" dans la liste
4. Cliquez sur le bouton "Configuration" (icône engrenage)

### Configuration générale

La page de configuration permet de définir les paramètres suivants :

- Paramètre 1 : Description du paramètre 1
- Paramètre 2 : Description du paramètre 2
- Clé API Google Maps : Clé pour l'affichage des cartes (optionnel)

### Importation de sites

Le module permet d'importer des sites à partir d'un fichier CSV :

1. Accédez à l'onglet "Import" dans la configuration du module
2. Téléchargez l'exemple de fichier CSV pour voir le format attendu
3. Préparez votre fichier CSV avec les colonnes suivantes :
   - label : Nom du site (obligatoire)
   - ref : Référence du site (optionnel, sera égal au label si vide)
   - address : Adresse (obligatoire)
   - zip : Code postal (obligatoire)
   - town : Ville (obligatoire)
   - phone : Téléphone (optionnel)
   - status : Statut (0=Brouillon, 1=Validé, 9=Fermé, optionnel)
   - latitude : Latitude GPS (optionnel, sera calculée si vide)
   - longitude : Longitude GPS (optionnel, sera calculée si vide)
   - fk_soc : ID du tiers associé (optionnel)
4. Importez votre fichier et vérifiez le résultat 