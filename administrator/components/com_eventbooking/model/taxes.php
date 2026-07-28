<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingModelTaxes extends RADModelList
{
	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  configuration data for the model
	 */
	public function __construct($config = [])
	{
		$config['search_fields'] = ['tbl.country', 'tbl.state'];

		parent::__construct($config);

		$this->state->insert('filter_country', 'string', '')
			->insert('filter_category_id', 'int', 0)
			->insert('filter_event_id', 'int', 0)
			->insert('vies', 'int', -1)
			->setDefault('filter_order', 'tbl.country');
	}

	/**
	 * Builds SELECT columns list for the query
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return $this
	 */
	protected function buildQueryColumns(JDatabaseQuery $query)
	{
		$query->select(['tbl.*'])
			->select('b.title, b.event_date')
			->select('c.name AS category_name');

		return $this;
	}

	/**
	 * Builds JOINS clauses for the query
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return $this
	 */
	protected function buildQueryJoins(JDatabaseQuery $query)
	{
		$query->leftJoin('#__eb_events AS b ON tbl.event_id = b.id')
			->leftJoin('#__eb_categories AS c ON tbl.category_id = c.id');

		return $this;
	}

	/**
	 * Builds a WHERE clause for the query
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return $this
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		parent::buildQueryWhere($query);

		$db    = $this->getDbo();
		$state = $this->getState();

		if ($state->filter_category_id > 0)
		{
			$query->where('tbl.category_id = ' . $state->filter_category_id);
		}

		if ($state->filter_event_id > 0)
		{
			$query->where('tbl.event_id = ' . $state->filter_event_id);
		}

		if ($state->filter_country)
		{
			$query->where('tbl.country = ' . $db->quote($state->filter_country));
		}

		if ($state->vies != -1)
		{
			$query->where('tbl.vies = ' . $state->vies);
		}

		return $this;
	}
}
