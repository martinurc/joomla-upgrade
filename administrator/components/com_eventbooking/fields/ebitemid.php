<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\MenuItem;

if (version_compare(JVERSION, '4.4.99', '>'))
{
	JLoader::registerAlias('JFormFieldGroupedList', '\\Joomla\\CMS\\Form\\Field\\GroupedlistField');
}
else
{
	FormHelper::loadFieldClass('GroupedList');
}

class JFormFieldEBItemid extends JFormFieldGroupedList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'ebitemid';

	/**
	 * Return list of options for the field
	 *
	 * @return array
	 */
	public function getGroups()
	{
		$component = ComponentHelper::getComponent('com_eventbooking');
		$menus     = Factory::getApplication()->getMenu('site');

		$attributes = ['component_id'];
		$values     = [$component->id];
		$items      = $menus->getItems($attributes, $values);

		$groups = [];

		foreach ($items as $item)
		{
			if ($this->isValidMenuItem($item))
			{
				if ($item->language !== '*')
				{
					$lang = ' (' . $item->language . ')';
				}
				else
				{
					$lang = '';
				}

				$groups[$item->menutype][] = HTMLHelper::_('select.option', $item->id, str_repeat('- ', $item->level) . $item->title . $lang);
			}
		}

		array_unshift($groups, [HTMLHelper::_('select.option', 0, Text::_('Use Default'))]);

		return $groups;
	}

	/**
	 * Method to check if the menu item is a valid menu item which can be used
	 *
	 * @param   MenuItem  $item
	 *
	 * @return bool
	 */
	private function isValidMenuItem($item)
	{
		if (!empty($item->query['view']) && in_array(
				$item->query['view'],
				['upcomingevents', 'fullcalendar', 'calendar', 'categories', 'category', 'search']
			))
		{
			return true;
		}

		return false;
	}
}
