#!/bin/bash

# Script pour tout réparer d'un coup

echo "=== Restauration des ressources utilisateur ==="

# Se connecter à MySQL et exécuter les requêtes
mysql -u panel -p'Panel@2024' panel << EOF

-- Afficher l'état actuel
SELECT id, username, bought_cpu, bought_memory, bought_disk, bought_slots, bought_databases, bought_backups FROM users;

-- Restaurer les ressources par défaut
UPDATE users 
SET bought_cpu = 100, 
    bought_memory = 2048, 
    bought_disk = 4096, 
    bought_slots = 1, 
    bought_databases = 2, 
    bought_backups = 2
WHERE bought_cpu = 0 AND bought_memory = 0;

-- Afficher le résultat
SELECT id, username, bought_cpu, bought_memory, bought_disk, bought_slots, bought_databases, bought_backups FROM users;

-- Vérifier les limites admin
SELECT * FROM settings WHERE \`key\` LIKE 'store:limit%';

EOF

echo "=== Terminé ! ==="
