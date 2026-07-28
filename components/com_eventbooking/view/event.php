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
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

trait EventbookingViewEvent
{
	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * List dropdowns which is used on submit event form
	 *
	 * @var array
	 */
	protected $lists = [];

	/**
	 * Event custom fields form
	 *
	 * @var Form
	 */
	protected $form;

	/**
	 * Group registration rates of editing event
	 *
	 * @var array
	 */
	protected $prices;

	/**
	 * Plugin outputs which will be displayed using tabs
	 *
	 * @var array
	 */
	protected $plugins;

	/**
	 * The value represent database null date
	 *
	 * @var string
	 */
	protected $nullDate;

	/**
	 * The configured date picker format
	 *
	 * @var string
	 */
	protected $datePickerFormat;

	/**
	 * The date format
	 *
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * Build data use on submit event form
	 *
	 * @param   EventbookingTableEvent  $item
	 * @param   array                   $categories
	 * @param   array                   $locations
	 */
	public function buildFormData($item, $categories, $locations)
	{
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$config = EventbookingHelper::getConfig();
		$params = new Registry($item->params ?? '{}');

		//Locations dropdown
		$options                    = [];
		$options[]                  = HTMLHelper::_('select.option', 0, Text::_('EB_SELECT_LOCATION'), 'id', 'name');
		$options                    = array_merge($options, $locations);
		$this->lists['location_id'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'location_id',
			' class="advancedSelect form-select" ',
			'id',
			'name',
			$item->location_id
		);

		if ($this->getLayout() == 'simple')
		{
			$selectCategoryValue = '';
		}
		else
		{
			$selectCategoryValue = 0;
		}

		$options = EventbookingHelper::callOverridableHelperMethod('Html', 'getCategoryOptions', [$categories, $selectCategoryValue]);

		if ($item->id)
		{
			$query->clear()
				->select('category_id')
				->from('#__eb_event_categories')
				->where('event_id = ' . $item->id)
				->where('main_category = 0');
			$db->setQuery($query);
			$additionalCategories = $db->loadColumn();
		}
		else
		{
			$additionalCategories = $this->input->post->get('category_id', [], 'array');
			$additionalCategories = ArrayHelper::toInteger($additionalCategories);
		}

		$this->lists['main_category_id'] = HTMLHelper::_('select.genericlist', $options, 'main_category_id', [
			'option.text.toHtml' => false,
			'option.text'        => 'text',
			'option.value'       => 'value',
			'list.attr'          => 'class="advancedSelect input-xlarge validate[required] form-select"',
			'list.select'        => (int) $item->main_category_id,
		]);

		array_shift($options);

		$this->lists['category_id'] = HTMLHelper::_('select.genericlist', $options, 'category_id[]', [
			'option.text.toHtml' => false,
			'option.text'        => 'text',
			'option.value'       => 'value',
			'list.attr'          => 'class="advancedSelect input-xlarge form-select"  size="5" multiple="multiple"',
			'list.select'        => $additionalCategories,
		]);

		$options                                 = [];
		$options[]                               = HTMLHelper::_('select.option', 1, Text::_('%'));
		$options[]                               = HTMLHelper::_('select.option', 2, $config->currency_symbol);
		$this->lists['discount_type']            = HTMLHelper::_(
			'select.genericlist',
			$options,
			'discount_type',
			' class="input-medium form-select d-inline-block" ',
			'value',
			'text',
			$item->discount_type
		);
		$this->lists['early_bird_discount_type'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'early_bird_discount_type',
			'class="input-medium form-select d-inline-block"',
			'value',
			'text',
			$item->early_bird_discount_type
		);
		$this->lists['late_fee_type']            = HTMLHelper::_(
			'select.genericlist',
			$options,
			'late_fee_type',
			'class="input-medium form-select d-inline-block"',
			'value',
			'text',
			$item->late_fee_type
		);

		if ($config->activate_deposit_feature)
		{
			$this->lists['deposit_type'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'deposit_type',
				' class="input-small form-select" ',
				'value',
				'text',
				$item->deposit_type
			);
		}

		if (!$item->id)
		{
			$item->registration_type = $config->registration_type;
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_INDIVIDUAL_GROUP'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_INDIVIDUAL_ONLY'));
		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_GROUP_ONLY'));
		$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_DISABLE_REGISTRATION'));

