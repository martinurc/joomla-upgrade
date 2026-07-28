<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Multilanguage;

class EventbookingModelMitem extends RADModelAdmin
{
	/**
	 * Override store method to store data into #__eb_messages table
	 *
	 * @param   RADInput  $input
	 * @param   array     $ignore
	 *
	 * @return bool|void
	 * @throws Exception
	 */
	public function store($input, $ignore = [])
	{
		$id    = $input->getInt('id', 0);
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_mitems')
			->where('id = ' . $id);
		$db->setQuery($query);
		$row = $db->loadObject();

		if (!$row)
		{
			throw new Exception('Invalid Message Item', 404);
		}

		$data = [];

		$data[$row->name] = $input->get($row->name, '', 'raw');

		if ($row->translatable && Multilanguage::isEnabled())
		{
			foreach (EventbookingHelper::getLanguages() as $language)
			{
				$inputName        = $row->name . '_' . $language->sef;
				$data[$inputName] = $input->get($inputName, '', 'raw');
			}
		}

		$this->insertOrUpdateMessages($data);
	}

	/**
	 * Method to insert or update messages
	 *
	 * @param   array  $data
	 *
	 * @return void
	 */
	private function insertOrUpdateMessages($data)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('message_key')
			->from('#__eb_messages');
		$db->setQuery($query);
		$existingMessages = $db->loadColumn();

		foreach ($data as $messageKey => $message)
		{
			if (in_array($messageKey, $existingMessages))
			{
				// Update
				$query->clear()
					->update('#__eb_messages')
					->set('message = ' . $db->quote($message))
					->where('message_key = ' . $db->quote($messageKey));
			}
			else
			{
				// Insert
				$query->clear()
					->insert('#__eb_messages')
					->columns($db->quoteName(['message_key', 'message']))
					->values(implode(',', $db->quote([$messageKey, $message])));
			}

			$db->setQuery($query)
				->execute();
		}
	}
}
