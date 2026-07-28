<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Utilities\IpHelper;

class RsformModelSubmissions extends ListModel
{
	public $_data = array();
	
	public $firstFormId = 0;
	public $allFormIds = array();
	
	public $export = false;
	public $rows = 0;
	public $exportType = '';
	public $stripLines = false;
	public $multipleSeparator;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array_merge(array_keys($this->getHeaders()), array_keys($this->getStaticHeaders()));

			// Need these so that 'Filter Options' is shown
			$config['filter_fields'][] = 'dateFrom';
			$config['filter_fields'][] = 'dateTo';
			$config['filter_fields'][] = 'language';
		}

		parent::__construct($config);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.language');
		$id .= ':' . $this->getState('filter.dateFrom');
		$id .= ':' . $this->getState('filter.dateTo');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		$sortColumn 	= $this->getSortColumn();
		$sortOrder 		= $this->getSortOrder();
		$formId 		= $this->getFormId();
		$filter 		= $this->getFilter();
		$languageFilter	= $this->getLang();
		$isStaticHeader = in_array($sortColumn, array_keys($this->getStaticHeaders()));
		$join			= false;
		$db             = $this->getDbo();

		$query = $db->getQuery(true)
			->select('s.*')
			->from($db->qn('#__rsform_submissions', 's'))
			->where($db->qn('s.FormId') . ' = ' . $db->q($formId));

		// Only for export - export selected rows
		if ($this->export && !empty($this->rows))
		{
			$query->where($db->qn('s.SubmissionId') . ' IN (' . implode(',', $db->q($this->rows)) . ')');
		}

		// Check if there's a filter (search) set
		if (!$this->export)
		{
			if ($filter !== null && $filter !== '' && strlen($filter))
			{
				$or 			= array();
				$join 			= true;
				$escapedFilter  = $db->q('%' . $db->escape($filter) . '%', false);

				if (!preg_match('#([^0-9\-: ])#', $filter))
				{
					$or[] = $db->qn('s.DateSubmitted') . ' LIKE ' . $escapedFilter;
				}
				$or[] = $db->qn('s.Username') . ' LIKE ' . $escapedFilter;
				$or[] = $db->qn('s.UserIp') . ' LIKE ' . $escapedFilter;

				if ($isStaticHeader)
				{
					$or[] = $db->qn('sv.FieldValue') . ' LIKE ' . $escapedFilter;
				}

				else
				{
					$subquery = $db->getQuery(true)
						->select($db->qn('SubmissionId'))
						->from($db->qn('#__rsform_submission_values'))
						->where($db->qn('FormId') . ' = ' . $db->q($formId))
						->where($db->qn('FieldValue') . ' LIKE ' . $escapedFilter);

					$or[] = $db->qn('s.SubmissionId') . ' IN (' . $subquery . ')';
				}

				$query->where('(' . implode(' OR ', $or) . ')');
			}

			if ($languageFilter)
			{
				$query->where($db->qn('s.Lang') . ' = ' . $db->q($languageFilter));
			}

			if ($from = $this->getDateFrom())
			{
				$query->where($db->qn('s.DateSubmitted') . ' >= ' . $db->q(Factory::getDate($from)->toSql()));
			}

			if ($to = $this->getDateTo())
			{
				$query->where($db->qn('s.DateSubmitted') . ' <= ' . $db->q(Factory::getDate($to)->toSql()));
			}
		}

		// Order by static headers
		if ($isStaticHeader)
		{
			$query->order($db->qn('s.' . $sortColumn) . ' ' . $db->escape($sortOrder));
		}
		else
		{
			$join = true;

			if ($this->isOrderingPossible($sortColumn))
			{
				$query->where($db->qn('sv.FieldName') . ' = ' . $db->q($sortColumn));
			}

			$query->order($db->qn('sv.FieldValue') . ' ' . $db->escape($sortOrder));
		}

		if ($join)
		{
			$query->join('left', $db->qn('#__rsform_submission_values', 'sv') . ' ON (' . $db->qn('s.SubmissionId') . ' = ' . $db->qn('sv.SubmissionId') . ')')
				->group(array($db->qn('s.SubmissionId')));
		}
		
		return $query;
	}
	
	public function isOrderingPossible($field)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('SubmissionValueId'))
			->from($db->qn('#__rsform_submission_values'))
			->where($db->qn('FieldName') . ' = ' . $db->q($field))
			->where($db->qn('FormId') . ' = ' . $db->q($this->getFormId()));

		$db->setQuery($query);
		return $db->loadResult();
	}
	
	public function getDateFrom()
	{
		return $this->getState('filter.dateFrom');
	}
	
	public function getDateTo()
	{
		return $this->getState('filter.dateTo');
	}

	public function getLang()
	{
		return $this->getState('filter.language');
	}

	public function getFilter()
	{
		return $this->getState('filter.search');
	}

	public function getSpecialFields()
	{
		return RSFormProHelper::getDirectoryFormProperties($this->getFormId(), true);
	}

	public function getFormProperties()
	{
		return RSFormProHelper::getForm($this->getFormId());
	}
	
	public function getSubmissions()
	{
		if (empty($this->_data))
		{
			$formId = $this->getFormId();
			$app	= Factory::getApplication();
			$db     = $this->getDbo();
			$form   = $this->getFormProperties();

			$multipleSeparator = $form->MultipleSeparator;
			if ($this->multipleSeparator !== null)
			{
				$multipleSeparator = $this->multipleSeparator;
			}
			$multipleSeparator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $multipleSeparator);

			if (empty($form))
			{
				return $this->_data;
			}
			
			$uploadFields 	= array();
			$multipleFields = array();
			$textareaFields = array();
			$fieldTypes = $this->getSpecialFields();
			if (isset($fieldTypes['uploadFields']))
			{
				$uploadFields = $fieldTypes['uploadFields'];	
			}
			if (isset($fieldTypes['multipleFields']))
			{
				$multipleFields = $fieldTypes['multipleFields'];	
			}
			if (isset($fieldTypes['textareaFields']))
			{
				$textareaFields = $fieldTypes['textareaFields'];	
			}
			
			$db->setQuery("SET SQL_BIG_SELECTS=1")->execute();
			
			$results = $this->_getList($this->getListQuery(), $this->getStart(), $this->getState('list.limit'));

			foreach ($results as $result)
			{
				$this->_data[$result->SubmissionId] = array(
					'SubmissionId'     => $result->SubmissionId,
					'FormId'           => $result->FormId,
					'DateSubmitted'    => RSFormProHelper::getDate($result->DateSubmitted),
					'UserIp'           => $result->UserIp,
					'Username'         => $result->Username,
					'UserId'           => $result->UserId,
					'Lang'             => $result->Lang,
					'confirmed'        => $result->confirmed ? Text::_('RSFP_YES') : Text::_('RSFP_NO'),
					'ConfirmedIp'      => $result->ConfirmedIp,
					'ConfirmedDate'    => $result->ConfirmedDate ? RSFormProHelper::getDate($result->ConfirmedDate) : '',
					'SubmissionValues' => array(),
				);
			}

			$submissionIds = array_keys($this->_data);
			
			if (!empty($submissionIds))
			{
				$must_escape = $app->input->get('view') == 'submissions' && $app->input->get('layout') == 'default';

				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__rsform_submission_values'))
					->where($db->qn('SubmissionId') . ' IN (' . implode(',', $db->q($submissionIds)) . ')');
				
				$results = $db->setQuery($query)->loadObjectList();
				foreach ($results as $result)
				{
					// Check if this is an upload field
					if (in_array($result->FieldName, $uploadFields) && !empty($result->FieldValue) && !$this->export)
					{
						$files = RSFormProHelper::explode($result->FieldValue);

						$values = array();
						foreach ($files as $file)
						{
							$values[] = '<a href="index.php?option=com_rsform&amp;task=submissions.viewfile&amp;id='.$result->SubmissionValueId.'&amp;file='.md5($file).'">'.RSFormProHelper::htmlEscape(basename($file)).'</a>';
						}

						$result->FieldValue = implode('<br />', $values);
					}
					else
					{
						// Check if this is a multiple field
						if (in_array($result->FieldName, $multipleFields) && $this->export)
						{
							$result->FieldValue = str_replace("\n", $multipleSeparator, $result->FieldValue);
						}
						// Transform new lines
						elseif ($form->TextareaNewLines && in_array($result->FieldName, $textareaFields))
						{
							if ($must_escape)
							{
								$result->FieldValue = RSFormProHelper::htmlEscape($result->FieldValue);
							}
							elseif ($this->exportType === 'csv' && !$this->stripLines)
							{
								$result->FieldValue = nl2br($result->FieldValue);
							}
						}
						elseif ($must_escape)
						{
							$result->FieldValue = RSFormProHelper::htmlEscape($result->FieldValue);
						}
					}
						
					$this->_data[$result->SubmissionId]['SubmissionValues'][$result->FieldName] = array(
						'Value' => $result->FieldValue,
						'Id'    => $result->SubmissionValueId
					);
				}
				
				Factory::getApplication()->triggerEvent('onRsformBackendManageSubmissions', array(array(
                    'formId'   		=> $formId,
                    'submissions' 	=> &$this->_data,
                    'export'  		=> $this->export,
                    'escape'  		=> $must_escape,
                )));
			}
			unset($results);
		}
		
		return $this->_data;
	}
	
	public function getSubmission()
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsform_submissions'))
			->where($db->qn('SubmissionId') . ' = ' . $db->q($this->getSubmissionId()));

		return $db->setQuery($query)->loadObject();
	}

	protected function getSkippedFields()
	{
		$skippedFields = array(RSFORM_FIELD_BUTTON, RSFORM_FIELD_CAPTCHA, RSFORM_FIELD_HASHCASH, RSFORM_FIELD_PREVIEW, RSFORM_FIELD_FREETEXT, RSFORM_FIELD_SUBMITBUTTON);
		$skippedFields = array_merge($skippedFields, RSFormProHelper::$captchaFields);

		Factory::getApplication()->triggerEvent('onRsformBackendGetSkippedFields', array(&$skippedFields));

		return $skippedFields;
	}
	
	public function getHeaders()
	{
		$db     = Factory::getDbo();
		$formId = $this->getFormId();

		$query = $db->getQuery(true)
			->select($db->qn('p.PropertyValue'))
			->from($db->qn('#__rsform_components', 'c'))
			->join('left', $db->qn('#__rsform_properties', 'p') . ' ON (' . $db->qn('c.ComponentId') . '=' . $db->qn('p.ComponentId') . ' AND ' . $db->qn('p.PropertyName') . ' = ' . $db->q('NAME') . ')')
			->join('left', $db->qn('#__rsform_component_types', 'ct') . ' ON (' . $db->qn('c.ComponentTypeId') . '=' . $db->qn('ct.ComponentTypeId') . ')')
			->where($db->qn('c.FormId') . ' = ' . $db->q($formId))
			->where($db->qn('c.Published') . ' = ' . $db->q(1));

		$query->where($db->qn('ct.ComponentTypeId') . ' NOT IN (' . implode(',', $db->q($this->getSkippedFields())) . ')');

		$query->order($db->qn('c.Order'));

		$headers = $db->setQuery($query)->loadColumn();

        Factory::getApplication()->triggerEvent('onRsformBackendGetSubmissionHeaders', array(&$headers, $formId));

        // Get labels
		$results = array();
		if ($headers)
		{
			foreach ($headers as $header)
			{
				$value = $header;

				Factory::getApplication()->triggerEvent('onRsformBackendGetHeaderLabel', array(&$header, $formId));

				$results[$value] = new RSFormProSubmissionHeader($value, $header, 0, $formId);
			}
		}

		return $results;
	}
	
	public function getUnescapedFields()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('p.PropertyValue'))
			->from($db->qn('#__rsform_components', 'c'))
			->join('left', $db->qn('#__rsform_properties', 'p') . ' ON (' . $db->qn('c.ComponentId') . '=' . $db->qn('p.ComponentId') . ')')
			->where($db->qn('c.FormId') . ' = ' . $db->q($this->getFormId()))
			->where($db->qn('c.ComponentTypeId') . ' = ' . $db->q(RSFORM_FIELD_FILEUPLOAD))
			->where($db->qn('p.PropertyName') . ' = ' . $db->q('NAME'));
		$fields = $db->setQuery($query)->loadColumn();
		
		Factory::getApplication()->triggerEvent('onRsformBackendManageSubmissionsCreateUnescapedFields', array(array(
            'formId'    => $this->getFormId(),
            'fields'    => &$fields
        )));

        return $fields;
	}
	
	public function getStaticHeaders()
	{
		$headers = array('SubmissionId', 'DateSubmitted', 'UserIp', 'Username', 'UserId', 'Lang');

		if ($this->addConfirmedHeader())
		{
			$headers[] = 'confirmed';
			$headers[] = 'ConfirmedIp';
			$headers[] = 'ConfirmedDate';
		}

		$results = array();

		foreach ($headers as $header)
		{
			$results[$header] = new RSFormProSubmissionHeader($header, Text::_('RSFP_' . $header), 1, $this->getFormId());
		}
		
		return $results;
	}
	
	public function addConfirmedHeader()
	{
		if ($form = $this->getFormProperties())
		{
			return (bool) $form->ConfirmSubmission;
		}

		return false;
	}
	
	public function getFormTitle()
	{
		$formId = $this->getFormId();
		$db     = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('FormTitle'))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));
		$title = $db->setQuery($query)->loadResult();

		if ($translations = RSFormProHelper::getTranslations('forms', $formId, RSFormProHelper::getCurrentLanguage($formId)))
		{
			if (isset($translations['FormTitle']))
			{
				$title = $translations['FormTitle'];
			}
		}

		return $title;
	}

	public function getFormMultipleSeparator()
	{
		$formId = $this->getFormId();
		$db     = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('MultipleSeparator'))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));
		return $db->setQuery($query)->loadResult();
	}
	
	public function getForms()
	{
		$mainframe 	= Factory::getApplication();
		$db        	= Factory::getDbo();
		$sortColumn = $mainframe->getUserState('com_rsform.forms.filter_order', 'FormId');
		$sortOrder  = $mainframe->getUserState('com_rsform.forms.filter_order_Dir', 'ASC');
		$return 	= array();

        $query = $db->getQuery(true)
            ->select($db->qn('FormId'))
            ->select($db->qn('FormTitle'))
            ->select($db->qn('Lang'))
            ->from($db->qn('#__rsform_forms'))
            ->order($db->qn($sortColumn) . ' ' . $db->escape($sortOrder));
        if ($results = $db->setQuery($query)->loadObjectList())
        {
            foreach ($results as $result)
            {
                $lang = RSFormProHelper::getCurrentLanguage($result->FormId);
                if ($lang != $result->Lang)
                {
                    if ($translations = RSFormProHelper::getTranslations('forms', $result->FormId, $lang))
                    {
                        foreach ($translations as $field => $value)
                        {
                            if (isset($result->{$field}))
                            {
                                $result->{$field} = $value;
                            }
                        }
                    }
                }

                $return[] = HTMLHelper::_('select.option', $result->FormId, $result->FormTitle);
                $this->allFormIds[] = $result->FormId;
            }

            if (!empty($results[0]->FormId))
			{
				$this->firstFormId = $results[0]->FormId;
			}
        }
		
		return $return;
	}
	
	public function getSortColumn()
	{
		return $this->getState('list.ordering', 'DateSubmitted');
	}
	
	public function getSortOrder()
	{
		return $this->getState('list.direction', 'desc');
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// We do this here because it overrides our setUserState() below
		parent::populateState('DateSubmitted', 'desc');

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$app = Factory::getApplication();
		$offset = $app->get('offset');

		foreach (array('dateFrom', 'dateTo') as $date)
		{
			$value = $this->getUserStateFromRequest($this->context . '.filter.' . $date, 'filter_' . $date, '', 'string');

			if (strlen($value))
			{
				// Test if date is valid
				try
				{
					$value = Factory::getDate($value, $offset)->toSql();
				}
				catch (Exception $e)
				{
					$app->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');

					// Reset the value
					$value = '';
				}
			}

			$this->setState('filter.' . $date, $value);
			$app->setUserState($this->context . '.filter.' . $date, $value);
		}
	}
	
	public function getFormId()
	{
		$mainframe = Factory::getApplication();
		
		if (empty($this->firstFormId))
		{
			$this->getForms();
		}

		$formId = $mainframe->getUserStateFromRequest('com_rsform.submissions.formId', 'formId', $this->firstFormId, 'int');
		if ($formId && !in_array($formId, $this->allFormIds))
		{
			$formId = $this->firstFormId;
			$mainframe->setUserState('com_rsform.submissions.formId', $formId);
		}
		
		return $formId;
	}
	
	public function getSubmissionId()
	{
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');

		if (is_array($cid))
		{
			$cid = (int) reset($cid);
		}
		else
		{
			$cid = (int) $cid;
		}
		
		return $cid;
	}
	
	public function getEditFields()
	{
        $mainframe  = Factory::getApplication();
        $db         = $this->getDbo();
		$isPDF      = $mainframe->input->get('format') == 'pdf';
		$cid        = $this->getSubmissionId();
		$return     = array();

        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
        $submission = RSFormProSubmissionsHelper::getSubmission($cid);
		
		if (empty($submission))
		{
			$mainframe->redirect('index.php?option=com_rsform&view=submissions');
			return $return;
		}
		
		if ($isPDF)
		{
		    $query = $db->getQuery(true)
                ->select($db->qn('MultipleSeparator'))
                ->select($db->qn('TextareaNewLines'))
                ->from($db->qn('#__rsform_forms'))
                ->where($db->qn('FormId') . ' = ' . $db->q($submission->FormId));
			$form = $db->setQuery($query)->loadObject();

			$form->MultipleSeparator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $form->MultipleSeparator);
		}

        $query = $db->getQuery(true);
        $query->select($db->qn('p.PropertyValue'))
            ->select($db->qn('ct.ComponentTypeName'))
            ->select($db->qn('c.ComponentId'))
            ->from($db->qn('#__rsform_components','c'))
            ->join('left', $db->qn('#__rsform_properties','p').' ON '.$db->qn('p.ComponentId').' = '.$db->qn('c.ComponentId'))
            ->join('left', $db->qn('#__rsform_component_types','ct').' ON '.$db->qn('c.ComponentTypeId').' = '.$db->qn('ct.ComponentTypeId'))
            ->where($db->qn('c.FormId') . ' = ' . $db->q($submission->FormId))
            ->where($db->qn('p.PropertyName') . ' = ' . $db->q('NAME'))
            ->where($db->qn('c.Published') . ' = ' . $db->q(1))
            ->order($db->qn('c.Order') . ' ' . $db->escape('asc'));

        // Skip some fields
		$query->where($db->qn('ct.ComponentTypeId') . ' NOT IN (' . implode(',', $db->q($this->getSkippedFields())) . ')');

        $fields = $db->setQuery($query)->loadObjectList();

		if (empty($fields))
        {
            return $return;
        }

		$componentIds = array();
		foreach ($fields as $field)
        {
            $componentIds[] = $field->ComponentId;
        }

		$properties = RSFormProHelper::getComponentProperties($componentIds);

		foreach ($fields as $field)
		{
			$data = $properties[$field->ComponentId];
            $name = $field->PropertyValue;
			
			$new_field = array();
			$new_field[0] = $name;
			$new_field[3] = $name;

			$value = isset($submission->values[$field->PropertyValue]) ? $submission->values[$field->PropertyValue] : '';
			
			switch ($field->ComponentTypeName)
			{
				// skip this field for now, no need to edit it
				case 'freeText':
					continue 2;
				break;
				
				default:
					if ($isPDF)
					{
						$new_field[1] = RSFormProHelper::htmlEscape($value);
					}
					else
					{
						if (strpos($value, "\n") !== false || strpos($value, "\r") !== false)
						{
							$new_field[1] = '<textarea style="width: 95%" class="rs_textarea" rows="10" cols="60" name="form['.$name.']">'.RSFormProHelper::htmlEscape($value).'</textarea>';
						}
						else
						{
							$new_field[1] = '<input class="rs_inp rs_80" size="105" type="text" name="form['.$name.']" value="'.RSFormProHelper::htmlEscape($value).'" />';
						}
					}
				break;
				
				case 'textArea':
					if ($isPDF)
					{
						if ($form->TextareaNewLines && (!isset($data['WYSIWYG']) || $data['WYSIWYG'] == 'NO'))
                        {
                            $value = nl2br(RSFormProHelper::htmlEscape($value));
                        }

						$new_field[1] = $value;
					}
					elseif (isset($data['WYSIWYG']) && $data['WYSIWYG'] == 'YES')
					{
						$new_field[1] = RSFormProHelper::WYSIWYG('form['.$name.']', RSFormProHelper::htmlEscape($value), '', 600, 100, 60, 10);
					}
					else
					{
						$new_field[1] = '<textarea style="width: 95%" class="rs_textarea" rows="10" cols="60" name="form['.$name.']">'.RSFormProHelper::htmlEscape($value).'</textarea>';
					}
				break;
				
				case 'radioGroup':
				case 'checkboxGroup':
				case 'selectList':
					if ($isPDF)
					{
						$new_field[1] = str_replace("\n", $form->MultipleSeparator, $value);
						break;
					}

					$options = array();
					
					if ($field->ComponentTypeName == 'radioGroup')
					{
						$data['SIZE'] = 0;
						$data['MULTIPLE'] = 'NO';
						$options[] = HTMLHelper::_('select.option', '', Text::_('COM_RSFORM_NO_VALUE'));
					}
					elseif ($field->ComponentTypeName == 'checkboxGroup')
					{
						$data['SIZE'] = 5;
						$data['MULTIPLE'] = 'YES';
					}
					
					$value = RSFormProHelper::explode($value);

                    require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fielditem.php';
                    require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fieldmultiple.php';
                    $f = new RSFormProFieldMultiple(array(
                        'formId' 			=> $submission->FormId,
                        'componentId' 		=> $field->ComponentId,
                        'data' 				=> $data,
                        'value' 			=> array('formId' => $submission->FormId, $data['NAME'] => $value),
                        'invalid' 			=> false
                    ));

                    if ($items = $f->getItems())
                    {
                        foreach ($items as $item)
                        {
                            $item = new RSFormProFieldItem($item);

                            if ($item->flags['optgroup'])
                            {
                                $options[] = HTMLHelper::_('select.option', '<OPTGROUP>', $item->label, 'value', 'text');
                            }
                            elseif ($item->flags['/optgroup'])
							{
                                $options[] = HTMLHelper::_('select.option', '</OPTGROUP>', $item->label, 'value', 'text');
                            }
                            else
                            {
                                $options[] = HTMLHelper::_('select.option', $item->value, $item->label, 'value', 'text');
                            }
                        }
                    }

                    $attribs = array();

                    if ((int) $data['SIZE'] > 0)
                    {
                        $attribs[] = 'size="'.(int) $data['SIZE'].'"';
                    }

                    if ($data['MULTIPLE'] == 'YES')
                    {
                        $attribs[] = 'multiple="multiple"';
                    }

                    $attribs = implode(' ', $attribs);
					
					$new_field[1] = HTMLHelper::_('select.genericlist', $options, 'form['.$name.'][]', $attribs, 'value', 'text', $value);
				break;
				
				case 'fileUpload':
					if ($isPDF)
					{
						if (!empty($data['FILESSEPARATOR']))
						{
							$separator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $data['FILESSEPARATOR']);
						}
						else
						{
							$separator = '<br />';
						}

						$new_field[1] = implode($separator, RSFormProHelper::explode($value));

						break;
					}

					if ($value)
					{
						$files = RSFormProHelper::explode($value);
					}
					else
					{
						$files = array();
					}

					$new_field[1] = '<ul class="rsfp-multiupload-list">';

					foreach ($files as $file)
					{
						$new_field[1] .= '<li><button type="button" class="btn btn-secondary btn-mini btn-sm" onclick="RSFormPro.removeFile(this);">' . Text::_('COM_RSFORM_REMOVE_FILE') . '</button> <input type="hidden" name="form[' . $data['NAME'] . '][]" value="' . RSFormProHelper::htmlEscape($file) . '" />' . RSFormProHelper::htmlEscape(basename($file)) . '</li>';
					}

					$multiple =  !empty($data['MULTIPLE']) && $data['MULTIPLE'] == 'YES';
					$new_field[1] .= '</ul>';

					$new_field[1] .= '<input size="45" type="file" name="upload['.$name.']' . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . ' />';


				break;
			}
			
			$return[] = $new_field;
		}

        $mainframe->triggerEvent('onRsformBackendGetEditFields', array(&$return, $submission));
		
		return $return;
	}
	
	public function save()
	{
		$app	= Factory::getApplication();
		$db     = $this->getDbo();
        $formId = $app->input->getInt('formId');
		$cid    = $this->getSubmissionId();
		$form   = $app->input->post->get('form', array(), 'array');
        $static = $app->input->post->get('formStatic', array(), 'array');
        $files  = $app->input->files->get('upload', array(), 'array');

		$static['DateSubmitted'] = Factory::getDate($static['DateSubmitted'], Factory::getApplication()->get('offset'))->toSql();
		if (isset($static['ConfirmedDate']))
		{
			$static['ConfirmedDate'] = Factory::getDate($static['ConfirmedDate'], Factory::getApplication()->get('offset'))->toSql();
		}
		else
		{
			$static['ConfirmedDate'] = null;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		$submission = RSFormProSubmissionsHelper::getSubmission($cid);

		// Check if submission exists
		if (!$submission)
		{
			return false;
		}

		// Handle file uploads first
		if (!empty($files))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fileupload.php';

            foreach ($files as $field => $file)
            {
				if ($componentId = RSFormProHelper::getComponentId($field, $formId))
				{
					$data = RSFormProHelper::getComponentProperties($componentId);

					// If we've cleared all files
					if (!isset($form[$field]))
					{
						$form[$field] = array();
					}

					$valueSent = (array) $form[$field];

					if (isset($submission->values[$field]))
					{
						$originals = RSFormProHelper::explode($submission->values[$field]);

						foreach ($originals as $k => $original)
						{
							// File has been removed from list, remove the original file to save up space
							if (!in_array($original, $valueSent) && file_exists($original) && is_file($original))
							{
								try
								{
									File::delete($original);
								}
								catch (Exception $e)
								{
									$app->enqueueMessage($e->getMessage(), 'error');
								}
							}
						}
					}

					$f = new RSFormProFieldFileUpload(array(
						'formId' 		=> $formId,
						'componentId' 	=> $componentId,
						'data' 			=> $data,
					));

					if ($object = $f->processBeforeStore($submission->SubmissionId, $form, $files, false))
					{
						if (!is_array($form[$field]))
						{
							$form[$field] = (array) $form[$field];
						}

						if (!empty($data['MULTIPLE']) && $data['MULTIPLE'] === 'YES')
						{
							$form[$field] = array_merge($form[$field], RSFormProHelper::explode($object->FieldValue));
						}
						else
						{
							$form[$field] = RSFormProHelper::explode($object->FieldValue);
						}
					}
				}
            }
        }

        // Static submission data
        if ($static)
        {
            $object = new stdClass();
			$object->SubmissionId = $submission->SubmissionId;
            foreach ($static as $field => $value)
            {
				$object->{$field} = $value;
            }

            $db->updateObject('#__rsform_submissions', $object, array('SubmissionId'), true);
        }

		// Checkboxes and other empty fields don't send a value, so just make sure we have them all here
        if ($fields = $this->getEditFields())
        {
        	foreach ($fields as $field)
	        {
	        	if (!isset($form[$field[0]]))
		        {
		        	$form[$field[0]] = '';
		        }
	        }
        }

		// Update dynamic fields
		foreach ($form as $field => $value)
		{
			if (is_array($value))
            {
                $value = implode("\n", $value);
            }

			$object = (object) array(
				'FormId'        => $formId,
				'SubmissionId'  => $submission->SubmissionId,
				'FieldName'     => $field,
				'FieldValue'    => $value
			);

			if (!isset($submission->values[$field]))
			{
			    $db->insertObject('#__rsform_submission_values', $object);
			}
			elseif ($submission->values[$field] !== $value)
			{
				// Update only if we've changed something
				$db->updateObject('#__rsform_submission_values', $object, array('SubmissionId', 'FormId', 'FieldName'));
			}
		}

		$app->triggerEvent('onRsformSubmissionSave', array('formId' => $formId, 'submissionId' => $submission->SubmissionId));
	}
	
	public function getSubmissionFormId()
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('FormId'))
			->from($db->qn('#__rsform_submissions'))
			->where($db->qn('SubmissionId') . ' = ' . $db->q($this->getSubmissionId()));

		return $db->setQuery($query)->loadResult();
	}
	
	public function getExportSelected()
	{
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$cid = array_map('intval', $cid);
		
		return $cid;
	}
	
	public function getExportFile()
	{
		return uniqid('');
	}
	
	public function getStaticFields()
	{
		$submissionid = $this->getSubmissionId();
		$db           = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsform_submissions'))
			->where($db->qn('SubmissionId') . ' = ' . $db->q($submissionid));
		
		$db->setQuery($query);
		$submission = $db->loadObject();
		
		if ($submission)
		{
			$submission->DateSubmitted = HTMLHelper::_('date', $submission->DateSubmitted, 'Y-m-d H:i:s');
			if ($submission->ConfirmedDate)
			{
				$submission->ConfirmedDate = HTMLHelper::_('date', $submission->ConfirmedDate, 'Y-m-d H:i:s');
			}
		}
		
		return $submission;
	}
	
	public function getExportType()
	{
		$task = Factory::getApplication()->input->getCmd('task');
		$task = explode('.', $task);
		return end($task);
	}
	
	public function getExportTotal()
	{
		$rows   = Factory::getApplication()->input->get('ExportRows', '', 'string');

		switch ($rows)
		{
			case '0':
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('COUNT(' . $db->qn('SubmissionId') . ')')
					->from($db->qn('#__rsform_submissions'))
					->where($db->qn('FormId') . ' = ' . $db->q($this->getFormId()));
				$db->setQuery($query);
				return $db->loadResult();
				break;

			case '-1':
				return $this->getTotal();
				break;

			default:
				return count(explode(',', $rows));
				break;
		}
	}

	public function getImportTotal()
    {
        $app    = Factory::getApplication();
        $file   = $app->get('tmp_path') . '/' . md5($app->get('secret'));

        return file_exists($file) ? filesize($file) : 0;
    }
	
	public function getLanguages()
	{
		$languages 	= LanguageHelper::getKnownLanguages(JPATH_SITE);
		$return 	= array();

		$return[] = HTMLHelper::_('select.option', '', Text::_('RSFP_SUBMISSIONS_ALL_LANGUAGES'));
		foreach ($languages as $tag => $properties)
		{
			$return[] = HTMLHelper::_('select.option', $tag, $properties['name']);
		}

		return $return;
	}

	public function getPreviewImportData()
    {
        $session    = Factory::getSession();
        $app        = Factory::getApplication();
        $file       = $app->get('tmp_path') . '/' . md5($app->get('secret'));
        $options    = $session->get('com_rsform.import.options', array());

        $skipHeaders    = !empty($options['skipHeaders']);
        $delimiter      = empty($options['delimiter']) ? ',' : $options['delimiter'];
        $enclosure      = empty($options['enclosure']) ? '"' : $options['enclosure'];
        $lines          = array();

        if (version_compare(PHP_VERSION, '8.1', '<'))
        {
	        ini_set('auto_detect_line_endings', true);
        }

        setlocale(LC_ALL, 'en_US.UTF-8');

		if (!file_exists($file))
		{
			$app->enqueueMessage(Text::sprintf('COM_RSFORM_IMPORT_NO_FILE_FOUND', $file), 'error');
			return array();
		}

		$this->previewHeaders = array();
		$h = fopen($file, 'r');
        if (is_resource($h))
        {
            for ($i = 0; $i < 5; $i++)
            {
                $data = fgetcsv($h, 0, $delimiter, $enclosure, '');

                if ($data !== false)
                {
                    if ($i == 0)
                    {
						$this->previewHeaders = $data;

						if ($skipHeaders)
						{
							continue;
						}
                    }
                    $lines[] = $data;
                }
                else
                {
                    break;
                }
            }
            fclose($h);
        }

        return $lines;
    }

	public function getPreviewSelectedData()
	{
		return $this->previewHeaders;
	}

    public function confirm($formId, $cid)
	{
		$db         = Factory::getDbo();
		$app        = Factory::getApplication();
		$form       = RSFormProHelper::getForm($formId);
		$ipAddress  = $form->KeepIP ? IpHelper::getIp() : '0.0.0.0';
		$query      = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsform_submissions'))
			->where($db->qn('SubmissionId') . ' IN (' . implode(',', $db->q($cid)) . ')');

		if ($submissions = $db->setQuery($query)->loadObjectList())
		{
			foreach ($submissions as $submission)
			{
				$SubmissionId   = $submission->SubmissionId;
				$hash           = md5($submission->SubmissionId . $formId . $submission->DateSubmitted);

				$query->clear()
					->update($db->qn('#__rsform_submissions'))
					->set($db->qn('confirmed') . ' = ' . $db->q(1))
					->set($db->qn('ConfirmedDate') . ' = ' . $db->q(Factory::getDate()->toSql()))
					->set($db->qn('ConfirmedIp') . ' = ' . $db->q($ipAddress))
					->where($db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId));
				$db->setQuery($query);
				$db->execute();

				$app->triggerEvent('onRsformFrontendSubmissionConfirmation', array(array('SubmissionId' => $SubmissionId, 'hash' => $hash)));

				if ($form->ConfirmSubmission && !empty($form->ConfirmSubmissionDefer) && json_decode($form->ConfirmSubmissionDefer))
				{
					RSFormProHelper::sendSubmissionEmails($SubmissionId);
				}
			}
		}
	}

	public function saveColumns($formId, $staticcolumns, $columns)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->delete($db->qn('#__rsform_submission_columns'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));
		$db->setQuery($query)->execute();

		if ($staticcolumns || $columns)
		{
			$query->clear();
			$query->insert($db->qn('#__rsform_submission_columns'))
				->columns($db->qn(array('FormId', 'ColumnName', 'ColumnStatic')));

			if ($staticcolumns)
			{
				foreach ($staticcolumns as $column)
				{
					$query->values(implode(',', array($db->q($formId), $db->q($column), $db->q(1))));
				}
			}

			if ($columns)
			{
				foreach ($columns as $column)
				{
					$query->values(implode(',', array($db->q($formId), $db->q($column), $db->q(0))));
				}
			}

			$db->setQuery($query)->execute();
		}
	}
}