		$this->lists['registration_type'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'registration_type',
			' class="input-xlarge form-select" ',
			'value',
			'text',
			$item->registration_type
		);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_EACH_MEMBER'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_EACH_REGISTRATION'));

		$this->lists['members_discount_apply_for'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'members_discount_apply_for',
			'class="form-select"',
			'value',
			'text',
			$item->members_discount_apply_for
		);

		$options = [];

		if ($config->enable_coupon)
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('EB_ENABLE') . ')';
		}
		else
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('EB_DISABLE') . ')';
		}

		$options[] = HTMLHelper::_('select.option', 0, $useGlobalText);
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_INDIVIDUAL_ONLY'));
		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_GROUP_ONLY'));
		$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_INDIVIDUAL_GROUP'));
		$options[] = HTMLHelper::_('select.option', 4, Text::_('EB_DISABLE'));

		$this->lists['enable_coupon'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'enable_coupon',
			' class="form-select" ',
			'value',
			'text',
			$item->enable_coupon
		);

		if ($config->activate_waitinglist_feature)
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JYES') . ')';
		}
		else
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JNO') . ')';
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));
		$options[] = HTMLHelper::_('select.option', 2, $useGlobalText);

		$this->lists['activate_waiting_list'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'activate_waiting_list',
			' class="form-select" ',
			'value',
			'text',
			$item->activate_waiting_list
		);

		$this->lists['access']              = HTMLHelper::_('access.level', 'access', $item->access, 'class="form-select"', false);
		$this->lists['registration_access'] = HTMLHelper::_(
			'access.level',
			'registration_access',
			$item->registration_access,
			'class="form-select"',
			false
		);

		$dataValidationError = (bool) $this->input->getInt('validate_input_error', 0);

		if (!$dataValidationError && (int) $item->event_date)
		{
			$selectedHour   = date('G', strtotime($item->event_date));
			$selectedMinute = date('i', strtotime($item->event_date));
		}
		else
		{
			$selectedHour   = $this->input->post->getInt('event_date_hour', $config->get('default_event_date_hour', 0));
			$selectedMinute = $this->input->post->getInt('event_date_minute', $config->get('default_event_date_minute', 0));
		}

		$this->lists['event_date_hour'] = HTMLHelper::_(
			'select.integerlist',
			0,
			23,
			1,
			'event_date_hour',
			' class="input-small form-select w-auto d-inline-block" ',
			$selectedHour
		);

		$this->lists['event_date_minute'] = HTMLHelper::_(
			'select.integerlist',
			0,
			55,
			5,
			'event_date_minute',
			' class="input-small form-select w-auto d-inline-block" ',
			(int) $selectedMinute,
			'%02d'
		);

		if (!$dataValidationError && (int) $item->event_end_date)
		{
			$selectedHour   = date('G', strtotime($item->event_end_date));
			$selectedMinute = date('i', strtotime($item->event_end_date));
		}
		else
		{
			$selectedHour   = $this->input->post->getInt('event_end_date_hour', $config->get('default_event_end_date_hour', 0));
			$selectedMinute = $this->input->post->getInt('event_end_date_minute', $config->get('default_event_end_date_minute', 0));
		}

		$this->lists['event_end_date_hour'] = HTMLHelper::_(
			'select.integerlist',
			0,
			23,
			1,
			'event_end_date_hour',
			' class="input-small form-select w-auto d-inline-block" ',
			$selectedHour
		);

		$this->lists['event_end_date_minute'] = HTMLHelper::_(
			'select.integerlist',
			0,
			55,
			5,
			'event_end_date_minute',
			' class="input-small form-select w-auto d-inline-block" ',
			(int) $selectedMinute,
			'%02d'
		);

		// Cut off time
		if (!$dataValidationError && (int) $item->cut_off_date)
		{
			$selectedHour   = date('G', strtotime($item->cut_off_date));
			$selectedMinute = date('i', strtotime($item->cut_off_date));
		}
		else
		{
			$selectedHour   = $this->input->post->getInt('cut_off_hour', 0);
			$selectedMinute = $this->input->post->getInt('cut_off_minute', 0);
		}

		$this->lists['cut_off_hour']   = HTMLHelper::_(
			'select.integerlist',
			0,
			23,
			1,
			'cut_off_hour',
			' class="input-small form-select w-auto d-inline-block" ',
			$selectedHour
		);
		$this->lists['cut_off_minute'] = HTMLHelper::_(
			'select.integerlist',
			0,
			55,
			5,
			'cut_off_minute',
			' class="input-small form-select w-auto d-inline-block" ',
			(int) $selectedMinute,
			'%02d'
		);

		// Registration start time
		if (!$dataValidationError && (int) $item->registration_start_date)
		{
			$selectedHour   = date('G', strtotime($item->registration_start_date));
			$selectedMinute = date('i', strtotime($item->registration_start_date));
		}
		else
		{
			$selectedHour   = $this->input->post->getInt('registration_start_hour', 0);
			$selectedMinute = $this->input->post->getInt('registration_start_minute', 0);
		}

		$this->lists['registration_start_hour']   = HTMLHelper::_(
			'select.integerlist',
			0,
			23,
			1,
			'registration_start_hour',
			' class="form-select input-small form-select w-auto d-inline-block" ',
			$selectedHour
		);
		$this->lists['registration_start_minute'] = HTMLHelper::_(
			'select.integerlist',
			0,
			55,
			5,
			'registration_start_minute',
			' class="form-select input-small form-select w-auto d-inline-block" ',
			(int) $selectedMinute,
			'%02d'
		);

		$nullDate = $db->getNullDate();

		//Custom field handles
		if ($config->event_custom_field)
		{
			$data = new stdClass();

			if ($this->input->getMethod() === 'POST')
			{
				$data->params = $this->input->post->get('params', [], 'array');
			}
			else
			{
				$registry     = new Registry($item->custom_fields);
				$data->params = $registry->toArray();
			}

			try
			{
				$form = Form::getInstance('pmform', JPATH_ROOT . '/components/com_eventbooking/fields.xml', [], false, '//config');
				$form->bind($data);
				$this->form = $form;
			}
			catch (Exception $e)
			{
			}
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', Text::_('EB_ALL_PAYMENT_METHODS'), 'id', 'title');

		$query->clear()
			->select('id, title')
			->from('#__eb_payment_plugins')
			->where('published = 1');
		$db->setQuery($query);

		// This is added to support a customer only, normally, this should never be an array
		if (is_array($item->payment_methods))
		{
			$item->payment_methods = implode(',', $item->payment_methods);
		}

		$this->lists['payment_methods'] = HTMLHelper::_(
			'select.genericlist',
			array_merge($options, $db->loadObjectList()),
			'payment_methods[]',
			' class="form-select advancedSelect" multiple="multiple" ',
			'id',
			'title',
			explode(',', $item->payment_methods ?? '')
		);

		$currencies = require JPATH_ROOT . '/components/com_eventbooking/helper/currencies.php';
		ksort($currencies);
		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', Text::_('EB_SELECT_CURRENCY'));

		foreach ($currencies as $code => $title)
		{
			$options[] = HTMLHelper::_('select.option', $code, $title);
		}

		$this->lists['currency_code'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'currency_code',
			'class="form-select"',
			'value',
			'text',
			$item->currency_code
		);

		$this->lists['discount_groups'] = HTMLHelper::_(
			'access.usergroup',
			'discount_groups[]',
			explode(',', $item->discount_groups ?? ''),
			' multiple="multiple" size="6" ',
			false
		);

		$this->lists['available_attachment'] = EventbookingHelper::callOverridableHelperMethod(
			'Helper',
			'attachmentList',
			[explode('|', $item->attachment ?? ''), $config]
		);

		if ($config->user_registration)
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JYES') . ')';
		}
		else
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JNO') . ')';
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', $useGlobalText);
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));

		$this->lists['user_registration'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'user_registration',
			'class="form-select"',
			'value',
			'text',
			$params->get('user_registration', '')
		);

		if ($config->accept_term)
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JYES') . ')';
		}
		else
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JNO') . ')';
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));
		$options[] = HTMLHelper::_('select.option', 2, $useGlobalText);

		$this->lists['enable_terms_and_conditions'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'enable_terms_and_conditions',
			' class="form-select" ',
			'value',
			'text',
			$item->enable_terms_and_conditions
		);

		if ($config->prevent_duplicate_registration)
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JYES') . ')';
		}
		else
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JNO') . ')';
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', $useGlobalText);
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));

		$this->lists['prevent_duplicate_registration'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'prevent_duplicate_registration',
			'class="form-select"',
			'value',
			'text',
			$item->prevent_duplicate_registration
		);

		if ($config->collect_member_information)
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JYES') . ')';
		}
		else
		{
			$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JNO') . ')';
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', $useGlobalText);
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));

		$this->lists['collect_member_information'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'collect_member_information',
			'class="form-select"',
			'value',
			'text',
			$item->collect_member_information
		);

		switch ($config->send_emails)
		{
			case 0:
				$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('EB_ENABLE') . ')';
				break;
			case 1:
				$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('EB_ONLY_TO_ADMIN') . ')';
				break;
			case 2:
				$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('EB_ONLY_TO_REGISTRANT') . ')';
				break;
			case 3:
				$useGlobalText = Text::_('EB_USE_GLOBAL') . ' (' . Text::_('JYES') . ')';
				break;
			default:
				$useGlobalText = Text::_('EB_USE_GLOBAL');
				break;
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', -1, $useGlobalText);
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_ENABLE'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_ONLY_TO_ADMIN'));
		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_ONLY_TO_REGISTRANT'));
		$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_DISABLE'));

		$this->lists['send_emails'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'send_emails',
			'class="form-select"',
			'value',
			'text',
			$item->send_emails
		);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_PENDING'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_PAID'));

		$this->lists['free_event_registration_status'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'free_event_registration_status',
			'class="form-select"',
			'value',
			'text',
			$item->free_event_registration_status
		);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '1', Text::_('EB_BEFORE'));
		$options[] = HTMLHelper::_('select.option', '-1', Text::_('EB_AFTER'));

		$this->lists['send_first_reminder_time']  = HTMLHelper::_(
			'select.genericlist',
			$options,
			'send_first_reminder_time',
			'class="input-medium form-select d-inline-block"',
			'value',
			'text',
			$item->send_first_reminder >= 0 ? 1 : -1
		);
		$this->lists['send_second_reminder_time'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'send_second_reminder_time',
			'class="input-medium form-select d-inline-block"',
			'value',
			'text',
			$item->send_second_reminder >= 0 ? 1 : -1
		);

		$this->lists['send_third_reminder_time'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'send_third_reminder_time',
			'class="input-medium form-select d-inline-block"',
			'value',
			'text',
			$item->send_third_reminder >= 0 ? 1 : -1
		);

		// Workaround to allow easier supporting up to 6 reminders
		if (property_exists($item, 'send_fourth_reminder'))
		{
			$this->lists['send_fourth_reminder_time']  = HTMLHelper::_(
				'select.genericlist',
				$options,
				'send_fourth_reminder_time',
				'class="input-medium form-select d-inline-block"',
				'value',
				'text',
				$item->send_fourth_reminder >= 0 ? 1 : -1
			);

			$item->send_fourth_reminder  = abs($item->send_fourth_reminder);
		}

		if (property_exists($item, 'send_fifth_reminder'))
		{
			$this->lists['send_fifth_reminder_time']  = HTMLHelper::_(
				'select.genericlist',
				$options,
				'send_fifth_reminder_time',
				'class="input-medium form-select d-inline-block"',
				'value',
				'text',
				$item->send_fifth_reminder >= 0 ? 1 : -1
			);

			$item->send_fifth_reminder  = abs($item->send_fifth_reminder);
		}

		if (property_exists($item, 'send_sixth_reminder'))
		{
			$this->lists['send_sixth_reminder_time']  = HTMLHelper::_(
				'select.genericlist',
				$options,
				'send_sixth_reminder_time',
				'class="input-medium form-select d-inline-block"',
				'value',
				'text',
				$item->send_sixth_reminder >= 0 ? 1 : -1
			);

			$item->send_sixth_reminder  = abs($item->send_sixth_reminder);
		}

		$item->send_first_reminder  = abs($item->send_first_reminder);
		$item->send_second_reminder = abs($item->send_second_reminder);
		$item->send_third_reminder  = abs($item->send_third_reminder);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 'd', Text::_('EB_DAYS'));
		$options[] = HTMLHelper::_('select.option', 'h', Text::_('EB_HOURS'));

		$this->lists['first_reminder_frequency']  = HTMLHelper::_(
			'select.genericlist',
			$options,
			'first_reminder_frequency',
			'class="form-select d-inline-block w-auto"',
			'value',
			'text',
			$item->first_reminder_frequency ?: 'd'
		);
		$this->lists['second_reminder_frequency'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'second_reminder_frequency',
			'class="form-select d-inline-block w-auto"',
			'value',
			'text',
			$item->second_reminder_frequency ?: 'd'
		);
		$this->lists['third_reminder_frequency']  = HTMLHelper::_(
			'select.genericlist',
			$options,
			'third_reminder_frequency',
			'class="form-select d-inline-block w-auto"',
			'value',
			'text',
			$item->third_reminder_frequency ?: 'd'
		);

		if (property_exists($item, 'fourth_reminder_frequency'))
		{
			$this->lists['fourth_reminder_frequency']  = HTMLHelper::_(
				'select.genericlist',
				$options,
				'fourth_reminder_frequency',
				'class="form-select d-inline-block w-auto"',
				'value',
				'text',
				$item->fourth_reminder_frequency ?: 'd'
			);
		}

		if (property_exists($item, 'fifth_reminder_frequency'))
		{
			$this->lists['fifth_reminder_frequency']  = HTMLHelper::_(
				'select.genericlist',
				$options,
				'fifth_reminder_frequency',
				'class="form-select d-inline-block w-auto"',
				'value',
				'text',
				$item->fifth_reminder_frequency ?: 'd'
			);
		}

		if (property_exists($item, 'sixth_reminder_frequency'))
		{
			$this->lists['sixth_reminder_frequency']  = HTMLHelper::_(
				'select.genericlist',
				$options,
				'sixth_reminder_frequency',
				'class="form-select d-inline-block w-auto"',
				'value',
				'text',
				$item->sixth_reminder_frequency ?: 'd'
			);
		}

		// Recurring settings
		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_NO_REPEAT'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_DAILY'));
		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_WEEKLY'));
		$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_MONTHLY_BY_DAYS'));
		$options[] = HTMLHelper::_('select.option', 4, Text::_('EB_MONTHLY_BY_WEEKDAY'));

		$this->lists['recurring_type'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'recurring_type',
			' class="form-select input-large" ',
			'value',
			'text',
			$item->recurring_type
		);

		if ($item->published == 2)
		{
			$options                  = [];
			$options[]                = HTMLHelper::_('select.option', 0, Text::_('EB_UNPUBLISHED'));
			$options[]                = HTMLHelper::_('select.option', 1, Text::_('EB_PUBLISHED'));
			$options[]                = HTMLHelper::_('select.option', 2, Text::_('EB_CANCELLED'));
			$this->lists['published'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'published',
				' class="form-select" ',
				'value',
				'text',
				$item->published
			);
		}

		#Plugin support
		PluginHelper::importPlugin('eventbooking');
		$results = Factory::getApplication()->triggerEvent('onEditEvent', [$item]);

		$this->datePickerFormat = $config->get('date_field_format', '%Y-%m-%d');
		$this->dateFormat       = str_replace('%', '', $this->datePickerFormat);
		$this->prices           = EventbookingHelperDatabase::getGroupRegistrationRates($item->id);
		$this->nullDate         = $nullDate;
		$this->config           = $config;
		$this->plugins          = $results;
	}
}
