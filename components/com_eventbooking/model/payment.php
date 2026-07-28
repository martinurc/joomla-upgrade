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
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

class EventbookingModelPayment extends RADModel
{
	/**
	 * Process reminder payment for registration
	 *
	 * @param   array  $data
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processPayment($data)
	{
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$config = EventbookingHelper::getConfig();

		/* @var EventbookingTableRegistrant $row */
		$row = $this->getTable('Registrant');
		$row->load((int) $data['registrant_id']);

		// Calculate the payment amount
		$paymentMethod = $data['payment_method'] ?? '';

		$row->deposit_payment_method = $paymentMethod;

		// Mark the registration record as "deposit payment processing"
		$row->process_deposit_payment = 1;

		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		$data['event_title'] = $event->title;

		// Store registration_code into session, use for registration complete code
		Factory::getApplication()->getSession()->set('payment_id', $row->id);

		if ($row->deposit_amount > 0)
		{
			require_once JPATH_COMPONENT . '/payments/' . $paymentMethod . '.php';

			$fees = EventbookingHelper::callOverridableHelperMethod('Registration', 'calculateRemainderFees', [$row, $paymentMethod]);

			$row->deposit_payment_processing_fee = $fees['payment_processing_fee'];

			$row->store();

			$data['amount'] = $fees['gross_amount'];

			$itemName = Text::_('EB_PROCESS_DEPOSIT_PAYMENT');
			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row);

			$itemName = EventbookingHelper::replaceCaseInsensitiveTags($itemName, $replaces);

			$itemName          = str_replace('[REGISTRATION_ID]', $row->id, $itemName);
			$data['item_name'] = $itemName;

			// Guess card type based on card number
			if (!empty($data['x_card_num']) && empty($data['card_type']))
			{
				$data['card_type'] = EventbookingHelperCreditcard::getCardType($data['x_card_num']);
			}

			$query->clear()
				->select('title, params')
				->from('#__eb_payment_plugins')
				->where('name = ' . $db->quote($paymentMethod));
			$db->setQuery($query);
			$plugin       = $db->loadObject();
			$params       = new Registry($plugin->params);
			$paymentClass = new $paymentMethod($params);
			$paymentClass->setTitle(Text::_($plugin->title));

			// Convert payment amount to USD if the currency is not supported by payment gateway
			$currency = $event->currency_code ?: $config->currency_code;

			if (method_exists($paymentClass, 'getSupportedCurrencies'))
			{
				$currencies = $paymentClass->getSupportedCurrencies();

				if (!in_array($currency, $currencies))
				{
					$data['amount'] = EventbookingHelper::callOverridableHelperMethod('Helper', 'convertAmountToUSD', [$data['amount'], $currency]);
					$currency       = 'USD';
				}
			}

			$data['currency'] = $currency;

			$country         = empty($data['country']) ? $config->default_country : $data['country'];
			$data['country'] = EventbookingHelper::getCountryCode($country);

			// Store payment amount and payment currency for future validation
			$row->payment_currency = $currency;
			$row->payment_amount   = $data['amount'];
			$row->store();

			try
			{
				$paymentClass->processPayment($row, $data);
			}
			catch (RADPaymentException $e)
			{
				// Redirect to payment failure page
				$app    = Factory::getApplication();
				$Itemid = $app->input->getInt('Itemid');
				$url    = Route::_('index.php?option=com_eventbooking&view=failure&Itemid=' . $Itemid, false);
				$app->getSession()->set('omnipay_payment_error_reason', $e->getMessage());
				$app->redirect($url);
			}
		}
		else
		{
			echo Text::_('EB_INVALID_DEPOSIT_PAYMENT');
		}
	}

	/**
	 * Process individual registration
	 *
	 * @param   array  $data
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processRegistrationPayment($data)
	{
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$config = EventbookingHelper::getConfig();
		$row    = $this->getTable('Registrant');
		$row->load((int) $data['registrant_id']);
		$paymentMethod = $data['payment_method'] ?? '';
		$event         = EventbookingHelperDatabase::getEvent($row->event_id);

		// Store registration_code into session, use for registration complete code
		Factory::getApplication()->getSession()->set('eb_registration_code', $row->registration_code);

		if ($row->amount > 0)
		{
			require_once JPATH_COMPONENT . '/payments/' . $paymentMethod . '.php';

			// Make the record as processing registration payment
			$params = new Registry($row->params);
			$params->set('process_registration_payment', 1);
			$row->params = $params->toString();

			$fees = EventbookingHelper::callOverridableHelperMethod('Registration', 'calculateRegistrationFees', [$row, $paymentMethod]);

			$row->payment_processing_fee = $fees['payment_processing_fee'];
			$row->amount                 = $fees['gross_amount'];
			$row->store();

			$data['amount'] = $fees['gross_amount'];

			$itemName          = Text::_('EB_PROCESS_REGISTRATION_PAYMENT');
			$itemName          = str_replace('[EVENT_TITLE]', $event->title, $itemName);
			$itemName          = str_replace('[REGISTRATION_ID]', $row->id, $itemName);
			$data['item_name'] = $itemName;

			// Guess card type based on card number
			if (!empty($data['x_card_num']) && empty($data['card_type']))
			{
				$data['card_type'] = EventbookingHelperCreditcard::getCardType($data['x_card_num']);
			}

			$query->clear()
				->select('params')
				->from('#__eb_payment_plugins')
				->where('name = ' . $db->quote($paymentMethod));
			$db->setQuery($query);
			$params       = new Registry($db->loadResult());
			$paymentClass = new $paymentMethod($params);

			// Convert payment amount to USD if the currency is not supported by payment gateway
			$currency = $event->currency_code ?: $config->currency_code;

			if (method_exists($paymentClass, 'getSupportedCurrencies'))
			{
				$currencies = $paymentClass->getSupportedCurrencies();

				if (!in_array($currency, $currencies))
				{
					$data['amount'] = EventbookingHelper::callOverridableHelperMethod('Helper', 'convertAmountToUSD', [$data['amount'], $currency]);
					$currency       = 'USD';
				}
			}

			$data['currency'] = $currency;

			$country         = empty($data['country']) ? $config->default_country : $data['country'];
			$data['country'] = EventbookingHelper::getCountryCode($country);

			// Store payment amount and payment currency for future validation
			$row->payment_currency = $currency;
			$row->payment_amount   = $data['amount'];
			$row->store();

			$paymentClass->processPayment($row, $data);
		}
		else
		{
			echo Text::_('EB_INVALID_DEPOSIT_PAYMENT');
		}
	}
}
