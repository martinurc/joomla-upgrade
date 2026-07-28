<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/radiogroup.php';

class RSFormProFieldBootstrap4RadioGroup extends RSFormProFieldRadioGroup
{
	public function buildItem($data)
	{
		$flow = $data['flow'];

		return '<div class="' . ($flow == 'HORIZONTAL' ? 'form-check form-check-inline' : 'form-check') . '">' . $this->buildInput($data) . ' ' . $this->buildLabel($data) . '</div> ';
	}

	protected function buildLabel($data)
	{
		// For convenience
		extract($data);

		return '<label id="'.$this->escape($id).$i.'-lbl" for="'.$this->escape($id).$i.'" class="form-check-label">'.$item->label.'</label>';
	}
	
	public function setFlow() {
		$flow = $this->getProperty('FLOW', 'HORIZONTAL');
			
		if ($flow != 'HORIZONTAL') {
			$this->blocks = array('1' => 'col-sm-12', '2' => 'col-sm-6', '3' => 'col-sm-4', '4' => 'col-sm-3', '6' => 'col-sm-2');
			$this->gridStart = '<div class="row">';
			$this->gridEnd = '</div>';
			$this->splitterStart = '<div class="{block_size}">';
			$this->splitterEnd = '</div>';
		}
	}

    public function getAttributes() {
        $attr = parent::getAttributes();
        if (strlen($attr['class'])) {
            $attr['class'] .= ' ';
        }
        $attr['class'] .= 'form-check-input';

        return $attr;
    }
}