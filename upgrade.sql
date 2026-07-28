-- ==============================================================================
-- ACAVe Joomla 4 Upgrade SQL Script
-- Execute this script on the production database after updating code files.
-- ==============================================================================

-- 1. Enable Easy Profile (com_jsn) plugins required for the Members Area
UPDATE `aca1_extensions`
SET `enabled` = 1
WHERE `element` IN ('jsn_system', 'jsn_author', 'jsn_users')
  AND `type` = 'plugin';

-- 2. Fix target article ID for Menu Item #401 (Documentos)
UPDATE `aca1_menu`
SET `link` = 'index.php?option=com_content&view=article&id=27'
WHERE `id` = 401;
