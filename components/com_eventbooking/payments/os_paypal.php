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
use Joomla\CMS\Language\Multilanguage;

class os_paypal extends RADPayment
{
	/**
	 * Constructor functions, init some parameter
	 *
	 * @param   \Joomla\Registry\Registry  $params
	 */
	public function __construct($params, $config = [])
	{
		parent::__construct($params, $config);

		$this->mode = $params->get('paypal_mode');

		if ($this->mode)
		{
			$this->url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		else
		{
			$this->url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

			if (!$this->params->get('sandbox_paypal_id'))
			{
				$this->params->set('sandbox_paypal_id', $this->params->get('paypal_id'));
			}
		}

		$this->setParameter('business', $this->mode ? $this->params->get('paypal_id') : $this->params->get('sandbox_paypal_id'));
		$this->setParameter('rm', 2);
		$this->setParameter('cmd', '_xclick');
		$this->setParameter('no_shipping', 1);
		$this->setParameter('no_note', 1);
		$this->setParameter('charset', 'utf-8');
		$this->setParameter('tax', 0);

		$locale = $params->get('paypal_locale');

		if (empty($locale))
		{
			if (Multilanguage::isEnabled())
			{
				$locale = Factory::getLanguage()->getTag();
				$locale = str_replace('-', '_', $locale);
			}
			else
			{
				$locale = 'en_US';
			}
		}

		$this->setParameter('lc', $locale);
	}

	/**
	 * Process Payment
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   array                        $data
	 */
	public function processPayment($row, $data)
	{
		$Itemid = Factory::getApplication()->input->getInt('Itemid', 0);

		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		if ($paypalEmail = $this->getPayPalEmail($event))
		{
			$this->setParameter('business', $paypalEmail);
		}

		$this->setParameter('currency_code', $data['currency']);
		$this->setParameter('item_name', $data['item_name']);
		$this->setParameter('amount', round($data['amount'], 2));
		$this->setParameter('custom', $row->id);

		$this->setParameter('return', $this->getPaymentCompleteUrl($row, $Itemid, true));

		if ($row->state)
		{
			$config  = EventbookingHelper::getConfig();
			$country = $row->country ?: $config->default_country;
			$state   = EventbookingHelper::getStateCode($country, $row->state);
		}
		else
		{
			$state = '';
		}

		$this->setParameter('notify_url', $this->getPaymentNotifyUrl($row));
		$this->setParameter('cancel_return', $this->getPaymentCancelUrl($row, $Itemid, true));
		$this->setParameter('address1', $row->address);
		$this->setParameter('address2', $row->address2);
		$this->setParameter('city', $row->city);
		$this->setParameter('country', $data['country']);
		$this->setParameter('first_name', $row->first_name);
		$this->setParameter('last_name', $row->last_name);
		$this->setParameter('state', $state);
		$this->setParameter('zip', $row->zip);
		$this->setParameter('email', $row->email);

		$this->renderRedirectForm();
	}

	/**
	 * Verify payment
	 *
	 * @return bool
	 */
	public function verifyPayment()
	{
		if (!$this->validate())
		{
			return false;
		}

		$id            = $this->notificationData['id'];
		$transactionId = $this->notificationData['transaction_id'];
		$amount        = $this->notificationData['amount'];

		if ($amount < 0)
		{
			return false;
		}

		$row = $this->getRegistrantTable();

		if (!$row->load($id))
		{
			return false;
		}

		if ($this->transactionProcessed($row, $transactionId))
		{
			return false;
		}

		// Validate receiver account
		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		if ($paypalEmail = $this->getPayPalEmail($event))
		{
			$payPalId = $paypalEmail;
		}
		else
		{
			$payPalId = $this->mode ? $this->params->get('paypal_id') : $this->params->get('sandbox_paypal_id');
		}

		$receiverEmail = strtoupper($this->notificationData['receiver_email']);
		$receiverId    = strtoupper($this->notificationData['receiver_id']);
		$business      = strtoupper($this->notificationData['business']);
		$payPalId      = strtoupper($payPalId);

		if ($receiverEmail != $payPalId && $receiverId != $payPalId && $business != $payPalId)
		{
			return false;
		}

		// Validate currency
		$receivedPaymentCurrency = strtoupper($this->notificationData['mc_currency']);

		if ($receivedPaymentCurrency != strtoupper($row->payment_currency))
		{
			return false;
		}

		// Validate payment amount, accept 0.05$ difference
		if (($row->payment_amount - $amount) > 0.05)
		{
			return false;
		}

		$this->onPaymentSuccess($row, $transactionId);

		return true;
	}

	/**
	 * Get list of supported currencies
	 *
	 * @return array
	 */
	public function getSupportedCurrencies()
	{
		return [
			'AUD',
			'BRL',
			'CAD',
			'CZK',
			'DKK',
			'EUR',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'MYR',
			'MXN',
			'NOK',
			'NZD',
			'PHP',
			'PLN',
			'GBP',
			'RUB',
			'SGD',
			'SEK',
			'CHF',
			'TWD',
			'THB',
			'TRY',
			'USD',
			'INR',
		];
	}

	/**
	 * Validate the post data from paypal to our server
	 *
	 * @return string
	 */
	protected function validate()
	{
		JLoader::register('PaypalIPN', JPATH_ROOT . '/components/com_eventbooking/payments/paypal/PayPalIPN.php');

		$ipn = new PaypalIPN();

		// Use sandbox URL if test mode is configured
		if (!$this->mode)
		{
			$ipn->useSandbox();
		}

		// Disable use custom certs
		if ($this->params->get('use_local_certs', 0) == 0)
		{
			// Disable use custom certs
			$ipn->usePHPCerts();
		}

		$this->notificationData = $_POST;

		try
		{
			$valid = $ipn->verifyIPN();
			$this->logGatewayData($ipn->getResponse());

			if (!$this->mode || $valid)
			{
				$this->notificationData['id']             = (int) $this->notificationData['custom'];
				$this->notificationData['transaction_id'] = $this->notificationData['txn_id'];
				$this->notificationData['amount']         = (float) $this->notificationData['mc_gross'];

				return true;
			}

			return false;
		}
		catch (Exception $e)
		{
			$this->logGatewayData($e->getMessage());

			return false;
		}
	}

	/**
	 * Method to check if API Credentials is entered into the payment plugin parameters
	 */
	public function supportRefundPayment()
	{
		[$apiUrl, $apiUser, $apiPassword, $apiSignature] = $this->getNvpApiParameters();

		return $apiUser && $apiPassword && $apiSignature;
	}

	/**
	 * Refund payment
	 *
	 * @param $row
	 *
	 * @return bool
	 * @throws Exception
	 *
	 * @since 1.0
	 */
	public function refund($row)
	{
		[$apiUrl, $apiUser, $apiPassword, $apiSignature] = $this->getNvpApiParameters();

		if (!$apiUser || !$apiPassword || !$apiSignature)
		{
			Factory::getApplication()->enqueueMessage(
				'You need to enter API parameters in Advanced tab of the payment plugin to be able to refund',
				'error'
			);

			return false;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_URL, $apiUrl);
		curl_setopt(
			$curl,
			CURLOPT_POSTFIELDS,
			http_build_query([
				'USER'          => $apiUser,
				'PWD'           => $apiPassword,
				'SIGNATURE'     => $apiSignature,
				'VERSION'       => '108',
				'METHOD'        => 'RefundTransaction',
				'TRANSACTIONID' => $row->transaction_id,
				'REFUNDTYPE'    => 'Full',
			])
		);

		$response = curl_exec($curl);
		curl_close($curl);

		$nvp = $this->deformatNVP($response);

		if ($nvp['ACK'] == 'Success')
		{
			return true;
		}
		Factory::getApplication()->enqueueMessage($nvp['L_LONGMESSAGE0'], 'error');

		return false;
	}

	/**
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return string
	 */
	private function getPayPalEmail($event)
	{
		// If PayPal email is entered in the event, use it
		if (strlen(trim($event->paypal_email)))
		{
			return trim($event->paypal_email);
		}

		// Try to find PayPal Email from the category
		$category = EventbookingHelperDatabase::getCategory($event->main_category_id);

		// If PayPal email is entered in the category, use it
		if (strlen(trim($category->paypal_email)))
		{
			return trim($category->paypal_email);
		}

		return '';
	}

	/**
	 * Get NvpApi Parameters
	 *
	 * @return array
	 */
	private function getNvpApiParameters()
	{
		if ($this->mode)
		{
			$apiUrl       = 'https://api-3t.paypal.com/nvp';
			$apiUser      = $this->params->get('paypal_api_user');
			$apiPassword  = $this->params->get('paypal_api_password');
			$apiSignature = $this->params->get('paypal_api_signature');
		}
		else
		{
			$apiUrl       = 'https://api-3t.sandbox.paypal.com/nvp';
			$apiUser      = $this->params->get('paypal_api_user_sandbox');
			$apiPassword  = $this->params->get('paypal_api_password_sandbox');
			$apiSignature = $this->params->get('paypal_api_signature_sandbox');
		}

		return [$apiUrl, $apiUser, $apiPassword, $apiSignature];
	}

	/**
	 * Extract response from PayPal into array
	 *
	 * @param $response
	 *
	 * @return array
	 */
	private function deformatNVP($response)
	{
		$nvp = [];

		parse_str(urldecode($response), $nvp);

		return $nvp;
	}
}
