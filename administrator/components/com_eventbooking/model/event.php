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
use Joomla\CMS\Image\Image;
use Joomla\Registry\Registry;

class EventbookingModelEvent extends EventbookingModelCommonEvent
{
	/**
	 * @param $file
	 * @param $filename
	 *
	 * @return int
	 * @throws Exception
	 */
	public function import($file, $filename = '')
	{
		$events = EventbookingHelperData::getDataFromFile($file, $filename);

		if (!count($events))
		{
			return 0;
		}

		$config = EventbookingHelper::getConfig();

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, name')
			->from('#__eb_categories');
		$db->setQuery($query);
		$categories = $db->loadObjectList('name');

		$query->clear()
			->select('id, name')
			->from('#__eb_locations');
		$db->setQuery($query);
		$locations = $db->loadObjectList('name');

		$imported    = 0;
		$eventFields = [];

		if ($config->event_custom_field)
		{
			$xml = simplexml_load_file(JPATH_ROOT . '/components/com_eventbooking/fields.xml');

			if ($xml !== false)
			{
				$fields = $xml->fields->fieldset->children();

				foreach ($fields as $field)
				{
					$eventFields[] = (string) $field->attributes()->name;
				}
			}
		}

		foreach ($events as $event)
		{
			if (empty($event['id'])
				&& (empty($event['title']) || empty($event['category']) || empty($event['event_date'])))
			{
				continue;
			}

			foreach ($event as $key => $value)
			{
				if ($event[$key] === null)
				{
					$event[$key] = '';
				}
			}

			/* @var EventbookingTableEvent $row */
			$row = $this->getTable();

			if (!empty($event['id']))
			{
				$row->load($event['id']);
				$isNew = false;
			}
			else
			{
				$isNew = true;
			}

			if (isset($event['location']))
			{
				if (is_numeric($event['location']))
				{
					$event['location_id'] = $event['location'];
				}
				else
				{
					$locationName         = trim($event['location']);
					$event['location_id'] = isset($locations[$locationName]) ? $locations[$locationName]->id : 0;
				}
			}

			if (!empty($event['image']) && File::exists(JPATH_ROOT . '/' . $event['image']))
			{
				$fileName = File::makeSafe(basename($event['image']));

				if (!File::exists(JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $fileName))
				{
					$imagePath = JPATH_ROOT . '/media/com_eventbooking/images/' . $fileName;
					$thumbPath = JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $fileName;

					File::copy(JPATH_ROOT . '/' . $event['image'], $imagePath);

					$image = new Image($imagePath);
					$image->cropResize($config->thumb_width, $config->thumb_height, false)
						->toFile($thumbPath);

					$event['thumb'] = $fileName;
				}
				else
				{
					if (!$row->thumb)
					{
						$event['thumb'] = $fileName;
					}
				}
			}

			if ($isNew)
			{
				if (!isset($event['access']))
				{
					$event['access'] = $config->get('access', 1);
				}

				if (!isset($event['registration_access']))
				{
					$event['registration_access'] = $config->get('registration_access', 1);
				}
			}

			$row->bind($event, ['id']);

			if (!empty($eventFields))
			{
				$params = new Registry($row->custom_fields);

				foreach ($eventFields as $fieldName)
				{
					$params->set($fieldName, $event[$fieldName] ?? '');
				}

				$row->custom_fields = $params->toString();
			}

			if (empty($event['id']) || isset($event['category']))
			{
				// Main category
				if (is_numeric($event['category']))
				{
					$categoryId = $event['category'];
				}
				else
				{
					$categoryName = trim($event['category']);
					$categoryId   = isset($categories[$categoryName]) ? $categories[$categoryName]->id : 0;
				}

				$row->main_category_id = $categoryId;
			}

			$this->prepareTable($row, 'save');
			$row->store();
			$eventId = $row->id;

			if (!$isNew && isset($event['category']))
			{
				$query->clear()
					->delete('#__eb_event_categories')
					->where('event_id = ' . (int) $event['id'])
					->where('main_category = 1');
				$db->setQuery($query);
				$db->execute();
			}

			if (!$isNew && isset($event['additional_categories']))
			{
				$query->clear()
					->delete('#__eb_event_categories')
					->where('event_id = ' . (int) $event['id'])
					->where('main_category = 0');
				$db->setQuery($query);
				$db->execute();
			}

			if (!empty($categoryId))
			{
				$query->clear()
					->insert('#__eb_event_categories')
					->columns('event_id, category_id, main_category')
					->values("$eventId, $categoryId, 1");
				$db->setQuery($query);
				$db->execute();
			}

			$eventCategories = $event['additional_categories'] ?? '';
			$eventCategories = explode(' | ', $eventCategories);

			foreach ($eventCategories as $eventCategory)
			{
				$category = trim($eventCategory);

				if ($category && isset($categories[$category]))
				{
					$categoryId = $categories[$category]->id;
				}
				elseif (is_numeric($category))
				{
					$categoryId = (int) $category;
				}
				else
				{
					$categoryId = 0;
				}

				if ($categoryId)
				{
					$query->clear()
						->insert('#__eb_event_categories')
						->columns('event_id, category_id, main_category')
						->values("$eventId, $categoryId, 0");
					$db->setQuery($query)
						->execute();
				}
			}

			$this->afterEventImported($eventId, $event, $isNew);

			$imported++;
		}

		return $imported;
	}

	/**
	 * Method to call after an event is imported
	 *
	 * @param   int    $eventId
	 * @param   array  $event
	 * @param   bool   $isNew
	 *
	 */
	protected function afterEventImported($eventId, $event, $isNew)
	{
	}
}
