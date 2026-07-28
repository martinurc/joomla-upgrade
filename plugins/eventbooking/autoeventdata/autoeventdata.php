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
use Joomla\CMS\Plugin\CMSPlugin;

class plgEventbookingAutoEventData extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 */
	protected $db;

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   bool                    $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		$events = [$row];

		if ($row->event_type == 1)
		{
			$db    = $this->db;
			$query = $db->getQuery(true)
				->select('id')
				->from('#__eb_events')
				->where('parent_id = ' . $row->id);
			$db->setQuery($query);

			foreach ($db->loadColumn() as $id)
			{
				$rowChild = new EventbookingTableEvent($db);
				$rowChild->load($id);
				$events[] = $rowChild;
			}
		}

		$offset     = $this->app->get('offset');
		$numberDays = (int) $this->params->get('number_days_for_cancel_before_date', 0);

		foreach ($events as $event)
		{
			if ($this->params->get('only_set_if_empty') && (int) $event->cancel_before_date)
			{
				continue;
			}

			$date = Factory::getDate($event->event_date, $offset);

			if ($numberDays > 0)
			{
				$date->modify(sprintf('-%d day', $numberDays));
			}
			
			$event->cancel_before_date = $date->toSql(true);
			$event->store();
		}
	}
}
