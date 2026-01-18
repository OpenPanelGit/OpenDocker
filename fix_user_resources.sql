-- Fix user resources that were reset to 0
-- This gives default resources to users who had them reset

UPDATE users 
SET 
    bought_cpu = 100,
    bought_memory = 2048,
    bought_disk = 4096,
    bought_slots = 1,
    bought_databases = 2,
    bought_backups = 2
WHERE 
    bought_cpu = 0 
    AND bought_memory = 0 
    AND bought_disk = 0 
    AND bought_slots = 0;
