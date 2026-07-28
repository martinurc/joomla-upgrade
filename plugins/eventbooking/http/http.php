<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class plgEventbookingHttp extends CMSPlugin
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
			return [];
		}

		ob_start();
		$this->drawSettingForm($row);

		return [
			'title' => Text::_('EB_HTTP_PLUGIN_SETTINGS'),
			'form'  => ob_get_clean(),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
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

		$params->set('http_enable', $data['http_enable'] ?? 1);
		$params->set('http_url', $data['http_url']);
		$row->params = $params->toString();

		$row->store();
	}

	/**
	 * Subscription Active
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return bool
	 */
	public function onAfterPaymentSuccess($row)
	{
		$event  = EventbookingHelperDatabase::getEvent($row->event_id);
		$params = new Registry($event->params);

		if (!$params->get('http_enable', 0))
		{
			return true;
		}

		if ($params->get('http_url'))
		{
			$url = $params->get('http_url');
		}
		else
		{
			$url = $this->params->get('url');
		}

		if (!$url)
		{
			return true;
		}

		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);


		// Get custom fields data
		$data = EventbookingHelperRegistration::getRegistrantData($row);

		// Add other registrant related data
		$registrantRelatedFields = [
			'id',
			'event_id',
			'user_id',
			'group_id',
			'number_registrants',
			'total_amount',
			'discount_amount',
			'tax_amount',
			'payment_processing_fee',
			'amount',
			'register_date',
			'payment_date',
			'payment_method',
			'transaction_id',
			'published',
			'language',
			'ticket_number',
			'invoice_number',
			'tax_rate',
			'formatted_invoice_number',
			'coupon_id',
		];

		foreach ($registrantRelatedFields as $field)
		{
			$data[$field] = $row->{$field};
		}

		$data['ticket_code'] = $row->ticket_qrcode ?: $row->ticket_code;

		$amountFields = [
			'total_amount',
			'discount_amount',
			'tax_amount',
			'payment_processing_fee',
			'amount',
		];

		foreach ($amountFields as $amountField)
		{
			$data[$amountField . '_with_current_formatted'] = EventbookingHelper::formatCurrency(
				$row->{$amountField},
				$config,
				$event->currency_symbol
			);

			$data[$amountField . '_formatted'] = EventbookingHelper::formatAmount($row->{$amountField}, $config);
		}

		if ($row->invoice_number > 0)
		{
			$data['invoice_number_formatted'] = EventbookingHelper::callOverridableHelperMethod(
				'Helper',
				'formatInvoiceNumber',
				[$row->invoice_number, $config, $row]
			);
		}
		else
		{
			$data['invoice_number_formatted'] = '';
		}

		// Format data
		if ((int) $row->payment_date)
		{
			$data['payment_date'] = HTMLHelper::_('date', $row->payment_date, $config->date_format);
		}
		else
		{
			$data['payment_date'] = '';
		}

		if ((int) $row->register_date)
		{
			$data['register_date_formatted'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
		}
		else
		{
			$data['register_date_formatted'] = '';
		}

		if ((int) $row->registration_cancel_date)
		{
			$data['registration_cancel_date_formatted'] = HTMLHelper::_('date', $row->registration_cancel_date, $config->date_format . ' H:i');
		}
		else
		{
			$data['registration_cancel_date_formatted'] = '';
		}

		if ($row->ticket_number > 0)
		{
			$data['ticket_number_formatted'] = EventbookingHelperTicket::formatTicketNumber($event->ticket_prefix, $row->ticket_number, $config);
		}
		else
		{
			$data['ticket_number_formatted'] = '';
		}

		$method = EventbookingHelperPayments::loadPaymentMethod($row->payment_method);

		if ($method)
		{
			$data['payment_method']      = Text::_($method->title);
			$data['payment_method_name'] = $row->payment_method;
		}
		else
		{
			$data['payment_method']      = '';
			$data['payment_method_name'] = '';
		}

		// Other calculated fields
		$data['country_code'] = EventbookingHelper::getCountryCode($row->country);

		if ($row->user_id)
		{
			$query->select('username')
				->from('#__users')
				->where('id = ' . (int) $row->user_id);
			$db->setQuery($query);
			$data['username'] = $db->loadResult();
		}
		else
		{
			$data['username'] = '';
		}

		if ($row->coupon_id)
		{
			$query->clear()
				->select($db->quoteName('code'))
				->from('#__eb_coupons')
				->where('id = ' . $row->coupon_id);
			$db->setQuery($query);
			$data['coupon_code'] = $db->loadResult();
		}
		else
		{
			$data['coupon_code'] = '';
		}

		// Get event related information
		$data = array_merge($data, $this->getEventData($event, $config));

		// OK, we will now make a post request with json data
		try
		{
			$http    = HttpFactory::getHttp();
			$headers = $this->getHeaders();
			$data    = $this->getMappedData($data);

			if ($additionalData = $this->getAdditionalData())
			{
				$data = array_merge($data, $additionalData);
			}

			if ($this->params->get('content_type', 'application/json') === 'application/json')
			{
				$data = json_encode($data);
			}

			$http->post($url, $data, $headers);
		}
		catch (Exception $e)
		{
		}
	}

	/**
	 * @param $event
	 * @param $config
	 *
	 * @return array
	 */
	private function getEventData($event, $config)
	{
		$data       = [];
		$timeFormat = $config->event_time_format ?: 'g:i a';

		$data['short_description']      = $event->short_description;
		$data['description']            = $event->description;
		$data['event_id']               = $event->id;
		$data['event_title']            = $event->title;
		$data['registered_event_title'] = $event->title;
		$data['alias']                  = $event->alias;
		$data['price_text']             = $event->price_text;

		if ($event->event_date == EB_TBC_DATE)
		{
			$data['event_date']         = Text::_('EB_TBC');
			$data['event_date_date']    = Text::_('EB_TBC');
			$data['event_date_time']    = Text::_('EB_TBC');
			$data['event_day']          = Text::_('EB_TBC');
			$data['event_month']        = Text::_('EB_TBC');
			$data['event_year']         = Text::_('EB_TBC');
			$data['event_month_short']  = Text::_('EB_TBC');
			$data['event_month_number'] = Text::_('EB_TBC');
		}
		else
		{
			if (strpos($event->event_date, '00:00:00') !== false)
			{
				$data['event_date'] = HTMLHelper::_('date', $event->event_date, $config->date_format, null);
			}
			else
			{
				$data['event_date'] = HTMLHelper::_('date', $event->event_date, $config->event_date_format, null);
			}

			$data['event_date_date']    = HTMLHelper::_('date', $event->event_date, $config->date_format, null);
			$data['event_date_time']    = HTMLHelper::_('date', $event->event_date, $timeFormat, null);
			$data['event_day']          = HTMLHelper::_('date', $event->event_date, 'd', null);
			$data['event_month']        = HTMLHelper::_('date', $event->event_date, 'F', null);
			$data['event_month_short']  = HTMLHelper::_('date', $event->event_date, 'M', null);
			$data['event_month_number'] = HTMLHelper::_('date', $event->event_date, 'm', null);
			$data['event_year']         = HTMLHelper::_('date', $event->event_date, 'Y', null);
		}

		if ((int) $event->event_end_date)
		{
			if (strpos($event->event_end_date, '00:00:00') !== false)
			{
				$data['event_end_date'] = HTMLHelper::_('date', $event->event_end_date, $config->date_format, null);
			}
			else
			{
				$data['event_end_date'] = HTMLHelper::_('date', $event->event_end_date, $config->event_date_format, null);
			}

			$data['event_end_date_date'] = HTMLHelper::_('date', $event->event_end_date, $config->date_format, null);
			$data['event_end_date_time'] = HTMLHelper::_('date', $event->event_end_date, $timeFormat, null);
		}
		else
		{
			$data['event_end_date']      = '';
			$data['event_end_date_date'] = '';
			$data['event_end_date_time'] = '';
		}

		$data['enable_cancel_registration'] = (int) $event->enable_cancel_registration;

		if ((int) $event->cancel_before_date)
		{
			$data['cancel_before_date'] = HTMLHelper::_('date', $event->cancel_before_date, $config->event_date_format, null);
		}
		else
		{
			$data['cancel_before_date'] = HTMLHelper::_('date', $event->cancel_before_date, $config->event_date_format, null);
		}

		if ((int) $event->cut_off_date)
		{
			$data['cut_off_date'] = HTMLHelper::_('date', $event->cut_off_date, $config->event_date_format, null);
		}
		else
		{
			$data['cut_off_date'] = HTMLHelper::_('date', $event->cut_off_date, $config->event_date_format, null);
		}

		$data['event_capacity'] = $event->event_capacity;

		if (property_exists($event, 'total_registrants'))
		{
			$data['total_registrants'] = $event->total_registrants;

			if ($event->event_capacity > 0)
			{
				$data['available_place'] = $event->event_capacity - $event->total_registrants;
			}
			else
			{
				$data['available_place'] = '';
			}
		}

		$data['WAITING_LIST_QUANTITY'] = EventbookingHelperRegistration::countNumberWaitingList($event);

		$data['individual_price'] = EventbookingHelper::formatCurrency($event->individual_price, $config, $event->currency_symbol);

		if ($event->location_id > 0)
		{
			$rowLocation = EventbookingHelperDatabase::getLocation($event->location_id);

			$locationInformation = [];

			if ($rowLocation->address)
			{
				$locationInformation[] = $rowLocation->address;
			}

			if (count($locationInformation))
			{
				$locationName = $rowLocation->name . ' (' . implode(', ', $locationInformation) . ')';
			}
			else
			{
				$locationName = $rowLocation->name;
			}

			$data['location_name_address'] = $locationName;
			$data['location_name']         = $rowLocation->name;
			$data['location_city']         = $rowLocation->city;
			$data['location_state']        = $rowLocation->state;
			$data['location_address']      = $rowLocation->address;
			$data['location_description']  = $rowLocation->description;

			if ($rowLocation->image)
			{
				$data['location_image'] = EventbookingHelperHtml::getCleanImagePath($rowLocation->image);
			}
			else
			{
				$data['location_image'] = '';
			}
		}
		else
		{
			$data['location_name_address'] = '';
			$data['location_name']         = '';
			$data['location_city']         = '';
			$data['location_state']        = '';
			$data['location_address']      = '';
			$data['location_description']  = '';
			$data['location_image']        = '';
		}

		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			EventbookingHelperData::prepareCustomFieldsData([$event]);

			foreach ($event->paramData as $customFieldName => $param)
			{
				$data[strtoupper($customFieldName)] = $param['value'];
			}
		}

		// Speakers
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('a.*')
			->from('#__eb_speakers AS a')
			->innerJoin('#__eb_event_speakers AS b ON a.id = b.speaker_id')
			->where('b.event_id = ' . ($event->parent_id ?: $event->id))
			->order('b.id');
		$db->setQuery($query);
		$rowSpeakers = $db->loadObjectList();

		$speakerNames = [];

		foreach ($rowSpeakers as $rowSpeaker)
		{
			$data['speaker_' . $rowSpeaker->id] = $rowSpeaker->name;
			$speakerNames[]                     = $rowSpeaker->name;
		}

		$data['speakers'] = implode(', ', $speakerNames);

		$query->clear()
			->select('a.id, a.name, a.description')
			->from('#__eb_categories AS a')
			->innerJoin('#__eb_event_categories AS b ON a.id = b.category_id')
			->where('b.event_id = ' . $event->id)
			->order('b.id');

		$db->setQuery($query);
		$categories    = $db->loadObjectList();
		$categoryNames = [];

		foreach ($categories as $category)
		{
			$categoryNames[] = $category->name;

			if ($category->id == $event->main_category_id)
			{
				$data['main_category_name']        = $category->name;
				$data['main_category_description'] = $category->description;
			}
		}

		$data['main_category_id'] = $event->main_category_id;
		$data['category_name']    = implode(', ', $categoryNames);

		if ($event->created_by > 0)
		{
			$creator = Factory::getUser($event->created_by);
		}

		if (!empty($creator->id))
		{
			$data['event_creator_name']     = $creator->name;
			$data['event_creator_username'] = $creator->username;
			$data['event_creator_email']    = $creator->email;
			$data['event_creator_id']       = $creator->id;
		}
		else
		{
			$data['event_creator_name']     = '';
			$data['event_creator_username'] = '';
			$data['event_creator_email']    = '';
			$data['event_creator_id']       = '';
		}

		return $data;
	}

	/**
	 * Get additional data defined in plugin parameters
	 *
	 * @return array
	 */
	private function getAdditionalData(): array
	{
		$data = [];

		foreach ($this->params->get('additional_data', []) as $field)
		{
			if (is_string($field->additional_data_field_name)
				&& is_string($field->additional_data_field_value)
				&& strlen(trim($field->additional_data_field_name)) > 0
				&& strlen(trim($field->additional_data_field_value)) > 0)
			{
				$data[$field->additional_data_field_name] = $field->additional_data_field_value;
			}
		}

		return $data;
	}

	/**
	 * Get headers to send in the request
	 *
	 * @return array
	 */
	private function getHeaders(): array
	{
		$headers = [];

		$headers['Content-Type'] = $this->params->get('content_type', 'application/json');

		foreach ($this->params->get('headers', []) as $header)
		{
			if (is_string($header->name) && is_string($header->value) && strlen(trim($header->name)) > 0 && strlen(trim($header->value)) > 0)
			{
				$headers[$header->name] = $header->value;
			}
		}

		return $headers;
	}

	/**
	 * @param   array  $data
	 *
	 * @return array
	 */
	private function getMappedData(array $data): array
	{
		foreach ($this->params->get('fields_mapping', []) as $fieldMapping)
		{
			if (is_string($fieldMapping->original_field_name)
				&& is_string($fieldMapping->new_field_name)
				&& strlen(trim($fieldMapping->original_field_name)) > 0
				&& strlen(trim($fieldMapping->new_field_name)) > 0)
			{
				if (array_key_exists(trim($fieldMapping->original_field_name), $data))
				{
					$data[trim($fieldMapping->new_field_name)] = $data[trim($fieldMapping->original_field_name)];
					unset($data[trim($fieldMapping->original_field_name)]);
				}
			}
		}

		return $data;
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
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

		return true;
	}
}