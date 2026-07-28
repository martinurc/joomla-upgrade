<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;

class EventbookingHelperDatabase
{
	/**
	 * Get category data from database
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function getCategory($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_categories')
			->where('id = ' . (int) $id);

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			self::getMultilingualFields($query, [
				'name',
				'page_title',
				'page_heading',
				'meta_keywords',
				'meta_description',
				'description',
			], $fieldSuffix);
		}

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Get event information from database
	 *
	 * @param   int     $id
	 * @param   string  $currentDate
	 * @param   string  $fieldSuffix
	 * @param   bool    $reload
	 *
	 * @return mixed
	 */
	public static function getEvent($id, $currentDate = null, $fieldSuffix = null, $reload = false)
	{
		static $events = [];

		$cacheKey = $id . $currentDate . $fieldSuffix;

		if (!array_key_exists($cacheKey, $events) || $reload)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			if ($fieldSuffix === null)
			{
				$fieldSuffix = EventbookingHelper::getFieldSuffix();
			}

			if (empty($currentDate))
			{
				$currentDate = EventbookingHelper::getServerTimeFromGMTTime();
			}

			$currentDate = $db->quote($currentDate);

			$query->select('a.*, (IFNULL(SUM(b.number_registrants), 0) + a.private_booking_count) AS total_registrants')
				->select("DATEDIFF(event_date, $currentDate) AS number_event_dates")
				->select("TIMESTAMPDIFF(MINUTE, a.event_date, $currentDate) AS event_start_minutes")
				->select("DATEDIFF($currentDate, a.late_fee_date) AS late_fee_date_diff")
				->select("TIMESTAMPDIFF(SECOND, registration_start_date, $currentDate) AS registration_start_minutes")
				->select("TIMESTAMPDIFF(MINUTE, cut_off_date, $currentDate) AS cut_off_minutes")
				->select("TIMESTAMPDIFF(MINUTE, $currentDate, early_bird_discount_date) AS date_diff")
				->select('l.lat, l.long, l.address AS location_address')
				->select($db->quoteName('l.name' . $fieldSuffix, 'location_name'))
				->select('c.tax_rate AS cat_tax_rate')
				->from('#__eb_events AS a')
				->leftJoin('#__eb_categories AS c ON a.main_category_id = c.id')
				->leftJoin('#__eb_locations AS l ON a.location_id = l.id')
				->leftJoin(
					'#__eb_registrants AS b ON (a.id = b.event_id AND b.group_id=0 AND (b.published = 1 OR (b.published = 0 AND b.payment_method LIKE "os_offline%")))'
				)
				->where('a.id = ' . (int) $id);

			if ($fieldSuffix)
			{
				self::getMultilingualFields(
					$query,
					['a.title', 'a.short_description', 'a.description', 'a.meta_keywords', 'a.meta_description'],
					$fieldSuffix
				);
			}

			$query->group('a.id');
			$db->setQuery($query);

			$event = $db->loadObject();

			if ($event)
			{
				$event->tax_rate = EventbookingHelperRegistration::calculateEventTaxRate($event);

				$params = new Registry($event->params);

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

				foreach ($keys as $key)
				{
					if ($params->exists($key) || !property_exists($event, $key))
					{
						$event->{$key} = $params->get($key, '');
					}
				}
			}

			$events[$cacheKey] = $event;
		}

