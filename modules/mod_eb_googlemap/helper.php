<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

class modEventBookingGoogleMapHelper
{
	/**
	 * @param   Joomla\Registry\Registry  $params
	 * @param   int                       $Itemid
	 *
	 * @return array
	 */
	public static function loadAllLocations($params, $Itemid)
	{
		$user   = Factory::getUser();
		$db     = Factory::getDbo();
		$config = EventbookingHelper::getConfig();

		$categoryIds        = array_filter(ArrayHelper::toInteger($params->get('category_ids', [])));
		$excludeCategoryIds = array_filter(ArrayHelper::toInteger($params->get('exclude_category_ids', [])));
		$locationIds        = $params->get('location_ids');
		$numberEvents       = $params->get('number_events', 10);
		$hidePastEvents     = $params->get('hide_past_events', 1);
		$currentDate        = $db->quote(HTMLHelper::_('date', 'Now', 'Y-m-d'));
		$filterDuration     = $params->get('duration_filter');

		$nullDate    = $db->quote($db->getNullDate());
		$nowDate     = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		$query = $db->getQuery(true)
			->select('a.id, a.location_id, a.main_category_id')
			->select($db->quoteName('a.title' . $fieldSuffix, 'title'))
			->select('l.lat, l.long, l.address')
			->select($db->quoteName('l.name' . $fieldSuffix, 'name'))
			->from('#__eb_events AS a')
			->innerJoin('#__eb_locations AS l ON a.location_id = l.id')
			->where('l.published = 1')
			->where('(l.lat != 0 OR l.long != 0)')
			->where('a.published = 1')
			->where('a.hidden = 0')
			->where('a.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
			->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
			->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');

		if ($locationIds)
		{
			$query->where('l.id IN (' . implode(',', $locationIds) . ')');
		}

		if ($categoryIds)
		{
			$query->where('a.id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (' . implode(',', $categoryIds) . '))');
		}

		if ($excludeCategoryIds)
		{
			$query->where(
				'a.id NOT IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (' . implode(',', $excludeCategoryIds) . '))'
			);
		}

		if ($hidePastEvents)
		{
			if ($config->show_until_end_date)
			{
				$query->where('(DATE(a.event_date) >= ' . $currentDate . ' OR DATE(a.event_end_date) >= ' . $currentDate . ')');
			}
			else
			{
				$query->where('(DATE(a.event_date) >= ' . $currentDate . ' OR DATE(a.cut_off_date) >= ' . $currentDate . ')');
			}
		}

		if ($filterDuration)
		{
			[$fromDate, $toDate] = EventbookingHelper::getDateDuration($filterDuration, true);
			$query->where('a.event_date >= ' . $db->quote($fromDate))
				->where('a.event_date <= ' . $db->quote($toDate));
		}

		if ($fieldSuffix)
		{
			$query->where('LENGTH(' . $db->quoteName('a.title' . $fieldSuffix) . ') > 0');
		}

		$query->order('a.event_date, a.ordering');

		$db->setQuery($query);

		$rows = [];

		foreach ($db->loadObjectList() as $row)
		{
			$rows[$row->location_id][] = $row;
		}

		$locations = [];

		foreach ($rows as $locationId => $events)
		{
			$location = new stdClass();
			$event    = $events[0];

			$location->id      = $locationId;
			$location->lat     = $event->lat;
			$location->long    = $event->long;
			$location->address = $event->address;
			$location->name    = $event->name;

			$popupContent   = [];
			$popupContent[] = '<div class="row-fluid">';
			$popupContent[] = '<ul class="bubble">';
			$popupContent[] = '<li class="location_name"><h4>' . $location->name . '</h4></li>';
			$popupContent[] = '<p class="location_address">' . $location->address . '</p>';
			$popupContent[] = '</ul>';

			$popupContent[] = '<ul>';
			$events         = array_slice($events, 0, $numberEvents);

			foreach ($events as $event)
			{
				$popupContent[] = '<li><h4>' . HTMLHelper::link(
						Route::_(EventbookingHelperRoute::getEventRoute($event->id, $event->main_category_id, $Itemid)),
						$event->title
					) . '</h4></li>';
			}

			$popupContent[] = '</ul>';

			$location->popupContent = implode('', $popupContent);

			$locations[] = $location;
		}


		return $locations;
	}
}
