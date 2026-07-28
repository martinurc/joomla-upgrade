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
use Joomla\CMS\HTML\HTMLHelper;

class RsformViewSubmissions extends HtmlView
{
    protected $previewArray = array();
    protected $staticHeaders = array();
    protected $headers = array();

	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('submissions.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		if (version_compare(JVERSION, '4.0', '>='))
		{
			Factory::getApplication()->input->set('hidemainmenu', true);
		}
		
		$this->tooltipClass = RSFormProHelper::getTooltipClass();
		$this->formId = $this->get('formId');
		
		$layout = strtolower($this->getLayout());
		if ($layout == 'export')
		{
			$this->headers = $this->get('headers');
			$this->staticHeaders = $this->get('staticHeaders');

			for ($i = 0; $i < count($this->staticHeaders) + count($this->headers); $i++)
			{
				$this->previewArray[] = 'Value '.$i;
			}

			$this->formTitle = $this->get('formTitle');
			$this->multipleSeparator = $this->get('FormMultipleSeparator');
			$this->exportSelected = $this->get('exportSelected');
			$this->exportSelectedCount = count($this->exportSelected);
			$this->exportFilteredCount = $this->get('Total');
			$this->exportAll = $this->exportSelectedCount == 0;
			$this->exportType = $this->get('exportType');
			$this->exportFile = $this->get('exportFile');
			$this->tabs = new RSFormProAdapterTabs('exportTabs');

			ToolbarHelper::title('RSForm! Pro <small>['.Text::sprintf('RSFP_EXPORTING', $this->exportType, $this->formTitle).']</small>','rsform');

			ToolbarHelper::custom('submissions.exporttask', 'archive', 'archive', Text::_('RSFP_EXPORT'), false);
			ToolbarHelper::spacer();
			ToolbarHelper::cancel('submissions.manage');
		}
        elseif ($layout == 'import')
        {
            $this->headers = $this->get('headers');
            $this->staticHeaders = $this->get('staticHeaders');
            $this->formTitle = $this->get('formTitle');
            $this->previewData = $this->get('previewImportData');
            $this->countHeaders = $this->previewData ? count(reset($this->previewData)) : 0;

            $options = array(
                HTMLHelper::_('select.option', '', Text::_('COM_RSFORM_IMPORT_IGNORE'))
            );
            foreach ($this->staticHeaders as $header)
            {
                $options[] = HTMLHelper::_('select.option', $header->value, $header->label);
            }
            foreach ($this->headers as $header)
            {
                $options[] = HTMLHelper::_('select.option', $header->value, $header->label);
            }
            $this->options = $options;
			$this->selected = $this->get('previewSelectedData');

            ToolbarHelper::title('RSForm! Pro <small>['.Text::sprintf('COM_RSFORM_IMPORTING', $this->formTitle).']</small>','rsform');

	        ToolbarHelper::custom('submissions.importtask', 'archive', 'archive', Text::_('COM_RSFORM_IMPORT_SUBMISSIONS'), false);
	        ToolbarHelper::spacer();
	        ToolbarHelper::cancel('submissions.manage');
        }
		elseif ($layout == 'exportprocess')
		{
			$this->limit        = RSFormProHelper::getConfig('export.limit');
			$this->total        = $this->get('exportTotal');
			$this->file         = Factory::getApplication()->input->getCmd('ExportFile');
			$this->exportType   = Factory::getApplication()->input->getCmd('ExportType');
			$this->formId	    = $this->get('FormId');

			ToolbarHelper::title('RSForm! Pro <small>['.Text::sprintf('RSFP_EXPORTING', $this->exportType, $this->get('formTitle')).']</small>','rsform');

			ToolbarHelper::custom('submissions.cancelform', 'previous', 'previous', Text::_('RSFP_BACK_TO_FORM'), false);
			ToolbarHelper::custom('submissions.back', 'database', 'database', Text::_('RSFP_SUBMISSIONS'), false);
        }
        elseif ($layout == 'importprocess')
        {
            $this->limit    = 500;
            $this->total    = $this->get('importTotal');
            $this->formId	= $this->get('FormId');

            ToolbarHelper::title('RSForm! Pro <small>['.Text::sprintf('COM_RSFORM_IMPORTING', $this->get('formTitle')).']</small>','rsform');

            ToolbarHelper::custom('submissions.cancelform', 'previous', 'previous', Text::_('RSFP_BACK_TO_FORM'), false);
            ToolbarHelper::custom('submissions.back', 'database', 'database', Text::_('RSFP_SUBMISSIONS'), false);
        }
		elseif ($layout == 'edit')
		{
			$this->formId = $this->get('submissionFormId');
			$this->submissionId = $this->get('submissionId');
			$this->submission = $this->get('submission');
			$this->staticHeaders = $this->get('staticHeaders');
			$this->staticFields = $this->get('staticFields');
			$this->fields = $this->get('editFields');

			ToolbarHelper::title('RSForm! Pro','rsform');

			ToolbarHelper::custom('submissions.exportpdf', 'archive', 'archive', Text::_('RSFP_EXPORT_PDF'), false);
			ToolbarHelper::spacer();
			ToolbarHelper::apply('submissions.apply');
			ToolbarHelper::save('submissions.save');
			ToolbarHelper::spacer();
			ToolbarHelper::cancel('submissions.manage');
		}
		else
		{
		    $this->user = Factory::getUser();
			$this->form = $this->get('FormProperties');
			$this->headers = $this->get('headers');
			$this->unescapedFields = $this->get('unescapedFields');
			$this->staticHeaders = $this->get('staticHeaders');
			$this->submissions = $this->get('submissions');
			$this->pagination = $this->get('pagination');
			$this->sortColumn = $this->get('sortColumn');
			$this->sortOrder = $this->get('sortOrder');
			$this->specialFields = $this->get('specialFields');
			$this->filter = $this->get('filter');

			$this->state         = $this->get('State');
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');

            if ($this->user->authorise('forms.manage', 'com_rsform'))
            {
                ToolbarHelper::custom('submissions.cancelform', 'previous', 'previous', Text::_('RSFP_BACK_TO_FORM'), false);
                ToolbarHelper::spacer();
            }

            // Choose columns
			ToolbarHelper::modal('columnsModal', 'icon icon-checkmark', 'RSFP_CUSTOMIZE_COLUMNS');
			ToolbarHelper::spacer();
			ToolbarHelper::custom('submissions.resend', 'mail', 'mail', Text::_('RSFP_RESEND_EMAILS'), true);

			if ($this->form->ConfirmSubmission)
			{
				ToolbarHelper::custom('submissions.confirm', 'checkmark-2', 'checkmark-2', Text::_('COM_RSFORM_CONFIRM_SUBMISSIONS'), true);
			}

            ToolbarHelper::modal('exportModal', 'icon-archive icon white', 'RSFP_EXPORT');
            ToolbarHelper::modal('importModal', 'icon-upload icon white', 'COM_RSFORM_IMPORT_SUBMISSIONS');
            ToolbarHelper::spacer();
			ToolbarHelper::editList('submissions.edit', Text::_('JTOOLBAR_EDIT'));
			ToolbarHelper::deleteList(Text::_('RSFP_ARE_YOU_SURE_DELETE'), 'submissions.delete', Text::_('JTOOLBAR_DELETE'));
			ToolbarHelper::spacer();
			ToolbarHelper::cancel('submissions.cancel', Text::_('JTOOLBAR_CLOSE'));

			ToolbarHelper::title('RSForm! Pro <small>['.$this->get('formTitle').']</small>','rsform');
		}
		
		parent::display($tpl);
	}
}