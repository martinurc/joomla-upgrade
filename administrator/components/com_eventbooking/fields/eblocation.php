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
}

class JFormFieldEBLocation extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'eblocation';

	protected function getOptions()
	{
		require_once JPATH_ROOT . '/components/com_eventbooking/helper/database.php';

		if (version_compare(JVERSION, '4.0.0-dev', 'ge'))
		{
			$this->layout = 'joomla.form.field.list-fancy-select';
		}

		$options = [];

		if (!$this->multiple)
		{
			$options[] = HTMLHelper::_('select.option', '0', Text::_('Select Location'));
		}

		foreach (EventbookingHelperDatabase::getAllLocations() as $location)
		{
			$options[] = HTMLHelper::_('select.option', $location->id, $location->name);
		}

		// Convert value of value
		if ($this->multiple && is_string($this->value))
		{
			$this->value = [$this->value];
		}

		return $options;
	}
}
