<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsformViewRsform extends HtmlView
{
	public function display( $tpl = null )
	{
		$this->params	= $this->get('Params');
		$this->formId 	= $this->get('FormId');
		
		$title = $this->params->get('page_title', '');
		if (empty($title)) {
			$title = Factory::getApplication()->get('sitename');
		}
		elseif (Factory::getApplication()->get('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', Factory::getApplication()->get('sitename'), $title);
		}
		elseif (Factory::getApplication()->get('sitename_pagetitles', 0) == 2) {
			$title = Text::sprintf('JPAGETITLE', $title, Factory::getApplication()->get('sitename'));
		}
		
		$this->document->setTitle($title);
		
		if ($this->params->get('robots')) {
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		
		if ($this->params->get('menu-meta_description')) {
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		
		if ($this->params->get('menu-meta_keywords')) {
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		
		parent::display($tpl);
	}
}