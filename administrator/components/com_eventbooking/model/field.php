<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;

class EventbookingModelField extends RADModelAdmin
{
	/**
	 * Allow event handling
	 *
	 * @var bool
	 */
	protected $triggerEvents = true;

	/**
	 * Pre-process data before custom field is being saved to database
	 *
	 * @param   Table  $row
	 * @param   RADInput                 $input
	 * @param   bool                     $isNew
	 */
	protected function beforeStore($row, $input, $isNew)
	{
		$input->set('depend_on_options', json_encode($input->get('depend_on_options', [], 'array')));
		$input->set('depend_on_ticket_type_ids', implode(',', $input->get('depend_on_ticket_type_ids', [], 'array')));

		if (in_array($row->id, $this->getRestrictedFieldIds()))
		{
			$data = $input->getData(RAD_INPUT_ALLOWRAW);
			unset($data['field_type']);
			unset($data['published']);
			unset($data['validation_rules']);
			$input->setData($data);
		}

		// Prevent copied field to be a core field
		if ($input->getInt('source_id'))
		{
			$input->set('is_core', 0);
		}
	}

	/**
	 * Post - process, Store custom fields mapping with events.
	 *
	 * @param   EventbookingTableField  $row
	 * @param   RADInput                $input
	 * @param   bool                    $isNew
	 */
	protected function afterStore($row, $input, $isNew)
	{
		$languages = EventbookingHelper::getLanguages();
		$config    = EventbookingHelper::getConfig();

		$categoriesAssignment = in_array($config->get('custom_field_assignment', 0), [0, 1]);
		$eventsAssignment     = in_array($config->get('custom_field_assignment', 0), [0, 2]);

		$assignment  = $input->getInt('assignment', 0);
		$categoryIds = $input->get('category_id', [], 'array');
		$categoryIds = ArrayHelper::toInteger($categoryIds);

		if (($eventsAssignment && $assignment == 0)
			|| in_array($row->id, $this->getRestrictedFieldIds()))
		{
			$row->event_id = -1;
		}
		else
		{
			$row->event_id = 1;
		}

		if (($categoriesAssignment && in_array(-1, $categoryIds))
			|| in_array($row->name, $this->getRestrictedFieldIds()))
		{
			$row->category_id = -1;
		}
		else
		{
			$row->category_id = 1;
		}

		$categoryIds = array_filter($categoryIds, function ($value) {
			return $value > 0;
		});

		// Assignment auto correction
		if ($categoriesAssignment)
		{
			// If use all except selected options but no categories are selected, need to assign to all categories to have it works
			if ($assignment == -1 && count($categoryIds) == 0)
			{
				$row->category_id = -1;
			}

			// In case field is assigned to certain categories, it should not be assigned to all selected events
			if ($assignment == 0 && $row->category_id == 1)
			{
				$row->event_id = 1;
				$input->set('assignment', 1);
			}
		}

		$row->store();

		if ($categoriesAssignment)
		{
			$this->storeFieldCategories($row, $input, $isNew);
		}

		if ($eventsAssignment)
		{
			$this->storeFieldEvents($row, $input, $isNew);
		}

		// Calculate depend on options in different languages
		if (Multilanguage::isEnabled()
			&& count($languages)
			&& $row->depend_on_field_id)
		{
			$this->storeMultilingualDependOnOptions($row, $languages);
		}

		// Store data changed (in method calls) to custom field back to database
		$row->store();
	}

	/**
	 * Store field to events assignment
	 *
	 * @param   EventbookingTableField  $row
	 * @param   RADInput                $input
	 * @param   bool                    $isNew
	 */
	protected function storeFieldEvents($row, $input, $isNew)
	{
		$db         = $this->getDbo();
		$query      = $db->getQuery(true);
		$config     = EventbookingHelper::getConfig();
		$assignment = $input->getInt('assignment', 0);
		$eventIds   = $input->get('event_id', [], 'array');
		$eventIds   = array_filter(ArrayHelper::toInteger($eventIds));

		// Delete the old field events assignment
		if (!$isNew)
		{
			// Delete all excepted event assignment if assignment changed
			if ($assignment >= 0)
			{
				$query->clear()
					->delete('#__eb_field_events')
					->where('field_id = ' . $row->id)
					->where('event_id <= 0');
				$db->setQuery($query)
					->execute();
			}
			else
			{
				$query->clear()
					->delete('#__eb_field_events')
					->where('field_id = ' . $row->id)
					->where('event_id > 0');
				$db->setQuery($query)
					->execute();
			}

			if ($row->event_id == -1)
			{
				// Field are assigned to all events, so delete all existing assignments
				$query->clear()
					->delete('#__eb_field_events')
					->where('field_id = ' . $row->id);
				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				// Field are assigned to some selected events, we need to delete events which were assigned before but not now
				$rowEvents   = EventbookingHelperDatabase::getAllEvents(
					$config->sort_events_dropdown,
					$config->hide_past_events_from_events_dropdown
				);
				$allEventIds = [];

				foreach ($rowEvents as $rowEvent)
				{
					$allEventIds[] = $rowEvent->id;
				}

				$noneSelectedEventIds = array_diff($allEventIds, $eventIds);

				if (count($noneSelectedEventIds))
				{
					$query->clear()
						->delete('#__eb_field_events')
						->where('field_id = ' . $row->id)
						->where('event_id IN (' . implode(',', $noneSelectedEventIds) . ')');
					$db->setQuery($query)
						->execute();

					$noneSelectedEventIds = array_map(function ($value) {
						return -1 * $value;
					}, $noneSelectedEventIds);

					$query->clear()
						->delete('#__eb_field_events')
						->where('field_id = ' . $row->id)
						->where('event_id IN (' . implode(',', $noneSelectedEventIds) . ')');
					$db->setQuery($query)
						->execute();
				}

				// Calculate new events which are assigned to this field
				$query->clear()
					->select('event_id')
					->from('#__eb_field_events')
					->where('field_id = ' . $row->id);
				$db->setQuery($query);
				$eventIds = array_diff($eventIds, $db->loadColumn());
			}
		}

		if ($row->event_id != -1 && count($eventIds))
		{
			$eventIds = array_values($eventIds);
			$eventIds = ArrayHelper::toInteger($eventIds);

			$query->clear()
				->insert('#__eb_field_events')->columns('field_id, event_id');

			foreach ($eventIds as $eventId)
			{
				$eventId *= $assignment;
				$query->values("$row->id, $eventId");
			}

			$db->setQuery($query)
				->execute();
		}
	}

