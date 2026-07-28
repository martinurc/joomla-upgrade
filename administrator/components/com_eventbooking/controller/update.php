<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

class EventbookingControllerUpdate extends RADController
{
	/**
	 * Update database schema when users update from old version to 1.6.4.
	 * We need to implement this function outside the installation script to avoid timeout during upgrade
	 */
	public function update()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/install.eventbooking.php';

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Create table if not exists
		com_eventbookingInstallerScript::createTablesIfNotExist();

		$config = EventbookingHelper::getConfig();

		//Registrants table
		$fields = array_keys($db->getTableColumns('#__eb_registrants'));

		if (!in_array('invoice_year', $fields))
		{
			$sql = "ALTER TABLE  `#__eb_registrants` ADD  `invoice_year` INT NOT NULL DEFAULT  '0';";
			$db->setQuery($sql)
				->execute();

			$sql = 'UPDATE #__eb_registrants SET `invoice_year` = YEAR(`register_date`)';
			$db->setQuery($sql)
				->execute();
		}

		$fields = array_keys($db->getTableColumns('#__eb_coupons'));

		if (!in_array('max_usage_per_user', $fields))
		{
			$sql = "ALTER TABLE  `#__eb_coupons` ADD  `max_usage_per_user` INT(11) NOT NULL DEFAULT '0';";
			$db->setQuery($sql)
				->execute();
		}

		// Custom fields table
		$fields = array_keys($db->getTableColumns('#__eb_fields'));

		if (!in_array('filterable', $fields))
		{
			$sql = "ALTER TABLE  `#__eb_fields` ADD  `filterable` TINYINT NOT NULL DEFAULT '0'";
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('hide_for_first_group_member', $fields))
		{
			$sql = "ALTER TABLE  `#__eb_fields` ADD  `hide_for_first_group_member` TINYINT NOT NULL DEFAULT  '0' ;";
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('not_required_for_first_group_member', $fields))
		{
			$sql = "ALTER TABLE  `#__eb_fields` ADD  `not_required_for_first_group_member` TINYINT NOT NULL DEFAULT  '0' ;";
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('show_on_public_registrants_list', $fields))
		{
			$sql = "ALTER TABLE  `#__eb_fields` ADD  `show_on_public_registrants_list` TINYINT NOT NULL DEFAULT  '0';";
			$db->setQuery($sql)
				->execute();

			$customFields = [1, 2];

			if (trim($config->registrant_list_custom_field_ids))
			{
				$customFields = explode(',', trim($config->registrant_list_custom_field_ids));
			}

			$query->clear()
				->select('custom_field_ids')
				->from('#__eb_events')
				->where('LENGTH(custom_field_ids) > 0');
			$db->setQuery($query);

			try
			{
				$eventFields = $db->loadColumn();
			}
			catch (Exception $e)
			{
				$eventFields = [];
			}

			foreach ($eventFields as $eventField)
			{
				if (trim($eventField))
				{
					$customFields = array_merge($customFields, explode(',', $eventField));
				}
			}

			$customFields = array_filter(ArrayHelper::toInteger($customFields));

			if (count($customFields))
			{
				$query->clear()
					->update('#__eb_fields')
					->set('show_on_public_registrants_list = 1')
					->where('id IN (' . implode(',', $customFields) . ')');
				$db->setQuery($query)
					->execute();
			}
		}

		// Update database schema to latest version
		com_eventbookingInstallerScript::synchronizeDBSchema();

		// Setup default data
		com_eventbookingInstallerScript::setupDefaultData();

		$config = EventbookingHelper::getConfig();

		if ($config->map_api_key == 'AIzaSyDIq19TVV4qOX2sDBxQofrWfjeA7pebqy4')
		{
			$sql = 'UPDATE #__eb_configs SET config_value="" WHERE config_key="map_api_key"';
			$db->setQuery($sql)
				->execute();
		}

		if (empty($config->invoice_format))
		{
			//Need to insert default data into the system
			$invoiceFormat = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_eventbooking/sql/invoice_format.html');

			if (property_exists($config, 'invoice_format'))
			{
				$sql = 'UPDATE #__eb_configs SET config_value = ' . $db->quote($invoiceFormat) . ' WHERE config_key="invoice_format"';
			}
			else
			{
				$sql = 'INSERT INTO #__eb_configs(config_key, config_value) VALUES ("invoice_format", ' . $db->quote($invoiceFormat) . ')';
			}

			$db->setQuery($sql)
				->execute();
		}

