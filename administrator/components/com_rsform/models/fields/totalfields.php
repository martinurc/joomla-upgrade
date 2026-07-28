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

class JFormFieldTotalfields extends ListField
{
	protected function getOptions()
    {
		$options = array();

		$types = array(RSFORM_FIELD_TEXTBOX, RSFORM_FIELD_HIDDEN);

		Factory::getApplication()->triggerEvent('onRsformDefineTotalFields', array(&$types));

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$formId = $this->form->getValue('formId');

		// need to get the component type name so that we can load the specific class
		$query->clear()
			->select($db->qn('p.PropertyValue', 'name'))
			->from($db->qn('#__rsform_properties', 'p'))
			->join('LEFT', $db->qn('#__rsform_components', 'c').' ON ('.$db->qn('c.ComponentId').' = '.$db->qn('p.ComponentId').')')
			->join('LEFT', $db->qn('#__rsform_component_types', 'ct').' ON ('.$db->qn('ct.ComponentTypeId').' = '.$db->qn('c.ComponentTypeId').')')
			->where($db->qn('c.FormId') . ' = ' . $db->q($formId))
			->where($db->qn('p.PropertyName') . ' = ' . $db->q('NAME'))
			->where($db->qn('ct.ComponentTypeId') . ' IN (' . implode(',', $db->q($types)) . ')')
			->order($db->qn('c.Order') . ' ASC');


		if ($fields = $db->setQuery($query)->loadColumn())
		{
			foreach ($fields as $field)
			{
				$options[] = HTMLHelper::_('select.option', $field, $field);
			}
		}

		reset($options);

		return array_merge(parent::getOptions(), $options);
	}
}
