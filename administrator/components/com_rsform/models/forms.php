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
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\HTML\HTMLHelper;

class RsformModelForms extends ListModel
{
	public $_mdata = null;

	/* @var TableRSForm_Forms */
	public $_form = null;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'FormTitle',
				'FormName',
				'Published',
				'FormId',
				'state'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$this->setState('filter.search', 	$this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', 	$this->getUserStateFromRequest($this->context.'.filter.state', 	'filter_state'));

		// List state information.
		parent::populateState('FormId', 'asc');
	}

	protected function getListQuery()
	{
		$filter_search = $this->getState('filter.search', '');
		$lang		   = Factory::getLanguage();
		$db            = $this->getDbo();
		$query		   = $db->getQuery(true);
		$or 	= array();
		$ids 	= array();

		// Flag to know if we need translations - no point in doing a join if we're only using the default language.
		if (RSFormProHelper::getConfig('global.disable_multilanguage') && RSFormProHelper::getConfig('global.default_language') == 'en-GB')
		{
			$needs_translation = false;
		}
		else
		{
			// Must check if we've changed the language for some forms (each form has its own remembered language).
			if ($sessions = Factory::getSession()->get('com_rsform.form'))
			{
				// For each form in the session, we join a specific language and form id.
				foreach ($sessions as $form => $data)
				{
					if (strpos($form, 'formId') === 0 && isset($data->lang))
					{
						$id 	= (int) substr($form, strlen('formId'));
						$ids[] 	= $id;
						$or[] 	= '(' . $db->qn('t.lang_code') . ' = ' . $db->q($data->lang) . ' AND ' . $db->qn('t.form_id') . ' = ' . $db->q($id) . ')';
					}
				}

				// Now that we've joined the session forms, we must remove them so they do not show up as duplicates.
				if ($ids)
				{
					$or[] = '(' . $db->qn('t.lang_code') . ' = ' . $db->q($lang->getTag()) . ' AND ' . $db->qn('t.form_id') . ' NOT IN (' . implode(',', $db->q($ids)) . '))';
				}
			}

			$needs_translation = $lang->getTag() != $lang->getDefault() || $ids || (RSFormProHelper::getConfig('global.disable_multilanguage') && RSFormProHelper::getConfig('global.default_language') != 'en-GB');
		}

		$query->select($db->qn('f.FormId'))
			->select($db->qn('f.FormName'))
			->select($db->qn('f.Backendmenu'))
			->select($db->qn('f.Published'))
			->from($db->qn('#__rsform_forms', 'f'));

		if ($needs_translation)
		{
			$query->select('IFNULL(' . $db->qn('t.value') . ', ' . $db->qn('f.FormTitle') . ') AS FormTitle');
		}
		else
		{
			$query->select($db->qn('f.FormTitle'));
		}

		if ($needs_translation)
		{
			$on = array(
				$db->qn('f.FormId') . ' = ' . $db->qn('t.form_id'),
				$db->qn('t.reference') . ' = ' . $db->q('forms'),
				$db->qn('t.reference_id') . ' = ' . $db->q('FormTitle')
			);

			if ($or && !RSFormProHelper::getConfig('global.disable_multilanguage'))
			{
				$on[] = '(' . implode(' OR ', $or) . ')';
			}
			else
			{
				if (RSFormProHelper::getConfig('global.default_language') == 'en-GB')
				{
					$on[] = $db->qn('t.lang_code') . ' = ' . $db->q($lang->getTag());
				}
				else
				{
					$on[] = $db->qn('t.lang_code') . ' = ' . $db->q(RSFormProHelper::getConfig('global.default_language'));
				}
			}

			$query->join('left', $db->qn('#__rsform_translations', 't') . ' ON (' . implode(' AND ', $on) . ')');
		}

		if (strlen($filter_search))
		{
			if (stripos($filter_search, 'id:') === 0)
			{
				$query->where($db->qn('f.FormId') . ' = ' . (int) substr($filter_search, 3));
			}
			else
			{
				$query->having('(' . $db->qn('FormTitle') . ' LIKE ' . $db->q('%' . $filter_search . '%') . ' OR ' . $db->qn('FormName') . ' LIKE ' . $db->q('%' . $filter_search . '%') . ')');
			}
		}

		$state = $this->getState('filter.state');
		if ($state != '')
		{
			$query->where($db->qn('Published') . '=' . $db->q($state));
		}

		$query->order($db->qn($this->getSortColumn()) . ' ' . $db->escape($this->getSortOrder()));

		return $query;
	}