	/**
	 * Store field to events assignment
	 *
	 * @param   EventbookingTableField  $row
	 * @param   RADInput                $input
	 * @param   bool                    $isNew
	 */
	protected function storeFieldCategories($row, $input, $isNew)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$categoryIds = $input->get('category_id', [], 'array');
		$categoryIds = ArrayHelper::toInteger($categoryIds);

		$categoryIds = array_filter($categoryIds, function ($value) {
			return $value > 0;
		});

		if (!$isNew)
		{
			// Delete the none assigned categories
			$query->clear()
				->delete('#__eb_field_categories')
				->where('field_id = ' . $row->id);

			if ($row->category_id != -1 && count($categoryIds) > 0)
			{
				$query->where('category_id NOT IN (' . implode(',', $categoryIds) . ')');
			}

			$db->setQuery($query)
				->execute();

			$query->clear()
				->select('category_id')
				->from('#__eb_field_categories')
				->where('field_id = ' . $row->id);
			$db->setQuery($query);
			$categoryIds = array_diff($categoryIds, $db->loadColumn());
		}

		if ($row->category_id != -1 && count($categoryIds) > 0)
		{
			$query->clear()
				->insert('#__eb_field_categories')->columns('field_id, category_id');

			$categoryIds = array_values($categoryIds);

			foreach ($categoryIds as $categoryId)
			{
				$categoryId = (int) $categoryId;
				$query->values("$row->id, $categoryId");
			}

			$db->setQuery($query)
				->execute();
		}
	}

	/**
	 * Store depends on options for multilingual
	 *
	 * @param   EventbookingTableField  $row
	 * @param   array                   $languages
	 */
	protected function storeMultilingualDependOnOptions($row, $languages)
	{
		$masterField = $this->getTable();
		$masterField->load($row->depend_on_field_id);
		$masterFieldValues = explode("\r\n", $masterField->values);
		$dependOnOptions   = json_decode($row->depend_on_options);
		$dependOnIndexes   = [];

		foreach ($dependOnOptions as $option)
		{
			$index = array_search($option, $masterFieldValues);

			if ($index !== false)
			{
				$dependOnIndexes[] = $index;
			}
		}

		foreach ($languages as $language)
		{
			$sef                             = $language->sef;
			$dependOnOptionsWithThisLanguage = [];
			$values                          = explode("\r\n", $masterField->{'values_' . $sef});

			foreach ($dependOnIndexes as $index)
			{
				if (isset($values[$index]))
				{
					$dependOnOptionsWithThisLanguage[] = $values[$index];
				}
			}

			$row->{'depend_on_options_' . $sef} = json_encode($dependOnOptionsWithThisLanguage);
		}
	}

	/**
	 * Method to remove  fields
	 *
	 * @access    public
	 * @return    boolean    True on success
	 */
	public function delete($cid = [])
	{
		if (count($cid) === 0)
		{
			return true;
		}

		$db   = $this->getDbo();
		$cids = implode(',', $cid);

		$query = $db->getQuery(true)
			->delete('#__eb_field_values')->where('field_id IN (' . $cids . ')');
		$db->setQuery($query)
			->execute();

		$query->clear()
			->delete('#__eb_field_events')->where('field_id IN (' . $cids . ')');
		$db->setQuery($query)
			->execute();

		$query->clear()
			->delete('#__eb_field_categories')->where('field_id IN (' . $cids . ')');
		$db->setQuery($query)
			->execute();

		//Do not allow deleting core fields
		$query->clear()
			->delete('#__eb_fields')->where('id IN (' . $cids . ') AND is_core=0');
		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Change require status
	 *
	 * @param   array  $cid
	 * @param   int    $state
	 *
	 * @return boolean
	 */
	public function required($cid, $state)
	{
		$cids  = implode(',', $cid);
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->update('#__eb_fields')
			->set('required=' . $state)
			->where('id IN (' . $cids . ' )');
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array  $pks    A list of the primary keys to change.
	 * @param   int    $value  The value of the published state.
	 *
	 * @throws Exception
	 */
	public function publish($pks, $value = 1)
	{
		$restrictedFieldIds = $this->getRestrictedFieldIds();
		$pks                = array_diff($pks, $restrictedFieldIds);

		if (count($pks))
		{
			parent::publish($pks, $value);
		}
	}

	/**
	 * Get Ids of restricted fields which cannot be changed status, ddeleted...
	 *
	 * @return array
	 */
	protected function getRestrictedFieldIds()
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__eb_fields')
			->where('name IN ("email")');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Initial data for new record
	 *
	 * @return void
	 */
	protected function initData()
	{
		parent::initData();

		$this->data->discountable                        = 1;
		$this->data->populate_from_previous_registration = 1;
		$this->data->position                            = 0;
		$this->data->taxable                             = 1;
		$this->data->display_in                          = 5;
	}
}
