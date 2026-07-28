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

class plgEventbookingCheckedIn extends CMSPlugin
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
	 * Ask Joomla to load plugin language automatically
	 *
	 * @var bool
	 */
	protected $autoloadLanguage = true;

	/**
	 * Render settings form
	 *
	 * @param $row
	 *
	 * @return array
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
			'title' => Text::_('PLG_EVENTBOOKING_CHECKED_IN_SETTINGS'),
			'form'  => ob_get_clean(),
		];
	}

	/**
	 * Store setting into database
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   Boolean                 $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);
		$params->set('checked_in_joomla_group_ids', implode(',', $data['checked_in_joomla_group_ids'] ?? []));
		$row->params = $params->toString();

		$row->store();
	}

	/**
	 * This method is run after registration record is stored into database
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onEBCheckinSuccess($row)
	{
		if (!$row->user_id)
		{
			return;
		}

		$user          = Factory::getUser($row->user_id);
		$currentGroups = $user->groups;

		$event = new EventbookingTableEvent($this->db);
		$event->load($row->event_id);
		$params   = new Registry($event->params);
		$groupIds = $params->get('checked_in_joomla_group_ids');

		if (!$groupIds)
		{
			$groupIds = implode(',', $this->params->get('default_user_groups', []));
		}

		if ($groupIds)
		{
			$groups        = explode(',', $groupIds);
			$currentGroups = array_unique(array_merge($currentGroups, $groups));
		}

		$user->groups = $currentGroups;
		$user->save(true);
	}

	/**
	 * Display form allows users to change setting for this subscription plan
	 *
	 * @param   object  $row
	 */
	private function drawSettingForm($row)
	{
		$params   = new Registry($row->params);
		$groupIds = explode(',', $params->get('checked_in_joomla_group_ids', ''));

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