		return $events[$cacheKey];
	}

	/**
	 * Method to load location object from database
	 *
	 * @param   int     $id
	 * @param   string  $fieldSuffix
	 *
	 * @return mixed
	 */
	public static function getLocation($id, $fieldSuffix = null)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_locations')
			->where('id = ' . (int) $id);

		if ($fieldSuffix === null)
		{
			$fieldSuffix = EventbookingHelper::getFieldSuffix();
		}

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['name', 'alias', 'description'], $fieldSuffix);
		}

		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Method to get group registration rates for an event
	 *
	 * @param $eventId
	 *
	 * @return array
	 */
	public static function getGroupRegistrationRates($eventId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_event_group_prices')
			->where('event_id = ' . (int) $eventId)
			->order('id');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all published categories
	 *
	 * @param   string  $order
	 * @param   bool    $checkAccessLevel
	 *
	 * @return mixed
	 */
	public static function getAllCategories($order = 'title', $checkAccessLevel = false)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, parent AS parent_id, name AS title')
			->from('#__eb_categories')
			->where('published=1')
			->order($order);

		if ($checkAccessLevel)
		{
			$user = Factory::getUser();

			if (!$user->authorise('core.admin', 'com_eventbooking'))
			{
				$query->where('submit_event_access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
			}
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all published events
	 *
	 * @param   string  $order
	 * @param   bool    $hidePastEvents
	 * @param   array   $wheres
	 * @param   string  $fieldSuffix
	 *
	 * @return mixed
	 */
	public static function getAllEvents($order = 'title', $hidePastEvents = false, $wheres = [], $fieldSuffix = null)
	{
		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select('id, event_date')
			->select($db->quoteName('title' . $fieldSuffix, 'title'))
			->from('#__eb_events')
			->order($order);

		if ($config->get('hide_unpublished_events_from_events_dropdown', '1') === '1')
		{
			$query->where('published = 1');
		}

		if ($hidePastEvents)
		{
			$currentDate = $db->quote(HTMLHelper::_('date', 'Now', 'Y-m-d'));
			$query->where('(DATE(event_date) >= ' . $currentDate . ' OR DATE(event_end_date) >= ' . $currentDate . ')');
		}

		foreach ($wheres as $where)
		{
			$query->where($where);
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all published countries
	 *
	 * @param   string  $order
	 *
	 * @return mixed
	 */
	public static function getAllCountries($order = 'name')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, name')
			->from('#__eb_countries')
			->where('published')
			->order($order);
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all locations in the system
	 *
	 * @param   string  $order
	 *
	 * @return mixed
	 */
	public static function getAllLocations($order = 'name')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, name')
			->from('#__eb_locations')
			->where('published = 1')
			->order($order);
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Helper method to get fields from database table in case the site is multilingual
	 *
	 * @param   JDatabaseQuery  $query
	 * @param   array           $fields
	 * @param   string          $fieldSuffix
	 */
	public static function getMultilingualFields(JDatabaseQuery $query, $fields, $fieldSuffix)
	{
		foreach ($fields as $field)
		{
			$alias  = $field;
			$dotPos = strpos($field, '.');

			if ($dotPos !== false)
			{
				$alias = substr($field, $dotPos + 1);
			}

			$query->select($query->quoteName($field . $fieldSuffix, $alias));
		}
	}

	/**
	 * Helper method to get fields from database table in case the site is multilingual
	 *
	 * @param   JDatabaseQuery  $query
	 * @param   array           $fields
	 * @param   string          $fieldSuffix
	 */
	public static function getMultilingualFieldsUseDefaultLanguageData(JDatabaseQuery $query, $fields, $fieldSuffix)
	{
		foreach ($fields as $field)
		{
			$alias  = $field;
			$dotPos = strpos($field, '.');

			if ($dotPos !== false)
			{
				$alias = substr($field, $dotPos + 1);
			}

			$quotedFieldWithSuffix = $query->quoteName($field . $fieldSuffix);
			$quotedField           = $query->quoteName($field);
			$quotedAlias           = $query->quoteName($alias);

			$query->select("IF(LENGTH($quotedFieldWithSuffix) > 0, $quotedFieldWithSuffix, $quotedField) AS $quotedAlias");
		}
	}

	/**
	 * Apply past events filter to query depends on several config options
	 *
	 * @param   JDatabaseQuery  $query
	 * @param   string          $tableAlias
	 *
	 * @deprecated
	 */
	public static function applyHidePastEventsFilter($query, $tableAlias = 'tbl.')
	{
		$config = EventbookingHelper::getConfig();

		if ($config->show_upcoming_events)
		{
			$currentDate = Factory::getDbo()->quote(EventbookingHelper::getServerTimeFromGMTTime());
		}
		else
		{
			$currentDate = Factory::getDbo()->quote(HTMLHelper::_('date', 'Now', 'Y-m-d'));
		}

		$fields = [$tableAlias . 'event_date'];

		if ($config->show_until_end_date)
		{
			$fields[] = $tableAlias . 'event_end_date';
		}
		else
		{
			$fields[] = $tableAlias . 'cut_off_date';
		}

		if ($config->show_children_events_under_parent_event)
		{
			$fields[] = $tableAlias . 'max_end_date';
		}

		$conditions = [];

		// Show until current date time greater than event date time
		if ($config->show_upcoming_events)
		{
			foreach ($fields as $field)
			{
				$conditions[] = $field . ' >= ' . $currentDate;
			}
		}
		else
		{
			foreach ($fields as $field)
			{
				$conditions[] = 'DATE(' . $field . ') >= ' . $currentDate;
			}
		}

		$query->where('(' . implode(' OR ', $conditions) . ')');
	}

	/**
	 * Get ID of categories which event is assigned to
	 *
	 * @param   int[]  $eventIds
	 *
	 * @return array
	 */
	public static function getEventCategories($eventIds)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('a.id')
			->from('#__eb_categories AS a')
			->innerJoin('#__eb_event_categories AS b ON a.id = b.category_id')
			->where('b.event_id IN (' . implode(',', $eventIds) . ') ')
			->where('a.published = 1');
		$db->setQuery($query);

		return $db->loadColumn();
	}
}
