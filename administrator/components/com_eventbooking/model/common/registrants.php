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
use Joomla\Utilities\ArrayHelper;

class EventbookingModelCommonRegistrants extends RADModelList
{
	/**
	 * The selected registrants to export
	 *
	 * @var array
	 */
	protected $registrantIds = [];

	/**
	 * The export templates
	 *
	 * @var array
	 */
	protected $exportTemplates;

	/**
	 * Exclude status
	 *
	 * @var array
	 */
	protected $excludeStatus = [];

	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  configuration data for the model
	 */
	public function __construct($config = [])
	{
		if (!isset($config['search_fields']))
		{
			$config['search_fields'] = [
				'tbl.first_name',
				'tbl.last_name',
				'tbl.organization',
				'tbl.email',
				'tbl.transaction_id',
				'tbl.invoice_number',
				'tbl.ticket_qrcode',
				'tbl.ticket_code',
				'tbl.ticket_number',
				'tbl.formatted_invoice_number',
			];
		}

		if (!isset($config['remember_states']))
		{
			$config['remember_states'] = true;
		}

		$config['table'] = '#__eb_registrants';

		parent::__construct($config);

		$ebConfig = EventbookingHelper::getConfig();

		if ($ebConfig->allow_filter_registrants_by_type
			&& Factory::getApplication()->isClient('administrator'))
		{
			if ($ebConfig->get('include_group_billing_in_registrants', 1)
				&& $ebConfig->get('include_group_members_in_registrants'))
			{
				$defaultRegistrantsType = 0;
			}
			elseif ($ebConfig->get('include_group_members_in_registrants'))
			{
				$defaultRegistrantsType = 2;
			}
			else
			{
				$defaultRegistrantsType = 1;
			}
		}
		else
		{
			$defaultRegistrantsType = -1;
		}

		$this->state->insert('filter_registrants_type', 'int', $defaultRegistrantsType)
			->insert('filter_category_id', 'int', 0)
			->insert('filter_event_id', 'int', 0)
			->insert('filter_ticket_type_id', 'int', 0)
			->insert('filter_published', 'int', -1)
			->insert('filter_checked_in', 'int', -1)
			->insert('filter_from_date', 'string', '')
			->insert('filter_to_date', 'string', '')
			->insert('filter_fields', 'array', [])
			->insert('filter_exclude_status', 'array', [])
			->insert('filter_date_field', 'string', 'tbl.register_date')
			->setDefault('filter_order', $ebConfig->get('registrants_management_order', 'tbl.id'))
			->setDefault('filter_order_Dir', $ebConfig->get('registrants_management_order_dir', 'DESC'));

		// Dynamic searchable fields
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->where('is_searchable = 1');
		$db->setQuery($query);
		$searchableFields = $db->loadColumn();

		foreach ($searchableFields as $field)
		{
			$field = 'tbl.' . $field;

			if (!in_array($field, $this->searchFields))
			{
				$this->searchFields[] = $field;
			}
		}
	}

