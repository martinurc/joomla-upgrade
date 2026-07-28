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

class RsformViewRichtext extends HtmlView
{
	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('forms.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		$this->noEditor = $this->get('NoEditor');
		$this->lang 	= $this->get('Lang');
		$this->formId	= $this->get('FormId');

        if ($this->noEditor)
		{
			$this->textarea = $this->get('Textarea');
		}
        else
		{
			$this->editor = $this->get('Editor');
		}

		$this->editorText = $this->get('EditorText');
		$this->editorName = $this->get('EditorName');

		parent::display($tpl);
	}
}