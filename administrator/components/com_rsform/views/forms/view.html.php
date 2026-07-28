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
use Joomla\CMS\Plugin\PluginHelper;

class RsformViewForms extends HtmlView
{
    protected $layouts = array();

	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('forms.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		ToolbarHelper::title('RSForm! Pro','rsform');

		$layout = $this->getLayout();
		$this->tooltipClass = RSFormProHelper::getTooltipClass();

		if ($layout !== 'default' || $layout !== 'edit')
		{
			$this->formId = Factory::getApplication()->input->getInt('formId');
		}

		if ($layout == 'edit')
		{
			Factory::getApplication()->input->set('hidemainmenu', true);

			$this->user = Factory::getUser();

			ToolbarHelper::apply('forms.apply');
			ToolbarHelper::save('forms.save');
			ToolbarHelper::spacer();
			ToolbarHelper::custom('forms.preview', 'new tab', 'new tab', Text::_('JGLOBAL_PREVIEW'), false);
			if ($this->user->authorise('submissions.manage', 'com_rsform'))
			{
                ToolbarHelper::custom('submissions.back', 'database', 'database', Text::_('RSFP_SUBMISSIONS'), false);
            }
            if ($this->user->authorise('directory.manage', 'com_rsform'))
            {
			    ToolbarHelper::custom('forms.directory', 'folder', 'folder', Text::_('RSFP_DIRECTORY'), false);
            }
			ToolbarHelper::custom('components.copy', 'copy', 'copy', Text::_('RSFP_COPY_TO_FORM'), true);
			ToolbarHelper::custom('components.duplicate', 'copy', 'copy', Text::_('RSFP_DUPLICATE'), true);
			ToolbarHelper::deleteList(Text::_('RSFP_ARE_YOU_SURE_DELETE'), 'components.remove', Text::_('JTOOLBAR_DELETE'));
			ToolbarHelper::publishList('components.publish', Text::_('JTOOLBAR_PUBLISH'));
			ToolbarHelper::unpublishList('components.unpublish', Text::_('JTOOLBAR_UNPUBLISH'));
			ToolbarHelper::spacer();
			ToolbarHelper::cancel('forms.cancel');

			$this->tabposition = Factory::getApplication()->input->getInt('tabposition', 0);
			$this->tab 		   = Factory::getApplication()->input->getInt('tab', 0);
			$this->form 	   = $this->get('form');
			if (empty($this->form->FormId))
			{
				throw new Exception(Text::_('COM_RSFORM_FORM_DOES_NOT_EXIST'));
			}
			$this->jform	   = $this->get('JForm');
			$this->postJForm   = $this->get('PostJForm');
			$this->show_previews = RSFormProHelper::getConfig('global.grid_show_previews');
			$this->show_caption  = RSFormProHelper::getConfig('global.grid_show_caption');

			$this->hasSubmitButton = $this->get('hasSubmitButton');
			$this->hasHttpHeadersPlugin = PluginHelper::isEnabled('system', 'httpheaders');

			ToolbarHelper::title('RSForm! Pro <small>['.Text::sprintf('RSFP_EDITING_FORM', $this->form->FormTitle).']</small>','rsform');

			$this->lang = $this->get('lang');

			// workaround for first time visit
			$session 	 = Factory::getSession();
			$session->set('com_rsform.form.formId'.$this->form->FormId.'.lang', $this->lang);

			$this->fields = $this->get('fields');
			$this->quickfields = $this->get('quickfields');
			$this->pagination = $this->get('fieldspagination');
			$this->calculations = $this->get('calculations');

			$this->mappings = $this->get('mappings');
			$this->conditions = $this->get('conditions');
			$this->formId = $this->form->FormId;
			$this->emails = $this->get('emails');

			// layouts
			$this->layouts = RSFormProHelper::getFormLayouts($this->formId);

			$displayPlaceholders = RSFormProHelper::generateQuickAddGlobal('display', true);
			foreach ($this->quickfields as $fields)
			{
				$displayPlaceholders = array_merge($displayPlaceholders, $fields['display']);
			}

			$this->fieldGroups = $this->get('FieldGroups');

			$this->document->addScriptDeclaration('RSFormPro.Placeholders = ' . json_encode(array_values($displayPlaceholders)) . ';');
			$this->document->addScriptDeclaration('RSFormPro.PreviewItemid = ' . (int) RSFormProHelper::getConfig('global.preview_itemid') . ';');
		}
		elseif ($layout == 'component_copy')
		{
			ToolbarHelper::custom('components.copyprocess', 'copy', 'copy', Text::_('RSFP_DO_COPY'), false);
			ToolbarHelper::cancel('components.copycancel');

			$this->cids = Factory::getApplication()->input->get('cid', array(), 'array');
			$this->lists = array(
				'forms' => HTMLHelper::_('select.genericlist', $this->get('formlist'), 'toFormId', array('class' => 'form-select'), 'value', 'text')
			);
		}
		elseif ($layout == 'edit_mappings')
		{
			$this->mappings = $this->get('mappings');
		}
		elseif ($layout == 'edit_conditions')
		{
			$this->conditions = $this->get('conditions');
		}
		elseif ($layout == 'edit_emails')
		{
			$this->emails   = $this->get('emails');
			$this->lang     = $this->get('lang');
		}
		elseif ($layout == 'edit_calculations')
		{
			$this->calculations = $this->get('calculations');
		}
		elseif ($layout == 'show')
		{
            Factory::getLanguage()->load('com_rsform', JPATH_SITE);

			$this->setToolbarTitle();
		}
		else
		{
			$this->addToolbar();

            ToolbarHelper::addNew('wizard.stepfinal', Text::_('JTOOLBAR_NEW'));
            ToolbarHelper::custom('wizard.add', 'play', 'play', Text::_('COM_RSFORM_NEW_FORM_WIZARD'), false);
			ToolbarHelper::spacer();
			ToolbarHelper::custom('forms.copy', 'copy', 'copy', Text::_('RSFP_DUPLICATE'), true);
			ToolbarHelper::spacer();
			ToolbarHelper::deleteList(Text::_('RSFP_ARE_YOU_SURE_DELETE'), 'forms.delete', Text::_('JTOOLBAR_DELETE'));
			ToolbarHelper::spacer();
			ToolbarHelper::publishList('forms.publish', Text::_('JTOOLBAR_PUBLISH'));
			ToolbarHelper::unpublishList('forms.unpublish', Text::_('JTOOLBAR_UNPUBLISH'));

			$this->user       = Factory::getUser();
			$this->items 	  = $this->get('forms');
			$this->pagination = $this->get('Pagination');

			$this->sortColumn = $this->get('sortColumn');
			$this->sortOrder  = $this->get('sortOrder');

			$this->month = Factory::getDate('now', Factory::getUser()->getTimezone());
			$this->month->setDate($this->month->year, $this->month->month, 1);
			$this->month->setTime(0, 0, 0);
			$this->month = $this->month->format('Y-m-d');

			$this->today = HTMLHelper::_('date', 'now', 'Y-m-d');

			$this->disable_multilanguage = RSFormProHelper::getConfig('global.disable_multilanguage');
			$this->preview_itemid = RSFormProHelper::getConfig('global.preview_itemid');

			$this->state 		 = $this->get('State');
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}

		parent::display($tpl);
	}

