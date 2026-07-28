<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/


defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/birthday.php';

class RSFormProFieldFoundationBirthDay extends RSFormProFieldBirthDay
{
	public function getFormInput() {
		$separator	= $this->getProperty('DATESEPARATOR');
		$items = parent::getFormInput();
		
		if (preg_match_all('/<select.*?><\/select>/', $items, $matches))
		{
			$items = $matches[0];
		}
		else
		{
			// This shouldn't be the case (it wasn't the case)
			$items = explode($separator, $items);
		}
		
		// extra classes for proper alignment
		foreach ($items as &$item)
		{
			$item = '<div class="cell auto">'.$item.'</div>';
		}
		return '<div class="grid-x grid-padding-x">'.implode('', $items).'</div>';
	}
}