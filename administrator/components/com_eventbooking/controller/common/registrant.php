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
use Joomla\Utilities\ArrayHelper;

trait EventbookingControllerCommonRegistrant
{
	/**
	 * Resend confirmation email to registrants in case they didn't receive it
	 */
	public function resend_email()
	{
		$this->csrfProtection();

		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You need to have registrants management permission to resend email', 403);
		}

		$cid = $this->input->get('cid', [], 'array');
		$cid = ArrayHelper::toInteger($cid);

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();
		$ret   = true;

		foreach ($cid as $id)
		{
			$ret = $model->resendEmail($id);
		}

		if ($ret)
		{
			$this->setMessage(Text::_('EB_EMAIL_SUCCESSFULLY_RESENT'));
		}
		else
		{
			$this->setMessage(Text::_('EB_COULD_NOT_RESEND_EMAIL_TO_GROUP_MEMBER'), 'notice');
		}

		$this->setRedirect($this->getViewListUrl());
	}

	/**
	 * Send payment request to selected registrant
	 *
	 * @return void
	 */
	public function request_payment()
	{
		$this->csrfProtection();

		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You need to have registrants management permission to request payment', 403);
		}

		$cid = $this->input->get('cid', [], 'array');
		$cid = ArrayHelper::toInteger($cid);
		$cid = $this->getManagableIds($cid);

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();

		try
		{
			foreach ($cid as $id)
			{
				$model->sendPaymentRequestEmail($id);
			}

			$this->setMessage(Text::_('EB_REQUEST_PAYMENT_EMAIL_SENT_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect($this->getViewListUrl());
	}

	/**
	 * Resend confirmation email to registrants in case they didn't receive it
	 */
	public function send_certificates()
	{
		$this->csrfProtection();

		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You need to have registrants management permission to send certificates', 403);
		}

		$cid = $this->input->get('cid', [], 'array');
		$cid = ArrayHelper::toInteger($cid);
		$cid = $this->getManagableIds($cid);

		// Update certificate_sent status
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->update('#__eb_registrants')
			->set('certificate_sent = 1')
			->where('id IN (' . implode(',', $cid) . ')');
		$db->setQuery($query)
			->execute();

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();

		try
		{
			foreach ($cid as $id)
			{
				$model->sendCertificates($id);
			}

			$this->setMessage(Text::_('EB_CERTIFICATES_SUCCESSFULLY_SENT'));
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'error');
		}

		$this->setRedirect($this->getViewListUrl());
	}

	/**
	 * Download Certificates for selected registrants
	 */
	public function download_certificates()
	{
		$this->csrfProtection();

		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You do not have permission to download certificates', 403);
		}

		$cid = $this->input->get('cid', [], 'array');
		$cid = ArrayHelper::toInteger($cid);
		$cid = $this->getManagableIds($cid);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('id IN (' . implode(',', $cid) . ')')
			->order('id');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$config = EventbookingHelper::getConfig();

		[$fileName, $filePath] = EventbookingHelper::callOverridableHelperMethod(
			'Certificate',
			'generateCertificates',
			[$rows, $config]
		);

		$this->processDownloadFile($filePath, $fileName);
	}

	/**
	 * Method to checkout selected registrants
	 *
	 * @throws Exception
	 */
	public function check_out()
	{
		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You do not have permission to checkout registrant', 403);
		}

		$cid = $this->input->get('cid', [], 'array');
		$cid = ArrayHelper::toInteger($cid);

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();

		try
		{
			foreach ($cid as $id)
			{
				$model->resetCheckin($id);
			}

			$this->setMessage(Text::_('EB_CHECKOUT_REGISTRANTS_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'error');
		}

		$this->setRedirect($this->getViewListUrl());
	}

	/**
	 * Send batch mail to registrants
	 */
	public function batch_mail()
	{
		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You do not have permission to cancel registrations', 403);
		}

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();

		try
		{
			$model->batchMail($this->input);
			$this->setMessage(Text::_('EB_BATCH_MAIL_SUCCESS'));
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'error');
		}

		$this->setRedirect($this->getViewListUrl());
	}

	/**
	 * Cancel the selected registrationr records
	 *
	 * @return void
	 */
	public function cancel_registrations()
	{
		if (Factory::getApplication()->isClient('site')
			&& !Factory::getUser()->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			throw new Exception('You do not have permission to cancel registrations', 403);
		}

		$cid = $this->input->get('cid', [], 'array');
		$cid = ArrayHelper::toInteger($cid);
		$cid = $this->getManagableIds($cid);

		// For some reasons, no records was selected, don't process further
		if (!$cid)
		{
			echo 'No registration records selected';

			return;
		}

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();
		$model->cancelRegistrations($cid);
		$this->setRedirect($this->getViewListUrl(), Text::_('EB_SUCCESSFULLY_CANCELLED_REGISTRATIONS'));
	}

	/**
	 * Get fields and headers base on export template setup
	 *
	 * @param   array  $templateFields
	 * @param   array  $fields
	 * @param   array  $headers
	 *
	 * @return array
	 */
	protected function getFieldsAndHeadersFromExportTemplates(array $templateFields, array $fields, array $headers): array
	{
		$newFields = $newHeaders = [];

		foreach ($templateFields as $field)
		{
			if (in_array($field, $fields))
			{
				$fieldIndex   = array_search($field, $fields);
				$newFields[]  = $field;
				$newHeaders[] = $headers[$fieldIndex];
			}
			elseif ($field == 'eb_ticket_types_plugin')
			{
				// Special handle for ticket types
				foreach ($fields as $outputField)
				{
					if (strpos($outputField, 'event_ticket_type_') !== false)
					{
						$fieldIndex   = array_search($outputField, $fields);
						$newFields[]  = $outputField;
						$newHeaders[] = $headers[$fieldIndex];
					}
				}
			}
		}

		return [$newFields, $newHeaders];
	}
}