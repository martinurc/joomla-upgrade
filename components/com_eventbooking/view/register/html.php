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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class EventbookingViewRegisterHtml extends EventbookingViewRegisterBase
{
	use EventbookingViewCaptcha;

	/**
	 * Flag tomakr if has ticket types discount
	 *
	 * @var bool
	 */
	protected $hasTicketTypesDiscount;

	/**
	 * The ticket types of the event
	 *
	 * @var array
	 */
	protected $ticketTypes;

	/**
	 * Flag to makr if we should collect members information
	 *
	 * @var bool
	 */
	protected $collectMembersInformation;

	/**
	 * The part of form to allow entering members information of purchased tickets
	 *
	 * @var string
	 */
	protected $ticketsMembers;

	/**
	 * The selected payment method
	 *
	 * @var string
	 */
	protected $paymentMethod;

	/**
	 * The available payment methods
	 *
	 * @var array
	 */
	protected $methods;

	/**
	 * The flag to mark if coupon is enabled
	 *
	 * @var bool
	 */
	protected $enableCoupon;

	/**
	 * The current User Id
	 *
	 * @var int
	 */
	protected $userId;

	/**
	 * The deposit payment flag
	 *
	 * @var int|bool
	 */
	protected $depositPayment;

	/**
	 * The selected payment type
	 *
	 * @var string
	 */
	protected $paymentType;

	/**
	 * @var RADForm
	 */
	protected $form;

	/**
	 * The flag to mark if waiting list is activated
	 *
	 * @var bool
	 */
	protected $waitingList;

	/**
	 * The flag to makr if we should show payment fee
	 *
	 * @var bool
	 */
	protected $showPaymentFee;

	/**
	 * Total payment amount
	 *
	 * @var float
	 */
	protected $totalAmount;

	/**
	 * Tax amount
	 *
	 * @var float
	 */
	protected $taxAmount;

	/**
	 * Discount amount
	 *
	 * @var float
	 */
	protected $discountAmount;

	/**
	 * Late payment fee
	 *
	 * @var float
	 */
	protected $lateFee;

	/**
	 * The deposit payment amount
	 *
	 * @var float
	 */
	protected $depositAmount;

	/**
	 * The final amount users have to pay
	 *
	 * @var float
	 */
	protected $amount;

	/**
	 * The payment processing fee
	 *
	 * @var float
	 */
	protected $paymentProcessingFee;

	/**
	 * The discount rate
	 *
	 * @var float
	 */
	protected $discountRate;

	/**
	 * The bundle discount amount
	 *
	 * @var float
	 */
	protected $bundleDiscountAmount;

	/**
	 * The registration fees data
	 *
	 * @var array
	 */
	protected $fees;

	/**
	 * ID of terms and conditions article
	 *
	 * @var int
	 */
	protected $termsAndConditionsArticleId;

	/**
	 * The flag to mark if captcha is valid
	 *
	 * @var bool
	 */
	protected $captchaInvalid;

	/**
	 * The flag to mark if we should show billing information step for group registration
	 *
	 * @var bool
	 */
	protected $showBillingStep;

	/**
	 * The flag to mark if we should bypass Number Members Step
	 *
	 * @var bool
	 */
	protected $bypassNumberMembersStep;

	/**
	 * The flag to makr if we should collect members information
	 * @var bool
	 */
	protected $collectMemberInformation;

	/**
	 * The flag to mark if squareup payment plugin is enabled
	 *
	 * @var bool
	 */
	protected $squareUpEnabled;

	/**
	 * The default step on group registration
	 *
	 * @var string
	 */
	protected $defaultStep;

	/**
	 * The events in cart
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Title of the registered event
	 *
	 * @var string
	 */
	protected $eventTitle;

	/**
	 * The form data
	 *
	 * @var array
	 */
	protected $formData;

	/**
	 * Flag to mark if we use custom fields default data on bind
	 *
	 * @var bool
	 */
	protected $useDefault;

	/**
	 * The bundle discount amount
	 *
	 * @var float
	 */
	protected $bunldeDiscount;

	/**
	 * The event being registered
	 *
	 * @var stdClass
	 */
	protected $event;

	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * The page heading
	 *
	 * @var string
	 */
	protected $pageHeading;

	/**
	 * The messaege displayed above registration form
	 *
	 * @var string
	 */
	protected $formMessage;

	/**
	 * Array contains event information
	 *
	 * @var array
	 */
	protected $replaces = [];

	/**
	 * Display interface to user
	 */
	public function display()
	{
		$config                = EventbookingHelper::getConfig();
		$layout                = $this->getLayout();
		$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();

		// Load common js code
		$this->loadAndInitializeJS();

		if ($layout == 'cart')
		{
			Factory::getApplication()->getDocument()->addScriptOptions('currencyCode', $config->currency_code);
			$this->displayCart();

			return;
		}

		$eventId = $this->input->getInt('event_id', 0);
		$event   = EventbookingHelperDatabase::getEvent($eventId);

		$canRegister = $this->validateRegistration($event, $config);

		if ($canRegister === false)
		{
			return;
		}

		Factory::getApplication()->getDocument()->addScriptOptions('currencyCode', $event->currency_code ?: $config->currency_code);

		$category = EventbookingHelperDatabase::getCategory($event->main_category_id);

		if (!$event->payment_methods && $category->payment_methods)
		{
			$event->payment_methods = $category->payment_methods;
		}

		// Set page title
		$this->setPageTitle($event, $layout);

		// Breadcrumb
		$this->generateBreadcrumb($event, $layout);

		switch ($layout)
		{
			case 'group':
				EventbookingHelperPayments::writeJavascriptObjects();
				$this->displayGroupForm($event, $this->input);
				break;
			default:
				$this->displayIndividualRegistrationForm($event, $this->input);
				break;
		}
	}

	/**
	 * Display individual registration Form
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   RADInput                $input
	 *
	 * @return  void
	 */
	protected function displayIndividualRegistrationForm($event, $input)
	{
		$config  = EventbookingHelper::getConfig();
		$user    = Factory::getUser();
		$userId  = $user->id;
		$eventId = $event->id;

		EventbookingHelper::overrideGlobalConfig($config, $event);

		if ($event->event_capacity > 0 && ($event->event_capacity <= $event->total_registrants))
		{
			$waitingList = true;
		}
		else
		{
			$waitingList = false;
		}

		$typeOfRegistration = EventbookingHelperRegistration::getTypeOfRegistration($event);

		$rowFields = EventbookingHelperRegistration::getFormFields($eventId, 0, null, $userId, $typeOfRegistration);
		EventbookingHelperRegistration::passFieldPaymentMethodDataToJS($rowFields);
		EventbookingHelperRegistration::passFieldTicketTypesDataToJS($rowFields);

		$this->setFieldsContainerSize($rowFields, $event);

		$captchaInvalid = $input->getInt('captcha_invalid', 0);

		// Add support for deposit payment
		if ($event->deposit_amount > 0)
		{
			$paymentType = $input->post->getInt('payment_type', $config->get('default_payment_type', 0));

			if (!$config->get('enable_full_payment', 1))
			{
				$paymentType = 1;
			}
		}
		else
		{
			$paymentType = 0;
		}

		if ($captchaInvalid)
		{
			$data = $input->post->getData();
		}
		else
		{
			$data = EventbookingHelperRegistration::getFormData($rowFields, $eventId, $userId);

			// IN case there is no data, get it from URL (get for example)
			if (empty($data))
			{
				$data = $input->getData();
			}
		}

		$data['payment_type'] = $paymentType;

		$this->setCommonViewData($config, $data, 'calculateIndividualRegistrationFee();');

		//Get data
		$form = new RADForm($rowFields);

		if ($captchaInvalid)
		{
			$useDefault = false;
		}
		else
		{
			$useDefault = true;
		}

		$data['use_field_default_value'] = $useDefault;

		$form->bind($data, $useDefault);
		$form->prepareFormFields('calculateIndividualRegistrationFee();');
		$paymentMethod = $input->post->getString('payment_method', EventbookingHelperPayments::getDefautPaymentMethod(trim($event->payment_methods)));
		$form->handleFieldsDependOnPaymentMethod($paymentMethod);

		if ($waitingList)
		{
			$fees = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculateIndividualRegistrationFees',
				[$event, $form, $data, $config, null],
				'Helper'
			);
		}
		else
		{
			$fees = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'calculateIndividualRegistrationFees',
				[$event, $form, $data, $config, $paymentMethod],
				'Helper'
			);
		}

		$methods = EventbookingHelperPayments::getPaymentMethods(trim($event->payment_methods));

		if (($event->enable_coupon == 0 && $config->enable_coupon) || in_array($event->enable_coupon, [1, 3]))
		{
			$enableCoupon = 1;

			if (!EventbookingHelperRegistration::isCouponAvailableForEvent($event, 0))
			{
				$enableCoupon = 0;
			}
		}
		else
		{
			$enableCoupon = 0;
		}

		// Check to see if there is payment processing fee or not
		$showPaymentFee = false;

		foreach ($methods as $method)
		{
			if ($method->paymentFee)
			{
				$showPaymentFee = true;
				break;
			}
		}

		if ($config->activate_deposit_feature && $event->deposit_amount > 0
			&& EventbookingHelper::isNullOrGreaterThan($event->deposit_until_date))
		{
			$depositPayment = 1;
		}
		else
		{
			$depositPayment = 0;
		}

		$this->loadCaptcha();

		// Reset some values if waiting list is activated
		if ($waitingList)
		{
			if (!$config->enable_coupon_for_waiting_list)
			{
				$enableCoupon = false;
			}

			$depositPayment = false;
			$paymentType    = false;
			$showPaymentFee = false;
		}
		else
		{
			$form->setEventId($eventId);
		}

		$this->hasTicketTypesDiscount = false;

		if ($event->has_multiple_ticket_types)
		{
			$this->ticketTypes = EventbookingHelperData::getTicketTypes($event->id, true);

			$selectedTicketTypeIds = [];

			foreach ($this->ticketTypes as $ticketType)
			{
				if (strlen(trim($ticketType->discount_rules)))
				{
					$this->hasTicketTypesDiscount = true;
				}

				$ticketTypeInputFieldName = 'ticket_type_' . $ticketType->id;

				if (!empty($data[$ticketTypeInputFieldName]))
				{
					$selectedTicketTypeIds[] = $ticketType->id;
				}
			}

			$form->handleFieldsDependOnTicketTypes($selectedTicketTypeIds);
		}

		$params                          = new Registry($event->params);
		$this->collectMembersInformation = $params->get('ticket_types_collect_members_information', 0);

		if (isset($fees['tickets_members']))
		{
			$this->ticketsMembers = $fees['tickets_members'];
		}

		if ($taxStateCountries = EventbookingHelperRegistration::getTaxStateCountries())
		{
			$fields = $form->getFields();

			if (isset($fields['state'], $data['country']) && in_array($data['country'], $taxStateCountries))
			{
				$fields['state']->setAttribute('onchange', 'calculateIndividualRegistrationFee();');
			}
		}

		// Assign these parameters
		$this->paymentMethod               = $paymentMethod;
		$this->config                      = $config;
		$this->event                       = $event;
		$this->methods                     = $methods;
		$this->enableCoupon                = $enableCoupon;
		$this->userId                      = $userId;
		$this->depositPayment              = $depositPayment;
		$this->paymentType                 = $paymentType;
		$this->form                        = $form;
		$this->waitingList                 = $waitingList;
		$this->showPaymentFee              = $showPaymentFee;
		$this->totalAmount                 = $fees['total_amount'];
		$this->taxAmount                   = $fees['tax_amount'];
		$this->discountAmount              = $fees['discount_amount'];
		$this->lateFee                     = $fees['late_fee'];
		$this->depositAmount               = $fees['deposit_amount'];
		$this->amount                      = $fees['amount'];
		$this->paymentProcessingFee        = $fees['payment_processing_fee'];
		$this->discountRate                = $fees['discount_rate'];
		$this->bundleDiscountAmount        = $fees['bundle_discount_amount'];
		$this->fees                        = $fees;
		$this->termsAndConditionsArticleId = $this->getTermsAndConditionsArticleId($this->event, $this->config);

		// Calculate page heading and message
		$this->setRegistrationFormHeadingAndMessage('default');

		// Add JS variable
		$ItemidLink = EventbookingHelper::getItemIdLink($this->Itemid);
		$langLink   = EventbookingHelper::getLangLink();

		if ($this->config->get('use_sef_url_for_ajax'))
		{
			$calculateIndividualRegistrationFeeUrl = Route::_(
				'index.php?option=com_eventbooking&task=register.calculate_individual_registration_fee' . $langLink . $ItemidLink,
				false
			);
		}
		else
		{
			$calculateIndividualRegistrationFeeUrl = Uri::root(true)
				. '/index.php?option=com_eventbooking&task=register.calculate_individual_registration_fee'
				. $langLink
				. $ItemidLink;
		}

		Factory::getApplication()->getDocument()->addScriptOptions('calculateIndividualRegistrationFeeUrl', $calculateIndividualRegistrationFeeUrl);

		if ($this->collectMembersInformation)
		{
			EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/assets/js/ajaxupload.min.js');
		}

		// Load modal script
		if ($this->config->show_privacy_policy_checkbox || $this->termsAndConditionsArticleId)
		{
			EventbookingHelperModal::iframeModal();
		}

		parent::display();
	}

	/**
	 * Display Group Registration Form
	 *
	 * @param   object    $event
	 * @param   RADInput  $input
	 *
	 * @return  void
	 */
	protected function displayGroupForm($event, $input)
	{
		EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/assets/js/ajaxupload.min.js');

		$this->event           = $event;
		$this->message         = EventbookingHelper::getMessages();
		$this->fieldSuffix     = EventbookingHelper::getFieldSuffix();
		$this->config          = EventbookingHelper::getConfig();
		$this->captchaInvalid  = $input->get('captcha_invalid', 0);
		$this->showBillingStep = EventbookingHelperRegistration::showBillingStep($event->id);

		if (($event->event_capacity > 0) && ($event->event_capacity <= $event->total_registrants))
		{
			$waitingList = true;
		}
		else
		{
			$waitingList = false;
		}

		$this->bypassNumberMembersStep = false;

		if ($this->event->collect_member_information === '')
		{
			$this->collectMemberInformation = $this->config->collect_member_information;
		}
		else
		{
			$this->collectMemberInformation = $this->event->collect_member_information;
		}

		if ($event->max_group_number > 0 && ($event->max_group_number == $event->min_group_number))
		{
			Factory::getApplication()->getSession()->set('eb_number_registrants', $event->max_group_number);
			$this->bypassNumberMembersStep = true;
		}

		// This is needed here so that Stripe JS can be loaded using API
		$methods = EventbookingHelperPayments::getPaymentMethods(trim($event->payment_methods));

		$squareUpEnabled = false;

		foreach ($methods as $method)
		{
			if ($method->getName() == 'os_squareup')
			{
				$squareUpEnabled = true;
				break;
			}
		}

		$this->squareUpEnabled = $squareUpEnabled;
		$this->waitingList     = $waitingList;

		$defaultStep = '';

		if ($this->captchaInvalid)
		{
			if ($this->showBillingStep)
			{
				$defaultStep = 'group_billing';
			}
			else
			{
				$defaultStep = 'group_members';
			}
		}
		elseif ($this->bypassNumberMembersStep)
		{
			if ($this->collectMemberInformation)
			{
				$defaultStep = 'group_members';
			}
			else
			{
				$defaultStep = 'group_billing';
			}
		}

		$this->defaultStep = $defaultStep;

		$this->loadCaptcha(true);

		// Load modal script
		EventbookingHelperModal::iframeModal();

		// Calculate page heading and message
		$this->setRegistrationFormHeadingAndMessage('group');

		// Add necessary js variables
		$this->addGroupRegistrationJSVariables();

		// Load modal script
		if ($this->config->show_privacy_policy_checkbox
			|| $this->getTermsAndConditionsArticleId($this->event, $this->config))
		{
			EventbookingHelperModal::iframeModal();
		}

		parent::display();
	}

	/**
	 * Display registration page in case shopping cart is enabled
	 *
	 * @return void
	 */
	protected function displayCart()
	{
		$app    = Factory::getApplication();
		$config = EventbookingHelper::getConfig();
		$user   = Factory::getUser();
		$input  = $this->input;
		$userId = $user->id;
		$cart   = new EventbookingHelperCart();
		$items  = $cart->getItems();

		if (!count($items))
		{
			$active = Factory::getApplication()->getMenu()->getActive();

			if ($active
				&& isset($active->query['view'], $active->query['layout'])
				&& $active->query['view'] == 'register'
				&& $active->query['layout'] == 'cart')
			{
				$url = Uri::root();
			}
			else
			{
				$url = Route::_('index.php?option=com_eventbooking&Itemid=' . $input->getInt('Itemid', 0));
			}

			$app->enqueueMessage(Text::_('EB_NO_EVENTS_FOR_CHECKOUT'), 'warning');
			$app->redirect($url);
		}

		$eventId   = (int) $items[0];
		$rowFields = EventbookingHelperRegistration::getFormFields(0, 4);

		$this->setFieldsContainerSize($rowFields);

		EventbookingHelperRegistration::passFieldPaymentMethodDataToJS($rowFields);

		$captchaInvalid = $input->getInt('captcha_invalid', 0);
		$paymentType    = $input->post->getInt('payment_type', $config->get('default_payment_type', 0));

		if ($captchaInvalid)
		{
			$data = $input->post->getData();
		}
		else
		{
			$data = EventbookingHelperRegistration::getFormData($rowFields, $eventId, $userId);

			// IN case there is no data, get it from URL (get for example)
			if (empty($data))
			{
				$data = $input->getData();
			}
		}

		$data['payment_type'] = $paymentType;

		$this->setCommonViewData($config, $data);

		//Get data
		$form = new RADForm($rowFields);

		if ($captchaInvalid)
		{
			$useDefault = false;
		}
		else
		{
			$useDefault = true;
		}

		$form->bind($data, $useDefault);
		$form->prepareFormFields('calculateCartRegistrationFee();');
		$paymentMethod = $input->post->getString('payment_method', EventbookingHelperPayments::getDefautPaymentMethod());
		$form->handleFieldsDependOnPaymentMethod($paymentMethod);

		// Set payment_type to Deposit Payment temporarily to have deposit amount calculated properly
		$data['payment_type'] = 1;
		$fees                 = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'calculateCartRegistrationFee',
			[$cart, $form, $data, $config, $paymentMethod, $useDefault],
			'Helper'
		);

		// Restore original value of payment_type
		$data['payment_type'] = $paymentType;

		$events  = $cart->getEvents();
		$methods = EventbookingHelperPayments::getPaymentMethods();

		//Coupon will be enabled if there is at least one event has coupon enabled, same for deposit payment
		$enableCoupon  = 0;
		$enableDeposit = 0;
		$eventTitles   = [];

		foreach ($events as $event)
		{
			if (in_array($event->enable_coupon, [1, 2, 3]) || ($event->enable_coupon == 0 && $config->enable_coupon))
			{
				$enableCoupon = 1;
			}

			if ($event->deposit_amount > 0 && EventbookingHelper::isNullOrGreaterThan($event->deposit_until_date))
			{
				$enableDeposit = 1;
			}

			$eventTitles[] = $event->title;
		}

		if ($config->activate_deposit_feature && $enableDeposit)
		{
			$depositPayment = 1;
		}
		else
		{
			$depositPayment = 0;
		}

		// Check to see if there is payment processing fee or not
		$showPaymentFee = false;

		foreach ($methods as $method)
		{
			if ($method->paymentFee)
			{
				$showPaymentFee = true;
				break;
			}
		}

		if ($taxStateCountries = EventbookingHelperRegistration::getTaxStateCountries())
		{
			$fields = $form->getFields();

			if (isset($fields['state'], $data['country']) && in_array($data['country'], $taxStateCountries))
			{
				$fields['state']->setAttribute('onchange', 'calculateCartRegistrationFee();');
			}
		}

		// Load captcha
		$this->loadCaptcha();

		// Assign these parameters
		$this->paymentMethod        = $paymentMethod;
		$this->config               = $config;
		$this->methods              = $methods;
		$this->enableCoupon         = $enableCoupon;
		$this->userId               = $userId;
		$this->depositPayment       = $depositPayment;
		$this->items                = $events;
		$this->eventTitle           = implode(', ', $eventTitles);
		$this->form                 = $form;
		$this->showPaymentFee       = $showPaymentFee;
		$this->paymentType          = $paymentType;
		$this->formData             = $data;
		$this->useDefault           = $useDefault;
		$this->totalAmount          = $fees['total_amount'];
		$this->taxAmount            = $fees['tax_amount'];
		$this->discountAmount       = $fees['discount_amount'];
		$this->bunldeDiscount       = $fees['bundle_discount_amount'];
		$this->lateFee              = $fees['late_fee'];
		$this->depositAmount        = $fees['deposit_amount'];
		$this->paymentProcessingFee = $fees['payment_processing_fee'];
		$this->amount               = $fees['amount'];
		$this->fees                 = $fees;

		$this->setRegistrationFormMessageForCart();

		// Add JS variable
		$langLink   = EventbookingHelper::getLangLink();
		$ItemidLink = EventbookingHelper::getItemIdLink($this->Itemid);

		if ($this->config->get('use_sef_url_for_ajax'))
		{
			$calculateCartRegistrationFeeUrl = Route::_(
				'index.php?option=com_eventbooking&task=cart.calculate_cart_registration_fee' . $langLink . $ItemidLink,
				false
			);
		}
		else
		{
			$calculateCartRegistrationFeeUrl = Uri::root(true)
				. '/index.php?option=com_eventbooking&task=cart.calculate_cart_registration_fee' . $langLink . $ItemidLink;
		}

		Factory::getApplication()->getDocument()->addScriptOptions('calculateCartRegistrationFeeUrl', $calculateCartRegistrationFeeUrl);

		// Load modal script
		if ($this->config->accept_term == 1 && $this->config->article_id)
		{
			EventbookingHelperModal::iframeModal();
		}

		parent::display();
	}

	/**
	 * Generate Breadcrumb for event detail page, allow users to come back to event details
	 *
	 * @param   \Joomla\CMS\Table\Table  $event
	 * @param   string                   $layout
	 */
	protected function generateBreadcrumb($event, $layout)
	{
		if ($this->input->getInt('hmvc_call'))
		{
			return;
		}

		$app      = Factory::getApplication();
		$active   = $app->getMenu()->getActive();
		$pathway  = $app->getPathway();
		$menuView = !empty($active->query['view']) ? $active->query['view'] : null;

		if (in_array($menuView, ['calendar', 'fullcalendar', 'categories', 'category', 'upcomingevents']))
		{
			$pathway->addItem($event->title, Route::_(EventbookingHelperRoute::getEventRoute($event->id, 0, $app->input->getInt('Itemid'))));
		}

		if ($layout == 'default')
		{
			$title = Text::_('EB_INDIVIDUAL_REGISTRATION');
			$title = str_replace('[EVENT_TITLE]', $event->title, $title);
			$pathway->addItem($title);
		}
		else
		{
			$title = Text::_('EB_GROUP_REGISTRATION');
			$title = str_replace('[EVENT_TITLE]', $event->title, $title);
			$pathway->addItem($title);
		}
	}

	/**
	 * Method to check and make sure registration is still possible with this even
	 *
	 * @param $event
	 * @param $config
	 */
	protected function validateRegistration($event, $config)
	{
		$app          = Factory::getApplication();
		$user         = Factory::getUser();
		$accessLevels = $user->getAuthorisedViewLevels();

		if (empty($event)
			|| !$event->published
			|| !in_array($event->access, $accessLevels)
			|| !in_array($event->registration_access, $accessLevels)
		)
		{
			if (!$user->id && $event && $event->published)
			{
				$app->enqueueMessage(Text::_('EB_LOGIN_TO_REGISTER_FOR_EVENT'));
				$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()->toString())));
			}
			else
			{
				$app->enqueueMessage(Text::_('EB_ERROR_REGISTRATION'), 'error');

				if (!$this->input->getInt('hmvc_call'))
				{
					$app->redirect(Uri::root(), 403);
				}
				else
				{
					return false;
				}
			}
		}

		if (!EventbookingHelper::callOverridableHelperMethod('Registration', 'acceptRegistration', [$event]))
		{
			if ($event->activate_waiting_list == 2)
			{
				$waitingList = $config->activate_waitinglist_feature;
			}
			else
			{
				$waitingList = $event->activate_waiting_list;
			}

			// If even is not full, we are not in waiting list state
			if ($waitingList && (!$event->event_capacity || $event->event_capacity > $event->total_registrants))
			{
				$waitingList = false;
			}

			if ($event->cut_off_date != Factory::getDbo()->getNullDate())
			{
				$registrationOpen = ($event->cut_off_minutes < 0);
			}
			elseif (isset($event->event_start_minutes))
			{
				$registrationOpen = ($event->event_start_minutes < 0);
			}
			else
			{
				$registrationOpen = ($event->number_event_dates > 0);
			}

			if (!$waitingList || !$registrationOpen)
			{
				$error = EventbookingHelperRegistration::getRegistrationErrorMessage($event);
				$app->enqueueMessage($error, 'error');

				if (!$this->input->getInt('hmvc_call'))
				{
					$app->redirect(Route::_(EventbookingHelperRoute::getEventRoute($event->id, $event->main_category_id), false));
				}
				else
				{
					return false;
				}
			}

			if ($waitingList && $user->id)
			{
				//Check to see whether the current user has registered for the event
				if ($event->prevent_duplicate_registration === '')
				{
					$preventDuplicateRegistration = $config->prevent_duplicate_registration;
				}
				else
				{
					$preventDuplicateRegistration = $event->prevent_duplicate_registration;
				}

				// Check to see if user joined waiting list before, if Yes, prevent them from joining waiting list again
				if ($preventDuplicateRegistration && $user->id > 0)
				{
					$db    = Factory::getDbo();
					$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from('#__eb_registrants')
						->where('event_id = ' . $event->id)
						->where('group_id = 0')
						->where('(user_id = ' . $user->id . ' OR email = ' . $db->quote($user->email) . ')')
						->where('published = 3');
					$db->setQuery($query);
					$total = (int) $db->loadResult();

					// User joined waiting list, prevent them from doing that again
					if ($total)
					{
						$app->enqueueMessage(Text::_('EB_JOINED_WAITING_LIST_ALREADY'), 'error');

						if (!$this->input->getInt('hmvc_call'))
						{
							$app->redirect(Route::_(EventbookingHelperRoute::getEventRoute($event->id, $event->main_category_id), false));
						}
						else
						{
							return false;
						}
					}
				}
			}
		}

		if ($event->event_password)
		{
			$passwordPassed = $app->getSession()->get('eb_passowrd_' . $event->id, 0);

			if (!$passwordPassed)
			{
				$return = base64_encode(Uri::getInstance()->toString());

				$app->redirect(
					Route::_(
						'index.php?option=com_eventbooking&view=password&event_id=' . $event->id . '&return=' . $return . '&Itemid=' . $this->Itemid,
						false
					)
				);
			}
		}
	}

	/**
	 * Load js script needed for registration form
	 *
	 * @return void
	 */
	protected function loadAndInitializeJS()
	{
		$document = Factory::getApplication()->getDocument();
		$config   = EventbookingHelper::getConfig();
		$rootUri  = Uri::root(true);

		if (EventbookingHelperRegistration::isEUVatTaxRulesEnabled())
		{
			$config           = EventbookingHelper::getConfig();
			$euVatNumberField = $config->eu_vat_number_field;
		}
		else
		{
			$euVatNumberField = '';
		}

		$document->addScriptDeclaration(
			'var siteUrl = "' . EventbookingHelper::getSiteUrl() . '";'
		);

		$document->addScriptDeclaration('var stripeCard = null;')
			->addScriptOptions('euVatNumberField', $euVatNumberField)
			->addScriptOptions('ebSiteUrl', $rootUri . '/')
			->addScriptOptions('isCountryBaseTax', EventbookingHelperRegistration::isCountryBaseTax())
			->addScriptOptions('isEUTaxRuleEnabled', EventbookingHelperRegistration::isEUVatTaxRulesEnabled())
			->addScriptOptions('taxStateCountries', EventbookingHelperRegistration::getTaxStateCountries());

		HTMLHelper::_('behavior.core');
		Text::script('EB_INVALID_VATNUMBER', true);

		EventbookingHelperJquery::loadjQuery();
		EventbookingHelperHtml::addOverridableScript(
			'media/com_eventbooking/assets/js/paymentmethods.min.js',
			['version' => EventbookingHelper::getInstalledVersion()]
		);

		if ($config->load_legacy_js)
		{
			EventbookingHelperHtml::addOverridableScript(
				'media/com_eventbooking/js/site-register-legacy.js',
				['version' => EventbookingHelper::getInstalledVersion()]
			);
		}

		$customJSFile = JPATH_ROOT . '/media/com_eventbooking/assets/js/custom.js';

		if (file_exists($customJSFile) && filesize($customJSFile) > 0)
		{
			$document->addScript($rootUri . '/media/com_eventbooking/assets/js/custom.js');
		}

		EventbookingHelper::addLangLinkForAjax();
	}

	/**
	 * Calculate and set page title for registration page
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   string                  $layout
	 *
	 * @return  void
	 */
	protected function setPageTitle($event, $layout)
	{
		if ($this->input->getInt('hmvc_call'))
		{
			return;
		}

		$pageTitle = '';
		$active    = Factory::getApplication()->getMenu()->getActive();

		// Try to get page title from menu item settings
		if ($active
			&& isset($active->query['view'], $active->query['event_id'])
			&& $active->query['view'] == 'register'
			&& $active->query['event_id'] == $event->id)
		{
			$params = $active->getParams();

			$pageTitle = $params->get('page_title');
		}

		// If page title not set from menu item parameter, use language item
		if (!$pageTitle)
		{
			$config   = EventbookingHelper::getConfig();
			$language = Factory::getLanguage();

			if (($layout == 'default' || $layout == '') && $language->hasKey('EB_INDIVIDUAL_REGISTRATION_PAGE_TITLE'))
			{
				$pageTitle = Text::_('EB_INDIVIDUAL_REGISTRATION_PAGE_TITLE');
			}
			elseif ($layout == 'group' && $language->hasKey('EB_GROUP_REGISTRATION_PAGE_TITLE'))
			{
				$pageTitle = Text::_('EB_GROUP_REGISTRATION_PAGE_TITLE');
			}
			else
			{
				$pageTitle = Text::_('EB_EVENT_REGISTRATION');
			}

			$pageTitle = str_replace('[EVENT_TITLE]', $event->title, $pageTitle);
			$pageTitle = str_replace('[EVENT_DATE]', HTMLHelper::_('date', $event->event_date, $config->event_date_format, null), $pageTitle);
		}

		if ($pageTitle)
		{
			Factory::getApplication()->getDocument()->setTitle($pageTitle);
		}
	}

	/**
	 * Method to calculate the message displayed individual and group registration form
	 *
	 * @param   string  $layout
	 *
	 * @return void
	 */
	protected function setRegistrationFormHeadingAndMessage($layout)
	{
		if ($this->waitingList)
		{
			$pageHeading = Text::_('EB_JOIN_WAITINGLIST');

			if ($this->fieldSuffix && EventbookingHelper::isValidMessage($this->message->{'waitinglist_form_message' . $this->fieldSuffix}))
			{
				$msg = $this->message->{'waitinglist_form_message' . $this->fieldSuffix};
			}
			else
			{
				$msg = $this->message->waitinglist_form_message;
			}
		}
		else
		{
			if ($layout == 'default')
			{
				$pageHeading = Text::_('EB_INDIVIDUAL_REGISTRATION');
				$messageKey  = 'registration_form_message' . $this->fieldSuffix;
			}
			else
			{
				$pageHeading = Text::_('EB_GROUP_REGISTRATION');
				$messageKey  = 'registration_form_message_group' . $this->fieldSuffix;
			}

			if ($this->fieldSuffix && EventbookingHelper::isValidMessage($this->event->{$messageKey}))
			{
				$msg = $this->event->{$messageKey};
			}
			elseif ($this->fieldSuffix && EventbookingHelper::isValidMessage($this->message->{$messageKey}))
			{
				$msg = $this->message->{$messageKey};
			}
			elseif (EventbookingHelper::isValidMessage($this->event->{$messageKey}))
			{
				$msg = $this->event->{$messageKey};
			}
			else
			{
				$msg = $this->message->{$messageKey};
			}
		}

		$replaces = EventbookingHelperRegistration::buildEventTags($this->event, $this->config);

		if (isset($this->amount))
		{
			$replaces['AMOUNT'] = EventbookingHelper::formatCurrency($this->amount, $this->config, $this->event->currency_symbol);
		}

		$msg         = EventbookingHelper::replaceUpperCaseTags($msg, $replaces);
		$pageHeading = EventbookingHelper::replaceUpperCaseTags($pageHeading, $replaces);

		$this->formMessage = $msg;
		$this->pageHeading = $pageHeading;
		$this->replaces    = $replaces;
	}

	/**
	 * Method to calculate the message displayed cart registration form
	 *
	 * @return void
	 */
	protected function setRegistrationFormMessageForCart()
	{
		if ($this->fieldSuffix && EventbookingHelper::isValidMessage($this->message->{'registration_form_message' . $this->fieldSuffix}))
		{
			$msg = $this->message->{'registration_form_message' . $this->fieldSuffix};
		}
		else
		{
			$msg = $this->message->registration_form_message;
		}

		if (strlen($msg))
		{
			$msg = str_replace('[EVENT_TITLE]', $this->eventTitle, $msg);
			$msg = str_replace('[AMOUNT]', EventbookingHelper::formatCurrency($this->amount, $this->config), $msg);
		}

		$this->formMessage = $msg;
	}

	/**
	 * Add JS variables needed for group registration
	 *
	 * @return void
	 */
	protected function addGroupRegistrationJSVariables()
	{
		HTMLHelper::_('behavior.core');

		$rootUri    = Uri::root(true);
		$langLink   = EventbookingHelper::getLangLink();
		$ItemidLink = EventbookingHelper::getItemIdLink($this->Itemid);
		$couponCode = $this->input->getString('coupon_code', '');

		$typeOfRegistration = EventbookingHelperRegistration::getTypeOfRegistration($this->event);
		$rowFields          = EventbookingHelperRegistration::getFormFields($this->event->id, 2, null, null, $typeOfRegistration);
		$memberFields       = [];

		foreach ($rowFields as $rowField)
		{
			$memberFields[] = $rowField->name;
		}

		Factory::getApplication()->getDocument()->addScriptOptions('defaultStep', $this->defaultStep)
			->addScriptOptions('returnUrl', base64_encode(Uri::getInstance()->toString() . '#group_billing'))
			->addScriptOptions('collectMemberInformation', (bool) $this->collectMemberInformation)
			->addScriptOptions('showBillingStep', (bool) $this->showBillingStep)
			->addScriptOptions('showCaptcha', $this->showCaptcha)
			->addScriptOptions('captchaPlugin', $this->captchaPlugin)
			->addScriptOptions('squareUpEnabled', (bool) $this->squareUpEnabled)
			->addScriptOptions('waitingList', (bool) $this->waitingList)
			->addScriptOptions('eventId', $this->event->id)
			->addScriptOptions('Itemid', $this->Itemid)
			->addScriptOptions('memberFields', $memberFields)
			->addScriptOptions('ajaxLoadingImageUrl', $rootUri . '/media/com_eventbooking/ajax-loadding-animation.gif')
			->addScriptDeclaration('var returnUrl = "' . base64_encode(Uri::getInstance()->toString() . '#group_billing') . '";');

		if ($this->config->get('use_sef_url_for_ajax'))
		{
			$numberMembersUrl                 = Route::_(
				'index.php?option=com_eventbooking&view=register&layout=number_members&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink,
				false
			);
			$groupMembersUrl                  = Route::_(
				'index.php?option=com_eventbooking&view=register&layout=group_members&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink,
				false
			);
			$groupBillingUrl                  = Route::_(
				'index.php?option=com_eventbooking&view=register&layout=group_billing&event_id=' . $this->event->id . ($couponCode ? '&coupon_code=' . $couponCode : '') . '&format=raw' . $langLink . $ItemidLink,
				false
			);
			$storeNumberMembersUrl            = Route::_(
				'index.php?option=com_eventbooking&task=register.store_number_registrants&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink,
				false
			);
			$storeGroupMembersDataUrl         = Route::_(
				'index.php?option=com_eventbooking&task=register.validate_and_store_group_members_data&event_id=' . $this->event->id . ($couponCode ? '&coupon_code=' . $couponCode : '') . '&format=raw' . $langLink . $ItemidLink,
				false
			);
			$storeGroupBillingDataUrl         = Route::_(
				'index.php?option=com_eventbooking&task=register.store_billing_data_and_display_group_members_form&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink,
				false
			);
			$calculateGroupRegistrationFeeUrl = Route::_(
				'index.php?option=com_eventbooking&task=register.calculate_group_registration_fee' . $langLink . $ItemidLink,
				false
			);
		}
		else
		{
			$numberMembersUrl                 = $rootUri . '/index.php?option=com_eventbooking&view=register&layout=number_members&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink;
			$groupMembersUrl                  = $rootUri . '/index.php?option=com_eventbooking&view=register&layout=group_members&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink;
			$groupBillingUrl                  = $rootUri . '/index.php?option=com_eventbooking&view=register&layout=group_billing&event_id=' . $this->event->id . ($couponCode ? '&coupon_code=' . $couponCode : '') . '&format=raw' . $langLink . $ItemidLink;
			$storeNumberMembersUrl            = $rootUri . '/index.php?option=com_eventbooking&task=register.store_number_registrants&event_id=' . $this->event->id . '&format=raw' . $langLink . '&Itemid=' . $this->Itemid;
			$storeGroupMembersDataUrl         = $rootUri . '/index.php?option=com_eventbooking&task=register.validate_and_store_group_members_data&event_id=' . $this->event->id . ($couponCode ? '&coupon_code=' . $couponCode : '') . '&format=raw' . $langLink . $ItemidLink;
			$storeGroupBillingDataUrl         = $rootUri . '/index.php?option=com_eventbooking&task=register.store_billing_data_and_display_group_members_form&event_id=' . $this->event->id . '&format=raw' . $langLink . $ItemidLink;
			$calculateGroupRegistrationFeeUrl = $rootUri . '/index.php?option=com_eventbooking&task=register.calculate_group_registration_fee' . $langLink . $ItemidLink;
		}

		Factory::getApplication()->getDocument()->addScriptOptions('numberMembersUrl', $numberMembersUrl)
			->addScriptOptions('groupMembersUrl', $groupMembersUrl)
			->addScriptOptions('groupBillingUrl', $groupBillingUrl)
			->addScriptOptions('storeNumberMembersUrl', $storeNumberMembersUrl)
			->addScriptOptions('storeGroupMembersDataUrl', $storeGroupMembersDataUrl)
			->addScriptOptions('storeGroupBillingDataUrl', $storeGroupBillingDataUrl)
			->addScriptOptions('calculateGroupRegistrationFeeUrl', $calculateGroupRegistrationFeeUrl);

		// Load input mask if required
		$requireImask = false;

		// First check group members fields
		foreach ($rowFields as $rowField)
		{
			if ($rowField->input_mask)
			{
				$requireImask = true;
				break;
			}
		}

		// Check billing fields
		$billingFields = EventbookingHelperRegistration::getFormFields($this->event->id, 1, null, null, $typeOfRegistration);
		EventbookingHelperRegistration::passFieldPaymentMethodDataToJS($billingFields);

		if (!$requireImask)
		{
			foreach ($billingFields as $billingField)
			{
				if ($billingField->input_mask)
				{
					$requireImask = true;
					break;
				}
			}
		}

		if ($requireImask)
		{
			Factory::getApplication()->getDocument()->addScript(Uri::root(true) . '/media/com_eventbooking/assets/js/imask/imask.min.js');
		}
	}
}
