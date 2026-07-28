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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class EventbookingViewCancelHtml extends RADViewHtml
{
	/**
	 * The component's messages
	 *
	 * @var RADConfig
	 */
	protected $message;

	/**
	 * Flag to mark that this view does not have associated model
	 *
	 * @var bool
	 */
	public $hasModel = false;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$this->setLayout('default');

		$app         = Factory::getApplication();
		$config      = EventbookingHelper::getConfig();
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();
		$id          = $this->input->getInt('id');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('id = ' . (int) $id);
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (!$rowRegistrant)
		{
			$app->enqueueMessage(Text::_('EB_INVALID_REGISTRATION_CODE'), 'warning');
			$app->redirect(Uri::root(), 404);
		}

		if ($rowRegistrant->published == 1)
		{
			// Redirect to registration complete page, workaround for PayPal bug
			$app->redirect(Route::_('index.php?option=com_eventbooking&view=complete&Itemid=' . $this->Itemid, false));
		}

		$rowEvent = EventbookingHelperDatabase::getEvent($rowRegistrant->event_id);

		if (strlen(trim(strip_tags($message->{'cancel_message' . $fieldSuffix}))))
		{
			$cancelMessage = $message->{'cancel_message' . $fieldSuffix};
		}
		else
		{
			$cancelMessage = $message->cancel_message;
		}

		$replaces = EventbookingHelperRegistration::getRegistrationReplaces($rowRegistrant, $rowEvent, 0, $config->multiple_booking, false);

		$cancelMessage = EventbookingHelper::replaceCaseInsensitiveTags($cancelMessage, $replaces);

		$this->message = $cancelMessage;
	}
}
