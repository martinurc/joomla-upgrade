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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class EventbookingViewCompleteHtml extends RADViewHtml
{
	/**
	 * Flag to tell the system that this view does not have associated model
	 *
	 * @var bool
	 */
	public $hasModel = false;

	/**
	 * The registered registration record
	 *
	 * @var stdClass
	 */
	protected $rowRegistrant;

	/**
	 * The thank you massage
	 *
	 * @var string
	 */
	protected $message;

	/**
	 * Twitter Bootstrap Helper
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * The registration code of the registration record
	 *
	 * @var string
	 */
	protected $registrationCode;

	/**
	 * The conversion tracking code
	 *
	 * @var string
	 */
	protected $conversionTrackingCode;
	/**
	 * Should we show print button
	 *
	 * @var bool
	 */
	protected $showPrintButton;

	/**
	 * Are we on printing state
	 *
	 * @var bool
	 */
	protected $print;

	/**
	 * Preview view data before it's being rendered
	 *
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		//Hardcoded the layout, it happens with some clients. Maybe it is a bug of Joomla core code, will find out it later
		$this->setLayout('default');

		$app         = Factory::getApplication();
		$config      = EventbookingHelper::getConfig();
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if ($this->input->getString('registration_code'))
		{
			$registrationCode = $this->input->getString('registration_code');
		}
		else
		{
			$registrationCode = $app->getSession()->get('eb_registration_code', '');
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_registrants')
			->where('registration_code = ' . $db->quote($registrationCode));
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (!$registrationCode || !$rowRegistrant)
		{
			$app->enqueueMessage(Text::_('EB_INVALID_REGISTRATION_CODE'), 'warning');
			$app->redirect(Uri::root(), 404);
		}

		// Wait up to 18 seconds for payment gateway to notify the system about the payment if status of the registration record is still Pending
		if ($rowRegistrant->amount > 0 && $rowRegistrant->published == 0 && strpos($rowRegistrant->payment_method, 'os_offline') === false)
		{
			for ($i = 0; $i < 6; $i++)
			{
				sleep(3);

				// Reload the registration record from database to get updated data
				$db->setQuery($query);
				$rowRegistrant = $db->loadObject();

				if ($rowRegistrant->published == 1)
				{
					break;
				}
			}
		}

		if ($rowRegistrant->published == 0 && (in_array($rowRegistrant->payment_method, ['os_ideal', 'os_payu'])))
		{
			// Use online payment method and the payment is not success for some reason, we need to redirect to failure page
			$failureUrl = Route::_(
				'index.php?option=com_eventbooking&view=failure&id=' . $rowRegistrant->id . '&Itemid=' . $this->Itemid,
				false,
				false
			);
			$app->enqueueMessage(Text::_('EB_PAYMENT_ERROR_MESSAGE'), 'warning');
			$app->redirect($failureUrl);
		}

		// Make the registration complete page accessible within 30 minutes only to prevent leaking data
		$date         = Factory::getDate();
		$registerDate = Factory::getDate($rowRegistrant->register_date);
		$registerDate->modify('+30 minutes');

		if ($date > $registerDate)
		{
			// Make the registration complete page accessible within 30 minutes only
			$app->enqueueMessage('The link is expired', 'error');

			return;
		}

		$rowEvent    = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);
		$rowCategory = EventbookingHelperDatabase::getCategory($rowEvent->main_category_id);

		EventbookingHelper::setEventMessagesDataFromCategory($rowEvent, $rowCategory, ['thanks_message', 'thanks_message_offline'], $fieldSuffix);

		if (strpos($rowRegistrant->payment_method, 'os_offline') !== false
			&& $rowRegistrant->published == 0
			&& $rowEvent->offline_payment_registration_complete_url
			&& filter_var($rowEvent->offline_payment_registration_complete_url, FILTER_VALIDATE_URL))
		{
			$app->redirect($rowEvent->offline_payment_registration_complete_url);
		}
		elseif ($rowEvent->registration_complete_url && filter_var($rowEvent->registration_complete_url, FILTER_VALIDATE_URL))
		{
			$app->redirect($rowEvent->registration_complete_url);
		}

		if ($rowRegistrant->published == 0 && strpos($rowRegistrant->payment_method, 'os_offline') !== false)
		{
			$offlineSuffix = str_replace('os_offline', '', $rowRegistrant->payment_method);

			if ($offlineSuffix && $fieldSuffix && EventbookingHelper::isValidMessage(
					$message->{'thanks_message_offline' . $offlineSuffix . $fieldSuffix}
				))
			{
				$thankMessage = $message->{'thanks_message_offline' . $offlineSuffix . $fieldSuffix};
			}
			elseif ($offlineSuffix && EventbookingHelper::isValidMessage($message->{'thanks_message_offline' . $offlineSuffix}))
			{
				$thankMessage = $message->{'thanks_message_offline' . $offlineSuffix};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($rowEvent->{'thanks_message_offline' . $fieldSuffix}))
			{
				$thankMessage = $rowEvent->{'thanks_message_offline' . $fieldSuffix};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'thanks_message_offline' . $fieldSuffix}))
			{
				$thankMessage = $message->{'thanks_message_offline' . $fieldSuffix};
			}
			elseif (EventbookingHelper::isValidMessage($rowEvent->thanks_message_offline))
			{
				$thankMessage = $rowEvent->thanks_message_offline;
			}
			else
			{
				$thankMessage = $message->thanks_message_offline;
			}
		}
		else
		{
			if ($fieldSuffix && EventbookingHelper::isValidMessage($rowEvent->{'thanks_message' . $fieldSuffix}))
			{
				$thankMessage = $rowEvent->{'thanks_message' . $fieldSuffix};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'thanks_message' . $fieldSuffix}))
			{
				$thankMessage = $message->{'thanks_message' . $fieldSuffix};
			}
			elseif (EventbookingHelper::isValidMessage($rowEvent->thanks_message))
			{
				$thankMessage = $rowEvent->thanks_message;
			}
			else
			{
				$thankMessage = $message->thanks_message;
			}
		}

		$replaces = EventbookingHelperRegistration::getRegistrationReplaces($rowRegistrant, $rowEvent, 0, $config->multiple_booking, false);

		$thankMessage = EventbookingHelper::replaceCaseInsensitiveTags($thankMessage, $replaces);

		$thankMessage = EventbookingHelperRegistration::processQRCODE($rowRegistrant, $thankMessage);

		$trackingCode = $config->conversion_tracking_code;

		if (!empty($trackingCode))
		{
			$replaces['total_amount']           = $replaces['amt_total_amount'];
			$replaces['discount_amount']        = $replaces['amt_discount_amount'];
			$replaces['tax_amount']             = $replaces['amt_tax_amount'];
			$replaces['amount']                 = $replaces['amt_amount'];
			$replaces['payment_processing_fee'] = $replaces['amt_payment_processing_fee'];

			$trackingCode = EventbookingHelper::replaceCaseInsensitiveTags($trackingCode, $replaces);
		}

		// Add Breadcrumb
		$title = Text::_('EB_REGISTRATION_COMPLETE_PAGE_TITLE');
		$title = str_replace('[EVENT_TITLE]', $rowEvent->title, $title);
		$app->getPathway()->addItem($title);

		$thankMessage = EventbookingHelperHtml::processConditionalText($thankMessage);

		PluginHelper::importPlugin('eventbooking');
		$app->triggerEvent('onPrepareRegistrationCompleteMessage', [$rowRegistrant, &$thankMessage]);

		$this->rowRegistrant          = $rowRegistrant;
		$this->message                = $thankMessage;
		$this->bootstrapHelper        = EventbookingHelperBootstrap::getInstance();
		$this->conversionTrackingCode = $trackingCode;
		$this->registrationCode       = $registrationCode;
		$this->print                  = $this->input->getInt('print', 0);
		$this->showPrintButton        = $config->get('show_print_button', '1');

		Factory::getApplication()->getDocument()->setMetaData('robots', 'noindex, nofollow');

		// Reset cart
		if ($config->multiple_booking)
		{
			$cart = new EventbookingHelperCart();
			$cart->reset();
		}
	}
}
