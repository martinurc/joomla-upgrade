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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class plgEventBookingDependencies extends CMSPlugin
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
	 * @param $subject
	 * @param $config
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		Factory::getLanguage()->load('plg_eventbooking_dependencies', JPATH_ADMINISTRATOR);
	}

	/**
	 * Render setting form
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return mixed
	 */
	public function onEditEvent($row)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		ob_start();

		$this->drawSettingForm($row);

		return [
			'title' => Text::_('PLG_EB_DEPENDENCY_EVENTS'),
			'form'  => ob_get_clean(),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of events table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   bool                    $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);

		$params->set('dependency_event_ids', $data['dependency_event_ids']);
		$params->set('dependency_type', $data['dependency_type']);

		$row->params = $params->toString();

		$row->store();
	}

	/**
	 * Check accept registration
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return void|bool
	 */
	public function onEBCheckAcceptRegistration($event): bool
	{
		$params             = new Registry($event->params);
		$dependencyEventIds = explode(',', $params->get('dependency_event_ids', ''));
		$dependencyType     = $params->get('dependency_type', 'all');
		$dependencyEventIds = array_filter(ArrayHelper::toInteger($dependencyEventIds));

		if (count($dependencyEventIds) === 0)
		{
			return true;
		}

		$user = Factory::getUser();

		if (!$user->id)
		{
			if ($dependencyType == 'all')
			{
				$event->cannot_register_reason = 'require_dependency_events';
			}
			else
			{
				$event->cannot_register_reason = 'require_dependency_events_one';
			}

			return false;
		}

		// Get all the event which current user has registered for
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('event_id')
			->from('#__eb_registrants')
			->where('user_id = ' . $user->id)
			->where('(published = 1 OR (published = 0 AND payment_method LIKE "os_offline%"))');
		$db->setQuery($query);
		$registeredEventIds = $db->loadColumn();

		// User need to register for all events
		if ($dependencyType == 'all')
		{
			// There is dependency events which the current user has not registered yet
			if (count(array_diff($dependencyEventIds, $registeredEventIds)) > 0)
			{
				// Add flag so that we can show proper error message why he could not register for this event
				$event->cannot_register_reason = 'require_dependency_events';

				return false;
			}
		}
		else
		{
			// User needs to register for one of the event only
			if (!count(array_intersect($registeredEventIds, $dependencyEventIds)))
			{
				$event->cannot_register_reason = 'require_dependency_events_one';

				return false;
			}
		}

		return true;
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   object  $row
	 */
	private function drawSettingForm($row)
	{
		if ($row->id)
		{
			$params             = new Registry($row->params);
			$dependencyEventIds = $params->get('dependency_event_ids');
			$dependencyType     = $params->get('dependency_type', 'all');
		}
		else
		{
			$dependencyEventIds = '';
			$dependencyType     = 'all';
		}

		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
	}

	/**
	 * Method to check to see whether the plugin should run
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return bool
	 */
	private function canRun($row)
	{
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		return true;
	}
}
