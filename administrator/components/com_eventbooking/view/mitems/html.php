<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

class EventbookingViewMitemsHtml extends RADViewList
{
	/**
	 * Prepare data for the view
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_MESSAGE_GROUP'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_REGISTRATION_FORM_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_REGISTRATION_EMAIL_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_REMINDER_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 4, Text::_('EB_REGISTRATION_CANCEL_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 5, Text::_('EB_SUBMIT_EVENT_EMAIL_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 6, Text::_('EB_INVITATION_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 7, Text::_('EB_WAITING_LIST_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 8, Text::_('EB_DEPOSIT_PAYMENT_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 9, Text::_('EB_SMS_MESSAGES'));
		$options[] = HTMLHelper::_('select.option', 10, Text::_('EB_MESSAGE_CUSTOM_SETTINGS'));

		$this->lists['filter_group'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_group',
			' class="form-select" onchange="submit();" ',
			'value',
			'text',
			$this->state->filter_group
		);

		// Insert messages for additional offline payment plugins
		$db    = $this->model->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_payment_plugins')
			->where('name LIKE "os_offline_%"')
			->where('published = 1');
		$db->setQuery($query);

		$extraOfflinePlugins = $db->loadObjectList();

		$query->clear()
			->select('name')
			->from('#__eb_mitems');
		$db->setQuery($query);
		$existingMessages = $db->loadColumn();

		foreach ($extraOfflinePlugins as $offlinePaymentPlugin)
		{
			$name   = $offlinePaymentPlugin->name;
			$title  = $offlinePaymentPlugin->title;
			$prefix = str_replace('os_offline', '', $name);

			$messageKey = 'user_email_body_offline' . $prefix;

			if (!in_array($messageKey, $existingMessages))
			{
				$item               = new stdClass();
				$item->name         = $messageKey;
				$item->title        = Text::_('User email body (' . $title . ')');
				$item->description  = '';
				$item->type         = 'editor';
				$item->group        = '2';
				$item->translatable = 1;
				$item->featured     = 0;

				$db->insertObject('#__eb_mitems', $item);
			}

			$messageKey = 'thanks_message_offline' . $prefix;

			if (!in_array($messageKey, $existingMessages))
			{
				$item               = new stdClass();
				$item->name         = $messageKey;
				$item->title        = Text::_('Thank you message (' . $title . ')');
				$item->description  = 'This message will be displayed on the thank you page after users complete registration using ' . $title . ' payment method';
				$item->type         = 'editor';
				$item->group        = '1';
				$item->translatable = 1;
				$item->featured     = 0;

				$db->insertObject('#__eb_mitems', $item);
			}
		}
	}

	/**
	 * Override addToolbar method, we only need to set title, no other buttons
	 *
	 * @return void
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_(strtoupper('EB_' . RADInflector::singularize($this->name) . '_MANAGEMENT')), 'link ' . $this->name);
	}
}
