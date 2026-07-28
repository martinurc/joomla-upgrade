<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/submitbutton.php';

class RSFormProFieldPicoSubmitButton extends RSFormProFieldSubmitButton
{
	public function getFormInput()
	{
		return '<div role="group">' . parent::getFormInput() . '</div>';
	}

	public function getAttributes($type='button') {
		$attr = parent::getAttributes($type);
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}

		if ($type == 'previous') {
			$attr['class'] .= ' secondary';
		}

		return $attr;
	}
}