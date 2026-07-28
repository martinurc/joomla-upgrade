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

class EventbookingHelperEmailtags
{
	/**
	 * Get tags related to event information
	 *
	 * @return array
	 */
	public static function getEventTags()
	{
		$tags = [
			'EVENT_ID',
			'EVENT_TITLE',
			'ALIAS',
			'PRICE_TEXT',
			'EVENT_DATE',
			'EVENT_END_DATE',
			'EVENT_DATE_DATE',
			'EVENT_DATE_TIME',
			'EVENT_END_DATE_DATE',
			'EVENT_END_DATE_TIME',
			'CANCEL_BEFORE_DATE',
			'CUT_OFF_DATE',
			'SHORT_DESCRIPTION',
			'DESCRIPTION',
			'EVENT_CAPACITY',
			'TOTAL_REGISTRANTS',
			'AVAILABLE_PLACE',
			'INDIVIDUAL_PRICE',
			'EVENT_LINK',
			'MAIN_CATEGORY_NAME',
			'MAIN_CATEGORY_DESCRIPTION',
			'CATEGORY_NAME',
			'CATEGORY_LINK',
			'LOCATION_NAME',
			'LOCATION_ADDRESS',
			'LOCATION_DESCRIPTION',
			'LOCATION',
			'LOCATION_NAME_ADDRESS',
			'EVENT_CREATOR_NAME',
			'EVENT_CREATOR_USERNAME',
			'EVENT_CREATOR_EMAIL',
			'EVENT_CREATOR_ID',
			'SPEAKERS',
		];

		$config = EventbookingHelper::getConfig();

		if ($config->event_custom_field)
		{
			$tags = array_merge($tags, EventbookingHelper::getEventCustomFields());
		}

		return $tags;
	}

	/**
	 * Get tags related to registration information
	 *
	 * @return array
	 */
	public static function getRegistrationTags()
	{
		$tags = [
			'REGISTRATION_DETAIL',
			'COUPON_CODE',
			'USER_ID',
			'USERNAME',
			'NAME',
			'GROUP_MEMBERS_NAMES',
			'GROUP_MEMBERS',
			'NUMBER_REGISTRANTS',
			'INVOICE_NUMBER',
			'TRANSACTION_ID',
			'REGISTRANT_ID',
			'ID',
			'DATE',
			'PAYMENT_DATE',
			'REGISTER_DATE',
			'REGISTER_DATE_TIME',
			'PAYMENT_METHOD',
			'PAYMENT_METHOD_NAME',
			'REGISTRATION_STATUS',
			'PAYMENT_STATUS',
			'PUBLISHED',
			'SUBSCRIBE_NEWSLETTER',
		];

		// Get list of custom fields
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->order('ordering');
		$db->setQuery($query);

		$tags = array_merge($tags, $db->loadColumn());

		$tags = array_merge($tags, [
			'REGISTRATION_RATE',
			'TAX_RATE',
			'TOTAL_AMOUNT',
			'TOTAL_AMOUNT_MINUS_DISCOUNT',
			'DISCOUNT_AMOUNT',
			'LATE_FEE',
			'PAYMENT_PROCESSING_FEE',
			'AMOUNT',
			'DEPOSIT_AMOUNT',
			'DUE_AMOUNT',
			'CANCEL_REGISTRATION_LINK',
			'DEPOSIT_PAYMENT_LINK',
			'PAYMENT_LINK',
			'DOWNLOAD_CERTIFICATE_LINK',
			'DOWNLOAD_TICKET_LINK',
			'TICKET_TYPE',
			'TICKET_TYPES',
			'TICKET_TYPES_TABLE',
		]);

		return $tags;
	}

	/**
	 *  Get tags which can be used in the email send to group members
	 */
	public static function getGroupMemberEmailTags()
	{
		$tags = [
			'ID',
			'MEMBER_DETAIL',
			'REGISTRATION_DETAIL',
			'PAYMENT_METHOD',
			'PAYMENT_METHOD_NAME',
			'TRANSACTION_ID',
			'DATE',
			'GROUP_BILLING_FIRST_NAME',
			'GROUP_BILLING_LAST_NAME',
			'GROUP_BILLING_EMAIL',
			'EVENT_TITLE',
			'EVENT_DATE',
			'EVENT_END_DATE',
			'SHORT_DESCRIPTION',
			'DESCRIPTION',
			'LOCATION',
			'EVENT_LINK',
			'DOWNLOAD_CERTIFICATE_LINK',
		];

		// Get list of custom fields
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->where('display_in IN (0, 4, 5)')
			->order('ordering');
		$db->setQuery($query);

		$tags = array_merge($tags, $db->loadColumn());

		$config = EventbookingHelper::getConfig();

		if ($config->event_custom_field)
		{
			$tags = array_merge($tags, EventbookingHelper::getEventCustomFields());
		}

		return $tags;
	}

	/**
	 * Get deposit payment tags
	 *
	 * @return array
	 */
	public static function getDepositPaymentTags()
	{
		static $tags;

		if ($tags !== null)
		{
			return $tags;
		}

		$tags = [
			'PAYMENT_METHOD',
			'AMOUNT',
			'PAYMENT_AMOUNT',
			'REGISTRATION_ID',
			'TRANSACTION_ID',
		];

		// Get list of custom fields
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->where('id < 13')
			->order('ordering');
		$db->setQuery($query);
		$tags = array_merge($tags, $db->loadColumn());

		return $tags;
	}

	/**
	 * Get tags which can be used in SMS messages
	 *
	 * @return array
	 */
	public static function getSMSTags()
	{
		static $tags;

		if ($tags !== null)
		{
			return $tags;
		}

		$tags = [
			'event_id',
			'event_title',
			'event_date',
			'event_date_date',
			'event_date_time',
			'event_end_date',
			'event_end_date_date',
			'event_end_date_time',
			'location_name',
			'location_address',
		];

		// Get list of custom fields
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->where('id < 13')
			->order('ordering');
		$db->setQuery($query);
		$tags = array_merge($tags, $db->loadColumn());

		return $tags;
	}
}
