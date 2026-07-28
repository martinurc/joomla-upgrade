<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

if (version_compare(JVERSION, '4.4.99', '>'))
{
	JLoader::registerAlias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');
}
else
{
	FormHelper::loadFieldClass('list');
};

class JFormFieldEbeventfield extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'ebeventfield';

	protected function getOptions()
	{
		JLoader::register('EventbookingHelper', JPATH_ROOT . '/components/com_eventbooking/helper/helper.php');

		$config = EventbookingHelper::getConfig();

		$options = [];

		if ($config->event_custom_field)
		{
			// Get List Of defined custom fields
			$xml = simplexml_load_file(JPATH_ROOT . '/components/com_eventbooking/fields.xml');

			if ($xml === false)
			{
				return $options;
			}

			$fields = $xml->fields->fieldset->children();

			foreach ($fields as $field)
			{
				$name      = $field->attributes()->name;
				$label     = Text::_($field->attributes()->label);
				$options[] = HTMLHelper::_('select.option', $name, $label);
			}
		}

		return $options;
	}
}
