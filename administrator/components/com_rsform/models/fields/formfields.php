<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\ListField', 'JFormFieldList');
}

class JFormFieldFormfields extends ListField
{
	protected function getOptions()
    {
		$formId = Factory::getApplication()->input->getInt('formId');

		if (!$formId)
		{
			$formId = $this->form->getValue('formId', 'params');
		}

		$options = array();

		if ($formId)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('p.PropertyValue'))
				->select($db->qn('c.ComponentId'))
				->from($db->qn('#__rsform_components', 'c'))
				->join('LEFT', $db->qn('#__rsform_properties', 'p') . ' ON (' . $db->qn('c.ComponentId') . '=' . $db->qn('p.ComponentId') . ')')
				->where($db->qn('c.FormId') . '=' . $db->q($formId))
				->where($db->qn('p.PropertyName') . '=' . $db->q('NAME'))
				->order($db->qn('c.Order') . ' ' . $db->escape('ASC'));

			if ($fields = $db->setQuery($query)->loadColumn())
			{
				foreach ($fields as $field)
				{
					$options[] = HTMLHelper::_('select.option', $field, $field);
				}
			}

			reset($options);
		}

		return array_merge(parent::getOptions(), $options);
	}
}
