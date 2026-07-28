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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

class EventbookingViewFullcalendarHtml extends RADViewHtml
{
	public function display()
	{
		HTMLHelper::_('behavior.core');

		$config  = EventbookingHelper::getConfig();
		$rootUri = Uri::root(true);

		// Full calendar tooltip needs jQuery, so we need to load it
		if ($config->display_event_in_tooltip)
		{
			HTMLHelper::_('jquery.framework');
		}

		$document = Factory::getApplication()->getDocument();

		if (Factory::getLanguage()->getTag() !== 'en-GB')
		{
			$document->addScript($rootUri . '/media/com_eventbooking/fullcalendar/moment-with-locales.min.js');
		}
		else
		{
			$document->addScript($rootUri . '/media/com_eventbooking/fullcalendar/moment.min.js');
		}

		$document->addScript($rootUri . '/media/com_eventbooking/fullcalendar/main.min.js')
			->addScript($rootUri . '/media/com_eventbooking/fullcalendar/main.global.min.js');

		EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/js/site-fullcalendar-default.min.js');

		$document->addScriptOptions('calendarOptions', $this->getCalendarOptions())
			->addScriptOptions('displayEventInTooltip', (bool) $config->display_event_in_tooltip)
			->addStyleSheet($rootUri . '/media/com_eventbooking/fullcalendar/main.min.css');

		if ($this->params->get('menu_item_id') > 0)
		{
			$this->Itemid = $this->params->get('menu_item_id');
		}

		$this->setDocumentMetadata();

		parent::display();
	}

	/**
	 * Method to get full calendar options
	 *
	 * @return array
	 */
	protected function getCalendarOptions()
	{
		$config = EventbookingHelper::getConfig();
		$date   = new DateTime('now', new DateTimeZone(Factory::getApplication()->get('offset')));
		$year   = $this->params->get('default_year') ?: $date->format('Y');
		$month  = $this->params->get('default_month') ?: $date->format('m');
		$month  = str_pad($month, 2, '0', STR_PAD_LEFT);

		$buttons = [];

		if ($this->params->get('show_month_button'))
		{
			$buttons[] = 'dayGridMonth';
		}

		if ($this->params->get('show_week_button'))
		{
			$buttons[] = 'timeGridWeek';
		}

		if ($this->params->get('show_day_button'))
		{
			$buttons[] = 'timeGridDay';
		}

		if (count($buttons) == 1)
		{
			$buttons = [];
		}

		$defaultView = $this->params->get('default_view', 'month');

		$oldVersionViews = [
			'month'      => 'dayGridMonth',
			'agendaWeek' => 'timeGridWeek',
			'agendaDay'  => 'timeGridDay',
		];

		if (isset($oldVersionViews[$defaultView]))
		{
			$defaultView = $oldVersionViews[$defaultView];
		}

		$options = [
			'headerToolbar'    => [
				'left'   => 'prev,next' . ($this->params->get('show_today_button', 1) ? ' today' : ''),
				'center' => 'title',
				'right'  => implode(',', $buttons),
			],
			'locale'           => $this->getLocale(),
			'eventDisplay'     => 'block',
			'initialView'      => $defaultView,
			'initialDate'      => $year . '-' . $month . '-' . $date->format('d'),
			'navLinks'         => true,
			'eventSources'     => [
				Route::_(
					'index.php?option=com_eventbooking&view=fullcalendar&format=raw&Itemid=' . $this->Itemid,
					false
				),
			],
			'firstDay'         => (int) $config->calendar_start_date,
			'weekends'         => (bool) $this->params->get('show_weekend', 1),
			'hiddenDays'       => ArrayHelper::toInteger($this->params->get('hidden_days')),
			'displayEventTime' => (bool) $config->show_event_time,
			'eventTimeFormat'  => $this->params->get('event_time_format', 'HH:mm'),
			'slotLabelFormat'  => $this->params->get('slot_label_format', 'ha'),
			'slotMinTime'      => $this->params->get('slot_min_time', '00:00:00'),
			'slotMaxTime'      => $this->params->get('slot_max_time', '24:00:00'),
			'allDaySlot'       => (bool) $this->params->get('show_all_day_slot', 0),
			'buttonText'       => [
				'today' => Text::_('EB_TODAY'),
				'month' => Text::_('EB_MONTH'),
				'week'  => Text::_('EB_WEEK'),
				'day'   => Text::_('EB_DAY'),
			],
			'views'            => [
				'dayGridMonth' => [
					'titleFormat'         => $this->params->get('title_format_month', 'MMMM YYYY'),
					'showNonCurrentDates' => (bool) $this->params->get('show_non_current_dates', false),
					'fixedWeekCount'      => false,
				],
				'timeGridWeek' => [
					'titleFormat'     => $this->params->get('title_format_week', 'MMM D YYYY'),
					'dayHeaderFormat' => $this->params->get('day_header_format_week', 'ddd M/D'),
				],
				'timeGridDay'  => [
					'titleFormat' => $this->params->get('title_format_day', 'MMMM D YYYY'),
				],
			],
		];

		if ($options['locale'] !== 'en')
		{
			unset($options['views']['dayGridMonth']['titleFormat']);
		}

		return $options;
	}

	/**
	 * Get locale for calendar
	 *
	 * @return string
	 */
	private function getLocale()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('sef'))
			->from('#__languages')
			->where('lang_code = ' . $db->quote(Factory::getLanguage()->getTag()));
		$db->setQuery($query);

		return $db->loadResult() ?: 'en';
	}
}
