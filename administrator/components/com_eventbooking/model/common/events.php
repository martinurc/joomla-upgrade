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
use Joomla\CMS\HTML\HTMLHelper;

class EventbookingModelCommonEvents extends RADModelList
{
	/**
	 * Selected event ids which will be exported
	 *
	 * @var array
	 */
	protected $eventIds = [];

	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  configuration data for the model
	 */
	public function __construct($config = [])
	{
		if (!array_key_exists('search_fields', $config))
		{
			$config['search_fields'] = ['tbl.title', 'tbl.alias', 'tbl.custom_fields'];
		}

		parent::__construct($config);

		$this->state->insert('filter_category_id', 'int', 0)
			->insert('filter_location_id', 'int', 0)
			->insert('filter_events', 'int', 1)
			->insert('filter_upcoming_events', 'int', 0)
			->insert('filter_from_date', 'string', '')
			->insert('filter_to_date', 'string', '')
			->insert('filter_featured', 'string', '')
			->setDefault('filter_order', 'tbl.event_date');
	}

	/**
	 * Get list of categories belong to each event before it is displayed
	 *
	 * @param   array  $rows
	 */
	protected function beforeReturnData($rows)
	{
		if (!count($rows))
		{
			return;
		}

		$db              = $this->getDbo();
		$query           = $db->getQuery(true);
		$eventCategories = [];
		$ids             = [];

		foreach ($rows as $row)
		{
			$ids[] = $row->id;
		}

		$query->select('a.name, b.event_id FROM #__eb_categories AS a')
			->innerJoin('#__eb_event_categories AS b ON a.id = b.category_id')
			->where('b.event_id IN (' . implode(',', $ids) . ')')
			->order('b.id');
		$db->setQuery($query);

		foreach ($db->loadObjectList() as $rowEventCategory)
		{
			$eventCategories[$rowEventCategory->event_id][] = $rowEventCategory;
		}

		foreach ($rows as $row)
		{
			// Prevent the special case event has no categories
			if (!isset($eventCategories[$row->id]))
			{
				$row->category = $row->category_name = $row->additional_categories = '';

				continue;
			}

			$categories           = $eventCategories[$row->id];
			$categoryNames        = [];
			$additionalCategories = [];

			for ($i = 0, $n = count($categories); $i < $n; $i++)
			{
				$category        = $categories[$i];
				$categoryNames[] = $category->name;

				if ($i == 0)
				{
					$row->category = $category->name;
				}
				else
				{
					$additionalCategories[] = $category->name;
				}

				$row->category_name         = implode(' | ', $categoryNames);
				$row->additional_categories = implode(' | ', $additionalCategories);
			}
		}
	}

	/**
	 * Builds SELECT columns list for the query
	 */
	protected function buildQueryColumns(JDatabaseQuery $query)
	{
		$query->select('tbl.*')
			->select('vl.title AS access_level')
			->select('u.name, u.username')
			->select(
				$this->getDbo()->quoteName(['l.name', 'l.address', 'l.city', 'l.state'],
					['location', 'location_address', 'location_city', 'location_state'])
			)
			->select('(IFNULL(SUM(rgt.number_registrants), 0) + tbl.private_booking_count) AS total_registrants');

		return $this;
	}

	/**
	 * Builds LEFT JOINS clauses for the query
	 */
	protected function buildQueryJoins(JDatabaseQuery $query)
	{
		$query->leftJoin('#__viewlevels AS vl ON vl.id = tbl.access')
			->leftJoin('#__users AS u ON tbl.created_by = u.id')
			->leftJoin('#__eb_locations AS l ON tbl.location_id = l.id')
			->leftJoin(
				'#__eb_registrants AS rgt ON (tbl.id = rgt.event_id AND rgt.group_id = 0 AND (rgt.published=1 OR (rgt.published = 0 AND rgt.payment_method LIKE "os_offline%")))'
			);

		return $this;
	}

