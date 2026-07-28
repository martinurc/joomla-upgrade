CREATE TABLE IF NOT EXISTS `#__rsform_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `lang_code` varchar(16) NOT NULL,
  `reference` varchar(64) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `lang_code` (`lang_code`),
  KEY `reference` (`reference`),
  KEY `lang_search` (`form_id`,`lang_code`,`reference`)
) DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;