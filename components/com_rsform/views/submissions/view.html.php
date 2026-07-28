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

class RsformViewSubmissions extends HtmlView
{
	/* @var $params Joomla\Registry\Registry */
	public $params;

	public function display($tpl = null)
	{
		$this->params = Factory::getApplication()->getParams('com_rsform');
		
		if ($this->getLayout() == 'default')
		{
			$this->template		= $this->get('listingTemplate');
			$this->filter 		= $this->get('filter');
			$this->pagination 	= $this->get('pagination');
		}
		else
		{
			// Add pathway
			Factory::getApplication()->getPathway()->addItem(Text::_('RSFP_VIEW_SUBMISSION'), '');

			$this->template = $this->get('detailTemplate');
		}
		
		$title = $this->params->get('page_title', '');
		$this->setDocumentTitle($title);
		
		if ($robots = $this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $robots);
		}
		
		if ($desc = $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($desc);
		}
		
		if ($keywords = $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $keywords);
		}
		
		parent::display($tpl);
	}
}