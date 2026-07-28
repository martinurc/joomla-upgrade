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
use Joomla\CMS\Pagination\Pagination;

class EventbookingViewRegistrantlistHtml extends RADViewHtml
{
	/**
	 * Model state
	 *
	 * @var RADModelState
	 */
	protected $state;

	/**
	 * The event which registrants are being displayed
	 *
	 * @var stdClass
	 */
	protected $event;

	/**
	 * Registration records data
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	protected $pagination;

	/**
	 * Ticket Types
	 *
	 * @var array
	 */
	protected $ticketTypes;

	/**
	 * Registrants Ticket Data
	 *
	 * @var array
	 */
	protected $tickets;

	/**
	 * ID of fields which will be displayed
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Title of fields
	 *
	 * @var array
	 */
	protected $fieldTitles;

	/**
	 * Registrants custom fields value
	 *
	 * @var array
	 */
	protected $fieldValues;

	/**
	 * Core fields
	 *
	 * @var array
	 */
	protected $coreFields;

	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Flag to mark if last name should be shown
	 *
	 * @var bool
	 */
	protected $showLastName = false;

	/**
	 * Twitter Bootstrap Helper
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * Flag to mark of custom fields need to be displayed
	 *
	 * @var bool
	 */
	protected $displayCustomField = false;

	/**
	 * Array contains all fields should be shown on public registrants list
	 *
	 * @var array
	 */
	protected $showOnRegistrantsListFieldNames = [];

	public function display()
	{
		$state   = $this->model->getState();
		$eventId = $state->id;

		if (!$eventId)
		{
			return;
		}

		if (!EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$eventId]))
		{
			$user = Factory::getUser();

			if (!$user->id)
			{
				$this->requestLogin();
			}
			else
			{
				return;
			}
		}

		$rows      = $this->model->getData();
		$config    = EventbookingHelper::getConfig();
		$event     = EventbookingHelperDatabase::getEvent($eventId);
		$rowFields = EventbookingHelperRegistration::getAllPublicEventFields($eventId);

		if (count($rowFields))
		{
			$this->prepareCustomFieldsData($rows, $rowFields);
			$this->displayCustomField = true;
		}

		foreach ($rowFields as $rowField)
		{
			if ($rowField->name == 'last_name')
			{
				$this->showLastName = true;
			}

			$this->showOnRegistrantsListFieldNames[] = $rowField->name;
		}

		if ($config->get('public_registrants_list_show_ticket_types'))
		{
			[$ticketTypes, $tickets] = $this->model->getTicketsData($eventId);
		}
		else
		{
			$ticketTypes = $tickets = [];
		}

		$this->state           = $state;
		$this->event           = $event;
		$this->items           = $rows;
		$this->pagination      = $this->model->getPagination();
		$this->ticketTypes     = $ticketTypes;
		$this->tickets         = $tickets;
		$this->config          = $config;
		$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();
		$this->coreFields      = EventbookingHelperRegistration::getPublishedCoreFields();

		// Do not display anything in the plugin if there is no registrants data
		if ($this->input->getInt('hmvc_call') && !count($this->items))
		{
			return;
		}

		$this->setLayout('default');

		parent::display();
	}

	/**
	 * Prepare custom fields data for registration records
	 *
	 * @param   array  $rows
	 * @param   array  $rowFields
	 */
	protected function prepareCustomFieldsData($rows, $rowFields)
	{
		$fields      = [];
		$fieldTitles = [];

		foreach ($rowFields as $rowField)
		{
			if (in_array($rowField->name, ['first_name', 'last_name']))
			{
				continue;
			}

			$fieldTitles[$rowField->id] = $rowField->title;
			$fields[]                   = $rowField->id;
		}

		$this->fieldValues = $this->model->getFieldsData($fields);

		foreach ($rows as $row)
		{
			foreach ($rowFields as $rowField)
			{
				if (property_exists($row, $rowField->name))
				{
					continue;
				}

				if (isset($this->fieldValues[$row->id][$rowField->id]))
				{
					$fieldValue = $this->fieldValues[$row->id][$rowField->id];
				}
				else
				{
					$fieldValue = '';
				}

				$row->{$rowField->name} = $fieldValue;
			}
		}

		$this->fieldTitles = $fieldTitles;
		$this->fields      = $fields;
	}
}
