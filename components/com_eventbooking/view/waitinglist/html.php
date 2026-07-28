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
use Joomla\CMS\Uri\Uri;

class EventbookingViewWaitinglistHtml extends RADViewHtml
{
	/**
	 * The message
	 *
	 * @var string
	 */
	protected $message;

	/**
	 * The waiting list registration record
	 *
	 * @var stdClass
	 */
	protected $rowRegistrant;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$app    = Factory::getApplication();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$config = EventbookingHelper::getConfig();

		$registrationCode = $app->getSession()->get('eb_registration_code', '');
		$query->select('*')
			->from('#__eb_registrants')
			->where('registration_code = ' . $db->quote($registrationCode));
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (!$rowRegistrant)
		{
			$app->enqueueMessage(Text::_('EB_INVALID_REGISTRATION_CODE'), 'error');
			$app->redirect(Uri::root(), 404);
		}

		if (!$rowRegistrant->event_id)
		{
			$app->enqueueMessage(Text::_('Invalid Event ID'), 'error');
			$app->redirect(Uri::root(), 404);
		}
		
		$rowEvent    = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if (strlen(strip_tags($message->{'waitinglist_complete_message' . $fieldSuffix})))
		{
			$msg = $message->{'waitinglist_complete_message' . $fieldSuffix};
		}
		else
		{
			$msg = $message->waitinglist_complete_message;
		}

		if ($rowRegistrant->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($rowEvent->id, 1);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($rowEvent->id, 0);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($rowRegistrant, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod(
			'Registration',
			'buildTags',
			[$rowRegistrant, $form, $rowEvent, $config],
			'Helper'
		);

		$msg = EventbookingHelper::replaceUpperCaseTags($msg, $replaces);

		$this->setLayout('default');

		$this->message       = $msg;
		$this->rowRegistrant = $rowRegistrant;
	}
}
