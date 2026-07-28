<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Version;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;

class RsformViewRsform extends HtmlView
{
	protected $buttons;
	// version info
	protected $code;
	protected $version;
	
	public function display($tpl = null)
	{
		$this->addToolbar();

		$this->buttons  = $this->get('Buttons');
		$this->code		= $this->get('code');
		$this->version	= (string) new RSFormProVersion();
		$this->jversion = new Version();
		
		parent::display($tpl);
	}
	
	protected function addToolbar() {
		if (Factory::getUser()->authorise('core.admin', 'com_rsform'))
		{
			ToolbarHelper::preferences('com_rsform');
		}
		
		// set title
		ToolbarHelper::title('RSForm! Pro', 'rsform');
		
		require_once JPATH_COMPONENT.'/helpers/toolbar.php';
		RSFormProToolbarHelper::addToolbar('rsform');
	}
}