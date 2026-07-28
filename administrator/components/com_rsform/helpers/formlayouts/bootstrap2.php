<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

require_once __DIR__.'/../formlayout.php';

class RSFormProFormLayoutBootstrap2 extends RSFormProFormLayout
{
	public $errorClass      = ' error';
    public $fieldErrorClass = '';

	public $progressContent = '<div><div class="progress progress-info"><div class="bar" style="width: {percent}%"><em>{page_lang} <strong>{page}</strong> {of_lang} {total}</em></div></div></div>';
	
	
	public function __construct() {
		if ($this->getDirection() == 'rtl') {
			$this->progressContent = '<div><div class="progress progress-info"><div class="bar" style="width: {percent}%{direction}"><em>{total} {of_lang} <strong>{page}</strong> {page_lang}</em></div></div></div>';
		}
		$this->progressOverwritten = true;
		parent::__construct();
		
	}
	
	public function loadFramework()
	{
		if (version_compare(JVERSION, '4.0', '<'))
		{
			// Joomla! 3 has Bootstrap 2.3.2 built-in
			HTMLHelper::_('bootstrap.framework');
			HTMLHelper::_('bootstrap.loadCss', true, $this->getDirection());

			// Load tooltips
			HTMLHelper::_('bootstrap.tooltip');
		}
		else
		{
			// Joomla! 4 needs its own files
			// Load jQuery
			$this->addjQuery();

			// Load the CSS files
			$this->addStyleSheet('com_rsform/frameworks/bootstrap2/bootstrap.min.css');

			// Load Javascript
			$this->addScript('com_rsform/frameworks/bootstrap2/bootstrap.min.js');

			// Load tooltips
			$this->addScriptDeclaration('jQuery(function($){ $(document).find(".hasTooltip").tooltip({"html": true,"container": "body"}); });');
		}
	}

    public function generateButton($goto)
    {
        return '<button type="button" class="rsform-submit-button rsform-thankyou-button btn btn-primary" name="continue" onclick="'.$goto.'">'.Text::_('RSFP_THANKYOU_BUTTON').'</button>';
    }
}