	/**
	 * Get list group name for group members records
	 *
	 * @param   array  $rows
	 */
	protected function beforeReturnData($rows)
	{
		// Get group billing records
		$billingIds = [];

		foreach ($rows as $row)
		{
			if ($row->group_id)
			{
				$billingIds[] = $row->group_id;
			}
		}

		if (count($billingIds) == 0)
		{
			return;
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('id, first_name, last_name')
			->from('#__eb_registrants')
			->where('id IN (' . implode(',', $billingIds) . ')');
		$db->setQuery($query);
		$billingRecords = $db->loadObjectList('id');

		foreach ($rows as $row)
		{
			if (!$row->group_id)
			{
				continue;
			}

			$billingRecord = $billingRecords[$row->group_id] ?? null;

			if ($billingRecord)
			{
				$row->group_name = trim($billingRecord->first_name . ' ' . $billingRecord->last_name);
			}
		}
	}

	/**
	 * Get registrants custom fields data
	 *
	 * @param   array  $fields
	 *
	 * @return array
	 */
	public function getFieldsData($fields)
	{
		if ($this->data === null || !count($this->data) || !count($fields))
		{
			return [];
		}

		$rows       = $this->data;
		$fieldsData = [];

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('id, encrypt_data, fieldtype')
			->from('#__eb_fields')
			->where('id IN (' . implode(',', $fields) . ')');
		$db->setQuery($query);
		$rowFields = $db->loadObjectList('id');

		$registrantIds = [];

		foreach ($rows as $row)
		{
			$registrantIds[] = $row->id;
		}

		$query->clear()
			->select('registrant_id, field_id, field_value')
			->from('#__eb_field_values')
			->where('registrant_id IN (' . implode(',', $registrantIds) . ')')
			->where('field_id IN (' . implode(',', $fields) . ')');
		$db->setQuery($query);
		$rowFieldValues = $db->loadObjectList();

		$config = EventbookingHelper::getConfig();

		foreach ($rowFieldValues as $rowFieldValue)
		{
			$fieldValue = $rowFieldValue->field_value;
			$rowField   = $rowFields[$rowFieldValue->field_id];

			if ($rowField->fieldtype == 'Date')
			{
				try
				{
					$dateTime   = new DateTime($fieldValue);
					$fieldValue = $dateTime->format($config->date_format);
				}
				catch (Exception $e)
				{
				}
			}
			elseif (is_string($fieldValue) && is_array(json_decode($fieldValue)))
			{
				$fieldValue = implode(', ', json_decode($fieldValue));
			}
			elseif ($rowField->fieldtype == 'Text' && $rowField->encrypt_data && $fieldValue)
			{
				$fieldValue = EventbookingHelperCryptor::decrypt($fieldValue);
			}

			$fieldsData[$rowFieldValue->registrant_id][$rowFieldValue->field_id] = $fieldValue;
		}

		// Get data from core fields
		$query->clear()
			->select('id, name')
			->from('#__eb_fields')
			->where('id IN (' . implode(',', $fields) . ')')
			->where('is_core = 1');
		$db->setQuery($query);
		$coreFields = $db->loadObjectList();

		if (count($coreFields))
		{
			foreach ($rows as $row)
			{
				foreach ($coreFields as $coreField)
				{
					$fieldsData[$row->id][$coreField->id] = $row->{$coreField->name};
				}
			}
		}

		return $fieldsData;
	}

	/**
	 * Get tickets data for the selected events
	 *
	 * @param   int  $eventId
	 *
	 * @return array
	 */
	public function getTicketsData($eventId = 0)
	{
		$ticketTypes = [];
		$tickets     = [];

		if (!$eventId)
		{
			$eventId = $this->state->filter_event_id ?: $this->state->id;
		}

		if ($eventId > 0)
		{
			$event = EventbookingHelperDatabase::getEvent($eventId);

			if ($event->has_multiple_ticket_types)
			{
				$ticketTypes = EventbookingHelperData::getTicketTypes($eventId);

				$ticketTypeIds = [];

				foreach ($ticketTypes as $ticketType)
				{
					$ticketTypeIds[] = $ticketType->id;
				}

				$db    = $this->getDbo();
				$query = $db->getQuery(true);
				$query->select('registrant_id, ticket_type_id, quantity')
					->from('#__eb_registrant_tickets')
					->where('ticket_type_id IN (' . implode(',', $ticketTypeIds) . ')');
				$db->setQuery($query);
				$registrantTickets = $db->loadObjectList();

				foreach ($registrantTickets as $registrantTicket)
				{
					$tickets[$registrantTicket->registrant_id][$registrantTicket->ticket_type_id] = $registrantTicket->quantity;
				}
			}
		}

		return [$ticketTypes, $tickets];
	}

	/**
	 * Builds SELECT columns list for the query
	 */
	protected function buildQueryColumns(JDatabaseQuery $query)
	{
		if (Factory::getApplication()->isClient('site'))
		{
			$fieldSuffix = EventbookingHelper::getFieldSuffix();
		}
		else
		{
			$fieldSuffix = '';
		}

		$db          = $this->getDbo();
		$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$query->select('tbl.*')
			->select('ev.event_date, ev.event_end_date, ev.ticket_prefix, ev.main_category_id, ev.custom_fields, ev.activate_certificate_feature')
			->select($db->quoteName('ev.title' . $fieldSuffix, 'title'))
			->select("TIMESTAMPDIFF(MINUTE, ev.event_end_date, $currentDate) AS event_end_date_minutes")
			->select('u.username')
			->select('cp.code AS coupon_code, cp.id AS coupon_id')
			->select($db->quoteName('c.name' . $fieldSuffix, 'category_name'))
			->select($db->quoteName('l.name' . $fieldSuffix, 'location_name'))
			->select('l.address AS location_address');

		return $this;
	}

	/**
	 * Builds LEFT JOINS clauses for the query
	 */
	protected function buildQueryJoins(JDatabaseQuery $query)
	{
		$query->innerJoin('#__eb_events AS ev ON tbl.event_id = ev.id')
			->leftJoin('#__users AS u ON tbl.user_id = u.id')
			->leftJoin('#__eb_coupons AS cp ON tbl.coupon_id = cp.id')
			->leftJoin('#__eb_categories AS c ON ev.main_category_id = c.id')
			->leftJoin('#__eb_locations AS l ON ev.location_id = l.id');

		return $this;
	}

	/**
	 * Build where clase of the query
	 *
	 * @see RADModelList::buildQueryWhere()
	 */
	protected function buildQueryWhere(JDatabaseQuery $query)
	{
		$db   = $this->getDbo();
		$user = Factory::getUser();

		if ($this->name == 'registrants')
		{
			$userHasAdminPermission = $user->authorise('core.admin', 'com_eventbooking');
		}
		else
		{
			$userHasAdminPermission = true;
		}

		$config     = EventbookingHelper::getConfig();
		$dateFormat = str_replace('%', '', $config->get('date_field_format', '%Y-%m-%d')) . ' H:i:s';

		if (!empty($this->registrantIds))
		{
			$query->clear('where')
				->where('tbl.id IN (' . implode(',', $this->registrantIds) . ')');

			return $this;
		}

		if ($this->state->filter_published != -1)
		{
			$query->where(' tbl.published = ' . $this->state->filter_published);
		}

		if ($this->state->filter_checked_in != -1)
		{
			$query->where(' tbl.checked_in = ' . $this->state->filter_checked_in);
		}

		if ($this->state->filter_category_id)
		{
			$allCategoryIds = EventbookingHelperData::getAllChildrenCategories($this->state->filter_category_id, true);
			$query->where(
				'tbl.event_id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (' . implode(
					',',
					$allCategoryIds
				) . '))'
			);
		}

		if ($this->state->filter_event_id || $this->state->id)
		{
			$eventId = $this->state->filter_event_id ?: $this->state->id;

			$query->where(' tbl.event_id = ' . $eventId);
		}
		elseif (!$userHasAdminPermission && !$this->state->filter_category_id)
		{
			// Only show registrants from events belong to category which users can submit event to
			$query->where(
				'tbl.event_id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (SELECT c.id FROM #__eb_categories AS c WHERE submit_event_access IN (' . implode(
					',',
					$user->getAuthorisedViewLevels()
				) . ') ))'
			);
		}

		if ($this->state->filter_event_id > 0)
		{
			$event = EventbookingHelperDatabase::getEvent($this->state->filter_event_id);

			if ($event && $event->has_multiple_ticket_types && $this->state->filter_ticket_type_id > 0)
			{
				$query->where(
					'tbl.id IN (SELECT registrant_id FROM #__eb_registrant_tickets WHERE ticket_type_id = ' . $this->state->filter_ticket_type_id . ')'
				);
			}
		}

		if ((int) $this->state->filter_from_date)
		{
			// In case use only select date, we will set time of From Date to 00:00:00
			if (strpos($this->state->filter_from_date, ' ') === false && strlen($this->state->filter_from_date) <= 10)
			{
				$fromDate = $this->state->filter_from_date . ' 00:00:00';
			}
			else
			{
				$fromDate = $this->state->filter_from_date;
			}

			try
			{
				$date = DateTime::createFromFormat($dateFormat, $fromDate, new DateTimeZone(Factory::getApplication()->get('offset')));

				if ($date !== false)
				{
					$date->setTime(0, 0, 0);
					$date->setTimezone(new DateTimeZone('UTC'));

					if ($this->state->filter_date_field === 'tbl.register_date')
					{
						$query->where('tbl.register_date >= ' . $db->quote($date->format('Y-m-d H:i:s')));
					}
					elseif ($this->state->filter_date_field === 'tbl.payment_date')
					{
						$query->where('tbl.payment_date >= ' . $db->quote($date->format('Y-m-d H:i:s')));
					}
					else
					{
						$query->where(
							'tbl.event_id IN (SELECT tbl1.id FROM #__eb_events AS tbl1 WHERE tbl1.event_date >= ' . $db->quote(
								$date->format('Y-m-d H:i:s')
							) . ')'
						);
					}
				}
			}
			catch (Exception $e)
			{
				// Do-nothing
			}
		}

		if ((int) $this->state->filter_to_date)
		{
			// In case use only select date, we will set time of To Date to 23:59:59
			// In case use only select date, we will set time of From Date to 00:00:00
			if (strpos($this->state->filter_to_date, ' ') === false && strlen($this->state->filter_to_date) <= 10)
			{
				$toDate = $this->state->filter_to_date . ' 23:59:59';
			}
			else
			{
				$toDate = $this->state->filter_to_date;
			}

			try
			{
				$date = DateTime::createFromFormat($dateFormat, $toDate, new DateTimeZone(Factory::getApplication()->get('offset')));

				if ($date !== false)
				{
					$date->setTime(23, 59, 59);
					$date->setTimezone(new DateTimeZone('UTC'));

					if ($this->state->filter_date_field === 'tbl.register_date')
					{
						$query->where('tbl.register_date <= ' . $db->quote($date->format('Y-m-d H:i:s')));
					}
					elseif ($this->state->filter_date_field === 'tbl.payment_date')
					{
						$query->where('tbl.payment_date <= ' . $db->quote($date->format('Y-m-d H:i:s')));
					}
					else
					{
						$query->where(
							'tbl.event_id IN (SELECT tbl2.id FROM #__eb_events AS tbl2 WHERE tbl2.event_date <= ' . $db->quote(
								$date->format('Y-m-d H:i:s')
							) . ')'
						);
					}
				}
			}
			catch (Exception $e)
			{
				// Do-nothing
			}
		}

		if ($this->state->get('filter_exclude_status', []))
		{
			$query->where('tbl.published NOT IN (' . implode(',', $this->state->get('filter_exclude_status', [])) . ')');
		}

		$filterFields = array_filter($this->state->get('filter_fields', []));

		foreach ($filterFields as $fieldName => $fieldValue)
		{
			$pos        = strrpos($fieldName, '_');
			$fieldId    = (int) substr($fieldName, $pos + 1);
			$fieldValue = $db->quote('%' . $db->escape(trim($fieldValue), true) . '%', false);
			$query->where(
				'tbl.id IN (SELECT registrant_id FROM #__eb_field_values WHERE field_id = ' . $fieldId . ' AND field_value LIKE ' . $fieldValue . ')'
			);
		}

		if ($this->excludeStatus)
		{
			$query->where('tbl.published NOT IN (' . implode(',', $this->excludeStatus) . ')');
		}

		return parent::buildQueryWhere($query);
	}

	/**
	 * Setter method to set selected registrantIds for exporting
	 *
	 * @param   array  $registrantIds
	 */
	public function setRegistrantIds($registrantIds)
	{
		$this->registrantIds = $registrantIds;
	}

	/**
	 * Get export templates
	 *
	 * @return array|mixed
	 */
	public function getExportTemplates()
	{
		if ($this->exportTemplates === null)
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_exporttmpls')
				->where('published = 1')
				->order('ordering');
			$db->setQuery($query);

			$this->exportTemplates = $db->loadObjectList();
		}

		return $this->exportTemplates;
	}

	/**
	 * Get export template
	 *
	 * @param   int  $id
	 *
	 * @return mixed|null
	 */
	public function getExportTemplate($id)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_exporttmpls')
			->where('id = ' . $id);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Set exclude status
	 *
	 * @param   array  $statues
	 *
	 * @return void
	 */
	public function setExcludeStatus($statues)
	{
		$this->excludeStatus = array_filter(ArrayHelper::toInteger($statues));
	}
}
