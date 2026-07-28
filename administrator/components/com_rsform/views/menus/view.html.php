<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsformViewMenus extends HtmlView
{
	public function display($tpl = null)
	{
		ToolbarHelper::title('RSForm! Pro','rsform');
		
		$this->formId 		= Factory::getApplication()->input->getInt('formId');
		$this->formTitle 	= $this->get('formtitle');
		$this->menus 		= $this->get('menus');
		$this->pagination 	= $this->get('pagination');

		ToolbarHelper::custom('menus.cancelform', 'previous', 'previous', Text::_('RSFP_BACK_TO_FORM'), false);
		ToolbarHelper::spacer();
		ToolbarHelper::cancel('submissions.cancel', Text::_('JTOOLBAR_CLOSE'));
		
		parent::display($tpl);
	}
}