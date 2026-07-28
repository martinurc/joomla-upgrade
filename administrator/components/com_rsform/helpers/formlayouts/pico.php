<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__.'/../formlayout.php';

class RSFormProFormLayoutPico extends RSFormProFormLayout
{
    public $errorClass      = '';
    public $fieldErrorClass = 'rsform-error';

	public $progressContent = '<div>{page_lang} <strong>{page}</strong> {of_lang} {total}<progress value="{percent}" max="100"></progress></div>';
	
	public function __construct() {
		if ($this->getDirection() == 'rtl') {
			$this->progressContent = '<div>{total} {of_lang} <strong>{page}</strong> {page_lang}<progress value="{percent}" max="100"></progress></div>';
		}
		$this->progressOverwritten = true;
		parent::__construct();
		
	}
    public function loadFramework() {
        // Load the CSS files
        $this->addStyleSheet('com_rsform/frameworks/pico/pico.css');

		if ($color = RSFormProHelper::getConfig('global.default_pico_color'))
		{
			$this->addStyleSheet('com_rsform/frameworks/pico/pico.' . $color . '.css');
		}
    }

    public function modifyForm(&$form)
	{
		$form->CSSClass .= ' pico';
	}

    public function generateButton($goto)
    {
        return
            '<div class="pico">'.
                '<button type="button" class="rsform-submit-button rsform-thankyou-button" name="continue" onclick="'.$goto.'">'.Text::_('RSFP_THANKYOU_BUTTON').'</button>'.
            '</div>';
    }
}