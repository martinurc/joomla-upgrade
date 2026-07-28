-- ==============================================================================
-- ACAVe Joomla Upgrade SQL Script
-- Execute this script on the production database after updating code files.
-- (Note: Replace `aca1_` with your database table prefix if different)
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

-- 3. Disable WebAuthn (Autenticación Web) system plugin
UPDATE `aca1_extensions`
SET `enabled` = 0
WHERE `element` = 'webauthn'
  AND `folder` = 'system'
  AND `type` = 'plugin';

-- 4. Enable "hideempty" parameter in Easy Profile (com_jsn) so empty profile fields are hidden
UPDATE `aca1_extensions`
SET `params` = REPLACE(`params`, '"hideempty":"0"', '"hideempty":"1"')
WHERE `element` = 'com_jsn'
  AND `type` = 'component';
