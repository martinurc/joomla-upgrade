<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class plgEventbookingAutoGroupMembers extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var \Joomla\CMS\Application\CMSApplication
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
	 * @return array|void
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
			'title' => Text::_('EB_AUTO_GROUP_MEMBERS_SETTINGS'),
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
		$params->set('enable_auto_group_members', trim($data['enable_auto_group_members']));
		$row->params = $params->toString();
		$row->store();
	}

	/**
	 * Generate group members in case collect group members information is disabled
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	public function onAfterStoreRegistrant($row): void
	{
		if (!$this->needToGenerateGroupMembers($row))
		{
			return;
		}

		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		if ($event->has_multiple_ticket_types)
		{
			$this->generateGroupMembersForTicketTypes($row);

			return;
		}

		$this->generateGroupMembersFromBillingRecordData($row, $row->number_registrants);
	}

	/**
	 * Generate group members for ticket types order
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	private function generateGroupMembersForTicketTypes($row): void
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrant_tickets')
			->where('registrant_id = ' . $row->id)
			->order('id');
		$db->setQuery($query);

		foreach ($db->loadObjectList() as $rowTicketTypeOrderItem)
		{
			$this->generateGroupMembersFromBillingRecordData($row, $rowTicketTypeOrderItem->quantity, $rowTicketTypeOrderItem->ticket_type_id);
		}

		$row->is_group_billing = 1;
		$row->store();
	}

	/**
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $numberRegistrants
	 * @param   int                          $ticketTypeId
	 *
	 * @return void
	 */
	private function generateGroupMembersFromBillingRecordData($row, $numberRegistrants, $ticketTypeId = 0)
	{
		$db     = $this->db;
		$fields = [
			'event_id',
			'user_id',
			'first_name',
			'last_name',
			'organization',
			'address',
			'address2',
			'city',
			'state',
			'country',
			'zip',
			'phone',
			'fax',
			'email',
			'comment',
			'published',
			'payment_method',
			'transaction_id',
			'user_ip',
			'payment_status',
			'register_date',
			'created_by',
		];

		for ($i = 0; $i < $numberRegistrants; $i++)
		{
			$rowMember = new EventbookingTableRegistrant($db);

			$rowMember->group_id               = $row->id;
			$rowMember->number_registrants     = 1;
			$rowMember->total_amount           = 0;
			$rowMember->discount_amount        = 0;
			$rowMember->late_fee               = 0;
			$rowMember->tax_amount             = 0;
			$rowMember->payment_processing_fee = 0;
			$rowMember->amount                 = 0;
			$rowMember->ticket_qrcode          = EventbookingHelperRegistration::getTicketCode();
			$rowMember->registration_code      = EventbookingHelperRegistration::getRegistrationCode();

			foreach ($fields as $field)
			{
				$rowMember->{$field} = $row->{$field};
			}

			$rowMember->store();

			if ($ticketTypeId)
			{
				$this->storeTicketTypeDataForRegistration($rowMember->id, $ticketTypeId, 1);
			}
		}
	}

	/**
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return bool
	 */
	private function needToGenerateGroupMembers($row): bool
	{
		// Only create if record is created from frontend of the site
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		$config = EventbookingHelper::getConfig();

		if ($config->multiple_booking)
		{
			return false;
		}

		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		$params = new Registry($event->params);

		if (!$params->get('enable_auto_group_members'))
		{
			return false;
		}

		if ($this->isMemberInformationCollectedForEvent($event))
		{
			return false;
		}

		// Do not generate if this is not an event with ticket types and not a group billing
		if (!$event->has_multiple_ticket_types && !$row->is_group_billing)
		{
			return false;
		}

		// Is group members generated before ?
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_registrants')
			->where('group_id = ' . $row->id);
		$db->setQuery($query);

		if ($db->loadResult() > 0)
		{
			return false;
		}

		return true;
	}

	/**
	 * Store ticket type data for a registration
	 *
	 * @param   int  $id
	 * @param   int  $ticketTypeId
	 * @param   int  $quantity
	 *
	 * @return void
	 */
	private function storeTicketTypeDataForRegistration(int $id, int $ticketTypeId, int $quantity = 1): void
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->insert('#__eb_registrant_tickets')
			->columns('registrant_id, ticket_type_id, quantity')
			->values("$id, $ticketTypeId, $quantity");
		$db->setQuery($query)
			->execute();
	}

	/**
	 * @param $event
	 *
	 * @return bool
	 */
	private function isMemberInformationCollectedForEvent($event): bool
	{
		if ($event->has_multiple_ticket_types)
		{
			$params = new Registry($event->params);

			if ($params->get('ticket_types_collect_members_information'))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		$config = EventbookingHelper::getConfig();

		if ($event->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $event->collect_member_information;
		}

		return (bool) $collectMemberInformation;
	}

	/**
	 * Display form allows users to change settings on add/edit event screen
	 *
	 * @param   EventbookingTableEvent  $row
	 */
	private function drawSettingForm($row)
	{
		$params = new Registry($row->params);

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

		$config = EventbookingHelper::getConfig();

		if ($config->multiple_booking)
		{
			return false;
		}

		return true;
	}
}
