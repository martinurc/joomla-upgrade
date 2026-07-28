<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/jquerycalendar.php';

class RSFormProFieldFoundationJqueryCalendar extends RSFormProFieldJqueryCalendar
{
	protected function setFieldOutput($input, $button, $container, $hidden, $layout) {
		if ($layout == 'FLAT') {
			return '<div class="grid-x"><div class="medium-12 cell">'.$input.'</div>'.'<div class="medium-12 cell">'.$container.'</div>'.$hidden.'</div>';
		} else {
			return '<div class="input-group">' . $input . '<div class="input-group-button">' . $button . '</div></div>'.$container.$hidden;
		}
	}
	
	// @desc All calendars should have a 'rsform-calendar-box' class for easy styling
	//		 Since the calendar is composed of multiple items, we need to differentiate the attributes through the $type parameter
	public function getAttributes($type='input') {
		$attr = parent::getAttributes($type);
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}
		
		if ($type == 'button') {
			$attr['class'] .= 'button secondary';
		}

		if ($type === 'input')
		{
			$attr['class'] .= 'input-group-field';
		}
		
		return $attr;
	}
}