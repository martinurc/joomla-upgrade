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

class EventbookingViewInviteHtml extends RADViewHtml
{
	use EventbookingViewCaptcha;

	/**
	 * The event data
	 *
	 * @var stdClass
	 */
	protected $event;

	/**
	 * The invite message
	 *
	 * @var string
	 */
	protected $inviteMessage;

	/**
	 * The friend names , each name in one line
	 *
	 * @var string
	 */
	protected $friendNames;

	/**
	 * The friend emails, each email in one line
	 *
	 * @var string
	 */
	protected $friendEmails;

	/**
	 * The message to send
	 *
	 * @var string
	 */
	protected $mesage;

	/**
	 * The bootstrap helper class
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * The component's messages
	 *
	 * @var RADConfig
	 */
	protected $message;

	/**
	 * Display invitation form for an event
	 *
	 * @throws Exception
	 */
	public function display()
	{
		$config = EventbookingHelper::getConfig();

		if (!$config->show_invite_friend)
		{
			throw new Exception(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		$layout = $this->getLayout();

		if ($layout == 'complete')
		{
			$this->displayInviteComplete();
		}
		else
		{
			$this->setLayout('default');

			$user        = Factory::getUser();
			$message     = EventbookingHelper::getMessages();
			$fieldSuffix = EventbookingHelper::getFieldSuffix();

			if (strlen(trim(strip_tags($message->{'invitation_form_message' . $fieldSuffix}))))
			{
				$inviteMessage = $message->{'invitation_form_message' . $fieldSuffix};
			}
			else
			{
				$inviteMessage = $message->invitation_form_message;
			}

			// Load captcha
			$this->loadCaptcha();

			$eventId = $this->input->getInt('id');
			$name    = $this->input->getString('name');

			if (empty($name))
			{
				$name = $user->name;
			}

			$this->event = EventbookingHelperDatabase::getEvent($eventId);

			if (!$this->event)
			{
				throw new Exception(Text::_('EB_EVENT_NOT_FOUND'), 404);
			}

			$this->name            = $name;
			$this->inviteMessage   = $inviteMessage;
			$this->friendNames     = $this->input->getString('friend_names');
			$this->friendEmails    = $this->input->getString('friend_emails');
			$this->mesage          = $this->input->getString('message');
			$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();

			EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$this->event, ['title']]);

			parent::display();
		}
	}

	/**
	 * Display invitation complete message
	 */
	protected function displayInviteComplete()
	{
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if (strlen(trim(strip_tags($message->{'invitation_complete' . $fieldSuffix}))))
		{
			$this->message = $message->{'invitation_complete' . $fieldSuffix};
		}
		else
		{
			$this->message = $message->invitation_complete;
		}

		parent::display();
	}
}
