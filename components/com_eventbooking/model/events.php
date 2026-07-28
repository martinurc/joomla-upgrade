<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

JLoader::register('EventbookingModelCommonEvents', JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/common/events.php');

use Joomla\CMS\Factory;

class EventbookingModelEvents extends EventbookingModelCommonEvents
{
	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  configuration data for the model
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->state->setDefault('filter_order', $this->params->get('menu_filter_order', 'tbl.event_date'))
			->setDefault('filter_order_Dir', $this->params->get('menu_filter_order_dir', 'DESC'));

		// Remember filter states
		$this->rememberStates = true;
	}

	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$user = Factory::getUser();

		if (!$user->authorise('core.admin', 'com_eventbooking'))
		{
			$query->where('tbl.created_by = ' . (int) $user->id);
		}

		return parent::buildQueryWhere($query);
	}
}
