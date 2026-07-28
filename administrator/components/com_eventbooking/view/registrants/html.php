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
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class EventbookingViewRegistrantsHtml extends RADViewList
{
	use EventbookingViewRegistrants;

	/**
	 * Total number of published payment plugins
	 *
	 * @var int
	 */
	protected $totalPlugins;

	/**
	 * The date picket format
	 *
	 * @var string
	 */
	protected $datePickerFormat;

	/**
	 * The date format
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * The component's messages
	 *
	 * @var RADConfig
	 */
	protected $message;

	/**
	 * Flag to allow trigger event before view is being displayed
	 *
	 * @var bool
	 */
	protected $triggerEvent = true;

	/**
	 * Flag to mark if there is filter field on the view
	 *
	 * @var bool
	 */
	protected $hasFilterFields = false;

	/**
	 * Prepare the view before it is displayed
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$this->prepareViewData();

		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_payment_plugins')
			->where('published = 1');
		$db->setQuery($query);

		$this->totalPlugins     = $db->loadResult();
		$this->datePickerFormat = $config->get('date_field_format', '%Y-%m-%d');
		$this->dateFormat       = str_replace('%', '', $this->datePickerFormat);
		$this->message          = EventbookingHelper::getMessages();

		EventbookingHelper::displayPHPVersionWarning();
	}

	/**
	 * Add custom toolbar buttons needed for registrants management
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function addCustomToolbarButtons()
	{
		$config = EventbookingHelper::getConfig();

		// Instantiate a new JLayoutFile instance and render the batch button
		$bar       = Toolbar::getInstance('toolbar');
		$isJoomla4 = EventbookingHelper::isJoomla4();

		if ($isJoomla4)
		{
			/* @var DropdownButton $dropdown */
			$dropdown = $bar->dropdownButton('status-group')
				->text('EB_ACTION_EXPORT')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action');

			$childBar = $dropdown->getChildToolbar();

			if (count($this->model->getExportTemplates()))
			{
				$childBar->popupButton('batch')
					->text('EB_EXPORT_XLSX')
					->selector('collapseModal_Export_Template');
			}
			else
			{
				$childBar->standardButton('export')
					->text('EB_EXPORT_XLSX')
					->icon('icon-download')
					->task('export');
			}

			$childBar->standardButton('export_pdf')
				->text('EB_EXPORT_PDF')
				->icon('icon-download')
				->task('export_pdf');

			if ($config->activate_invoice_feature)
			{
				$childBar->standardButton('export_invoices')
					->text('EB_EXPORT_INVOICES')
					->icon('icon-download')
					->task('export_invoices');
			}

			if ($config->activate_tickets_pdf)
			{
				$childBar->standardButton('export_tickets')
					->text('EB_EXPORT_TICKETS')
					->icon('icon-download')
					->task('export_tickets');
			}

			/* @var DropdownButton $dropdown */
			$dropdown = $bar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action');

			$childBar = $dropdown->getChildToolbar();

			$childBar->standardButton('cancel_registrations', 'EB_CANCEL_REGISTRATIONS', 'cancel_registrations')
				->icon('icon-cancel')
				->listCheck(true);

			if ($config->activate_checkin_registrants)
			{
				$childBar->checkin('checkin_multiple_registrants')
					->listCheck(true);
				$childBar->unpublish('reset_check_in', 'EB_CHECKOUT')
					->listCheck(true);
			}

			if ($config->activate_certificate_feature)
			{
				$childBar->standardButton('download_certificates', 'EB_DOWNLOAD_CERTIFICATES', 'download_certificates')
					->icon('icon-download')
					->listCheck(true);

				$childBar->standardButton('send_certificates', 'EB_SEND_CERTIFICATES', 'send_certificates')
					->icon('icon-envelope')
					->listCheck(true);
			}

			$childBar->popupButton('batch')
				->text('EB_MASS_MAIL')
				->selector('collapseModal')
				->listCheck(true);

			// Show batch SMS button
			if (PluginHelper::isEnabled('system', 'eventbookingsms'))
			{
				$childBar->popupButton('batch')
					->text('EB_BATCH_SMS')
					->selector('collapseModal_Sms')
					->listCheck(true);
			}

			$childBar->standardButton('resend_email', 'EB_RESEND_EMAIL', 'resend_email')
				->icon('icon-envelope')
				->listCheck(true);
		}
		else
		{
			if (count($this->model->getExportTemplates()))
			{
				$dhtml = EventbookingHelperHtml::loadCommonLayout(
					'common/batch_nocheck.php',
					['title' => Text::_('EB_EXPORT_XLSX'), 'selector' => 'collapseModal_Export_Template']
				);
				$bar->appendButton('Custom', $dhtml, 'batch');
			}
			else
			{
				ToolbarHelper::custom('export', 'download', 'download', 'EB_EXPORT_XLSX', false);
			}

			ToolbarHelper::custom('export_pdf', 'download', 'download', 'EB_EXPORT_PDF', false);

			if ($config->activate_invoice_feature)
			{
				ToolbarHelper::custom('export_invoices', 'download', 'download', 'EB_EXPORT_INVOICES', false);
			}

			if ($config->activate_tickets_pdf)
			{
				ToolbarHelper::custom('export_tickets', 'download', 'download', 'EB_EXPORT_TICKETS', false);
			}

			ToolbarHelper::custom('cancel_registrations', 'cancel', 'cancel', 'EB_CANCEL_REGISTRATIONS', true);

			if ($config->activate_checkin_registrants)
			{
				ToolbarHelper::checkin('checkin_multiple_registrants');
				ToolbarHelper::unpublish('reset_check_in', Text::_('EB_CHECKOUT'), true);
			}

			if ($config->activate_certificate_feature)
			{
				ToolbarHelper::custom('download_certificates', 'download', 'download', 'EB_DOWNLOAD_CERTIFICATES', true);
				ToolbarHelper::custom('send_certificates', 'envelope', 'envelope', 'EB_SEND_CERTIFICATES', true);
			}

			$layout = new FileLayout('joomla.toolbar.batch');
			$dhtml  = $layout->render(['title' => Text::_('EB_MASS_MAIL')]);
			$bar->appendButton('Custom', $dhtml, 'batch');

			if (PluginHelper::isEnabled('system', 'eventbookingsms'))
			{
				$dhtml = EventbookingHelperHtml::loadCommonLayout(
					'common/batch.php',
					['title' => Text::_('EB_BATCH_SMS'), 'selector' => 'collapseModal_Sms']
				);
				$bar->appendButton('Custom', $dhtml, 'batch');
			}

			ToolbarHelper::custom('resend_email', 'envelope', 'envelope', 'EB_RESEND_EMAIL', true);
		}

		$hasPendingPayment = false;

		foreach ($this->items as $item)
		{
			if ($item->published == 0 && $item->amount > 0)
			{
				$hasPendingPayment = true;
				break;
			}
		}

		if ($config->activate_waitinglist_feature || $hasPendingPayment)
		{
			if ($isJoomla4)
			{
				$childBar->standardButton('request_payment', 'EB_REQUEST_PAYMENT', 'request_payment')
					->icon('icon-envelope')
					->listCheck(true);
			}
			else
			{
				ToolbarHelper::custom('request_payment', 'envelope', 'envelope', 'EB_REQUEST_PAYMENT', true);
			}
		}
	}
}
