DELETE FROM `#__rsform_component_types` WHERE `ComponentTypeId` IN (2424);

INSERT IGNORE INTO `#__rsform_component_types` (`ComponentTypeId`, `ComponentTypeName`, `CanBeDuplicated`) VALUES
(2424, 'recaptchav2', 0);

INSERT IGNORE INTO `#__rsform_config` (`SettingName`, `SettingValue`) VALUES
('recaptchav2.site.key', ''),
('recaptchav2.secret.key', ''),
('recaptchav2.language', 'auto'),
('recaptchav2.noscript', '1'),
('recaptchav2.asyncdefer', '0'),
('recaptchav2.domain', 'google.com');

DELETE FROM `#__rsform_component_type_fields` WHERE ComponentTypeId = 2424;

INSERT IGNORE INTO `#__rsform_component_type_fields` (`ComponentTypeId`, `FieldName`, `FieldType`, `FieldValues`, `Properties`, `Ordering`) VALUES
(2424, 'NAME', 'textbox', '', '', 0),
(2424, 'CAPTION', 'textbox', '', '', 1),
(2424, 'ADDITIONALATTRIBUTES', 'textarea', '', '', 2),
(2424, 'DESCRIPTION', 'textarea', '', '', 3),
(2424, 'VALIDATIONMESSAGE', 'textarea', 'INVALIDINPUT', '', 4),
(2424, 'THEME', 'select', 'LIGHT\r\nDARK', '', 5),
(2424, 'TYPE', 'select', 'IMAGE\r\nAUDIO', '', 6),
(2424, 'SIZE', 'select', 'NORMAL\r\nCOMPACT\r\nINVISIBLE', '{"case":{"INVISIBLE":{"show":["BADGE"],"hide":[]},"NORMAL":{"show":[],"hide":["BADGE"]},"COMPACT":{"show":[],"hide":["BADGE"]}}}', 7),
(2424, 'BADGE', 'select', 'INLINE\r\nBOTTOMRIGHT\r\nBOTTOMLEFT', '', 8),
(2424, 'COMPONENTTYPE', 'hidden', '2424', '', 8);