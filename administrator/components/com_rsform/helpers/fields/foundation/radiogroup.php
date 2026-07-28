<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/radiogroup.php';

class RSFormProFieldFoundationRadioGroup extends RSFormProFieldRadioGroup
{
	public function setFlow()
	{
		$flow = $this->getProperty('FLOW', 'HORIZONTAL');

		if ($flow != 'HORIZONTAL')
		{
			$this->glue = '<br>';
			$this->blocks = array('1' => 'medium-12 cell', '2' => 'medium-6 cell', '3' => 'medium-4 cell', '4' => 'medium-3 cell', '6' => 'medium-2 cell');
			$this->gridStart = '<div class="grid-x grid-padding-x">';
			$this->gridEnd = '</div>';
			$this->splitterStart = '<div class="{block_size}">';
			$this->splitterEnd = '</div>';
		}
	}
}