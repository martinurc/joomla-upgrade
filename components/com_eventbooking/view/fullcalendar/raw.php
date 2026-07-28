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

class EventbookingViewFullcalendarRaw extends RADViewHtml
{
	use EventbookingViewCalendar;

	/**
	 * Override display method to render output in JSON format
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display()
	{
		$rows   = $this->model->getData();
		$config = EventbookingHelper::getConfig();
		$Itemid = $this->input->getInt('Itemid', 0);

		EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$rows, ['title', 'price_text']]);

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

		EventbookingHelperRoute::$eventsAlias = array_filter($eventsAlias);

		$this->prepareCalendarData($rows, $this->params, $Itemid);

		// Mark all days event so that time is not being displayed
		foreach ($rows as $row)
		{
			if (strpos($row->event_date, '00:00:00') !== false)
			{
				$row->allDay = true;
			}
		}

		echo json_encode($rows);

		Factory::getApplication()->close();
	}
}
