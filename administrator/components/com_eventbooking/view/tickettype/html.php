<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class EventbookingViewTickettypeHtml extends RADViewItem
{
	/**
	 * Ticket Types plugin params
	 * @var Registry
	 */
	protected $pluginParams;

	/**
	 * Date picker format
	 *
	 * @var string
	 */
	protected $datePickerFormat = '';

	/**
	 * Prepare data for the view
	 *
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$config = EventbookingHelper::getConfig();

		$filters = [];

		if ($config->hide_disable_registration_events)
		{
			$filters[] = 'registration_type != 3';
		}

		$rows = EventbookingHelperDatabase::getAllEvents($config->sort_events_dropdown, $config->hide_past_events_from_events_dropdown, $filters);

		if ($this->item->id
			&& ($config->hide_past_events_from_events_dropdown || $config->get('hide_unpublished_events_from_events_dropdown', 1)))
		{
			$eventExists = false;

			foreach ($rows as $row)
			{
				if ($row->id == $this->item->event_id)
				{
					$eventExists = true;
					break;
				}
			}

			if (!$eventExists)
			{
				$event  = EventbookingHelperDatabase::getEvent($this->item->event_id);
				$rows[] = $event;
			}
		}

		$this->lists['event_id'] = EventbookingHelperHtml::getEventsDropdown($rows, 'event_id', 'class="form-select"', $this->item->event_id);

		$this->datePickerFormat = $config->get('date_field_format', '%Y-%m-%d');

		$plugin = PluginHelper::getPlugin('eventbooking', 'tickettypes');

		if ($plugin)
		{
			$this->pluginParams = new Registry($plugin->params);
		}
		else
		{
			$this->pluginParams = new Registry();
		}
	}
}
