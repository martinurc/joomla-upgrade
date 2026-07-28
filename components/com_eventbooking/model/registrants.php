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

class EventbookingModelRegistrants extends EventbookingModelCommonRegistrants
{
	public function __construct($config = [])
	{
		parent::__construct($config);

		if ($this->params->get('display_num') > 0)
		{
			$this->state->setDefault('limit', (int) $this->params->get('display_num'));
		}
	}

	/**
	 * Build where clase of the query
	 *
	 * @see RADModelList::buildQueryWhere()
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$user   = Factory::getUser();
		$config = EventbookingHelper::getConfig();

		if (!$config->show_pending_registrants)
		{
			$query->where('(tbl.published >= 1 OR tbl.payment_method LIKE "os_offline%")');
		}

		// Only hide billing records if the group members records are configured to be shown
		if (!$config->get('include_group_billing_in_registrants', 1)
			&& $config->include_group_members_in_registrants)
		{
			$query->where(' tbl.is_group_billing = 0 ');
		}

		if (!$config->include_group_members_in_registrants)
		{
			$query->where(' tbl.group_id = 0 ');
		}

		if ($config->only_show_registrants_of_event_owner && !$user->authorise('core.admin', 'com_eventbooking'))
		{
			$query->where('tbl.event_id IN (SELECT id FROM #__eb_events WHERE created_by =' . $user->id . ')');
		}

		if ($this->params->get('show_registrants_of_past_events', 1) === '0')
		{
			$currentDate = $this->db->quote(EventbookingHelper::getServerTimeFromGMTTime());
			$query->where(
				'tbl.event_id IN (SELECT id FROM #__eb_events WHERE event_date >= ' . $currentDate . ' OR event_end_date >= ' . $currentDate . ')'
			);
		}

		return parent::buildQueryWhere($query);
	}
}
