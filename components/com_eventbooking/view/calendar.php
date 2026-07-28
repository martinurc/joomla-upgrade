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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

trait EventbookingViewCalendar
{
	/**
	 * @param   array     $rows
	 * @param   Registry  $params
	 * @param   int       $Itemid
	 *
	 * @throws Exception
	 */
	protected function prepareCalendarData(&$rows, $params, $Itemid)
	{
		$config    = EventbookingHelper::getConfig();
		$rootUri   = Uri::root(true);
		$thumbPath = JPATH_ROOT . '/media/com_eventbooking/images/thumbs/';

		if ($config->display_event_in_tooltip)
		{
			EventbookingHelper::callOverridableHelperMethod('Data', 'preProcessEventData', [$rows, 'list']);
		}

		foreach ($rows as $row)
		{
			if ($config->show_children_events_under_parent_event && $row->parent_id > 0)
			{
				$eventId = $row->parent_id;
			}
			else
			{
				$eventId = $row->id;
			}

			if ($params->get('link_event_to_registration_form') && EventbookingHelper::callOverridableHelperMethod(
					'Registration',
					'acceptRegistration',
					[$row]
				))
			{
				if ($row->registration_handle_url)
				{
					$url = $row->registration_handle_url;
				}
				else
				{
					$url = Route::_(
						'index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $eventId . '&Itemid=' . $Itemid
					);
				}
			}
			elseif ($row->event_detail_url)
			{
				$url = $row->event_detail_url;
			}
			else
			{
				$url = Route::_(EventbookingHelperRoute::getEventRoute($eventId, $row->main_category_id, $Itemid));
			}

			$row->url = $url;

			if ($row->color_code)
			{
				$row->backgroundColor = '#' . $row->color_code;
			}
			else
			{
				$row->backgroundColor = '';
			}

			if ($row->text_color)
			{
				$row->textColor = '#' . $row->text_color;
			}
			else
			{
				$row->textColor = '';
			}

			if ($config->show_thumb_in_calendar && $row->thumb && file_exists($thumbPath . $row->thumb))
			{
				$row->thumb = $rootUri . '/media/com_eventbooking/images/thumbs/' . $row->thumb;
			}
			else
			{
				$row->thumb = '';
			}

			if ($config->display_event_in_tooltip)
			{
				$layoutData = [
					'item'     => $row,
					'config'   => $config,
					'nullDate' => Factory::getDbo()->getNullDate(),
					'Itemid'   => $Itemid,
				];

				$row->tooltip = EventbookingHelperHtml::loadCommonLayout('common/calendar_tooltip.php', $layoutData);
			}
			else
			{
				$row->tooltip = '';
			}

			if ($row->event_capacity > 0 && $row->total_registrants >= $row->event_capacity)
			{
				$row->eventFull = 1;
			}
			else
			{
				$row->eventFull = 0;
			}
		}

		if ($params->get('hide_full_events'))
		{
			$rows = array_filter($rows, function ($row) {
				return $row->eventFull == 0;
			});

			$rows = array_values($rows);
		}
	}
}
