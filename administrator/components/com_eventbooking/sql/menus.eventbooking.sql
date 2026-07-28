TRUNCATE TABLE `#__eb_menus`;
INSERT INTO `#__eb_menus` (`id`, `menu_name`, `menu_parent_id`, `menu_link`, `published`, `ordering`, `menu_class`) VALUES
(1, 'EB_DASHBOARD', 0, 'index.php?option=com_eventbooking&view=dashboard', 1, 1, 'home'),

(2, 'EB_SETUP', 0, NULL, 1, 2, 'list-view'),
(3, 'EB_CATEGORIES', 2, 'index.php?option=com_eventbooking&view=categories', 1, 1, 'folder-open'),
(5, 'EB_CUSTOM_FIELDS', 2, 'index.php?option=com_eventbooking&view=fields', 1, 2, 'list'),
(6, 'EB_LOCATIONS', 2, 'index.php?option=com_eventbooking&view=locations', 1, 3, 'location'),
(15, 'EB_PAYMENT_PLUGINS', 2, 'index.php?option=com_eventbooking&view=plugins', 1, 4, 'wrench'),
(37, 'EB_TAX_RULES', 2, 'index.php?option=com_eventbooking&view=taxes', 1, 5, 'location'),
(16, 'EB_EMAIL_MESSAGES', 2, 'index.php?option=com_eventbooking&view=mitems', 1, 6, 'envelope'),
(40, 'EB_TICKET_TYPES', 2, 'index.php?option=com_eventbooking&view=tickettypes', 1, 7, 'tags'),
(34, 'EB_SPEAKERS', 2, 'index.php?option=com_eventbooking&view=speakers', 1, 8, 'user'),
(35, 'EB_SPONSORS', 2, 'index.php?option=com_eventbooking&view=sponsors', 1, 9, 'user'),
(41, 'EB_AGENDAS', 2, 'index.php?option=com_eventbooking&view=agendas', 1, 10, 'book'),
(23,'EB_DISCOUNT_BUNDLES', 2, 'index.php?option=com_eventbooking&view=discounts', 1, 11, 'tags'),
(7, 'EB_COUNTRIES', 2, 'index.php?option=com_eventbooking&view=countries', 1, 12, 'flag'),
(8, 'EB_STATES', 2, 'index.php?option=com_eventbooking&view=states', 1, 13, 'book'),
(33, 'EB_THEMES', 2, 'index.php?option=com_eventbooking&view=themes', 1, 14, 'tablet'),
(39, 'EB_EXPORT_TEMPLATES', 2, 'index.php?option=com_eventbooking&view=exporttmpls', 1, 15, 'tablet'),
(42, 'EB_MASS_MAIL_TEMPLATES', 2, 'index.php?option=com_eventbooking&view=mmtemplates', 1, 16, 'tablet'),

(24, 'EB_EVENTS', 0, NULL, 1, 3, 'calendar'),
(25, 'EB_EVENTS', 24, 'index.php?option=com_eventbooking&view=events', 1, 1, 'calendar'),
(26, 'EB_IMPORT', 24, 'index.php?option=com_eventbooking&view=event&layout=import', 1, 2, 'upload'),
(27, 'EB_EXPORT', 24, 'index.php?option=com_eventbooking&task=event.export', 1, 3, 'download'),

(9, 'EB_REGISTRANTS', 0, NULL, 1, 4, 'user'),
(28, 'EB_REGISTRANTS', 9, 'index.php?option=com_eventbooking&view=registrants', 1, 1, 'user'),
(29, 'EB_IMPORT_TEMPLATE', 9, 'index.php?option=com_eventbooking&task=registrant.import_template', 1, 2, 'list'),
(30, 'EB_IMPORT', 9, 'index.php?option=com_eventbooking&view=registrant&layout=import', 1, 3, 'upload'),
(31, 'EB_EXPORT', 9, 'index.php?option=com_eventbooking&task=registrant.export', 1, 4, 'download'),

(10, 'EB_COUPONS', 0, NULL, 1, 5, 'tags'),
(11, 'EB_COUPONS', 10, 'index.php?option=com_eventbooking&view=coupons', 1, 1, 'tags'),
(12, 'EB_IMPORT', 10, 'index.php?option=com_eventbooking&view=coupon&layout=import', 1, 2, 'upload'),
(13, 'EB_EXPORT', 10, 'index.php?option=com_eventbooking&task=coupon.export', 1, 3, 'download'),
(14, 'EB_BATCH', 10, 'index.php?option=com_eventbooking&view=coupon&layout=batch', 1, 4, 'list'),

(17, 'EB_TRANSLATION', 0, 'index.php?option=com_eventbooking&view=language', 1, 8, 'flag'),
(18, 'EB_CONFIGURATION', 0, 'index.php?option=com_eventbooking&view=configuration', 1, 9, 'cog'),

(19, 'EB_TOOLS', 0, NULL, 1, 10, 'tools'),
(20, 'EB_PURGE_URLS', 19, 'index.php?option=com_eventbooking&task=tool.reset_urls', 1, 1, 'refresh'),
(21, 'EB_FIX_DATABASE', 19, 'index.php?option=com_eventbooking&task=update.update', 1, 2, 'ok'),
(32, 'EB_EMAILS_LOG', 19, 'index.php?option=com_eventbooking&view=emails', 1, 3, 'envelope'),
(38, 'EB_LEGACY_EMAIL_MESSAGES', 19, 'index.php?option=com_eventbooking&view=message', 1, 4, 'envelope'),
(22, 'EB_SHARE_TRANSLATION', 19, 'index.php?option=com_eventbooking&task=tool.share_translation', 1, 5, 'heart'),
(36, 'Download MPDF Fonts', 19, 'index.php?option=com_eventbooking&task=tool.download_mpdf_font', 1, 6, 'download');