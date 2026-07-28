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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

trait EventbookingViewRegistrants
{
	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Array of filter dropdowns
	 *
	 * @var array
	 */
	protected $filters;

	/**
	 * Custom Fields which will be shown on registrants management screen
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Custom Fields Data
	 *
	 * @var array
	 */
	protected $fieldsData;

	/**
	 * Ticket Types
	 *
	 * @var array
	 */
	protected $ticketTypes;

	/**
	 * Registrants tickets data
	 *
	 * @var array
	 */
	protected $tickets;

	/**
	 * List of core fields
	 *
	 * @var array
	 */
	protected $coreFields;

	/**
	 * Export Templates
	 *
	 * @var array
	 */
	protected $exportTemplates;

	/**
	 * Prepare data for the view
	 *
	 * @throws Exception
	 */
	protected function prepareViewData()
	{
		$app                    = Factory::getApplication();
		$config                 = EventbookingHelper::getConfig();
		$user                   = Factory::getUser();
		$db                     = $this->model->getDbo();
		$query                  = $db->getQuery(true);
		$userHasAdminPermission = $user->authorise('core.admin', 'com_eventbooking');

		if ($app->isClient('site'))
		{
			$fieldSuffix = EventbookingHelper::getFieldSuffix();
		}
		else
		{
			$fieldSuffix = null;
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_ALL_REGISTRANTS'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_ONLY_BILLING_RECORDS'));
		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_ONLY_MEMBER_RECORDS'));

		$this->lists['filter_registrants_type'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_registrants_type',
			' class="input-xlarge form-select" onchange="submit()" ',
			'value',
			'text',
			$this->state->filter_registrants_type
		);

		// Categories filter
		$filters = [];

