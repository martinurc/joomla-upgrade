<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/pagebreak.php';

class RSFormProFieldPicoPageBreak extends RSFormProFieldPageBreak
{
	public function getFormInput()
	{
		return '<div role="group">' . parent::getFormInput() . '</div>';
	}

	public function getAttributes($action = null) {
		$attr = parent::getAttributes($action);
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}

		if ($action === 'prev')
		{
			$attr['class'] .= 'secondary';
		}

		return $attr;
	}
}