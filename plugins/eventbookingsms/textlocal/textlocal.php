<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;

class plgEventbookingSMSTextlocal extends CMSPlugin
{
	public function onEBSendingSMSReminder($rows)
	{
		if (!$this->params->get('api_key'))
		{
			return false;
		}

		foreach ($rows as $row)
		{
			$this->sendSMS([$this->sanitize($row->phone)], $row->sms_message);
		}

		// Return true to tell the system that SMS were successfully sent so that it could update sms sending status for registrants
		return true;
	}

	/**
	 * Method to send SMS messages
	 *
	 * @param   array   $phones
	 * @param   string  $smsMessage
	 * @param   string  $sender
	 */
	private function sendSMS($phones, $smsMessage)
	{
		$http = HttpFactory::getHttp();
		$data = [
			'apikey'  => $this->params->get('api_key'),
			'numbers' => implode(',', $phones),
			'sender'  => $this->params->get('sender', 'TXTLCL'),
			'message' => $smsMessage,
		];

		try
		{
			$response = $http->post('https://api.txtlocal.com/send/', $data);
			// EventbookingHelper::logData(__DIR__ . '/textlocal.txt', ['code' => $response->code, 'body' => $response->body]);
		}
		catch (Exception $e)
		{
			EventbookingHelper::logData(__DIR__ . '/textlocal.txt', $data, $e->getMessage());
		}
	}

	/**
	 * Helper method used to sanitize phone numbers.
	 *
	 * @param   string  $phone  The phone number to sanitize.
	 *
	 * @return    string    The cleansed number.
	 */
	protected function sanitize($phone)
	{
		$phone = trim(str_replace(' ', '', $phone));

		if (substr($phone, 0, 1) != '+')
		{
			if (substr($phone, 0, 2) == '00')
			{
				$phone = '+' . substr($phone, 2);
			}
			else
			{
				$phone = $this->params->get('prefix') . $phone;
			}
		}

		return $phone;
	}
}
