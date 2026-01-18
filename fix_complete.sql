-- ============================================
-- SCRIPT DE RÉPARATION COMPLÈTE
-- ============================================

-- 1. Afficher l'état actuel des utilisateurs
SELECT '=== ÉTAT ACTUEL DES UTILISATEURS ===' AS info;
SELECT id, username, bought_cpu, bought_memory, bought_disk, bought_slots, bought_databases, bought_backups, coins 
FROM users;

-- 2. Restaurer les ressources pour TOUS les utilisateurs
SELECT '=== RESTAURATION DES RESSOURCES ===' AS info;
UPDATE users 
SET bought_cpu = 100, 
    bought_memory = 2048, 
    bought_disk = 4096, 
    bought_slots = 1, 
    bought_databases = 2, 
    bought_backups = 2;

-- 3. Vérifier après mise à jour
SELECT '=== APRÈS RESTAURATION ===' AS info;
SELECT id, username, bought_cpu, bought_memory, bought_disk, bought_slots, bought_databases, bought_backups, coins 
FROM users;

-- 4. Vérifier les limites admin dans settings
SELECT '=== LIMITES ADMIN ===' AS info;
SELECT * FROM settings WHERE `key` LIKE 'store:%';

-- 5. Si les limites n'existent pas, les créer
INSERT INTO settings (`key`, value) VALUES ('store:limit_cpu', '100') 
ON DUPLICATE KEY UPDATE value = '100';

INSERT INTO settings (`key`, value) VALUES ('store:limit_memory', '4096') 
ON DUPLICATE KEY UPDATE value = '4096';

INSERT INTO settings (`key`, value) VALUES ('store:limit_disk', '10240') 
ON DUPLICATE KEY UPDATE value = '10240';

INSERT INTO settings (`key`, value) VALUES ('store:limit_databases', '5') 
ON DUPLICATE KEY UPDATE value = '5';

INSERT INTO settings (`key`, value) VALUES ('store:limit_backups', '5') 
ON DUPLICATE KEY UPDATE value = '5';

-- 6. Vérifier les produits de la boutique
SELECT '=== PRODUITS DE LA BOUTIQUE ===' AS info;
SELECT * FROM store_products;

SELECT '=== RÉPARATION TERMINÉE ! ===' AS info;
