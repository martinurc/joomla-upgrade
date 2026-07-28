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

class RsformViewFiles extends HtmlView
{
	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('forms.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		$this->canUpload 	= $this->get('canUpload');
		$this->files 		= $this->get('files');
		$this->folders 		= $this->get('folders');
		$this->elements 	= $this->get('elements');
		$this->current 		= $this->get('current');
		$this->previous 	= $this->get('previous');
		
		parent::display($tpl);
	}
}