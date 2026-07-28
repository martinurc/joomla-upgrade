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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

class EventbookingViewTickettypesHtml extends RADViewList
{
	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Prepare data for the view
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$config = EventbookingHelper::getConfig();

		// Event filter
		$filters = [];

		if ($config->hide_disable_registration_events)
		{
			$filters[] = 'registration_type != 3';
		}

		$rows = EventbookingHelperDatabase::getAllEvents($config->sort_events_dropdown, $config->hide_past_events_from_events_dropdown, $filters);

		$this->lists['filter_event_id'] = EventbookingHelperHtml::getEventsDropdown(
			$rows,
			'filter_event_id',
			'onchange="submit();" class="form-select" ',
			$this->state->filter_event_id
		);

		$this->config = EventbookingHelper::getConfig();

		if (count($this->items) > 0 && !PluginHelper::isEnabled('eventbooking', 'tickettypes'))
		{
			$plugin = EventbookingHelperPlugin::getPlugin('eventbooking', 'tickettypes');

			if ($plugin)
			{
				$link = 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->extension_id;
			}
			else
			{
				$link = '#';
			}

			Factory::getApplication()->enqueueMessage(Text::sprintf('EB_ENABLE_TICKET_TYPES_PLUGIN', $link), 'warning');
		}
	}
}
