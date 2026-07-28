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

class EventbookingControllerScan extends EventbookingController
{
	/**
	 * Checkin method using HTML5 QRCODE Reader
	 */
	public function qr_code_checkin()
	{
		$this->eb_qrcode_checkin();
	}

	/**
	 * Method to checkin registrant using EB QRCODE Checkin APP
	 *
	 * @return void
	 */
	public function eb_qrcode_checkin()
	{
		if (!$this->validateCheckinApiKey())
		{
			$response = [
				'success' => false,
				'message' => Text::_('EB_INVALID_API_KEY'),
			];

			$this->sendJsonResponse($response);

			return;
		}

		$ticketCode = $this->input->getString('value');

		[$success, $message] = $this->processCheckin($ticketCode);

		$response = [
			'success' => $success,
			'message' => $message,
		];

		$this->sendJsonResponse($response);
	}

	/**
	 * Method to checkin registrant using QRCODE APP
	 *
	 * @return void
	 */
	public function qr_code_plus()
	{
		if (!$this->validateCheckinApiKey())
		{
			$this->sendJsonResponse(['code' => 1, 'msg' => Text::_('EB_INVALID_API_KEY')]);

			return;
		}

		$ticketCode = $this->input->getString('code');

		[$success, $message] = $this->processCheckin($ticketCode);

		$this->sendJsonResponse(['code' => $success ? 0 : 1, 'msg' => $message]);
	}

	/**
	 * Checkin registrant base on provided ticket code
	 *
	 * @param   string  $code
	 *
	 * @return array
	 */
	protected function processCheckin($code)
	{
		$success = false;
		$message = '';

		if ($code)
		{
			$db         = Factory::getDbo();
			$ticketCode = $db->quote($code);
			$query      = $db->getQuery(true)
				->select('a.*, b.title AS event_title, b.event_date, b.event_end_date')
				->from('#__eb_registrants AS a')
				->innerJoin('#__eb_events AS b ON a.event_id = b.id')
				->where('(a.ticket_qrcode = ' . $ticketCode . ' OR a.ticket_code = ' . $ticketCode . ')');
			$db->setQuery($query);
			$rowRegistrant = $db->loadObject();

			if ($rowRegistrant)
			{
				$config = EventbookingHelper::getConfig();

				/* @var EventbookingModelRegistrant $model */
				$model  = $this->getModel('Registrant');
				$result = $model->checkinRegistrant($rowRegistrant->id, false, (bool) $config->get('validate_checkin_date', 1));


				switch ($result)
				{
					case 0:
						$message = Text::_('EB_INVALID_REGISTRATION_RECORD');
						break;
					case 1:
						$message = Text::_('EB_REGISTRANT_ALREADY_CHECKED_IN');
						break;
					case 3:
						$message = Text::_('EB_CHECKED_IN_FAIL_REGISTRATION_CANCELLED');
						break;
					case 2:
						$message = Text::_('EB_CHECKED_IN_SUCCESSFULLY');
						$success = true;
						break;
					case 4:
						$message = Text::_('EB_CHECKED_IN_REGISTRATION_PENDING');
						$success = true;
						break;
					case 5:
						$message = Text::_('EB_CHECKED_IN_PAST_EVENT');
						break;
					case 6:
						$message = Text::_('EB_CHECKED_IN_FUTURE_EVENT');
				}

				$registrantId = $rowRegistrant->id;

				$rowRegistrant = new EventbookingTableRegistrant($db);
				$rowRegistrant->load($registrantId);

				$replaces = EventbookingHelperRegistration::getRegistrationReplaces($rowRegistrant, null, Factory::getUser()->id);

				$message = EventbookingHelper::replaceCaseInsensitiveTags($message, $replaces);

				PluginHelper::importPlugin('eventbooking');
				$this->app->triggerEvent('onAfterCheckinRegistrant', [$rowRegistrant, $result, $success]);
			}
			else
			{
				$message = Text::_('EB_INVALID_TICKET_CODE');
			}
		}
		else
		{
			$message = Text::_('EB_TICKET_CODE_IS_EMPTY');
		}

		return [$success, $message];
	}

	/**
	 * Validate and make sure the provided checkin API Key is valid
	 *
	 * @return bool
	 */
	protected function validateCheckinApiKey()
	{
		$config = EventbookingHelper::getConfig();

		if ($config->get('checkin_api_key') && $config->get('checkin_api_key') != $this->input->getString('api_key'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Send json response
	 *
	 * @param   array  $response
	 */
	protected function sendJsonResponse($response)
	{
		echo json_encode($response);

		$this->app->close();
	}
}
