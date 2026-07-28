<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/radiogroup.php';

class RSFormProFieldPicoRadioGroup extends RSFormProFieldRadioGroup
{
	protected function buildLabel($data)
	{
		extract($data);
		$flow = $this->getProperty('FLOW', 'HORIZONTAL');

		if ($flow !== 'HORIZONTAL')
		{
			return '<label id="'.$this->escape($id).$i.'-lbl" for="'.$this->escape($id).$i.'">'.$this->buildInput($data).$item->label.'</label>';
		}
		else
		{
			return parent::buildLabel($data);
		}
	}

	public function buildItem($data)
	{
		$flow = $this->getProperty('FLOW', 'HORIZONTAL');

		if ($flow !== 'HORIZONTAL')
		{
			return $this->buildLabel($data);
		}
		else
		{
			return parent::buildItem($data);
		}
	}

	public function setFlow()
	{
		$flow = $this->getProperty('FLOW', 'HORIZONTAL');

		$this->glue = '';

		if ($flow != 'HORIZONTAL')
		{
			$this->blocks = array('1' => 'pico-col-12', '2' => 'pico-col-6', '3' => 'pico-col-4', '4' => 'pico-col-3', '6' => 'pico-col-2');
			$this->gridStart = '<div class="pico-row-fluid">';
			$this->gridEnd = '</div>';
			$this->splitterStart = '<div class="{block_size}">';
			$this->splitterEnd = '</div>';
		}
	}
}