	protected function triggerEvent($event, $params = array()) {
        Factory::getApplication()->triggerEvent($event, $params);
	}

	protected function addToolbar() {
		static $called;

		// this is a workaround so if called multiple times it will not duplicate the buttons
		if (!$called) {
			// set title
			ToolbarHelper::title('RSForm! Pro', 'rsform');

			require_once JPATH_COMPONENT.'/helpers/toolbar.php';
			RSFormProToolbarHelper::addToolbar('forms');

			$called = true;
		}
	}

	protected function setToolbarTitle()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->qn('FormTitle'))
            ->from($db->qn('#__rsform_forms'))
            ->where($db->qn('FormId') . ' = ' . $db->q($this->formId));

        $title = $db->setQuery($query)->loadResult();

        $lang = RSFormProHelper::getCurrentLanguage($this->formId);
        if ($translations = RSFormProHelper::getTranslations('forms', $this->formId, $lang))
        {
            if (isset($translations['FormTitle']))
            {
                $title = $translations['FormTitle'];
            }
        }

        ToolbarHelper::title($title,'rsform');
    }
	
	protected function buildGrid()
	{
		$rows 		= array();
		$hidden		= array();
		$row_index 	= 0;
		if (strlen($this->form->GridLayout))
		{
			$used = array();
			$data = json_decode($this->form->GridLayout, true);
			
			// If decoding is successful, we should have $rows and $hidden
			if (is_array($data) && isset($data[0], $data[1]))
			{
				$rows 	= $data[0];
				$hidden = $data[1];
			}
			
			// Actual layout (rows and columns)
			if ($rows)
			{
				foreach ($rows as $row_index => &$row)
				{
					foreach ($row['columns'] as $column_index => $fields)
					{
						foreach ($fields as $position => $id)
						{
							if (isset($this->fields[$id]))
							{
								// Pages have a special property
								if ($this->fields[$id]->type_id == RSFORM_FIELD_PAGEBREAK)
								{
									$row['has_pagebreak'] = true;
								}
								$row['columns'][$column_index][$position] = $this->fields[$id];
								
								$used[] = $id;
							}
							else
							{
								// Field doesn't exist, remove it from grid
								unset($row['columns'][$column_index][$position]);
							}
						}
					}
				}
				unset($row);
			}
			
			// This array just holds hidden fields so we can sort them separately
			if ($hidden)
			{
				foreach ($hidden as $hidden_index => $id)
				{
					if (isset($this->fields[$id]))
					{
						$hidden[$hidden_index] = $this->fields[$id];
						
						$used[] = $id;
					}
					else
					{
						// Field doesn't exist, remove it from grid
						unset($hidden[$hidden_index]);
					}
				}
			}
			
			// Let's see if we've added new fields in the meantime
			$diff = array();
			if ($array_diff = array_diff(array_keys($this->fields), $used))
			{
				foreach ($array_diff as $id)
				{
					$diff[] = $this->fields[$id];
				}

				// Must not be a page container
				$row = end($rows);
				if (!empty($row['has_pagebreak']))
				{
                    $row_index++;
                }
			}
		}
		else
		{
			$diff = $this->fields;
		}

		$hiddenComponents = array(
			RSFORM_FIELD_HIDDEN,
			RSFORM_FIELD_TICKET
		);

		Factory::getApplication()->triggerEvent('onRsformDefineHiddenComponents', array(&$hiddenComponents));

		$session = Factory::getSession();
		$receivedRowIndex = $session->set('com_rsform.grid.row', null);
		$receivedColumnIndex = $session->set('com_rsform.grid.column', null);

		// Let's add fields to rows, keeping pages on a separate row
		foreach ($diff as $field)
		{
			// These are hidden fields and should be sorted separately in the $hidden array
			if (in_array($field->type_id, $hiddenComponents) || $field->type_name == 'hidden')
			{
				$hidden[] = $field;
				continue;
			}
			
			if (!isset($rows[$row_index]))
			{
				$rows[$row_index] = array(
					'columns' => array(array()),
					'sizes'   => array(12)
				);
			}
			
			// Pages are the only item on a row, they can't be resized
			if ($field->type_id == RSFORM_FIELD_PAGEBREAK)
			{
				$page = array(
					'columns'       => array(array($field)),
					'sizes'         => array(12),
					'has_pagebreak' => true
				);
				if ($receivedRowIndex !== null && $row_index > $receivedRowIndex)
				{
					$first = array_slice($rows, 0, $receivedRowIndex + 1);
					$second = array_slice($rows, $receivedRowIndex + 1);

					$rows = array_merge($first, array($page), $second);
				}
				else
				{
					// Add new row with just this page
					$rows[++$row_index] = $page;

					$row_index++;
				}
			}
			else
			{
				if ($receivedRowIndex !== null && $receivedColumnIndex !== null && isset($rows[$receivedRowIndex]['columns'][$receivedColumnIndex]))
				{
					$rows[$receivedRowIndex]['columns'][$receivedColumnIndex][] = $field;
				}
				else
				{
					$rows[$row_index]['columns'][0][] = $field;
				}
			}
		}
		
		return array($rows, $hidden);
	}

	protected function adjustPreview($preview, $useDivs = true)
	{
		if ($useDivs)
		{
			$preview = '<div>' . $preview . '</div>';
		}

		if (class_exists('DOMDocument'))
		{
			$doc    = new DOMDocument();
			$errors = libxml_use_internal_errors(true);
			$doc->loadHTML('<?xml version="1.0" encoding="UTF-8"?><html_tags>' . $preview . '</html_tags>');
			$doc->encoding = 'UTF-8';
			libxml_clear_errors();
			$preview = substr($doc->saveHTML($doc->getElementsByTagName('html_tags')->item(0)), strlen('<html_tags>'), -strlen('</html_tags>'));

			libxml_use_internal_errors($errors);
		}

		return $preview;
	}
}