<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\Archive\Archive;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class EventbookingControllerTool extends RADController
{
	public function display_root_path()
	{
		if (Factory::getUser()->authorise('core.admin'))
		{
			echo JPATH_ROOT;
		}
		else
		{
			echo 'You do not have permission to view JPPATH_ROOT';
		}
	}

	public function delete_all_registrants()
	{
		$user = Factory::getUser();

		if (!$user->authorise('core.admin'))
		{
			throw new Exception('You do not have permission to delete all registrants');
		}

		$db = Factory::getDbo();
		$db->setQuery('TRUNCATE TABLE #__eb_field_values')
			->execute();

		$db->setQuery('TRUNCATE TABLE #__eb_registrants')
			->execute();
	}

	public function delete_orphans_registrant()
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->delete('#__eb_registrants')
			->where('event_id NOT IN (SELECT id FROM #__eb_events)');
		$db->setQuery($query)
			->execute();
	}

	public function generate_formatted_invoice_number()
	{
		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('invoice_number > 0');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$invoiceNumber = EventbookingHelper::callOverridableHelperMethod('Helper', 'formatInvoiceNumber', [$row->invoice_number, $config, $row]);
			$query->clear()
				->update('#__eb_registrants')
				->set('formatted_invoice_number = ' . $db->quote($invoiceNumber))
				->where('id = ' . $row->id);
			$db->setQuery($query)
				->execute();
		}

		echo 'Successfully Generating Formatted Invoice Number';
	}

	public function download_and_set_font()
	{
		$font = $this->input->getString('font');

		$fontPackageUrl = 'https://joomdonation.com/tcpdf/fonts.zip';

		$fontFile = InstallerHelper::downloadPackage($fontPackageUrl, 'fonts.zip');

		if ($fontFile === false)
		{
			echo Text::_('The requested font could not be downloaded');

			return;
		}

		$tmpPath = $this->app->get('tmp_path');

		if (!Folder::exists($tmpPath))
		{
			$tmpPath = JPATH_ROOT . '/tmp';
		}

		$fontPackage = $tmpPath . '/fonts.zip';

		$extractDir = JPATH_ROOT . '/components/com_eventbooking/tcpdf/fonts';

		if (EventbookingHelper::isJoomla4())
		{
			$archive = new Archive(['tmp_path' => $tmpPath]);
			$result  = $archive->extract($fontPackage, $extractDir);
		}
		else
		{
			$result = JArchive::extract($fontPackage, $extractDir);
		}

		if (!$result)
		{
			echo 'Error extract font package';

			return;
		}

		// Delete the downloaded zip file
		File::delete($fontPackage);

		if ($font)
		{
			// Now, set font to that font
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->update('#__eb_configs')
				->set('config_value = ' . $db->quote($font))
				->set('config_key = "pdf_font"');
			$db->setQuery($query)
				->execute();

			echo 'Font was successfully downloaded and set for PDF Font config option';
		}
		else
		{
			echo 'Fonts were successfully downloaded and extracted';
		}
	}

	public function remove_wrong_group_registration_records()
	{
		$cids    = [];
		$eventId = $this->input->getInt('event_id', 0);
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true)
			->select('id, number_registrants')
			->from('#__eb_registrants')
			->where('is_group_billing = 1')
			->where('(published = 1 OR payment_method LIKE "os_offline%")');

		if ($eventId > 0)
		{
			$query->where('event_id = ' . $eventId);
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$query->clear()
				->select('COUNT(*)')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$total = $db->loadResult();

			if ($row->number_registrants != $total)
			{
				$cids[] = $row->id;
			}
		}

		if (count($cids))
		{
			/* @var EventbookingModelRegistrant $model */
			$model = $this->getModel('Registrant');
			$model->delete($cids);
		}

		echo sprintf('Deleted %s registrations', count($cids));
	}

	/**
	 * Method to trigger active events for registrations from one or all events
	 *
	 * @return void
	 */
	public function trigger_active_event()
	{
		$eventId = $this->input->getInt('event_id', 0);
		$plugin  = $this->input->getString('plugin', null);

		PluginHelper::importPlugin('eventbooking', $plugin);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__eb_registrants')
			->where('published = 1')
			->where('group_id = 0')
			->order('id');

		if ($eventId)
		{
			$query->where('event_id = ' . $eventId);
		}

		$db->setQuery($query);

		foreach ($db->loadColumn() as $id)
		{
			$row = new EventbookingTableRegistrant($db);
			$row->load($id);
			$this->app->triggerEvent('onAfterPaymentSuccess', [$row]);
		}

		echo 'Trigger Completed';
	}

	public function remove_orphant_group_member_records()
	{
		$eventId = $this->input->getInt('event_id', 0);
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true)
			->delete('#__eb_registrants')
			->where('group_id > 0')
			->where('group_id NOT IN (SELECT id FROM #__eb_registrants WHERE is_group_billing = 1)');

		if ($eventId > 0)
		{
			$query->where('event_id = ' . $eventId);
		}

		$db->setQuery($query)
			->execute();

		echo $db->getAffectedRows();
	}

	public function find_missing_group_members_registration()
	{
		$eventId = $this->input->getInt('event_id', 0);
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true)
			->select('id, number_registrants')
			->from('#__eb_registrants')
			->where('is_group_billing = 1')
			->where('(published = 1 OR payment_method LIKE "os_offline%")');

		if ($eventId > 0)
		{
			$query->where('event_id = ' . $eventId);
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$query->clear()
				->select('COUNT(*)')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$total = $db->loadResult();

			if ($row->number_registrants != $total)
			{
				echo $row->id . '<br />';
			}
		}

		echo 'Done';
	}

	/**
	 * Reset locations data for events
	 *
	 * @return void
	 */
	public function fix_location_id_for_events()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->update('#__eb_events')
			->set('location_id = 0')
			->where('location_id NOT IN (SELECT id FROM #__eb_locations)');
		$db->setQuery($query)
			->execute();
	}

	/**
	 * Reset the urls table
	 */
	public function reset_urls()
	{
		Factory::getDbo()->truncateTable('#__eb_urls');
		$this->setRedirect('index.php?option=com_eventbooking&view=dashboard', Text::_('Urls have successfully reset'));
	}

	/**
	 * Setup multilingual fields
	 */
	public function setup_multilingual_fields()
	{
		EventbookingHelper::setupMultilingual();
	}

	/**
	 * Remove multilingual fields
	 */
	public function remove_multilingual()
	{
		$db = Factory::getDbo();

		$categoryTableFields = array_keys($db->getTableColumns('#__eb_categories'));
		$eventTableFields    = array_keys($db->getTableColumns('#__eb_events'));
		$fieldTableFields    = array_keys($db->getTableColumns('#__eb_fields'));
		$locationTableFields = array_keys($db->getTableColumns('#__eb_locations'));

		$suffixes = ['_fr', '_vi', '_pt', '_es-co', '_es', '_ms', '_ko', '_ja'];

		$fields = [
			'name',
			'alias',
			'page_title',
			'page_heading',
			'meta_keywords',
			'meta_description',
			'description',
		];

		foreach ($fields as $field)
		{
			foreach ($suffixes as $suffix)
			{
				$fieldName = $field . $suffix;

				if (in_array($fieldName, $categoryTableFields))
				{
					// Drop the field
					$sql = "ALTER TABLE  `#__eb_categories` DROP  `$fieldName`";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields = [
			'title',
			'alias',
			'page_title',
			'page_heading',
			'meta_keywords',
			'meta_description',
			'price_text',
			'registration_handle_url',
			'short_description',
			'description',
			'registration_form_message',
			'registration_form_message_group',
			'admin_email_body',
			'user_email_body',
			'user_email_body_offline',
			'thanks_message',
			'thanks_message_offline',
			'registration_approved_email_body',
			'invoice_format',
			'ticket_layout',
		];

		foreach ($fields as $field)
		{
			foreach ($suffixes as $suffix)
			{
				$fieldName = $field . $suffix;

				if (in_array($fieldName, $eventTableFields))
				{
					// Drop the field
					$sql = "ALTER TABLE  `#__eb_events` DROP  `$fieldName`";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields = [
			'title',
			'description',
			'values',
			'default_values',
			'depend_on_options',
			'place_holder',
		];

		foreach ($fields as $field)
		{
			foreach ($suffixes as $suffix)
			{
				$fieldName = $field . $suffix;

				if (in_array($fieldName, $fieldTableFields))
				{
					// Drop the field
					$sql = "ALTER TABLE  `#__eb_fields` DROP  `$fieldName`";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields = [
			'name',
			'alias',
			'description',
		];

		foreach ($fields as $field)
		{
			foreach ($suffixes as $suffix)
			{
				$fieldName = $field . $suffix;

				if (in_array($fieldName, $locationTableFields))
				{
					// Drop the field
					$sql = "ALTER TABLE  `#__eb_locations` DROP  `$fieldName`";
					$db->setQuery($sql)
						->execute();
				}
			}
		}
	}

	/**
	 * Add more decimal number to price related fields
	 */
	public function add_more_decimal_numbers()
	{
		$db = Factory::getDbo();

		$fieldsToChange = [
			'#__eb_events'             => ['individual_price', 'discount', 'early_bird_discount_amount', 'late_fee_amount', 'tax_rate'],
			'#__eb_event_group_prices' => ['price'],
			'#__eb_ticket_types'       => ['price'],
			'#__eb_coupons'            => ['discount'],
			'#__eb_discounts'          => ['discount_amount'],
			'#__eb_registrants'        => ['total_amount', 'discount_amount', 'amount', 'tax_amount', 'deposit_amount', 'tax_rate'],
		];

		foreach ($fieldsToChange as $table => $fields)
		{
			$table = $db->quoteName($table);

			foreach ($fields as $field)
			{
				$field = $db->quoteName($field);
				$sql   = "ALTER TABLE  $table  CHANGE  $field $field  DECIMAL (15,8)";
				$db->setQuery($sql)
					->execute();
			}
		}

		echo 'Done';
	}

	/**
	 * Method to allow sharing language files for Events Booking
	 */
	public function share_translation()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('lang_code')
			->from('#__languages')
			->where('published = 1')
			->where('lang_code != "en-GB"')
			->order('ordering');
		$db->setQuery($query);
		$languages = $db->loadObjectList();

		if (count($languages))
		{
			$mailer   = Factory::getMailer();
			$app      = Factory::getApplication();
			$jConfig  = Factory::getConfig();
			$mailFrom = $app->get('mailfrom');
			$fromName = $app->get('fromname');
			$mailer->setSender([$mailFrom, $fromName]);
			$mailer->addRecipient('tuanpn@joomdonation.com');
			$mailer->setSubject('Language Packages for Events Booking shared by ' . Uri::root());
			$mailer->setBody('Dear Tuan \n. I am happy to share my language packages for Events Booking.\n Enjoy!');
			foreach ($languages as $language)
			{
				$tag = $language->lang_code;
				if (file_exists(JPATH_ROOT . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini'))
				{
					$mailer->addAttachment(JPATH_ROOT . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini', $tag . '.com_eventbooking.ini');
				}

				if (file_exists(JPATH_ADMINISTRATOR . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini'))
				{
					echo JPATH_ADMINISTRATOR . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini';
					$mailer->addAttachment(
						JPATH_ADMINISTRATOR . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini',
						'admin.' . $tag . '.com_eventbooking.ini'
					);
				}
			}

			require_once JPATH_COMPONENT . '/libraries/vendor/dbexporter/dumper.php';

			$tables = [$db->replacePrefix('#__eb_fields'), $db->replacePrefix('#__eb_messages')];

			try
			{
				$sqlFile = $tag . '.com_eventbooking.sql';
				$options = [
					'host'           => $app->get('host'),
					'username'       => $app->get('user'),
					'password'       => $app->get('password'),
					'db_name'        => $app->get('db'),
					'include_tables' => $tables,
				];
				$dumper  = Shuttle_Dumper::create($options);
				$dumper->dump(JPATH_ROOT . '/tmp/' . $sqlFile);

				$mailer->addAttachment(JPATH_ROOT . '/tmp/' . $sqlFile, $sqlFile);
			}
			catch (Exception $e)
			{
				//Do nothing
			}

			$mailer->Send();

			$msg = 'Thanks so much for sharing your language files to Events Booking Community';
		}
		else
		{
			$msg = 'Thanks so willing to share your language files to Events Booking Community. However, you don"t have any none English langauge file to share';
		}

		$this->setRedirect('index.php?option=com_eventbooking&view=dashboard', $msg);
	}

	/**
	 * Method to make a given field search and sortable easier
	 */
	public function make_field_search_sort_able()
	{
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$fieldId = $this->input->getInt('field_id');

		$query->select('*')
			->from('#__eb_fields')
			->where('id = ' . (int) $fieldId);
		$db->setQuery($query);
		$field = $db->loadObject();

		if (!$field)
		{
			throw new Exception('The field does not exist');
		}

		// Add new field to #__eb_registrants
		$fields = array_keys($db->getTableColumns('#__eb_registrants'));

		if (!in_array($field->name, $fields))
		{
			$sql = "ALTER TABLE  `#__eb_registrants` ADD  `$field->name` VARCHAR( 255 ) NULL;";
			$db->setQuery($sql);
			$db->execute();
		}

		// Mark the field as searchable
		$query->clear()
			->update('#__eb_fields')
			->set('is_searchable = 1')
			->where('id = ' . (int) $fieldId);
		$db->setQuery($query);
		$db->execute();

		$query->clear()
			->select('*')
			->from('#__eb_field_values')
			->where('field_id = ' . $fieldId);
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$fieldName = $db->quoteName($field->name);

		foreach ($rows as $row)
		{
			$query->clear()
				->update('#__eb_registrants')
				->set($fieldName . ' = ' . $db->quote($row->field_value))
				->where('id = ' . $row->registrant_id);
			$db->setQuery($query);
			$db->execute();
		}

		echo 'Done !';
	}

	/**
	 * Resize large event image to the given size
	 */
	public function resize_large_images()
	{
		$start = $this->input->getInt('start', 0);

		$config = EventbookingHelper::getConfig();
		$width  = (int) $config->large_image_width ?: 800;
		$height = (int) $config->large_image_height ?: 600;

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('image')
			->from('#__eb_events')
			->where('published = 1')
			->order('id DESC')
			->where('LENGTH(image) > 0');
		$db->setQuery($query, $start, 100);
		$images = $db->loadColumn();

		if (count($images) == 0)
		{
			$this->setRedirect('index.php?option=com_eventbooking&view=events', 'Large Images were successfully resized for your events');
		}
		else
		{
			foreach ($images as $image)
			{
				$image = EventbookingHelperHtml::getCleanImagePath($image);

				$path = JPATH_ROOT . '/' . $image;

				if (!file_exists($path))
				{
					continue;
				}

				EventbookingHelper::resizeImage($path, $path, $width, $height);
			}

			$start += count($images);
			$this->setRedirect('index.php?option=com_eventbooking&task=tool.resize_large_images&start=' . $start);
			sleep(3);
		}
	}

	/**
	 * Resize large event image to the given size
	 */
	public function resize_thumb_images()
	{
		$start = $this->input->getInt('start', 0);

		$config = EventbookingHelper::getConfig();
		$width  = (int) $config->thumb_width ?: 200;
		$height = (int) $config->thumb_height ?: 200;

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('image')
			->from('#__eb_events')
			->where('published = 1')
			->order('id DESC')
			->where('LENGTH(image) > 0');
		$db->setQuery($query, $start, 100);
		$images = $db->loadColumn();

		if (count($images) == 0)
		{
			$this->setRedirect('index.php?option=com_eventbooking&view=events', 'Thumb Images were successfully resized for your events');
		}
		else
		{
			foreach ($images as $image)
			{
				$image = EventbookingHelperHtml::getCleanImagePath($image);

				$path = JPATH_ROOT . '/' . $image;

				if (!file_exists($path))
				{
					continue;
				}

				$fileName  = basename($image);
				$thumbPath = JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $fileName;

				EventbookingHelper::resizeImage($path, $thumbPath, $width, $height);
			}

			$start += count($images);
			$this->setRedirect('index.php?option=com_eventbooking&task=tool.resize_thumb_images&start=' . $start);
			sleep(3);
		}
	}

	/**
	 * Fix "Row size too large" issue
	 */
	public function fix_row_size()
	{
		$db = Factory::getDbo();

		$tables = [
			'#__eb_categories',
			'#__eb_events',
			'#__eb_fields',
			'#__eb_locations',
			'#__eb_registrants',
			'#__eb_ticket_types',
			'#__eb_agendas',
			'#__eb_speakers',
		];

		foreach ($tables as $table)
		{
			$query = "ALTER TABLE `$table` ROW_FORMAT = DYNAMIC";
			$db->setQuery($query)
				->execute();
		}
	}

	/**
	 * Method for finding menu linked to the extension
	 */
	public function find_menus()
	{
		$component = ComponentHelper::getComponent('com_eventbooking');
		$menus     = $this->app->getMenu('site');
		$items     = $menus->getItems('component_id', $component->id);
		?>
		<table class="table table-striped">
			<thead>
			<tr>
				<th>ID</th>
				<th>Title</th>
				<th>Alias</th>
				<th>Link</th>
				<th>Menu</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ($items as $item)
			{
				?>
				<tr>
					<td>
						<?php
						echo $item->id; ?>
					</td>
					<td><?php
						echo $item->title; ?></td>
					<td><?php
						echo $item->alias; ?></td>
					<td><?php
						echo $item->link ?></td>
					<td><?php
						echo $item->menutype; ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * The second option to fix row size
	 */
	public function fix_row_size2()
	{
		$db        = Factory::getDbo();
		$languages = EventbookingHelper::getLanguages();

		if (count($languages))
		{
			$categoryTableFields = array_keys($db->getTableColumns('#__eb_categories'));
			$eventTableFields    = array_keys($db->getTableColumns('#__eb_events'));
			$fieldTableFields    = array_keys($db->getTableColumns('#__eb_fields'));
			$locationTableFields = array_keys($db->getTableColumns('#__eb_locations'));

			foreach ($languages as $language)
			{
				$prefix = $language->sef;

				$fields = [
					'name',
					'alias',
					'page_title',
					'page_heading',
					'meta_keywords',
					'meta_description',
					'description',
				];

				foreach ($fields as $field)
				{
					$fieldName = $field . '_' . $prefix;

					if (!in_array($fieldName, $categoryTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` TEXT NULL;";
					}
					else
					{
						$sql = "ALTER TABLE  `#__eb_categories` MODIFY  `$fieldName` TEXT NULL;";
					}

					$db->setQuery($sql);

					try
					{
						$db->execute();
					}
					catch (Exception $e)
					{
						$this->app->enqueueMessage(sprintf('Field %s already exist in table %s', $fieldName, '#__eb_categories'));
					}
				}

				$fields = [
					'title',
					'alias',
					'page_title',
					'page_heading',
					'meta_keywords',
					'meta_description',
					'price_text',
					'registration_handle_url',
					'short_description',
					'description',
					'registration_form_message',
					'registration_form_message_group',
					'user_email_body',
					'user_email_body_offline',
					'thanks_message',
					'thanks_message_offline',
					'registration_approved_email_body',
					'invoice_format',
				];

				foreach ($fields as $field)
				{
					$fieldName = $field . '_' . $prefix;

					if (!in_array($fieldName, $eventTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` TEXT NULL;";
					}
					else
					{
						$sql = "ALTER TABLE  `#__eb_events` MODIFY  `$fieldName` TEXT NULL;";
					}

					$db->setQuery($sql);

					try
					{
						$db->execute();
					}
					catch (Exception $e)
					{
						$this->app->enqueueMessage(sprintf('Field %s already exist in table %s', $fieldName, '#__eb_events'));
					}
				}

				$fields = [
					'title',
					'description',
					'values',
					'default_values',
					'depend_on_options',
				];

				foreach ($fields as $field)
				{
					$fieldName = $field . '_' . $prefix;

					if (!in_array($fieldName, $fieldTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_fields` ADD  `$fieldName` TEXT NULL;";
					}
					else
					{
						$sql = "ALTER TABLE  `#__eb_fields` MODIFY  `$fieldName` TEXT NULL;";
					}

					$db->setQuery($sql);

					try
					{
						$db->execute();
					}
					catch (Exception $e)
					{
						$this->app->enqueueMessage(sprintf('Field %s already exist in table %s', $fieldName, '#__eb_fields'));
					}
				}

				$fields = [
					'name',
					'alias',
					'description',
				];

				foreach ($fields as $field)
				{
					$fieldName = $field . '_' . $prefix;

					if (!in_array($fieldName, $locationTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_locations` ADD  `$fieldName` TEXT NULL;";
					}
					else
					{
						$sql = "ALTER TABLE  `#__eb_locations` MODIFY  `$fieldName` TEXT NULL;";
					}

					$db->setQuery($sql);

					try
					{
						$db->execute();
					}
					catch (Exception $e)
					{
						$this->app->enqueueMessage(sprintf('Field %s already exist in table %s', $fieldName, '#__eb_locations'));
					}
				}
			}
		}
	}

	/**
	 * Method to create helper override
	 * https://domain.com/administrator/index.php?option=com_eventbooking&task=tool.create_helper_override&helper=registration
	 */
	public function create_helper_override()
	{
		$helper = $this->input->getCmd('helper', 'helper');

		// First, create override folder if does not exist
		$path = JPATH_ROOT . '/components/com_eventbooking/helper/override';

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$helperFile = $path . '/' . $helper . '.php';

		if (!File::exists($helper))
		{
			File::write($helperFile, '<?php');
		}

		echo sprintf('Successfully create helper override %s', $helperFile);
	}

	/**
	 * Method to create model override
	 * https://domain.com/administrator/index.php?option=com_eventbooking&task=tool.create_model_override&model=events[&app=site]
	 */

	public function create_model_override()
	{
		$app   = $this->input->getCmd('app', 'site');
		$model = $this->input->getCmd('model');

		if (!$model)
		{
			echo 'Please pass model you want to create override via model variable';

			return;
		}

		if ($app == 'site')
		{
			$path = JPATH_ROOT . '/components/com_eventbooking/model/override';
		}
		else
		{
			$path = JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/override';
		}

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$modelFile = $path . '/' . $model . '.php';

		if (!File::exists($modelFile))
		{
			File::write($modelFile, '<?php');
		}

		echo sprintf('Successfully create model override %s', $modelFile);
	}

	/**
	 * Method to create controller override
	 *
	 * https://domain.com/administrator/index.php?option=com_eventbooking&task=tool.create_controller_override&controller=registrant[&app=site]
	 */

	public function create_controller_override()
	{
		$app        = $this->input->getCmd('app', 'site');
		$controller = $this->input->getCmd('controller');

		if (!$controller)
		{
			echo 'Please pass controller you want to create override via controller variable';

			return;
		}

		if ($app == 'site')
		{
			$path = JPATH_ROOT . '/components/com_eventbooking/controller/override';
		}
		else
		{
			$path = JPATH_ADMINISTRATOR . '/components/com_eventbooking/controller/override';
		}

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$controllerFile = $path . '/' . $controller . '.php';

		if (!File::exists($controllerFile))
		{
			File::write($controllerFile, '<?php');
		}

		echo sprintf('Successfully create controller override %s', $controllerFile);
	}

	/**
	 * Method to create view override
	 *
	 * https://domain.com/administrator/index.php?option=com_eventbooking&task=tool.create_view_override&view=registrant[&app=site]
	 */

	public function create_view_override()
	{
		$app  = $this->input->getCmd('app', 'site');
		$view = $this->input->getCmd('view');

		if (!$view)
		{
			echo 'Please pass view you want to create override via view variable';

			return;
		}

		if ($app == 'site')
		{
			$path = JPATH_ROOT . '/components/com_eventbooking/view/override';
		}
		else
		{
			$path = JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/override';
		}

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$path = $path . '/' . $view;

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$viewFile = $path . '/html.php';

		if (!File::exists($viewFile))
		{
			File::write($viewFile, '<?php');
		}

		echo sprintf('Successfully create view override %s', $viewFile);
	}

	/**
	 * Method to create layout override
	 *
	 * https://domain.com/administrator/index.php?option=com_eventbooking&task=tool.create_layout_override&view=registrant[&layout=default&app=site&template=protostar]
	 */

	public function create_layout_override()
	{
		$app = $this->input->getCmd('app', 'site');

		$template = $this->input->getCmd('template', $this->getDefaultTemplate($app));

		$view   = $this->input->getCmd('view');
		$layout = $this->input->getCmd('layout', 'default');

		if (!$template)
		{
			echo sprintf('Invalid Template %s', $template);

			return;
		}

		if (!$view)
		{
			echo 'Please pass view you want to create override via view variable';

			return;
		}

		if ($app == 'site')
		{
			$path = JPATH_ROOT . '/templates/' . $template . '/html/com_eventbooking';
		}
		else
		{
			$path = JPATH_ADMINISTRATOR . '/templates/' . $template . '/html/com_eventbooking';
		}

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$path = $path . '/' . $view;

		if (!Folder::exists($path))
		{
			Folder::create($path);
		}

		$layoutFile = $path . '/' . $layout . '.php';

		if (!File::exists($layoutFile))
		{
			// Copy the original layout to template

			if ($app == 'site')
			{
				$source = JPATH_ROOT . '/components/com_eventbooking/themes/default/' . $view . '/' . $layout . '.php';
			}
			else
			{
				$source = JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/' . $view . '/tmpl/' . $layout . '.php';
			}

			if (File::exists($source))
			{
				File::copy($source, $layoutFile);
			}
			else
			{
				echo sprintf('Invalid source file %s', $source);
			}
		}

		echo sprintf('Successfully create view override %s', $layoutFile);
	}

	private function getDefaultTemplate($app)
	{
		if ($app == 'site')
		{
			$client = 0;
		}
		else
		{
			$client = 1;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, home, template, s.params')
			->from('#__template_styles as s')
			->where('s.client_id = ' . $client)
			->where('e.enabled = 1')
			->join('LEFT', '#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

		$db->setQuery($query);

		foreach ($db->loadObjectList() as $templateStyle)
		{
			if ($templateStyle->home == 1)
			{
				return $templateStyle->template;
			}
		}

		return '';
	}

	public function download_mpdf_font()
	{
		$extractDir = JPATH_ROOT . '/plugins/eventbooking/mpdf/mpdf';

		if (!Folder::exists($extractDir))
		{
			$this->app->enqueueMessage('You are not using MPDF plugin, so no need for downloading font', 'info');

			return;
		}

		$fontPackageUrl = 'https://joomdonation.com/tcpdf/ttfonts.zip';

		$fontFile = InstallerHelper::downloadPackage($fontPackageUrl, 'ttfonts.zip');

		if ($fontFile === false)
		{
			echo Text::_('The requested font could not be downloaded');

			return;
		}

		$tmpPath = $this->app->get('tmp_path');

		if (!Folder::exists($tmpPath))
		{
			$tmpPath = JPATH_ROOT . '/tmp';
		}

		$fontPackage = $tmpPath . '/ttfonts.zip';

		if (EventbookingHelper::isJoomla4())
		{
			$archive = new Archive(['tmp_path' => $tmpPath]);
			$result  = $archive->extract($fontPackage, $extractDir);
		}
		else
		{
			$result = JArchive::extract($fontPackage, $extractDir);
		}

		if (!$result)
		{
			echo 'Error extract font package';

			return;
		}

		// Delete the downloaded zip file
		File::delete($fontPackage);

		$this->setRedirect('index.php?option=com_eventbooking&view=registrants', 'ttfonts for MPDF is downloaded and extracted');
	}

	public function download_mpdf_font_full()
	{
		$fontPackageUrl = 'https://joomdonation.com/tcpdf/ttfonts_full.zip';

		$fontFile = InstallerHelper::downloadPackage($fontPackageUrl, 'ttfonts.zip');

		if ($fontFile === false)
		{
			echo Text::_('The requested font could not be downloaded');

			return;
		}

		$tmpPath = $this->app->get('tmp_path');

		if (!Folder::exists($tmpPath))
		{
			$tmpPath = JPATH_ROOT . '/tmp';
		}

		$fontPackage = $tmpPath . '/ttfonts.zip';

		$extractDir = JPATH_ROOT . '/plugins/eventbooking/mpdf/mpdf';

		if (EventbookingHelper::isJoomla4())
		{
			$archive = new Archive(['tmp_path' => $tmpPath]);
			$result  = $archive->extract($fontPackage, $extractDir);
		}
		else
		{
			$result = JArchive::extract($fontPackage, $extractDir);
		}

		if (!$result)
		{
			echo 'Error extract font package';

			return;
		}

		// Delete the downloaded zip file
		File::delete($fontPackage);

		$this->setRedirect('index.php?option=com_eventbooking&view=registrants', 'ttfonts for MPDF is downloaded and extracted');
	}

	/**
	 * Method to update to new countries and states database
	 */
	public function update_countries_states_database()
	{
		if (!Factory::getUser()->authorise('core.admin'))
		{
			echo 'You do not have permission to execute this task';
		}

		// We need to change data type for state_2_code and state_3_code so that it can store longer data
		$db = Factory::getDbo();

		$sql = 'ALTER TABLE  `#__eb_states` CHANGE  `state_2_code` `state_2_code` char(10) DEFAULT NULL';
		$db->setQuery($sql)
			->execute();

		$sql = 'ALTER TABLE  `#__eb_states` CHANGE  `state_3_code` `state_3_code` char(10) DEFAULT NULL';
		$db->setQuery($sql)
			->execute();

		EventbookingHelper::executeSqlFile(JPATH_ADMINISTRATOR . '/components/com_eventbooking/sql/countries_states.sql');

		echo 'Countries, States database successfully updated';
	}

	/**
	 * Build EU tax rules
	 */
	public function build_eu_tax_rules()
	{
		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$db->truncateTable('#__eb_taxes');
		$defaultCountry     = $config->default_country;
		$defaultCountryCode = EventbookingHelper::getCountryCode($defaultCountry);

		$query = $db->getQuery(true)
			->insert('#__eb_taxes')
			->columns('event_id, country, rate, vies, published');

		// Without VAT number, use local tax rate
		foreach (EventbookingHelperEuvat::$europeanUnionVATInformation as $countryCode => $vatInfo)
		{
			$countryName    = $vatInfo[0];
			$countryTaxRate = EventbookingHelperEuvat::getEUCountryTaxRate($countryCode);

			$query->values(implode(',', $db->quote([0, $countryName, $countryTaxRate, 0, 1])));

			if ($countryCode == $defaultCountryCode)
			{
				$localTaxRate = EventbookingHelperEuvat::getEUCountryTaxRate($defaultCountryCode);
				$query->values(implode(',', $db->quote([0, $countryName, $localTaxRate, 1, 1])));
			}
		}

		$db->setQuery($query)
			->execute();

		$this->setRedirect('index.php?option=com_eventbooking&view=taxes', Text::_('EU Tax Rules were successfully created'));
	}

	public function migrate_tax_rates()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_taxes');
		$db->setQuery($query);

		if ($db->loadResult() > 0)
		{
			return;
		}

		$query->clear()
			->select('DISTINCT tax_rate')
			->from('#__eb_events')
			->where('tax_rate > 0');
		$db->setQuery($query);
		$taxRates = $db->loadColumn();

		if (!count($taxRates))
		{
			// The existing system does not use tax
			return;
		}

		if (count($taxRates) == 1)
		{
			// Only has single tax rate, assume that tax rate is the same for all events
			$query->clear()
				->insert('#__eb_taxes')
				->columns('event_id, rate')
				->values(implode(',', $db->quote([0, $taxRates[0]])));
			$db->setQuery($query)
				->execute();
		}
		else
		{
			// There are different tax rates for different event
			$query->clear()
				->select('id, tax_rate')
				->from('#__eb_events')
				->where('tax_rate > 0');
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$query->clear()
				->insert('#__eb_taxes')
				->columns('event_id, rate');

			foreach ($rows as $row)
			{
				$query->values(implode(',', $db->quote([$row->id, $row->tax_rate])));
			}

			$db->setQuery($query)
				->execute();
		}
	}

	/**
	 * Tool to allow clean email logs older than certain number of months/years
	 *
	 * @return void
	 */
	public function clean_emails_log()
	{
		$numberMonths = $this->input->getInt('number_months', 0);
		$numberYears  = $this->input->getInt('number_years', 0);

		if ($numberMonths > 0 || $numberYears > 0)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete('#__eb_emails');

			if ($numberMonths > 0)
			{
				$query->where('TIMESTAMPDIFF(MONTH, `sent_at`, UTC_TIMESTAMP()) >= ' . $numberMonths);
			}
			else
			{
				$query->where('TIMESTAMPDIFF(YEAR, `sent_at`, UTC_TIMESTAMP()) >= ' . $numberYears);
			}

			$db->setQuery($query)
				->execute();

			echo 'Old emails were successfully deleted';
		}
		else
		{
			echo 'Please provide number months or number years via number_months or number_years parameter';
		}
	}

	/**
	 *
	 */
	public function generate_ticket_number_for_registrants()
	{
		$eventId = $this->input->getInt('event_id', 0);

		if (!$eventId)
		{
			echo 'Please provide ID of the event which you want to generate tickets for registrants via event_id parameter';

			return;
		}

		$db       = Factory::getDbo();
		$rowEvent = new EventbookingTableEvent($db);
		$rowEvent->load($eventId);

		if (!$rowEvent->activate_tickets_pdf)
		{
			echo 'Tickets PDF is not enabled for this event';

			return;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('event_id = ' . $eventId)
			->where('published = 1')
			->where('group_id = 0')
			->where('LENGTH(ticket_code) = 0')
			->order('id');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		// Get the next ticket number
		$query->clear()
			->select('MAX(ticket_number)')
			->from('#__eb_registrants')
			->where('event_id = ' . $eventId);
		$db->setQuery($query);
		$ticketNumber = (int) $db->loadResult() + 1;

		$ticketNumber = max($ticketNumber, $rowEvent->ticket_start_number);

		foreach ($rows as $rowRegistrant)
		{
			if ($rowRegistrant->is_group_billing)
			{
				$ticketCode = EventbookingHelperRegistration::getTicketCode();

				$query->clear()
					->update('#__eb_registrants')
					->set('ticket_code = ' . $db->quote($ticketCode))
					->where('id = ' . $rowRegistrant->id);
				$db->setQuery($query);
				$db->execute();

				$query->clear()
					->select('id')
					->from('#__eb_registrants')
					->where('group_id = ' . $rowRegistrant->id)
					->order('id');
				$db->setQuery($query);

				$memberIds = $db->loadColumn();

				foreach ($memberIds as $memberId)
				{
					$ticketCode = EventbookingHelperRegistration::getTicketCode();

					$query->clear()
						->update('#__eb_registrants')
						->set('ticket_code = ' . $db->quote($ticketCode))
						->set('ticket_number = ' . $ticketNumber)
						->where('id = ' . $memberId);
					$db->setQuery($query);
					$db->execute();

					$ticketNumber++;
				}
			}
			else
			{
				$ticketCode = EventbookingHelperRegistration::getTicketCode();

				$query->clear()
					->update('#__eb_registrants')
					->set('ticket_code = ' . $db->quote($ticketCode))
					->set('ticket_number = ' . $ticketNumber)
					->where('id = ' . $rowRegistrant->id);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Method to drop all multilgual fields
	 *
	 * @return void
	 */
	public function drop_all_multilingual_fields(): void
	{
		$db        = Factory::getDbo();
		$languages = EventbookingHelper::getLanguages();

		$fields     = array_keys($db->getTableColumns('#__eb_categories'));
		$dropFields = [
			'name',
			'alias',
			'page_title',
			'page_heading',
			'meta_keywords',
			'meta_description',
			'description',
			'admin_email_body',
			'user_email_body',
			'user_email_body_offline',
			'group_member_email_body',
			'thanks_message',
			'thanks_message_offline',
			'registration_approved_email_body',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_categories DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields     = array_keys($db->getTableColumns('#__eb_events'));
		$dropFields = [
			'title',
			'alias',
			'page_title',
			'page_heading',
			'meta_keywords',
			'meta_description',
			'price_text',
			'registration_handle_url',
			'short_description',
			'description',
			'registration_form_message',
			'registration_form_message_group',
			'admin_email_body',
			'user_email_body',
			'user_email_body_offline',
			'group_member_email_body',
			'thanks_message',
			'thanks_message_offline',
			'registration_approved_email_body',
			'invoice_format',
			'ticket_layout',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_events DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields     = array_keys($db->getTableColumns('#__eb_fields'));
		$dropFields = [
			'title',
			'description',
			'values',
			'default_values',
			'depend_on_options',
			'place_holder',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_fields DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields     = array_keys($db->getTableColumns('#__eb_locations'));
		$dropFields = [
			'name',
			'alias',
			'description',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_locations DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields     = array_keys($db->getTableColumns('#__eb_ticket_types'));
		$dropFields = [
			'title',
			'description',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_ticket_types DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields     = array_keys($db->getTableColumns('#__eb_agendas'));
		$dropFields = [
			'title',
			'description',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_agendas DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}

		$fields     = array_keys($db->getTableColumns('#__eb_speakers'));
		$dropFields = [
			'name',
			'title',
			'url',
			'description',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($dropFields as $dropField)
			{
				$field = $dropField . '_' . $prefix;

				if (in_array($field, $fields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_speakers DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}
	}

	/**
	 *
	 */
	public function drop_unnecessary_multilingual_fields()
	{
		$db = Factory::getDbo();

		$languages        = EventbookingHelper::getLanguages();
		$eventTableFields = array_keys($db->getTableColumns('#__eb_events'));
		$eventDropFields  = [
			'page_title',
			'page_heading',
			'registration_form_message',
			'registration_form_message_group',
			'admin_email_body',
			'user_email_body',
			'user_email_body_offline',
			'group_member_email_body',
			'thanks_message',
			'thanks_message_offline',
			'registration_approved_email_body',
			'invoice_format',
			'ticket_layout',
		];

		foreach ($languages as $language)
		{
			$prefix = $language->sef;

			foreach ($eventDropFields as $eventDropField)
			{
				$field = $eventDropField . '_' . $prefix;

				if (in_array($field, $eventTableFields))
				{
					$field = $db->quoteName($field);
					$sql   = "ALTER TABLE #__eb_events DROP COLUMN $field";
					$db->setQuery($sql)
						->execute();
				}
			}
		}
	}

	/**
	 * Add tool to remove PDF invoices to save space
	 */
	public function remove_pdf_invoices()
	{
		$invoicesPath = JPATH_ROOT . '/media/com_eventbooking/invoices';

		$files = Folder::files($invoicesPath, '.pdf', false, true);

		foreach ($files as $file)
		{
			File::delete($file);
		}

		echo 'Deleted ' . count($files) . ' PDF invoice files';
	}

	/**
	 * Add tool to remove PDF invoices to save space
	 */
	public function remove_pdf_tickets()
	{
		$ticketsPath = JPATH_ROOT . '/media/com_eventbooking/tickets';

		$files = Folder::files($ticketsPath, '.pdf', false, true);

		foreach ($files as $file)
		{
			File::delete($file);
		}

		echo 'Deleted ' . count($files) . ' PDF ticket files';
	}

	/**
	 * Clean up PDF files to save space
	 *
	 * @return void
	 */
	public function clean_up_pdf_files()
	{
		$pdFilesPaths = [
			JPATH_ROOT . '/media/com_eventbooking/invoices',
			JPATH_ROOT . '/media/com_eventbooking/tickets',
			JPATH_ROOT . '/media/com_eventbooking/certificates',
		];

		$totalFiles = 0;

		foreach ($pdFilesPaths as $path)
		{
			$files = Folder::files($path, '.pdf', false, true);

			foreach ($files as $file)
			{
				File::delete($file);
			}

			$totalFiles += count($files);
		}

		echo 'Deleted ' . $totalFiles . ' PDF files';
	}

	/**
	 * Add tool to remove PDF certificates to save space
	 */
	public function remove_pdf_certificates(): void
	{
		$certificatesPath = JPATH_ROOT . '/media/com_eventbooking/certificates';

		$files = Folder::files($certificatesPath, '.pdf', false, true);

		foreach ($files as $file)
		{
			File::delete($file);
		}

		echo 'Deleted ' . count($files) . ' PDF certificate files';
	}

	public function recreate_menu_table()
	{
		$db  = Factory::getDbo();
		$sql = 'DROP TABLE IF EXISTS #__eb_menus';
		$db->setQuery($sql)
			->execute();
		$sql = 'CREATE TABLE IF NOT EXISTS `#__eb_menus` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `menu_name` varchar(255) DEFAULT NULL,
		  `menu_parent_id` int(11) DEFAULT NULL,
		  `menu_link` varchar(255) DEFAULT NULL,
		  `published` tinyint(1) unsigned DEFAULT NULL,
		  `ordering` int(11) DEFAULT NULL,
		  `menu_class` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		$db->setQuery($sql)
			->execute();
	}

	/**
	 * Tool to convert important tables to use utf8mb4
	 *
	 * @return void
	 */
	public function convert_to_utf8mb4()
	{
		$db = Factory::getDbo();

		$tablesToConvert = [
			'#__eb_categories',
			'#__eb_events',
			'#__eb_fields',
			'#__eb_messages',
			'#__eb_ticket_types',
			'#__eb_agendas',
		];

		foreach ($tablesToConvert as $table)
		{
			// Convert table
			$sql = "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
			$db->setQuery($sql)
				->execute();
		}
	}

	/**
	 * Method to reset created_by of events in case the user who created the event was deleted
	 *
	 * @return void
	 */
	public function reset_created_by()
	{
		$userId = $this->input->getInt('user_id', 0);
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->update('#__eb_events')
			->set('created_by = ' . $userId)
			->where('created_by NOT IN (SELECT id FROM #__users)');
		$db->setQuery($query);
		$db->execute();
		echo 'Done';
	}

	/**
	 * Method to update language for group members record
	 *
	 * @return void
	 */
	public function update_group_members_language()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->update('#__eb_registrants AS a')
			->innerJoin('#__eb_registrants AS b ON a.group_id =  b.id')
			->set('a.language = b.language')
			->where('a.group_id >0');
		$db->setQuery($query)
			->execute();
	}

	/**
	 * Method to
	 *
	 * @return void
	 */
	public function drop_params_event_fields()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_events');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$keys = [
			'ticket_bg_top',
			'ticket_bg_left',
			'ticket_bg_width',
			'ticket_bg_height',
			'certificate_bg_left',
			'certificate_bg_top',
			'certificate_bg_width',
			'certificate_bg_height',
		];

		foreach ($rows as $row)
		{
			$params = new Registry($row->params);

			foreach ($keys as $key)
			{
				if (!$params->exists($key) && property_exists($row, $key))
				{
					$params->set($key, $row->{$key});
				}
			}

			$query->clear()
				->update('#__eb_events')
				->set('params = ' . $db->quote($params->toString()))
				->where('id = ' . $row->id);

			$db->setQuery($query)
				->execute();
		}

		// Now it is safe to delete these fields from #__eb_events table
		$fields = array_keys($db->getTableColumns('#__eb_events'));

		foreach ($keys as $key)
		{
			if (in_array($key, $fields))
			{
				$sql = "ALTER TABLE  `#__eb_events` DROP  `$key`";
				$db->setQuery($sql)
					->execute();
			}
		}
	}

	/**
	 * Method to add fields to database to support up to 6 reminders
	 *
	 * @return void
	 */
	public function add_fourth_reminder_fields()
	{
		$db = Factory::getDbo();

		$categoryTableFields   = array_keys($db->getTableColumns('#__eb_categories'));
		$eventTableFields      = array_keys($db->getTableColumns('#__eb_events'));
		$registrantTableFields = array_keys($db->getTableColumns('#__eb_registrants'));

		$newFieldsToCategoryTable = [
			'fourth_reminder_email_subject' => 'varchar',
			'fourth_reminder_email_body'    => 'text',
		];

		foreach ($newFieldsToCategoryTable as $fieldName => $type)
		{
			if (in_array($fieldName, $categoryTableFields))
			{
				continue;
			}

			if ($type == 'varchar')
			{
				$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` VARCHAR( 255 );";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'text')
			{
				$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` TEXT NULL;";
				$db->setQuery($sql);
				$db->execute();
			}
		}

		$newFieldsToEventTable = [
			'send_fourth_reminder'          => 'int',
			'fourth_reminder_frequency'     => 'char',
			'fourth_reminder_email_subject' => 'varchar',
			'fourth_reminder_email_body'    => 'text',
		];

		foreach ($newFieldsToEventTable as $fieldName => $type)
		{
			if (in_array($fieldName, $eventTableFields))
			{
				continue;
			}

			if ($type == 'varchar')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` VARCHAR( 255 );";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'char')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` CHAR(1) DEFAULT 'd';";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'int')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` INT NOT NULL DEFAULT 0;";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'text')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` TEXT NULL;";
				$db->setQuery($sql);
				$db->execute();
			}
		}

		$newFieldsToRegistrantsTable = [
			'is_fourth_reminder_sent',
		];

		foreach ($newFieldsToRegistrantsTable as $fieldName)
		{
			if (in_array($fieldName, $registrantTableFields))
			{
				continue;
			}

			$sql = "ALTER TABLE  `#__eb_registrants` ADD  `$fieldName` TINYINT NOT NULL DEFAULT 0;";
			$db->setQuery($sql);
			$db->execute();
		}

		// Add extra reminder messages
		$items = [
			[
				'name'     => 'fourth_reminder_email_subject',
				'title'    => 'EB_FOURTH_REMINDER_EMAIL_SUBJECT',
				'title_en' => 'Fourth Reminder Email Subject',
				'type'     => 'text',
				'group'    => 3,
			],
			[
				'name'     => 'fourth_reminder_email_body',
				'title'    => 'EB_FOURTH_REMINDER_EMAIL_BODY',
				'title_en' => 'Fourth Reminder Email Body',
				'type'     => 'editor',
				'group'    => 3,
			],
		];

		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/install.eventbooking.php';

		com_eventbookingInstallerScript::insertMessageItems($items);
	}

	/**
	 * Method to add fields to database to support up to 6 reminders
	 *
	 * @return void
	 */
	public function add_fifth_reminder_fields()
	{
		$db = Factory::getDbo();

		$categoryTableFields   = array_keys($db->getTableColumns('#__eb_categories'));
		$eventTableFields      = array_keys($db->getTableColumns('#__eb_events'));
		$registrantTableFields = array_keys($db->getTableColumns('#__eb_registrants'));

		$newFieldsToCategoryTable = [
			'fourth_reminder_email_subject' => 'varchar',
			'fourth_reminder_email_body'    => 'text',
			'fifth_reminder_email_subject'  => 'varchar',
			'fifth_reminder_email_body'     => 'text',
		];

		foreach ($newFieldsToCategoryTable as $fieldName => $type)
		{
			if (in_array($fieldName, $categoryTableFields))
			{
				continue;
			}

			if ($type == 'varchar')
			{
				$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` VARCHAR( 255 );";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'text')
			{
				$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` TEXT NULL;";
				$db->setQuery($sql);
				$db->execute();
			}
		}

		$newFieldsToEventTable = [
			'send_fourth_reminder'          => 'int',
			'fourth_reminder_frequency'     => 'char',
			'fourth_reminder_email_subject' => 'varchar',
			'fourth_reminder_email_body'    => 'text',
			'send_fifth_reminder'           => 'int',
			'fifth_reminder_frequency'      => 'char',
			'fifth_reminder_email_subject'  => 'varchar',
			'fifth_reminder_email_body'     => 'text',
		];

		foreach ($newFieldsToEventTable as $fieldName => $type)
		{
			if (in_array($fieldName, $eventTableFields))
			{
				continue;
			}

			if ($type == 'varchar')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` VARCHAR( 255 );";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'char')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` CHAR(1) DEFAULT 'd';";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'int')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` INT NOT NULL DEFAULT 0;";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'text')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` TEXT NULL;";
				$db->setQuery($sql);
				$db->execute();
			}
		}

		$newFieldsToRegistrantsTable = [
			'is_fourth_reminder_sent',
			'is_fifth_reminder_sent',
		];

		foreach ($newFieldsToRegistrantsTable as $fieldName)
		{
			if (in_array($fieldName, $registrantTableFields))
			{
				continue;
			}

			$sql = "ALTER TABLE  `#__eb_registrants` ADD  `$fieldName` TINYINT NOT NULL DEFAULT 0;";
			$db->setQuery($sql);
			$db->execute();
		}

		// Add extra reminder messages
		$items = [
			[
				'name'     => 'fourth_reminder_email_subject',
				'title'    => 'EB_FOURTH_REMINDER_EMAIL_SUBJECT',
				'title_en' => 'Fourth Reminder Email Subject',
				'type'     => 'text',
				'group'    => 3,
			],
			[
				'name'     => 'fourth_reminder_email_body',
				'title'    => 'EB_FOURTH_REMINDER_EMAIL_BODY',
				'title_en' => 'Fourth Reminder Email Body',
				'type'     => 'editor',
				'group'    => 3,
			],
			[
				'name'     => 'fifth_reminder_email_subject',
				'title'    => 'EB_FIFTH_REMINDER_EMAIL_SUBJECT',
				'title_en' => 'Fifth Reminder Email Subject',
				'type'     => 'text',
				'group'    => 3,
			],
			[
				'name'     => 'fifth_reminder_email_body',
				'title'    => 'EB_FIFTH_REMINDER_EMAIL_BODY',
				'title_en' => 'Fifth Reminder Email Body',
				'type'     => 'editor',
				'group'    => 3,
			],
		];

		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/install.eventbooking.php';

		com_eventbookingInstallerScript::insertMessageItems($items);
	}

	/**
	 * Method to add fields to database to support up to 6 reminders
	 *
	 * @return void
	 */
	public function add_sixth_reminder_fields()
	{
		$db = Factory::getDbo();

		$categoryTableFields   = array_keys($db->getTableColumns('#__eb_categories'));
		$eventTableFields      = array_keys($db->getTableColumns('#__eb_events'));
		$registrantTableFields = array_keys($db->getTableColumns('#__eb_registrants'));

		$newFieldsToCategoryTable = [
			'fourth_reminder_email_subject' => 'varchar',
			'fourth_reminder_email_body'    => 'text',
			'fifth_reminder_email_subject'  => 'varchar',
			'fifth_reminder_email_body'     => 'text',
			'sixth_reminder_email_subject'  => 'varchar',
			'sixth_reminder_email_body'     => 'text',
		];

		foreach ($newFieldsToCategoryTable as $fieldName => $type)
		{
			if (in_array($fieldName, $categoryTableFields))
			{
				continue;
			}

			if ($type == 'varchar')
			{
				$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` VARCHAR( 255 );";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'text')
			{
				$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` TEXT NULL;";
				$db->setQuery($sql);
				$db->execute();
			}
		}

		$newFieldsToEventTable = [
			'send_fourth_reminder'          => 'int',
			'fourth_reminder_frequency'     => 'char',
			'fourth_reminder_email_subject' => 'varchar',
			'fourth_reminder_email_body'    => 'text',
			'send_fifth_reminder'           => 'int',
			'fifth_reminder_frequency'      => 'char',
			'fifth_reminder_email_subject'  => 'varchar',
			'fifth_reminder_email_body'     => 'text',
			'send_sixth_reminder'           => 'int',
			'sixth_reminder_frequency'      => 'char',
			'sixth_reminder_email_subject'  => 'varchar',
			'sixth_reminder_email_body'     => 'text',
		];

		foreach ($newFieldsToEventTable as $fieldName => $type)
		{
			if (in_array($fieldName, $eventTableFields))
			{
				continue;
			}

			if ($type == 'varchar')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` VARCHAR( 255 );";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'char')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` CHAR(1) DEFAULT 'd';";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'int')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` INT NOT NULL DEFAULT 0;";
				$db->setQuery($sql);
				$db->execute();
			}
			elseif ($type == 'text')
			{
				$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` TEXT NULL;";
				$db->setQuery($sql);
				$db->execute();
			}
		}

		$newFieldsToRegistrantsTable = [
			'is_fourth_reminder_sent',
			'is_fifth_reminder_sent',
			'is_six_reminder_sent',
		];

		foreach ($newFieldsToRegistrantsTable as $fieldName)
		{
			if (in_array($fieldName, $registrantTableFields))
			{
				continue;
			}

			$sql = "ALTER TABLE  `#__eb_registrants` ADD  `$fieldName` TINYINT NOT NULL DEFAULT 0;";
			$db->setQuery($sql);
			$db->execute();
		}

		// Add extra reminder messages
		$items = [
			[
				'name'     => 'fourth_reminder_email_subject',
				'title'    => 'EB_FOURTH_REMINDER_EMAIL_SUBJECT',
				'title_en' => 'Fourth Reminder Email Subject',
				'type'     => 'text',
				'group'    => 3,
			],
			[
				'name'     => 'fourth_reminder_email_body',
				'title'    => 'EB_FOURTH_REMINDER_EMAIL_BODY',
				'title_en' => 'Fourth Reminder Email Body',
				'type'     => 'editor',
				'group'    => 3,
			],
			[
				'name'     => 'fifth_reminder_email_subject',
				'title'    => 'EB_FIFTH_REMINDER_EMAIL_SUBJECT',
				'title_en' => 'Fifth Reminder Email Subject',
				'type'     => 'text',
				'group'    => 3,
			],
			[
				'name'     => 'fifth_reminder_email_body',
				'title'    => 'EB_FIFTH_REMINDER_EMAIL_BODY',
				'title_en' => 'Fifth Reminder Email Body',
				'type'     => 'editor',
				'group'    => 3,
			],
			[
				'name'     => 'sixth_reminder_email_subject',
				'title'    => 'EB_SIXTH_REMINDER_EMAIL_SUBJECT',
				'title_en' => 'Sixth Reminder Email Subject',
				'type'     => 'text',
				'group'    => 3,
			],
			[
				'name'     => 'sixth_reminder_email_body',
				'title'    => 'EB_SIXTH_REMINDER_EMAIL_BODY',
				'title_en' => 'Sixth Reminder Email Body',
				'type'     => 'editor',
				'group'    => 3,
			],
		];

		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/install.eventbooking.php';

		com_eventbookingInstallerScript::insertMessageItems($items);
	}

	/**
	 * Tool to recalculate number registrants for records from events using ticket types
	 *
	 * @return void
	 */
	public function recalculate_number_registrants_for_ticket_types(): void
	{
		$eventId = $this->input->getInt('event_id', 0);

		if (!$eventId)
		{
			return;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('(published = 1 OR payment_method LIKE "os_offline%")')
			->where('group_id = 0')
			->where('event_id = ' . $eventId);
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$query->clear()
				->select('a.weight, b.quantity')
				->from('#__eb_ticket_types AS a')
				->innerJoin('#__eb_registrant_tickets AS b ON a.id = b.ticket_type_id')
				->where('b.registrant_id = ' . $row->id);
			$db->setQuery($query);
			$rowTickets        = $db->loadObjectList();
			$numberRegistrants = 0;

			foreach ($rowTickets as $rowTicket)
			{
				$weight = (int) $rowTicket->weight;

				$numberRegistrants += $weight * $rowTicket->quantity;
			}

			if ($numberRegistrants > 0 && $row->number_registrants != $numberRegistrants)
			{
				$query->clear()
					->update('#__eb_registrants')
					->set('number_registrants = ' . $numberRegistrants)
					->where('id = ' . $row->id);
				$db->setQuery($query)
					->execute();
			}
		}
	}

	/**
	 * Method to create paymongo_webhook
	 *
	 * @return void
	 */
	public function create_paymongo_webhook(): void
	{
		$http = HttpFactory::getHttp();
		$name = $this->input->getString('name', 'os_paymongo');

		$plugin = EventbookingHelperPayments::loadPaymentMethod($name);

		if (!$plugin)
		{
			$this->app->enqueueMessage('The paymongo payment plugin is not installed', 'warning');

			return;
		}

		$params = new Registry($plugin->params);

		if ($params->get('mode', 1))
		{
			$apiKey = $params->get('api_key', '');
		}
		else
		{
			$apiKey = $params->get('sandbox_api_key', '');
		}

		if (!$apiKey)
		{
			$this->app->enqueueMessage('Please enter API Key for the payment plugin before the system can create webhook', 'warning');

			return;
		}

		$request = [
			'data' => [
				'attributes' => [
					'url'    => Uri::root() . 'index.php?option=com_eventbooking&task=payment_confirm&payment_method=' . $name,
					'events' => ['checkout_session.payment.paid'],
				],
			],
		];

		$headers = [
			'Content-Type'  => 'application/json',
			'authorization' => 'Basic ' . base64_encode($apiKey . ':'),
		];

		$response = $http->post('https://api.paymongo.com/v1/webhooks', json_encode($request), $headers);

		$responseData = json_decode($response->body, false);

		if ($response->code == 200)
		{
			$secretKey = $responseData->data->attributes->secret_key;

			if ($responseData->data->attributes->livemode)
			{
				$params->set('webhook_secret_key', $secretKey);
			}
			else
			{
				$params->set('sandbox_webhook_secret_key', $secretKey);
			}

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->update('#__eb_payment_plugins')
				->set('params = ' . $db->quote($params->toString()))
				->where('id = ' . $plugin->id);
			$db->setQuery($query)
				->execute();
		}
		else
		{
			$errors = [];

			foreach ($responseData->errors as $error)
			{
				$errors[] = $error->detail;
			}

			$this->app->enqueueMessage(implode("\r\n", $errors), 'warning');
		}
	}

	/**
	 * Tool to detect category with wrong parent data
	 *
	 * @return void
	 */
	public function detect_wrong_categories(): void
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_categories')
			->where('parent > 0')
			->where('parent NOT IN (SELECT id FROM #__eb_categories)');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			echo $row->id . ':' . $row->name . ':' . $row->parent . PHP_EOL;
		}
	}

	/**
	 * Tool to fix set parent to 0 for categories has parent set to invalid category id
	 *
	 * @return void
	 */
	public function fix_wrong_parent_for_categories(): void
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->update('#__eb_categories')
			->set('parent = 0')
			->where('parent NOT IN (SELECT id FROM #__eb_categories)');
		$db->setQuery($query)
			->execute();

		echo $db->getAffectedRows();
	}

	/**
	 * Tool to detect events with wrong main category
	 *
	 * @return void
	 */
	public function detect_events_with_wrong_main_category(): void
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_events')
			->where('main_category_id NOT IN (SELECT id FROM #__eb_categories)');
		$db->setQuery($query);

		foreach ($db->loadObjectList() as $row)
		{
			echo $row->id . ':' . $row->title . ':' . $row->main_category_id . PHP_EOL;
		}

		echo 'Finish';
	}
}
