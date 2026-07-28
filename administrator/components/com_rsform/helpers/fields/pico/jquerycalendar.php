<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/jquerycalendar.php';

class RSFormProFieldPicoJqueryCalendar extends RSFormProFieldJqueryCalendar
{
	protected function setFieldOutput($input, $button, $container, $hidden, $layout) {
		if ($layout == 'FLAT') {
			return '<div class="row-fluid"><div class="pico-col-12">'.$input.'</div>'.'<div class="pico-col-12">'.$container.'</div>'.$hidden.'</div>';
		} else {
            return '<div role="group">'.$input.$button.'</div>'.$container.$hidden;
		}
	}
}