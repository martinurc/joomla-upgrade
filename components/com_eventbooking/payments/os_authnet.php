<?php

/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;

use Omnipay\AuthorizeNet\Message\AbstractRequest;

class os_authnet extends RADPaymentOmnipay
{
	protected $omnipayPackage = 'AuthorizeNet_AIM';

	/**
	 * Constructor
	 *
	 * @param   \Joomla\Registry\Registry  $params
	 * @param   array                      $config
	 */
	public function __construct($params, $config = ['type' => 1])
	{
		// Use sandbox sandbox account API Login and Transaction Key for test mode
		if (!$params->get('authnet_mode'))
		{
			if ($params->get('sandbox_x_login'))
			{
				$params->set('x_login', $params->get('sandbox_x_login'));
			}

			if ($params->get('sandbox_x_tran_key'))
			{
				$params->set('x_tran_key', $params->get('sandbox_x_tran_key'));
			}
		}

		$config['params_map'] = [
			'apiLoginId'     => 'x_login',
			'transactionKey' => 'x_tran_key',
			'developerMode'  => 'authnet_mode',
		];

		parent::__construct($params, $config);
	}

	/**
	 * Pass additional gateway data to payment gateway
	 *
	 * @param   AbstractRequest          $request
	 * @param   \Joomla\CMS\Table\Table  $row
	 * @param   array                    $data
	 */
	protected function beforeRequestSend($request, $row, $data)
	{
		$event = EventbookingHelperDatabase::getEvent($row->event_id);

		if (!empty($event->api_login) && !empty($event->transaction_key))
		{
			$request->setApiLoginId($event->api_login);
			$request->setTransactionKey($event->transaction_key);
		}

		parent::beforeRequestSend($request, $row, $data);

		$request->setInvoiceNumber($row->id);
	}
}