		if (empty($config->invoice_format_cart))
		{
			$invoiceFormat = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_eventbooking/sql/invoice_format_cart.html');

			if (property_exists($config, 'invoice_format_cart'))
			{
				$sql = 'UPDATE #__eb_configs SET config_value = ' . $db->quote($invoiceFormat) . ' WHERE config_key="invoice_format_cart"';
			}
			else
			{
				$sql = 'INSERT INTO #__eb_configs(config_key, config_value) VALUES ("invoice_format_cart", ' . $db->quote($invoiceFormat) . ')';
			}

			$db->setQuery($sql)
				->execute();
		}

		if (empty($config->certificate_layout))
		{
			//Need to insert default data into the system
			$invoiceFormat = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_eventbooking/sql/certificate_layout.html');

			if (property_exists($config, 'certificate_layout'))
			{
				$sql = 'UPDATE #__eb_configs SET config_value = ' . $db->quote($invoiceFormat) . ' WHERE config_key="certificate_layout"';
			}
			else
			{
				$sql = 'INSERT INTO #__eb_configs(config_key, config_value) VALUES ("certificate_layout", ' . $db->quote($invoiceFormat) . ')';
			}

			$db->setQuery($sql)
				->execute();
		}

		com_eventbookingInstallerScript::enableRequiredPlugin();

		if (Multilanguage::isEnabled())
		{
			EventbookingHelper::callOverridableHelperMethod('Helper', 'setupMultilingual');
		}

		// Insert deposit payment related messages
		$query->clear()
			->select('COUNT(*)')
			->from('#__eb_messages')
			->where('message_key = "deposit_payment_form_message"');
		$db->setQuery($query);
		$total = $db->loadResult();

		if (!$total)
		{
			$depositMessagesSql = JPATH_ADMINISTRATOR . '/components/com_eventbooking/sql/deposit.eventbooking.sql';

			EventbookingHelper::executeSqlFile($depositMessagesSql);
		}

		// Migrate speakers, sponsors data to new schema
		$query->clear()
			->select('COUNT(*)')
			->from('#__eb_event_speakers');
		$db->setQuery($query);
		$total = $db->loadResult();

		if (!$total)
		{
			$sql = 'INSERT INTO #__eb_event_speakers(event_id, speaker_id) SELECT event_id, id FROM #__eb_speakers';
			$db->setQuery($sql)
				->execute();
		}

		$query->clear()
			->select('COUNT(*)')
			->from('#__eb_event_sponsors');
		$db->setQuery($query);
		$total = $db->loadResult();

		if (!$total)
		{
			$sql = 'INSERT INTO #__eb_event_sponsors(event_id, sponsor_id) SELECT event_id, id FROM #__eb_sponsors';
			$db->setQuery($sql)
				->execute();
		}

		# Add index to improve the speed
		$this->createIndexes();

		// Redirect to dashboard view
		$installType = $this->input->getCmd('install_type', '');
		$app         = Factory::getApplication();

		if ($installType == 'install')
		{
			$msg = Text::_('The extension was successfully installed');
		}
		else
		{
			$msg = Text::_('The extension was successfully updated');
		}

		$app->enqueueMessage($msg);

