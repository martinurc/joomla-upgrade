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
use Joomla\CMS\Uri\Uri;

class EventbookingViewPaymentHtml extends RADViewHtml
{
	use EventbookingViewCaptcha;

	/**
	 * Available payment methods
	 *
	 * @var array
	 */
	protected $methods;

	/**
	 * The payment fee data
	 *
	 * @var array
	 */
	protected $fees;

	/**
	 * The javascript onlick handle function
	 *
	 * @var string
	 */
	protected $onClickHandle;

	/**
	 * The bootstrap helper
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * The selected payment method
	 *
	 * @var string
	 */
	protected $paymentMethod;

	/**
	 * The component's config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * The event object data
	 *
	 * @var stdClass
	 */
	protected $event;

	/**
	 * The select lists use on form
	 *
	 * @var array
	 */
	protected $lists;

	/**
	 * The message object
	 *
	 * @var RADConfig
	 */
	protected $message;

	/**
	 * Field suffix of current language
	 *
	 * @var string
	 */
	protected $fieldSuffix;

	/**
	 * The form object
	 *
	 * @var RADForm
	 */
	protected $form;

	/**
	 * The registration record data
	 *
	 * @var stdClass
	 */
	protected $rowRegistrant;

	/**
	 * Flag to mark if we should show payment fee
	 *
	 * @var bool
	 */
	protected $showPaymentFee;

	/**
	 * The currency symbol
	 *
	 * @var string
	 */
	protected $currencySymol;

	/**
	 * Display interface to user
	 */
	public function display()
	{
		$layout = $this->getLayout();

		if ($layout == 'complete')
		{
			$this->displayPaymentComplete();

			return;
		}

		// Load common js code
		$document = Factory::getApplication()->getDocument();
		$rootUri  = Uri::root(true);

		$document->addScriptDeclaration(
			'var siteUrl = "' . EventbookingHelper::getSiteUrl() . '";'
		);

		EventbookingHelperJquery::loadjQuery();
		EventbookingHelperHtml::addOverridableScript(
			'media/com_eventbooking/assets/js/paymentmethods.min.js',
			['version' => EventbookingHelper::getInstalledVersion()]
		);

		$customJSFile = JPATH_ROOT . '/media/com_eventbooking/assets/js/custom.js';

		if (file_exists($customJSFile) && filesize($customJSFile) > 0)
		{
			$document->addScript($rootUri . '/media/com_eventbooking/assets/js/custom.js');
		}

		EventbookingHelper::addLangLinkForAjax();

		if ($layout == 'registration')
		{
			$this->displayRegistrationPayment();

			return;
		}

		$registrationCode = $this->input->getString('order_number') ?: $this->input->getString('registration_code');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_registrants')
			->where('registration_code = ' . $db->quote($registrationCode));
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (empty($rowRegistrant))
		{
			echo Text::_('EB_INVALID_REGISTRATION_RECORD');

			return;
		}

		if ($rowRegistrant->payment_status == 1)
		{
			echo Text::_('EB_DEPOSIT_PAYMENT_COMPLETED');

			return;
		}

		$event    = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);
		$category = EventbookingHelperDatabase::getCategory($event->main_category_id);

		if (!$event->payment_methods && $category->payment_methods)
		{
			$event->payment_methods = $category->payment_methods;
		}

		$this->setBaseFormData($rowRegistrant, $event);

		if (count($this->methods) == 0)
		{
			echo Text::_('EB_ENABLE_PAYMENT_METHODS');

			return;
		}

		$config = EventbookingHelper::getConfig();

		$document->addScriptOptions('currencyCode', $event->currency_code ?: $config->currency_code);

		$fees = EventbookingHelper::callOverridableHelperMethod('Registration', 'calculateRemainderFees', [$rowRegistrant, $this->paymentMethod]);

		$this->loadCaptcha();

		// Assign these parameters
		$this->fees          = $fees;
		$this->onClickHandle = 'calculateRemainderFee();';

		// Force default layout to avoid error in case the active menu item uses the layout not supported
		$this->setLayout('default');

