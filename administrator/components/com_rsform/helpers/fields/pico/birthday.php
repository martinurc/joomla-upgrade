<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/


defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/birthday.php';

class RSFormProFieldPicoBirthDay extends RSFormProFieldBirthDay
{
	public function getFormInput()
	{
		$this->setProperty('DATESEPARATOR', '<span class="pico-button">' . $this->getProperty('DATESEPARATOR') . '</span>');
		return '<div role="group">' . parent::getFormInput() . '</div>';
	}
}