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

class plgSystemEBRegistrants extends CMSPlugin
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
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array    $config   An optional associative array of configuration settings.
	 */
	public function __construct(&$subject, $config = [])
	{
		if (!file_exists(JPATH_ROOT . '/components/com_eventbooking/eventbooking.php'))
		{
			return;
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Send reminder to registrants
	 *
	 * @return void
	 * @throws Exception
	 */
	public function onAfterRespond()
	{
		if (!$this->app)
		{
			return;
		}

		if (!$this->canRun())
		{
			return;
		}

		// Require library + register autoloader
		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

		$cacheTime = (int) $this->params->get('cache_time', 20) * 60; // 60 minutes

		// We only need to check and store last runtime if cron job is not configured
		if (!$this->params->get('trigger_code')
			&& !EventbookingHelperPlugin::checkAndStoreLastRuntime($this->params, $cacheTime, $this->_name))
		{
			return;
		}

		$db    = $this->db;
		$now   = $db->quote(Factory::getDate('now', Factory::getApplication()->get('offset'))->toSql(true));
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_events')
			->where('published = 1')
			->where('registrants_emailed = 0')
			->where('event_date >= ' . $now)
			->order('event_date');

		$timeToSend     = (int) $this->params->get('time_to_send', 1) ?: 1;
		$timeToSendUnit = $this->params->get('time_to_send_unit', 'd');

		if ($timeToSendUnit == 'd')
		{
			$query->where("DATEDIFF(event_date, $now) <= " . $timeToSend);
		}
		else
		{
			$query->where("TIMESTAMPDIFF(HOUR, $now, event_date) <= " . $timeToSend);
		}

		$db->setQuery($query, 0, 1);
		$row = $db->loadObject();

		if ($row)
		{
			JLoader::register('EventbookingModelEvent', JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/event.php');

			/* @var  EventbookingModelEvent $model */
			$model = RADModel::getTempInstance('Event', 'EventbookingModel');
			$model->sendRegistrantsList($row->id);
			$query->clear()
				->update('#__eb_events')
				->set('registrants_emailed = 1')
				->where('id = ' . $row->id);
			$db->setQuery($query)
				->execute();
		}
	}

	/**
	 * Method to check whether this plugin should be run
	 *
	 * @return bool
	 */
	private function canRun()
	{
		if (!$this->app)
		{
			return false;
		}

		// If trigger code is set, we will only process sending reminder from cron job
		if (trim($this->params->get('trigger_code', ''))
			&& trim($this->params->get('trigger_code', '')) != $this->app->input->getString('trigger_code'))
		{
			return false;
		}

		// If time ranges is set and current time is not within these specified ranges, we won't process sending reminder
		if ($this->params->get('time_ranges'))
		{
			$withinTimeRage = false;
			$date           = Factory::getDate('Now', Factory::getApplication()->get('offset'));
			$currentHour    = $date->format('G', true);
			$timeRanges     = explode(';', $this->params->get('time_ranges'));// Time ranges format 6,10;14,20

			foreach ($timeRanges as $timeRange)
			{
				if (strpos($timeRange, ',') == false)
				{
					continue;
				}

				[$fromHour, $toHour] = explode(',', $timeRange);

				if ($fromHour <= $currentHour && $toHour >= $currentHour)
				{
					$withinTimeRage = true;
					break;
				}
			}

			if (!$withinTimeRage)
			{
				return false;
			}
		}

		return true;
	}
}
