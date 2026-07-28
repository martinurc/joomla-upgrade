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
use Joomla\CMS\Plugin\PluginHelper;

class RsformViewDirectory extends HtmlView
{
	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('directory.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		// set title
		ToolbarHelper::title('RSForm! Pro', 'rsform');
		
		$layout = strtolower($this->getLayout());
		
		if ($layout == 'edit')
		{
			Factory::getApplication()->input->set('hidemainmenu', true);

			ToolbarHelper::apply('directory.apply');
			ToolbarHelper::save('directory.save');
			ToolbarHelper::cancel('directory.cancel');

			Text::script('RSFP_AUTOGENERATE_LAYOUT_WARNING_SURE');

            $this->user = Factory::getUser();

            if ($this->user->authorise('forms.manage', 'com_rsform'))
            {
                ToolbarHelper::spacer();
                ToolbarHelper::custom('directory.cancelform', 'previous', 'previous', Text::_('RSFP_BACK_TO_FORM'), false);
            }

            $this->form         = $this->get('Form');
			$this->directory	= $this->get('Directory');
			$this->formId		= Factory::getApplication()->input->getInt('formId',0);
			$this->tab			= Factory::getApplication()->input->getInt('tab', 0);
			$this->emails		= $this->get('emails');
			$this->fields		= RSFormProHelper::getDirectoryFields($this->formId);
			$this->quickfields	= $this->get('QuickFields');
			$this->allowedDateFields = $this->get('AllowedDateFields');
			$this->hasHttpHeadersPlugin = PluginHelper::isEnabled('system', 'httpheaders');

			ToolbarHelper::title('RSForm! Pro <small>['.Text::sprintf('RSFP_EDITING_DIRECTORY', $this->get('formTitle')).']</small>','rsform');
		}
		elseif ($layout == 'edit_emails')
		{
			$this->emails = $this->get('emails');
		}
		else
		{
			$this->addToolbar();
			ToolbarHelper::title(Text::_('RSFP_SUBM_DIR'),'rsform');
			ToolbarHelper::deleteList('','directory.remove');

			$this->items		= $this->get('forms');
			$this->pagination	= $this->get('pagination');
			$this->sortColumn 	= $this->get('sortColumn');
			$this->sortOrder 	= $this->get('sortOrder');

			$this->state         = $this->get('State');
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}
		
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		static $called;
		
		// this is a workaround so if called multiple times it will not duplicate the buttons
		if (!$called) {			
			require_once JPATH_COMPONENT.'/helpers/toolbar.php';
			RSFormProToolbarHelper::addToolbar('directory');
			
			$called = true;
		}
	}

	public function getHeaderLabel($field)
    {
        Factory::getApplication()->triggerEvent('onRsformBackendGetHeaderLabel', array(&$field->FieldName, $this->formId));

        $staticHeaders = RSFormProHelper::getDirectoryStaticHeaders();

        if ($field->componentId < 0 && isset($staticHeaders[$field->componentId]))
        {
            return Text::sprintf('RSFP_DIRECTORY_SUBMISSION_HEADER', $field->FieldName);
        }

        return $field->FieldName;
    }
}