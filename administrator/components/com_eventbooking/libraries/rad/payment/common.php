<?php
/**
 * Part of the Ossolution Payment Package
 *
 * @copyright  Copyright (C) 2015 - 2016 Ossolution Team. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

trait RADPaymentCommon
{
	/**
	 * Payment Fee
	 *
	 * @var bool
	 */
	public $paymentFee = false;

	/**
	 * Payment method icon uri
	 *
	 * @var string
	 */
	public $iconUri = '';

	/**
	 * Method to check if the payment plugin supports refund payment
	 *
	 * @return bool
	 */
	public function supportRefundPayment()
	{
		return method_exists($this, 'refund');
	}

	/**
	 * Method to check whether we need to show card type on form for this payment method.
	 * Always return false as when use Omnipay, we don't need card type parameter. It can be detected automatically
	 * from given card number
	 *
	 * @return bool|int
	 */
	public function getCardType()
	{
		return 0;
	}

	/**
	 * Method to check whether we need to show card holder name in the form
	 *
	 * @return bool|int
	 */
	public function getCardHolderName()
	{
		return $this->type;
	}

	/**
	 * Method to check whether we need to show card cvv input on form
	 *
	 * @return bool|int
	 */
	public function getCardCvv()
	{
		return $this->type;
	}

	/**
	 * Method to check if this payment method is a CreditCard based payment method
	 *
	 * @return int
	 */
	public function getCreditCard()
	{
		return $this->type;
	}

	/**
	 * Get name of the payment method
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get title of the payment method
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set title of the payment method
	 *
	 * @param $title String
	 */

	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 *  This method is called when payment for the registration is success, it needs to be used by all payment class
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $transactionId
	 */
	protected function onPaymentSuccess($row, $transactionId)
	{
		$config = EventbookingHelper::getConfig();

		if ($row->process_deposit_payment)
		{
			$row->payment_processing_fee         += $row->deposit_payment_processing_fee;
			$row->amount                         += $row->deposit_payment_processing_fee;
			$row->deposit_payment_transaction_id = $transactionId;
			$row->payment_status                 = 1;

			$row->store();

			PluginHelper::importPlugin('eventbooking');
			Factory::getApplication()->triggerEvent('onDepositPaymentSuccess', [$row]);
			EventbookingHelper::callOverridableHelperMethod('Mail', 'sendDepositPaymentEmail', [$row, $config]);
		}
		else
		{
			$row->transaction_id = $transactionId;
			$row->payment_date   = gmdate('Y-m-d H:i:s');

			// If user from waiting list and make payment, we change register date to current date
			if ($row->published == 3)
			{
				$row->register_date = Factory::getDate()->toSql();
			}

			$params = new Registry($row->params);

			if ($params->get('process_registration_payment'))
			{
				$row->payment_method = get_class($this);
				$params->set('process_registration_payment', 0);
				$row->params = $params->toString();
			}

			$row->published = 1;
			$row->store();

			if ($row->is_group_billing)
			{
				EventbookingHelperRegistration::updateGroupRegistrationRecord($row->id);
			}

			PluginHelper::importPlugin('eventbooking');
			Factory::getApplication()->triggerEvent('onAfterPaymentSuccess', [$row]);

			EventbookingHelper::callOverridableHelperMethod('Mail', 'sendEmails', [$row, $config]);
		}
	}

	/**
	 * Get payment complete URL
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $Itemid
	 * @param   bool                         $absolute
	 *
	 * @return string
	 */
	protected function getPaymentCompleteUrl($row, $Itemid, $absolute = false)
	{
		$langLink = EventbookingHelper::getLangLink();

		if ($row->process_deposit_payment)
		{
			$url = 'index.php?option=com_eventbooking&view=payment&layout=complete&registration_code=' . $row->registration_code . '&Itemid=' . $Itemid . $langLink;
		}
		else
		{
			$registrationCompleteItemid = EventbookingHelperRoute::findView('complete', $Itemid);
			$url                        = 'index.php?option=com_eventbooking&view=complete&registration_code=' . $row->registration_code . '&Itemid=' . $registrationCompleteItemid . $langLink;
		}

		return Route::_($url, false, 0, $absolute);
	}

	/**
	 * Get payment failure URL
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $Itemid
	 * @param   bool                         $absolute
	 *
	 * @return string
	 */
	protected function getPaymentFailureUrl($row, $Itemid, $absolute = false): string
	{
		$url = 'index.php?option=com_eventbooking&view=failure&Itemid=' . $Itemid;

		return Route::_($url, false, 0, $absolute);
	}

	/**
	 * Get payment failure URL
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $Itemid
	 * @param   bool                         $absolute
	 *
	 * @return string
	 */
	protected function getPaymentCancelUrl($row, $Itemid, $absolute = false): string
	{
		$url = 'index.php?option=com_eventbooking&view=cancel&layout=default&id=' . $row->id . '&Itemid=' . $Itemid;

		return Route::_($url, false, 0, $absolute);
	}

	/**
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $Itemid
	 * @param   string                       $queryString
	 *
	 * @return string
	 */
	protected function getPaymentNotifyUrl($row, $Itemid = 0, $queryString = ''): string
	{
		$url = Uri::root() . 'index.php?option=com_eventbooking&task=payment_confirm&payment_method=' . $this->getName();

		if ($queryString)
		{
			$url .= '&' . $queryString;
		}

		if ($Itemid > 0)
		{
			$url .= '&Itemid=' . $Itemid;
		}

		$url .= EventbookingHelper::getLangLink();

		return $url;
	}

	/**
	 * Store payment error message into session to have it displayed on payment failure page
	 *
	 * @param   string  $error
	 *
	 * @return void
	 */
	protected function setPaymentErrorMessage($error): void
	{
		Factory::getApplication()->getSession()->set('omnipay_payment_error_reason', $error);
	}

	/**
	 * Handle payment error. Store the error message in the session and redirect user to payment
	 * failure page
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   int                          $Itemid
	 * @param   string                       $message
	 *
	 * @return void
	 */
	protected function handlePaymentError($row, $Itemid, $message)
	{
		$url = $this->getPaymentFailureUrl($row, $Itemid);
		$this->setPaymentErrorMessage($message);
		Factory::getApplication()->redirect($url);
	}

	/**
	 * Method to get a table class from Events Booking
	 *
	 * @param   string  $name
	 *
	 * @return Table
	 */
	protected function getTable($name)
	{
		$db    = Factory::getDbo();
		$class = 'EventbookingTable' . ucfirst($name);

		return new $class($db);
	}

	/**
	 * Method to get Registrant table object
	 *
	 * @return EventbookingTableRegistrant
	 */
	protected function getRegistrantTable()
	{
		return $this->getTable('Registrant');
	}

	/**
	 * Method to check if the payment success process for this transaction already processed
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $transactionId
	 *
	 * @return bool
	 */
	protected function transactionProcessed($row, $transactionId = ''): bool
	{
		if ($row->published == 1 && $row->payment_status)
		{
			return true;
		}

		return false;
	}

	/**
	 * Get payment plugin layout
	 *
	 * @param   string  $layout
	 *
	 * @return string
	 */
	protected function getLayoutPath($layout = 'form')
	{
		/* @var \Joomla\CMS\Application\CMSApplication $app */
		$app      = Factory::getApplication();
		$template = $app->getTemplate();

		// Remove os_ from plugin name and remove any _ characters to get folder name
		$name = str_replace(
			'_',
			'',
			substr($this->getName(), 3)
		);

		if (File::exists(JPATH_THEMES . '/' . $template . '/html/com_eventbooking/payments/' . $name . '/' . $layout . '.php'))
		{
			return JPATH_THEMES . '/' . $template . '/html/com_eventbooking/payments/' . $name . '/' . $layout . '.php';
		}

		return JPATH_ROOT . '/components/com_eventbooking/payments/' . $name . '/tmpl/' . $layout . '.php';
	}
}
