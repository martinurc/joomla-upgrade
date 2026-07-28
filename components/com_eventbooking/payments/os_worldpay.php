<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class os_worldpay extends RADPaymentOmnipay
{
	protected $omnipayPackage = 'WorldPay';

	/**
	 * Constructor
	 *
	 * @param   \Joomla\Registry\Registry  $params
	 * @param   array                      $config
	 */
	public function __construct($params, $config = [])
	{
		$config['params_map'] = [
			'installationId'   => 'wp_installation_id',
			'callbackPassword' => 'wp_callback_password',
			'testMode'         => 'worldpay_mode',
		];

		parent::__construct($params, $config);
	}
}
