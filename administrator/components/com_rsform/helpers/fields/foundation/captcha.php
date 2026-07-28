<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;
require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/captcha.php';

class RSFormProFieldFoundationCaptcha extends RSFormProFieldCaptcha
{
	protected function setFieldOutput($image, $input, $refreshBtn, $flow) {
		$html = array();

		if ($flow === 'VERTICAL')
		{
			$html[] = '<div class="medium-12 cell text-center">';
			$html[] = '<p>' . $image . '</p>';
			$html[] = '</div>';
		}

		$html[] = '<div class="medium-12 cell">';
		if ($flow === 'HORIZONTAL' || $refreshBtn)
		{
			$html[] = '<div class="input-group">';
			if ($flow === 'HORIZONTAL')
			{
				$html[] = '<span class="input-group-label">' . $image . '</span>';
			}
			$html[] = $input;
			if ($refreshBtn)
			{
				$html[] = '<div class="input-group-button">' . str_replace(array('<a href="javascript:void(0)"', '</a>'), array('<button type="button"', '</button>'), $refreshBtn) . '</div>';
			}
			$html[] = '</div>';
		}
		else
		{
			$html[] = $input;
		}

		$html[] = '</div>';

		return implode("\n", $html);
	}

	public function getAttributes()
	{
		$attr = parent::getAttributes();
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}
		$attr['class'] .= 'input-group-field';

		return $attr;
	}
	
	protected function getRefreshAttributes() {
		$attr = array(
			'class="rsform-captcha-refresh-button button secondary"'
		);
		
		return implode(' ', $attr);
	}
}