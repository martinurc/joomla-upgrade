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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

class EventbookingViewRegistrantHtml extends RADViewHtml
{
	use EventbookingViewRegistrant;

	/**
	 * The registration record being added/edited
	 *
	 * @var stdClass
	 */
	protected $item;

	/**
	 * The event data
	 *
	 * @var stdClass
	 */
	protected $event;

	/**
	 * The array which keeps list of "list" options which will be displayed on the form
	 *
	 * @var array
	 */
	protected $lists = [];

	/**
	 * The flag to mark if change ticket's quantity is allowed
	 *
	 * @var bool
	 */
	protected $canChangeTicketsQuantity;

	/**
	 * The user type is editing the record
	 *
	 * @var string
	 */
	protected $userType;

	/**
	 * Flag to mark if changing registration status is allowed
	 *
	 * @var bool
	 */
	protected $canChangeStatus;

	/**
	 * Flag to mark if changing fee fields is allowed
	 *
	 * @var bool
	 */
	protected $canChangeFeeFields;

	/**
	 * The return url
	 *
	 * @var string
	 */
	protected $return;

	/**
	 * Flag to mark if saving registration is allowed
	 *
	 * @var bool
	 */
	protected $disableEdit;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$rootUri  = Uri::root(true);
		$document = Factory::getApplication()->getDocument();
		$user     = Factory::getUser();

		$item       = $this->model->getData();
		$this->item = $item;

		// Add scripts
		EventbookingHelper::addLangLinkForAjax();
		$document->addScriptDeclaration('var siteUrl="' . EventbookingHelper::getSiteUrl() . '";');

		EventbookingHelperJquery::loadjQuery();
		EventbookingHelperHtml::addOverridableScript(
			'media/com_eventbooking/assets/js/paymentmethods.min.js',
			['version' => EventbookingHelper::getInstalledVersion()]
		);

		$customJSFile = JPATH_ROOT . '/media/com_eventbooking/assets/js/custom.js';

		if (file_exists($customJSFile) && filesize($customJSFile) > 0)
		{
			$document->addScript($rootUri . '/media/com_eventbooking/assets/js/custom.js');
		}

		$disableEdit = false;

		if ($item->id
			&& (int) $item->registrant_edit_close_date
			&& $item->edit_close_minutes > 0
			&& !$user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			// Not allowed to edit the record after edit closing data reached
			$disableEdit = true;

			$this->item->disableEdit = true;

			// Hide the save button
			$this->hideButtons[] = 'registrant.save';
			$this->hideButtons[] = 'registrant.apply';
		}

		$this->prepareViewData();

		if ($this->event->has_multiple_ticket_types)
		{
			$this->canChangeTicketsQuantity = $this->userType == 'registrants_manager';
		}

		$this->canChangeStatus = $this->userType == 'registrants_manager';

		if ($disableEdit)
		{
			$this->canChangeStatus          = false;
			$this->canChangeFeeFields       = false;
			$this->canChangeTicketsQuantity = false;
		}

		$this->return      = $this->input->get->getBase64('return');
		$this->disableEdit = $disableEdit;

		$this->addToolbar();

		$this->setLayout('default');
	}

	/**
	 * Build Form Toolbar
	 */
	protected function addToolbar()
	{
		if (!in_array('registrant.apply', $this->hideButtons))
		{
			ToolbarHelper::apply('apply', 'JTOOLBAR_APPLY');
		}

		if (!in_array('registrant.save', $this->hideButtons))
		{
			ToolbarHelper::save('registrant.save', 'JTOOLBAR_SAVE');
		}

		if ($this->item->id
			&& $this->item->published != 2
			&& !in_array('registrant.cancel', $this->hideButtons)
			&& EventbookingHelperAcl::canCancelRegistration($this->item->event_id)
		)
		{
			ToolbarHelper::custom('registrant.cancel', 'delete', 'delete', Text::_('EB_CANCEL_REGISTRATION'), false);
		}

		if (!in_array('registrant.cancel_edit', $this->hideButtons))
		{
			ToolbarHelper::cancel('registrant.cancel_edit', 'JTOOLBAR_CLOSE');
		}

		if (EventbookingHelperRegistration::canRefundRegistrant($this->item)
			&& !in_array('registrant.refund', $this->hideButtons))
		{
			ToolbarHelper::custom('registrant.refund', 'delete', 'delete', Text::_('EB_REFUND'), false);
		}
	}
}
