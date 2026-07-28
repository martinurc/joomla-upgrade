<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

class EventbookingViewUsercouponsHtml extends RADViewList
{
	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Available Types of Discount
	 *
	 * @var array
	 */
	protected $discountTypes = [];

	/**
	 * Prepare the view before it's being rendered
	 *
	 * @throws Exception
	 */
	protected function prepareView()
	{
		// Require user to login before allowing access to user coupons page
		$this->requestLogin();

		$config = EventbookingHelper::getConfig();

		$discountTypes       = [0 => '%', 1 => $config->get('currency_symbol', '$'), 2 => Text::_('EB_VOUCHER')];
		$this->discountTypes = $discountTypes;
		$this->config        = $config;

		parent::prepareView();
	}
}