	public function getForms()
	{
		$items = $this->getItems();

		$formIds = array();
		foreach ($items as $row)
		{
			$formIds[] = $row->FormId;
		}

		if ($formIds)
		{
			$date = Factory::getDate();
			$tzoffset = Factory::getDate('now', Factory::getUser()->getTimezone())->getOffsetFromGmt(true);

			// Count submissions
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('COUNT(' . $db->qn('SubmissionId') . ') AS ' . $db->qn('total'))
				->select($db->qn('FormId'))
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('FormId') . ' IN (' . implode(',', $db->q($formIds)) . ')')
				->group($db->qn('FormId'));
			$allSubmissions = $db->setQuery($query)->loadObjectList('FormId');

			// Month submissions
			$query = $db->getQuery(true)
				->select('COUNT(' . $db->qn('SubmissionId') . ') AS ' . $db->qn('total'))
				->select($db->qn('FormId'))
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('FormId') . ' IN (' . implode(',', $db->q($formIds)) . ')')
				->group($db->qn('FormId'));

			if ($tzoffset)
			{
				$query->where('DATE_FORMAT(DATE_ADD(' . $db->qn('DateSubmitted') . ', INTERVAL ' . $tzoffset . ' HOUR), ' . $db->q('%Y-%m') . ') = ' . $db->q($date->format('Y-m')));
			}
			else
			{
				$query->where('DATE_FORMAT(' . $db->qn('DateSubmitted') . ', ' . $db->q('%Y-%m') . ') = ' . $db->q($date->format('Y-m')));
			}
			$monthSubmissions = $db->setQuery($query)->loadObjectList('FormId');