		parent::display();
	}

	/**
	 * Display form which allow users to click on to complete payment for a registration
	 *
	 * @return void
	 */
	protected function displayRegistrationPayment()
	{
		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);

		$registrationCode = $this->input->getString('order_number') ?: $this->input->getString('registration_code');

		$query->select('*')
			->from('#__eb_registrants')
			->where('registration_code = ' . $db->quote($registrationCode));
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (empty($rowRegistrant))
		{
			echo Text::_('EB_INVALID_REGISTRATION_RECORD');

			return;
		}

		if ($rowRegistrant->published == 1)
		{
			echo Text::_('EB_PAYMENT_WAS_COMPLETED');

			return;
		}

		if ($rowRegistrant->published == 2)
		{
			echo Text::_('EB_REGISTRATION_WAS_CANCELLED');

			return;
		}

		$event = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);

		$validateCapacity = $config->get('validate_event_capacity_for_waiting_list_payment', 1);

		if (property_exists($event, 'validate_capacity_for_waiting_list_payment') && $event->validate_capacity_for_waiting_list_payment !== '')
		{
			$validateCapacity = $event->validate_capacity_for_waiting_list_payment;
		}

		// Validate and make sure there is still space available to join
		if ($validateCapacity
			&& $rowRegistrant->published != 0
			&& $event->event_capacity > 0
			&& ($event->event_capacity - $event->total_registrants < $rowRegistrant->number_registrants))
		{
			echo Text::_('EB_EVENT_IS_FULL_COULD_NOT_JOIN');

			return;
		}

		$this->setBaseFormData($rowRegistrant, $event);

		if (count($this->methods) == 0)
		{
			echo Text::_('EB_ENABLE_PAYMENT_METHODS');

			return;
		}

		$fees = EventbookingHelper::callOverridableHelperMethod('Registration', 'calculateRegistrationFees', [$rowRegistrant, $this->paymentMethod]);

		Factory::getApplication()->getDocument()->addScriptOptions('currencyCode', $event->currency_code ?: $config->currency_code);

		// Assign these parameters
		$this->fees          = $fees;
		$this->onClickHandle = 'calculateRegistrationFee();';

		parent::display();
	}

	/**
	 * Method to calculate and set base form data
	 *
	 * @param   EventbookingTableRegistrant  $rowRegistrant
	 * @param   EventbookingTableEvent       $event
	 */
	protected function setBaseFormData($rowRegistrant, $event)
	{
		$config    = EventbookingHelper::getConfig();
		$user      = Factory::getUser();
		$userId    = $user->id;
		$rowFields = EventbookingHelper::callOverridableHelperMethod('Registration', 'getDepositPaymentFormFields');

		$captchaInvalid = $this->input->getInt('captcha_invalid', 0);

		if ($captchaInvalid)
		{
			$data = $this->input->post->getData();
		}
		else
		{
			$data = EventbookingHelperRegistration::getRegistrantData($rowRegistrant, $rowFields);

			// IN case there is no data, get it from URL (get for example)
			if (empty($data))
			{
				$data = $this->input->getData();
			}
		}

		if ($userId && !isset($data['first_name']))
		{
			//Load the name from Joomla default name
			[$firstName, $lastName] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'detectFirstAndLastNameFromFullName',
				[$user->name]
			);

			$data['first_name'] = $firstName;
			$data['last_name']  = $lastName;
		}

		if ($userId && !isset($data['email']))
		{
			$data['email'] = $user->email;
		}

		if (!isset($data['country']) || !$data['country'])
		{
			$data['country'] = $config->default_country;
		}

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

		$paymentMethod = $this->input->post->getString(
			'payment_method',
			EventbookingHelperPayments::getDefautPaymentMethod(trim($event->payment_methods), false)
		);

		$currentYear        = date('Y');
		$expMonth           = $this->input->post->getInt('exp_month', date('n'));
		$expYear            = $this->input->post->getInt('exp_year', $currentYear);
		$lists['exp_month'] = HTMLHelper::_('select.integerlist', 1, 12, 1, 'exp_month', [
			'list.select'   => $expMonth,
			'option.format' => '%02d',
			'list.attr'     => 'class="input-medium form-select"',
			'id'            => 'exp_month',
		]);
		$lists['exp_year']  = HTMLHelper::_('select.integerlist', $currentYear, $currentYear + 10, 1, 'exp_year', [
			'list.select' => $expYear,
			'list.attr'   => 'class="input-medium form-select"',
			'id'          => 'exp_year',
		]);

		$methods = EventbookingHelperPayments::getPaymentMethods(trim((string) $event->payment_methods), false);

		if (count($methods) == 0)
		{
			echo Text::_('EB_ENABLE_PAYMENT_METHODS');

			return;
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

		if (empty($paymentMethod) && count($methods))
		{
			$paymentMethod = $methods[0]->getName();
		}

		// Assign these parameters
		$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();
		$this->paymentMethod   = $paymentMethod;
		$this->config          = $config;
		$this->event           = $event;
		$this->methods         = $methods;
		$this->lists           = $lists;
		$this->message         = EventbookingHelper::getMessages();
		$this->fieldSuffix     = EventbookingHelper::getFieldSuffix();
		$this->message         = EventbookingHelper::getMessages();
		$this->form            = $form;
		$this->rowRegistrant   = $rowRegistrant;
		$this->showPaymentFee  = $showPaymentFee;
		$this->currencySymol   = $event->currency_symbol ?: $config->currency_symbol;

		$this->loadCaptcha();
	}

	/**
	 * Display payment complete page
	 */
	protected function displayPaymentComplete()
	{
		$config      = EventbookingHelper::getConfig();
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if (strlen(trim(strip_tags($message->{'deposit_payment_thanks_message' . $fieldSuffix}))))
		{
			$thankMessage = $message->{'deposit_payment_thanks_message' . $fieldSuffix};
		}
		else
		{
			$thankMessage = $message->deposit_payment_thanks_message;
		}

		$id = (int) Factory::getApplication()->getSession()->get('payment_id', 0);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_registrants')
			->where('id = ' . $id);
		$db->setQuery($query);
		$row = $db->loadObject();

		if (empty($row->id))
		{
			echo Text::_('Invalid Registration Record');

			return;
		}

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildDepositPaymentTags', [$row, $config]);

		$thankMessage = EventbookingHelper::replaceCaseInsensitiveTags($thankMessage, $replaces);

		$this->message = $thankMessage;

		parent::display();
	}
}
