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
use Joomla\CMS\Toolbar\ToolbarHelper;

class RsformViewConfiguration extends HtmlView
{
	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('core.admin', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		$this->addToolbar();
		
		ToolbarHelper::apply('configuration.apply');
		ToolbarHelper::save('configuration.save');
		ToolbarHelper::cancel('configuration.cancel');

		$this->tabs		 = $this->get('RSTabs');
		$this->form		 = $this->get('Form');
		$this->fieldsets = $this->form->getFieldsets();
		
		parent::display($tpl);
	}
	
	public function triggerEvent($event, $args=null)
	{
        Factory::getApplication()->triggerEvent($event, $args);
	}
	
	protected function addToolbar() {
		// set title
		ToolbarHelper::title('RSForm! Pro', 'rsform');
		
		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		RSFormProToolbarHelper::addToolbar('configuration');
	}
}