class RSFormProSubmissionHeader
{
	/* @var string Holds the actual value of the header */
	public $value;

	/* @var string Holds the label (displayed) value of the header */
	public $label;

	/* @var int 0 - form field, 1 - static submission header */
	public $static;

	/* @var int Checks if this header is shown in the submissions list */
	public $enabled;

	public function __construct($value, $label, $static = 0, $formId = 0)
	{
		$this->value = $value;
		$this->label = $label;
		$this->static = $static;

		if ($formId)
		{
			$this->enabled = $this->isHeaderEnabled($formId);
		}
	}

	public function __toString()
	{
		return $this->value;
	}

	protected function isHeaderEnabled($formId)
	{
		static $cache = array();

		if (!isset($cache[$formId]))
		{
			$cache[$formId] = (object) array(
				'staticHeaders' => array(),
				'headers'       => array()
			);

			$db = Factory::getDbo();

			$query = $db->getQuery(true)
				->select($db->qn('ColumnName'))
				->select($db->qn('ColumnStatic'))
				->from($db->qn('#__rsform_submission_columns'))
				->where($db->qn('FormId') . ' = ' . $db->q($formId));

			if ($results = $db->setQuery($query)->loadObjectList())
			{
				foreach ($results as $result)
				{
					if ($result->ColumnStatic)
					{
						$cache[$formId]->staticHeaders[] = $result->ColumnName;
					}
					else
					{
						$cache[$formId]->headers[] = $result->ColumnName;
					}
				}
			}
		}

		$array = $this->static ? 'staticHeaders' : 'headers';

		if (empty($cache[$formId]->headers) && empty($cache[$formId]->staticHeaders))
		{
			return true;
		}

		return in_array($this->value, $cache[$formId]->{$array});
	}
}