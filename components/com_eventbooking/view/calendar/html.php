<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class EventbookingViewCalendarHtml extends RADViewHtml
{
	use EventbookingViewCalendar;

	/**
	 * The model state
	 *
	 * @var RADModelState
	 */
	protected $state;

	/**
	 * The intro text
	 *
	 * @var string
	 */
	protected $introText;

	/**
	 * Events data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The selected year in calendar
	 *
	 * @var int
	 */
	protected $year;

	/**
	 * The selected month in calendar
	 *
	 * @var int
	 */
	protected $month;

	/**
	 * The years filter dropdown
	 *
	 * @var string
	 */
	protected $searchYear;

	/**
	 * The month filter dropdown
	 *
	 * @var string
	 */
	protected $searchMonth;

	/**
	 * Current date data
	 *
	 * @var array
	 */
	protected $currentDateData;

	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * List of months
	 *
	 * @var array
	 */
	protected $listMonth;

	/**
	 * Whether the system should show the calendar navigation
	 *
	 * @var bool
	 */
	protected $showCalendarMenu;

	/**
	 * Events data, used for weekly and daily layout
	 *
	 * @var array
	 */
	protected $events;

	/**
	 * The first day of the selected week
	 *
	 * @var string
	 */
	protected $first_day_of_week;

	/**
	 * The selected day in daily layout
	 *
	 * @var string
	 */
	protected $day;

	/**
	 * Display calendar
	 *
	 * @throws Exception
	 */
	public function display()
	{
		/* @var EventbookingModelCalendar $model */
		$model = $this->getModel();

		$config = EventbookingHelper::getConfig();
		$layout = $this->getLayout();

		$this->config           = $config;
		$this->showCalendarMenu = $config->activate_weekly_calendar_view || $config->activate_daily_calendar_view;

		#Support Weekly and Daily
		if ($layout == 'weekly')
		{
			$this->displayWeeklyView();

			return;
		}
		elseif ($layout == 'daily')
		{
			$this->displayDailyView();

			return;
		}

		$this->setLayout('default');

		// Use override menu item
		if ($this->params->get('menu_item_id') > 0)
		{
			$this->Itemid = $this->params->get('menu_item_id');
		}

		$rows = $model->getData();

		$this->prepareCalendarData($rows, $this->params, $this->Itemid);

		//Set evens alias to EventbookingHelperRoute to improve performance
		$eventsAlias = [];

		foreach ($rows as $row)
		{
			if ($config->insert_event_id)
			{
				$eventsAlias[$row->id] = $row->id . '-' . $row->alias;
			}
			else
			{
				$eventsAlias[$row->id] = $row->alias;
			}
		}

		EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$rows, ['title', 'price_text']]);

		$state = $model->getState();
		$year  = $state->year;
		$month = $state->month;

		$this->data  = EventbookingHelperData::getCalendarData($rows, $year, $month);
		$this->month = $month;
		$this->year  = $year;

		$listMonth = [
			Text::_('JANUARY'),
			Text::_('FEBRUARY'),
			Text::_('MARCH'),
			Text::_('APRIL'),
			Text::_('MAY'),
			Text::_('JUNE'),
			Text::_('JULY'),
			Text::_('AUGUST'),
			Text::_('SEPTEMBER'),
			Text::_('OCTOBER'),
			Text::_('NOVEMBER'),
			Text::_('DECEMBER'),
		];

		$options = [];

		foreach ($listMonth as $key => $monthName)
		{
			$value     = $key + 1;
			$options[] = HTMLHelper::_('select.option', $value, $monthName);
		}

		$this->searchMonth = HTMLHelper::_(
			'select.genericlist',
			$options,
			'month',
			'class="input-medium form-select w-auto" onchange="submit();" ',
			'value',
			'text',
			(int) $month
		);

		$options = [];

		$startYear = max((int) $this->params->get('start_year'), $year - 3);

		for ($i = $startYear; $i < ($year + 5); $i++)
		{
			$options[] = HTMLHelper::_('select.option', $i, $i);
		}

		$this->searchYear = HTMLHelper::_(
			'select.genericlist',
			$options,
			'year',
			'class="input-medium form-select w-auto" onchange="submit();" ',
			'value',
			'text',
			$year
		);

		$fieldSuffix = EventbookingHelper::getFieldSuffix();
		$message     = EventbookingHelper::getMessages();

		$categoryIds = array_filter(ArrayHelper::toInteger($this->params->get('category_ids')));

		if (count($categoryIds) == 1)
		{
			$categoryId = $categoryIds[0];
			$category   = EventbookingHelperDatabase::getCategory($categoryId);
			$introText  = $category->description;
		}
		elseif (EventbookingHelper::isValidMessage($this->params->get('intro_text')))
		{
			$introText = $this->params->get('intro_text');
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'intro_text' . $fieldSuffix}))
		{
			$introText = $message->{'intro_text' . $fieldSuffix};
		}
		else
		{
			$introText = $message->intro_text;
		}

		if ($introText)
		{
			$introText = HTMLHelper::_('content.prepare', $introText);
		}

		EventbookingHelperRoute::$eventsAlias = array_filter($eventsAlias);

		$this->listMonth       = $listMonth;
		$this->introText       = $introText;
		$this->state           = $model->getState();
		$this->currentDateData = $model->getCurrentDate();

		$this->setDocumentMetadata();

		parent::display();
	}

	/**
	 * Display weekly events
	 */
	protected function displayWeeklyView()
	{
		/* @var EventbookingModelCalendar $model */
		$model = $this->getModel();

		$this->events = $model->getEventsByWeek();

		foreach ($this->events as $events)
		{
			EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$events, ['title', 'price_text']]);
		}

		$this->first_day_of_week = $model->getState('date');
		$this->currentDateData   = $model->getCurrentDate();

		parent::display();
	}

	/**
	 * Display daily events
	 */
	protected function displayDailyView()
	{
		EventbookingHelperModal::iframeModal('a.eb-colorbox-map', 'eb-map-modal');

		/* @var EventbookingModelCalendar $model */
		$model = $this->getModel();

		$this->events          = $model->getEventsByDaily();
		$this->day             = $model->getState('day');
		$this->currentDateData = $model->getCurrentDate();

		EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$this->events, ['title', 'price_text']]);

		parent::display();
	}

	/**
	 * Get calendar event attributes
	 *
	 * @param   stdClass  $event
	 * @param   Registry  $params
	 *
	 * @return array
	 */
	protected function getCalendarEventAttributes($event, $params)
	{
		$rootUri = Uri::root(true);

		if ($event->thumb)
		{
			$thumbSource = $event->thumb;
		}
		elseif ($params->get('show_event_icon', '1'))
		{
			$thumbSource = $rootUri . '/media/com_eventbooking/assets/images/calendar_event.png';
		}
		else
		{
			$thumbSource = '';
		}

		$eventClasses = [];

		if ($event->tooltip)
		{
			$eventClasses[] = 'eb_event_link eb-calendar-tooltip';
			$eventLinkTitle = HTMLHelper::tooltipText('', $event->tooltip, false, true);
		}
		else
		{
			$eventClasses[] = 'eb_event_link';
			$eventLinkTitle = $event->title;
		}

		$eventInlineStyle = '';

		if ($event->textColor || $event->backgroundColor)
		{
			$eventInlineStyle = ' style="';

			if ($event->textColor)
			{
				$eventInlineStyle .= 'color:' . $event->textColor . ';';
			}

			if ($event->backgroundColor)
			{
				$eventInlineStyle .= 'background-color:' . $event->backgroundColor . ';';
			}

			$eventInlineStyle .= '"';
		}

		if ($event->eventFull)
		{
			$eventClasses[] = 'eb-event-full';
		}

		if ($event->published == 2)
		{
			$eventClasses[] = 'eb-event-cancelled';
		}

		return [$thumbSource, $eventClasses, $eventLinkTitle, $eventInlineStyle];
	}
}
