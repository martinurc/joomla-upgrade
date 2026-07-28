<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use CB\Database\Table\UserTable;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Users\Site\Model\RegistrationModel;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class EventbookingHelperRegistration
{
	/**
	 * Calculate discount rate which the current user will receive
	 *
	 * @param $discount
	 * @param $groupIds
	 *
	 * @return float
	 * @deprecated Use EventbookingHelperRegistration::calculateMemberDiscountForUser instead
	 */
	public static function calculateMemberDiscount($discount, $groupIds)
	{
		return self::calculateMemberDiscountForUser($discount, $groupIds);
	}

	/**
	 * Calculate discount rate which the current user will receive
	 *
	 * @param   string|float  $discount
	 * @param   string        $groupIds
	 * @param   User          $user
	 *
	 * @return float
	 */
	public static function calculateMemberDiscountForUser($discount, $groupIds, ?User $user = null)
	{
		$user = $user ?? Factory::getUser();

		if (!$discount)
		{
			return 0;
		}

		if (!$groupIds)
		{
			return $discount;
		}

		$userGroupIds = explode(',', $groupIds);
		$userGroupIds = ArrayHelper::toInteger($userGroupIds);
		$groups       = $user->groups;

		if (count(array_intersect($groups, $userGroupIds)))
		{
			//Calculate discount amount
			if (strpos($discount, ',') !== false)
			{
				$discountRates = explode(',', $discount);
				$maxDiscount   = 0;

				foreach ($groups as $group)
				{
					$index = array_search($group, $userGroupIds);

					if ($index !== false && isset($discountRates[$index]))
					{
						$maxDiscount = max($maxDiscount, $discountRates[$index]);
					}
				}

				return $maxDiscount;
			}

			return $discount;
		}

		return 0;
	}

	/**
	 * Method to check to see if the event has coupon code available
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	public static function isCouponAvailableForEvent($event, $registrationType = 0)
	{
		$user       = Factory::getUser();
		$db         = Factory::getDbo();
		$couponCode = '';

		$query = EventbookingHelper::callOverridableHelperMethod('Registration', 'getCouponQuery', [$couponCode, $event, $user]);

		switch ($registrationType)
		{
			case 0:
				// Individual registration
				$query->where('enable_for IN (0, 1)');
				break;
			case 1:
				// Group Registration
				$query->where('enable_for IN (0, 2)');
				break;
		}

		$db->setQuery($query);

		return (int) $db->loadResult() > 0;
	}

	/**
	 * Check to see if joining waiting list is still available for the event
	 *
	 * @param   EventbookingTableEvent  $event
	 */
	public static function countNumberWaitingList($event)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('IFNULL(SUM(number_registrants), 0)')
			->from('#__eb_registrants')
			->where('event_id = ' . (int) $event->id)
			->where('group_id = 0')
			->where('published = 3');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Count awaiting payment registrations
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return int
	 */
	public static function countAwaitingPaymentRegistrations($event)
	{
		$config        = EventbookingHelper::getConfig();
		$numberMinutes = (int) $config->count_awaiting_payment_registration_times;

		if ($numberMinutes == 0)
		{
			return 0;
		}

		$db          = Factory::getDbo();
		$currentDate = $db->quote(Factory::getDate()->toSql());
		$query       = $db->getQuery(true)
			->select('IFNULL(SUM(number_registrants), 0) AS total_registrants')
			->from('#__eb_registrants')
			->where('event_id = ' . (int) $event->id)
			->where('group_id = 0')
			->where('published = 0')
			->where("TIMESTAMPDIFF(MINUTE, register_date, $currentDate) <= $numberMinutes")
			->where('payment_method NOT LIKE "os_offline%"');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Check to see if we should show invite friend button for certain event
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	public static function canInviteFriend($event)
	{
		// If cut off date is entered, we will check registration based on cut of date, not event date
		if ((int) $event->cut_off_date)
		{
			if ($event->cut_off_minutes > 0)
			{
				return false;
			}
		}
		elseif (isset($event->event_start_minutes))
		{
			if ($event->event_start_minutes > 0)
			{
				return false;
			}
		}
		else
		{
			if ($event->number_event_dates < 0)
			{
				return false;
			}
		}

		if ($event->event_capacity && ($event->total_registrants >= $event->event_capacity))
		{
			return false;
		}

		return true;
	}

	/**
	 * Pass field-payment method data to JS
	 *
	 * @param   array  $rowFields
	 *
	 * @return void
	 */
	public static function passFieldPaymentMethodDataToJS($rowFields)
	{
		HTMLHelper::_('behavior.core');

		$allFields           = [];
		$paymentMethodFields = [];

		foreach ($rowFields as $rowField)
		{
			if (!$rowField->payment_method)
			{
				continue;
			}

			$allFields[]                                      = $rowField->name;
			$paymentMethodFields[$rowField->payment_method][] = $rowField->name;
		}

		$document = Factory::getApplication()->getDocument();
		$document->addScriptOptions('all_payment_method_fields', $allFields);

		foreach ($paymentMethodFields as $paymentMethod => $fields)
		{
			$document->addScriptOptions($paymentMethod . '_fields', $fields);
		}
	}

	/**
	 * Pass field-payment method data to JS
	 *
	 * @param   array  $rowFields
	 *
	 * @return void
	 */
	public static function passFieldTicketTypesDataToJS($rowFields)
	{
		HTMLHelper::_('behavior.core');

		$ticketTypesFields = [];

		foreach ($rowFields as $rowField)
		{
			if (!$rowField->depend_on_ticket_type_ids)
			{
				continue;
			}

			$ticketTypeIds = array_filter(ArrayHelper::toInteger(explode(',', $rowField->depend_on_ticket_type_ids)));

			if (count($ticketTypeIds) === 0)
			{
				continue;
			}

			foreach ($ticketTypeIds as $ticketTypeId)
			{
				$ticketTypesFields[$ticketTypeId][] = $rowField->name;
			}
		}

		$document = Factory::getApplication()->getDocument();

		foreach ($ticketTypesFields as $ticketTypeId => $fields)
		{
			$document->addScriptOptions('ticket_type_' . $ticketTypeId . '_fields', $fields);
		}
	}

	/**
	 * Check to see whether this event still accept registration
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	public static function acceptRegistration($event)
	{
		$user         = Factory::getUser();
		$accessLevels = $user->getAuthorisedViewLevels();

		if (empty($event) || !$event->published)
		{
			$event->cannot_register_reason = 'invalid_event_or_event';

			return false;
		}

		if ($event->published == 2)
		{
			$event->cannot_register_reason = 'event_cancelled';

			return false;
		}

		if (!in_array($event->access, $accessLevels))
		{
			$event->cannot_register_reason = 'user_does_not_have_access';

			return false;
		}

		if ($event->registration_type == 3)
		{
			$event->cannot_register_reason = 'registration_is_disabled';

			return false;
		}

		if (!in_array($event->registration_access, $accessLevels))
		{
			$event->cannot_register_reason = 'user_does_not_have_registration_access';

			return false;
		}

		if ($event->registration_start_minutes < 0)
		{
			$event->cannot_register_reason = 'registration_is_not_started';

			return false;
		}

		// If cut off date is entered, we will check registration based on cut of date, not event date
		if ((int) $event->cut_off_date)
		{
			if ($event->cut_off_minutes > 0)
			{
				$event->cannot_register_reason = 'cut_off_date_pass';

				return false;
			}
		}
		elseif (isset($event->event_start_minutes))
		{
			if ($event->event_start_minutes > 0)
			{
				$event->cannot_register_reason = 'event_already_started';

				return false;
			}
		}
		else
		{
			if ($event->number_event_dates < 0)
			{
				$event->cannot_register_reason = 'event_already_started';

				return false;
			}
		}

		$eventIsFull                      = false;
		$numberAwaitingPaymentRegistrants = static::countAwaitingPaymentRegistrations($event);

		if ($event->event_capacity && (($event->total_registrants + $numberAwaitingPaymentRegistrants) >= $event->event_capacity))
		{
			$eventIsFull = true;
		}

		$config = EventbookingHelper::getConfig();

		//Check to see whether the current user has registered for the event
		if ($event->prevent_duplicate_registration === '')
		{
			$preventDuplicateRegistration = $config->prevent_duplicate_registration;
		}
		else
		{
			$preventDuplicateRegistration = $event->prevent_duplicate_registration;
		}

		if ($preventDuplicateRegistration && $user->id)
		{
			if (static::isUserJoinedEvent($event->id))
			{
				$event->cannot_register_reason = 'duplicate_registration';

				return false;
			}
			elseif (static::isUserJoinedWaitingList($event->id) && $eventIsFull)
			{
				$event->cannot_register_reason = 'duplicate_registration_waiting_list';

				return false;
			}
		}

		if ($eventIsFull)
		{
			$event->cannot_register_reason = 'event_is_full';

			return false;
		}

		PluginHelper::importPlugin('eventbooking');
		$results = Factory::getApplication()->triggerEvent('onEBCheckAcceptRegistration', [$event]);

		if (in_array(false, $results, true))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the reason registration is not enabled for the current user
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return string
	 */
	public static function getRegistrationErrorMessage($event)
	{
		$db   = Factory::getDbo();
		$user = Factory::getUser();

		$accessLevels = $user->getAuthorisedViewLevels();
		if (empty($event)
			|| !$event->published
			|| !in_array($event->access, $accessLevels)
			|| !in_array($event->registration_access, $accessLevels)
		)
		{
			return Text::_('EB_REGISTRATION_NOT_AVAILABLE_FOR_ACCOUNT');
		}

		if ($event->registration_type == 3)
		{
			return Text::_('EB_REGISTRATION_IS_DISABLED');
		}

		if ($event->registration_start_minutes < 0)
		{
			return Text::_('EB_REGISTRATION_IS_NOT_STARTED_YET');
		}

		// If cut off date is entered, we will check registration based on cut of date, not event date
		if ((int) $event->cut_off_date)
		{
			if ($event->cut_off_minutes > 0)
			{
				return Text::_('EB_NO_LONGER_ACCEPT_REGISTRATION');
			}
		}
		elseif (isset($event->event_start_minutes))
		{
			if ($event->event_start_minutes > 0)
			{
				return Text::_('EB_NO_LONGER_ACCEPT_REGISTRATION');
			}
		}
		else
		{
			if ($event->number_event_dates < 0)
			{
				return Text::_('EB_NO_LONGER_ACCEPT_REGISTRATION');
			}
		}

		if ($event->event_capacity && ($event->total_registrants >= $event->event_capacity))
		{
			return Text::_('EB_EVENT_IS_FULL');
		}

		$config = EventbookingHelper::getConfig();

		//Check to see whether the current user has registered for the event
		if ($event->prevent_duplicate_registration === '')
		{
			$preventDuplicateRegistration = $config->prevent_duplicate_registration;
		}
		else
		{
			$preventDuplicateRegistration = $event->prevent_duplicate_registration;
		}

		if ($preventDuplicateRegistration && $user->id && static::getRegistrantId($event->id) != false)
		{
			return Text::_('EB_YOU_REGISTERED_ALREADY');
		}

		return '';
	}

	/**
	 * Get all custom fields for an event
	 *
	 * @param   int  $eventId
	 *
	 * @return array
	 */
	public static function getAllEventFields($eventId)
	{
		$db = Factory::getDbo();

		if ($eventId)
		{
			$query = EventbookingHelperRegistration::getBaseEventFieldsQuery($eventId);
		}
		else
		{
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_fields')
				->where('published = 1')
				->order('ordering');
		}

		if (Factory::getApplication()->isClient('site') && $fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['title'], $fieldSuffix);
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get all custom fields for an event
	 *
	 * @param   int  $eventId
	 *
	 * @return array
	 */
	public static function getAllPublicEventFields($eventId)
	{
		$db    = Factory::getDbo();
		$query = EventbookingHelperRegistration::getBaseEventFieldsQuery($eventId)
			->clear('select')
			->select('id, name, title, is_core')
			->where('show_on_public_registrants_list = 1')
			->where('`access` IN (' . implode(',', Factory::getUser()->getAuthorisedViewLevels()) . ')');

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['title'], $fieldSuffix);
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get name of published core fields in the system
	 *
	 * @return array
	 */
	public static function getPublishedCoreFields()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->where('is_core = 1');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Get the form fields to display in deposit payment form
	 *
	 * @return array
	 */
	public static function getDepositPaymentFormFields()
	{
		$user  = Factory::getUser();
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_fields')
			->where('published=1')
			->where('id < 13')
			->where(' `access` IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
			->order('ordering');

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			EventbookingHelperDatabase::getMultilingualFields(
				$query,
				['title', 'description', 'values', 'default_values', 'depend_on_options'],
				$fieldSuffix
			);
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Method
	 *
	 * @param $eventId
	 *
	 * @return JDatabaseQuery|string
	 */
	public static function getBaseEventFieldsQuery($eventId)
	{
		$config = EventbookingHelper::getConfig();

		if ($config->get('use_custom_fields_from_parent_event'))
		{
			$event = EventbookingHelperDatabase::getEvent($eventId);

			if ($event->parent_id > 0)
			{
				$eventId = $event->parent_id;
			}
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_fields')
			->where('published = 1')
			->order('ordering');

		$filterByCategories = in_array($config->get('custom_field_assignment', 0), [0, 1]);
		$filterByEvents     = in_array($config->get('custom_field_assignment', 0), [0, 2]);

		$whereOrs = [];

		if ($filterByCategories)
		{
			$whereOrs[]  = 'category_id = - 1';
			$categoryIds = EventbookingHelperDatabase::getEventCategories([$eventId]);

			if (count($categoryIds) > 0)
			{
				$categoryIds = implode(',', $categoryIds);
				$whereOrs[]  = 'id IN (SELECT field_id FROM #__eb_field_categories AS fc WHERE fc.category_id IN (' . $categoryIds . '))';
			}
		}

		if ($filterByEvents)
		{
			$negEventId = -1 * $eventId;
			$whereOrs[] = 'event_id = -1';
			$whereOrs[] = 'id IN (SELECT field_id FROM #__eb_field_events AS fv WHERE fv.event_id = ' . $eventId . ' OR fv.event_id < 0)';

			$query->where('id NOT IN (SELECT field_id FROM #__eb_field_events WHERE event_id = ' . $negEventId . ')');
		}

		$query->where('(' . implode(' OR ', $whereOrs) . ')');

		return $query;
	}

	/**
	 * Get the form fields to display in registration form
	 *
	 * @param   int     $eventId             (ID of the event or ID of the registration record in case the system use shopping cart)
	 * @param   int     $registrationType
	 * @param   string  $activeLanguage
	 * @param   int     $userId
	 * @param   int     $typeOfRegistration  The type of registration 1: Standard Registraiton, 2: Waiting List
	 *
	 * @return array
	 */
	public static function getFormFields($eventId = 0, $registrationType = 0, $activeLanguage = null, $userId = null, $typeOfRegistration = 1)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideRegistration', 'getFormFields'))
		{
			return EventbookingHelperOverrideRegistration::getFormFields($eventId, $registrationType, $activeLanguage, $userId);
		}

		static $cache;

		$cacheKey = md5(serialize(func_get_args()));

		if (empty($cache[$cacheKey]))
		{
			$app = Factory::getApplication();

			if ($userId != -1)
			{
				$user = Factory::getUser($userId);
			}
			else
			{
				$user = Factory::getUser();
			}

			// Fallback to current user to prevent fatal error while loading invalid user
			if ($user->id === null)
			{
				$user = Factory::getUser();
			}

			$db          = Factory::getDbo();
			$query       = $db->getQuery(true);
			$config      = EventbookingHelper::getConfig();
			$fieldSuffix = EventbookingHelper::getFieldSuffix($activeLanguage);

			$query->select('*')
				->from('#__eb_fields')
				->where('published = 1');

			if ($userId != -1
				&& (!$user->authorise('core.admin', 'com_eventbooking') || $app->isClient('site')))
			{
				$query->where(' `access` IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
			}

			if ($fieldSuffix)
			{
				EventbookingHelperDatabase::getMultilingualFields(
					$query,
					['title', 'place_holder', 'description', 'values', 'default_values', 'depend_on_options'],
					$fieldSuffix
				);
			}

			switch ($registrationType)
			{
				case 0:
					$query->where('display_in IN (0, 1, 3, 5)'); // Individual Registration Form
					break;
				case 1:
					$query->where('display_in IN (0, 2, 3)'); // Group Registration Billing Form
					break;
				case 2: // Group Member Form
					$query->where('display_in IN (0, 4, 5)');
					break;
				case 4: // Shopping cart
					$query->where('display_in IN (0, 1, 2, 3)');
					break;
			}

			if ($typeOfRegistration > 0)
			{
				$query->where("show_on_registration_type IN (0, $typeOfRegistration)");
			}

			$filterByCategories = in_array($config->get('custom_field_assignment', 0), [0, 1]);
			$filterByEvents     = in_array($config->get('custom_field_assignment', 0), [0, 2]);

			$whereOrs = [];

			if ($filterByCategories)
			{
				$whereOrs[] = 'category_id = - 1';
			}

			if ($filterByEvents)
			{
				$whereOrs[] = 'event_id = -1';
			}

			if ($registrationType == 4)
			{
				$cart  = new EventbookingHelperCart();
				$items = $cart->getItems();

				//In this case, we have ID of registration record, so, get list of events from that registration
				if (!count($items))
				{
					$subQuery = $db->getQuery(true)
						->select('event_id')
						->from('#__eb_registrants')
						->where('(id = ' . $eventId . ' OR cart_id = ' . $eventId . ')');
					$db->setQuery($subQuery);
					$items = $db->loadColumn();
				}

				if ($filterByCategories)
				{
					$categoryIds = EventbookingHelperDatabase::getEventCategories($items);

					if (count($categoryIds) > 0)
					{
						$categoryIds = implode(',', $categoryIds);
						$whereOrs[]  = 'id IN (SELECT field_id FROM #__eb_field_categories AS fc WHERE fc.category_id IN (' . $categoryIds . '))';
					}
				}

				if ($filterByEvents)
				{
					$whereOrs[] = 'id IN (SELECT field_id FROM #__eb_field_events AS fv WHERE fv.event_id IN (' . implode(',', $items) . '))';
					/*$negEventIds = array_map(function ($eventId) {
						return -1 * $eventId;
					}, $items);

					$query->where('id NOT IN (SELECT field_id FROM #__eb_field_events WHERE event_id IN (' . implode(',', $negEventIds) . '))');
					*/
				}

				$query->where('(' . implode(' OR ', $whereOrs) . ')');
			}
			else
			{
				// Get custom fields from parent event for children events
				if ($config->get('use_custom_fields_from_parent_event'))
				{
					$event = EventbookingHelperDatabase::getEvent($eventId);

					if ($event->parent_id > 0)
					{
						$eventId = $event->parent_id;
					}
				}

				if ($filterByCategories)
				{
					$categoryIds = EventbookingHelperDatabase::getEventCategories([$eventId]);

					if (count($categoryIds) > 0)
					{
						$categoryIds = implode(',', $categoryIds);
						$whereOrs[]  = 'id IN (SELECT field_id FROM #__eb_field_categories AS fc WHERE fc.category_id IN (' . $categoryIds . '))';
					}
				}

				if ($filterByEvents)
				{
					$negEventId = -1 * $eventId;

					if ($filterByCategories)
					{
						$whereOrs[] = 'id IN (SELECT field_id FROM #__eb_field_events AS fv WHERE fv.event_id = ' . $eventId . ')';
					}
					else
					{
						$whereOrs[] = 'id IN (SELECT field_id FROM #__eb_field_events AS fv WHERE fv.event_id = ' . $eventId . ' OR fv.event_id < 0)';
					}

					$query->where('id NOT IN (SELECT field_id FROM #__eb_field_events WHERE event_id = ' . $negEventId . ')');
				}

				$query->where('(' . implode(' OR ', $whereOrs) . ')');
			}

			$query->order('ordering');
			$db->setQuery($query);

			$cache[$cacheKey] = $db->loadObjectList();
		}

		return $cache[$cacheKey];
	}

	/**
	 * Method to get form fields for current group member
	 *
	 * @param   array  $rowFields
	 * @param   int    $memberNumber
	 *
	 * @return array
	 */
	public static function getGroupMemberFields($rowFields, $memberNumber)
	{
		$memberFields = array_map(function ($field) {
			return clone $field;
		}, $rowFields);

		if ($memberNumber == 1)
		{
			foreach ($memberFields as $i => $field)
			{
				if ($field->hide_for_first_group_member)
				{
					unset($memberFields[$i]);

					continue;
				}

				if ($field->not_required_for_first_group_member && $field->required)
				{
					$field->required         = 0;
					$field->validation_rules = RADFormField::getOptionalValudationRules($field->validation_rules);
				}
			}
		}
		else
		{
			foreach ($memberFields as $i => $field)
			{
				if ($field->only_show_for_first_member)
				{
					unset($memberFields[$i]);

					continue;
				}

				if ($field->only_require_for_first_member && $field->required)
				{
					$field->required         = 0;
					$field->validation_rules = RADFormField::getOptionalValudationRules($field->validation_rules);
				}
			}
		}

		return array_values($memberFields);
	}

	/**
	 *  Get registration replace tags
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   EventbookingTableEvent       $rowEvent
	 * @param   int                          $userId
	 * @param   bool                         $enableShoppingCart
	 * @param   bool                         $loadCss
	 *
	 * @return array
	 */
	public static function getRegistrationReplaces($row, $rowEvent = null, $userId = 0, $enableShoppingCart = false, $loadCss = true)
	{
		static $cache = [];

		$config = EventbookingHelper::getConfig();

		if (!$userId)
		{
			$userId = (int) $row->user_id;
		}

		if ($enableShoppingCart)
		{
			$cacheKey = $row->id . '_' . $userId . '_true';
		}
		else
		{
			$cacheKey = $row->id . '_' . $userId . '_false';
		}

		if (isset($cache[$cacheKey]))
		{
			return $cache[$cacheKey];
		}

		if ($rowEvent === null)
		{
			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
			$rowEvent    = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		}

		if ($row->published == 3)
		{
			$typeOfRegistration = 2;
		}
		else
		{
			$typeOfRegistration = 1;
		}

		if ($enableShoppingCart)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language, $userId, $typeOfRegistration);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language, $userId, $typeOfRegistration);
		}
		elseif ($row->group_id > 0)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 2, $row->language, $userId, $typeOfRegistration);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language, $userId, $typeOfRegistration);
		}

		// Backup $config->multiple_booking value
		$multipleBooking = $config->multiple_booking;

		// Fake multiple_booking config option in case we don't need to care about shopping cart on build tags
		$config->multiple_booking = $enableShoppingCart;

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'buildTags',
			[$row, $form, $rowEvent, $config, $loadCss],
			'Helper'
		);

		$cache[$cacheKey] = $replaces;

		// Restore $config->multiple_booking value
		$config->multiple_booking = $multipleBooking;

		return $cache[$cacheKey];
	}

	/**
	 * Get registration rate for group registration
	 *
	 * @param   int  $eventId
	 * @param   int  $numberRegistrants
	 *
	 * @return mixed
	 */
	public static function getRegistrationRate($eventId, $numberRegistrants)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('price')
			->from('#__eb_event_group_prices')
			->where('event_id = ' . (int) $eventId)
			->where('registrant_number <= ' . (int) $numberRegistrants)
			->order('registrant_number DESC');
		$db->setQuery($query, 0, 1);
		$rate = $db->loadResult();

		if (!$rate)
		{
			$query->clear()
				->select('individual_price')
				->from('#__eb_events')
				->where('id = ' . $eventId);
			$db->setQuery($query);
			$rate = $db->loadResult();
		}

		return $rate;
	}

	/**
	 * Get type of registration
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return int
	 */
	public static function getTypeOfRegistration($event)
	{
		if ($event->event_capacity > 0 && ($event->event_capacity <= $event->total_registrants))
		{
			return 2;
		}

		return 1;
	}

	/**
	 * Method to calculate payment processing fee
	 *
	 * @param   string   $paymentMethod
	 * @param   float    $amount
	 * @param   boolean  $includeFeeAmount
	 *
	 * @return float
	 */
	public static function calculatePaymentProcessingFee($paymentMethod, $amount, $includeFeeAmount = true)
	{
		if ($paymentMethod !== null && strpos($paymentMethod, 'os_paypal') !== false)
		{
			return static::calculatePayPalPaymentProcessingFee($paymentMethod, $amount, $includeFeeAmount);
		}

		if ($paymentMethod)
		{
			$method            = EventbookingHelperPayments::loadPaymentMethod($paymentMethod);
			$params            = new Registry($method->params);
			$paymentFeeAmount  = $includeFeeAmount ? (float) $params->get('payment_fee_amount') : 0;
			$paymentFeePercent = (float) $params->get('payment_fee_percent');

			if ($paymentFeeAmount != 0 || $paymentFeePercent != 0)
			{
				return round($paymentFeeAmount + $amount * $paymentFeePercent / 100, 2);
			}
		}

		return 0.00;
	}

	/**
	 * Method to calculate PayPal payment processing fee
	 *
	 * @param   string   $paymentMethod
	 * @param   float    $amount
	 * @param   boolean  $includeFeeAmount
	 *
	 * @return float
	 */
	public static function calculatePayPalPaymentProcessingFee($paymentMethod, $amount, $includeFeeAmount = true)
	{
		$method            = EventbookingHelperPayments::loadPaymentMethod($paymentMethod);
		$params            = new Registry($method->params);
		$paymentFeeAmount  = $includeFeeAmount ? (float) $params->get('payment_fee_amount') : 0;
		$paymentFeePercent = (float) $params->get('payment_fee_percent');

		if ($paymentFeeAmount != 0 || $paymentFeePercent != 0)
		{
			$paymentProcessingFee = ($amount + $paymentFeeAmount) / (1 - $paymentFeePercent / 100) - $amount;

			return round($paymentProcessingFee, 2);
		}

		return 0.00;
	}

	/**
	 * Calculate registration fee
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateRegistrationFees($row, $paymentMethod)
	{
		$fees['amount']                 = $row->amount - $row->payment_processing_fee;
		$fees['payment_processing_fee'] = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'calculatePaymentProcessingFee',
			[$paymentMethod, $fees['amount']]
		);
		$fees['gross_amount']           = $fees['amount'] + $fees['payment_processing_fee'];

		return $fees;
	}

	/**
	 * Calculate remainder fee
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateRemainderFees($row, $paymentMethod)
	{
		$fees['amount']                 = $amount = $row->amount - $row->deposit_amount;
		$fees['payment_processing_fee'] = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'calculatePaymentProcessingFee',
			[$paymentMethod, $fees['amount']]
		);
		$fees['gross_amount']           = $fees['amount'] + $fees['payment_processing_fee'];

		return $fees;
	}

	/**
	 * Get coupon code for shopping cart registration
	 *
	 * @param   string                $couponCode
	 * @param   Joomla\CMS\User\User  $user
	 *
	 * @return null|stdClass
	 */
	public static function getCouponForCartRegistration($couponCode, $user)
	{
		$db          = Factory::getDbo();
		$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$nullDate    = $db->getNullDate();

		$query = $db->getQuery(true)
			->clear()
			->select('*')
			->from('#__eb_coupons')
			->where('published = 1')
			->where('`access` IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
			->where('code = ' . $db->quote($couponCode))
			->where('(valid_from = ' . $db->quote($nullDate) . ' OR valid_from <= ' . $currentDate . ')')
			->where('(valid_to = ' . $db->quote($nullDate) . ' OR valid_to >= ' . $currentDate . ')')
			->where('user_id IN (0, ' . $user->id . ')')
			->where('(times = 0 OR times > used)')
			->where('discount > used_amount')
			->order('id DESC');
		$db->setQuery($query);
		$coupon = $db->loadObject();

		if ($coupon && $coupon->max_usage_per_user > 0 && $user->id > 0)
		{
			$query->clear()
				->select('COUNT(*)')
				->from('#__eb_registrants')
				->where('user_id = ' . $user->id)
				->where('coupon_id = ' . $coupon->id)
				->where('group_id = 0')
				->where('(published = 1 OR (published = 0 AND payment_method LIKE "%os_offline"))');
			$db->setQuery($query);
			$total = $db->loadResult();

			if ($total >= $coupon->max_usage_per_user)
			{
				$coupon = null;
			}
		}

		return $coupon;
	}

	/**
	 * Get query to validate coupon
	 *
	 * @param   string                $couponCode
	 * @param   stdClass              $event
	 * @param   Joomla\CMS\User\User  $user
	 *
	 * @return JDatabaseQuery|string
	 */
	public static function getCouponQuery($couponCode, $event, $user)
	{
		$db          = Factory::getDbo();
		$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$nullDate    = $db->getNullDate();
		$negEventId  = -1 * $event->id;

		// Get all categories of the event
		$categoryIds = EventbookingHelperDatabase::getEventCategories([$event->id]);

		$whereOrs   = [];
		$whereOrs[] = 'category_id = - 1';
		$whereOrs[] = 'event_id = -1';

		if (count($categoryIds) > 0)
		{
			$whereOrs[] = 'id IN (SELECT coupon_id FROM #__eb_coupon_categories AS ec WHERE ec.category_id IN (' . implode(',', $categoryIds) . '))';
		}

		$whereOrs[] = 'id IN (SELECT coupon_id FROM #__eb_coupon_events AS cv WHERE cv.event_id = ' . $event->id . ')';

		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_coupons')
			->where('published = 1')
			->where('`access` IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
			->where('(valid_from = ' . $db->quote($nullDate) . ' OR valid_from <= ' . $currentDate . ')')
			->where('(valid_to = ' . $db->quote($nullDate) . ' OR valid_to >= ' . $currentDate . ')')
			->where('(times = 0 OR times > used)')
			->where('discount > used_amount')
			->where('user_id IN (0, ' . $user->id . ')')
			->where('(' . implode(' OR ', $whereOrs) . ')')
			->where('id NOT IN (SELECT coupon_id FROM #__eb_coupon_events WHERE event_id = ' . $negEventId . ')')
			->order('id DESC');

		if ($couponCode)
		{
			$query->where('code = ' . $db->quote($couponCode));
		}

		return $query;
	}

	/**
	 * Calculate fees use for individual registration
	 *
	 * @param   object     $event
	 * @param   RADForm    $form
	 * @param   array      $data
	 * @param   RADConfig  $config
	 * @param   string     $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateIndividualRegistrationFees($event, $form, $data, $config, $paymentMethod = null)
	{
		$fees        = [];
		$user        = Factory::getUser();
		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);
		$couponCode  = $data['coupon_code'] ?? '';
		$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$nullDate    = $db->getNullDate();

		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideData', 'calculateEventsDiscountedPrice'))
		{
			// This is added here for backward compatible purpose, in case someone override the method
			EventbookingHelperOverrideData::calculateEventsDiscountedPrice([$event]);
		}
		else
		{
			EventbookingHelper::callOverridableHelperMethod('Data', 'calculateEventsDiscountedPriceForUser', [[$event], $user]);
		}

		[$vatNumberValid, $taxRate, $showVatNumberField] = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'calculateRegistrationTaxRate',
			[$event, $form, $data, $config]
		);

		$event->tax_rate = $taxRate;

		$feeCalculationTags = [
			'NUMBER_REGISTRANTS' => 1,
			'INDIVIDUAL_PRICE'   => $event->individual_price,
			'DISCOUNTED_PRICE'   => $event->discounted_price,
			'SUB_TOTAL'          => $event->individual_price,
		];

		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			EventbookingHelperData::prepareCustomFieldsData([$event]);

			$filterInput = InputFilter::getInstance();

			foreach ($event->paramData as $customFieldName => $param)
			{
				$feeCalculationTags[strtoupper($customFieldName)] = $filterInput->clean($param['value'], 'float');
			}
		}

		if ($event->has_multiple_ticket_types)
		{
			$ticketTypes = EventbookingHelperData::getTicketTypes($event->id);
		}
		else
		{
			$ticketTypes = [];
		}

		$totalNumberTickets     = 0;
		$totalNumberPaidTickets = 0;
		$ticketTypeIds          = [];

		foreach ($ticketTypes as $ticketType)
		{
			if (!empty($data['ticket_type_' . $ticketType->id]))
			{
				$totalNumberTickets += $data['ticket_type_' . $ticketType->id];

				if ($ticketType->price > 0)
				{
					$totalNumberPaidTickets          += $data['ticket_type_' . $ticketType->id];
					$feeCalculationTags['SUB_TOTAL'] += $data['ticket_type_' . $ticketType->id] * $ticketType->price;
				}

				$ticketTypeIds[] = $ticketType->id;
			}
		}

		if ($event->has_multiple_ticket_types)
		{
			$form->handleFieldsDependOnTicketTypes($ticketTypeIds);
		}

		$feeCalculationTags['NUMBER_TICKETS']      = $totalNumberTickets;
		$feeCalculationTags['NUMBER_PAID_TICKETS'] = $totalNumberPaidTickets;

		$totalAmount         = $event->individual_price + $form->calculateFee($feeCalculationTags);
		$noneDiscountableFee = empty($feeCalculationTags['none_discountable_fee']) ? 0 : $feeCalculationTags['none_discountable_fee'];
		$realTotalAmount     = $totalAmount;
		$totalAmount         -= $noneDiscountableFee;
		$discountAmount      = 0;

		if ($event->has_multiple_ticket_types)
		{
			$params                    = new Registry($event->params);
			$collectMembersInformation = $params->get('ticket_types_collect_members_information', 0);

			foreach ($ticketTypes as $ticketType)
			{
				if (empty($data['ticket_type_' . $ticketType->id]))
				{
					continue;
				}

				$ticketType->quantity = $data['ticket_type_' . $ticketType->id];
				$totalAmount          += (int) $ticketType->quantity * $ticketType->price;

				if ($ticketType->discount_rules)
				{
					$rules = explode(',', $ticketType->discount_rules);

					$ticketDiscountAmount = 0;

					foreach ($rules as $rule)
					{
						if (strpos($rule, ':') === false)
						{
							continue;
						}

						[$ruleQuantity, $ruleDiscountAmount] = explode(':', $rule);

						if ($ticketType->quantity >= $ruleQuantity)
						{
							$ticketDiscountAmount = $ruleDiscountAmount;
						}
					}

					$discountAmount += $ticketDiscountAmount;
				}
			}

			if ($collectMembersInformation)
			{
				$ticketsMembersData                = [];
				$ticketsMembersData['eventId']     = $event->id;
				$ticketsMembersData['ticketTypes'] = $ticketTypes;
				$ticketsMembersData['formData']    = $data;

				$rowFields = EventbookingHelperRegistration::getFormFields($event->id, 2);

				if (isset($data['use_field_default_value']))
				{
					$useDefault = $data['use_field_default_value'];
				}
				else
				{
					$useDefault = true;
				}

				$count = 0;

				foreach ($ticketTypes as $item)
				{
					if (empty($item->quantity))
					{
						continue;
					}

					for ($i = 0; $i < $item->quantity; $i++)
					{
						$count++;
						$memberForm = new RADForm($rowFields);
						$memberForm->setFieldSuffix($count);
						$memberForm->bind($data, $useDefault);
						$memberForm->handleFieldsDependOnTicketTypes([$item->id]);
						$totalAmount += $memberForm->calculateFee();
					}
				}

				$fees['tickets_members'] = EventbookingHelperHtml::loadCommonLayout('common/tmpl/tickets_members.php', $ticketsMembersData);
			}
		}

		if ($config->get('setup_price'))
		{
			$totalAmount         = $totalAmount / (1 + $event->tax_rate / 100);
			$noneDiscountableFee = $noneDiscountableFee / (1 + $event->tax_rate / 100);
		}

		$fees['discount_rate'] = 0;

		if ($user->id)
		{
			if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideRegistration', 'calculateMemberDiscount'))
			{
				// This is added here for backward compatible purpose, in case someone overrides the method calculateMemberDiscount before
				$discountRate = EventbookingHelperOverrideRegistration::calculateMemberDiscount($event->discount_amounts, $event->discount_groups);
			}
			else
			{
				$discountRate = EventbookingHelper::callOverridableHelperMethod(
					'Registration',
					'calculateMemberDiscountForUser',
					[$event->discount_amounts, $event->discount_groups, $user]
				);
			}

			if ($discountRate > 0 && $config->get('setup_price') && $event->discount_type == 2)
			{
				$discountRate = $discountRate / (1 + $event->tax_rate / 100);
			}

			if ($discountRate > 0)
			{
				$fees['discount_rate'] = $discountRate;

				if ($event->discount_type == 1)
				{
					$discountAmount = $totalAmount * $discountRate / 100;
				}
				else
				{
					$discountAmount = $discountRate;
				}
			}
		}

		if ((int) $event->early_bird_discount_date
			&& $event->date_diff >= 0
			&& $event->early_bird_discount_amount > 0)
		{
			if ($event->early_bird_discount_type == 1)
			{
				$discountAmount = $discountAmount + $totalAmount * $event->early_bird_discount_amount / 100;
			}
			else
			{
				if ($config->get('setup_price'))
				{
					$discountAmount = $discountAmount + $event->early_bird_discount_amount / (1 + $event->tax_rate / 100);
				}
				else
				{
					$discountAmount = $discountAmount + $event->early_bird_discount_amount;
				}
			}
		}

		if ($couponCode)
		{
			$coupon = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'getCouponForIndividualRegistration',
				[$couponCode, $event, $user]
			);

			if ($coupon
				&& ($coupon->min_payment_amount == 0 || $coupon->min_payment_amount <= $realTotalAmount)
				&& ($coupon->max_payment_amount == 0 || $coupon->max_payment_amount >= $realTotalAmount))
			{
				$fees['coupon_valid'] = 1;
				$fees['coupon']       = $coupon;

				if ($coupon->coupon_type == 0)
				{
					$discountAmount = $discountAmount + $totalAmount * $coupon->discount / 100;
				}
				elseif ($coupon->coupon_type == 1)
				{
					if ($coupon->apply_to == 0 && $event->has_multiple_ticket_types)
					{
						foreach ($ticketTypes as $item)
						{
							if (empty($item->quantity))
							{
								continue;
							}

							$discountAmount = $discountAmount + $item->quantity * $coupon->discount;
						}
					}
					else
					{
						$discountAmount = $discountAmount + $coupon->discount;
					}
				}
			}
			else
			{
				$fees['coupon_valid'] = 0;
			}
		}
		else
		{
			$fees['coupon_valid'] = 1;
		}

		$fees['bundle_discount_amount'] = 0;
		$fees['bundle_discount_ids']    = [];

		// Calculate bundle discount if setup
		if ($user->id > 0)
		{
			$query->clear()
				->select('id, event_ids, discount_amount')
				->from('#__eb_discounts')
				->where('(from_date = ' . $db->quote($nullDate) . ' OR from_date <=' . $currentDate . ')')
				->where('(to_date = ' . $db->quote($nullDate) . ' OR to_date >= ' . $currentDate . ')')
				->where('(times = 0 OR times > used)')
				->where('published = 1')
				->where('id IN (SELECT discount_id FROM #__eb_discount_events WHERE event_id = ' . $event->id . ')');
			$db->setQuery($query);

			$discountRules = $db->loadObjectList();

			if (!empty($discountRules))
			{
				$query->clear()
					->select('DISTINCT event_id')
					->from('#__eb_registrants')
					->where('user_id = ' . $user->id)
					->where('(published = 1 OR (payment_method LIKE "os_offline%" AND published IN (0, 1)))');
				$db->setQuery($query);
				$registeredEventIds = $db->loadColumn();

				if (count($registeredEventIds))
				{
					$registeredEventIds[] = $event->id;

					foreach ($discountRules as $rule)
					{
						$eventIds = explode(',', $rule->event_ids);

						if (!array_diff($eventIds, $registeredEventIds))
						{
							$fees['bundle_discount_amount'] += $rule->discount_amount;
							$discountAmount                 += $rule->discount_amount;
							$fees['bundle_discount_ids'][]  = $rule->id;
						}
					}
				}
			}
		}

		$totalAmount += $noneDiscountableFee;

		if ($discountAmount > $totalAmount)
		{
			$discountAmount = $totalAmount;
		}

		// Late Fee
		$lateFee = 0;

		if ((int) $event->late_fee_date
			&& $event->late_fee_date_diff >= 0
			&& $event->late_fee_amount > 0)
		{
			if ($event->late_fee_type == 1)
			{
				$lateFee = $totalAmount * $event->late_fee_amount / 100;
			}
			else
			{
				$lateFee = $event->late_fee_amount;
			}
		}

		if ($event->tax_rate > 0 && ($totalAmount - $discountAmount + $lateFee > 0))
		{
			$taxAmount = round(($totalAmount - $discountAmount + $lateFee) * $event->tax_rate / 100, 2);
			$amount    = $totalAmount - $discountAmount + $taxAmount + $lateFee;
		}
		else
		{
			$taxAmount = 0;
			$amount    = $totalAmount - $discountAmount + $taxAmount + $lateFee;
		}

		// Init payment processing fee amount
		$fees['payment_processing_fee'] = 0;

		$paymentType = isset($data['payment_type']) ? (int) $data['payment_type'] : 0;

		if ($paymentType == 0 && $amount > 0)
		{
			$fees['payment_processing_fee'] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculatePaymentProcessingFee',
				[$paymentMethod, $amount]
			);
			$amount                         += $fees['payment_processing_fee'];
		}

		$couponDiscountAmount = 0;

		if (!empty($coupon) && $coupon->coupon_type == 2)
		{
			$couponAvailableAmount = $coupon->discount - $coupon->used_amount;

			if ($couponAvailableAmount >= $amount)
			{
				$couponDiscountAmount = $amount;
				$amount               = 0;
			}
			else
			{
				$amount               = $amount - $couponAvailableAmount;
				$couponDiscountAmount = $couponAvailableAmount;
			}
		}

		$discountAmount += $couponDiscountAmount;

		// Calculate the deposit amount as well
		if ($config->activate_deposit_feature && $event->deposit_amount > 0)
		{
			if ($event->deposit_type == 2)
			{
				$depositAmount = $event->deposit_amount;
			}
			else
			{
				$depositAmount = $event->deposit_amount * $amount / 100;
			}
		}
		else
		{
			$depositAmount = 0;
		}

		if ($paymentType == 1 && $depositAmount > 0)
		{
			$fees['payment_processing_fee'] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculatePaymentProcessingFee',
				[$paymentMethod, $depositAmount]
			);
			$amount                         += $fees['payment_processing_fee'];
			$depositAmount                  += $fees['payment_processing_fee'];
		}

		$fees['total_amount']           = round($totalAmount, 2);
		$fees['discount_amount']        = round($discountAmount, 2);
		$fees['tax_amount']             = round($taxAmount, 2);
		$fees['amount']                 = round($amount, 2);
		$fees['deposit_amount']         = round($depositAmount, 2);
		$fees['late_fee']               = round($lateFee, 2);
		$fees['coupon_discount_amount'] = round($couponDiscountAmount, 2);
		$fees['vat_number_valid']       = $vatNumberValid;
		$fees['tax_rate']               = $taxRate;
		$fees['show_vat_number_field']  = $showVatNumberField;
		$fees['fields_fee_amount']      = $feeCalculationTags['fields_fee_amount'] ?? [];

		return $fees;
	}

	/**
	 * Method to get coupon for individual registration
	 *
	 * @param   string    $couponCode
	 * @param   stdClass  $event
	 * @param   Joomla\CMS\User\User
	 *
	 * @return mixed|null
	 * @throws Exception
	 */
	public static function getCouponForIndividualRegistration($couponCode, $event, $user)
	{
		$db          = Factory::getDbo();
		$couponQuery = EventbookingHelper::callOverridableHelperMethod('Registration', 'getCouponQuery', [$couponCode, $event, $user]);
		$couponQuery->where('enable_for IN (0, 1)');
		$db->setQuery($couponQuery);
		$coupon = $db->loadObject();

		if ($coupon && $coupon->max_usage_per_user > 0 && $user->id > 0)
		{
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from('#__eb_registrants')
				->where('user_id = ' . $user->id)
				->where('coupon_id = ' . $coupon->id)
				->where('group_id = 0')
				->where('(published = 1 OR (published = 0 AND payment_method LIKE "%os_offline"))');
			$db->setQuery($query);
			$total = $db->loadResult();

			if ($total >= $coupon->max_usage_per_user)
			{
				$coupon = null;
			}
		}

		return $coupon;
	}

	/**
	 * Calculate fees use for group registration
	 *
	 * @param   object     $event
	 * @param   RADForm    $form
	 * @param   array      $data
	 * @param   RADConfig  $config
	 * @param   string     $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateGroupRegistrationFees($event, $form, $data, $config, $paymentMethod = null)
	{
		$fees    = [];
		$session = Factory::getApplication()->getSession();

		if (isset($data['__registrant_user']) && ($data['__registrant_user'] instanceof User))
		{
			$user = $data['__registrant_user'];
		}
		else
		{
			$user = Factory::getUser();
		}

		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);
		$couponCode  = $data['coupon_code'] ?? '';
		$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$nullDate    = $db->getNullDate();
		$eventId     = $event->id;

		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideData', 'calculateEventsDiscountedPrice'))
		{
			// This is added here for backward compatible purpose, in case someone override the method
			EventbookingHelperOverrideData::calculateEventsDiscountedPrice([$event]);
		}
		else
		{
			EventbookingHelper::callOverridableHelperMethod('Data', 'calculateEventsDiscountedPriceForUser', [[$event], $user]);
		}

		[$vatNumberValid, $taxRate, $showVatNumberField] = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'calculateRegistrationTaxRate',
			[$event, $form, $data, $config]
		);

		$event->tax_rate = $taxRate;

		$numberRegistrants = (int) $session->get('eb_number_registrants', '');

		if (!$numberRegistrants && isset($data['number_registrants']))
		{
			$numberRegistrants = (int) $data['number_registrants'];
		}

		$memberFormFields = EventbookingHelperRegistration::getFormFields($eventId, 2);
		$rate             = EventbookingHelper::callOverridableHelperMethod('Registration', 'getRegistrationRate', [$eventId, $numberRegistrants]);

		$feeCalculationTags = [
			'NUMBER_REGISTRANTS'            => $numberRegistrants,
			'INDIVIDUAL_PRICE'              => $rate,
			'INDIVIDUAL_REGISTRATION_PRICE' => $event->individual_price,
			'DISCOUNTED_PRICE'              => $event->discounted_price,
			'SUB_TOTAL'                     => $numberRegistrants * $rate,
		];

		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			EventbookingHelperData::prepareCustomFieldsData([$event]);

			$filterInput = InputFilter::getInstance();

			foreach ($event->paramData as $customFieldName => $param)
			{
				$feeCalculationTags[strtoupper($customFieldName)] = $filterInput->clean($param['value'], 'float');
			}
		}

		$extraFee            = $form->calculateFee($feeCalculationTags);
		$noneDiscountableFee = empty($feeCalculationTags['none_discountable_fee']) ? 0 : $feeCalculationTags['none_discountable_fee'];
		$fieldsFeeAmount     = $feeCalculationTags['fields_fee_amount'] ?? [];

		$membersForm                     = [];
		$membersTotalAmount              = [];
		$membersDiscountAmount           = [];
		$membersLateFee                  = [];
		$membersTaxAmount                = [];
		$membersAmount                   = [];
		$membersNoneDiscountableFee      = [];
		$totalMembersNoneDiscountableFee = 0;

		if ($event->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $event->collect_member_information;
		}

		// Members data
		if ($collectMemberInformation)
		{
			$membersData = $session->get('eb_group_members_data', null);

			if ($membersData)
			{
				$membersData = unserialize($membersData);
			}
			elseif (!empty($data['re_calculate_fee']))
			{
				$membersData = $data;
			}
			else
			{
				$membersData = [];
			}

			for ($i = 0; $i < $numberRegistrants; $i++)
			{
				$currentMemberFormFields = static::getGroupMemberFields($memberFormFields, $i + 1);
				$memberForm              = new RADForm($currentMemberFormFields);
				$memberForm->setFieldSuffix($i + 1);
				$memberForm->bind($membersData);
				$memberExtraFee                  = $memberForm->calculateFee($feeCalculationTags);
				$memberNoneDiscountableFee       = empty($feeCalculationTags['none_discountable_fee']) ? 0 : $feeCalculationTags['none_discountable_fee'];
				$extraFee                        += $memberExtraFee;
				$totalMembersNoneDiscountableFee += $memberNoneDiscountableFee;
				$membersTotalAmount[$i]          = $rate + $memberExtraFee;
				$membersNoneDiscountableFee[$i]  = $memberNoneDiscountableFee;
				$membersTotalAmount[$i]          -= $memberNoneDiscountableFee;

				if ($config->get('setup_price'))
				{
					$membersTotalAmount[$i]         = $membersTotalAmount[$i] / (1 + $event->tax_rate / 100);
					$membersNoneDiscountableFee[$i] = $membersNoneDiscountableFee[$i] / (1 + $event->tax_rate / 100);
				}

				$membersDiscountAmount[$i] = 0;
				$membersLateFee[$i]        = 0;
				$membersForm[$i]           = $memberForm;
			}
		}
		else
		{
			for ($i = 0; $i < $numberRegistrants; $i++)
			{
				$membersTotalAmount[$i] = $rate;

				if ($config->get('setup_price'))
				{
					$membersTotalAmount[$i] = $membersTotalAmount[$i] / (1 + $event->tax_rate / 100);
				}
			}
		}

		if ($event->fixed_group_price > 0)
		{
			$totalAmount     = $event->fixed_group_price + $extraFee - $noneDiscountableFee - $totalMembersNoneDiscountableFee;
			$realTotalAmount = $event->fixed_group_price + $extraFee;
		}
		else
		{
			$realTotalAmount = $rate * $numberRegistrants + $extraFee;
			$totalAmount     = $rate * $numberRegistrants + $extraFee - $noneDiscountableFee - $totalMembersNoneDiscountableFee;
		}


		if ($config->get('setup_price'))
		{
			$totalAmount                     = $totalAmount / (1 + $event->tax_rate / 100);
			$noneDiscountableFee             = $noneDiscountableFee / (1 + $event->tax_rate / 100);
			$totalMembersNoneDiscountableFee = $totalMembersNoneDiscountableFee / (1 + $event->tax_rate / 100);
		}

		// Calculate discount amount
		$discountAmount = 0;

		if ($user->id)
		{
			if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideRegistration', 'calculateMemberDiscount'))
			{
				// This is added here for backward compatible purpose, in case someone overrides the method calculateMemberDiscount before
				$discountRate = EventbookingHelperOverrideRegistration::calculateMemberDiscount($event->discount_amounts, $event->discount_groups);
			}
			else
			{
				$discountRate = EventbookingHelper::callOverridableHelperMethod(
					'Registration',
					'calculateMemberDiscountForUser',
					[$event->discount_amounts, $event->discount_groups, $user]
				);
			}

			if ($discountRate > 0 && $config->get('setup_price') && $event->discount_type == 2)
			{
				$discountRate = $discountRate / (1 + $event->tax_rate / 100);
			}

			if ($discountRate > 0)
			{
				if ($event->discount_type == 1)
				{
					// Discount applied for first member only
					if ($event->members_discount_apply_for)
					{
						$discountAmount = $membersTotalAmount[0] * $discountRate / 100;

						if ($collectMemberInformation)
						{
							$membersDiscountAmount[0] += $membersTotalAmount[0] * $discountRate / 100;
						}
					}
					else
					{ // Discount applied for each members in group
						$discountAmount = $totalAmount * $discountRate / 100;

						if ($collectMemberInformation)
						{
							for ($i = 0; $i < $numberRegistrants; $i++)
							{
								$membersDiscountAmount[$i] += $membersTotalAmount[$i] * $discountRate / 100;
							}
						}
					}
				}
				else
				{
					// Discount applied for first member only
					if ($event->members_discount_apply_for)
					{
						$discountAmount = $discountRate;

						if ($collectMemberInformation)
						{
							$membersDiscountAmount[0] = $discountRate;
						}
					}
					else
					{ // Discount applied for each members in the group
						$discountAmount = $numberRegistrants * $discountRate;

						if ($collectMemberInformation)
						{
							for ($i = 0; $i < $numberRegistrants; $i++)
							{
								$membersDiscountAmount[$i] += $discountRate;
							}
						}
					}
				}
			}
		}

		if ($couponCode)
		{
			$coupon = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'getCouponForGroupRegistration',
				[$couponCode, $event, $user, $numberRegistrants]
			);

			if ($coupon
				&& ($coupon->min_payment_amount == 0 || $coupon->min_payment_amount <= $realTotalAmount)
				&& ($coupon->max_payment_amount == 0 || $coupon->max_payment_amount >= $realTotalAmount))
			{
				$fees['coupon_valid'] = 1;
				$fees['coupon']       = $coupon;

				if ($coupon->coupon_type == 0)
				{
					$discountAmount = $discountAmount + $totalAmount * $coupon->discount / 100;

					if ($collectMemberInformation)
					{
						for ($i = 0; $i < $numberRegistrants; $i++)
						{
							$membersDiscountAmount[$i] += $membersTotalAmount[$i] * $coupon->discount / 100;
						}
					}
				}
				elseif ($coupon->coupon_type == 1)
				{
					if ($coupon->apply_to == 0)
					{
						$discountAmount = $discountAmount + $numberRegistrants * $coupon->discount;

						if ($collectMemberInformation)
						{
							for ($i = 0; $i < $numberRegistrants; $i++)
							{
								$membersDiscountAmount[$i] += $coupon->discount;
							}
						}
					}
					else
					{
						$discountAmount           = $discountAmount + $coupon->discount;
						$membersDiscountAmount[0] += $coupon->discount;
					}
				}
			}
			else
			{
				$fees['coupon_valid'] = 0;
			}
		}
		else
		{
			$fees['coupon_valid'] = 1;
		}

		if ((int) $event->early_bird_discount_date
			&& $event->date_diff >= 0
			&& $event->early_bird_discount_amount > 0)
		{
			if ($event->early_bird_discount_type == 1)
			{
				$discountAmount = $discountAmount + $totalAmount * $event->early_bird_discount_amount / 100;

				if ($collectMemberInformation)
				{
					for ($i = 0; $i < $numberRegistrants; $i++)
					{
						$membersDiscountAmount[$i] += $membersTotalAmount[$i] * $event->early_bird_discount_amount / 100;
					}
				}
			}
			else
			{
				$discountAmount = $discountAmount + $numberRegistrants * $event->early_bird_discount_amount;

				if ($collectMemberInformation)
				{
					for ($i = 0; $i < $numberRegistrants; $i++)
					{
						$membersDiscountAmount[$i] += $event->early_bird_discount_amount;
					}
				}
			}
		}

		$fees['bundle_discount_amount'] = 0;
		$fees['bundle_discount_ids']    = [];

		// Calculate bundle discount if setup
		if ($user->id > 0)
		{
			$query->clear()
				->select('id, event_ids, discount_amount')
				->from('#__eb_discounts')
				->where('(from_date = ' . $db->quote($nullDate) . ' OR from_date <=' . $currentDate . ')')
				->where('(to_date = ' . $db->quote($nullDate) . ' OR to_date >= ' . $currentDate . ')')
				->where('(times = 0 OR times > used)')
				->where('published = 1')
				->where('id IN (SELECT discount_id FROM #__eb_discount_events WHERE event_id = ' . $event->id . ')');
			$db->setQuery($query);
			$discountRules = $db->loadObjectList();

			if (!empty($discountRules))
			{
				$query->clear()
					->select('DISTINCT event_id')
					->from('#__eb_registrants')
					->where('user_id = ' . $user->id)
					->where('(published = 1 OR (payment_method LIKE "os_offline%" AND published IN (0, 1)))');
				$db->setQuery($query);
				$registeredEventIds = $db->loadColumn();

				if (count($registeredEventIds))
				{
					$registeredEventIds[] = $event->id;

					foreach ($discountRules as $rule)
					{
						$eventIds = explode(',', $rule->event_ids);

						if (!array_diff($eventIds, $registeredEventIds))
						{
							$fees['bundle_discount_amount'] += $rule->discount_amount;
							$discountAmount                 += $rule->discount_amount;
							$fees['bundle_discount_ids'][]  = $rule->id;
						}
					}
				}
			}
		}

		// Re-set none discountable fee back to total amount
		$totalAmount += $noneDiscountableFee + $totalMembersNoneDiscountableFee;

		if ($collectMemberInformation)
		{
			for ($i = 0; $i < $numberRegistrants; $i++)
			{
				$membersTotalAmount[$i] += $membersNoneDiscountableFee[$i];
			}
		}

		// Late Fee
		$lateFee = 0;

		if ((int) $event->late_fee_date
			&& $event->late_fee_date_diff >= 0
			&& $event->late_fee_amount > 0)
		{
			if ($event->late_fee_type == 1)
			{
				$lateFee = $totalAmount * $event->late_fee_amount / 100;

				if ($collectMemberInformation)
				{
					for ($i = 0; $i < $numberRegistrants; $i++)
					{
						$membersLateFee[$i] = $membersTotalAmount[$i] * $event->late_fee_amount / 100;
					}
				}
			}
			else
			{
				$lateFee = $numberRegistrants * $event->late_fee_amount;

				if ($collectMemberInformation)
				{
					for ($i = 0; $i < $numberRegistrants; $i++)
					{
						$membersLateFee[$i] = $event->late_fee_amount;
					}
				}
			}
		}

		// In case discount amount greater than total amount, reset it to total amount
		if ($discountAmount > $totalAmount)
		{
			$discountAmount = $totalAmount;
		}

		if ($collectMemberInformation)
		{
			for ($i = 0; $i < $numberRegistrants; $i++)
			{
				if ($membersDiscountAmount[$i] > $membersTotalAmount[$i])
				{
					$membersDiscountAmount[$i] = $membersTotalAmount[$i];
				}
			}
		}

		// Calculate tax amount
		if ($event->tax_rate > 0 && ($totalAmount - $discountAmount + $lateFee > 0))
		{
			$taxAmount = round(($totalAmount - $discountAmount + $lateFee) * $event->tax_rate / 100, 2);
			// Gross amount
			$amount = $totalAmount - $discountAmount + $taxAmount + $lateFee;

			if ($collectMemberInformation)
			{
				for ($i = 0; $i < $numberRegistrants; $i++)
				{
					$membersTaxAmount[$i] = round(
						($membersTotalAmount[$i] - $membersDiscountAmount[$i] + $membersLateFee[$i]) * $event->tax_rate / 100,
						2
					);
					$membersAmount[$i]    = $membersTotalAmount[$i] - $membersDiscountAmount[$i] + $membersLateFee[$i] + $membersTaxAmount[$i];
				}
			}
		}
		else
		{
			$taxAmount = 0;
			// Gross amount
			$amount = $totalAmount - $discountAmount + $taxAmount + $lateFee;

			if ($collectMemberInformation)
			{
				for ($i = 0; $i < $numberRegistrants; $i++)
				{
					$membersTaxAmount[$i] = 0;
					$membersAmount[$i]    = $membersTotalAmount[$i] - $membersDiscountAmount[$i] + $membersLateFee[$i] + $membersTaxAmount[$i];
				}
			}
		}

		// Init payment processing fee amount
		$fees['payment_processing_fee'] = 0;

		$paymentType = isset($data['payment_type']) ? (int) $data['payment_type'] : 0;

		if ($paymentType == 0 && $amount > 0)
		{
			$fees['payment_processing_fee'] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculatePaymentProcessingFee',
				[$paymentMethod, $amount]
			);
			$amount                         += $fees['payment_processing_fee'];
		}

		$couponDiscountAmount = 0;

		if (!empty($coupon) && $coupon->coupon_type == 2)
		{
			$couponAvailableAmount = $coupon->discount - $coupon->used_amount;

			if ($couponAvailableAmount >= $amount)
			{
				$couponDiscountAmount = $amount;
			}
			else
			{
				$couponDiscountAmount = $couponAvailableAmount;
			}

			$amount -= $couponDiscountAmount;

			if ($collectMemberInformation)
			{
				for ($i = 0; $i < $numberRegistrants; $i++)
				{
					if ($couponAvailableAmount >= $membersAmount[$i])
					{
						$memberCouponDiscountAmount = $membersAmount[$i];
					}
					else
					{
						$memberCouponDiscountAmount = $couponAvailableAmount;
					}

					$membersAmount[$i]         = $membersAmount[$i] - $memberCouponDiscountAmount;
					$membersDiscountAmount[$i] += $memberCouponDiscountAmount;

					$couponAvailableAmount -= $memberCouponDiscountAmount;

					if ($couponAvailableAmount <= 0)
					{
						break;
					}
				}
			}
		}

		$discountAmount += $couponDiscountAmount;

		// Deposit amount
		if ($config->activate_deposit_feature && $event->deposit_amount > 0)
		{
			if ($event->deposit_type == 2)
			{
				$depositAmount = $numberRegistrants * $event->deposit_amount;
			}
			else
			{
				$depositAmount = $event->deposit_amount * $amount / 100;
			}
		}
		else
		{
			$depositAmount = 0;
		}

		if ($paymentType == 1 && $depositAmount > 0)
		{
			$fees['payment_processing_fee'] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculatePaymentProcessingFee',
				[$paymentMethod, $depositAmount]
			);
			$amount                         += $fees['payment_processing_fee'];
			$depositAmount                  += $fees['payment_processing_fee'];
		}

		$fees['total_amount']            = round($totalAmount, 2);
		$fees['discount_amount']         = round($discountAmount, 2);
		$fees['late_fee']                = round($lateFee, 2);
		$fees['tax_amount']              = round($taxAmount, 2);
		$fees['amount']                  = round($amount, 2);
		$fees['deposit_amount']          = round($depositAmount, 2);
		$fees['members_form']            = $membersForm;
		$fees['members_total_amount']    = $membersTotalAmount;
		$fees['members_discount_amount'] = $membersDiscountAmount;
		$fees['members_tax_amount']      = $membersTaxAmount;
		$fees['members_amount']          = $membersAmount;
		$fees['members_late_fee']        = $membersLateFee;
		$fees['coupon_discount_amount']  = $couponDiscountAmount;
		$fees['vat_number_valid']        = $vatNumberValid;
		$fees['tax_rate']                = $taxRate;
		$fees['show_vat_number_field']   = $showVatNumberField;
		$fees['fields_fee_amount']       = $fieldsFeeAmount;

		return $fees;
	}

	/**
	 * Method to get coupon for group registration
	 *
	 * @param   string    $couponCode
	 * @param   stdClass  $event
	 * @param   Joomla\CMS\User\User
	 * @param   int       $numberRegistrants
	 *
	 * @return mixed|null
	 * @throws Exception
	 */
	public static function getCouponForGroupRegistration($couponCode, $event, $user, $numberRegistrants)
	{
		$db = Factory::getDbo();

		$couponQuery = EventbookingHelper::callOverridableHelperMethod('Registration', 'getCouponQuery', [$couponCode, $event, $user]);
		$couponQuery->where('enable_for IN (0, 2)')
			->where('(min_number_registrants = 0 OR min_number_registrants <= ' . $numberRegistrants . ')')
			->where('(max_number_registrants = 0 OR max_number_registrants >= ' . $numberRegistrants . ')');

		$db->setQuery($couponQuery);
		$coupon = $db->loadObject();

		if ($coupon && $coupon->max_usage_per_user > 0 && $user->id > 0)
		{
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from('#__eb_registrants')
				->where('user_id = ' . $user->id)
				->where('coupon_id = ' . $coupon->id)
				->where('group_id = 0')
				->where('(published = 1 OR (published = 0 AND payment_method LIKE "%os_offline"))');
			$db->setQuery($query);
			$total = $db->loadResult();

			if ($total >= $coupon->max_usage_per_user)
			{
				$coupon = null;
			}
		}

		return $coupon;
	}

	/**
	 * Calculate registration fee for cart registration
	 *
	 * @param   EventbookingHelperCart  $cart
	 * @param   RADForm                 $form
	 * @param   array                   $data
	 * @param   RADConfig               $config
	 * @param   string                  $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateCartRegistrationFee($cart, $form, $data, $config, $paymentMethod = null, $useDefault = false)
	{
		$user  = Factory::getUser();
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$items = $cart->getItems();

		// Early return to prevent error in case session was lost for some reasons
		if (empty($items))
		{
			$fees['total_amount']           = 0;
			$fees['discount_amount']        = 0;
			$fees['late_fee']               = 0;
			$fees['tax_amount']             = 0;
			$fees['amount']                 = 0;
			$fees['deposit_amount']         = 0;
			$fees['payment_processing_fee'] = 0;
			$fees['coupon_discount_amount'] = 0;

			$fees['vat_number_valid']      = 1;
			$fees['tax_rate']              = 0;
			$fees['show_vat_number_field'] = 0;
			$fees['fields_fee_amount']     = [];

			return $fees;
		}

		$quantities                          = $cart->getQuantities();
		$cartAmount                          = $cart->calculateTotal() - $cart->calculateTotalDiscount();
		$fees                                = [];
		$recordsData                         = [];
		$replaces                            = [];
		$replaces['CART_NUMBER_EVENTS']      = count($items);
		$replaces['CART_NUMBER_REGISTRANTS'] = array_sum($quantities);
		$totalAmount                         = 0;
		$discountAmount                      = 0;
		$lateFee                             = 0;
		$taxAmount                           = 0;
		$amount                              = 0;
		$couponDiscountAmount                = 0;
		$depositAmount                       = 0;
		$paymentProcessingFee                = 0;
		$feeAmount                           = $form->calculateFee($replaces);
		$noneDiscountableFee                 = empty($replaces['none_discountable_fee']) ? 0 : $replaces['none_discountable_fee'];
		$feeAmount                           -= $noneDiscountableFee;
		$paymentType                         = $data['payment_type'] ?? 1;
		$couponCode                          = $data['coupon_code'] ?? '';
		$collectRecordsData                  = $data['collect_records_data'] ?? false;
		$nullDate                            = $db->getNullDate();
		$currentDate                         = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$couponDiscountedEventIds            = [];
		$couponDiscountedCategoryIds         = [];
		$couponAvailableAmount               = 0;

		$numberDecimals = (int) $config->get('decimals') > 2 ? (int) $config->get('decimals') : 2;

		if ($couponCode)
		{
			$coupon = static::getCouponForCartRegistration($couponCode, $user);

			if ($coupon)
			{
				$fees['coupon_valid'] = 1;

				if ($coupon->event_id != -1)
				{
					// Get list of events which will receive discount
					$query->clear()
						->select('event_id')
						->from('#__eb_coupon_events')
						->where('coupon_id = ' . $coupon->id);
					$db->setQuery($query);
					$couponDiscountedEventIds = $db->loadColumn();
				}

				if ($coupon->category_id != -1)
				{
					$query->clear()
						->select('category_id')
						->from('#__eb_coupon_categories')
						->where('coupon_id = ' . $coupon->id);
					$db->setQuery($query);
					$couponDiscountedCategoryIds = $db->loadColumn();
				}

				if ($coupon->coupon_type == 2)
				{
					$couponAvailableAmount = $coupon->discount - $coupon->used_amount;
				}
			}
			else
			{
				$fees['coupon_valid'] = 0;
			}
		}
		else
		{
			$fees['coupon_valid'] = 1;
		}

		if ($config->collect_member_information_in_cart)
		{
			$membersForm                   = [];
			$membersTotalAmount            = [];
			$membersDiscountAmount         = [];
			$membersNoneDiscountableAmount = [];
			$membersLateFee                = [];
			$membersTaxAmount              = [];
			$membersAmount                 = [];
		}

		// Calculate bundle discount if setup
		$fees['bundle_discount_amount'] = 0;
		$fees['bundle_discount_ids']    = [];

		// First check if there are discount bundles rules with number events greater than number events in cart
		$query->clear()
			->select('*')
			->from('#__eb_discounts')
			->where('(from_date = ' . $db->quote($nullDate) . ' OR from_date <=' . $currentDate . ')')
			->where('(to_date = ' . $db->quote($nullDate) . ' OR to_date >= ' . $currentDate . ')')
			->where('number_events > 0')
			->where('number_events <= ' . count($items))
			->where('(times = 0 OR times > used)')
			->where('published = 1')
			->order('discount_amount DESC');
		$db->setQuery($query);
		$largestDiscountBundle = $db->loadObject();

		if ($largestDiscountBundle)
		{
			if ($largestDiscountBundle->discount_type == 1)
			{
				$fees['bundle_discount_amount'] += $largestDiscountBundle->discount_amount;
			}
			else
			{
				$fees['bundle_discount_amount'] += round($cartAmount * $largestDiscountBundle->discount_amount / 100, $numberDecimals);
			}

			$fees['bundle_discount_ids'][] = $largestDiscountBundle->id;
		}
		else
		{
			$query->clear()
				->select('id, event_ids, discount_type ,discount_amount')
				->from('#__eb_discounts')
				->where('(from_date = ' . $db->quote($nullDate) . ' OR from_date <=' . $currentDate . ')')
				->where('(to_date = ' . $db->quote($nullDate) . ' OR to_date >= ' . $currentDate . ')')
				->where('(times = 0 OR times > used)')
				->where('published = 1')
				->where('id IN (SELECT discount_id FROM #__eb_discount_events WHERE event_id IN (' . implode(',', $items) . '))');
			$db->setQuery($query);
			$discountRules = $db->loadObjectList();

			if (!empty($discountRules))
			{
				$registeredEventIds = $items;

				if ($user->id)
				{
					$query->clear()
						->select('DISTINCT event_id')
						->from('#__eb_registrants')
						->where('user_id = ' . $user->id)
						->where('(published = 1 OR (payment_method LIKE "os_offline%" AND published IN (0, 1)))');
					$db->setQuery($query);
					$registeredEventIds = array_merge($registeredEventIds, $db->loadColumn());
				}

				foreach ($discountRules as $rule)
				{
					$eventIds = explode(',', $rule->event_ids);

					if (!array_diff($eventIds, $registeredEventIds))
					{
						if ($rule->discount_type == 1)
						{
							$fees['bundle_discount_amount'] += $rule->discount_amount;
						}
						else
						{
							$fees['bundle_discount_amount'] += round($cartAmount * $rule->discount_amount / 100, $numberDecimals);
						}

						$fees['bundle_discount_ids'][] = $rule->id;
					}
				}
			}
		}

		$count                     = 0;
		$paymentFeeAmountAdded     = false;
		$totalBundleDiscountAmount = $fees['bundle_discount_amount'];

		$vatNumberValid     = true;
		$showVatNumberField = false;
		$taxRate            = 0;

		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$eventId                  = (int) $items[$i];
			$quantity                 = (int) $quantities[$i];
			$recordsData[$eventId]    = [];
			$event                    = EventbookingHelperDatabase::getEvent($eventId);
			$rate                     = EventbookingHelper::callOverridableHelperMethod('Registration', 'getRegistrationRate', [$eventId, $quantity]);
			$eventNoneDiscountableFee = 0;
			$eventCategoryIds         = EventbookingHelperDatabase::getEventCategories([$eventId]);

			[$vatNumberValid, $taxRate, $showVatNumberField] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculateRegistrationTaxRate',
				[$event, $form, $data, $config]
			);

			$recordsData[$eventId]['tax_rate'] = $taxRate;

			$event->tax_rate = $taxRate;

			if ($i == 0)
			{
				$registrantTotalAmount = $rate * $quantity + $feeAmount;
			}
			else
			{
				$registrantTotalAmount = $rate * $quantity;
			}

			if ($config->get('setup_price'))
			{
				$registrantTotalAmount = $registrantTotalAmount / (1 + $event->tax_rate / 100);
			}

			// Members data
			if ($config->collect_member_information_in_cart)
			{
				$memberFormFields = EventbookingHelperRegistration::getFormFields($eventId, 2);

				for ($j = 0; $j < $quantity; $j++)
				{
					$count++;
					$currentMemberFormFields = static::getGroupMemberFields($memberFormFields, $j + 1);
					$memberForm              = new RADForm($currentMemberFormFields);
					$memberForm->setFieldSuffix($count);
					$memberForm->bind($data, $useDefault);
					$memberExtraFee                   = $memberForm->calculateFee($replaces);
					$memberNoneDiscountableFee        = empty($replaces['none_discountable_fee']) ? 0 : $replaces['none_discountable_fee'];
					$memberExtraFee                   -= $memberNoneDiscountableFee;
					$registrantTotalAmount            += $memberExtraFee;
					$membersTotalAmount[$eventId][$j] = $rate + $memberExtraFee;
					$eventNoneDiscountableFee         += $memberNoneDiscountableFee;

					if ($config->get('setup_price'))
					{
						$membersTotalAmount[$eventId][$j] = $membersTotalAmount[$eventId][$j] / (1 + $event->tax_rate / 100);
					}

					$membersDiscountAmount[$eventId][$j]         = 0;
					$membersNoneDiscountableAmount[$eventId][$j] = $memberNoneDiscountableFee;
					$membersLateFee[$eventId][$j]                = 0;

					$membersForm[$eventId][$j] = $memberForm;
				}
			}

			$registrantDiscount = 0;

			// Member discount
			if ($user->id)
			{
				if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideRegistration', 'calculateMemberDiscount'))
				{
					// This is added here for backward compatible purpose
					$discountRate = EventbookingHelperOverrideRegistration::calculateMemberDiscount(
						$event->discount_amounts,
						$event->discount_groups
					);
				}
				else
				{
					$discountRate = EventbookingHelper::callOverridableHelperMethod(
						'Registration',
						'calculateMemberDiscountForUser',
						[$event->discount_amounts, $event->discount_groups, $user]
					);
				}

				if ($discountRate > 0 && $config->get('setup_price') && $event->discount_type == 2)
				{
					$discountRate = $discountRate / (1 + $event->tax_rate / 100);
				}

				if ($discountRate > 0)
				{
					if ($event->discount_type == 1)
					{
						$registrantDiscount += $registrantTotalAmount * $discountRate / 100;

						if ($config->collect_member_information_in_cart)
						{
							for ($j = 0; $j < $quantity; $j++)
							{
								$membersDiscountAmount[$eventId][$j] += $membersTotalAmount[$eventId][$j] * $discountRate / 100;
							}
						}
					}
					else
					{
						$registrantDiscount += $quantity * $discountRate;

						if ($config->collect_member_information_in_cart)
						{
							for ($j = 0; $j < $quantity; $j++)
							{
								$membersDiscountAmount[$eventId][$j] += $discountRate;
							}
						}
					}
				}
			}

			if ((int) $event->early_bird_discount_date
				&& $event->date_diff >= 0
				&& $event->early_bird_discount_amount > 0)
			{
				if ($event->early_bird_discount_type == 1)
				{
					$registrantDiscount += $registrantTotalAmount * $event->early_bird_discount_amount / 100;

					if ($config->collect_member_information_in_cart)
					{
						for ($j = 0; $j < $quantity; $j++)
						{
							$membersDiscountAmount[$eventId][$j] += $membersTotalAmount[$eventId][$j] * $event->early_bird_discount_amount / 100;
						}
					}
				}
				else
				{
					if ($config->get('setup_price'))
					{
						$event->early_bird_discount_amount = $event->early_bird_discount_amount / (1 + $event->tax_rate / 100);
					}

					$registrantDiscount += $quantity * $event->early_bird_discount_amount;

					if ($config->collect_member_information_in_cart)
					{
						for ($j = 0; $j < $quantity; $j++)
						{
							$membersDiscountAmount[$eventId][$j] += $event->early_bird_discount_amount;
						}
					}
				}
			}

			// Coupon discount
			if (!empty($coupon)
				&& (
					$coupon->category_id == -1
					|| $coupon->event_id == -1
					|| in_array($eventId, $couponDiscountedEventIds)
					|| count(array_intersect($eventCategoryIds, $couponDiscountedCategoryIds)) > 0)
				&& !in_array($eventId * -1, $couponDiscountedEventIds)
			)
			{
				if ($coupon->coupon_type == 0)
				{
					$registrantDiscount = $registrantDiscount + $registrantTotalAmount * $coupon->discount / 100;

					if ($config->collect_member_information_in_cart)
					{
						for ($j = 0; $j < $quantity; $j++)
						{
							$membersDiscountAmount[$eventId][$j] += $membersTotalAmount[$eventId][$j] * $coupon->discount / 100;
						}
					}
				}
				elseif ($coupon->coupon_type == 1)
				{
					$registrantDiscount = $registrantDiscount + $coupon->discount;

					if ($config->collect_member_information_in_cart)
					{
						$membersDiscountAmount[$eventId][0] += $coupon->discount;
					}
				}

				if ($collectRecordsData)
				{
					$recordsData[$eventId]['coupon_id'] = $coupon->id;
				}
			}

			// Restore registrant total amount
			if ($i == 0)
			{
				$registrantTotalAmount += $noneDiscountableFee;
			}

			$registrantTotalAmount += $eventNoneDiscountableFee;
			$remainingAmount       = $registrantTotalAmount - $registrantDiscount;

			if ($remainingAmount > 0 && $totalBundleDiscountAmount > 0)
			{
				if ($totalBundleDiscountAmount > $remainingAmount)
				{
					$registrantDiscount        += $remainingAmount;
					$totalBundleDiscountAmount = $totalBundleDiscountAmount - $remainingAmount;
				}
				else
				{
					$registrantDiscount        += $totalBundleDiscountAmount;
					$totalBundleDiscountAmount = 0;
				}
			}

			if ($registrantDiscount > $registrantTotalAmount)
			{
				$registrantDiscount = $registrantTotalAmount;
			}

			if ($config->collect_member_information_in_cart)
			{
				for ($j = 0; $j < $quantity; $j++)
				{
					$membersTotalAmount[$eventId][$j] += $membersNoneDiscountableAmount[$eventId][$j];
				}
			}

			// Late Fee
			$registrantLateFee = 0;

			if ((int) $event->late_fee_date
				&& $event->late_fee_date_diff >= 0
				&& $event->late_fee_amount > 0)
			{
				if ($event->late_fee_type == 1)
				{
					$registrantLateFee = $registrantTotalAmount * $event->late_fee_amount / 100;

					if ($config->collect_member_information_in_cart)
					{
						for ($j = 0; $j < $quantity; $j++)
						{
							$membersLateFee[$eventId][$j] = $membersTotalAmount[$eventId][$j] * $event->late_fee_amount / 100;
						}
					}
				}
				else
				{
					$registrantLateFee = $quantity * $event->late_fee_amount;

					if ($config->collect_member_information_in_cart)
					{
						for ($j = 0; $j < $quantity; $j++)
						{
							$membersLateFee[$eventId][$j] = $event->late_fee_amount;
						}
					}
				}
			}

			if ($event->tax_rate > 0)
			{
				$registrantTaxAmount = $event->tax_rate * ($registrantTotalAmount - $registrantDiscount + $registrantLateFee) / 100;
				$registrantAmount    = $registrantTotalAmount - $registrantDiscount + $registrantTaxAmount + $registrantLateFee;

				if ($config->collect_member_information_in_cart)
				{
					for ($j = 0; $j < $quantity; $j++)
					{
						$membersTaxAmount[$eventId][$j] = round(
							$event->tax_rate * ($membersTotalAmount[$eventId][$j] - $membersDiscountAmount[$eventId][$j] + $membersLateFee[$eventId][$j]) / 100,
							$numberDecimals
						);
						$membersAmount[$eventId][$j]    = $membersTotalAmount[$eventId][$j] - $membersDiscountAmount[$eventId][$j] + $membersLateFee[$eventId][$j] + $membersTaxAmount[$eventId][$j];
					}
				}
			}
			else
			{
				$registrantTaxAmount = 0;
				$registrantAmount    = $registrantTotalAmount - $registrantDiscount + $registrantTaxAmount + $registrantLateFee;

				if ($config->collect_member_information_in_cart)
				{
					for ($j = 0; $j < $quantity; $j++)
					{
						$membersTaxAmount[$eventId][$j] = 0;
						$membersAmount[$eventId][$j]    = $membersTotalAmount[$eventId][$j] - $membersDiscountAmount[$eventId][$j] + $membersLateFee[$eventId][$j] + $membersTaxAmount[$eventId][$j];
					}
				}
			}

			if ($registrantAmount > 0)
			{
				if ($paymentFeeAmountAdded)
				{
					$registrantPaymentProcessingFee = EventbookingHelper::callOverridableHelperMethod(
						'Registration',
						'calculatePaymentProcessingFee',
						[$paymentMethod, $registrantAmount, false]
					);
				}
				else
				{
					$paymentFeeAmountAdded          = true;
					$registrantPaymentProcessingFee = EventbookingHelper::callOverridableHelperMethod(
						'Registration',
						'calculatePaymentProcessingFee',
						[$paymentMethod, $registrantAmount]
					);
				}

				$registrantAmount += $registrantPaymentProcessingFee;
			}
			else
			{
				$registrantPaymentProcessingFee = 0;
			}

			if (!empty($coupon) && $coupon->coupon_type == 2
				&& (
					$coupon->category_id == -1
					|| $coupon->event_id == -1
					|| in_array($eventId, $couponDiscountedEventIds)
					|| count(array_intersect($eventCategoryIds, $couponDiscountedCategoryIds)) > 0)
				&& !in_array($eventId * -1, $couponDiscountedEventIds)
			)
			{
				if ($couponAvailableAmount > $registrantAmount)
				{
					$registrantCouponDiscountAmount = $registrantAmount;
				}
				else
				{
					$registrantCouponDiscountAmount = $couponAvailableAmount;
				}

				$registrantAmount      -= $registrantCouponDiscountAmount;
				$registrantDiscount    += $registrantCouponDiscountAmount;
				$couponAvailableAmount -= $registrantCouponDiscountAmount;

				$couponDiscountAmount += $registrantCouponDiscountAmount;

				if ($config->collect_member_information_in_cart)
				{
					$totalMemberDiscountAmount = $registrantCouponDiscountAmount;

					for ($j = 0; $j < $quantity; $j++)
					{
						if ($totalMemberDiscountAmount > $membersAmount[$eventId][$j])
						{
							$memberCouponDiscountAmount = $membersAmount[$eventId][$j];
						}
						else
						{
							$memberCouponDiscountAmount = $totalMemberDiscountAmount;
						}

						$totalMemberDiscountAmount -= $memberCouponDiscountAmount;

						$membersAmount[$eventId][$j] -= $memberCouponDiscountAmount;

						$membersDiscountAmount[$eventId][$j] += $memberCouponDiscountAmount;

						if ($totalMemberDiscountAmount <= 0)
						{
							break;
						}
					}
				}
			}

			if ($config->activate_deposit_feature && $event->deposit_amount > 0 && $paymentType == 1
				&& EventbookingHelper::isNullOrGreaterThan($event->deposit_until_date))
			{
				if ($event->deposit_type == 2)
				{
					$registrantDepositAmount = $event->deposit_amount * $quantity;
				}
				else
				{
					$registrantDepositAmount = round($registrantAmount * $event->deposit_amount / 100, $numberDecimals);
				}
			}
			else
			{
				// If user chooses deposit payment, for this event which deposit is not allowed, set deposit amount equals to payment amount
				if (isset($data['payment_type']) && $data['payment_type'] == 1)
				{
					$registrantDepositAmount = $registrantAmount;
				}
				else
				{
					$registrantDepositAmount = 0;
				}
			}

			$totalAmount          += $registrantTotalAmount;
			$discountAmount       += $registrantDiscount;
			$lateFee              += $registrantLateFee;
			$depositAmount        += $registrantDepositAmount;
			$taxAmount            += $registrantTaxAmount;
			$amount               += $registrantAmount;
			$paymentProcessingFee += $registrantPaymentProcessingFee;

			if ($collectRecordsData)
			{
				$recordsData[$eventId]['item_price']             = $rate;
				$recordsData[$eventId]['total_amount']           = round($registrantTotalAmount, $numberDecimals);
				$recordsData[$eventId]['discount_amount']        = round($registrantDiscount, $numberDecimals);
				$recordsData[$eventId]['late_fee']               = round($registrantLateFee, $numberDecimals);
				$recordsData[$eventId]['tax_amount']             = round($registrantTaxAmount, $numberDecimals);
				$recordsData[$eventId]['payment_processing_fee'] = round($registrantPaymentProcessingFee, $numberDecimals);
				$recordsData[$eventId]['amount']                 = round($registrantAmount, $numberDecimals);
				$recordsData[$eventId]['deposit_amount']         = round($registrantDepositAmount, $numberDecimals);
			}
		}

		$fees['total_amount']           = round($totalAmount, $numberDecimals);
		$fees['discount_amount']        = round($discountAmount, $numberDecimals);
		$fees['late_fee']               = round($lateFee, $numberDecimals);
		$fees['tax_amount']             = round($taxAmount, $numberDecimals);
		$fees['amount']                 = round($amount, $numberDecimals);
		$fees['deposit_amount']         = round($depositAmount, $numberDecimals);
		$fees['payment_processing_fee'] = round($paymentProcessingFee, $numberDecimals);
		$fees['coupon_discount_amount'] = round($couponDiscountAmount, $numberDecimals);

		$fees['vat_number_valid']      = $vatNumberValid;
		$fees['tax_rate']              = $taxRate;
		$fees['show_vat_number_field'] = $showVatNumberField;
		$fees['fields_fee_amount']     = $replaces['fields_fee_amount'] ?? [];

		if ($collectRecordsData)
		{
			$fees['records_data'] = $recordsData;
		}

		if ($config->collect_member_information_in_cart)
		{
			$fees['members_form']            = $membersForm;
			$fees['members_total_amount']    = $membersTotalAmount;
			$fees['members_discount_amount'] = $membersDiscountAmount;
			$fees['members_tax_amount']      = $membersTaxAmount;
			$fees['members_late_fee']        = $membersLateFee;
			$fees['members_amount']          = $membersAmount;
		}

		return $fees;
	}

	/**
	 * Check to see whether we will show billing form on group registration
	 *
	 * @param   int  $eventId
	 *
	 * @return boolean
	 */
	public static function showBillingStep($eventId)
	{
		$config = EventbookingHelper::getConfig();
		$event  = EventbookingHelperDatabase::getEvent($eventId);

		if ($event->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $event->collect_member_information;
		}

		if (!$collectMemberInformation || $config->show_billing_step_for_free_events)
		{
			return true;
		}

		if ($event->individual_price == 0 && $event->fixed_group_price == 0)
		{
			$db    = Factory::getDbo();
			$query = EventbookingHelperRegistration::getBaseEventFieldsQuery($eventId)
				->clear('select')
				->clear('order')
				->select('COUNT(*)')
				->where('fee_field = 1');
			$db->setQuery($query);

			$numberFeeFields = (int) $db->loadResult();

			if ($numberFeeFields == 0)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the form data used to bind to the RADForm object
	 *
	 * @param   array  $rowFields
	 * @param   int    $eventId
	 * @param   int    $userId
	 *
	 * @return array
	 */
	public static function getFormData($rowFields, $eventId, $userId)
	{
		$data = [];

		if ($userId)
		{
			$mappings = [];

			foreach ($rowFields as $rowField)
			{
				if ($rowField->field_mapping)
				{
					$mappings[$rowField->name] = $rowField->field_mapping;
				}
			}

			PluginHelper::importPlugin('eventbooking');
			$results = Factory::getApplication()->triggerEvent('onGetProfileData', [$userId, $mappings]);

			if (count($results))
			{
				foreach ($results as $res)
				{
					if (is_array($res) && count($res))
					{
						$data = $res;
						break;
					}
				}
			}

			if (!count($data))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('*')
					->from('#__eb_registrants')
					->where('user_id = ' . $userId . ' AND event_id = ' . $eventId . ' AND first_name != "" AND group_id = 0')
					->order('id DESC');
				$db->setQuery($query, 0, 1);
				$rowRegistrant = $db->loadObject();

				if (!$rowRegistrant)
				{
					//Try to get registration record from other events if available
					$query->clear('where')
						->where('user_id = ' . $userId . ' AND first_name != "" AND group_id = 0');
					$db->setQuery($query, 0, 1);
					$rowRegistrant = $db->loadObject();
				}

				if ($rowRegistrant)
				{
					$data = self::getRegistrantData($rowRegistrant, $rowFields);
				}
			}
		}

		foreach ($rowFields as $rowField)
		{
			if (!$rowField->populate_from_previous_registration)
			{
				unset($data[$rowField->name]);
			}
		}

		return $data;
	}

	/**
	 * Get data of registrant using to auto populate registration form
	 *
	 * @param   EventbookingTableRegistrant  $rowRegistrant
	 * @param   array                        $rowFields
	 *
	 * @return array
	 */
	public static function getRegistrantData($rowRegistrant, $rowFields = null)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$data  = [];

		// Get fields array if not provided in method call
		if ($rowFields === null)
		{
			$config = EventbookingHelper::getConfig();

			if ($config->multiple_booking)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($rowRegistrant->id, 4);
			}
			elseif ($rowRegistrant->is_group_billing)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($rowRegistrant->event_id, 1);
			}
			else
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($rowRegistrant->event_id, 0);
			}
		}

		$query->select('a.*, b.field_value')
			->from('#__eb_fields AS a')
			->innerJoin('#__eb_field_values AS b ON a.id = b.field_id')
			->where('b.registrant_id = ' . $rowRegistrant->id);
		$db->setQuery($query);
		$fieldValues = $db->loadObjectList('name');

		foreach ($rowFields as $rowField)
		{
			if ($rowField->is_core)
			{
				$data[$rowField->name] = $rowRegistrant->{$rowField->name};
			}
			else
			{
				if (isset($fieldValues[$rowField->name]))
				{
					$data[$rowField->name] = $fieldValues[$rowField->name]->field_value;

					if ($rowField->encrypt_data && $rowField->fieldtype === 'Text' && strlen($data[$rowField->name]))
					{
						$data[$rowField->name] = EventbookingHelperCryptor::decrypt($data[$rowField->name]);
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Create a user account
	 *
	 * @param   array  $data
	 *
	 * @return int Id of created user
	 */
	public static function saveRegistration($data)
	{
		$config = EventbookingHelper::getConfig();

		if ($config->use_cb_api)
		{
			return EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'userRegistrationCB',
				[$data['first_name'], $data['last_name'], $data['email'], $data['username'], $data['password1']]
			);
		}
		//Need to load com_users language file
		$lang = Factory::getLanguage();
		$tag  = $lang->getTag();

		if (!$tag)
		{
			$tag = 'en-GB';
		}

		$lang->load('com_users', JPATH_ROOT, $tag);
		$data['name']     = rtrim($data['first_name'] . ' ' . $data['last_name']);
		$data['password'] = $data['password2'] = $data['password1'];
		$data['email1']   = $data['email2'] = $data['email'];

		if (EventbookingHelper::isJoomla4())
		{
			Form::addFormPath(JPATH_ROOT . '/components/com_users/forms');

			/* @var RegistrationModel $model */
			$model = Factory::getApplication()->bootComponent('com_users')
				->getMVCFactory()->createModel('Registration', 'Site', ['ignore_request' => true]);
		}
		else
		{
			// Add path to load xml form definition
			if (Multilanguage::isEnabled())
			{
				Form::addFormPath(JPATH_ROOT . '/components/com_users/models/forms');
				Form::addFieldPath(JPATH_ROOT . '/components/com_users/models/fields');
			}

			JLoader::register('UsersModelRegistration', JPATH_ROOT . '/components/com_users/models/registration.php');

			$model = new UsersModelRegistration();
		}

		$result = $model->register($data);

		if ($result === false)
		{
			$logData = $data;

			unset($logData['password'], $logData['password1'], $logData['password2']);

			// Log the errors
			EventbookingHelper::logData(JPATH_ROOT . '/components/com_eventbooking/user_creation_error.txt', $logData, $model->getError());
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__users')
			->where('username = ' . $db->quote($data['username']));
		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Use CB API for saving user account
	 *
	 * @param       $firstName
	 * @param       $lastName
	 * @param       $email
	 * @param       $username
	 * @param       $password
	 *
	 * @return int
	 */
	public static function userRegistrationCB($firstName, $lastName, $email, $username, $password)
	{
		if ((!file_exists(JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php')) || (!file_exists(
				JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php'
			)))
		{
			echo 'CB not installed';

			return;
		}

		include_once JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';

		cbimport('cb.html');

		global $_CB_framework, $_PLUGINS, $ueConfig;

		$approval     = $ueConfig['reg_admin_approval'];
		$confirmation = ($ueConfig['reg_confirmation']);
		$user         = new UserTable();

		$user->set('username', $username);
		$user->set('email', $email);
		$user->set('name', trim($firstName . ' ' . $lastName));
		$user->set('gids', [(int) $_CB_framework->getCfg('new_usertype')]);
		$user->set('sendEmail', 0);
		$user->set('registerDate', $_CB_framework->getUTCDate());
		$user->set('password', $user->hashAndSaltPassword($password));
		$user->set('registeripaddr', cbGetIPlist());

		if ($approval == 0)
		{
			$user->set('approved', 1);
		}
		else
		{
			$user->set('approved', 0);
		}

		if ($confirmation == 0)
		{
			$user->set('confirmed', 1);
		}
		else
		{
			$user->set('confirmed', 0);
		}

		if (($user->get('confirmed') == 1) && ($user->get('approved') == 1))
		{
			$user->set('block', 0);
		}
		else
		{
			$user->set('block', 1);
		}

		$_PLUGINS->trigger('onBeforeUserRegistration', [&$user, &$user]);

		if ($user->store())
		{
			if ($user->get('confirmed') == 0)
			{
				$user->store();
			}

			$messagesToUser = activateUser($user, 1, 'UserRegistration');

			$_PLUGINS->trigger('onAfterUserRegistration', [&$user, &$user, true]);

			return $user->get('id');
		}

		return 0;
	}

	/**
	 * We only need to generate invoice for paid events only
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public static function needInvoice($row)
	{
		// Don't generate invoice for waiting list records
		if ($row->published === 3 || $row->cart_id > 0 || $row->group_id > 0)
		{
			return false;
		}

		$config = EventbookingHelper::getConfig();

		if ($config->always_generate_invoice)
		{
			return true;
		}

		if ($row->payment_method != 'os_offline' && strpos($row->payment_method, 'os_offline') !== false)
		{
			$method = EventbookingHelperPayments::loadPaymentMethod($row->payment_method);

			if ($method)
			{
				$params = new Registry($method->params);

				if ($params->get('generate_invoice', '1') === '0')
				{
					return false;
				}
			}
		}

		if ($config->generated_invoice_for_paid_registration_only && $row->published == 0)
		{
			return false;
		}

		if ($row->amount > 0)
		{
			return true;
		}

		if ($config->multiple_booking)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('SUM(amount)')
				->from('#__eb_registrants')
				->where('id = ' . $row->id . ' OR cart_id = ' . $row->id);
			$db->setQuery($query);
			$totalAmount = $db->loadResult();

			if ($totalAmount > 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the invoice number for this registration record
	 *
	 * return int
	 */
	public static function getInvoiceNumber($row = null)
	{
		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$query->select('MAX(invoice_number)')
			->from('#__eb_registrants');

		if ($config->reset_invoice_number)
		{
			$currentYear = date('Y');
			$query->where('invoice_year = ' . $currentYear);
			$row->invoice_year = $currentYear;
		}

		$db->setQuery($query);
		$invoiceNumber = (int) $db->loadResult();

		if (!$invoiceNumber)
		{
			$invoiceNumber = (int) $config->invoice_start_number;
		}
		else
		{
			$invoiceNumber++;
		}

		return $invoiceNumber;
	}

	/**
	 * Get Ticket Type for the given group member
	 *
	 * @param   int  $groupMemberId
	 *
	 * @return string
	 */
	public static function getGroupMemberTicketType($groupMemberId)
	{
		$ticketType = static::getGroupMemberTicketTypeData($groupMemberId);

		return $ticketType ? $ticketType->title : '';
	}

	/**
	 * Get Ticket Type for the given group member
	 *
	 * @param   int  $groupMemberId
	 *
	 * @pam string $language
	 *
	 * @return stdClass
	 */
	public static function getGroupMemberTicketTypeData($groupMemberId, $language = null)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.*')
			->from('#__eb_ticket_types AS a')
			->innerJoin('#__eb_registrant_tickets AS b ON a.id = b.ticket_type_id')
			->where('b.registrant_id = ' . $groupMemberId);
		$db->setQuery($query);

		$fieldSuffix = EventbookingHelper::getFieldSuffix($language);

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFieldsUseDefaultLanguageData($query, ['a.title', 'a.description'], $fieldSuffix);
		}

		return $db->loadObject();
	}

	/**
	 * Update Group Members record to have same information with billing record
	 *
	 * @param   int  $groupId
	 */
	public static function updateGroupRegistrationRecord($groupId)
	{
		$db  = Factory::getDbo();
		$row = new EventbookingTableRegistrant($db);

		if (!$row->load($groupId))
		{
			return;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->update('#__eb_registrants')
			->set('published = ' . $row->published)
			->set('payment_status = ' . $row->payment_status)
			->set('transaction_id = ' . $db->quote($row->transaction_id))
			->set('payment_method = ' . $db->quote($row->payment_method))
			->where('group_id = ' . $row->id);

		$db->setQuery($query)
			->execute();
	}

	/**
	 * Method to build common tags use for email messages
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 *
	 * @return array
	 */
	public static function buildDepositPaymentTags($row, $config)
	{
		$event  = EventbookingHelperDatabase::getEvent($row->event_id);
		$method = EventbookingHelperPayments::loadPaymentMethod($row->deposit_payment_method);

		$rowFields = static::getDepositPaymentFormFields();
		$replaces  = [];

		foreach ($rowFields as $rowField)
		{
			$replaces[$rowField->name] = $row->{$rowField->name};
		}

		if ($method)
		{
			$replaces['payment_method'] = Text::_($method->title);
		}
		else
		{
			$replaces['payment_method'] = '';
		}

		if ($row->payment_status == 1)
		{
			$dueAmount = 0;
		}
		else
		{
			$dueAmount = $row->amount - $row->deposit_amount;
		}

		$replaces['AMOUNT']         = EventbookingHelper::formatCurrency($row->amount - $row->deposit_amount, $config, $event->currency_symbol);
		$replaces['PAYMENT_AMOUNT'] = EventbookingHelper::formatCurrency(
			$row->amount - $row->deposit_amount + $row->deposit_payment_processing_fee,
			$config,
			$event->currency_symbol
		);

		$replaces['DUE_AMOUNT']      = EventbookingHelper::formatCurrency($dueAmount, $config, $event->currency_symbol);
		$replaces['REGISTRATION_ID'] = $row->id;
		$replaces['TRANSACTION_ID']  = $row->deposit_payment_transaction_id;

		$replaces = array_merge($replaces, static::buildEventTags($event, $config));

		return $replaces;
	}

	/**
	 * Build tags related to event
	 *
	 * @param   EventbookingTableEvent       $event
	 * @param   RADConfig                    $config
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $Itemid
	 *
	 * @return array
	 */
	public static function buildEventTags($event, $config, $row = null, $Itemid = 0)
	{
		$replaces   = [];
		$siteUrl    = EventbookingHelper::getSiteUrl();
		$timeFormat = $config->event_time_format ?: 'g:i a';

		$replaces['short_description']      = $event->short_description;
		$replaces['description']            = $event->description;
		$replaces['event_id']               = $event->id;
		$replaces['event_title']            = $event->title;
		$replaces['registered_event_title'] = $event->title;
		$replaces['alias']                  = $event->alias;
		$replaces['price_text']             = $event->price_text;

		if ($event->event_date == EB_TBC_DATE)
		{
			$replaces['event_date']         = Text::_('EB_TBC');
			$replaces['event_date_date']    = Text::_('EB_TBC');
			$replaces['event_date_time']    = Text::_('EB_TBC');
			$replaces['event_day']          = Text::_('EB_TBC');
			$replaces['event_month']        = Text::_('EB_TBC');
			$replaces['event_year']         = Text::_('EB_TBC');
			$replaces['event_month_short']  = Text::_('EB_TBC');
			$replaces['event_month_number'] = Text::_('EB_TBC');
		}
		else
		{
			if (strpos($event->event_date, '00:00:00') !== false)
			{
				$replaces['event_date'] = HTMLHelper::_('date', $event->event_date, $config->date_format, null);
			}
			else
			{
				$replaces['event_date'] = HTMLHelper::_('date', $event->event_date, $config->event_date_format, null);
			}

			$replaces['event_date_date']    = HTMLHelper::_('date', $event->event_date, $config->date_format, null);
			$replaces['event_date_time']    = HTMLHelper::_('date', $event->event_date, $timeFormat, null);
			$replaces['event_day']          = HTMLHelper::_('date', $event->event_date, 'd', null);
			$replaces['event_month']        = HTMLHelper::_('date', $event->event_date, 'F', null);
			$replaces['event_month_short']  = HTMLHelper::_('date', $event->event_date, 'M', null);
			$replaces['event_month_number'] = HTMLHelper::_('date', $event->event_date, 'm', null);
			$replaces['event_year']         = HTMLHelper::_('date', $event->event_date, 'Y', null);
		}

		if ((int) $event->event_end_date)
		{
			if (strpos($event->event_end_date, '00:00:00') !== false)
			{
				$replaces['event_end_date'] = HTMLHelper::_('date', $event->event_end_date, $config->date_format, null);
			}
			else
			{
				$replaces['event_end_date'] = HTMLHelper::_('date', $event->event_end_date, $config->event_date_format, null);
			}

			$replaces['event_end_date_date'] = HTMLHelper::_('date', $event->event_end_date, $config->date_format, null);
			$replaces['event_end_date_time'] = HTMLHelper::_('date', $event->event_end_date, $timeFormat, null);
		}
		else
		{
			$replaces['event_end_date']      = '';
			$replaces['event_end_date_date'] = '';
			$replaces['event_end_date_time'] = '';
		}

		$replaces['enable_cancel_registration'] = (int) $event->enable_cancel_registration;

		if ((int) $event->cancel_before_date)
		{
			$replaces['cancel_before_date'] = HTMLHelper::_('date', $event->cancel_before_date, $config->event_date_format, null);
		}
		else
		{
			$replaces['cancel_before_date'] = HTMLHelper::_('date', $event->cancel_before_date, $config->event_date_format, null);
		}

		if ((int) $event->cut_off_date)
		{
			$replaces['cut_off_date'] = HTMLHelper::_('date', $event->cut_off_date, $config->event_date_format, null);
		}
		else
		{
			$replaces['cut_off_date'] = HTMLHelper::_('date', $event->cut_off_date, $config->event_date_format, null);
		}

		$replaces['event_capacity'] = $event->event_capacity;

		if ($event && property_exists($event, 'total_registrants'))
		{
			$replaces['total_registrants'] = $event->total_registrants;

			if ($event->event_capacity > 0)
			{
				$replaces['available_place'] = $event->event_capacity - $event->total_registrants;
			}
			else
			{
				$replaces['available_place'] = '';
			}
		}

		$replaces['WAITING_LIST_QUANTITY'] = EventbookingHelperRegistration::countNumberWaitingList($event);

		$replaces['individual_price'] = EventbookingHelper::formatCurrency($event->individual_price, $config, $event->currency_symbol);

		if ($event->location_id > 0)
		{
			$rowLocation = EventbookingHelperDatabase::getLocation($event->location_id);

			$locationInformation = [];

			if ($rowLocation->address)
			{
				$locationInformation[] = $rowLocation->address;
			}

			$locationLink = $siteUrl . 'index.php?option=com_eventbooking&view=map&location_id=' . $rowLocation->id . '&Itemid=' . $Itemid;

			if (count($locationInformation))
			{
				$locationName = $rowLocation->name . ' (' . implode(', ', $locationInformation) . ')';
			}
			else
			{
				$locationName = $rowLocation->name;
			}

			$replaces['location']              = '<a href="' . $locationLink . '">' . $locationName . '</a>';
			$replaces['location_name_address'] = $locationName;
			$replaces['location_name']         = $rowLocation->name;
			$replaces['location_city']         = $rowLocation->city;
			$replaces['location_state']        = $rowLocation->state;
			$replaces['location_address']      = $rowLocation->address;
			$replaces['location_description']  = $rowLocation->description;

			if ($rowLocation->image)
			{
				$replaces['location_image'] = EventbookingHelperHtml::getCleanImagePath($rowLocation->image);
			}
			else
			{
				$replaces['location_image'] = '';
			}
		}
		else
		{
			$replaces['location']              = '';
			$replaces['location_name_address'] = '';
			$replaces['location_name']         = '';
			$replaces['location_city']         = '';
			$replaces['location_state']        = '';
			$replaces['location_address']      = '';
			$replaces['location_description']  = '';
			$replaces['location_image']        = '';
		}

		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			EventbookingHelperData::prepareCustomFieldsData([$event], false);

			foreach ($event->paramData as $customFieldName => $param)
			{
				$replaces[strtoupper($customFieldName)] = $param['value'];
			}
		}

		// Speakers
		$db    = Factory::getDbo();
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
			$replaces['speaker_' . $rowSpeaker->id] = $rowSpeaker->name;
			$speakerNames[]                         = $rowSpeaker->name;
		}

		$replaces['speakers'] = implode(', ', $speakerNames);

		if (!$Itemid)
		{
			$Itemid = EventbookingHelper::getItemid();
		}

		if (Factory::getApplication()->isClient('site'))
		{
			$defaultMenuId = $Itemid;
		}
		else
		{
			$defaultMenuId = EventbookingHelper::getItemid();
		}

		$replaces['event_link'] = Route::link(
			'site',
			EventbookingHelperRoute::getEventRoute($event->id, $event->main_category_id, $defaultMenuId),
			false,
			0,
			true
		);

		$replaces['category_link'] = Route::link(
			'site',
			EventbookingHelperRoute::getCategoryRoute($event->main_category_id, $defaultMenuId),
			false,
			0,
			true
		);

		if ($row && is_object($row))
		{
			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		}
		else
		{
			$fieldSuffix = EventbookingHelper::getFieldSuffix();
		}

		$query->clear()
			->select('a.id, a.name, a.description')
			->from('#__eb_categories AS a')
			->innerJoin('#__eb_event_categories AS b ON a.id = b.category_id')
			->where('b.event_id = ' . $event->id)
			->order('b.id');

		if ($fieldSuffix)
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['a.name', 'a.description'], $fieldSuffix);
		}

		$db->setQuery($query);
		$categories    = $db->loadObjectList();
		$categoryNames = [];

		foreach ($categories as $category)
		{
			$categoryNames[] = $category->name;

			if ($category->id == $event->main_category_id)
			{
				$replaces['main_category_name']        = $category->name;
				$replaces['main_category_description'] = $category->description;
			}
		}

		$replaces['main_category_id'] = $event->main_category_id;
		$replaces['category_name']    = implode(', ', $categoryNames);

		if ($event->created_by > 0)
		{
			$creator = Factory::getUser($event->created_by);
		}

		if (!empty($creator->id))
		{
			$replaces['event_creator_name']     = $creator->name;
			$replaces['event_creator_username'] = $creator->username;
			$replaces['event_creator_email']    = $creator->email;
			$replaces['event_creator_id']       = $creator->id;
		}
		else
		{
			$replaces['event_creator_name']     = '';
			$replaces['event_creator_username'] = '';
			$replaces['event_creator_email']    = '';
			$replaces['event_creator_id']       = '';
		}

		$thumbUrl = '';
		$imageUrl = '';

		if ($event->thumb && file_exists(JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $event->thumb))
		{
			$thumbUrl = 'media/com_eventbooking/images/thumbs/' . $event->thumb;

			if ($event->image && file_exists(JPATH_ROOT . '/' . $event->image))
			{
				$imageUrl = $event->image;
			}
			elseif (file_exists(JPATH_ROOT . '/media/com_eventbooking/images/' . $event->thumb))
			{
				$imageUrl = 'media/com_eventbooking/images/' . $event->thumb;
			}
			else
			{
				$imageUrl = 'media/com_eventbooking/images/thumbs/' . $event->thumb;
			}
		}

		if ($thumbUrl)
		{
			$replaces['EVENT_THUMB_IMAGE_URL'] = $thumbUrl;
			$replaces['EVENT_THUMB_IMAGE']     = '<img src="' . $thumbUrl . '" />';
		}
		else
		{
			$replaces['EVENT_THUMB_IMAGE_URL'] = '';
			$replaces['EVENT_THUMB_IMAGE']     = '';
		}

		if ($imageUrl)
		{
			$replaces['EVENT_IMAGE_URL'] = $imageUrl;
			$replaces['EVENT_IMAGE']     = '<img src="' . $imageUrl . '" />';
		}
		else
		{
			$replaces['EVENT_IMAGE_URL'] = '';
			$replaces['EVENT_IMAGE']     = '';
		}

		return $replaces;
	}

	/**
	 * Build tags array to use to replace the tags use in email & messages
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADForm                      $form
	 * @param   EventbookingTableEvent       $event
	 * @param   RADConfig                    $config
	 * @param   bool                         $loadCss
	 *
	 * @return array
	 */
	public static function buildTags($row, $form, $event, $config, $loadCss = true)
	{
		$app     = Factory::getApplication();
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$siteUrl = EventbookingHelper::getSiteUrl();

		if ($row->language && $row->language != '*' && LanguageHelper::exists($row->language))
		{
			$language = Language::getInstance($row->language);
		}
		else
		{
			$language = Factory::getLanguage();
		}

		$monthNames = [
			$language->_('JANUARY'),
			$language->_('FEBRUARY'),
			$language->_('MARCH'),
			$language->_('APRIL'),
			$language->_('MAY'),
			$language->_('JUNE'),
			$language->_('JULY'),
			$language->_('AUGUST'),
			$language->_('SEPTEMBER'),
			$language->_('OCTOBER'),
			$language->_('NOVEMBER'),
			$language->_('DECEMBER'),
		];

		$monthNamesShort = [
			$language->_('JANUARY_SHORT'),
			$language->_('FEBRUARY_SHORT'),
			$language->_('MARCH_SHORT'),
			$language->_('APRIL_SHORT'),
			$language->_('MAY_SHORT'),
			$language->_('JUNE_SHORT'),
			$language->_('JULY_SHORT'),
			$language->_('AUGUST_SHORT'),
			$language->_('SEPTEMBER_SHORT'),
			$language->_('OCTOBER_SHORT'),
			$language->_('NOVEMBER_SHORT'),
			$language->_('DECEMBER_SHORT'),
		];

		$form->handleFieldsDependOnPaymentMethod($row->payment_method);

		$task          = $app->input->getCmd('task');
		$defaultItemId = EventbookingHelper::getDefaultItemidForRegistration($row);

		if ($app->isClient('administrator') || ($task == 'payment_confirm' && !$app->input->get->getInt('Itemid')))
		{
			$Itemid = EventbookingHelperRoute::getEventMenuId($event->id, $event->main_category_id, $defaultItemId, $row->language);
		}
		else
		{
			$defaultItemId = Factory::getApplication()->input->getInt('Itemid', 0) ?: $defaultItemId;

			$Itemid = EventbookingHelperRoute::getEventMenuId($event->id, $event->main_category_id, $defaultItemId, $row->language);
		}

		$publicMenuItemId = $defaultItemId;

		$active = $app->getMenu('site')->getItem($Itemid);

		if ($active)
		{
			$langLink = EventbookingHelper::getLangLink($active->language);

			if ($active->access == 1)
			{
				$publicMenuItemId = $Itemid;
			}
		}
		else
		{
			$langLink = '';
		}

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$replaces = static::buildEventTags($event, $config, $row, $Itemid);

		// Event information
		if ($config->multiple_booking)
		{
			$query->select('event_id')
				->from('#__eb_registrants')
				->where("(id = $row->id OR cart_id = $row->id)")
				->order('id');
			$db->setQuery($query);
			$eventIds = $db->loadColumn();

			$query->clear()
				->select($db->quoteName('title' . $fieldSuffix, 'title'))
				->from('#__eb_events')
				->where('id IN (' . implode(',', $eventIds) . ')')
				->order('FIND_IN_SET(id, "' . implode(',', $eventIds) . '")');

			$db->setQuery($query);
			$replaces['event_title'] = implode(', ', $db->loadColumn());
		}

		$replaces['couponCode']         = $replaces['coupon_code'] = $replaces['coupon_note'] = '';
		$replaces['username']           = '';
		$replaces['TICKET_TYPES']       = '';
		$replaces['TICKET_TYPES_TABLE'] = '';
		$replaces['TICKET_TYPE']        = '';
		$replaces['TICKET_PRICE']       = 0;
		$replaces['user_id']            = $row->user_id;
		$replaces['name']               = trim($row->first_name . ' ' . $row->last_name);
		$replaces['language']           = $row->language;

		// Form fields
		$fields = $form->getFields();

		foreach ($fields as $field)
		{
			if ($field->hideOnDisplay)
			{
				$fieldValue = '';
			}
			else
			{
				if (is_string($field->value) && is_array(json_decode($field->value)))
				{
					$fieldValue = implode(', ', json_decode($field->value));
				}
				elseif ($field->type == 'Heading')
				{
					$fieldValue = $field->title;
				}
				elseif ($field->type == 'Message')
				{
					$fieldValue = $field->description;
				}
				elseif ($field->type == 'Textarea')
				{
					$fieldValue = nl2br($field->value);
				}
				else
				{
					$fieldValue = $field->value;
				}
			}

			if ($fieldValue && $field->type == 'Date')
			{
				$date = Factory::getDate($fieldValue);

				if ($date)
				{
					$dateFormat = $config->date_field_format ?: '%Y-%m-%d';
					$dateFormat = str_replace('%', '', $dateFormat);
					$fieldValue = $date->format($dateFormat);
				}
			}

			$replaces[$field->name] = $fieldValue;
		}

		// Add support for group members name tags
		if ($row->is_group_billing)
		{
			$groupMembersNames = [];

			$query->clear()
				->select('first_name, last_name')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id)
				->order('id');
			$db->setQuery($query);
			$rowMembers = $db->loadObjectList();

			foreach ($rowMembers as $rowMember)
			{
				$groupMembersNames[] = trim($rowMember->first_name . ' ' . $rowMember->last_name);
			}
		}
		else
		{
			$groupMembersNames = [trim($row->first_name . ' ' . $row->last_name)];
		}

		$replaces['group_members_names'] = implode(', ', $groupMembersNames);

		if ($row->coupon_id)
		{
			$query->clear()
				->select('a.*')
				->from('#__eb_coupons AS a')
				->where('a.id=' . (int) $row->coupon_id);
			$db->setQuery($query);
			$coupon = $db->loadObject();

			if ($coupon)
			{
				$replaces['coupon_code'] = $replaces['couponCode'] = $coupon->code;
				$replaces['coupon_note'] = $coupon->note;
			}
		}

		if ($row->user_id)
		{
			$query->clear()
				->select('username')
				->from('#__users')
				->where('id = ' . $row->user_id);
			$db->setQuery($query);
			$replaces['username'] = $db->loadResult();
		}

		if ($config->multiple_booking)
		{
			//Amount calculation
			$query->clear()
				->select('SUM(total_amount)')
				->from('#__eb_registrants')
				->where("(id = $row->id OR cart_id = $row->id)");
			$db->setQuery($query);
			$totalAmount = $db->loadResult();

			$query->clear('select')
				->select('SUM(tax_amount)');
			$db->setQuery($query);
			$taxAmount = $db->loadResult();

			$query->clear('select')
				->select('SUM(payment_processing_fee)');
			$db->setQuery($query);
			$paymentProcessingFee = $db->loadResult();

			$query->clear('select')
				->select('SUM(discount_amount)');
			$db->setQuery($query);
			$discountAmount = $db->loadResult();

			$query->clear('select')
				->select('SUM(late_fee)');
			$db->setQuery($query);
			$lateFee = $db->loadResult();

			$amount = $totalAmount - $discountAmount + $paymentProcessingFee + $taxAmount + $lateFee;

			if ($row->payment_status == 1)
			{
				$depositAmount = 0;
				$dueAmount     = 0;
			}
			else
			{
				$query->clear('select')
					->select('SUM(deposit_amount)');
				$db->setQuery($query);
				$depositAmount = $db->loadResult();

				$dueAmount = $amount - $depositAmount;
			}

			$replaces['total_amount']                = EventbookingHelper::formatCurrency($totalAmount, $config, $event->currency_symbol);
			$replaces['total_amount_minus_discount'] = EventbookingHelper::formatCurrency(
				$totalAmount - $discountAmount,
				$config,
				$event->currency_symbol
			);
			$replaces['tax_amount']                  = EventbookingHelper::formatCurrency($taxAmount, $config, $event->currency_symbol);
			$replaces['discount_amount']             = EventbookingHelper::formatCurrency($discountAmount, $config, $event->currency_symbol);
			$replaces['late_fee']                    = EventbookingHelper::formatCurrency($lateFee, $config, $event->currency_symbol);
			$replaces['payment_processing_fee']      = EventbookingHelper::formatCurrency($paymentProcessingFee, $config, $event->currency_symbol);
			$replaces['amount']                      = EventbookingHelper::formatCurrency($amount, $config, $event->currency_symbol);
			$replaces['deposit_amount']              = EventbookingHelper::formatCurrency($depositAmount, $config, $event->currency_symbol);
			$replaces['due_amount']                  = EventbookingHelper::formatCurrency($dueAmount, $config, $event->currency_symbol);

			$replaces['amt_total_amount']           = EventbookingHelper::roundAmount($totalAmount, $config);
			$replaces['amt_tax_amount']             = EventbookingHelper::roundAmount($taxAmount, $config);
			$replaces['amt_discount_amount']        = EventbookingHelper::roundAmount($discountAmount, $config);
			$replaces['amt_late_fee']               = EventbookingHelper::roundAmount($lateFee, $config);
			$replaces['amt_amount']                 = EventbookingHelper::roundAmount($amount, $config);
			$replaces['amt_payment_processing_fee'] = EventbookingHelper::roundAmount($paymentProcessingFee, $config);
			$replaces['amt_deposit_amount']         = EventbookingHelper::roundAmount($depositAmount, $config);
			$replaces['amt_due_amount']             = EventbookingHelper::roundAmount($dueAmount, $config);

			// Auto coupon code
			$query->clear()
				->select('auto_coupon_coupon_id')
				->from('#__eb_registrants')
				->where('(id = ' . $row->id . ' OR cart_id = ' . $row->id . ')')
				->where('auto_coupon_coupon_id > 0');
			$db->setQuery($query);
			$couponIds = $db->loadColumn();

			if (count($couponIds))
			{
				$query->clear()
					->select($db->quoteName('code'))
					->from('#__eb_coupons')
					->where('id IN (' . implode(',', $couponIds) . ')');
				$db->setQuery($query);
				$replaces['AUTO_COUPON_CODES'] = implode(', ', $db->loadColumn());
			}
			else
			{
				$replaces['AUTO_COUPON_CODES'] = '';
			}
		}
		else
		{
			$replaces['total_amount']                = EventbookingHelper::formatCurrency($row->total_amount, $config, $event->currency_symbol);
			$replaces['total_amount_minus_discount'] = EventbookingHelper::formatCurrency(
				$row->total_amount - $row->discount_amount,
				$config,
				$event->currency_symbol
			);
			$replaces['tax_amount']                  = EventbookingHelper::formatCurrency($row->tax_amount, $config, $event->currency_symbol);
			$replaces['discount_amount']             = EventbookingHelper::formatCurrency($row->discount_amount, $config, $event->currency_symbol);
			$replaces['late_fee']                    = EventbookingHelper::formatCurrency($row->late_fee, $config, $event->currency_symbol);
			$replaces['payment_processing_fee']      = EventbookingHelper::formatCurrency(
				$row->payment_processing_fee,
				$config,
				$event->currency_symbol
			);
			$replaces['amount']                      = EventbookingHelper::formatCurrency($row->amount, $config, $event->currency_symbol);

			$replaces['total_amount_without_currency']           = EventbookingHelper::formatAmount($row->total_amount, $config);
			$replaces['tax_amount_without_currency']             = EventbookingHelper::formatAmount($row->tax_amount, $config);
			$replaces['discount_amount_without_currency']        = EventbookingHelper::formatAmount($row->discount_amount, $config);
			$replaces['late_fee_without_currency']               = EventbookingHelper::formatAmount($row->late_fee, $config);
			$replaces['payment_processing_fee_without_currency'] = EventbookingHelper::formatAmount($row->payment_processing_fee, $config);
			$replaces['amount_without_currency']                 = EventbookingHelper::formatAmount($row->amount, $config);

			$replaces['amt_total_amount']           = EventbookingHelper::roundAmount($row->total_amount, $config);
			$replaces['amt_tax_amount']             = EventbookingHelper::roundAmount($row->tax_amount, $config);
			$replaces['amt_discount_amount']        = EventbookingHelper::roundAmount($row->discount_amount, $config);
			$replaces['amt_late_fee']               = EventbookingHelper::roundAmount($row->late_fee, $config);
			$replaces['amt_payment_processing_fee'] = EventbookingHelper::roundAmount($row->payment_processing_fee, $config);
			$replaces['amt_amount']                 = EventbookingHelper::roundAmount($row->amount, $config);

			if ($row->payment_status == 1)
			{
				$depositAmount = 0;
				$dueAmount     = 0;

				if ($row->published == 1)
				{
					$replaces['PAID_AMOUNT'] = $replaces['amount'];
				}
				else
				{
					$replaces['PAID_AMOUNT'] = 0;
				}
			}
			else
			{
				$depositAmount = $row->deposit_amount;
				$dueAmount     = $row->amount - $row->deposit_amount;

				if ($row->published == 1)
				{
					$replaces['PAID_AMOUNT'] = EventbookingHelper::formatCurrency($depositAmount, $config, $event->currency_symbol);
				}
				else
				{
					$replaces['PAID_AMOUNT'] = 0;
				}
			}

			if ($row->published == 0)
			{
				// Deposit paid
				if ($row->payment_status == 2)
				{
					$replaces['REGISTRATION_DUE_AMOUNT']  = EventbookingHelper::formatCurrency($dueAmount, $config, $event->currency_symbol);
					$replaces['REGISTRATION_PAID_AMOUNT'] = EventbookingHelper::formatCurrency($depositAmount, $config, $event->currency_symbol);
				}
				else
				{
					$replaces['REGISTRATION_DUE_AMOUNT']  = $replaces['amount'];
					$replaces['REGISTRATION_PAID_AMOUNT'] = EventbookingHelper::formatCurrency(0, $config, $event->currency_symbol);
				}
			}
			else
			{
				$replaces['REGISTRATION_DUE_AMOUNT']  = EventbookingHelper::formatCurrency(0, $config, $event->currency_symbol);
				$replaces['REGISTRATION_PAID_AMOUNT'] = $replaces['amount'];
			}

			$replaces['deposit_amount']                  = EventbookingHelper::formatCurrency($depositAmount, $config, $event->currency_symbol);
			$replaces['due_amount']                      = EventbookingHelper::formatCurrency($dueAmount, $config, $event->currency_symbol);
			$replaces['deposit_amount_without_currency'] = EventbookingHelper::formatAmount($depositAmount, $config);
			$replaces['due_amount_without_currency']     = EventbookingHelper::formatAmount($dueAmount, $config);

			$replaces['amt_deposit_amount'] = EventbookingHelper::roundAmount($depositAmount, $config);
			$replaces['amt_due_amount']     = EventbookingHelper::roundAmount($dueAmount, $config);

			// Ticket Types
			if ($event->has_multiple_ticket_types)
			{
				$query->clear()
					->select('id, title, price')
					->from('#__eb_ticket_types')
					->where('event_id = ' . $event->id);

				if ($fieldSuffix)
				{
					EventbookingHelperDatabase::getMultilingualFieldsUseDefaultLanguageData($query, ['title'], $fieldSuffix);
				}

				$db->setQuery($query);
				$ticketTypes = $db->loadObjectList('id');

				$query->clear()
					->select('ticket_type_id, quantity')
					->from('#__eb_registrant_tickets')
					->where('registrant_id = ' . $row->id);
				$db->setQuery($query);
				$registrantTickets = $db->loadObjectList();

				$ticketsOutput = [];

				foreach ($registrantTickets as $registrantTicket)
				{
					$ticketsOutput[]          = Text::_($ticketTypes[$registrantTicket->ticket_type_id]->title) . ': ' . $registrantTicket->quantity;
					$replaces['TICKET_TYPE']  = Text::_($ticketTypes[$registrantTicket->ticket_type_id]->title);
					$replaces['TICKET_PRICE'] = EventbookingHelper::formatAmount($ticketTypes[$registrantTicket->ticket_type_id]->price, $config);
				}

				$replaces['TICKET_TYPES'] = implode(', ', $ticketsOutput);

				$query->clear()
					->select('a.*, b.quantity')
					->from('#__eb_ticket_types AS a')
					->innerJoin('#__eb_registrant_tickets AS b ON a.id = ticket_type_id')
					->where('b.registrant_id = ' . $row->id);

				if ($fieldSuffix)
				{
					EventbookingHelperDatabase::getMultilingualFieldsUseDefaultLanguageData($query, ['a.title', 'a.description'], $fieldSuffix);
				}

				$db->setQuery($query);
				$replaces['TICKET_TYPES_TABLE'] = EventbookingHelperHtml::loadSharedLayout(
					'emailtemplates/tickettypes.php',
					['ticketTypes' => $db->loadObjectList(), 'eventId' => $row->event_id]
				);
			}
		}

		$rate                          = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'getRegistrationRate',
			[$row->event_id, $row->number_registrants]
		);
		$replaces['registration_rate'] = EventbookingHelper::formatCurrency($rate, $config, $event->currency_symbol);

		// Registration record related tags
		$replaces['number_registrants'] = $row->number_registrants;
		$replaces['invoice_number']     = EventbookingHelper::callOverridableHelperMethod(
			'Helper',
			'formatInvoiceNumber',
			[$row->invoice_number, $config, $row]
		);

		$replaces['checked_in_count']     = $row->checked_in_count;
		$replaces['transaction_id']       = $row->transaction_id;
		$replaces['registration_code']    = $row->registration_code;
		$replaces['ticket_code']          = $row->ticket_code;
		$replaces['ticket_qrcode_string'] = $row->ticket_qrcode;
		$replaces['id']                   = $row->id;
		$replaces['registrant_id']        = $row->id;
		$replaces['date']                 = HTMLHelper::_('date', 'Now', $config->date_format);
		$replaces['current_month_number'] = HTMLHelper::_('date', 'Now', 'm');
		$currentMonthNumber               = HTMLHelper::_('date', 'Now', 'n');
		$replaces['current_month']        = $monthNames[$currentMonthNumber - 1];
		$replaces['current_month_short']  = $monthNamesShort[$currentMonthNumber - 1];
		$replaces['current_year']         = HTMLHelper::_('date', 'Now', 'Y');

		if ((int) $row->payment_date)
		{
			$replaces['payment_date'] = HTMLHelper::_('date', $row->payment_date, $config->date_format);
		}
		else
		{
			$replaces['payment_date'] = '';
		}

		if ((int) $row->register_date)
		{
			$replaces['register_date']              = HTMLHelper::_('date', $row->register_date, $config->date_format);
			$replaces['register_date_time']         = HTMLHelper::_('date', $row->register_date, $config->date_format . ' H:i:s');
			$monthNumber                            = HTMLHelper::_('date', $row->register_date, 'n');
			$replaces['register_date_month_number'] = HTMLHelper::_('date', $row->register_date, 'm');
			$replaces['register_date_month']        = $monthNames[$monthNumber - 1];
			$replaces['register_date_month_short']  = $monthNamesShort[$monthNumber - 1];
			$replaces['register_date_year']         = HTMLHelper::_('date', $row->register_date, 'Y');
		}
		else
		{
			$replaces['register_date']              = '';
			$replaces['register_date_time']         = '';
			$replaces['register_date_month_number'] = '';
			$replaces['register_date_month']        = '';
			$replaces['register_date_month_short']  = '';
			$replaces['register_date_year']         = '';
		}

		if ((int) $row->checked_in_at)
		{
			$replaces['checked_in_at'] = HTMLHelper::_('date', $row->checked_in_at, $config->date_format . ' H:i');
		}
		else
		{
			$replaces['checked_in_at'] = '';
		}

		if ((int) $row->registration_cancel_date)
		{
			$replaces['registration_cancel_date'] = HTMLHelper::_('date', $row->registration_cancel_date, $config->date_format . ' H:i');
		}
		else
		{
			$replaces['registration_cancel_date'] = '';
		}

		if ($row->ticket_number > 0)
		{
			$replaces['ticket_number'] = EventbookingHelperTicket::formatTicketNumber($event->ticket_prefix, $row->ticket_number, $config);
		}
		else
		{
			$replaces['ticket_number'] = '';
		}

		$method = EventbookingHelperPayments::loadPaymentMethod($row->payment_method);

		if ($method)
		{
			$replaces['payment_method']      = Text::_($method->title);
			$replaces['payment_method_name'] = $row->payment_method;
		}
		else
		{
			$replaces['payment_method']      = '';
			$replaces['payment_method_name'] = '';
		}

		// Registration detail tags
		$replaces['registration_detail'] = static::getEmailContent($config, $row, $loadCss, $form);

		// Cancel registraiton link
		if ($event->enable_cancel_registration)
		{
			$replaces['cancel_registration_link'] = $siteUrl . 'index.php?option=com_eventbooking&task=cancel_registration_confirm&cancel_code=' . $row->registration_code . '&Itemid=' . $publicMenuItemId . $langLink;
		}
		else
		{
			$replaces['cancel_registration_link'] = '';
		}

		$replaces['payment_link']              = $siteUrl . 'index.php?option=com_eventbooking&view=payment&layout=registration&order_number=' . $row->registration_code . '&Itemid=' . $publicMenuItemId . $langLink;
		$replaces['deposit_payment_link']      = $siteUrl . 'index.php?option=com_eventbooking&view=payment&layout=default&order_number=' . $row->registration_code . '&Itemid=' . $publicMenuItemId . $langLink;
		$replaces['download_certificate_link'] = $siteUrl . 'index.php?option=com_eventbooking&task=registrant.download_certificate&download_code=' . $row->registration_code . '&Itemid=' . $publicMenuItemId . $langLink;
		$replaces['download_ticket_link']      = $siteUrl . 'index.php?option=com_eventbooking&task=registrant.download_ticket&download_code=' . $row->registration_code . '&Itemid=' . $publicMenuItemId . $langLink;
		$replaces['registration_detail_link']  = $siteUrl . 'administrator/index.php?option=com_eventbooking&view=registrant&id=' . $row->id;

		// Custom Fee Fields Fee Values
		$params          = new Registry($row->params);
		$fieldsFeeValues = (array) $params->get('fields_fee_amount', []);

		foreach ($fieldsFeeValues as $fieldName => $feeValue)
		{
			$replaces[$fieldName . '_fee_value'] = EventbookingHelper::formatAmount($feeValue, $config);
		}

		// Make sure if a custom field is not available, the used tag would be empty
		$query->clear()
			->select('*')
			->from('#__eb_fields')
			->where('published = 1');
		$db->setQuery($query);
		$allFields = $db->loadObjectList();

		foreach ($allFields as $field)
		{
			if (!isset($replaces[$field->name]))
			{
				if ($field->is_core)
				{
					$replaces[$field->name] = $row->{$field->name};
				}
				else
				{
					$replaces[$field->name] = '';
				}
			}

			if ($field->fee_field && !isset($replaces[$field->name . '_fee_value']))
			{
				$replaces[$field->name . '_fee_value'] = EventbookingHelper::formatAmount(0, $config);
			}
		}

		if (!isset($replaces['name']))
		{
			$replaces['name'] = trim($row->first_name . ' ' . $row->last_name);
		}

		// Registration status tag
		switch ($row->published)
		{
			case 0 :
				$replaces['REGISTRATION_STATUS'] = Text::_('EB_PENDING');
				break;
			case 1 :
				$replaces['REGISTRATION_STATUS'] = Text::_('EB_PAID');
				break;
			case 2 :
				$replaces['REGISTRATION_STATUS'] = Text::_('EB_CANCELLED');
				break;
			case 3:
				$replaces['REGISTRATION_STATUS'] = Text::_('EB_WAITING_LIST');
				break;
			default:
				$replaces['REGISTRATION_STATUS'] = '';
				break;
		}

		if ($row->payment_status == 0)
		{
			$replaces['PAYMENT_STATUS'] = Text::_('EB_PARTIAL_PAYMENT');
		}
		elseif ($row->payment_status == 2)
		{
			$replaces['PAYMENT_STATUS'] = Text::_('EB_DEPOSIT_PAID');
		}
		else
		{
			$replaces['PAYMENT_STATUS'] = Text::_('EB_FULL_PAYMENT');
		}

		$replaces['PAYMENT_STATUS_VALUE'] = $row->payment_status;

		// Auto coupon
		$replaces['AUTO_COUPON_CODE'] = '';

		if ($row->auto_coupon_coupon_id > 0)
		{
			$query->clear()
				->select($db->quoteName('code'))
				->from('#__eb_coupons')
				->where('id = ' . $row->auto_coupon_coupon_id);
			$db->setQuery($query);
			$replaces['AUTO_COUPON_CODE'] = $db->loadResult();
		}

		$replaces['published']      = $row->published;
		$replaces['payment_status'] = $row->payment_status;

		// Subscribe to newsletter
		if ($row->subscribe_newsletter)
		{
			$replaces['SUBSCRIBE_NEWSLETTER'] = Text::_('EB_SUBSCRIBED_TO_NEWSLETTER');
		}
		else
		{
			$replaces['SUBSCRIBE_NEWSLETTER'] = Text::_('EB_DO_NOT_SUBSCRIBE_TO_NEWSLETTER');
		}

		if ($event->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $event->collect_member_information;
		}

		// Group members tag
		if ($row->is_group_billing && $collectMemberInformation)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id)
				->order('id');
			$db->setQuery($query);
			$rowMembers                = $db->loadObjectList();
			$memberFormFields          = EventbookingHelperRegistration::getFormFields($row->event_id, 2, $row->language, $row->user_id);
			$replaces['group_members'] = EventbookingHelperHtml::loadSharedLayout(
				'emailtemplates/tmpl/email_group_members.php',
				['rowMembers' => $rowMembers, 'rowFields' => $memberFormFields]
			);
		}
		else
		{
			$replaces['group_members'] = '';
		}

		$replaces['tax_rate'] = EventbookingHelper::formatAmount($row->tax_rate, $config) . '%';

		if (is_string($row->params) && is_array(json_decode($row->params, true)))
		{
			$params = json_decode($row->params, true);

			foreach ($params as $key => $value)
			{
				if (!array_key_exists($key, $replaces) && is_scalar($value))
				{
					$replaces[$key] = $value;
				}
			}
		}

		// Basic support for billing data
		if ($row->group_id > 0)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $row->group_id);
			$db->setQuery($query);
			$rowBilling = $db->loadObject();
		}
		else
		{
			$rowBilling = $row;
		}

		$billingFields = [
			'first_name',
			'last_name',
			'organization',
			'address',
			'address2',
			'city',
			'state',
			'zip',
			'country',
			'phone',
			'fax',
			'email',
			'comment',
		];

		foreach ($billingFields as $billingField)
		{
			if (!isset($replaces['billing_' . $billingField]))
			{
				$replaces['billing_' . $billingField] = $rowBilling->{$billingField};
			}
		}

		// Common tags
		if ($config->common_tags)
		{
			$commonTags = json_decode($config->common_tags, true);

			foreach ($commonTags as $commonTag)
			{
				$tagName = $commonTag['name'];

				if (!is_string($tagName) || trim($tagName) === '')
				{
					continue;
				}

				$tagName = EventbookingHelper::normalizeCommonTagName($tagName);

				$replaces[$tagName] = $commonTag['value'];
			}
		}

		return $replaces;
	}

	/**
	 * Method to build tags for replacing in SMS message
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return array
	 */
	public static function buildSMSTags($row)
	{
		$config     = EventbookingHelper::getConfig();
		$timeFormat = $config->event_time_format ?: 'g:i a';

		$replaces = [];

		$replaces['event_id']    = $row->event_id;
		$replaces['event_title'] = $row->event_title;

		if ($row->event_date == EB_TBC_DATE)
		{
			$replaces['event_date']      = Text::_('EB_TBC');
			$replaces['event_date_date'] = Text::_('EB_TBC');
			$replaces['event_date_time'] = Text::_('EB_TBC');
		}
		else
		{
			$replaces['event_date']      = HTMLHelper::_('date', $row->event_date, $config->event_date_format, null);
			$replaces['event_date_date'] = HTMLHelper::_('date', $row->event_date, $config->date_format, null);
			$replaces['event_date_time'] = HTMLHelper::_('date', $row->event_date, $timeFormat, null);
		}

		if ((int) $row->event_end_date)
		{
			$replaces['event_end_date']      = HTMLHelper::_('date', $row->event_end_date, $config->event_date_format, null);
			$replaces['event_end_date_date'] = HTMLHelper::_('date', $row->event_end_date, $config->date_format, null);
			$replaces['event_end_date_time'] = HTMLHelper::_('date', $row->event_end_date, $timeFormat, null);
		}
		else
		{
			$replaces['event_end_date']      = '';
			$replaces['event_end_date_date'] = '';
			$replaces['event_end_date_time'] = '';
		}

		$replaces['location_name']    = $row->location_name;
		$replaces['location_address'] = $row->location_address;

		$fields = [
			'first_name',
			'last_name',
			'organization',
			'address',
			'address2',
			'city',
			'zip',
			'state',
			'country',
			'phone',
			'fax',
			'email',
			'comment',
		];

		foreach ($fields as $field)
		{
			$replaces[$field] = $row->{$field};
		}

		return $replaces;
	}

	/**
	 * Get email content, used for [REGISTRATION_DETAIL] tag
	 *
	 * @param   RADConfig                    $config
	 * @param   EventbookingTableRegistrant  $row
	 * @param   bool                         $loadCss
	 * @param   RADForm                      $form
	 * @param   bool                         $toAdmin
	 *
	 * @return string
	 */
	public static function getEmailContent($config, $row, $loadCss = true, $form = null, $toAdmin = false)
	{
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$data   = [];
		$Itemid = Factory::getApplication()->input->getInt('Itemid', 0);

		if ($config->multiple_booking)
		{
			if ($loadCss)
			{
				$layout = 'email_cart.php';
			}
			else
			{
				$layout = 'cart.php';
			}
		}
		else
		{
			if ($row->is_group_billing)
			{
				if ($loadCss)
				{
					$layout = 'email_group_detail.php';
				}
				else
				{
					$layout = 'group_detail.php';
				}
			}
			else
			{
				if ($loadCss)
				{
					$layout = 'email_individual_detail.php';
				}
				else
				{
					$layout = 'individual_detail.php';
				}
			}
		}

		if (!$loadCss)
		{
			// Need to pass bootstrap helper
			$data['bootstrapHelper'] = EventbookingHelperBootstrap::getInstance();
		}

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		if ($config->multiple_booking)
		{
			$data['row']    = $row;
			$data['config'] = $config;
			$data['Itemid'] = $Itemid;

			$query->select('a.*, b.event_date, b.event_end_date, b.custom_fields, l.address AS location_address')
				->select($db->quoteName(['b.title' . $fieldSuffix, 'l.name' . $fieldSuffix], ['title', 'location_name']))
				->from('#__eb_registrants AS a')
				->innerJoin('#__eb_events AS b ON a.event_id = b.id')
				->leftJoin('#__eb_locations AS l On b.location_id = l.id')
				->where("(a.id = $row->id OR a.cart_id = $row->id)")
				->order('a.id');
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$query->clear()
				->select('SUM(total_amount)')
				->from('#__eb_registrants')
				->where("(id = $row->id OR cart_id = $row->id)");
			$db->setQuery($query);
			$totalAmount = $db->loadResult();

			$query->clear('select')
				->select('SUM(tax_amount)');
			$db->setQuery($query);
			$taxAmount = $db->loadResult();

			$query->clear('select')
				->select('SUM(discount_amount)');
			$db->setQuery($query);
			$discountAmount = $db->loadResult();

			$query->clear('select')
				->select('SUM(late_fee)');
			$db->setQuery($query);
			$lateFee = $db->loadResult();

			$query->clear('select')
				->select('SUM(payment_processing_fee)');
			$db->setQuery($query);
			$paymentProcessingFee = $db->loadResult();

			$amount = $totalAmount + $paymentProcessingFee - $discountAmount + $taxAmount + $lateFee;

			$query->clear('select')
				->select('SUM(deposit_amount)');
			$db->setQuery($query);
			$depositAmount = $db->loadResult();

			//Added support for custom field feature
			$data['discountAmount']       = $discountAmount;
			$data['lateFee']              = $lateFee;
			$data['totalAmount']          = $totalAmount;
			$data['items']                = $rows;
			$data['amount']               = $amount;
			$data['taxAmount']            = $taxAmount;
			$data['paymentProcessingFee'] = $paymentProcessingFee;
			$data['depositAmount']        = $depositAmount;
			$data['form']                 = $form;
		}
		else
		{
			$rowEvent    = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
			$rowLocation = EventbookingHelperDatabase::getLocation($rowEvent->location_id, $fieldSuffix);

			$data['row']         = $row;
			$data['rowEvent']    = $rowEvent;
			$data['config']      = $config;
			$data['rowLocation'] = $rowLocation;
			$data['form']        = $form;

			$collectMemberInformation = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'isCollectMembersInformation',
				[$rowEvent, $config]
			);

			if ($row->is_group_billing && $collectMemberInformation)
			{
				$query->clear()
					->select('*')
					->from('#__eb_registrants')
					->where('group_id = ' . $row->id)
					->order('id');
				$db->setQuery($query);
				$rowMembers = $db->loadObjectList();
			}
			else
			{
				$rowMembers = [];
			}

			$data['rowMembers'] = $rowMembers;

			if ($rowEvent->has_multiple_ticket_types)
			{
				$query->clear()
					->select('a.*, b.quantity')
					->from('#__eb_ticket_types AS a')
					->innerJoin('#__eb_registrant_tickets AS b ON a.id = ticket_type_id')
					->where('b.registrant_id = ' . $row->id);

				if ($fieldSuffix)
				{
					EventbookingHelperDatabase::getMultilingualFieldsUseDefaultLanguageData($query, ['a.title', 'a.description'], $fieldSuffix);
				}

				$db->setQuery($query);
				$data['ticketTypes'] = $db->loadObjectList();

				$registeredTicketTypeIds = [];

				foreach ($data['ticketTypes'] as $ticketType)
				{
					$registeredTicketTypeIds[] = $ticketType->id;
				}

				$data['form']->handleFieldsDependOnTicketTypes($registeredTicketTypeIds);
			}
		}

		if ($toAdmin && $row->payment_method == 'os_offline_creditcard')
		{
			$cardNumber = Factory::getApplication()->input->getString('x_card_num', '');

			if ($cardNumber)
			{
				$last4Digits         = substr($cardNumber, strlen($cardNumber) - 4);
				$data['last4Digits'] = $last4Digits;
			}
		}

		if ($config->multiple_booking)
		{
			$query->clear()
				->select('auto_coupon_coupon_id')
				->from('#__eb_registrants')
				->where('(id = ' . $row->id . ' OR cart_id = ' . $row->id . ')')
				->where('auto_coupon_coupon_id > 0');
			$db->setQuery($query);
			$couponIds = $db->loadColumn();

			if (count($couponIds))
			{
				$query->clear()
					->select($db->quoteName('code'))
					->from('#__eb_coupons')
					->where('id IN (' . implode(',', $couponIds) . ')');
				$db->setQuery($query);
				$data['autoCouponCode'] = implode(', ', $db->loadColumn());
			}
		}
		elseif ($row->auto_coupon_coupon_id > 0)
		{
			$query->clear()
				->select($db->quoteName('code'))
				->from('#__eb_coupons')
				->where('id = ' . $row->auto_coupon_coupon_id);
			$db->setQuery($query);
			$data['autoCouponCode'] = $db->loadResult();
		}

		return EventbookingHelperHtml::loadSharedLayout('emailtemplates/tmpl/' . $layout, $data);
	}

	/**
	 * Get group member detail, using for [MEMBER_DETAIL] tag in the email message
	 *
	 * @param   RADConfig                    $config
	 * @param   EventbookingTableRegistrant  $rowMember
	 * @param   EventbookingTableEvent       $rowEvent
	 * @param   EventbookingTableLocation    $rowLocation
	 * @param   bool                         $loadCss
	 * @param   RADForm                      $memberForm
	 *
	 * @return string
	 */
	public static function getMemberDetails($config, $rowMember, $rowEvent, $rowLocation, $loadCss = true, $memberForm = null)
	{
		$data                = [];
		$data['rowMember']   = $rowMember;
		$data['rowEvent']    = $rowEvent;
		$data['config']      = $config;
		$data['rowLocation'] = $rowLocation;
		$data['memberForm']  = $memberForm;

		return EventbookingHelperHtml::loadSharedLayout('emailtemplates/tmpl/email_group_member_detail.php', $data);
	}

	/**
	 * Load payment method object
	 *
	 * @param   string  $name
	 *
	 * @return RADPayment
	 * @throws Exception
	 */
	public static function loadPaymentMethod($name)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_payment_plugins')
			->where('published = 1')
			->where('name = ' . $db->quote($name));
		$db->setQuery($query);
		$row = $db->loadObject();

		if ($row && file_exists(JPATH_ROOT . '/components/com_eventbooking/payments/' . $row->name . '.php'))
		{
			require_once JPATH_ROOT . '/components/com_eventbooking/payments/' . $row->name . '.php';

			$params = new Registry($row->params);

			/* @var RADPayment $method */
			$method = new $row->name($params);
			$method->setTitle($row->title);

			return $method;
		}

		throw new Exception(sprintf('Payment method %s not found', $name));
	}

	/**
	 * Check to see if we should show price column for ticket types of the given event
	 *
	 * @param   int  $eventId
	 *
	 * @return bool
	 */
	public static function showPriceColumnForTicketType($eventId)
	{
		$config = EventbookingHelper::getConfig();

		if (!$config->hide_price_column_for_free_ticket_types || static::eventHasPaidTicketType($eventId))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check to see whether the event has paid ticket types
	 *
	 * @param   int  $eventId
	 *
	 * @return bool
	 */
	public static function eventHasPaidTicketType($eventId)
	{
		$db       = Factory::getDbo();
		$nowDate  = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$nullDate = $db->quote($db->getNullDate());
		$query    = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_ticket_types')
			->where('event_id = ' . $eventId)
			->where('price > 0')
			->where('(publish_up = ' . $nullDate . ' OR publish_up <= ' . $nowDate . ')')
			->where('(publish_down = ' . $nullDate . ' OR publish_down >= ' . $nowDate . ')');

		$db->setQuery($query);

		return $db->loadResult() > 0;
	}

	/**
	 * Get unique registration code for a registration record
	 *
	 * @return string
	 */
	public static function getRegistrationCode()
	{
		return static::getUniqueCodeForRegistrationRecord('registration_code', 32);
	}

	/**
	 * Generate Random Ticket Code
	 *
	 * @return string
	 */
	public static function getTicketCode()
	{
		return static::getUniqueCodeForRegistrationRecord('ticket_code', 16);
	}

	/**
	 * Generate Random Ticket Code
	 *
	 * @return string
	 */
	public static function getTicketQrCode()
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideRegistration', 'getTicketQrCode'))
		{
			return EventbookingHelperOverrideRegistration::getTicketQrCode();
		}

		return static::getUniqueCodeForRegistrationRecord('ticket_qrcode', 16);
	}

	/**
	 * Method to get unique code for a field in #__eb_registrants table
	 *
	 * @param   string  $fieldName
	 * @param   int     $length
	 *
	 * @return string
	 */
	public static function getUniqueCodeForRegistrationRecord($fieldName = 'registration_code', $length = 10)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		while (true)
		{
			$uniqueCode = UserHelper::genRandomPassword($length);
			$query->clear()
				->select('COUNT(*)')
				->from('#__eb_registrants')
				->where($db->quoteName($fieldName) . ' = ' . $db->quote($uniqueCode));
			$db->setQuery($query);
			$total = $db->loadResult();

			if (!$total)
			{
				break;
			}
		}

		return $uniqueCode;
	}

	/**
	 * Generate TICKET_QRCODE
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	public static function generateTicketQrcode($row)
	{
		$code = ($row->ticket_qrcode ?: $row->ticket_code);

		static::generateQRCODEFile($code, JPATH_ROOT . '/media/com_eventbooking/qrcodes/' . $code . '.png');
	}

	/**
	 * Generate QRcode for a transaction
	 *
	 * @param   int  $registrantId
	 *
	 * @return void
	 */
	public static function generateQrcode($registrantId)
	{
		$Itemid     = EventbookingHelperRoute::findView('registrants', EventbookingHelper::getItemid());
		$checkinUrl = EventbookingHelper::getSiteUrl(
			) . 'index.php?option=com_eventbooking&task=registrant.checkin&id=' . $registrantId . '&Itemid=' . $Itemid;

		static::generateQRCODEFile(
			$checkinUrl,
			JPATH_ROOT . '/media/com_eventbooking/qrcodes/' . $registrantId . '.png',
			['version' => QRCode::VERSION_AUTO]
		);
	}

	/**
	 * Generate QRCODE for a ticket number
	 *
	 * @param   string  $ticketNumber
	 */
	public static function generateTicketNumberQrcode($ticketNumber)
	{
		static::generateQRCODEFile($ticketNumber, JPATH_ROOT . '/media/com_eventbooking/qrcodes/' . $ticketNumber . '.png');
	}

	/**
	 * Process QRCODE for ticket. Support [QRCODE] and [TICKET_QRCODE] tag
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $output
	 * @param   bool                         $absolutePath
	 *
	 * @return string
	 */
	public static function processQRCODE($row, $output, $absolutePath = true)
	{
		$rootUri = Uri::root();

		if (strpos($output, '[QRCODE]') !== false)
		{
			EventbookingHelper::callOverridableHelperMethod('Registration', 'generateQrcode', [$row->id]);

			$imgTag = '<img src="' . ($absolutePath ? $rootUri : '') . 'media/com_eventbooking/qrcodes/' . $row->id . '.png" border="0" class="eb-checkin-registrant-qrcode" alt="QRCODE" />';
			$output = str_ireplace('[QRCODE]', $imgTag, $output);
		}

		if (($row->ticket_code || $row->ticket_qrcode) && strpos($output, '[TICKET_QRCODE]') !== false)
		{
			EventbookingHelper::callOverridableHelperMethod('Registration', 'generateTicketQrcode', [$row]);
			$imgTag = '<img src="' . ($absolutePath ? $rootUri : '') . 'media/com_eventbooking/qrcodes/' . ($row->ticket_qrcode ?: $row->ticket_code) . '.png" border="0" class="eb-ticket-qrcode" alt="QRCODE" />';
			$output = str_ireplace('[TICKET_QRCODE]', $imgTag, $output);
		}

		if (strpos($output, '[QRCODE_REGISTRANT_DATA]') !== false)
		{
			$registrantQRCODEData = Text::_('EB_REGISTRANT_QRCODE_DATA');

			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row);

			$registrantQRCODEData = EventbookingHelper::replaceCaseInsensitiveTags($registrantQRCODEData, $replaces);

			self::generateQRCODEFile($registrantQRCODEData, JPATH_ROOT . '/media/com_eventbooking/qrcodes/registrant_data_' . $row->id . '.png');

			$imgTag = '<img src="' . ($absolutePath ? $rootUri : '') . 'media/com_eventbooking/qrcodes/registrant_data_' . $row->id . '.png" border="0" class="eb-registrant-data-qrcode" alt="QRCODE" />';
			$output = str_ireplace('[QRCODE_REGISTRANT_DATA]', $imgTag, $output);
		}

		return $output;
	}

	/**
	 * Method to generate QRCODE file
	 *
	 * @param   string  $data
	 * @param   string  $filePath
	 * @param   array   $qrOptions
	 */
	public static function generateQRCODEFile($data, $filePath, $qrOptions = [])
	{
		$config = EventbookingHelper::getConfig();

		if (isset($qrOptions['version']))
		{
			$version = (int) $qrOptions['version'];
		}
		else
		{
			$version = (int) $config->get('qrcode_size') ?: QRCODE::VERSION_AUTO;
		}

		$options = new QROptions([
			'version'          => $version,
			'outputType'       => QRCode::OUTPUT_IMAGE_PNG,
			'imageTransparent' => false,
		]);

		(new QRCode($options))->render($data, $filePath);
	}

	/**
	 * Method to validate username
	 *
	 * @param   string  $username
	 *
	 * @return array
	 */
	public static function validateUsername($username)
	{
		$filterInput = InputFilter::getInstance();
		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);
		$errors      = [];

		if (empty($username))
		{
			$errors[] = Text::sprintf('EB_FORM_FIELD_IS_REQURED', Text::_('EB_USERNAME'));
		}

		if ($filterInput->clean($username, 'TRIM') == '')
		{
			$errors[] = Text::_('JLIB_DATABASE_ERROR_PLEASE_ENTER_A_USER_NAME');
		}

		if (preg_match('#[<>"\'%;()&\\\\]|\\.\\./#', $username) || strlen(utf8_decode($username)) < 2
			|| $filterInput->clean($username, 'TRIM') !== $username
		)
		{
			$errors[] = Text::sprintf('JLIB_DATABASE_ERROR_VALID_AZ09', 2);
		}

		$query->select('COUNT(*)')
			->from('#__users')
			->where('username = ' . $db->quote($username));
		$db->setQuery($query);
		$total = $db->loadResult();

		if ($total)
		{
			$errors[] = Text::_('EB_VALIDATION_INVALID_USERNAME');
		}

		return $errors;
	}

	/**
	 * Method to validate password
	 *
	 * @param   string  $password
	 *
	 * @return array
	 */
	public static function validatePassword($password)
	{
		//Load language from user component
		if (EventbookingHelper::isJoomla4())
		{
			$prefix = 'JFIELD_PASSWORD_';
		}
		else
		{
			Factory::getLanguage()->load('com_users', JPATH_ROOT);
			$prefix = 'COM_USERS_MSG_';
		}

		$errors = [];

		$params           = ComponentHelper::getParams('com_users');
		$minimumIntegers  = $params->get('minimum_integers');
		$minimumSymbols   = $params->get('minimum_symbols');
		$minimumUppercase = $params->get('minimum_uppercase');
		$minimumLowercase = $params->get('minimum_lowercase');
		$minimumLength    = $params->get('minimum_length');


		// We don't allow white space inside passwords
		$valueTrim   = trim($password);
		$valueLength = strlen($password);

		if (strlen($valueTrim) !== $valueLength)
		{
			$errors[] = Text::_($prefix . 'SPACES_IN_PASSWORD');
		}

		if (!empty($minimumIntegers))
		{
			$nInts = preg_match_all('/[0-9]/', $password, $imatch);

			if ($nInts < $minimumIntegers)
			{
				$errors[] = Text::plural($prefix . 'NOT_ENOUGH_INTEGERS_N', $minimumIntegers);
			}
		}

		if (!empty($minimumSymbols))
		{
			$nsymbols = preg_match_all('[\W]', $password, $smatch);

			if ($nsymbols < $minimumSymbols)
			{
				$errors[] = Text::plural($prefix . 'NOT_ENOUGH_SYMBOLS_N', $minimumSymbols);
			}
		}

		if (!empty($minimumUppercase))
		{
			$nUppercase = preg_match_all('/[A-Z]/', $password, $umatch);

			if ($nUppercase < $minimumUppercase)
			{
				$errors[] = Text::plural($prefix . 'NOT_ENOUGH_UPPERCASE_LETTERS_N', $minimumUppercase);
			}
		}

		if (!empty($minimumLowercase))
		{
			$nLowercase = preg_match_all('/[a-z]/', $password, $lmatch);

			if ($nLowercase < $minimumLowercase)
			{
				$errors[] = Text::plural($prefix . 'NOT_ENOUGH_LOWERCASE_LETTERS_N', $minimumLowercase);
			}
		}

		if (!empty($minimumLength) && strlen((string) $password) < $minimumLength)
		{
			$errors[] = Text::plural($prefix . 'TOO_SHORT_N', $minimumLength);
		}

		return $errors;
	}

	/**
	 * Method to accept privacy consent for a registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public static function acceptPrivacyConsent($row)
	{
		if (!$row->user_id)
		{
			return;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__privacy_consents')
			->where('user_id = ' . (int) $row->user_id)
			->where('subject = ' . $db->quote('PLG_SYSTEM_PRIVACYCONSENT_SUBJECT'))
			->where('state = 1');
		$db->setQuery($query);

		// User consented, do not process it further
		if ($db->loadResult())
		{
			return;
		}

		Factory::getLanguage()->load('plg_system_privacyconsent', JPATH_ADMINISTRATOR, $row->language);

		$params = new Registry($row->params);

		// Create the user note
		$privacyConsent = (object) [
			'user_id' => $row->user_id,
			'subject' => 'PLG_SYSTEM_PRIVACYCONSENT_SUBJECT',
			'body'    => Text::sprintf('PLG_SYSTEM_PRIVACYCONSENT_BODY', $params->get('user_ip'), $params->get('user_agent')),
			'created' => Factory::getDate()->toSql(),
		];

		try
		{
			$db->insertObject('#__privacy_consents', $privacyConsent);
		}
		catch (Exception $e)
		{
		}
	}

	/**
	 * Method to get ID of registration record of current user for given event
	 *
	 * @param   int  $eventId
	 *
	 * @return int
	 */
	public static function getRegistrantId($eventId)
	{
		static $cache;

		if ($cache === null)
		{
			$db    = Factory::getDbo();
			$user  = Factory::getUser();
			$query = $db->getQuery(true)
				->select('id, event_id')
				->from('#__eb_registrants')
				->where('group_id = 0')
				->where('(user_id = ' . $user->id . ' OR email = ' . $db->quote($user->email) . ')')
				->where('(published = 1 OR published = 3 OR (payment_method LIKE "os_offline%" AND published NOT IN (2, 4)))')
				->order('id');
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			$cache = [];

			foreach ($rows as $row)
			{
				$cache[$row->event_id] = $row->id;
			}
		}

		if (isset($cache[$eventId]))
		{
			return $cache[$eventId];
		}

		return false;
	}

	/**
	 * Method to check if user already joined the given event
	 *
	 * @param   int  $eventId
	 *
	 * @return bool
	 */
	public static function isUserJoinedEvent($eventId)
	{
		static $cache = [];

		if (!array_key_exists($eventId, $cache))
		{
			$user = Factory::getUser();

			if (!$user->id)
			{
				$cache[$eventId] = false;
			}
			else
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from('#__eb_registrants')
					->where('event_id = ' . $eventId)
					->where('(user_id = ' . $user->id . ' OR email = ' . $db->quote($user->email) . ')')
					->where('(published = 1 OR (published = 0 AND payment_method LIKE "os_offline%"))');
				$db->setQuery($query);
				$cache[$eventId] = (bool) $db->loadResult();
			}
		}

		return $cache[$eventId];
	}

	/**
	 * Method to check to see if user joined waiting list
	 *
	 * @param $eventId
	 *
	 * @return bool
	 */
	public static function isUserJoinedWaitingList($eventId)
	{
		static $cache = [];

		if (!array_key_exists($eventId, $cache))
		{
			$user = Factory::getUser();

			if (!$user->id)
			{
				$cache[$eventId] = false;
			}
			else
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from('#__eb_registrants')
					->where('event_id = ' . $eventId)
					->where('(user_id = ' . $user->id . ' OR email = ' . $db->quote($user->email) . ')')
					->where('published = 3');
				$db->setQuery($query);
				$cache[$eventId] = (bool) $db->loadResult();
			}
		}

		return $cache[$eventId];
	}

	/**
	 * Helper method to check whether registrant can cancel registration for the event
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	public static function canCancelRegistrationNow($event)
	{
		$offset = Factory::getApplication()->get('offset');

		// Validate cancel before date
		$currentDate = Factory::getDate('Now', $offset);

		if ((int) $event->cancel_before_date)
		{
			$cancelBeforeDate = Factory::getDate($event->cancel_before_date, $offset);
		}
		else
		{
			$cancelBeforeDate = Factory::getDate($event->event_date, $offset);
		}

		if ($currentDate > $cancelBeforeDate)
		{
			return false;
		}

		return true;
	}

	/**
	 * Helper method to check to see where the subscription can be cancelled
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return bool
	 */
	public static function canRefundRegistrant($row)
	{
		if ($row
			&& $row->group_id == 0
			&& $row->amount > 0
			&& $row->payment_method
			&& $row->transaction_id
			&& !$row->refunded
			&& Factory::getUser()->authorise('core.admin', 'com_eventbooking'))
		{
			$method = EventbookingHelperPayments::getPaymentMethod($row->payment_method);

			if ($method && method_exists($method, 'supportRefundPayment') && $method->supportRefundPayment())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to check if EU Tax Rules is enabled on the site
	 *
	 * @return bool
	 */
	public static function isEUVatTaxRulesEnabled()
	{
		static $euVatTaxRulesEnabled;

		if ($euVatTaxRulesEnabled === null)
		{
			$config      = EventbookingHelper::getConfig();
			$countryCode = EventbookingHelper::getCountryCode($config->default_country);

			if ($config->eu_vat_number_field && EventbookingHelperEuvat::isEUCountry($countryCode))
			{
				$euVatTaxRulesEnabled = true;
			}
			else
			{
				$euVatTaxRulesEnabled = false;
			}
		}

		return $euVatTaxRulesEnabled;
	}

	/**
	 * Calculate tax rate for registration
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   RADForm                 $form
	 * @param   array                   $data
	 * @param   RADConfig               $config
	 *
	 * @return array
	 */
	public static function calculateRegistrationTaxRate($event, $form, $data, $config)
	{
		// Use simple tax rate
		if ($config->activate_simple_tax)
		{
			return [1, $event->tax_rate, 0];
		}

		$country     = $data['country'] ?? $config->default_country;
		$state       = $data['state'] ?? '';
		$countryCode = EventbookingHelper::getCountryCode($country);

		if ($countryCode == 'GR')
		{
			$countryCode = 'EL';
		}

		$vatNumberValid = 1;
		$vatNumber      = '';

		// Calculate tax
		if (!empty($config->eu_vat_number_field) && isset($data[$config->eu_vat_number_field]))
		{
			$vatNumber = $data[$config->eu_vat_number_field];

			if ($vatNumber)
			{
				// If users doesn't enter the country code into the VAT Number, append the code
				$firstTwoCharacters = substr($vatNumber, 0, 2);

				if (strtoupper($firstTwoCharacters) != $countryCode)
				{
					$vatNumber = $countryCode . $vatNumber;
				}
			}
		}

		if ($vatNumber)
		{
			$valid = EventbookingHelperEuvat::validateEUVATNumber($vatNumber);

			if ($valid)
			{
				$taxRate = EventbookingHelperRegistration::calculateEventTaxRate($event, $country, $state, 1);
			}
			else
			{
				$vatNumberValid = 0;
				$taxRate        = EventbookingHelperRegistration::calculateEventTaxRate($event, $country, $state, 0);
			}
		}
		else
		{
			$taxRate = EventbookingHelperRegistration::calculateEventTaxRate($event, $country, $state, 0);
		}

		if (!EventbookingHelperEuvat::isEUCountry($countryCode)
			|| ($config->hide_vat_field_for_home_country && $country == $config->default_country))
		{
			$showVatNumberField = 0;
		}
		else
		{
			$showVatNumberField = 1;
		}

		return [$vatNumberValid, $taxRate, $showVatNumberField];
	}

	/**
	 * Get unique tax rate
	 *
	 * @return float|null
	 */
	public static function getUniqueTaxRate()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_taxes')
			->where('published = 1');
		$db->setQuery($query, 0, 2);
		$rows = $db->loadObjectList();

		if (count($rows) == 0)
		{
			// No tax
			return 0;
		}
		elseif (count($rows) == 1)
		{
			return $rows[0]->rate;
		}

		return null;
	}

	/**
	 * Calculate tax rate for an event
	 *
	 * @param   EventbookingTableEvent|int  $eventId
	 * @param   string                      $country
	 * @param   string                      $state
	 * @param   int                         $vies
	 * @param   bool                        $useDefaultCountryIfEmpty
	 *
	 * @return int
	 */
	public static function calculateEventTaxRate($eventId, $country = '', $state = '', $vies = 2, $useDefaultCountryIfEmpty = true)
	{
		$config = EventbookingHelper::getConfig();

		// For backward compatible purpose
		if (is_object($eventId))
		{
			$event = $eventId;
		}
		else
		{
			$event = EventbookingHelperDatabase::getEvent($eventId);
		}

		if ($config->activate_simple_tax)
		{
			if ($event->tax_rate > 0)
			{
				return $event->tax_rate;
			}

			// Try to get tax rate from category
			if (property_exists($event, 'cat_tax_rate'))
			{
				return $event->cat_tax_rate;
			}
			$category = EventbookingHelperDatabase::getCategory($event->main_category_id);

			return $category->tax_rate;
		}

		// If the system has a single tax rate (which is in most of the case), we do not need to calculate it multiple times
		static $uniqueTaxRate = -1;

		if ($uniqueTaxRate === -1)
		{
			$uniqueTaxRate = static::getUniqueTaxRate();
		}

		if ($uniqueTaxRate !== null)
		{
			return $uniqueTaxRate;
		}

		if (empty($country) && $useDefaultCountryIfEmpty)
		{
			$config  = EventbookingHelper::getConfig();
			$country = $config->default_country;
		}

		// Convert state name to state code because tax rules are defined base on state code
		if ($state)
		{
			$state = EventbookingHelper::getStateCode($country, $state);
		}

		// First, try to find from specific rule for this event
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_taxes')
			->where('published = 1')
			->where('(event_id =  ' . $event->id . ')');

		$db->setQuery($query);
		$rules = $db->loadObjectList();

		$rule = self::matchTaxRule($rules, $country, $state);

		if ($rule)
		{
			return $rule->rate;
		}

		// If no matching rule found for this event, find from rules for the main category of event
		$query->clear()
			->select('*')
			->from('#__eb_taxes')
			->where('category_id = ' . $event->main_category_id)
			->where('published = 1');

		$db->setQuery($query);
		$rules = $db->loadObjectList();

		$rule = self::matchTaxRule($rules, $country, $state);

		if ($rule)
		{
			return $rule->rate;
		}

		// If no matching rule found for this event or it's category, find from rules for all events
		$query->clear()
			->select('*')
			->from('#__eb_taxes')
			->where('event_id = 0')
			->where('category_id = 0')
			->where('published = 1');

		if ($vies != 2)
		{
			$query->where('vies = ' . (int) $vies);
		}

		$db->setQuery($query);
		$rules = $db->loadObjectList();

		$rule = self::matchTaxRule($rules, $country, $state);

		if ($rule)
		{
			return $rule->rate;
		}

		// If no tax rule found, return 0
		return 0;
	}

	/**
	 * Match tax rule for country and state
	 *
	 * @param   array   $rules
	 * @param   string  $country
	 * @param   string  $state
	 *
	 * @return mixed|null
	 */
	public static function matchTaxRule($rules, $country, $state)
	{
		// First, find the rule which match both country and state
		foreach ($rules as $rule)
		{
			if ($rule->country == $country && $rule->state == $state)
			{
				return $rule;
			}
		}

		// If no rule found, find the rule which matches country and state empty
		foreach ($rules as $rule)
		{
			if ($rule->country == $country && $rule->state == '')
			{
				return $rule;
			}
		}

		// Finally, if no rule found, return rule which is assigned to all country
		foreach ($rules as $rule)
		{
			if ($rule->country == '')
			{
				return $rule;
			}
		}

		return;
	}

	/**
	 * Helper function to determine tax rate is based on country or not
	 *
	 * @return bool
	 */
	public static function isCountryBaseTax()
	{
		$config = EventbookingHelper::getConfig();

		if ($config->activate_simple_tax)
		{
			return false;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(id)')
			->from('#__eb_taxes')
			->where('LENGTH(country) > 0')
			->where('published = 1');
		$db->setQuery($query);

		return $db->loadResult() > 0;
	}

	/**
	 * Get list of countries which has tax based on state
	 *
	 * @return array
	 */
	public static function getTaxStateCountries()
	{
		$config = EventbookingHelper::getConfig();

		if ($config->activate_simple_tax)
		{
			return [];
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('DISTINCT(country)')
			->from('#__eb_taxes')
			->where('LENGTH(state) > 0')
			->where('published = 1');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * @param   EventbookingTableEvent  $event
	 * @param   RADConfig               $config
	 *
	 * @return mixed
	 */
	public static function isCollectMembersInformation($event, $config)
	{
		if ($event->has_multiple_ticket_types)
		{
			$params = new Registry($event->params);

			return $params->get('ticket_types_collect_members_information');
		}

		if ($config->multiple_booking)
		{
			return $config->collect_member_information_in_cart;
		}
		elseif ($event->collect_member_information !== '')
		{
			return $event->collect_member_information;
		}

		return $config->collect_member_information;
	}

	/**
	 * Method to get First Name and Last Name from Full Name
	 *
	 * @param   string  $name
	 *
	 * @return array
	 */
	public static function detectFirstAndLastNameFromFullName($name)
	{
		$pos = strpos($name, ' ');

		if ($pos !== false)
		{
			$firstName = substr($name, 0, $pos);
			$lastName  = substr($name, $pos + 1);
		}
		else
		{
			$firstName = $name;
			$lastName  = '';
		}

		return [$firstName, $lastName];
	}
}
