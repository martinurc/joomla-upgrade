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

class RsformViewWizard extends HtmlView
{
	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('forms.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		ToolbarHelper::title('RSForm! Pro','rsform');
		ToolbarHelper::save('wizard.stepfinal', Text::_('RSFP_FINISH'));
		ToolbarHelper::cancel('forms.cancel');

		$this->form = $this->get('Form');

		parent::display($tpl);
	}
}