-- version: 6.3.1

CREATE TABLE IF NOT EXISTS `#__sppagebuilder_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `content` mediumtext,
  `css` longtext NOT NULL,
  `attribs` varchar(5120) NOT NULL DEFAULT '[]',
  `og_title` varchar(255) NOT NULL DEFAULT '',
  `og_image` varchar(255) NOT NULL DEFAULT '',
  `og_description` varchar(255) NOT NULL DEFAULT '',
  `note` text,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_on` datetime NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL DEFAULT '0',

  PRIMARY KEY (`id`),
  KEY `idx_page_id` (`page_id`),
  KEY `idx_page_created` (`page_id`, `created_on`),
  KEY `idx_active` (`active`),

  CONSTRAINT `fk_sppagebuilder_versions_page`
    FOREIGN KEY (`page_id`)
    REFERENCES `#__sppagebuilder` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;