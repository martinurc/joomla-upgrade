<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\OutputFilter;

define('RSFORM_DIR_SORT_NUMBER', 1);
define('RSFORM_DIR_SORT_DATE', 2);

class RsformModelDirectory extends BaseDatabaseModel
{
	protected $fields;
	protected $needsSelect = array();
	protected $_app;

	/* @var $params Joomla\Registry\Registry */
    public $params;

    protected $validation;

    public function __construct($config = array())
    {
	    // Need to set database before calling isValid()
	    parent::__construct($config);

        $this->_app     = Factory::getApplication();
        $this->params   = $this->_app->getParams('com_rsform');
        $this->itemid   = $this->getItemid();
        $this->context  = 'com_rsform.directory' . $this->itemid;

	    // Check for a valid form
	    $this->isValid();

	    $this->getFields();
    }

    /**
     * Check if we are allowed to show content
	 *
	 * @throws Exception
	 *
     */
    public function isValid()
    {
        if (!$this->params->get('enable_directory', 0))
        {
            throw new Exception(Text::_('RSFP_VIEW_DIRECTORY_NOT_ENABLED_FORGOT'));
        }

        // Do we have a valid formId
        $formId = $this->params->get('formId', 0);
        if (empty($formId))
        {
			throw new Exception(Text::sprintf('RSFP_VIEW_DIRECTORY_NO_VALID_FORMID', $formId));
        }

        // Check if the directory exists
	    $db = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('formId'))
			->from($db->qn('#__rsform_directory'))
			->where($db->qn('formId') . ' = ' . $db->q($formId));

        if (!$db->setQuery($query)->loadResult())
        {
			throw new Exception(Text::_('RSFP_VIEW_DIRECTORY_NOT_SAVED_YET'));
        }

