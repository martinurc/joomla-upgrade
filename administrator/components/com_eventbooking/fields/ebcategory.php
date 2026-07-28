<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
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

class JFormFieldEBCategory extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'ebcategory';

	protected function getOptions()
	{
		if (version_compare(JVERSION, '4.0.0-dev', 'ge'))
		{
			$this->layout = 'joomla.form.field.list-fancy-select';
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id, parent AS parent_id, name AS title')
			->from('#__eb_categories')
			->where('published = 1');
		$db->setQuery($query);
		$rows     = $db->loadObjectList();
		$children = [];

		// first pass - collect children
		foreach ($rows as $v)
		{
			$pt            = $v->parent_id;
			$list          = $children[$pt] ?? [];
			$list[]        = $v;
			$children[$pt] = $list;
		}

		$list      = HTMLHelper::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);
		$options   = [];

		if (!$this->multiple)
		{
			$options[] = HTMLHelper::_('select.option', '0', Text::_('Top'));
		}

		foreach ($list as $item)
		{
			$options[] = HTMLHelper::_('select.option', $item->id, '&nbsp;&nbsp;&nbsp;' . $item->treename);
		}

		// Convert the values

		if ($this->multiple && is_string($this->value))
		{
			$this->value = explode(',', $this->value);
			$this->value = array_map('trim', $this->value);
		}

		return $options;
	}
}
