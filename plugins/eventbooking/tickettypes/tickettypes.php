<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

class plgEventBookingTicketTypes extends CMSPlugin
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
	 * Custom fields, added by customization to #__eb_ticket_types_tables
	 *
	 * @var array
	 */
	protected $customFields = [];

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array    $config
	 */
	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		// Detect none core fields
		$fields = array_keys($this->db->getTableColumns('#__eb_ticket_types'));

		$coreFields = [
			'id',
			'event_id',
			'title',
			'description',
			'discount_rules',
			'price',
			'capacity',
			'weight',
			'min_tickets_per_booking',
			'max_tickets_per_booking',
			'parent_ticket_type_id',
			'publish_up',
			'publish_down',
			'access',
			'ordering',
		];

		$this->customFields = array_diff($fields, $coreFields);
	}

	/**
	 * Render setting form
	 *
	 * @param   \Joomla\CMS\Table\Table  $row
	 *
	 * @return array
	 */
	public function onEditEvent($row)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		return [
			'title' => Text::_('EB_TICKET_TYPES'),
			'form'  => $this->drawSettingForm($row),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   array                   $data
	 * @param   bool                    $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		// The plugin will only be available in the backend
		if (!$this->canRun($row))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true);

		$config     = EventbookingHelper::getConfig();
		$dateFormat = str_replace('%', '', $config->get('date_field_format', '%Y-%m-%d'));

		// Convert date data to Y-m-d H:i:s format
		$dateFields = [
			'publish_up',
			'publish_down',
		];

		$hasMultipleTicketTypes = 0;
		$ticketTypes            = isset($data['ticket_types']) && is_array($data['ticket_types']) ? $data['ticket_types'] : [];

		$ticketTypeIds = [];
		$ordering      = 1;

		foreach ($ticketTypes as $ticketType)
		{
			if (empty($ticketType['title']))
			{
				continue;
			}

			if (!array_key_exists('weight', $ticketType))
			{
				$ticketType['weight'] = 1;
			}

			// Set default value for discount rules to prevent warnings
			$ticketType['discount_rules'] = $ticketType['discount_rules'] ?? '';

			// Convert date fields to correct format
			foreach ($dateFields as $field)
			{
				if ($ticketType[$field] && strpos($ticketType[$field], '0000') === false)
				{
					$datetime = DateTime::createFromFormat($dateFormat . ' H:i', $ticketType[$field]);

					if ($datetime !== false)
					{
						$ticketType[$field] = $datetime->format('Y-m-d H:i:s');
					}
				}
			}

			$rowTicketType = new EventbookingTableTickettype($this->db);
			$rowTicketType->bind($ticketType);

			// Prevent ticket type data being moved to new event on saveAsCopy
			if ($isNew)
			{
				$rowTicketType->id = 0;
			}

			$rowTicketType->event_id = $row->id;
			$rowTicketType->ordering = $ordering++;
			$rowTicketType->store();
			$ticketTypeIds[]        = $rowTicketType->id;
			$hasMultipleTicketTypes = true;
		}

		if (!$isNew)
		{
			$query->delete('#__eb_ticket_types')
				->where('event_id = ' . $row->id);

			if (count($ticketTypeIds))
			{
				$query->where('id NOT IN (' . implode(',', $ticketTypeIds) . ')');
			}

			$db->setQuery($query)
				->execute();
		}

		if (isset($data['update_ticket_types_to_children_events']) && !$data['update_ticket_types_to_children_events'])
		{
			$updateChildrenEvents = 0;
		}
		else
		{
			$updateChildrenEvents = 1;
		}

		$row->has_multiple_ticket_types = $hasMultipleTicketTypes;
		$params                         = new Registry($row->params);
		$params->set('ticket_types_collect_members_information', $data['ticket_types_collect_members_information']);
		$params->set('update_ticket_types_to_children_events', $updateChildrenEvents);
		$params->set('only_allow_register_one_ticket_type', $data['only_allow_register_one_ticket_type']);
		$row->params = $params->toString();

		$row->store();

		if ($row->event_type == 1 && $updateChildrenEvents)
		{
			$this->storeTicketTypeForChildrenEvents($row, $ticketTypeIds, $isNew, $hasMultipleTicketTypes);
		}
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
		if (strpos($row->payment_method, 'os_offline') === false)
		{
			$this->processTicketTypes($row);
		}
	}

	/**
	 * Generate invoice number after registrant complete registration in case he uses offline payment
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onAfterStoreRegistrant($row)
	{
		if (strpos($row->payment_method, 'os_offline') !== false)
		{
			$this->processTicketTypes($row);
		}
	}

	/**
	 * Check accept registration
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	public function onEBCheckAcceptRegistration($event): bool
	{
		if ($event->has_multiple_ticket_types)
		{
			$ticketTypes = EventbookingHelperData::getTicketTypes($event->id, true);

			// Validate min_tickets_per_booking
			foreach ($ticketTypes as $ticketType)
			{
				if ($ticketType->capacity && $ticketType->min_tickets_per_booking)
				{
					$available = $ticketType->capacity - (int) $ticketType->registered;

					if ($available < $ticketType->min_tickets_per_booking)
					{
						$event->cannot_register_reason = 'required_ticket_type_not_available';

						return false;
					}
				}
			}

			foreach ($ticketTypes as $ticketType)
			{
				if (!$ticketType->capacity || ($ticketType->capacity > $ticketType->registered))
				{
					return true;
				}
			}

			$event->cannot_register_reason = 'all_ticket_types_are_full';

			return false;
		}

		return true;
	}

	/**
	 * Store ticket types data for children events
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   array                   $ticketTypeIds
	 * @param   bool                    $isNew
	 * @param   bool                    $hasMultipleTicketTypes
	 */
	private function storeTicketTypeForChildrenEvents($row, $ticketTypeIds, $isNew, $hasMultipleTicketTypes)
	{
		$db    = $this->db;
		$query = $db->getQuery(true);

		// Get list of children events
		$query->select('id')
			->from('#__eb_events')
			->where('parent_id = ' . $row->id);
		$db->setQuery($query);
		$childEventIds = $db->loadColumn();

		if (!count($childEventIds))
		{
			$row->event_type = 0;
			$row->store();

			return;
		}

		if (count($this->customFields))
		{
			$customFields = ',' . implode(',', $db->quoteName($this->customFields));
		}
		else
		{
			$customFields = '';
		}

		if ($isNew)
		{
			foreach ($childEventIds as $childEventId)
			{
				$sql = 'INSERT INTO #__eb_ticket_types (event_id, title, discount_rules, description, price, capacity, weight, `access`, min_tickets_per_booking, max_tickets_per_booking, publish_up, publish_down, ordering, parent_ticket_type_id' . $customFields . ')'
					. " SELECT $childEventId, title, discount_rules, description, price, capacity, weight, `access`, min_tickets_per_booking, max_tickets_per_booking, publish_up, publish_down, ordering, id" . $customFields . " FROM #__eb_ticket_types WHERE event_id = $row->id";
				$db->setQuery($sql);
				$db->execute();
			}
		}
		else
		{
			foreach ($childEventIds as $childEventId)
			{
				foreach ($ticketTypeIds as $ticketTypeId)
				{
					$query->clear()
						->select('*')
						->from('#__eb_ticket_types')
						->where('id = ' . $ticketTypeId);
					$db->setQuery($query);
					$rowParentTicketType = $db->loadObject();

					$query->clear()
						->select('id')
						->from('#__eb_ticket_types')
						->where('event_id = ' . $childEventId)
						->where('parent_ticket_type_id = ' . $rowParentTicketType->id);
					$db->setQuery($query);
					$childEventTicketTypeId = (int) $db->loadResult();

					$weight = (int) $rowParentTicketType->weight;

					if ($childEventTicketTypeId)
					{
						// Update data of existing ticket type
						$query->clear()
							->update('#__eb_ticket_types')
							->set('title = ' . $db->quote($rowParentTicketType->title))
							->set('discount_rules = ' . $db->quote($rowParentTicketType->discount_rules))
							->set('description = ' . $db->quote($rowParentTicketType->description))
							->set('price = ' . $db->quote($rowParentTicketType->price))
							->set('capacity = ' . $db->quote($rowParentTicketType->capacity))
							->set('weight = ' . $weight)
							->set('access = ' . $db->quote($rowParentTicketType->access))
							->set('min_tickets_per_booking = ' . $db->quote($rowParentTicketType->min_tickets_per_booking))
							->set('max_tickets_per_booking = ' . $db->quote($rowParentTicketType->max_tickets_per_booking))
							->set('publish_up = ' . $db->quote($rowParentTicketType->publish_up))
							->set('publish_down = ' . $db->quote($rowParentTicketType->publish_down))
							->set('ordering = ' . $db->quote($rowParentTicketType->ordering))
							->where('id = ' . $childEventTicketTypeId);

						foreach ($this->customFields as $customField)
						{
							$query->set($db->quoteName($customField) . ' = ' . $db->quote($rowParentTicketType->{$customField}));
						}

						$db->setQuery($query)
							->execute();
					}
					else
					{
						$ticketFields = [
							'event_id',
							'title',
							'discount_rules',
							'description',
							'price',
							'capacity',
							'weight',
							'access',
							'min_tickets_per_booking',
							'max_tickets_per_booking',
							'publish_up',
							'publish_down',
							'ordering',
							'parent_ticket_type_id',
						];

						$ticketData = [
							$childEventId,
							$rowParentTicketType->title,
							$rowParentTicketType->discount_rules,
							$rowParentTicketType->description,
							$rowParentTicketType->price,
							$rowParentTicketType->capacity,
							$weight,
							$rowParentTicketType->access,
							$rowParentTicketType->min_tickets_per_booking,
							$rowParentTicketType->max_tickets_per_booking,
							$rowParentTicketType->publish_up,
							$rowParentTicketType->publish_down,
							$rowParentTicketType->ordering,
							$rowParentTicketType->id,
						];

						foreach ($this->customFields as $customField)
						{
							$ticketFields[] = $customField;
							$ticketData[]   = $rowParentTicketType->{$customField};
						}

						// Insert new Ticket type data
						$query->clear()
							->insert('#__eb_ticket_types')
							->columns(implode(',', $db->quoteName($ticketFields)))
							->values(implode(',', $db->quote($ticketData)));
						$db->setQuery($query)
							->execute();
					}
				}
			}
		}

		// Remove the deleted ticket types
		$query->clear()
			->delete('#__eb_ticket_types')
			->where('event_id IN (' . implode(',', $childEventIds) . ')');

		if (count($ticketTypeIds))
		{
			$query->where('parent_ticket_type_id NOT IN (' . implode(',', $ticketTypeIds) . ')');
		}

		$db->setQuery($query)
			->execute();

		$query->clear()
			->update('#__eb_events')
			->set('has_multiple_ticket_types = ' . $hasMultipleTicketTypes)
			->where('parent_id = ' . $row->id);
		$db->setQuery($query)
			->execute();
	}

	/**
	 * Process ticket types data after registration is completed:
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	private function processTicketTypes($row)
	{
		$config = EventbookingHelper::getConfig();
		$event  = EventbookingHelperDatabase::getEvent($row->event_id);

		if ($event->has_multiple_ticket_types && $config->get('calculate_number_registrants_base_on_tickets_quantity', 1))
		{
			$db    = $this->db;
			$query = $db->getQuery(true)
				->select('a.weight, b.quantity')
				->from('#__eb_ticket_types AS a')
				->innerJoin('#__eb_registrant_tickets AS b ON a.id = b.ticket_type_id')
				->where('b.registrant_id = ' . $row->id);
			$db->setQuery($query);
			$rowTickets        = $db->loadObjectList();
			$numberRegistrants = 0;

			foreach ($rowTickets as $rowTicket)
			{
				$weight = (int) $rowTicket->weight;

				$numberRegistrants += $weight * $rowTicket->quantity;
			}

			$row->number_registrants = $numberRegistrants;
			$row->store();
		}
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
			$ticketTypes               = EventbookingHelperData::getTicketTypes($row->id);
			$params                    = new Registry($row->params);
			$collectMembersInformation = $params->get('ticket_types_collect_members_information', 0);
		}
		else
		{
			$ticketTypes = [];

			for ($i = 0; $i <= 4; $i++)
			{
				$ticketType                          = new stdClass();
				$ticketType->id                      = 0;
				$ticketType->title                   = '';
				$ticketType->price                   = '';
				$ticketType->discount_rules          = '';
				$ticketType->description             = '';
				$ticketType->registered              = 0;
				$ticketType->capacity                = '';
				$ticketType->weight                  = '';
				$ticketType->min_tickets_per_booking = '';
				$ticketType->max_tickets_per_booking = '';
				$ticketType->publish_up              = null;
				$ticketType->publish_down            = null;
				$ticketType->access                  = 1;
				$ticketTypes[]                       = $ticketType;
			}

			$collectMembersInformation = $this->params->get('ticket_types_collect_members_information', 0);
			$params                    = new Registry();
		}

		$form = Form::getInstance('tickettypes', $this->getFormXML($row));

		$formData['ticket_types'] = [];

		foreach ($ticketTypes as $ticketType)
		{
			$ticketTypeData = [
				'id'                      => $ticketType->id,
				'title'                   => $ticketType->title,
				'price'                   => $ticketType->price,
				'discount_rules'          => $ticketType->discount_rules,
				'description'             => $ticketType->description,
				'registered'              => $ticketType->registered,
				'capacity'                => $ticketType->capacity,
				'weight'                  => $ticketType->weight,
				'min_tickets_per_booking' => $ticketType->min_tickets_per_booking,
				'max_tickets_per_booking' => $ticketType->max_tickets_per_booking,
				'publish_up'              => $ticketType->publish_up,
				'publish_down'            => $ticketType->publish_down,
				'access'                  => $ticketType->access,
			];

			foreach ($this->customFields as $customField)
			{
				if (property_exists($ticketType, $customField))
				{
					$ticketTypeData[$customField] = $ticketType->{$customField};
				}
			}

			$formData['ticket_types'][] = $ticketTypeData;
		}

		$form->bind($formData);

		$layoutData = [
			'collectMembersInformation' => $collectMembersInformation,
			'form'                      => $form,
			'row'                       => $row,
			'params'                    => $params,
		];

		return EventbookingHelperHtml::loadCommonLayout('plugins/tickettypes_form.php', $layoutData);
	}

	/**
	 * Method to get form xml definition. Change some field attributes base on Events Booking config and the event
	 * is being edited
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return string
	 */
	private function getFormXML($row)
	{
		$config           = EventbookingHelper::getConfig();
		$datePickerFormat = $config->get('date_field_format', '%Y-%m-%d') . ' %H:%M';

		// Set some default value for form xml base on component settings

		if (file_exists(JPATH_ROOT . '/plugins/eventbooking/tickettypes/form/override_tickettype.xml'))
		{
			$xml = simplexml_load_file(JPATH_ROOT . '/plugins/eventbooking/tickettypes/form/override_tickettype.xml');
		}
		else
		{
			$xml = simplexml_load_file(JPATH_ROOT . '/plugins/eventbooking/tickettypes/form/tickettype.xml');
		}

		$xml->field['layout'] = $this->params->get('subform_layout', 'joomla.form.field.subform.repeatable-table');

		foreach ($xml->field->form->children() as $field)
		{
			if ($field->getName() != 'field' && $field['type'] == 'calendar')
			{
				$field['format'] = $datePickerFormat;
			}

			if ($this->app->isClient('site') && in_array($field['name'], ['title', 'description']))
			{
				unset($field['class']);
			}
		}

		$fieldsToRemove = [];

		if (!$this->params->get('enable_weight'))
		{
			$fieldsToRemove[] = 'weight';
		}

		if (!$this->params->get('enable_min_tickets_per_booking'))
		{
			$fieldsToRemove[] = 'min_tickets_per_booking';
		}

		if (!$this->params->get('enable_discount_rules'))
		{
			$fieldsToRemove[] = 'discount_rules';
		}

		if (!$this->params->get('enable_description', 1))
		{
			$fieldsToRemove[] = 'description';
		}

		if (!$this->params->get('enable_publish_up', 1))
		{
			$fieldsToRemove[] = 'publish_up';
		}

		if (!$this->params->get('enable_publish_down', 1))
		{
			$fieldsToRemove[] = 'publish_down';
		}

		if (!$this->params->get('enable_access', 0))
		{
			$fieldsToRemove[] = 'access';
		}

		foreach ($fieldsToRemove as $fieldName)
		{
			$xpathQuery = "//field[@name='$fieldName']";
			$nodes      = $xml->xpath($xpathQuery);

			foreach ($nodes as $node)
			{
				$dom = dom_import_simplexml($node);
				$dom->parentNode->removeChild($dom);
			}
		}

		return $xml->asXML();
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