		if (!$userHasAdminPermission)
		{
			$filters[] = 'submit_event_access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')';
		}

		$this->lists['filter_category_id'] = EventbookingHelperHtml::getCategoryListDropdown(
			'filter_category_id',
			$this->state->filter_category_id,
			'class="form-select" onchange="submit();"',
			null,
			$filters
		);

		// Events filter
		$filters = [];

		if ($this->state->filter_category_id)
		{
			$allCategoryIds = EventbookingHelperData::getAllChildrenCategories($this->state->filter_category_id, true);
			$filters[]      = 'id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (' . implode(',', $allCategoryIds) . '))';
		}
		elseif (!$userHasAdminPermission)
		{
			// Only show events from categories which user has permission to submit event to
			$filters[] = 'id IN (SELECT event_id FROM #__eb_event_categories WHERE category_id IN (SELECT c.id FROM #__eb_categories AS c WHERE submit_event_access IN (' . implode(
					',',
					$user->getAuthorisedViewLevels()
				) . ') ))';
		}

		if ($config->hide_disable_registration_events)
		{
			$filters[] = 'registration_type != 3';
		}

		if ($config->only_show_registrants_of_event_owner && !$user->authorise('core.admin', 'com_eventbooking'))
		{
			$filters[] = 'created_by = ' . $user->id;
		}

		$rows = EventbookingHelperDatabase::getAllEvents(
			$config->sort_events_dropdown,
			$config->hide_past_events_from_events_dropdown,
			$filters,
			$fieldSuffix
		);

		$this->lists['filter_event_id'] = EventbookingHelperHtml::getEventsDropdown(
			$rows,
			'filter_event_id',
			'class="input-xlarge form-select" onchange="submit();"',
			$this->state->filter_event_id
		);

		if ($this->state->filter_event_id > 0)
		{
			$event = EventbookingHelperDatabase::getEvent($this->state->filter_event_id);

			if ($event->has_multiple_ticket_types)
			{
				$options   = [];
				$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_SELECT_TICKET_TYPE'), 'id', 'title');
				$query->clear()
					->select('id, title')
					->from('#__eb_ticket_types')
					->where('event_id = ' . $this->state->filter_event_id)
					->order('ordering');
				$db->setQuery($query);
				$options = array_merge($options, $db->loadObjectList());

				$this->lists['filter_ticket_type_id'] = HTMLHelper::_(
					'select.genericlist',
					$options,
					'filter_ticket_type_id',
					' class="input-medium form-select" onchange="submit()" ',
					'id',
					'title',
					$this->state->filter_ticket_type_id
				);
			}
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', -1, Text::_('EB_REGISTRATION_STATUS'));
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_PENDING'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_PAID'));

		if ($config->activate_waitinglist_feature)
		{
			$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_WAITING_LIST'));
			$options[] = HTMLHelper::_('select.option', 4, Text::_('EB_WAITING_LIST_CANCELLED'));
		}

		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_CANCELLED'));

		$this->lists['filter_published'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_published',
			' class="input-medium form-select" onchange="submit()" ',
			'value',
			'text',
			$this->state->filter_published
		);

		$options                          = [];
		$options[]                        = HTMLHelper::_('select.option', 'tbl.register_date', Text::_('EB_REGISTRATION_DATE'));
		$options[]                        = HTMLHelper::_('select.option', 'tbl.payment_date', Text::_('EB_PAYMENT_DATE'));
		$options[]                        = HTMLHelper::_('select.option', 'event_date', Text::_('EB_EVENT_DATE'));
		$this->lists['filter_date_field'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_date_field',
			' class="form-select input-medium" ',
			'value',
			'text',
			$this->state->filter_date_field
		);

		if ($config->activate_checkin_registrants)
		{
			$options                          = [];
			$options[]                        = HTMLHelper::_('select.option', -1, Text::_('EB_CHECKIN_STATUS'));
			$options[]                        = HTMLHelper::_('select.option', 1, Text::_('EB_CHECKED_IN'));
			$options[]                        = HTMLHelper::_('select.option', 0, Text::_('EB_NOT_CHECKED_IN'));
			$this->lists['filter_checked_in'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'filter_checked_in',
				' class="input-medium form-select" onchange="submit()" ',
				'value',
				'text',
				$this->state->filter_checked_in
			);
		}

		$exportTemplates = $this->model->getExportTemplates();

		if (count($exportTemplates))
		{
			$options   = [];
			$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_DEFAULT'), 'id', 'title');
			$options   = array_merge($options, $exportTemplates);

			$this->lists['export_template'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'export_template',
				'class="form-select"',
				'id',
				'title',
				0
			);
		}

		$query->clear()
			->select('*')
			->from('#__eb_mmtemplates')
			->where('published = 1')
			->order('ordering');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (count($rows))
		{
			$options   = [];
			$options[] = HTMLHelper::_('select.option', '0', Text::_('EB_SELECT'), 'id', 'title');
			$options   = array_merge($options, $rows);

			$this->lists['mm_template_id'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'mm_template_id',
				'class="form-select"',
				'id',
				'title',
				0
			);
		}

		$rowFields = EventbookingHelperRegistration::getAllEventFields($this->state->filter_event_id);
		$fields    = [];
		$filters   = [];

		$filterFieldsValues = $this->state->get('filter_fields', []);

		foreach ($rowFields as $rowField)
		{
			if ($rowField->filterable)
			{
				$fieldOptions = explode("\r\n", $rowField->values);

				$options   = [];
				$options[] = HTMLHelper::_('select.option', '', $rowField->title);

				foreach ($fieldOptions as $option)
				{
					$options[] = HTMLHelper::_('select.option', $option, $option);
				}

				$filters['field_' . $rowField->id] = HTMLHelper::_(
					'select.genericlist',
					$options,
					'filter_fields[field_' . $rowField->id . ']',
					' class="input-medium form-select" onchange="submit();" ',
					'value',
					'text',
					ArrayHelper::getValue($filterFieldsValues, 'field_' . $rowField->id)
				);
			}

			if ($rowField->show_on_registrants != 1 || in_array($rowField->name, ['first_name', 'last_name', 'email']))
			{
				continue;
			}

			$fields[$rowField->id] = $rowField;
		}

		if (count($fields))
		{
			$this->fieldsData = $this->model->getFieldsData(array_keys($fields));
		}

		[$ticketTypes, $tickets] = $this->model->getTicketsData();

		$this->fields          = $fields;
		$this->ticketTypes     = $ticketTypes;
		$this->tickets         = $tickets;
		$this->filters         = $filters;
		$this->config          = $config;
		$this->coreFields      = EventbookingHelperRegistration::getPublishedCoreFields();
		$this->exportTemplates = $exportTemplates;
	}

	/**
	 * Get ticket data for the given registration record
	 *
	 * @param   stdClass  $row
	 *
	 * @return array
	 */
	protected function getRegistrantTicketOutput($row): array
	{
		$ticketsOutput = [];

		if (!empty($this->tickets[$row->id]))
		{
			$tickets = $this->tickets[$row->id];

			foreach ($this->ticketTypes as $ticketType)
			{
				if (!empty($tickets[$ticketType->id]))
				{
					$ticketsOutput[] = Text::_($ticketType->title) . ': ' . $tickets[$ticketType->id];
				}
			}
		}

		return $ticketsOutput;
	}
}
