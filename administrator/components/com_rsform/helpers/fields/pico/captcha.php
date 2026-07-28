<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;
require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/captcha.php';

class RSFormProFieldPicoCaptcha extends RSFormProFieldCaptcha
{
	protected function setFieldOutput($image, $input, $refreshBtn, $flow) {
        $html = array();

        $html[] = '<div class="pico-row-fluid">';

        if ($flow === 'VERTICAL')
		{
			$html[] = '<div class="pico-col-md-12 text-center">';
			$html[] = '<p>' . $image . '</p>';
			$html[] = '</div>';
		}

        $html[] = '<div class="pico-col-md-12">';
        if ($flow === 'HORIZONTAL' || $refreshBtn)
        {
            $html[] = '<div role="group">';
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

	protected function getRefreshButton($onclick, $text)
	{
		return ' <button type="button" '.$this->getRefreshAttributes().' onclick="'.$onclick.'">'.$text.'</button>';
	}
}