			// Day submissions
			$query = $db->getQuery(true)
				->select('COUNT(' . $db->qn('SubmissionId') . ') AS ' . $db->qn('total'))
				->select($db->qn('FormId'))
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('FormId') . ' IN (' . implode(',', $db->q($formIds)) . ')')
				->group($db->qn('FormId'));

			if ($tzoffset)
			{
				$query->where('DATE_FORMAT(DATE_ADD(' . $db->qn('DateSubmitted') . ', INTERVAL ' . $tzoffset . ' HOUR), ' . $db->q('%Y-%m-%d') . ') >= ' . $db->q($date->format('Y-m-d')));
			}
			else
			{
				$query->where('DATE_FORMAT(' . $db->qn('DateSubmitted') . ', ' . $db->q('%Y-%m-%d') . ') = ' . $db->q($date->format('Y-m-d')));
			}
			$todaySubmissions = $db->setQuery($query)->loadObjectList('FormId');

			foreach ($items as $form)
			{
				$form->_todaySubmissions = isset($todaySubmissions[$form->FormId]) ? $todaySubmissions[$form->FormId]->total : 0;
				$form->_monthSubmissions = isset($monthSubmissions[$form->FormId]) ? $monthSubmissions[$form->FormId]->total : 0;
				$form->_allSubmissions = isset($allSubmissions[$form->FormId]) ? $allSubmissions[$form->FormId]->total : 0;
			}
		}

		return $items;
	}

	public function getSortColumn()
	{
		return $this->getState('list.ordering', 'FormId');
	}

	public function getSortOrder()
	{
		return $this->getState('list.direction', 'ASC');
	}

	public function getHasSubmitButton()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		$db     = $this->getDbo();

		$query = $db->getQuery(true)
            ->select($db->qn('ComponentId'))
            ->from($db->qn('#__rsform_components'))
            ->where($db->qn('FormId') . ' = ' . $db->q($formId))
            ->where($db->qn('ComponentTypeId') . ' = ' . $db->q(RSFORM_FIELD_SUBMITBUTTON));

		$db->setQuery($query);
		return $db->loadResult();
	}

	public function getFields()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		$db     = $this->getDbo();

		$return = array();

		$query = $db->getQuery(true)
			->select($db->qn('p.PropertyValue', 'ComponentName'))
			->select('c.*')
			->select($db->qn('ct.ComponentTypeName'))
			->from($db->qn('#__rsform_components', 'c'))
			->leftJoin($db->qn('#__rsform_properties', 'p') . ' ON (' . $db->qn('c.ComponentId') . ' = ' . $db->qn('p.ComponentId') . ' AND ' . $db->qn('p.PropertyName') . ' = ' . $db->q('NAME') . ')')
			->leftJoin($db->qn('#__rsform_component_types', 'ct') . ' ON (' . $db->qn('ct.ComponentTypeId') . ' = ' . $db->qn('c.ComponentTypeId') . ')')
			->where($db->qn('c.FormId') . ' = ' . $db->q($formId))
			->order($db->qn('c.Order') . ' ' . $db->escape('asc'));

		$db->setQuery($query);
		$components = $db->loadObjectList();

		$properties = RSFormProHelper::getComponentProperties($components);

		$pages = array();
		foreach ($components as $component)
		{
			if ($component->ComponentTypeId == RSFORM_FIELD_PAGEBREAK) {
				$pages[] = $component->ComponentId;
			}
		}

		foreach ($components as $component)
		{
			$data = $properties[$component->ComponentId];
			$data['componentId'] = $component->ComponentId;
			$data['componentTypeId'] = $component->ComponentTypeId;
			$data['ComponentTypeName'] = $component->ComponentTypeName;

			// Pagination
			if ($component->ComponentTypeId == RSFORM_FIELD_PAGEBREAK)
			{
				$data['PAGES'] = $pages;
			}

			$field = new stdClass();
			$field->id = $component->ComponentId;
			$field->type_id = $component->ComponentTypeId;
			$field->type_name = $component->ComponentTypeName;
			$field->name = $component->ComponentName;
			$field->published = $component->Published;
			$field->ordering = $component->Order;
			$field->preview = $this->showPreview($formId, $field->id, $data);

			$field->caption = isset($data['CAPTION']) && strlen($data['CAPTION']) ? $data['CAPTION'] : $field->name;

			if (!empty($data['REQUIRED']))
			{
				$field->hasRequired = true;
				$field->required = $data['REQUIRED'] == 'YES';
			}
			else
			{
				$field->required = false;
			}

			if (isset($data['VALIDATIONRULE']) && $data['VALIDATIONRULE'] != 'none')
			{
				$field->validation = $data['VALIDATIONRULE'];
			}
			elseif (isset($data['VALIDATIONRULE_DATE']) && $data['VALIDATIONRULE_DATE'] != 'none')
			{
				$field->validation = $data['VALIDATIONRULE_DATE'];
			}

			$return[$field->id] = $field;
		}
		
		return $return;
	}

	protected function showPreview($formId, $componentId, $data)
	{
		$mainframe 		= Factory::getApplication();
		$formId 		= (int) $formId;
		$componentId 	= (int) $componentId;

		// Legacy
		$r = array();
		$r['ComponentTypeName'] = $data['ComponentTypeName'];

		$out = '';

		//Trigger Event - onRsformBackendBeforeCreateComponentPreview
		$mainframe->triggerEvent('onRsformBackendBeforeCreateComponentPreview',array(array('out'=>&$out,'formId'=>$formId,'componentId'=>$componentId,'ComponentTypeName'=>$r['ComponentTypeName'],'data'=>$data)));

		$config    = array(
			'formId' 		=> $formId,
			'componentId' 	=> $componentId,
			'data' 			=> $data,
			'preview' 		=> true,
			'value' 		=> array(),
			'invalid' 		=> false,
			'errorClass' 	=> ''
		);

		if (!empty($r['ComponentTypeName']))
		{
			$type       = $r['ComponentTypeName'];
			$classFile  = JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/' . strtolower($type) . '.php';
			if (file_exists($classFile))
			{
				$class = 'RSFormProField' . $type;

				if (!class_exists($class))
				{
					require_once $classFile;
				}

				// Create the field
				$field = new $class($config, true);

				// Return the output
				$out .= $field->output;
			}
		}

		if (empty($out))
		{
			$out = '<em>'.Text::_('RSFP_COMP_PREVIEW_NOT_AVAILABLE').'</em>';
		}

		//Trigger Event - onRsformBackendAfterCreateComponentPreview
		$mainframe->triggerEvent('onRsformBackendAfterCreateComponentPreview',array(array('out'=>&$out, 'formId'=>$formId, 'componentId'=>$componentId, 'ComponentTypeName'=>$r['ComponentTypeName'],'data'=>$data)));

		return $out;
	}

	public function getFieldsTotal()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		$db 	= $this->getDbo();

		$query = $db->getQuery(true)
			->select('COUNT(' . $db->qn('ComponentId') . ')')
			->from($db->qn('#__rsform_components'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));

		return $db->setQuery($query)->loadResult();
	}

	public function getFieldsPagination()
	{
		$pagination	= new Pagination($this->getFieldsTotal(), 1, 0);
		// hack to show the order up icon for the first item
		$pagination->limitstart = 1;
		return $pagination;
	}

	public function getForm()
	{
		$formId = Factory::getApplication()->input->getInt('formId');

		if (empty($this->_form))
		{
			$this->_form = Table::getInstance('RSForm_Forms', 'Table');
			$this->_form->load($formId);

			if (empty($this->_form->Lang))
			{
				$this->_form->Lang = Factory::getLanguage()->getDefault();
			}

			if ($this->_form->FormLayoutAutogenerate)
			{
				$this->autoGenerateLayout();
			}

			$lang = $this->getLang();
			if ($lang != $this->_form->Lang)
			{
				if ($translations = RSFormProHelper::getTranslations('forms', $this->_form->FormId, $lang))
				{
					foreach ($translations as $field => $value)
					{
						if (isset($this->_form->{$field}))
						{
							$this->_form->{$field} = $value;
						}
					}
				}
			}
		}

		return $this->_form;
	}

	public function getFormPost()
	{
		$formId = Factory::getApplication()->input->getInt('formId');

		$post = Table::getInstance('RSForm_Posts', 'Table');
		$post->load($formId, false);

		return $post;
	}

	public function autoGenerateLayout()
	{
		$formId = $this->_form->FormId;
		$filter = InputFilter::getInstance();

		$layout = JPATH_ADMINISTRATOR.'/components/com_rsform/layouts/'.$filter->clean($this->_form->FormLayoutName, 'path').'.php';
		if (!file_exists($layout)) {
			return false;
		}

		// check if the form title should be shown
		$showFormTitle =  $this->_form->ShowFormTitle;
		// set the required field marker
		$requiredMarker = isset($this->_form->Required) ? $this->_form->Required : '(*)';
		// get the form fields
		$fieldsets 		= $this->getFieldNames('fieldsets');
		// get the form options
		$formOptions = RSFormProHelper::getForm($formId);

		// Generate the layout
		ob_start();
		// include the layout selected
		include $layout;
		$out = ob_get_contents();
		ob_end_clean();

		if ($out != $this->_form->FormLayout && $this->_form->FormId) {
			// Clean it
			// Update the layout
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__rsform_forms'))
				->set($db->qn('FormLayout').'='.$db->q($out))
				->where($db->qn('FormId').'='.$db->q($formId));

			$db->setQuery($query)->execute();
		}

		$this->_form->FormLayout = $out;
	}

	public function getJForm()
	{
		// Get the form.
		$form = $this->loadForm('com_rsform.form', 'form', array('control' => '', 'load_data' => false));
		if (empty($form))
		{
			return false;
		}

		if (empty($this->_form))
		{
			$this->getForm();
		}

		$data = clone $this->_form;
		$data->Language = $this->getLang();

		if (!empty($data->ConfirmSubmissionDefer))
		{
			$data->ConfirmSubmissionDefer = json_decode($data->ConfirmSubmissionDefer);
		}

		if (empty($data->ConfirmSubmissionDefer))
		{
			$data->ConfirmSubmissionDefer = array();
		}

		$form->bind($data->getProperties());

		return $form;
	}

	public function getPostJForm()
	{
		// Get the form.
		$form = $this->loadForm('com_rsform.post', 'post', array('control' => 'form_post', 'load_data' => false));
		if (empty($form))
		{
			return false;
		}

		$form->bind($this->getFormPost()->getProperties());

		return $form;
	}

	public function getProperty($fieldData, $prop, $default = null)
	{
		// Special case, we no longer use == 'YES' or == 'NO'
		if (isset($fieldData[$prop]))
		{
			if ($fieldData[$prop] === 'YES')
			{
				return true;
			}
			elseif ($fieldData[$prop] === 'NO')
			{
				return false;
			}
			else
			{
				return $fieldData[$prop];
			}
		}

		if ($default === 'YES')
		{
			return true;
		}
		elseif ($default === 'NO')
		{
			return false;
		}
		else
		{
			return $default;
		}
	}

	public function getComponentType($componentId, $formId)
	{
		$db 	= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select($db->qn('ComponentTypeId'))
			->from($db->qn('#__rsform_components'))
			->where($db->qn('FormId').'='.$db->q($formId))
			->where($db->qn('ComponentId').'='.$db->q($componentId));
		
		$query->setLimit(1);
		
		$db->setQuery($query);

		return $db->loadResult();
	}

	protected function getFieldNames($type = 'all')
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/quickfields.php';
		return RSFormProQuickFields::getFieldNames($type);
	}

	public function getRequiredFields() {
		return $this->getFieldNames('required');
	}

	public function getHiddenFields() {
		return $this->getFieldNames('hidden');
	}

	public function getQuickFields() {
		return $this->getFieldNames('all');
	}

	public function getPageFields() {
		return $this->getFieldNames('pages');
	}

	public function getFormList()
	{
		$return = array();
		$formId = Factory::getApplication()->input->getInt('formId');
		$db     = $this->getDbo();
		
		// Workaround to ignore searches
		$filter_search = $this->getState('filter.search');
		$filter_state = $this->getState('filter.state');
		$this->setState('filter.search', null);
		$this->setState('filter.state', null);

		$query = $this->getListQuery();
		
		// Revert
		$this->setState('filter.search', $filter_search);
		$this->setState('filter.state', $filter_state);

		$db->setQuery($query);
		$results = $db->loadObjectList();
		foreach ($results as $result)
		{
			$return[] = HTMLHelper::_('select.option', $result->FormId, $result->FormTitle, 'value', 'text', $result->FormId == $formId);
		}

		return $return;
	}

	public function save()
	{
		$mainframe 	= Factory::getApplication();
        $post 		= array();
		$form       = Table::getInstance('RSForm_Forms', 'Table');

		foreach ($form->getProperties(true) as $property => $value)
		{
			$post[$property] = $mainframe->input->post->get($property, $value, 'raw');
		}

		$post['formId'] = $post['FormId'];

		if (!empty($post['ThankYouMessagePopUp']))
		{
			$post['ScrollToThankYou'] = 0;
		}

		unset($form->Thankyou);
		unset($form->UserEmailText);
		unset($form->AdminEmailText);
		unset($form->DeletionEmailText);
		unset($form->ErrorMessage);

		if (!isset($post['FormLayoutAutogenerate']))
		{
			$post['FormLayoutAutogenerate'] = 0;
		}

		if (empty($post['Keepdata']))
		{
			$post['ConfirmSubmission'] = 0;
		}

		if (!isset($post['ConfirmSubmissionDefer']))
		{
			$post['ConfirmSubmissionDefer'] = array();
		}

		$post['ConfirmSubmissionDefer'] = json_encode($post['ConfirmSubmissionDefer']);

		try
		{
			$form->bind($post);

			if (!$form->check())
			{
				return false;
			}
		}
		catch (Exception $e)
		{
			$mainframe->enqueueMessage($e->getMessage(), 'error');
			return false;
		}

		$this->saveFormTranslation($form, $this->getLang());

		$original = Table::getInstance('RSForm_Forms', 'Table');
		$original->load($form->FormId);
		if ($form->store())
		{
			// Post to another location
			$row = Table::getInstance('RSForm_Posts', 'Table');

            $form_post 				= $mainframe->input->get('form_post', array(), 'array');
            $form_post['form_id'] 	= $post['formId'];
			$form_post['fields'] 	= array();
			if (isset($form_post['name'], $form_post['value']) && is_array($form_post['name']) && is_array($form_post['value']))
			{
				for ($i = 0; $i < count($form_post['name']); $i++)
				{
					$form_post['fields'][] = array(
						'name'  => $form_post['name'][$i],
						'value' => $form_post['value'][$i],
					);
				}
			}
			$form_post['headers'] 	= array();
			if (isset($form_post['headers_name'], $form_post['headers_value']) && is_array($form_post['headers_name']) && is_array($form_post['headers_value']))
			{
				for ($i = 0; $i < count($form_post['headers_name']); $i++)
				{
					$form_post['headers'][] = array(
						'name'  => $form_post['headers_name'][$i],
						'value' => $form_post['headers_value'][$i],
					);
				}
			}

			$row->save($form_post);

			// Trigger event
			$mainframe->triggerEvent('onRsformFormSave', array(&$form, $original));
			return true;
		}
		else
		{
			$mainframe->enqueueMessage($form->getError(), 'error');
			return false;
		}
	}

	public function saveFormTranslation(&$form, $lang)
	{
		if ($form->Lang == $lang || (RSFormProHelper::getConfig('global.disable_multilanguage') && RSFormProHelper::getConfig('global.default_language') == 'en-GB'))
        {
            return true;
        }

		$fields 	  = array('FormTitle', 'UserEmailFromName', 'UserEmailSubject', 'AdminEmailFromName', 'AdminEmailSubject', 'DeletionEmailFromName', 'DeletionEmailSubject', 'DeletionEmailReplyToName', 'MetaDesc', 'MetaKeywords', 'UserEmailReplyToName', 'AdminEmailReplyToName', 'ReturnUrl');
		$translations = RSFormProHelper::getTranslations('forms', $form->FormId, $lang, 'id');
		foreach ($fields as $field)
		{
            $translation = (object) array(
                'form_id'       => $form->FormId,
                'lang_code'     => $lang,
                'reference'     => 'forms',
                'reference_id'  => $field,
                'value'         => $form->{$field}
            );

			if (!isset($translations[$field]))
			{
			    $this->getDbo()->insertObject('#__rsform_translations', $translation);
			}
			else
			{
			    $translation->id = $translations[$field];
			    $this->getDbo()->updateObject('#__rsform_translations', $translation, array('id'));
			}
			unset($form->$field);
		}

		return true;
	}

	public function saveFormPropertyTranslation($formId, $componentId, &$params, $lang, $just_added, $properties)
	{
		$fields 	  = RSFormProHelper::getTranslatableProperties();
		$translations = RSFormProHelper::getTranslations('properties', $formId, $lang, 'id');
		$db           = $this->getDbo();

		foreach ($fields as $field)
		{
			if (!isset($params[$field])) continue;

			$reference_id = $componentId.'.'.$db->escape($field);

			$translation = (object) array(
                'form_id'       => $formId,
                'lang_code'     => $lang,
                'reference'     => 'properties',
                'reference_id'  => $reference_id,
                'value'         => $params[$field]
            );

			if (!isset($translations[$reference_id]))
			{
			    $db->insertObject('#__rsform_translations', $translation);
			}
			else
			{
			    $translation->id = $translations[$reference_id];
                $db->updateObject('#__rsform_translations', $translation, array('id'));
			}

			if (!$just_added && in_array($field, $properties))
			{
                unset($params[$field]);
            }
		}
	}

	public function getLang()
	{
		if (empty($this->_form))
		{
			$this->getForm();
		}

		return RSFormProHelper::getCurrentLanguage($this->_form->FormId);
	}

	public function getMappings()
	{
		if (empty($this->_mdata))
		{
			$db 	= Factory::getDbo();
			$formId	= Factory::getApplication()->input->getInt('formId');

			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__rsform_mappings'))
				->where($db->qn('formId') . ' = ' . $db->q($formId))
				->order($db->qn('ordering') . ' ' . $db->escape('asc'));

			$this->_mdata = $this->_getList($query);
		}

		return $this->_mdata;
	}

	public function getCalculations()
	{
		$formId	= Factory::getApplication()->input->getInt('formId');

		return RSFormProHelper::getCalculations($formId);
	}

	public function getConditions()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/conditions.php';

		$formId	= Factory::getApplication()->input->getInt('formId');
		$lang	= $this->getLang();

		return RSFormProConditions::getConditions($formId, $lang, null);
	}

	public function getEmails()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		if (!$formId)
		{
			return array();
		}

		$db 	= $this->getDbo();
		$query 	= $db->getQuery(true)
			->select($db->qn(array('id', 'to', 'subject', 'formId')))
			->from($db->qn('#__rsform_emails'))
			->where($db->qn('type') . ' = ' . $db->q('additional'))
			->where($db->qn('formId') . ' = ' . $db->q($formId));

		$emails = $this->_getList($query);
		if (!empty($emails))
		{
			$translations = RSFormProHelper::getTranslations('emails', $formId, Factory::getSession()->get('com_rsform.form.formId'.$formId.'.lang', Factory::getLanguage()->getDefault()));
			foreach ($emails as $id => $email)
			{
				if (isset($translations[$email->id.'.fromname']))
				{
					$emails[$id]->fromname = $translations[$email->id.'.fromname'];
				}

				if (isset($translations[$email->id.'.subject']))
				{
					$emails[$id]->subject = $translations[$email->id.'.subject'];
				}

				if (isset($translations[$email->id.'.message']))
				{
					$emails[$id]->message = $translations[$email->id.'.message'];
				}
			}
		}

		return $emails;
	}

	public function copyComponent($sourceComponentId, $toFormId)
	{
		$sourceComponentId 	= (int) $sourceComponentId;
		$toFormId 			= (int) $toFormId;
		$db 				= Factory::getDbo();

		$query = $db->getQuery(true)
			->select('c.*')
			->select('ct.CanBeDuplicated')
			->from($db->qn('#__rsform_components', 'c'))
			->join('left', $db->qn('#__rsform_component_types', 'ct') . ' ON (' . $db->qn('c.ComponentTypeId') . ' = ' . $db->qn('ct.ComponentTypeId') . ')')
			->where($db->qn('ComponentId').'='.$db->q($sourceComponentId));
		if ($component = $db->setQuery($query)->loadObject())
		{
			if (!$component->CanBeDuplicated)
			{
				if ($toFormId == $component->FormId)
				{
					throw new Exception(Text::_('COM_RSFORM_CANNOT_DUPLICATE_THIS_FIELD'));
				}
				else
				{
					// Check if the new form has this type of field already
					$query->clear()
						->select($db->qn('ComponentId'))
						->from($db->qn('#__rsform_components'))
						->where($db->qn('FormId') . ' = ' . $db->q($toFormId))
						->where($db->qn('ComponentTypeId') . ' = ' . $db->q($component->ComponentTypeId));

					if ($db->setQuery($query)->loadResult())
					{
						throw new Exception(Text::_('COM_RSFORM_CANNOT_COPY_THIS_FIELD_TO_FORM_ALREADY_EXISTS'));
					}
				}
			}
			// Get max ordering
			$query->clear()
				->select('MAX('.$db->qn('Order').')')
				->from($db->qn('#__rsform_components'))
				->where($db->qn('FormId').'='.$db->q($toFormId));
			$component->Order = (int) $db->setQuery($query)->loadResult() + 1;

			// Insert the new field
			$query->clear()
				->insert($db->qn('#__rsform_components'))
				->set($db->qn('FormId').'='.$db->q($toFormId))
				->set($db->qn('ComponentTypeId').'='.$db->q($component->ComponentTypeId))
				->set($db->qn('Order').'='.$db->q($component->Order))
				->set($db->qn('Published').'='.$db->q($component->Published));
			$db->setQuery($query)->execute();

			// Get the newly created field ID
			$newComponentId = $db->insertid();

			// Get the properties of the field so we can duplicate them
			$query->clear()
				->select('*')
				->from($db->qn('#__rsform_properties'))
				->where($db->qn('ComponentId').'='.$db->q($sourceComponentId));
			$properties = $db->setQuery($query)->loadObjectList();
			foreach ($properties as $property) {
				// Handle duplicated fields
				if ($property->PropertyName == 'NAME' && $toFormId == $component->FormId) {
					$property->PropertyValue .= '_copy';

					while (RSFormProHelper::componentNameExists($property->PropertyValue, $toFormId)) {
						$property->PropertyValue .= mt_rand(0,9);
					}
				}

				$query->clear()
					->insert('#__rsform_properties')
					->set($db->qn('ComponentId').'='.$db->q($newComponentId))
					->set($db->qn('PropertyName').'='.$db->q($property->PropertyName))
					->set($db->qn('PropertyValue').'='.$db->q($property->PropertyValue));
				$db->setQuery($query)->execute();
			}

			// Copy language
			$query->clear()
				->select('*')
				->from($db->qn('#__rsform_translations'))
				->where($db->qn('reference').'='.$db->q('properties'))
				->where($db->qn('reference_id').' LIKE '.$db->q($sourceComponentId.'.%'));
			$translations = $db->setQuery($query)->loadObjectList();
			foreach ($translations as $translation) {
				list($oldComponentId, $property) = explode('.', $translation->reference_id, 2);
				$reference_id = $newComponentId.'.'.$property;

				$query->clear()
					->insert('#__rsform_translations')
					->set($db->qn('form_id').'='.$db->q($toFormId))
					->set($db->qn('lang_code').'='.$db->q($translation->lang_code))
					->set($db->qn('reference').'='.$db->q('properties'))
					->set($db->qn('reference_id').'='.$db->q($reference_id))
					->set($db->qn('value').'='.$db->q($translation->value));

				$db->setQuery($query)->execute();
			}

			Factory::getApplication()->triggerEvent('onRsformComponentCopy', array('formId' => $component->FormId, 'id' => $sourceComponentId, 'newFormId' => $toFormId));

			return $newComponentId;
		}

		return false;
	}

	public function getFieldGroups()
	{
		$groups = array(
			'standard' => (object) array(
				'name' 		=> Text::_('RSFP_FORM_FIELDS'),
				'fields' 	=> array()
			),
			'multipage' => (object) array(
				'name' 		=> Text::_('RSFP_MULTIPAGE'),
				'fields' 	=> array()
			),
			'captcha' => (object) array(
				'name'		=> Text::_('RSFP_CAPTCHA'),
				'fields' 	=> array()
			),
			'advanced' => (object) array(
				'name' 		=> Text::_('RSFP_ADVANCED_FORM_FIELDS'),
				'fields' 	=> array()
			),
			'payment' => (object) array(
				'fields'	=> array()
			)
		);

		// Standard fields
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_TEXTBOX,
			'name' 	=> Text::_('RSFP_COMP_TEXTBOX'),
			'icon'  => 'rsficon rsficon-progress-full'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_TEXTAREA,
			'name' 	=> Text::_('RSFP_COMP_TEXTAREA'),
			'icon'  => 'rsficon rsficon-file-text'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_SELECTLIST,
			'name' 	=> Text::_('RSFP_COMP_DROPDOWN'),
			'icon'  => 'rsficon rsficon-caret-square-o-down'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_CHECKBOXGROUP,
			'name' 	=> Text::_('RSFP_COMP_CHECKBOX'),
			'icon'  => 'rsficon rsficon-check-square-o'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_RADIOGROUP,
			'name' 	=> Text::_('RSFP_COMP_RADIO'),
			'icon'  => 'rsficon rsficon-dot-circle-o'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_SUBMITBUTTON,
			'name' 	=> Text::_('RSFP_COMP_SUBMITBUTTON'),
			'icon'  => 'rsficon rsficon-square'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_PASSWORD,
			'name' 	=> Text::_('RSFP_COMP_PASSWORD'),
			'icon'  => 'rsficon rsficon-lock'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_FILEUPLOAD,
			'name' 	=> Text::_('RSFP_COMP_FILE'),
			'icon'  => 'rsficon rsficon-file-text-o'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_FREETEXT,
			'name' 	=> Text::_('RSFP_COMP_FREETEXT'),
			'icon'  => 'rsficon rsficon-sort-alphabetically'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_CALENDAR,
			'name' 	=> Text::_('RSFP_COMP_CALENDAR'),
			'icon'  => 'rsficon rsficon-calendar-o'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_JQUERY_CALENDAR,
			'name' 	=> Text::_('RSFP_COMP_JQUERY_CALENDAR'),
			'icon'  => 'rsficon rsficon-calendar'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_BUTTON,
			'name' 	=> Text::_('RSFP_COMP_BUTTON'),
			'icon'  => 'rsficon rsficon-square'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_HIDDEN,
			'name' 	=> Text::_('RSFP_COMP_HIDDEN'),
			'icon'  => 'rsficon rsficon-texture'
		);
		$groups['standard']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_TICKET,
			'name' 	=> Text::_('RSFP_COMP_TICKET'),
			'icon'  => 'rsficon rsficon-ticket'
		);

		// Multipage
		$groups['multipage']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_PAGEBREAK,
			'name' 	=> Text::_('RSFP_PAGE_BREAK'),
			'icon'  => 'rsficon rsficon-vertical_align_center'
		);

		// Captcha
		$formId = Factory::getApplication()->input->getInt('formId');
		$exists = RSFormProHelper::componentExists($formId, RSFORM_FIELD_CAPTCHA);
		$groups['captcha']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_CAPTCHA,
			'name' 	=> Text::_('RSFP_COMP_CAPTCHA'),
			'icon'  => 'rsficon rsficon-shield',
			'exists' => $exists ? $exists[0] : false
		);
		$exists = RSFormProHelper::componentExists($formId, RSFORM_FIELD_HASHCASH);
		$groups['captcha']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_HASHCASH,
			'name' 	=> Text::_('RSFP_COMP_HASHCASH'),
			'icon'  => 'rsficon rsficon-shield',
			'exists' => $exists ? $exists[0] : false
		);

		// Advanced
		$groups['advanced']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_PREVIEW,
			'name' 	=> Text::_('RSFP_COMP_PREVIEW'),
			'icon'  => 'rsficon rsficon-sort-alphabetically'
		);
		$groups['advanced']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_BIRTHDAY,
			'name' 	=> Text::_('RSFP_COMP_BIRTHDAY'),
			'icon'  => 'rsficon rsficon-birthday-cake'
		);
		$groups['advanced']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_GMAPS,
			'name' 	=> Text::_('RSFP_COMP_GMAP'),
			'icon'  => 'rsficon rsficon-map-marker'
		);
		$groups['advanced']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_RANGE_SLIDER,
			'name' 	=> Text::_('RSFP_COMP_RANGE_SLIDER'),
			'icon'  => 'rsficon rsficon-th-list'
		);
        $groups['advanced']->fields[] = (object) array(
            'id' 	=> RSFORM_FIELD_PHONENUMBER,
            'name' 	=> Text::_('RSFP_COMP_PHONENUMBER'),
            'icon'  => 'rsficon rsficon-phone1'
        );
		$groups['advanced']->fields[] = (object) array(
			'id' 	=> RSFORM_FIELD_SURVEYTABLE,
			'name' 	=> Text::_('RSFP_COMP_SURVEYTABLE'),
			'icon'  => 'rsficon rsficon-table'
		);

		Factory::getApplication()->triggerEvent('onRsformBackendAfterCreateFieldGroups', array(&$groups, $this));

		return $groups;
	}
}