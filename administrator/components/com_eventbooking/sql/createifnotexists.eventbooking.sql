CREATE TABLE IF NOT EXISTS `#__eb_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(255) DEFAULT NULL,
  `menu_parent_id` int(11) DEFAULT NULL,
  `menu_link` varchar(255) DEFAULT NULL,
  `published` tinyint(1) unsigned DEFAULT NULL,
  `ordering` int(11) DEFAULT NULL,
  `menu_class` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_taxes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int UNSIGNED DEFAULT 0,
  `event_id` int UNSIGNED DEFAULT 0,
  `country` varchar(255) NOT NULL DEFAULT '',
  `state` varchar(255) NOT NULL DEFAULT '',
  `rate` decimal(10,2) DEFAULT '0.00',
  `vies` tinyint UNSIGNED DEFAULT 0,
  `published` tinyint UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_discounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `number_events` int NOT NULL DEFAULT 0,
  `event_ids` tinytext,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` tinyint NOT NULL DEFAULT 1,
  `from_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `to_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `times` int NOT NULL DEFAULT 0,
  `used` int NOT NULL DEFAULT 0,
  `published` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_discount_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `discount_id` int NOT NULL DEFAULT 0,
  `event_id` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_discount_id` (`discount_id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_emails` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email_type` varchar(50) NOT NULL DEFAULT '',
  `sent_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sent_to` tinyint NOT NULL DEFAULT 0,
  `email` varchar(100) DEFAULT 0,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_ticket_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `discount_rules` text,
  `price` decimal(10,2) DEFAULT 0.00,
  `capacity` int DEFAULT 0,
  `weight` int NOT NULL DEFAULT 1,
  `max_tickets_per_booking` int NOT NULL DEFAULT 0,
  `parent_ticket_type_id` int NOT NULL DEFAULT 0,
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int NOT NULL DEFAULT 1,
  `ordering` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_registrant_tickets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `registrant_id` int DEFAULT 0,
  `ticket_type_id` int DEFAULT 0,
  `quantity` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_registrant_id` (`registrant_id`),
  KEY `idx_ticket_type_id` (`ticket_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_field_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int DEFAULT 0,
  `category_id` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_field_id` (`field_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_coupon_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int DEFAULT 0,
  `event_id` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `message_key` varchar(50) NOT NULL DEFAULT '',
  `message` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_urls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `md5_key` varchar(32) NOT NULL DEFAULT '',
  `query` text,
  `route` varchar(400) NOT NULL DEFAULT '',
  `view` varchar(15) NOT NULL DEFAULT '',
  `record_id` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_md5_key` (`md5_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_coupon_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int DEFAULT 0,
  `category_id` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_speakers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `url` varchar(255) NOT NULL DEFAULT '',
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_sponsors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `website` varchar(255) NOT NULL DEFAULT '',
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_agendas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED DEFAULT 0,
  `time` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `ordering` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_themes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `author` varchar(255) NOT NULL DEFAULT '',
  `creation_date` varchar(50) NOT NULL DEFAULT '',
  `copyright` varchar(255) NOT NULL DEFAULT '',
  `license` varchar(255) NOT NULL DEFAULT '',
  `author_email` varchar(50) NOT NULL DEFAULT '',
  `author_url` varchar(50) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '',
  `description` text,
  `params` text,
  `ordering` int DEFAULT 0,
  `published` tinyint UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_galleries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int UNSIGNED DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT '',
  `ordering` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_event_speakers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int DEFAULT 0,
  `speaker_id` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_speaker_id` (`speaker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_event_sponsors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int DEFAULT 0,
  `sponsor_id` int DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_sponsor_id` (`sponsor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_mitems` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NULL,
  `title` varchar(255) NULL,
  `title_en` varchar(400) NULL,
  `description` varchar(255) NULL,
  `type` varchar(255) NULL,
  `group` varchar(255) NULL,
  `translatable` tinyint UNSIGNED DEFAULT 1,
  `featured` tinyint UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_exporttmpls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `fields` text,
  `ordering` int DEFAULT 0,
  `published` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_mmtemplates` (
	`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
	`title` varchar(255) NOT NULL DEFAULT '',
	`message` text,
	`ordering` int NOT NULL DEFAULT 0,
	`published` tinyint NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `#__eb_accesstokens` (
	`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
	`token` varchar(400) NOT NULL DEFAULT '',
	`expire_at` bigint UNSIGNED NOT NULL DEFAULT 0,
	`vendor` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;