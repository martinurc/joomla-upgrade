
CREATE TABLE IF NOT EXISTS `#__jsn_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lft` int(11) NOT NULL DEFAULT '0',
  `rgt` int(11) NOT NULL DEFAULT '0',
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `note` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',

  `type` varchar(255) NOT NULL,
  `core` tinyint(1) NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `profile` tinyint(1) NOT NULL DEFAULT '0',
  `edit` tinyint(1) NOT NULL DEFAULT '0',
  `editbackend` tinyint(1) NOT NULL DEFAULT '1',
  `register` tinyint(1) NOT NULL DEFAULT '0',
  `search` tinyint(1) NOT NULL DEFAULT '0',

  `checked_out` int(11) unsigned NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `accessview` int(10) unsigned NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  `conditions` text NOT NULL,
  `metadesc` varchar(1024) NOT NULL COMMENT 'The meta description for the page.',
  `metakey` varchar(1024) NOT NULL COMMENT 'The meta keywords for the page.',
  `metadata` varchar(2048) NOT NULL COMMENT 'JSON encoded metadata properties.',
  `created_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `modified_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `modified_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `images` text NOT NULL,
  `urls` text NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `language` char(7) NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__jsn_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `privacy` TEXT NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `secondname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `params` TEXT NOT NULL,
  `facebook_id` varchar(255) NOT NULL,
  `twitter_id` varchar(255) NOT NULL,
  `google_id` varchar(255) NOT NULL,
  `linkedin_id` varchar(255) NOT NULL,
  `instagram_id` varchar(255) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO #__jsn_fields(`id`,`parent_id`,`level`,`title`,`alias`,`path`,`type`,`published`,`core`,`required`,`profile`,`register`,`lft`,`rgt`,`access`,`params`,`edit`,`accessview`) VALUES
	(1,0,0,'ROOT','root','','',1,0,0,0,0,  0,21,1, '', 0,1),
	(2,1,1,'DETAILS','default','default','',1,1,0,0,0,  1,20,1, '', 0,1),
	(3,2,2,'USERNAME','username','default/username','username',1,1,1,1,1,  2,3,1, '', 1,1),
	(4,2,2,'NAME','firstname','default/firstname','text',1,1,2,1,1,  4,5,1, '', 1,1),
	(5,2,2,'SECONDNAME','secondname','default/secondname','text',0,1,0,1,1,  6,7,1, '', 1,1),
	(6,2,2,'LASTNAME','lastname','default/lastname','text',1,1,2,1,1,  8,9,1, '', 1,1),
	(7,2,2,'EMAIL','email','default/email','usermail',1,1,1,1,1,  10,11,1, '', 1,1),
	(8,2,2,'PASSWORD','password','default/password','password',1,1,1,1,1,  12,13,1, '', 1,1),
	(9,2,2,'AVATAR','avatar','default/avatar','image',1,1,0,1,1,  14,15,1,'{"image_width":500,"image_height":500,"image_thumbwidth":100,"image_thumbheight":100,"image_alt":"Avatar","image_class":""}', 1,1),
	(10,2,2,'REGISTEREDDATE','registerdate','default/registerdate','registerdate',1,1,0,1,0,  16,17,1, '', 0,1),
	(11,2,2,'LASTVISITDATE','lastvisitdate','default/lastvisitdate','lastvisitdate',1,1,0,1,0,  18,19,1, '', 0,1);
	
UPDATE #__extensions SET ordering=1 WHERE name LIKE 'plg_authentication_joomla' OR name LIKE 'plg_user_profile';
