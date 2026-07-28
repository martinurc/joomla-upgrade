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

class EventbookingViewSpeakersHtml extends RADViewList
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

		if (count($this->items) > 0 && !PluginHelper::isEnabled('eventbooking', 'speakers'))
		{
			$plugin = EventbookingHelperPlugin::getPlugin('eventbooking', 'speakers');

			if ($plugin)
			{
				$link = 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->extension_id;
			}
			else
			{
				$link = '#';
			}

			Factory::getApplication()->enqueueMessage(Text::sprintf('EB_ENABLE_SPEAKERS_PLUGIN', $link), 'warning');
		}
	}
}
