<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingModelCategory extends EventbookingModelList
{
	/**
	 * Builds a WHERE clause for the query
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return $this
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$config = EventbookingHelper::getConfig();

		$displayEventsType = $this->params->get('display_events_type', 0);

		if ($displayEventsType == 0)
		{
			if ($config->hide_past_events)
			{
				$displayEventsType = 2;
			}
			else
			{
				$displayEventsType = 1;
			}
		}

		// Display upcoming events
		if ($displayEventsType == 2)
		{
			$this->applyHidePastEventsFilter($query);
		}
		elseif ($displayEventsType == 3)
		{
			// Display past events
			$this->applyHideFutureEventsFilter($query);
		}

		return parent::buildQueryWhere($query);
	}

	/**
	 * Builds a generic ORDER BY clause based on the model's state
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return $this
	 */
	protected function buildQueryOrder(JDatabaseQuery $query)
	{
		if ($filterOrder = $this->params->get('menu_filter_order'))
		{
			$this->setState('filter_order', $filterOrder);
		}

		if ($filterOrderDir = $this->params->get('menu_filter_order_dir'))
		{
			$this->setState('filter_order_Dir', $filterOrderDir);
		}

		return parent::buildQueryOrder($query);
	}
}
