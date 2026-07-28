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

class EventbookingModelUsercoupons extends RADModelList
{
	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  Configuration data for the model
	 */
	public function __construct($config = [])
	{
		$config['search_fields'] = ['tbl.code', 'tbl.note'];
		$config['table']         = '#__eb_coupons';

		parent::__construct($config);

		$this->state->setDefault('filter_order', 'tbl.id')
			->setDefault('filter_order_Dir', 'DESC');

		// Remember filter states
		$this->rememberStates = true;
	}

	/**
	 * Build where clause
	 *
	 * @param   JDatabaseQuery  $query
	 *
	 * @return $this
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$query->where('tbl.user_id = ' . Factory::getUser()->id);

		return parent::buildQueryWhere($query);
	}
}
