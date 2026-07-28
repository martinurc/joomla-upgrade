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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

class EventbookingViewEventHtml extends RADViewItem
{
	use EventbookingViewEvent;

	/**
	 * Prepare view data before it's layout is being rendered
	 *
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		if ($this->getLayout() == 'import')
		{
			return;
		}

		$config     = EventbookingHelper::getConfig();
		$locations  = EventbookingHelperDatabase::getAllLocations();
		$categories = EventbookingHelperDatabase::getAllCategories($config->get('category_dropdown_ordering', 'name'), true);
		$this->buildFormData($this->item, $categories, $locations);

		if (($this->item->send_first_reminder != 0
				|| $this->item->send_second_reminder != 0
				|| $this->item->send_third_reminder != 0)
			&& !PluginHelper::isEnabled('system', 'ebreminder'))
		{
			$plugin = EventbookingHelperPlugin::getPlugin('system', 'ebreminder');

			if ($plugin)
			{
				$link = 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->extension_id;
			}
			else
			{
				$link = '#';
			}

			Factory::getApplication()->enqueueMessage(
				'You need to enable <a href="' . $link . '" target="_blank"><strong>Events Booking - Reminder</strong></a> plugin so that the system can send reminder to your registrants',
				'warning'
			);
		}
	}

	/**
	 * Override addToolbar function to allow generating custom buttons for import & batch coupon feature
	 */
	protected function addToolbar()
	{
		$layout = $this->getLayout();

		if ($layout == 'default')
		{
			parent::addToolbar();


			if ($this->item->id && $this->hasRegistrants($this->item->id))
			{
				ToolbarHelper::link(
					Route::_('index.php?option=com_eventbooking&view=registrants&filter_event_id=' . $this->item->id, false),
					Text::_('EB_REGISTRANTS')
				);
			}
		}
	}

	/**
	 * Method to check if the event has registrants
	 *
	 * @param   int  $id
	 *
	 * @return bool
	 */
	private function hasRegistrants(int $id): bool
	{
		$db    = $this->model->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_registrants')
			->where('event_id = ' . $id)
			->where('(published = 1 OR payment_method LIKE "os_offline%")');
		$db->setQuery($query);

		return $db->loadResult() > 0;
	}
}
