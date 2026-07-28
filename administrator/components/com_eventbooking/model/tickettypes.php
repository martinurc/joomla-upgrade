<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingModelTickettypes extends RADModelList
{
	/**
	 * Constructor
	 *
	 * @param   array  $config
	 */
	public function __construct($config = [])
	{
		$config['table'] = '#__eb_ticket_types';

		parent::__construct($config);

		$this->state->insert('filter_event_id', 'int', 0);
	}

	/**
	 * Filter speakers by event
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return RADModelList
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		if ($this->state->filter_event_id)
		{
			$query->where('tbl.event_id = ' . $this->state->filter_event_id);
		}

		return parent::buildQueryWhere($query);
	}
}
