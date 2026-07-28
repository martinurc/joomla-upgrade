<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class plgEventbookingAutoMembership extends CMSPlugin
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
	 * Render setting form
	 *
	 * @param   EventbookingTableEvent  $row
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
			'title' => Text::_('EB_AUTO_MEMBERSHIP'),
			'form'  => ob_get_clean(),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   Boolean                 $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		// The plugin will only be available in the backend
		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);
		$params->set('auto_membership_plan_ids', implode(',', $data['auto_membership_plan_ids'] ?? []));
		$row->params = $params->toString();
		$row->store();
	}

	/**
	 * Generate invoice number after registrant complete payment for registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return bool
	 */
	public function onAfterPaymentSuccess($row)
	{
		// Do not handle this if registrant does not have user account or he is a group member
		if (!$row->user_id || $row->group_id > 0)
		{
			return true;
		}

		$params = new Registry($row->params);

		// This was processed before, do not process further
		if ($params->get('auto_membership_processed'))
		{
			return true;
		}

		$db    = $this->db;
		$query = $db->getQuery(true);

		$config = EventbookingHelper::getConfig();

		if ($config->multiple_booking)
		{
			$query->select('*')
				->from('#__eb_registrants')
				->where('(id = ' . $row->id . ' OR cart_id = ' . $row->id . ')');
			$db->setQuery($query);
			$rowRegistrants = $db->loadObjectList();
		}
		else
		{
			$rowRegistrants = [$row];
		}

		$autoSubscribePlanIds = [];

		foreach ($rowRegistrants as $rowRegistrant)
		{
			$event   = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);
			$params  = new Registry($event->params);
			$planIds = $params->get('auto_membership_plan_ids', '');

			// This event is not configured to subscribe registrants to Membership Plans
			if (empty($planIds))
			{
				continue;
			}

			$planIds = array_filter(ArrayHelper::toInteger(explode(',', $planIds)));

			if (count($planIds))
			{
				$autoSubscribePlanIds = array_merge_recursive($autoSubscribePlanIds, $planIds);
			}
		}

		// No plan to subscribe
		if (count($autoSubscribePlanIds) === 0)
		{
			return true;
		}

		// Get data from registration records, map it to the fields in Membership Pro base on fields mapping
		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0);
		}

		$registrantData = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);

		$subscriptionData = [];

		foreach ($rowFields as $rowField)
		{
			if ($rowField->field_mapping && array_key_exists($rowField->name, $registrantData))
			{
				$subscriptionData[$rowField->field_mapping] = $registrantData[$rowField->name];
			}
		}

		// No fields mapped, do not process it further
		if (!count($subscriptionData))
		{
			return true;
		}

		if (!array_key_exists('email', $subscriptionData))
		{
			$subscriptionData['email'] = $row->email;
		}
		
		// Require library + register autoloader
		require_once JPATH_ADMINISTRATOR . '/components/com_osmembership/loader.php';

		foreach ($autoSubscribePlanIds as $planId)
		{
			$data            = $subscriptionData;
			$data['plan_id'] = $planId;

			$model = new OSMembershipModelApi();

			try
			{
				$model->store($data);
			}
			catch (Exception $e)
			{
				// Ignore error for now
			}
		}

		$params->set('auto_membership_processed', 1);
		$row->params = $params->toString();
		$row->store();
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   object  $row
	 */
	private function drawSettingForm($row)
	{
		$params = new Registry($row->params);

		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id, title')
			->from('#__osmembership_plans')
			->where('published = 1')
			->order('ordering');
		$db->setQuery($query);

		$lists['auto_membership_plan_ids'] = HTMLHelper::_(
			'select.genericlist',
			$db->loadObjectList(),
			'auto_membership_plan_ids[]',
			'multiple class="form-select"',
			'id',
			'title',
			explode(',', $params->get('auto_membership_plan_ids', ''))
		);

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

		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_osmembership/osmembership.php'))
		{
			return false;
		}

		return true;
	}
}
