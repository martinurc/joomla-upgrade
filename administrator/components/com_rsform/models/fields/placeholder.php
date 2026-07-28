<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('text');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\TextField', 'JFormFieldText');
}

class JFormFieldPlaceholder extends TextField
{
	protected $dataAttributes = array();

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$result = parent::setup($element, $value, $group);

		if ($result && empty($this->dataAttributes))
		{
			// Lets detect miscellaneous data attribute. For eg, data-*
			foreach ($this->element->attributes() as $key => $value)
			{
				if (strpos($key, 'data-') === 0)
				{
					// Data attribute key value pair
					$this->dataAttributes[$key] = $value;
				}
			}
		}

		return $result;
	}

	protected function getInput()
	{
		$html = parent::getInput();

		if (version_compare(JVERSION, '4.0', '<') && !empty($this->dataAttributes))
		{
			$dataAttribute  = '';

			if (!empty($this->dataAttributes))
			{
				foreach ($this->dataAttributes as $key => $attrValue)
				{
					$dataAttribute .= ' ' . $key . '="' . htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8') . '"';
				}

				$dataAttribute .= ' ';
			}

			if ($dataAttribute)
			{
				$html = str_replace('<input ', '<input ' . $dataAttribute, $html);
			}
		}

		return $html;
	}
}
