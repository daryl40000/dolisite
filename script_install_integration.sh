#!/bin/bash
# Script d'installation de l'intégration entre le module sites2 et le module équipement
# À exécuter depuis le répertoire custom de Dolibarr

# Vérification des paramètres
if [ $# -ne 3 ]; then
    echo "Usage: $0 utilisateur_mysql mot_de_passe_mysql nom_base_de_donnees"
    exit 1
fi

MYSQL_USER=$1
MYSQL_PASSWORD=$2
MYSQL_DATABASE=$3

echo "Installation de l'intégration entre sites2 et équipement..."

# Exécution du premier script pour ajouter les champs dans la table
echo "1. Ajout des champs dans la table sites2_site..."
mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < sites2/sql/llx_sites2_site_add_equipment_fields.sql
if [ $? -eq 0 ]; then
    echo "   Structure de la table mise à jour avec succès."
else
    echo "   ATTENTION: Des erreurs sont survenues lors de la mise à jour de la structure de la table."
    echo "   Si l'erreur indique 'Duplicate column', cela signifie que les champs existent déjà et vous pouvez ignorer cette erreur."
fi

# Exécution du deuxième script pour créer les triggers
echo "2. Création des triggers de synchronisation..."
mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < sites2/sql/llx_sites2_equipement_triggers.sql
if [ $? -eq 0 ]; then
    echo "   Triggers créés avec succès."
else
    echo "   ATTENTION: Des erreurs sont survenues lors de la création des triggers."
    echo "   Si l'erreur indique 'Duplicate trigger', cela signifie que les triggers existent déjà et vous pouvez ignorer cette erreur."
fi

echo "Installation terminée."
echo "Vous pouvez maintenant associer des équipements à vos sites dans le module sites2."
echo "Rappel: Les relations existantes devront être recréées manuellement." 