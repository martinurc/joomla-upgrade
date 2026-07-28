<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Form;

class RsformModelRichtext extends BaseDatabaseModel
{
	public function getLang()
	{
		return RSFormProHelper::getCurrentLanguage($this->getFormId());
	}

	public function getEditor()
	{
		return RSFormProHelper::getEditor();
	}

	public function getTextarea()
	{
		$xml = new SimpleXMLElement('<field type="textarea" />');

		$xml->addAttribute('name', $this->getEditorName());
		$xml->addAttribute('rows', 20);
		$xml->addAttribute('cols', 75);
		$xml->addAttribute('class', 'rs_90');

		$field = FormHelper::loadFieldType('textarea');
		$form = new Form('rsformrichtextform');
		$field->setForm($form);
		$field->setup($xml, $this->getEditorText());

		return $field;
	}

	public function getNoEditor()
	{
		return Factory::getApplication()->input->getInt('noEditor');
	}

	public function getEditorName()
	{
		return Factory::getApplication()->input->getCmd('opener');
	}

	public function getFormId()
	{
		return Factory::getApplication()->input->getInt('formId');
	}

	public function getEditorText()
	{
		$db 	= $this->getDbo();
		$formId = $this->getFormId();
		$opener = $this->getEditorName();

		$query = $db->getQuery(true)
			->select($db->qn($opener))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));

		$value = $db->setQuery($query)->loadResult();

		$translations = RSFormProHelper::getTranslations('forms', $formId, $this->getLang());
		if ($translations && isset($translations[$opener]))
		{
			$value = $translations[$opener];
		}

		return $value;
	}

	public function saveTranslation($value)
	{
		$formId 		= $this->getFormId();
		$opener 		= $this->getEditorName();
		$lang 			= $this->getLang();
		$translations 	= RSFormProHelper::getTranslations('forms', $formId, $lang, 'id');

		$translation = (object) array(
			'form_id'       => $formId,
			'lang_code'     => $lang,
			'reference'     => 'forms',
			'reference_id'  => $opener,
			'value'         => $value
		);

		if (!isset($translations[$opener]))
		{
			$this->getDbo()->insertObject('#__rsform_translations', $translation);
		}
		else
		{
			$translation->id = $translations[$opener];
			$this->getDbo()->updateObject('#__rsform_translations', $translation, array('id'));
		}
	}
}