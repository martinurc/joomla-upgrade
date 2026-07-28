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
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;

class plgEventbookingGroupMemberAccount extends CMSPlugin
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
	 * Create User Account For Group Members
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onAfterStoreRegistrant($row)
	{
		if ($row->is_group_billing && strpos($row->payment_method, 'os_offline') !== false)
		{
			$this->createUserAccountForGroupMembers($row);
		}
	}

	/**
	 * Add registrants to selected Joomla groups when payment for registration completed
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onAfterPaymentSuccess($row)
	{
		if ($row->is_group_billing && strpos($row->payment_method, 'os_offline') === false)
		{
			$this->createUserAccountForGroupMembers($row);
		}

		$this->assignGroupMembersToUserGroups($row);
	}

	/**
	 * Method to create group user account for group members in a group registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	private function createUserAccountForGroupMembers($row): void
	{
		if (!$this->needToCreateUserAccountForGroupMembers($row))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('group_id = ' . $row->id);

		$db->setQuery($query);
		$rowMembers = $db->loadObjectList();

		foreach ($rowMembers as $rowMember)
		{
			if (!$rowMember->email || !MailHelper::isEmailAddress($rowMember->email))
			{
				continue;
			}

			// Create user account here
			$data     = EventbookingHelperRegistration::getRegistrantData($rowMember);
			$username = $data['username'] ?? $rowMember->email;

			// No username data available, do not create account
			if (!$username)
			{
				continue;
			}

			// Check to see if there is an existing account with this username or email
			$query->clear()
				->select('id')
				->from('#__users')
				->where('(username = ' . $db->quote($username) . ' OR email = ' . $db->quote($rowMember->email) . ')');
			$db->setQuery($query);

			if ($userId = $db->loadResult())
			{
				$query->clear()
					->update('#__eb_registrants')
					->set('user_id = ' . $userId)
					->where('id = ' . $rowMember->id);
				$db->setQuery($query)
					->execute();

				continue;
			}

			$accountData = [];
			$firstName   = $data['first_name'] ?? '';
			$lastName    = $data['last_name'] ?? '';

			if ($firstName || $lastName)
			{
				$accountData['first_name'] = $firstName;
				$accountData['last_name']  = $lastName;
			}
			else
			{
				$accountData['first_name'] = $username;
				$accountData['last_name']  = '';
			}

			$accountData['email']    = $rowMember->email;
			$accountData['username'] = $username;

			if ($this->params->get('password') === 'manually' && !empty($data['password']))
			{
				$accountData['password1'] = $data['password'];
			}
			elseif ($this->params->get('password') === 'random')
			{
				$accountData['password1'] = UserHelper::genRandomPassword();
			}
			elseif ($this->params->get('password') === 'username')
			{
				$accountData['password1'] = $username;
			}
			else
			{
				$accountData['password1'] = $rowMember->email;
			}

			$userId = EventbookingHelperRegistration::saveRegistration($accountData);

			if ($userId)
			{
				$query->clear()
					->update('#__eb_registrants')
					->set('user_id = ' . $userId)
					->where('id = ' . $rowMember->id);
				$db->setQuery($query)
					->execute();
			}
		}
	}

	/**
	 * Method to check if we need to create group member account
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return bool
	 */
	private function needToCreateUserAccountForGroupMembers($row): bool
	{
		if (!$row->is_group_billing)
		{
			return false;
		}

		$config = EventbookingHelper::getConfig();

		// We do not handle shopping cart for the time being
		if ($config->multiple_booking)
		{
			return false;
		}

		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		$params = new Registry($event->params);

		// Do not need to create account if user registration is not enabled for the event
		if (!$params->get('user_registration', $config->user_registration))
		{
			return false;
		}

		if ($event->has_multiple_ticket_types)
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to assign group members of a group registration to the configured Joomla user groups
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	private function assignGroupMembersToUserGroups($row)
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('user_id > 0')
			->where('group_id = ' . $row->id);
		$db->setQuery($query);
		$rowMembers = $db->loadObjectList();

		if (count($rowMembers))
		{
			$event = EventbookingHelperDatabase::getEvent($row->event_id);

			foreach ($rowMembers as $rowMember)
			{
				$this->assignToUserGroups($rowMember, $event);
			}
		}
	}

	/**
	 * Add registrants to selected Joomla groups which is configured in registered event
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   EventbookingTableEvent       $event
	 */
	private function assignToUserGroups($row, $event)
	{
		$user          = Factory::getUser($row->user_id);
		$currentGroups = $user->groups;

		$params   = new Registry($event->params);
		$groupIds = $params->get('joomla_group_ids');

		if (!$groupIds)
		{
			$plugin = PluginHelper::getPlugin('eventbooking', 'joomlagroups');

			if ($plugin)
			{
				$pluginParams = new Registry($plugin->params);
				$groupIds     = implode(',', $pluginParams->get('default_user_groups', []));
			}
		}

		if ($groupIds)
		{
			$groups        = explode(',', $groupIds);
			$currentGroups = array_unique(array_merge($currentGroups, $groups));
			$user->groups  = $currentGroups;
			$user->save(true);
		}
	}
}
