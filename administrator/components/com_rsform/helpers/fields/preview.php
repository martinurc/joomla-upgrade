<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/field.php';

class RSFormProFieldPreview extends RSFormProField
{
	// backend preview
	public function getPreviewInput()
	{
		$attachedField = '{value}';
		if ($data = $this->getAttachedFieldData())
		{
			$attachedField = $data['NAME'];
		}

		$value = $this->getProperty('TEXT', '');

		return '<pre class="rsfp-preview-freetext">'.str_replace('{value}', '<em>' . $attachedField . '</em>', $this->escape($value)).'</pre>';
	}
	
	// functions used for rendering in front view
	public function getFormInput()
	{
		if ($data = $this->getAttachedFieldData())
		{
			$options = array(
				'previewId' => $this->componentId,
				'fieldId' 	=> $data['componentId'],
				'fieldName' => $data['NAME'],
				'formId' 	=> $this->formId,
				'separator' => static::getFormSeparator($this->formId)
			);
			if (isset($data['DATESEPARATOR']))
			{
				$options['separator'] = $data['DATESEPARATOR'];
			}
			if (isset($data['FILESSEPARATOR']))
			{
				$options['separator'] = $data['FILESSEPARATOR'];
			}
			if (isset($data['SURVEYTEMPLATE']))
			{
				$options['template'] = $data['SURVEYTEMPLATE'];
			}

			$this->addScriptDeclaration('RSFormPro.previewFields.elements.push(' . json_encode($options) . ');');
			$this->addScriptDeclaration('RSFormProUtils.addEvent(window, \'load\', function() { window.setTimeout(RSFormPro.previewFields.attachEvents, 1); });');
		}

		$html = $this->getProperty('TEXT', '');
		$html = str_replace('{value}', '<span id="preview-' . $this->componentId . '"></span>', $html);
		
		return $html;
	}

	protected function getAttachedFieldData()
	{
		if ($field = $this->getProperty('SELECTFIELD'))
		{
			if ($data = RSFormProHelper::getComponentProperties($field))
			{
				return $data;
			}
		}

		return false;
	}

	protected static function getFormSeparator($formId)
	{
		static $cache = array();

		if (!isset($cache[$formId]))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('MultipleSeparator'))
				->from($db->qn('#__rsform_forms'))
				->where($db->qn('FormId') . ' = ' . $db->q($formId));
			$cache[$formId] = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $db->setQuery($query)->loadResult());
		}

		return $cache[$formId];
	}
}