		//Redirecting users to Dasboard
		$app->redirect('index.php?option=com_eventbooking&view=dashboard');
	}

	/**
	 * Create necessary indexes to speed up query process
	 *
	 * @return void
	 */
	protected function createIndexes()
	{
		$db  = Factory::getDbo();
		$sql = 'SHOW INDEX FROM #__eb_urls';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('md5_key', $fields))
		{
			$sql = 'CREATE INDEX `idx_md5_key` ON `#__eb_urls` (`md5_key`(32));';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_categories';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('parent', $fields))
		{
			$sql = 'CREATE INDEX `idx_parent` ON `#__eb_categories` (`parent`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('access', $fields))
		{
			$sql = 'CREATE INDEX `idx_access` ON `#__eb_categories` (`access`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('published', $fields))
		{
			$sql = 'CREATE INDEX `idx_published` ON `#__eb_categories` (`published`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('alias', $fields))
		{
			$sql = 'CREATE INDEX `idx_alias` ON `#__eb_categories` (`alias`(191));';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_events';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('location_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_location_id` ON `#__eb_events` (`location_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('parent_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_parent_id` ON `#__eb_events` (`parent_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('access', $fields))
		{
			$sql = 'CREATE INDEX `idx_access` ON `#__eb_events` (`access`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('published', $fields))
		{
			$sql = 'CREATE INDEX `idx_published` ON `#__eb_events` (`published`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('event_date', $fields))
		{
			$sql = 'CREATE INDEX `idx_event_date` ON `#__eb_events` (`event_date`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('event_end_date', $fields))
		{
			$sql = 'CREATE INDEX `idx_event_end_date` ON `#__eb_events` (`event_end_date`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('alias', $fields))
		{
			$sql = 'CREATE INDEX `idx_alias` ON `#__eb_events` (`alias`(191));';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_registrants';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('event_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_event_id` ON `#__eb_registrants` (`event_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('published', $fields))
		{
			$sql = 'CREATE INDEX `idx_published` ON `#__eb_registrants` (`published`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('first_name', $fields))
		{
			$sql = 'CREATE INDEX `idx_first_name` ON `#__eb_registrants` (`first_name`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('last_name', $fields))
		{
			$sql = 'CREATE INDEX `idx_last_name` ON `#__eb_registrants` (`last_name`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('email', $fields))
		{
			$sql = 'CREATE INDEX `idx_email` ON `#__eb_registrants` (`email`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('transaction_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_transaction_id` ON `#__eb_registrants` (`transaction_id`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_fields';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('access', $fields))
		{
			$sql = 'CREATE INDEX `idx_access` ON `#__eb_fields` (`access`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('published', $fields))
		{
			$sql = 'CREATE INDEX `idx_published` ON `#__eb_fields` (`published`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_field_values';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('registrant_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_registrant_id` ON `#__eb_field_values` (`registrant_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('field_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_field_id` ON `#__eb_field_values` (`field_id`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_field_events';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('field_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_field_id` ON `#__eb_field_events` (`field_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('event_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_event_id` ON `#__eb_field_events` (`event_id`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_field_categories';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('field_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_field_id` ON `#__eb_field_categories` (`field_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('category_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_category_id` ON `#__eb_field_categories` (`category_id`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_coupons';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('code', $fields))
		{
			$sql = 'CREATE INDEX `idx_code` ON `#__eb_coupons` (`code`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_coupon_events';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		for ($i = 0, $n = count($rows); $i < $n; $i++)
		{
			$row      = $rows[$i];
			$fields[] = $row->Column_name;
		}

		if (!in_array('event_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_event_id` ON `#__eb_coupon_events` (`event_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('coupon_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_coupon_id` ON `#__eb_coupon_events` (`coupon_id`);';
			$db->setQuery($sql)
				->execute();
		}

		$sql = 'SHOW INDEX FROM #__eb_event_categories';
		$db->setQuery($sql);
		$rows   = $db->loadObjectList();
		$fields = [];

		foreach ($rows as $row)
		{
			$fields[] = $row->Column_name;
		}

		if (!in_array('event_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_event_id` ON `#__eb_event_categories` (`event_id`);';
			$db->setQuery($sql)
				->execute();
		}

		if (!in_array('category_id', $fields))
		{
			$sql = 'CREATE INDEX `idx_category_id` ON `#__eb_event_categories` (`category_id`);';
			$db->setQuery($sql)
				->execute();
		}

		// Fix possible issue with categories data
		$sql = 'UPDATE #__eb_categories SET `parent` = 0 WHERE `parent` = `id`';
		$db->setQuery($sql)
			->execute();

		$sql = "ALTER TABLE  `#__eb_categories` CHANGE  `access` `access`  INT(11) NOT NULL DEFAULT '1'";
		$db->setQuery($sql)
			->execute();

		$sql = "ALTER TABLE  `#__eb_categories` CHANGE  `submit_event_access` `submit_event_access`  INT(11) NOT NULL DEFAULT '1'";
		$db->setQuery($sql)
			->execute();

		$sql = "ALTER TABLE  `#__eb_events` CHANGE  `access` `access`  INT(11) NOT NULL DEFAULT '1'";
		$db->setQuery($sql)
			->execute();

		$sql = "ALTER TABLE  `#__eb_events` CHANGE  `registration_access` `registration_access`  INT(11) NOT NULL DEFAULT '1'";
		$db->setQuery($sql)
			->execute();
	}

	/**
	 * Move events images from media folder to images folder to use media manage
	 *
	 * @throws Exception
	 */
	public function migrate_event_images()
	{
		$installType = $this->input->getCmd('install_type', '');

		if (!Folder::exists(JPATH_ROOT . '/images/com_eventbooking'))
		{
			Folder::create(JPATH_ROOT . '/images/com_eventbooking');
		}

		$db  = Factory::getDbo();
		$sql = 'SELECT thumb FROM #__eb_events WHERE thumb IS NOT NULL';
		$db->setQuery($sql);
		$thumbs = $db->loadColumn();

		if (count($thumbs))
		{
			$oldImagePath = JPATH_ROOT . '/media/com_eventbooking/images/';
			$newImagePath = JPATH_ROOT . '/images/com_eventbooking/';

			foreach ($thumbs as $thumb)
			{
				if ($thumb && file_exists($oldImagePath . $thumb))
				{
					File::copy($oldImagePath . $thumb, $newImagePath . $thumb);
				}
			}

			$sql = 'UPDATE #__eb_events SET `image` = CONCAT("images/com_eventbooking/", `thumb`) WHERE thumb IS NOT NULL';
			$db->setQuery($sql)
				->execute();
		}

		if ($installType == 'install')
		{
			$msg = Text::_('The extension was successfully installed');
		}
		else
		{
			$msg = Text::_('The extension was successfully updated');
		}

		$this->app->enqueueMessage($msg);

		//Redirecting users to Dasboard
		$this->app->redirect('index.php?option=com_eventbooking&view=dashboard');
	}

	/**
	 * Delete Files/Folders from old version
	 *
	 * @return void
	 */
	protected function deleteFilesFolders()
	{
		// Files, Folders clean up
		$deleteFiles = [
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/daylightsaving.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/controller/daylightsaving.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/controller.php',
			JPATH_ROOT . '/components/com_eventbooking/controller.php',
			JPATH_ROOT . '/components/com_eventbooking/helper/os_cart.php',
			JPATH_ROOT . '/components/com_eventbooking/helper/fields.php',
			JPATH_ROOT . '/components/com_eventbooking/helper/captcha.php',
			JPATH_ROOT . '/components/com_eventbooking/views/register/tmpl/group_member.php',
			JPATH_ROOT . '/components/com_eventbooking/views/waitinglist/tmpl/complete.php',
			JPATH_ROOT . '/components/com_eventbooking/models/waitinglist.php',
			JPATH_ROOT . '/components/com_eventbooking/ipn_logs.txt',
			JPATH_ROOT . '/modules/mod_eb_events/css/font.css',
			JPATH_ROOT . '/media/com_eventbooking/assets/css/themes/ocean.css',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/waitings.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/waiting.php',
			JPATH_ROOT . '/media/com_eventbooking/.htaccess',
			JPATH_ROOT . '/components/com_eventbooking/view/registrantlist/tmpl/default.mobile.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/categories/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/configuration/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/registrants/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/states/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/fields/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/message/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/plugins/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/countries/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/coupons/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/events/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/locations/tmpl/default.joomla3.php',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/config.json',
			// Layout files, removed from 3.7.0
			JPATH_ROOT . '/components/com_eventbooking/view/archive/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/archive/tmpl/table.php',
			JPATH_ROOT . '/components/com_eventbooking/view/calendar/tmpl/daily.php',
			JPATH_ROOT . '/components/com_eventbooking/view/calendar/tmpl/mini.php',
			JPATH_ROOT . '/components/com_eventbooking/view/calendar/tmpl/weekly.php',
			JPATH_ROOT . '/components/com_eventbooking/view/calendar/tmpl/table.php',
			JPATH_ROOT . '/components/com_eventbooking/view/calendar/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/cancel/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/cart/tmpl/default.mobile.php',
			JPATH_ROOT . '/components/com_eventbooking/view/cart/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/cart/tmpl/mini.mobile.php',
			JPATH_ROOT . '/components/com_eventbooking/view/cart/tmpl/mini.php',
			JPATH_ROOT . '/components/com_eventbooking/view/cart/tmpl/module.php',
			JPATH_ROOT . '/components/com_eventbooking/view/categories/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/categories/tmpl/events.php',
			JPATH_ROOT . '/components/com_eventbooking/view/category/tmpl/columns.php',
			JPATH_ROOT . '/components/com_eventbooking/view/category/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/category/tmpl/table.php',
			JPATH_ROOT . '/components/com_eventbooking/view/category/tmpl/timeline.php',
			JPATH_ROOT . '/components/com_eventbooking/view/complete/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_agendas.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_group_rates.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_location.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_plugins.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_share.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_social_buttons.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_speakers.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/default_sponsors.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form_discount_settings.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form_fields.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form_general.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form_group_rates.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form_misc.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/form_recurring_settings.php',
			JPATH_ROOT . '/components/com_eventbooking/view/event/tmpl/simple.php',
			JPATH_ROOT . '/components/com_eventbooking/view/events/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/events/tmpl/default_search_bar.bootstrap4.php',
			JPATH_ROOT . '/components/com_eventbooking/view/events/tmpl/default_search_bar.php',
			JPATH_ROOT . '/components/com_eventbooking/view/failure/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/fullcalendar/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/history/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/history/tmpl/default_search_bar.bootstrap4.php',
			JPATH_ROOT . '/components/com_eventbooking/view/history/tmpl/default_search_bar.php',
			JPATH_ROOT . '/components/com_eventbooking/view/invite/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/invite/tmpl/complete.php',
			JPATH_ROOT . '/components/com_eventbooking/view/location/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/location/tmpl/form.php',
			JPATH_ROOT . '/components/com_eventbooking/view/location/tmpl/popup.php',
			JPATH_ROOT . '/components/com_eventbooking/view/location/tmpl/table.php',
			JPATH_ROOT . '/components/com_eventbooking/view/location/tmpl/timeline.php',
			JPATH_ROOT . '/components/com_eventbooking/view/locations/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/massmail/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/password/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl/complete.php',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl/payment_amounts.php',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl/payment_javascript.php',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl/payment_methods.php',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl/registration.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/cart.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/cart_items.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/default_tickets.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/group.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/group_billing.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/group_members.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/number_members.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/register_gdpr.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/register_login.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/register_payment_amount.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/register_payment_methods.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/register_terms_and_conditions.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/register_user_registration.php',
			JPATH_ROOT . '/components/com_eventbooking/view/register/tmpl/tickets_members.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrant/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrantlist/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrants/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrants/tmpl/default_search_bar.bootstrap4.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrants/tmpl/default_search_bar.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrationcancel/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/registrationcancel/tmpl/confirmation.php',
			JPATH_ROOT . '/components/com_eventbooking/view/search/tmpl/columns.php',
			JPATH_ROOT . '/components/com_eventbooking/view/search/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/search/tmpl/table.php',
			JPATH_ROOT . '/components/com_eventbooking/view/search/tmpl/timeline.php',
			JPATH_ROOT . '/components/com_eventbooking/view/upcomingevents/tmpl/columns.php',
			JPATH_ROOT . '/components/com_eventbooking/view/upcomingevents/tmpl/default.php',
			JPATH_ROOT . '/components/com_eventbooking/view/upcomingevents/tmpl/table.php',
			JPATH_ROOT . '/components/com_eventbooking/view/upcomingevents/tmpl/timeline.php',
			JPATH_ROOT . '/components/com_eventbooking/view/waitinglist/tmpl/default.php',
		];

		$deleteFolders = [
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/vendor/PHPOffice',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/vendor/Respect',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/assets/chosen',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/models',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/views',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/daylightsaving',
			JPATH_ROOT . '/components/com_eventbooking/views/confirmation',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/waiting',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/waitings',
			JPATH_ROOT . '/components/com_eventbooking/models',
			JPATH_ROOT . '/components/com_eventbooking/assets',
			JPATH_ROOT . '/components/com_eventbooking/views',
			JPATH_ROOT . '/components/com_eventbooking/view/common',
			JPATH_ROOT . '/components/com_eventbooking/view/cancel/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/failure/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/invite/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/password/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/payment/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/registrant/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/registrationcancel/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/waitinglist/tmpl',
			JPATH_ROOT . '/components/com_eventbooking/view/emailtemplates',
			JPATH_ROOT . '/components/com_eventbooking/view/users',
			JPATH_ROOT . '/modules/mod_eb_events/css/font',
		];

		foreach ($deleteFiles as $file)
		{
			if (File::exists($file))
			{
				File::delete($file);
			}
		}

		foreach ($deleteFolders as $folder)
		{
			if (Folder::exists($folder))
			{
				Folder::delete($folder);
			}
		}
	}
}