	/**
	 * Build where clase of the query
	 *
	 * @see RADModelList::buildQueryWhere()
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$user       = Factory::getUser();
		$db         = $this->getDbo();
		$config     = EventbookingHelper::getConfig();
		$dateFormat = str_replace('%', '', $config->get('date_field_format', '%Y-%m-%d')) . ' H:i:s';

		if (!empty($this->eventIds))
		{
			$query->where('tbl.id IN (' . implode(',', $this->eventIds) . ')');
		}

		if ($this->state->filter_category_id)
		{
			$allCategoryIds = EventbookingHelperData::getAllChildrenCategories($this->state->filter_category_id);
			$query->where(
				'tbl.id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (' . implode(
					',',
					$allCategoryIds
				) . '))'
			);
		}

		if (!$user->authorise('core.admin', 'com_eventbooking'))
		{
			$query->where(
				'tbl.id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (SELECT id FROM #__eb_categories WHERE submit_event_access IN (' . implode(
					',',
					$user->getAuthorisedViewLevels()
				) . ')))'
			);
		}

		if ($this->state->filter_location_id)
		{
			$query->where('tbl.location_id=' . $this->state->filter_location_id);
		}

		if ($this->state->filter_events == 1)
		{
			$currentDate = $this->getDbo()->quote(HTMLHelper::_('date', 'Now', 'Y-m-d'));
			$query->where('(DATE(tbl.event_date) >= ' . $currentDate . ' OR DATE(tbl.event_end_date) >= ' . $currentDate . ')');
		}
		elseif ($this->state->filter_events == 2)
		{
			$query->where('tbl.parent_id = 0');
		}

		if (in_array($this->state->filter_featured, ['0', '1'], true))
		{
			$query->where('tbl.featured = ' . (int) $this->state->filter_featured);
		}

		if ($this->state->filter_upcoming_events)
		{
			$currentDate = $this->getDbo()->quote(EventbookingHelper::getServerTimeFromGMTTime());
			$query->where('tbl.event_date >= ' . $currentDate);
		}

		if ((int) $this->state->filter_from_date)
		{
			// In case use only select date, we will set time of From Date to 00:00:00
			if (strpos($this->state->filter_from_date, ' ') === false && strlen($this->state->filter_from_date) <= 10)
			{
				$fromDate = $this->state->filter_from_date . ' 00:00:00';
			}
			else
			{
				$fromDate = $this->state->filter_from_date;
			}

			try
			{
				$date = DateTime::createFromFormat($dateFormat, $fromDate);

				if ($date !== false)
				{
					$date->setTime(0, 0, 0);
					$query->where('tbl.event_date >= ' . $db->quote($date->format('Y-m-d H:i:s')));
				}
			}
			catch (Exception $e)
			{
				// Do-nothing
			}
		}

		if ((int) $this->state->filter_to_date)
		{
			// In case use only select date, we will set time of To Date to 23:59:59
			// In case use only select date, we will set time of From Date to 00:00:00
			if (strpos($this->state->filter_to_date, ' ') === false && strlen($this->state->filter_to_date) <= 10)
			{
				$toDate = $this->state->filter_to_date . ' 23:59:59';
			}
			else
			{
				$toDate = $this->state->filter_to_date;
			}

			try
			{
				$date = DateTime::createFromFormat($dateFormat, $toDate);

				if ($date !== false)
				{
					$date->setTime(23, 59, 59);
					$query->where('tbl.event_date <= ' . $db->quote($date->format('Y-m-d H:i:s')));
				}
			}
			catch (Exception $e)
			{
				// Do-nothing
			}
		}

		return parent::buildQueryWhere($query);
	}

	protected function buildQueryGroup(JDatabaseQuery $query)
	{
		$query->group('tbl.id');

		return $this;
	}

	/**
	 * Setter method to set selected event ids which will be exported
	 *
	 * @param   array  $eventIds
	 */
	public function setEventIds($eventIds = [])
	{
		$this->eventIds = $eventIds;
	}
}
