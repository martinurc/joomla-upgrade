<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

class RSFormProFormLayout
{	
	// the default progress bar layout
	public $progressContent = '<div><p><em>{page_lang} <strong>{page}</strong> {of_lang} {total}</em></p><div class="rsformProgressContainer"><div class="rsformProgressBar" style="width: {percent}%;"></div></div></div>';
	protected $progressOverwritten = false;
	
	public function __construct() {
		$replace = array('{page_lang}', '{of_lang}', '{direction}');
		$with = array(Text::_('RSFP_PROGRESS_PAGE'), Text::_('RSFP_PROGRESS_OF'), '; float:right');
		
		if ($this->getDirection() == 'rtl' && !$this->progressOverwritten) {
			$this->progressContent = '<div><p><em>{total} {of_lang} <strong>{page}</strong> {page_lang}</em></p><div class="rsformProgressContainer"><div class="rsformProgressBar" style="width: {percent}%;"></div></div></div>';
		}
		
		$this->progressContent = str_replace($replace, $with, $this->progressContent);
	}

	protected function getDirection()
	{
		return Factory::getDocument()->direction;
	}
	
	protected function addStyleSheet($path) {
		$stylesheet = HTMLHelper::_('stylesheet', $path, array('pathOnly' => true, 'relative' => true));
		RSFormProAssets::addStyleSheet($stylesheet);
	}
	
	protected function addScript($path) {
		$script = HTMLHelper::_('script', $path, array('pathOnly' => true, 'relative' => true));
		RSFormProAssets::addScript($script);
	} 
	
	protected function addScriptDeclaration($script) {
		RSFormProAssets::addScriptDeclaration($script);
	}
	
	protected function addjQuery()
	{
		RSFormProAssets::addJquery();
	}
}