        return true;
    }

    /**
     *    Get directory fields
     */
    public function getFields()
    {
        if (!is_array($this->fields))
        {
            $this->fields = RSFormProHelper::getDirectoryFields($this->params->get('formId'));
        }

        return $this->fields;
    }

    /**
     *    Submissions query
     */
    public function getListQuery()
    {
        // Get query
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Get headers
        $fields = $this->getFields();
        $headers = RSFormProHelper::getDirectoryStaticHeaders();

        // Check if it's a search.
        $search = $this->getSearch();

        // If 'Show Only Filtering Result' is enabled, don't create a query unless the user searches for something
        if ($this->params->get('show_filtering_result', 0) && !strlen($search))
		{
			return false;
		}

	    $order = $this->getListOrder();
	    $sortingField = false;

		if ($this->isValidField($order, true))
		{
			$this->needsSelect[] = $order;
		}

        // Get the SubmissionId
        $query->select($db->qn('s.SubmissionId'))
            ->from($db->qn('#__rsform_submission_values', 'sv'))
            ->join('left', $db->qn('#__rsform_submissions', 's') . ' ON (' . $db->qn('sv.SubmissionId') . '=' . $db->qn('s.SubmissionId') . ')')
            ->where($db->qn('s.FormId') . '=' . $db->q($this->params->get('formId')))
            ->group($db->qn('s.SubmissionId'))
            ->order($db->qn($order) . ' ' . $db->escape($this->getListDirn()));

        // Show only confirmed submissions?
        if ($this->params->get('show_confirmed', 0))
        {
            $query->where($db->qn('s.confirmed') . '=' . $db->q(1));
        }

        // Show only submissions for selected language
        if ($lang = $this->params->get('lang', ''))
        {
            $query->where($db->qn('s.Lang') . '=' . $db->q($lang));
        }

        if (!$this->params->get('show_all_submissions'))
		{
			// Check if we need to show only submissions related to UserId.
			$userId = $this->params->get('userId');

			if ($this->params->get('show_logged_in_submissions'))
			{
				// Get only logged in user's submissions
				$user = Factory::getUser();

				// Do not continue if he's a guest.
				if ($user->guest)
				{
					return false;
				}

				$query->where($db->qn('s.UserId') . '=' . $db->q($user->get('id')));
			}
			elseif ($userId)
			{
				// Show only the submissions of these users
				$userIds = explode(',', $userId);
				$userIds = array_map('intval', $userIds);

				if ($userIds)
				{
					$query->where($db->qn('s.UserId') . ' IN (' . implode(',', $userIds) . ')');
				}
			}
		}

        $needsSelect = $this->needsSelect;
	    $filters = $this->params->get('filter_values', array());
		if ($dynamicFilters = $this->getDynamicFilters())
		{
			$operators = $this->getDynamicFiltersOperators();
			if (!is_array($filters))
			{
				$filters = array();
			}

			if (!isset($filters['name']))
			{
				$filters['name'] = array();
			}

			if (!isset($filters['operator']))
			{
				$filters['operator'] = array();
			}

			if (!isset($filters['value']))
			{
				$filters['value'] = array();
			}

			if (!is_array($dynamicFilters))
			{
				$dynamicFilters = array();
			}

			foreach ($dynamicFilters as $fieldName => $fieldValue)
			{
				if ((is_string($fieldValue) && strlen($fieldValue)) || (is_array($fieldValue) && count($fieldValue)))
				{
					$filters['name'][] = $fieldName;
					$filters['operator'][] = isset($operators[$fieldName]) ? $operators[$fieldName] : 'is';
					$filters['value'][] = $fieldValue;
				}
			}
		}
		if ($filters)
		{
			$allHaving = array();
			$glue = $this->params->get('filter_glue', 'OR');

			if (is_array($filters) && !empty($filters['name']) && is_array($filters['name']))
			{
				$user = Factory::getUser();
				$global_placeholders = array(
					'{global:username}'  => $user->username,
					'{global:userid}'    => $user->id,
					'{global:useremail}' => $user->email,
					'{global:fullname}'  => $user->name,
					'{global:today}'     => Factory::getDate()->format('Y-m-d'),
					'{global:unixtoday}' => Factory::getDate()->toUnix()
				);

				for ($i = 0; $i < count($filters['name']); $i++)
				{
					$name = $filters['name'][$i];
					if (isset($filters['operator'][$i]))
					{
						$operator = $filters['operator'][$i];
					}
					else
					{
						continue;
					}

					if (isset($filters['value'][$i]))
					{
						$value = $filters['value'][$i];
						$value = str_replace(array_keys($global_placeholders), array_values($global_placeholders), $value);
					}
					else
					{
						continue;
					}

					if ($this->isValidField($name))
					{
						$needsSelect[] = $name;
						$having = $db->qn($name);

						switch ($operator)
						{
							default:
							case 'is':
								$having .= ' = ' . $db->q($value);
								break;

							case 'is_not':
								$having .= ' != ' . $db->q($value);
								break;

							case 'contains':
								$having .= ' LIKE ' . $db->q('%' . $db->escape($value, true) . '%', false);
								break;

							case 'contains_not':
								$having .= ' NOT LIKE ' . $db->q('%' . $db->escape($value, true) . '%', false);
								break;

							case 'starts':
								$having .= ' LIKE ' . $db->q($db->escape($value, true) . '%', false);
								break;

							case 'starts_not':
								$having .= ' NOT LIKE ' . $db->q($db->escape($value, true) . '%', false);
								break;

							case 'ends':
								$having .= ' LIKE ' . $db->q('%' . $db->escape($value, true), false);
								break;

							case 'ends_not':
								$having .= ' NOT LIKE ' . $db->q('%' . $db->escape($value, true), false);
								break;

							case 'greater_than':
								$having .= ' > ' . $db->q($value);
								break;

							case 'greater_or_equal':
								$having .= ' >= ' . $db->q($value);
								break;

							case 'less_than':
								$having .= ' < ' . $db->q($value);
								break;

							case 'less_or_equal':
								$having .= ' <= ' . $db->q($value);
								break;
						}

						$allHaving[] = $having;
					}
				}
			}

			if ($allHaving)
			{
				$query->having('(' . implode(' ' . $glue . ' ', $allHaving) . ')', 'AND');
			}
		}

	    // If 'Show Only Filtering Result' is enabled, don't create a query unless the user searches for something
	    if ($this->params->get('show_filtering_result', 0) && !strlen($search) && !$allHaving)
	    {
		    return false;
	    }

        // Iterate through fields to build the query
		if ($fields)
		{
			$allHaving = array();
			foreach ($fields as $field)
			{
				// If the field is viewable or searchable, we need to select() it.
				if ($field->viewable || $field->searchable || in_array($field->FieldName, $needsSelect))
				{
					if ($field->componentId < 0 && isset($headers[$field->componentId]))
					{
						// Static headers.
						// Select the value.
						if ($field->FieldName == 'confirmed')
						{
							// Make sure we display a text instead of 0 and 1.
							$query->select('IF(' . $db->qn('s.confirmed') . ' = ' . $db->q(1) . ', ' . $db->q(Text::_('RSFP_YES')) . ', ' . $db->q(Text::_('RSFP_NO')) . ') AS ' . $db->qn('confirmed'));
						}
						elseif ($field->FieldName !== 'SubmissionId')
						{
							$query->select($db->qn('s.' . $field->FieldName));
						}
					}
					else
					{
						// Dynamic headers.
						// Select the value.
						$query->select('GROUP_CONCAT(IF(' . $db->qn('sv.FieldName') . '=' . $db->q($field->FieldName) . ', ' . $db->qn('sv.FieldValue') . ', NULL)) AS ' . $db->qn($field->FieldName));
					}

					// If we're searching, add the field to the having() query.
					if ($search && $field->searchable)
					{
						// Datetime fields don't play well with LIKE
						if (in_array($field->FieldId, array(RSFORM_STATIC_DATESUBMITTED, RSFORM_STATIC_CONFIRMEDDATE)) && preg_match('#([^0-9\-: ])#', $search))
						{
							continue;
						}

						$allHaving[] = $db->qn($field->FieldName) . ' LIKE ' . $db->q('%' . $db->escape($search, true) . '%', false);
					}

					if ($field->viewable && $field->componentId > 0 && $order === $field->FieldName && $field->sort > 0)
					{
						$sortingField = $field;
					}
				}
			}

			if ($allHaving)
			{
				$query->having('(' . implode(' OR ', $allHaving) . ')', 'AND');
			}

			if ($sortingField)
			{
				switch ($sortingField->sort)
				{
					case RSFORM_DIR_SORT_NUMBER:
						$query->select('CAST(GROUP_CONCAT(IF(' . $db->qn('sv.FieldName') . '=' . $db->q($sortingField->FieldName) . ', ' . $db->qn('sv.FieldValue') . ', NULL)) AS DECIMAL(10,5)) AS ' . $db->qn($sortingField->FieldName . '_sort'))
							->clear('order')
							->order($db->qn($sortingField->FieldName . '_sort') . ' ' . $db->escape($this->getListDirn()));
						break;

					case RSFORM_DIR_SORT_DATE:
						$format = '';
						$properties = RSFormProHelper::getComponentProperties($sortingField->FieldId);

						switch ($sortingField->FieldType)
						{
							case RSFORM_FIELD_CALENDAR:
								$format = $this->translateDateFormat($properties['DATEFORMAT']);
								break;

							case RSFORM_FIELD_JQUERY_CALENDAR:
								$format = $properties['DATEFORMAT'];
								if ($properties['TIMEPICKER'] === 'YES')
								{
									$format .= ' ' . $properties['TIMEPICKERFORMAT'];
								}
								$format = $this->translateDateFormat($format);
								break;

							case RSFORM_FIELD_BIRTHDAY:
								$format = $properties['DATEORDERING'];
								if ($properties['SHOWDAY'] === 'NO')
								{
									$format = str_replace('D', '', $format);
								}
								if ($properties['SHOWMONTH'] === 'NO')
								{
									$format = str_replace('M', '', $format);
								}
								if ($properties['SHOWYEAR'] === 'NO')
								{
									$format = str_replace('Y', '', $format);
								}
								if ($properties['STORELEADINGZERO'] === 'YES')
								{
									$format = str_replace('D', 'd', $format);
									$format = str_replace('M', 'm', $format);
								}
								else
								{
									$format = str_replace('D', 'j', $format);
									$format = str_replace('M', 'n', $format);
								}
								$format = implode($properties['DATESEPARATOR'], str_split($format));
								$format = $this->translateDateFormat($format);
								break;
						}

						Factory::getApplication()->triggerEvent('onRsformDirectorySortDate', array(&$format, $sortingField, $properties, $this));

						if (!empty($format))
						{
							$query->select('STR_TO_DATE(GROUP_CONCAT(IF(' . $db->qn('sv.FieldName') . '=' . $db->q($sortingField->FieldName) . ', ' . $db->qn('sv.FieldValue') . ', NULL)), ' . $db->q($format) . ') AS ' . $db->qn($sortingField->FieldName . '_sort'))
								->clear('order')
								->order($db->qn($sortingField->FieldName . '_sort') . ' ' . $db->escape($this->getListDirn()));
						}
						break;
				}
			}
		}

		Factory::getApplication()->triggerEvent('onRsformAfterManageDirectoriesQueryCreated', array(&$query, $this->params->get('formId')));

        return $query;
    }

    protected function isValidField($name, $onlyDynamic = false)
	{
		static $fields;

		if ($fields === null)
		{
			$fields = array(
				'static'    => RSFormProHelper::getDirectoryStaticHeaders(),
				'dynamic'   => array()
			);

			if ($allFields = RSFormProHelper::getComponents($this->params->get('formId')))
			{
				foreach ($allFields as $field)
				{
					$fields['dynamic'][] = $field->name;
				}
			}
		}

		if ($onlyDynamic)
		{
			return in_array($name, $fields['dynamic']);
		}
		else
		{
			return in_array($name, $fields['static']) || in_array($name, $fields['dynamic']);
		}
	}

    public function setGroupConcatLimit()
    {
        $this->getDbo()->setQuery("SET SESSION `group_concat_max_len` = 1000000")->execute();
    }

	public function setNeedsSelect($downloadableFields)
	{
		if ($downloadableFields)
		{
			foreach ($downloadableFields as $downloadableField)
			{
				$this->needsSelect[] = $downloadableField->name;
			}
		}
	}

    /**
     *    Get Submissions
     */
    public function getItems()
    {
        $mainframe = Factory::getApplication();

        $this->setGroupConcatLimit();

        if ($query = $this->getListQuery())
        {
			try
			{
				$items = $this->getDbo()->setQuery($query, $this->getStart(), $this->getLimit())->loadObjectList();
			}
			catch (Exception $e)
			{
				$items = array();
			}
        }
        else
        {
            $items = array();
        }

        // small workaround - we need to have only string keys for the items
        foreach ($items as $i => $item)
        {
            $newItem = new stdClass();

            foreach ($item as $key => $value)
            {
                $newItem->{((string)$key)} = $value;
            }

            $items[$i] = $newItem;
        }

        $mainframe->triggerEvent('onRsformAfterManageDirectoriesQuery', array(&$items, $this->params->get('formId')));

        list($multipleSeparator, $uploadFields, $multipleFields, $textareaFields, $secret) = RSFormProHelper::getDirectoryFormProperties($this->params->get('formId'));

        $this->uploadFields = $uploadFields;
        $this->multipleFields = $multipleFields;

        if ($items)
        {
            foreach ($items as $item)
            {
                foreach ($uploadFields as $field)
                {
                    if (!empty($item->{$field}))
                    {
                    	$files = RSFormProHelper::explode($item->{$field});

                    	$values = array();
                    	foreach ($files as $file)
						{
							$values[] = '<a href="' . Route::_('index.php?option=com_rsform&task=submissions.viewfile&hash=' . md5($item->SubmissionId . $secret . $field) . '&file=' . md5($file)) . '">' . RSFormProHelper::htmlEscape(basename($file)) . '</a>';
						}
                    	
                        $item->{$field} = implode('<br />', $values);
                    }
                }

                foreach ($multipleFields as $field)
                {
                    if (isset($item->{$field}))
                    {
                        $item->{$field} = str_replace("\n", $multipleSeparator, RSFormProHelper::htmlEscape($item->{$field}));
                    }
                }
            }
        }

        return $items;
    }

    public function getAdditionalUnescaped()
    {
        $unescapedFields = array();

        Factory::getApplication()->triggerEvent('onRsformBackendManageDirectoriesCreateUnescapedFields', array(array('fields' => & $unescapedFields, 'formId' => $this->params->get('formId'))));

        return $unescapedFields;
    }

    /**
     *    Get directory details
     */
    public function getDirectory()
    {
        static $table;

        if (is_null($table))
        {
            $table = Table::getInstance('RSForm_Directory', 'Table');
            $table->load($this->params->get('formId', 0));
        }

        return $table;
    }

    public function getTemplate()
    {
        $cid 		= $this->_app->input->getInt('id', 0);
        $format 	= $this->_app->input->get('format');
        $user 		= Factory::getUser();
        $userId 	= $this->params->get('userId');
        $directory 	= $this->getDirectory();
        $template 	= $directory->ViewLayout;

        if (!$this->params->get('show_logged_in_submissions') && !$this->params->get('show_all_submissions'))
        {
            $userId = explode(',', $userId);
            $userId = array_map('intval', $userId);
        }

        // Grab submission
        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
        $submission = RSFormProSubmissionsHelper::getSubmission($cid, false);

        // Submission doesn't exist
        if (!$submission)
        {
            $this->_app->enqueueMessage(Text::sprintf('RSFP_SUBMISSION_DOES_NOT_EXIST', $cid), 'warning');
            return $this->_app->redirect(Route::_('index.php?option=com_rsform&view=directory', false));
        }

        // Submission doesn't belong to the configured form ID OR
        // can view only own submissions and not his own OR
        // can view only specified user IDs and this doesn't belong to any of the IDs
        if (($submission->FormId != $this->params->get('formId')) || ($this->params->get('show_logged_in_submissions') && $submission->UserId != $user->get('id')) || (is_array($userId) && !in_array($submission->UserId, $userId)))
        {
            $this->_app->enqueueMessage(Text::sprintf('RSFP_SUBMISSION_NOT_ALLOWED', $cid), 'warning');
            return $this->_app->redirect(Route::_('index.php?option=com_rsform&view=directory', false));
        }

        if ($this->params->get('show_confirmed', 0) && !$submission->confirmed)
        {
            $this->_app->enqueueMessage(Text::sprintf('RSFP_SUBMISSION_NOT_CONFIRMED', $cid), 'warning');
            return $this->_app->redirect(Route::_('index.php?option=com_rsform&view=directory', false));
        }

        $confirmed = $submission->confirmed ? Text::_('RSFP_YES') : Text::_('RSFP_NO');

        list($replace, $with) = RSFormProHelper::getReplacements($cid);
        $replace = array_merge($replace, array('{global:confirmed}', '{global:lang}'));
        $with = array_merge($with, array($confirmed, $submission->Lang));

        if ($format == 'pdf')
        {
            if (strpos($template, ':path}') !== false)
            {
                $template = str_replace(':path}', ':localpath}', $template);
            }

            $template = str_replace('{sitepath}', JPATH_SITE, $template);
        }
        else
        {
            $template = str_replace('{sitepath}', Uri::root(), $template);
        }

        if (strpos($template, '{/if}') !== false)
        {
            require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/scripting.php';
            RSFormProScripting::compile($template, $replace, $with);
        }

        $detailsLayout = str_replace($replace, $with, $template);
	    try
	    {
		    eval($directory->DetailsScript);
	    }
	    catch (Throwable $e)
	    {
		    Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
	    }

        // Set filename
        $directory->filename = str_replace($replace, $with, $directory->filename);

        return $detailsLayout;
    }

    public function delete($id)
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';

        RSFormProSubmissionsHelper::deleteSubmissions($id, true);
    }

	public function save()
	{
		$app        = Factory::getApplication();
		$db         = Factory::getDbo();
		$cid    	= $app->input->getInt('id');
		$formId     = $this->params->get('formId');
        $form       = $app->input->post->get('form', array(), 'array');
        $delete     = $app->input->post->get('delete', array(), 'array');
        $static     = $app->input->post->get('formStatic', array(), 'array');
        $files      = $app->input->files->get('form', array(), 'array');
		$validation = RSFormProHelper::validateForm($formId, 'directory', $cid);
		$directory  = $this->getDirectory();

		$this->validation =& $validation;

		if (!empty($validation))
		{
			return false;
		}

		$formFields 	= RSFormProHelper::getDirectoryFields($formId);
		$headers 		= RSFormProHelper::getDirectoryStaticHeaders();
		$staticFields   = array();
		$allowed		= array();
		$fieldProperties = array();

		foreach ($formFields as $field)
		{
			if ($field->editable)
			{
				if ($field->componentId < 0 && isset($headers[$field->componentId]))
				{
					$staticFields[] = $field->FieldName;
				}
				else
				{
					$fieldProperties[$field->FieldName] = $field;
					$allowed[] = $field->FieldName;
				}
			}
		}

		//Trigger Event - onBeforeDirectorySave
		$this->_app->triggerEvent('onRsformFrontendBeforeDirectorySave', array(array('SubmissionId' => &$cid, 'formId' => $formId, 'post' => &$form)));

		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		$submission = RSFormProSubmissionsHelper::getSubmission($cid);

		// Check if submission exists
		if (!$submission)
		{
			return false;
		}

		list($multipleSeparator, $uploadFields, $multipleFields, $textareaFields, $secret) = RSFormProHelper::getDirectoryFormProperties($formId);

		try
		{
			eval($directory->SaveScript);
		}
		catch (Throwable $e)
		{
			Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
		}

		// Handle file uploads first
		if ($allowedUploadFields = array_intersect($uploadFields, $allowed))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fileupload.php';

			foreach ($allowedUploadFields as $field)
			{
				if ($componentId = RSFormProHelper::getComponentId($field, $formId))
				{
					$data = RSFormProHelper::getComponentProperties($componentId);

					$f = new RSFormProFieldFileUpload(array(
						'formId' 		=> $formId,
						'componentId' 	=> $componentId,
						'data' 			=> $data,
					));

					$multiple = $f->getProperty('MULTIPLE', false);

					// If it's a multiple upload field, append new values by initializing the array with the values from the submission
					if (!empty($submission->values[$field]))
					{
						$form[$field] = RSFormProHelper::explode($submission->values[$field]);
					}
					else
					{
						$form[$field] = array();
					}

					// Remove requested values
					if (!empty($delete[$field]))
					{
						$f->removeHashedValues($form[$field], $delete[$field]);
					}

					// Upload has been successful.
					if ($object = $f->processBeforeStore($submission->SubmissionId, $form, $files, false))
					{
						// Merge new values, otherwise replace them
						if ($multiple)
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

		// Update fields
		if ($allowed)
		{
			foreach ($allowed as $field)
			{
				if (isset($fieldProperties[$field]) && $fieldProperties[$field]->FieldType === RSFORM_FIELD_SURVEYTABLE)
				{
					$type = 'surveyTable';
					$fieldTypeClass = 'RSFormProField' . $type;
					$fieldTypeFile  = JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/' . strtolower($type) . '.php';

					if (file_exists($fieldTypeFile))
					{
						// If class doesn't exist, load the file
						if (!class_exists($fieldTypeClass))
						{
							require_once $fieldTypeFile;
						}

						$config = array(
							'formId' => $formId,
							'componentId' => $fieldProperties[$field]->FieldId,
							'data' => RSFormProHelper::getComponentProperties($fieldProperties[$field]->FieldId)
						);

						// access the field class
						$fieldClass = new $fieldTypeClass($config);

						$fieldClass->processBeforeStore($cid, $form, $files, true);
					}
				}

				$value = isset($form[$field]) ? $form[$field] : '';
				if (is_array($value))
				{
					$value = implode("\n", $value);
				}
				$value = RSFormProHelper::stripJava($value);

				// Dynamic field - update value.
				$object = (object) array(
					'FormId' 		=> $formId,
					'SubmissionId' 	=> $cid,
					'FieldName'		=> $field,
					'FieldValue'	=> $value
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
		}

		$offset = Factory::getApplication()->get('offset');
		if ($static && $staticFields)
		{
			$object = new stdClass();
			$object->SubmissionId = $cid;

			// Static, update submission
			foreach ($staticFields as $field)
			{
				if (!isset($static[$field]))
				{
					$static[$field] = '';
				}

				if (in_array($field, array('DateSubmitted', 'ConfirmedDate')))
				{
					if ($static[$field])
					{
						try
						{
							$tmpDate = Factory::getDate($static[$field], $offset)->toSql();
							$static[$field] = $tmpDate;
						}
						catch (Exception $e)
						{
							$static[$field] = $field === 'ConfirmedDate' ? null : Factory::getDate()->toSql();
							$app->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
						}
					}
					else
					{
						$static[$field] = null;
					}
				}

				if ($static[$field] !== null)
				{
					$value = RSFormProHelper::stripJava($static[$field]);
				}
				else
				{
					$value = null;
				}

				$object->{$field} = $value;
			}

			$db->updateObject('#__rsform_submissions', $object, array('SubmissionId'), true);
		}

		// Send emails
		$this->sendEmails($formId, $cid);
		return true;
	}

	public function sendEmails($formId, $SubmissionId)
	{
		$directory = $this->getDirectory();
		$db        = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('Lang'))
			->from($db->qn('#__rsform_submissions'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId))
			->where($db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId));

		$lang = $db->setQuery($query)->loadResult();

		list($placeholders,$values) = RSFormProHelper::getReplacements($SubmissionId);

		$query->clear()
			->select('*')
			->from($db->qn('#__rsform_emails'))
			->where($db->qn('type') . ' = ' . $db->q('directory'))
			->where($db->qn('formId') . ' = ' . $db->q($formId))
			->where($db->qn('from') . ' != ' . $db->q(''));

		if ($emails = $db->setQuery($query)->loadObjectList())
		{
			$etranslations = RSFormProHelper::getTranslations('emails', $formId, $lang);

			foreach ($emails as $email)
			{
				foreach (array('fromname', 'subject', 'message', 'replytoname') as $value)
				{
					if (isset($etranslations[$email->id . '.' . $value]))
					{
						$email->{$value} = $etranslations[$email->id . '.' . $value];
					}
				}

				if (empty($email->fromname) || empty($email->subject) || empty($email->message))
				{
					continue;
				}

				$directoryEmail = array(
					'to' 			=> $email->to,
					'cc' 			=> $email->cc,
					'bcc' 			=> $email->bcc,
					'from' 			=> $email->from,
					'replyto' 		=> $email->replyto,
					'replytoName' 	=> $email->replytoname,
					'fromName' 		=> $email->fromname,
					'text' 			=> $email->message,
					'subject' 		=> $email->subject,
					'mode' 			=> $email->mode,
					'files' 		=> array()
				);

				try
				{
					eval($directory->EmailsCreatedScript);
				}
				catch (Throwable $e)
				{
					Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
				}
				
				// RSForm! Pro Scripting
				// performance check
				if (strpos($directoryEmail['text'], '{/if}') !== false)
				{
					require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/scripting.php';
					RSFormProScripting::compile($directoryEmail['text'], $placeholders, $values);
				}
				
				// We keep this separate, so we can use str_replace() below. This ensures we can keep backwards compatibility and edit ['files'] in the PHP script above.
				$directoryEmailFiles = $directoryEmail['files'];
				unset($directoryEmail['files']);

				// Replace placeholders
				$directoryEmail = str_replace($placeholders, $values, $directoryEmail);

				// Add the files
				$directoryEmail['files'] = $directoryEmailFiles;

				// additional cc
				if (strpos($directoryEmail['cc'], ',') !== false)
				{
					$directoryEmail['cc'] = explode(',', $directoryEmail['cc']);
				}

				// additional bcc
				if (strpos($directoryEmail['bcc'], ',') !== false)
				{
					$directoryEmail['bcc'] = explode(',', $directoryEmail['bcc']);
				}

				//Trigger Event - beforeDirectoryEmail
				$this->_app->triggerEvent('onRsformBeforeDirectoryEmail', array(array('directory' => &$directory, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'directoryEmail'=>&$directoryEmail)));

				try
				{
					eval($directory->EmailsScript);
				}
				catch (Throwable $e)
				{
					Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
				}

				// mail users
				$recipients = explode(',',$directoryEmail['to']);
				if (!empty($recipients))
				{
					foreach ($recipients as $recipient)
					{
						if (!empty($recipient))
						{
							RSFormProHelper::sendMail($directoryEmail['from'], $directoryEmail['fromName'], $recipient, $directoryEmail['subject'], $directoryEmail['text'], $directoryEmail['mode'], !empty($directoryEmail['cc']) ? $directoryEmail['cc'] : null, !empty($directoryEmail['bcc']) ? $directoryEmail['bcc'] : null, $directoryEmail['files'], !empty($directoryEmail['replyto']) ? $directoryEmail['replyto'] : '', !empty($directoryEmail['replytoName']) ? $directoryEmail['replytoName'] : null, '', $formId);
						}
					}
				}
			}
		}
	}

	public function getUploadFields()
	{
		return $this->uploadFields;
	}

	public function getMultipleFields()
	{
		return $this->multipleFields;
	}

	public function getTotal()
	{
		if ($query = $this->getListQuery())
		{
			$totalQuery = clone $query;

			$totalQuery->clear('order');

			if ($totalQuery->having !== null)
			{
				$cacheTime = (int) $this->params->get('total_cache', 0);
				if ($cacheTime > 0)
				{
					$cache = Factory::getCache('com_rsform_directory');
					$cache->setCaching(true);
					$cache->setLifeTime($cacheTime * 60);
					return $cache->get(array('RsformModelDirectory', 'getTotalCache'), array((string) $totalQuery));
				}
				else
				{
					return static::getTotalCache($totalQuery);
				}
			}

			$db = $this->getDbo();

			$totalQuery->clear('select')
				->select($db->qn('s.SubmissionId'));

			$db->setQuery($totalQuery)->execute();
			return $db->getNumRows();
		}

		return 0;
	}

	public static function getTotalCache($totalQuery)
	{
		$db = Factory::getDbo();
		$db->setQuery($totalQuery)->execute();

		return $db->getNumRows();
	}

	public function getPagination()
	{
		return new Pagination($this->getTotal(), $this->getStart(), $this->getLimit());
	}

	public function getStart() {
		static $limitstart;

		if (is_null($limitstart))
		{
			$limitstart	= Factory::getApplication()->input->get('limitstart', 0, '', 'int');
		}
		return $limitstart;
	}

	public function getLimit()
	{
		static $limit;

		if (is_null($limit))
		{
			$limit = Factory::getApplication()->input->get('limit', $this->params->get('display_num'), '', 'int');
		}

		return $limit;
	}

	public function getSearch()
	{
		return $this->_app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '', 'string');
	}

	public function getDynamicFiltersOperators()
	{
		$filters = $this->params->get('dynamic_filter_values', array());
		$operators = array();

		if ($filters)
		{
			if (isset($filters['name'], $filters['operator']))
			{
				$operators = array_combine($filters['name'], $filters['operator']);
			}
		}

		return $operators;
	}

	public function getDynamicFilters()
	{
		return $this->_app->getUserStateFromRequest($this->context.'.filter.dynamicfilter', 'filter_dynamicfilter', array(), 'array');
	}

	public function getListOrder()
	{
		return $this->_app->getUserStateFromRequest($this->context.'.filter.filter_order', 'filter_order', $this->params->get('default_ordering', 'SubmissionId'), 'string');
	}

	public function getListDirn()
	{
		$dir = $this->_app->getUserStateFromRequest($this->context.'.filter.filter_order_Dir', 'filter_order_Dir', $this->params->get('default_direction', 'desc'), 'word');
		$dir = strtolower($dir);

		if (in_array($dir, array('asc', 'desc')))
		{
			return $dir;
		}

		return 'desc';
	}

	public function getEditFields()
	{
		$db			= Factory::getDbo();
		$app		= Factory::getApplication();
		$return		= array();
		$values		= $app->input->get('form',array(),'array');
		$cid		= $app->input->getInt('id');

		// Load submission
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		$submission = RSFormProSubmissionsHelper::getSubmission($cid);

		if (!$submission)
		{
			return $return;
		}

		$query = $db->getQuery(true)
			->select($db->qn('MultipleSeparator'))
			->select($db->qn('TextareaNewLines'))
			->select($db->qn('FormLayoutName'))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($submission->FormId));
		$form = $db->setQuery($query)->loadObject();

		$form->MultipleSeparator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $form->MultipleSeparator);

		$layoutName = (string) preg_replace('/[^A-Z0-9]/i', '', $form->FormLayoutName);

		$submission->DateSubmitted = HTMLHelper::_('date', $submission->DateSubmitted, 'Y-m-d H:i:s');
		if (!in_array($submission->ConfirmedDate, array('', null, '0000-00-00 00:00:00', $db->getNullDate())))
		{
			$submission->ConfirmedDate = HTMLHelper::_('date', $submission->ConfirmedDate, 'Y-m-d H:i:s');
		}

		if (is_array($this->validation))
		{
			$validation = $this->validation;
		}
		elseif (!empty($values))
		{
			$validation = RSFormProHelper::validateForm($submission->FormId, 'directory', $cid);
		}
		else
		{
			$validation = array();
		}
		$headers        = RSFormProHelper::getDirectoryStaticHeaders();
		$formFields 	= RSFormProHelper::getDirectoryFields($submission->FormId);

		$query = $db->getQuery(true);
		$query->select($db->qn('ct.ComponentTypeName', 'type'))
			->select($db->qn('c.ComponentId'))
			->select($db->qn('ct.ComponentTypeId'))
			->from($db->qn('#__rsform_components', 'c'))
			->join('left', $db->qn('#__rsform_component_types', 'ct').' ON ('.$db->qn('c.ComponentTypeId').'='.$db->qn('ct.ComponentTypeId').')')
			->where($db->qn('c.FormId').'='.$db->q($submission->FormId))
			->where($db->qn('c.Published').'='.$db->q(1));
		$componentTypes = $db->setQuery($query)->loadObjectList('ComponentId');

		$componentIds = array();
		foreach ($formFields as $formField) {
			if ($formField->FieldId > 0) {
				$componentIds[] = $formField->FieldId;
			}

			// Assign the type
			$formField->type = '';
			if ($formField->FieldId < 0 && isset($headers[$formField->FieldId])) {
				$formField->type = 'static';
			} elseif (isset($componentTypes[$formField->FieldId])) {
				$formField->type = $componentTypes[$formField->FieldId]->type;
			}

			// For convenience...
			$formField->id 		= $formField->FieldId;
			$formField->name 	= $formField->FieldName;
		}

		$properties	= RSFormProHelper::getComponentProperties($componentIds, false);

		// Apply translations based on the current submission's language
		if ($translations = RSFormProHelper::getTranslations('properties', $submission->FormId, $submission->Lang))
		{
			foreach ($translations as $reference_id => $translation)
			{
				list ($componentId, $property) = explode('.', $reference_id, 2);

				if (isset($properties[$componentId][$property]))
				{
					$properties[$componentId][$property] = $translation;
				}
			}
		}

		$YUICalendars = false;
		$jQueryCalendars = false;

		foreach ($formFields as $field)
		{
			if (!$field->editable) {
				continue;
			}

			$invalid		= !empty($validation) && in_array($field->id,$validation) ? ' rsform-error' : '';
			$data			= $field->id > 0 ? $properties[$field->id] : array('NAME' => $field->name);
			$name			= $field->name;
			$new_field		= array(
				RSFORM_DIR_CAPTION      => !empty($data['CAPTION']) ? $data['CAPTION'] : $field->name,
				RSFORM_DIR_INPUT        => '',
				RSFORM_DIR_REQUIRED     => isset($data['REQUIRED']) && $data['REQUIRED'] == 'YES' ? '<strong class="formRequired">(*)</strong>' : '',
				RSFORM_DIR_NAME         => $field->name,
				RSFORM_DIR_DESCRIPTION  => isset($data['DESCRIPTION']) ? $data['DESCRIPTION'] : '',
				RSFORM_DIR_ID           => 'field-' . OutputFilter::stringURLSafe($field->name)
			);

			if ($invalid)
			{
				if (isset($data['VALIDATIONMESSAGE']))
				{
					$new_field[RSFORM_DIR_VALIDATION] = '<div id="component' . $field->id . '" class="dirError">' . $data['VALIDATIONMESSAGE'] . '</span>';
				}
			}

			if ($field->type != 'static') {
				if (isset($values[$field->name]))
					$value	= $values[$field->name];
				else {
					$value	= isset($submission->values[$field->name]) ? $submission->values[$field->name] : '';
				}
			} else {
				$value = isset($submission->{$field->name}) ? $submission->{$field->name} : '';
			}

			$placeholder = '';
			if (isset($data['PLACEHOLDER']))
			{
				$placeholder = 'placeholder="' . RSFormProHelper::htmlEscape($data['PLACEHOLDER']) . '"';
			}

			switch ($field->type)
			{
				case 'static':
					$new_field[RSFORM_DIR_CAPTION] = Text::_('RSFP_'.$field->name);

					// Show a dropdown for yes/no
					if ($field->name == 'confirmed')
					{
						$options = array(
							HTMLHelper::_('select.option', 0, Text::_('RSFP_NO')),
							HTMLHelper::_('select.option', 1, Text::_('RSFP_YES'))
						);

						$new_field[RSFORM_DIR_INPUT] = HTMLHelper::_('select.genericlist', $options, 'formStatic[confirmed]', array('class' => 'form-select'), 'value', 'text', $value);
					}
					else
					{
						$new_field[RSFORM_DIR_INPUT] = '<input class="rs_inp rs_80 form-control" type="text" id="' . $new_field[RSFORM_DIR_ID] . '" name="formStatic['.$name.']" value="'.RSFormProHelper::htmlEscape($value).'" />';
					}
					break;

				// skip this field for now, no need to edit it
				case 'freeText':
					$new_field[RSFORM_DIR_CAPTION] = '';
					$new_field[RSFORM_DIR_ID] = false;
					$new_field[RSFORM_DIR_INPUT] = RSFormProHelper::isCode($data['TEXT']);
					break;

				case 'surveyTable':
					$type = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', strtolower($field->type));
					$type = ltrim($type, '.');
					// For legacy reasons...
					$r = array(
						'ComponentTypeId' => $field->FieldType,
						'Order'			  => isset($data['Order']) ? $data['Order'] : 0
					);

					$layouts = array(
						// Path to the layout (overridden) class
						'RSFormProField' . $layoutName . $type => JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/'.strtolower($layoutName).'/'.strtolower($type).'.php',

						// Path to the fallback (basic) class
						'RSFormProField' . $type => JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/'.strtolower($type).'.php'
					);

					// Emulate variables
					$out = '';
					$formId = $submission->FormId;
					$val = isset($values[$field->name]) ? $values : $submission->values;
					if (!$values && isset($submission->values['_JSON_' . $field->name]) && ($storedValue = json_decode($submission->values['_JSON_' . $field->name], true)))
					{
						$val[$field->name] = $storedValue;
					}
					$data['componentTypeId'] 	= $field->FieldType;
					$data['ComponentTypeName'] 	= $field->type;
					$data['Order'] 				= $field->ordering;

					$app->triggerEvent('onRsformBackendBeforeCreateFrontComponentBody', array(array(
						'out' 			=> &$out,
						'formId' 		=> $submission->FormId,
						'componentId' 	=> $field->componentId,
						'data' 			=> &$data,
						'value' 		=> &$val
					)));

					$config = array(
						'formId' 			=> $formId,
						'componentId' 		=> $field->componentId,
						'data' 				=> $data,
						'value' 			=> $val,
						'invalid' 			=> $invalid,
						'errorClass' 		=> '',
						'fieldErrorClass' 	=> ''
					);

					require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/' . $type . '.php';

					foreach ($layouts as $class => $file)
					{
						if (file_exists($file))
						{
							// If class doesn't exist, load the file
							if (!class_exists($class))
							{
								require_once $file;
							}

							// Create the field
							$fieldClass = new $class($config);

							break;
						}
					}

					$out .= $fieldClass->output;

					$app->triggerEvent('onRsformBackendAfterCreateFrontComponentBody', array(array(
						'out' 			=> &$out,
						'formId' 		=> $formId,
						'componentId' 	=> $fieldClass->componentId,
						'data' 			=> $data,
						'value' 		=> $val,
						'r'				=> $r,
						'invalid' 		=> $invalid
					)));

					$new_field[RSFORM_DIR_INPUT] = $out;
					break;

				default:
					if (is_array($value))
					{
						$value = implode($form->MultipleSeparator, $value);
					}

					if (strpos($value, "\n") !== false || strpos($value, "\r") !== false || $field->type === 'surveyTable')
					{
						$new_field[RSFORM_DIR_INPUT] = '<textarea ' . $placeholder . ' style="width: 95%" class="rs_textarea'.$invalid.'" id="' . $new_field[RSFORM_DIR_ID] . '" rows="10" cols="60" name="form['.$name.']">'.RSFormProHelper::htmlEscape($value).'</textarea>';
					}
					else
					{
						$new_field[RSFORM_DIR_INPUT] = '<input class="rs_inp rs_80 form-control'.$invalid.'" type="text" id="' . $new_field[RSFORM_DIR_ID] . '" name="form['.$name.']" value="'.RSFormProHelper::htmlEscape($value).'" ' . $placeholder . ' />';
					}
					break;

				case 'textArea':
					if (isset($data['WYSIWYG']) && $data['WYSIWYG'] == 'YES')
					{
						$new_field[RSFORM_DIR_INPUT] = RSFormProHelper::WYSIWYG('form['.$name.']', RSFormProHelper::htmlEscape($value), '', 600, 100, 60, 10);
					}
					else
					{
						$new_field[RSFORM_DIR_INPUT] = '<textarea ' . $placeholder . ' style="width: 95%" class="rs_textarea form-control'.$invalid.'" rows="10" cols="60" id="' . $new_field[RSFORM_DIR_ID] . '" name="form['.$name.']">'.RSFormProHelper::htmlEscape($value).'</textarea>';
					}
					break;

				case 'checkboxGroup':
				case 'radioGroup':
					$new_field[RSFORM_DIR_ID] = false;
					$options = array();
					$value = !empty($values) ? $value : RSFormProHelper::explode($value);
					$value = (array) $value;

					if ($field->type === 'checkboxGroup')
					{
						$htmlType = 'checkbox';
						$htmlName = 'form[' . RSFormProHelper::htmlEscape($name) . '][]';
					}
					else
					{
						$htmlName = 'form[' . RSFormProHelper::htmlEscape($name) . ']';
						$htmlType = 'radio';
					}

					require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fielditem.php';
					require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fieldmultiple.php';
					$f = new RSFormProFieldMultiple(array(
						'formId' 			=> $submission->FormId,
						'componentId' 		=> $field->id,
						'data' 				=> $data,
						'value' 			=> array('formId' => $submission->FormId, $data['NAME'] => $value),
						'invalid' 			=> in_array($field->id, $validation)
					));

					if ($items = $f->getItems())
					{
						$i = 0;
						foreach ($items as $item)
						{
							$item = new RSFormProFieldItem($item);

							$html = '<label><input type="' . $htmlType . '" ' .
							' name="' . $htmlName . '"' .
							' value="' . RSFormProHelper::htmlEscape($item->value) . '"' .
							' id="' . RSFormProHelper::htmlEscape($name) . $i . '"';

							if ($item->flags['disabled']) {
								$html .= ' disabled="disabled"';
							}

							if (in_array($item->value, $value)) {
								$html .= ' checked="checked"';
							}

							if ($invalid)
							{
								$html .= ' class="' . $invalid . '"';
							}

							$html .= '> ' . $item->label . '</label>';

							$options[] = $html;

							$i++;
						}
					}

					if ($max = (int) $f->getProperty('MAXSELECTIONS'))
					{
						$id = $f->getId();
						RSFormProAssets::addScriptDeclaration("RSFormPro.limitSelections({$submission->FormId}, '{$id}', {$max});");
					}

					$new_field[RSFORM_DIR_INPUT] = '<p>' . implode('</p><p>', $options) . '</p>';

					break;

				case 'selectList':
					$options = array();

					$value = !empty($values) ? $value : RSFormProHelper::explode($value);

					require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fielditem.php';
					require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fieldmultiple.php';
					$f = new RSFormProFieldMultiple(array(
						'formId' 			=> $submission->FormId,
						'componentId' 		=> $field->id,
						'data' 				=> $data,
						'value' 			=> array('formId' => $submission->FormId, $data['NAME'] => $value),
						'invalid' 			=> in_array($field->id, $validation)
					));

					if ($items = $f->getItems())
					{
						foreach ($items as $item)
						{
							$item = new RSFormProFieldItem($item);

							if ($item->flags['optgroup']) {
								$options[] = HTMLHelper::_('select.option', '<OPTGROUP>', $item->label, 'value', 'text');
							} elseif ($item->flags['/optgroup']) {
								$options[] = HTMLHelper::_('select.option', '</OPTGROUP>', $item->label, 'value', 'text');
							} else {
								$options[] = HTMLHelper::_('select.option', $item->value, $item->label, 'value', 'text', $item->flags['disabled']);
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

					if ($invalid)
					{
						$attribs[] = 'class="form-select' . $invalid . '"';
					}
					else
					{
						$attribs[] = 'class="form-select"';
					}
					$attribs = implode(' ', $attribs);

					$new_field[RSFORM_DIR_INPUT] = HTMLHelper::_('select.genericlist', $options, 'form['.$name.'][]', $attribs, 'value', 'text', $value, $new_field[RSFORM_DIR_ID]);
					break;

				case 'fileUpload':
					if ($value)
					{
						$files = RSFormProHelper::explode($value);
					}
					else
					{
						$files = array();
					}

					$new_field[RSFORM_DIR_INPUT] = '<div>';

					foreach ($files as $file)
					{
						$new_field[RSFORM_DIR_INPUT] .= '<p><button type="button" class="btn btn-secondary btn-small btn-sm" onclick="RSFormProDirectory.clearUpload(\'' . $name . '\', this, \'' . md5($file) . '\');">' . Text::_('COM_RSFORM_CLEAR') . '</button> <span' . ($invalid ? ' class="' . $invalid . '"' : '') . '>' . RSFormProHelper::htmlEscape(basename($file)) . '</span></p>';
					}

					$new_field[RSFORM_DIR_INPUT] .= '</div>';

					$multiple =  !empty($data['MULTIPLE']) && $data['MULTIPLE'] == 'YES';

					$new_field[RSFORM_DIR_INPUT] .= '<input size="45" class="form-control" id="' . $new_field[RSFORM_DIR_ID] .'" type="file" name="form['.$name.']' . ($multiple ? '[]' : '') . '" ' . ($multiple ? 'multiple' : '') . ' />';
					break;

				case 'jQueryCalendar':
				case 'calendar':
					if ($field->type === 'jQueryCalendar')
					{
						$jQueryCalendars = true;
					}

					if ($field->type === 'calendar')
					{
						$YUICalendars = true;
					}

					$type = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', strtolower($field->type));
					$type = ltrim($type, '.');
					// For legacy reasons...
					$r = array(
						'ComponentTypeId' => $field->FieldType,
						'Order'			  => isset($data['Order']) ? $data['Order'] : 0
					);

					// Emulate variables
					$out = '';
					$formId = $submission->FormId;
					$val = isset($values[$field->name]) ? $values : $submission->values;
					$data['componentTypeId'] 	= $field->FieldType;
					$data['ComponentTypeName'] 	= $field->type;
					$data['Order'] 				= $field->ordering;

					$app->triggerEvent('onRsformBackendBeforeCreateFrontComponentBody', array(array(
						'out' 			=> &$out,
						'formId' 		=> $submission->FormId,
						'componentId' 	=> $field->componentId,
						'data' 			=> &$data,
						'value' 		=> &$val
					)));

					$config = array(
						'formId' 			=> $formId,
						'componentId' 		=> $field->componentId,
						'data' 				=> $data,
						'value' 			=> $val,
						'invalid' 			=> $invalid,
						'errorClass' 		=> '',
						'fieldErrorClass' 	=> ''
					);

					require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/' . $type . '.php';

					$class = 'RSFormProField' . $type;

					// Create the field
					$fieldClass = new $class($config);

					$out .= $fieldClass->output;

					$app->triggerEvent('onRsformBackendAfterCreateFrontComponentBody', array(array(
						'out' 			=> &$out,
						'formId' 		=> $formId,
						'componentId' 	=> $fieldClass->componentId,
						'data' 			=> $data,
						'value' 		=> $val,
						'r'				=> $r,
						'invalid' 		=> $invalid
					)));

					$new_field[RSFORM_DIR_INPUT] = $out;

					if ($field->type === 'jQueryCalendar')
					{
						$new_field[RSFORM_DIR_ID] = 'txtjQcal' . $fieldClass->customId;
					}
					elseif ($field->type === 'calendar')
					{
						$new_field[RSFORM_DIR_ID] = 'txtcal' . $fieldClass->customId;
					}

					break;
			}

			$return[$field->id] = $new_field;
		}

		if ($YUICalendars || $jQueryCalendars)
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/calendar.php';

			// render the YUI Calendars
			if ($YUICalendars)
			{
				$calendar = RSFormProCalendar::getInstance('YUICalendar');
				RSFormProAssets::addScriptDeclaration($calendar->printInlineScript($formId));
			}

			// render the jQuery Calendars
			if ($jQueryCalendars)
			{
				$calendar = RSFormProCalendar::getInstance('jQueryCalendar');
				RSFormProAssets::addScriptDeclaration($calendar->printInlineScript($formId));
			}
		}

		Factory::getApplication()->triggerEvent('onRsformFrontendGetEditFields', array(&$return, $submission));

		return $return;
	}

	// Get current Itemid
	public function getItemid() {
		if ($active = $this->_app->getMenu()->getActive())
		{
			return $active->id;
		}

		return 0;
	}

	public function translateDateFormat($format)
	{
		$translationTable = array(
			'%a' => "D", // Abbreviated weekday name (Sun..Sat)
			'%b' => "M", // Abbreviated month name (Jan..Dec)
			'%c' => "n", // Month, numeric (0..12)
			'%D' => "jS", // Day of the month with English suffix (0th, 1st, 2nd, 3rd, …)
			'%d' => "d", // Day of the month, numeric (00..31)
			'%e' => "j", // Day of the month, numeric (0..31)
			'%f' => "u", // Microseconds (000000..999999)
			'%H' => "H", // Hour (00..23)
			'%h' => "h", // Hour (01..12)
			'%I' => "h", // Hour (01..12)
			'%i' => "i", // Minutes, numeric (00..59)
			'%j' => "z", // Day of year (001..366)
			'%k' => "G", // Hour (0..23)
			'%l' => "g", // Hour (1..12)
			'%M' => "F", // Month name (January..December)
			'%m' => "m", // Month, numeric (00..12)
			'%p' => "A", // AM or PM
			'%r' => "H:i:s A", // Time, 12-hour (hh:mm:ss followed by AM or PM)
			'%S' => "s", // Seconds (00..59)
			'%s' => "s", // Seconds (00..59)
			'%T' => "H:i:s", // Time, 24-hour (hh:mm:ss)
			'%W' => "l", // Weekday name (Sunday..Saturday)
			'%w' => "w", // Day of the week (0=Sunday..6=Saturday)
			'%Y' => "Y", // Year, numeric, four digits
			'%y' => "y" // Year, numeric (two digits)
		);

		$newFormat = '';

		for ($i = 0; $i < strlen($format); $i++)
		{
			$current = $format[$i];

			// get previous char to see if is the dash
			$previous = isset($format[($i-1)]) ? $format[($i-1)] : '';

			if ($current == "\\" || $previous == "\\")
			{
				$newFormat .= $current;
				continue;
			}

			if (($pos = array_search($current, $translationTable)) !== false)
			{
				$newFormat .= $pos;
			}
			else
			{
				$newFormat .= $current;
			}
		}

		return $newFormat;
	}
}