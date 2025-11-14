# Intégration du module sites2 avec le module équipement

Ce document explique comment installer et configurer l'intégration entre le module sites2 et le module équipement sans migrer les données existantes.

## Prérequis

- Dolibarr 14.0.0 ou supérieur
- Module sites2 installé et activé
- Module équipement installé et activé

## Installation

### 1. Ajouter les champs nécessaires à la base de données

Le fichier SQL `llx_sites2_site_add_equipment_fields.sql` contient les commandes nécessaires pour ajouter les champs de référence aux équipements dans la table `llx_sites2_site`.

Pour exécuter ce script, vous pouvez :

- Utiliser l'outil d'exécution de requêtes SQL dans l'interface d'administration de Dolibarr
- Ou exécuter le script via la ligne de commande MySQL :

```bash
mysql -u [utilisateur] -p [base_de_données] < /chemin/vers/llx_sites2_site_add_equipment_fields.sql
```

### 2. Ajouter les triggers SQL pour la synchronisation

Le fichier SQL `llx_sites2_equipement_triggers.sql` contient les triggers nécessaires pour synchroniser automatiquement les relations entre équipements et sites.

Pour exécuter ce script, vous pouvez :

- Utiliser l'outil d'exécution de requêtes SQL dans l'interface d'administration de Dolibarr
- Ou exécuter le script via la ligne de commande MySQL :

```bash
mysql -u [utilisateur] -p [base_de_données] < /chemin/vers/llx_sites2_equipement_triggers.sql
```

## Utilisation

Une fois l'installation terminée, vous pourrez :

1. Accéder à la liste des équipements liés à un site via l'onglet "Équipements" dans la fiche d'un site
2. Associer des équipements à un site soit :
   - Depuis la fiche équipement, en sélectionnant un site dans le champ correspondant
   - Depuis la fiche site, en utilisant le bouton d'ajout dans l'onglet Équipements

## Remarques importantes

- Cette intégration n'inclut pas la migration des données existantes. Les relations entre équipements et sites devront être recréées manuellement.
- Les triggers nouvellement créés s'appliqueront uniquement aux nouvelles opérations (ajout, modification ou suppression d'équipements).
- Si vous rencontrez des problèmes avec les triggers, vérifiez que votre version de MySQL supporte bien les triggers et que l'utilisateur de base de données dispose des droits nécessaires.

## Résolution des problèmes courants

### Erreur lors de l'exécution des scripts SQL

Si vous rencontrez des erreurs lors de l'exécution des scripts SQL, vérifiez :

- Que les champs n'existent pas déjà dans la table (erreur de type "Duplicate column")
- Que les triggers n'existent pas déjà (erreur de type "Duplicate trigger")

Dans ce cas, vous pouvez simplement ignorer ces erreurs.

### Problèmes de droits d'accès

Si vous rencontrez des problèmes d'accès à la page des équipements, vérifiez que :

- L'utilisateur dispose des droits de lecture sur le module sites2
- L'utilisateur dispose des droits de lecture sur le module équipement 