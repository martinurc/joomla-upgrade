<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;

class RsformViewFormajax extends HtmlView
{
	public function display($tpl = null)
	{
		switch ($this->getLayout())
		{
			case 'component':
				$this->translateIcon = RSFormProHelper::translateIcon();
				$this->fields = $this->get('componentFields');
				$this->data = $this->get('componentData');
				
				$this->type_id = $this->get('componentType');
				$this->componentId = $this->get('componentId');
				$this->published = $this->get('published');
			break;
		}
		
		parent::display($tpl);
	}
}