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

class EventbookingViewFailureHtml extends RADViewHtml
{
	/**
	 * The payment error reason
	 *
	 * @var string
	 */
	protected $reason;

	/**
	 * Prepare data for the view before it's being rendered
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$this->setLayout('default');

		$app              = Factory::getApplication();
		$session          = $app->getSession();
		$reason           = $session->get('omnipay_payment_error_reason');
		$registrationCode = $session->get('eb_registration_code');

		if ($registrationCode)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_registrants')
				->where('registration_code = ' . $db->quote($registrationCode));
			$db->setQuery($query);
			$rowRegistrant = $db->loadObject();

			if ($reason && $rowRegistrant)
			{
				PluginHelper::importPlugin('eventbooking');
				$app->triggerEvent('onAfterEBPaymentFailure', [$rowRegistrant, $reason]);
			}
		}

		if (!$reason)
		{
			$reason = $this->input->getString('failReason', '');
		}

		if (!$reason)
		{
			$reason = Text::_('EB_PAYMENT_WAS_NOT_SUCCESS');
		}

		$this->reason = $reason;
	}
}
