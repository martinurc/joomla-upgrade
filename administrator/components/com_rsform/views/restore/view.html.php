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

class RsformViewRestore extends HtmlView
{
	public function display($tpl = null) {
        if (!Factory::getUser()->authorise('backuprestore.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		$this->addToolbar();

		$this->key			= $this->get('Key');
		$this->overwrite	= $this->get('Overwrite');
		$this->keepId		= $this->get('KeepId');
		
		$this->config = RSFormProConfig::getInstance();
		
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		// set title
		ToolbarHelper::title('RSForm! Pro', 'rsform');
		
		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		RSFormProToolbarHelper::addToolbar('restorescreen');
	}
}