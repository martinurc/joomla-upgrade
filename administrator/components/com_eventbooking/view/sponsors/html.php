<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

class EventbookingViewSponsorsHtml extends RADViewList
{
	protected function prepareView()
	{
		parent::prepareView();

		$config = EventbookingHelper::getConfig();

		$rows                           = EventbookingHelperDatabase::getAllEvents($config->sort_events_dropdown);
		$this->lists['filter_event_id'] = EventbookingHelperHtml::getEventsDropdown(
			$rows,
			'filter_event_id',
			'class="input-xlarge form-select" onchange="submit();"',
			$this->state->filter_event_id
		);

		if (count($this->items) > 0 && !PluginHelper::isEnabled('eventbooking', 'sponsors'))
		{
			$plugin = EventbookingHelperPlugin::getPlugin('eventbooking', 'sponsors');

			if ($plugin)
			{
				$link = 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->extension_id;
			}
			else
			{
				$link = '#';
			}

			Factory::getApplication()->enqueueMessage(Text::sprintf('EB_ENABLE_SPONSORS_PLUGIN', $link), 'warning');
		}
	}
}
