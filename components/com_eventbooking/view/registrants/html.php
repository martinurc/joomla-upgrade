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
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

class EventbookingViewRegistrantsHtml extends RADViewList
{
	use EventbookingViewRegistrants;

	/**
	 * Flag to determine that this view support display event trigger
	 *
	 * @var bool
	 */
	protected $triggerEvent = false;

	/**
	 * Prepare view data for displaying
	 *
	 * @throws Exception
	 */
	protected function prepareView()
	{
		$config = EventbookingHelper::getConfig();

		if ($this->params->get('show_registrants_of_past_events', 1) === '0')
		{
			// In case users do not want to show registrants of past events, the past events should not be displayed in events filter dropdown, too
			$config->hide_past_events_from_events_dropdown = 1;
		}
		else
		{
			$config->hide_past_events_from_events_dropdown = 0;
		}

		if ($config->allow_filter_registrants_by_category && $this->params->get('default_category_id'))
		{
			$this->model->setState('filter_category_id', (int) $this->params->get('default_category_id'));
		}

		parent::prepareView();

		$user = Factory::getUser();

		if (!$user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			if ($user->guest)
			{
				$this->requestLogin();
			}
			else
			{
				$app = Factory::getApplication();
				$app->enqueueMessage(Text::_('NOT_AUTHORIZED'), 'error');
				$app->redirect(Uri::root(), 403);
			}
		}


		$this->prepareViewData();
		$this->coreFields = EventbookingHelperRegistration::getPublishedCoreFields();

		$this->findAndSetActiveMenuItem();

		$this->addToolbar();

		$this->setLayout('default');
	}

	/**
	 * Override addToolbar method to add custom csv export function
	 * @see RADViewList::addToolbar()
	 */
	protected function addToolbar()
	{
		$this->hideButtons = $this->params->get('hide_buttons', []);

		if (!EventbookingHelperAcl::canDeleteRegistrant())
		{
			$this->hideButtons[] = 'delete';
		}

		parent::addToolbar();

		$isJoomla4 = EventbookingHelper::isJoomla4();

		if (!in_array('cancel_registrations', $this->hideButtons))
		{
			ToolbarHelper::custom('cancel_registrations', 'cancel', 'cancel', 'EB_CANCEL_REGISTRATIONS', true);
		}

		if ($this->config->activate_checkin_registrants)
		{
			if (!in_array('checkin_multiple_registrants', $this->hideButtons))
			{
				ToolbarHelper::checkin('checkin_multiple_registrants');
			}

			if (!in_array('check_out', $this->hideButtons))
			{
				ToolbarHelper::unpublish('check_out', Text::_('EB_CHECKOUT'), true);
			}
		}

		$bar = Toolbar::getInstance('toolbar');

		if (!in_array('batch_mail', $this->hideButtons))
		{
			if ($isJoomla4)
			{
				$bar->popupButton('batch-sms')
					->text('EB_MASS_MAIL')
					->selector('collapseModal')
					->listCheck(true);
			}
			else
			{
				$layout = new FileLayout('joomla.toolbar.batch');
				$dhtml  = $layout->render(['title' => Text::_('EB_MASS_MAIL')]);
				$bar->appendButton('Custom', $dhtml, 'batch-sms');
			}
		}

		if (!in_array('resend_email', $this->hideButtons))
		{
			ToolbarHelper::custom('resend_email', 'envelope', 'envelope', 'EB_RESEND_EMAIL', true);
		}

		if (!in_array('export', $this->hideButtons))
		{
			if (count($this->exportTemplates))
			{
				if ($isJoomla4)
				{
					$bar->popupButton('batch-export-registrants')
						->text('EB_EXPORT_XLSX')
						->selector('collapseModal_Export_Template')
						->icon('icon-download')
						->listCheck(false);
				}
				else
				{
					$dhtml = EventbookingHelperHtml::loadCommonLayout(
						'common/batch_nocheck.php',
						['title' => Text::_('EB_EXPORT_XLSX'), 'selector' => 'collapseModal_Export_Template', 'class' => 'icon-download']
					);

					$bar->appendButton('Custom', $dhtml, 'batch');
				}
			}
			else
			{
				ToolbarHelper::custom('export', 'download', 'download', 'EB_EXPORT_XLSX', false);
			}
		}

		if (!in_array('export_pdf', $this->hideButtons))
		{
			ToolbarHelper::custom('export_pdf', 'download', 'download', 'EB_EXPORT_PDF', false);
		}

		if ($this->config->activate_certificate_feature)
		{
			if (!in_array('download_certificates', $this->hideButtons))
			{
				ToolbarHelper::custom('download_certificates', 'download', 'download', 'EB_DOWNLOAD_CERTIFICATES', true);
			}

			if (!in_array('send_certificates', $this->hideButtons))
			{
				ToolbarHelper::custom('send_certificates', 'envelope', 'envelope', 'EB_SEND_CERTIFICATES', true);
			}
		}

		$hasPendingPayment = false;

		foreach ($this->items as $item)
		{
			if ($item->published == 0 && $item->amount > 0)
			{
				$hasPendingPayment = true;
			}
		}

		if (($this->config->activate_waitinglist_feature || $hasPendingPayment)
			&& !in_array('request_payment', $this->hideButtons))
		{
			ToolbarHelper::custom('request_payment', 'envelope', 'envelope', 'EB_REQUEST_PAYMENT', true);
		}
	}
}
