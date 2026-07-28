<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsformModelEmail extends AdminModel
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsform.email', 'email', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function getItem($pk = null)
	{
		if ($pk === null)
		{
			$pk = Factory::getApplication()->input->getInt('cid');
		}

		return parent::getItem($pk);
	}

	protected function loadFormData()
	{
		$app = Factory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_rsform.edit.email.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		if (empty($data->formId))
		{
			$data->formId = $this->getFormId();
		}

		if (empty($data->type))
		{
			$data->type = $this->getType();
		}

		$data->language = $this->getCurrentLanguage();

		if (!empty($data->id))
		{
			if ($translations = RSFormProHelper::getTranslations('emails', $data->formId, $data->language))
			{
				foreach (array('fromname', 'subject', 'message', 'replytoname') as $property)
				{
					$reference = $data->id . '.' . $property;

					if (isset($translations[$reference]))
					{
						$data->{$property} = $translations[$reference];
					}
				}
			}

			$data->message_0 = $data->message_1 = $data->message;
		}

		return $data;
	}

	public function getFormId()
	{
		return Factory::getApplication()->input->getInt('formId');
	}

	public function getType()
	{
		return Factory::getApplication()->input->getCmd('type','additional');
	}

	public function getCurrentLanguage()
	{
		if ($language = Factory::getApplication()->input->getString('language'))
		{
			return $language;
		}

		$data       = $this->getItem();
		$default    = RSFormProHelper::getCurrentLanguage($this->getFormId());

		if (!empty($data->id))
		{
			return Factory::getSession()->get('com_rsform.emails.emailId' . $data->id . '.lang', $default);
		}

		return $default;
	}

	public function getTable($type = 'Rsform_Emails', $prefix = 'Table', $options = array())
	{
		return parent::getTable($type, $prefix, $options);
	}

	public function getQuickfields()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/quickfields.php';

		return RSFormProQuickFields::getFieldNames('all');
	}

	public function save($data)
	{
		$row	= $this->getTable();
		$app    = Factory::getApplication();

		$row->bind($data);

		// Saving new row twice so we can save translations
		if (!$row->id)
		{
			if (!$row->store())
			{
				$app->enqueueMessage($row->getError(), 'error');
				return false;
			}
		}

		if (empty($data['language']))
		{
			$data['language'] = $this->getCurrentLanguage();
		}

		if ($this->saveTranslation($row, $data['language']))
		{
			$row->fromname = null;
			$row->subject = null;
			$row->message = null;
		}

		if (!$row->store())
		{
			$app->enqueueMessage($row->getError(), 'error');
			return false;
		}

		$app->enqueueMessage(Text::_('RSFP_CHANGES_SAVED'));

		return $row;
	}

	public function saveTranslation(&$email, $lang)
	{
		// We're saving a new email so we need to skip translations for now
		// This email is the base for future translations.
		if (!$email->id) {
			return false;
		}

		$fields 	  = array('fromname', 'subject', 'message', 'replytoname');
		$translations = RSFormProHelper::getTranslations('emails', $email->formId, $lang, 'id');

		// $translations is false when we're trying to get translations (en-GB) for the same language the form is in (en-GB)
		if ($translations === false) {
			return false;
		}

		foreach ($fields as $field)
		{
			$reference_id = $email->id . '.' . $field;

			$translation = (object) array(
				'form_id'       => $email->formId,
				'lang_code'     => $lang,
				'reference'     => 'emails',
				'reference_id'  => $reference_id,
				'value'         => $email->{$field}
			);

			if (!isset($translations[$reference_id]))
			{
				$this->getDbo()->insertObject('#__rsform_translations', $translation);
			}
			else
			{
				$translation->id = $translations[$reference_id];
				$this->getDbo()->updateObject('#__rsform_translations', $translation, array('id'));
			}
			unset($email->{$field});
		}

		return true;
	}
}