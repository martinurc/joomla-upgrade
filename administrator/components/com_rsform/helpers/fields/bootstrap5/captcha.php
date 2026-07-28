<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;
require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/captcha.php';

class RSFormProFieldBootstrap5Captcha extends RSFormProFieldCaptcha
{
	protected function setFieldOutput($image, $input, $refreshBtn, $flow) {
        $html = array();

        $html[] = '<div class="row">';

        if ($flow === 'VERTICAL')
		{
			$html[] = '<div class="col-md-12 text-center">';
			$html[] = '<p>' . $image . '</p>';
			$html[] = '</div>';
		}

        $html[] = '<div class="col-md-12">';
        if ($flow === 'HORIZONTAL' || $refreshBtn)
        {
            $html[] = '<div class="input-group">';
            if ($flow === 'HORIZONTAL')
			{
				$html[] = '<span class="input-group-text">' . $image . '</span>';
			}
            $html[] = $input;
            if ($refreshBtn)
			{
				$html[] = $refreshBtn;
			}
            $html[] = '</div>';
        }
        else
        {
            $html[] = $input;
        }

        $html[] = '</div>';
        $html[] = '</div>';

        return implode("\n", $html);
	}
	
	
	// @desc All captcha textboxes should have a 'rsform-captcha-box' class for easy styling
	public function getAttributes() {
		$attr = parent::getAttributes();
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}
		$attr['class'] .= 'form-control';
		
		return $attr;
	}
	
	protected function getRefreshAttributes() {
		$attr = array(
			'class="rsform-captcha-refresh-button btn btn-secondary"'
		);
		
		return implode(' ', $attr);
	}
}