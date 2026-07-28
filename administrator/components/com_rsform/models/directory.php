<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Filter\InputFilter;

class RsformModelDirectory extends ListModel
{
	public $_directory = null;

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'FormTitle',
				'FormName',
				'FormId',
				'state'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$this->setState('filter.search', 	$this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search'));
		$this->setState('filter.state', 	$this->getUserStateFromRequest($this->context.'.filter.state', 'filter_state'));

		// List state information.
		parent::populateState('FormId', 'asc');
	}

	protected function getListQuery()
	{
		$filter_search = $this->getState('filter.search', '');
		$filter_state  = $this->getState('filter.state');
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
			->select($db->qn('d.formId', 'DirectoryFormId'))
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

		$query->join('left', $db->qn('#__rsform_directory', 'd') . ' ON (' . $db->qn('f.FormId') . ' = ' . $db->qn('d.formId') . ')');

		if ($filter_state === '1')
		{
			$query->having('DirectoryFormId IS NOT NULL');
		}
		elseif ($filter_state === '0')
		{
			$query->having('DirectoryFormId IS NULL');
		}

		$query->order($db->qn($this->getSortColumn()) . ' ' . $db->escape($this->getSortOrder()));

		return $query;
	}

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsform.directory', 'directory', array('control' => 'jform', 'load_data' => false));
		if (empty($form))
		{
			return false;
		}

		$app = Factory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_rsform.edit.directory.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		$form->bind($data);

		return $form;
	}

	public function getForms()
	{
		return $this->getItems();
	}

	public function getFormTitle()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		$db     = $this->getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('FormTitle'))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));
		$title = $db->setQuery($query)->loadResult();

		$lang = RSFormProHelper::getCurrentLanguage($formId);
		if ($translations = RSFormProHelper::getTranslations('forms', $formId, $lang))
		{
			if (isset($translations['FormTitle']))
			{
				$title = $translations['FormTitle'];
			}
		}

		return $title;
	}

	public function getSortColumn()
	{
		return $this->getState('list.ordering', 'FormId');
	}

	public function getSortOrder()
	{
		return $this->getState('list.direction', 'ASC');
	}

	public function getItem()
	{
		return $this->getDirectory();
	}

	public function getDirectory()
	{
		if ($this->_directory === null)
		{
			$formId = Factory::getApplication()->input->getInt('formId');
			$table 	= Table::getInstance('RSForm_Directory', 'Table');

			$table->load($formId);

			if (!$table->formId) {
				$table->formId = $formId;
				$table->enablecsv = 0;
				$table->enablepdf = 0;
				$table->HideEmptyValues = 0;
				$table->ShowGoogleMap = 0;
				$table->ViewLayoutAutogenerate = 1;
				$table->ViewLayoutName = 'dir-inline';
			}

			if ($table->groups) {
				$registry = new Registry;
				$registry->loadString($table->groups);
				$table->groups = $registry->toArray();
			} else {
				$table->groups = array();
			}

			if ($table->DeletionGroups) {
				$registry = new Registry;
				$registry->loadString($table->DeletionGroups);
				$table->DeletionGroups = $registry->toArray();
			} else {
				$table->DeletionGroups = array();
			}

			$this->_directory = $table;

			if ($this->_directory->ViewLayoutAutogenerate)
			{
				$this->autoGenerateLayout();
			}
		}

		return $this->_directory;
	}

	public function save($data) {
		$table	= Table::getInstance('RSForm_Directory', 'Table');
		$app    = Factory::getApplication();
		$input	= $app->input;
		$db		= Factory::getDbo();

		if (isset($data['groups']) && is_array($data['groups']))
		{
			$registry = new Registry;
			$registry->loadArray($data['groups']);
			$data['groups'] = $registry->toString();
		}
		else
        {
            $data['groups'] = '';
        }

        if (isset($data['DeletionGroups']) && is_array($data['DeletionGroups']))
        {
            $registry = new Registry;
            $registry->loadArray($data['DeletionGroups']);
            $data['DeletionGroups'] = $registry->toString();
        }
        else
        {
            $data['DeletionGroups'] = '';
        }

		$query = $db->getQuery(true)
			->select($db->qn('formId'))
			->from($db->qn('#__rsform_directory'))
			->where($db->qn('formId') . ' = ' . $db->q($data['formId']));
		$isNew = !$db->setQuery($query)->loadResult();

		if (!$table->save($data))
		{
			$this->setError($table->getError());
			return false;
		}

		// Store directory fields
		$fields				= RSFormProHelper::getAllDirectoryFields($table->formId);
		$listingFields   	= $input->get('dirviewable',array(),'array');
		$searchableFields 	= $input->get('dirsearchable',array(),'array');
		$editableFields	  	= $input->get('direditable',array(),'array');
		$detailsFields	  	= $input->get('dirindetails',array(),'array');
		$csvFields		  	= $input->get('dirincsv',array(),'array');
		$cids	  		  	= $input->get('dircid',array(),'array');
		$orderingFields	  	= $input->get('dirorder',array(),'array');
		$sorts              = $input->get('dirsort', array(), 'array');

		// empty
        $query = $db->getQuery(true)
            ->delete($db->qn('#__rsform_directory_fields'))
            ->where($db->qn('formId') . ' = ' . $db->q($table->formId));

		$db->setQuery($query);
		$db->execute();

		foreach ($fields as $field) {
			$object = (object) array(
			    'formId'        => $table->formId,
			    'componentId'   => $field->FieldId,
                'viewable'      => (int) in_array($field->FieldId, $listingFields),
                'searchable'    => (int) in_array($field->FieldId, $searchableFields),
                'editable'      => (int) in_array($field->FieldId, $editableFields),
                'indetails'     => (int) in_array($field->FieldId, $detailsFields),
                'incsv'         => (int) in_array($field->FieldId, $csvFields),
                'ordering'      => $orderingFields[array_search($field->FieldId, $cids)],
                'sort'          => isset($sorts[$field->FieldId]) ? (int) $sorts[$field->FieldId] : 0,
            );

			$db->insertObject('#__rsform_directory_fields', $object);
		}

		$app->triggerEvent('onRsformDirectorySave', array('formId' => $data['formId'], 'isNew' => $isNew, 'data' => $data));

		return true;
	}

	public function getEmails()
	{
		$formId 	= Factory::getApplication()->input->getInt('formId');
		$db			= Factory::getDbo();
		$session 	= Factory::getSession();
		$lang 		= Factory::getLanguage();
		if (!$formId)
		{
			return array();
		}

		$query = $db->getQuery(true)
			->select($db->qn(array('id', 'to', 'subject', 'formId')))
			->from($db->qn('#__rsform_emails'))
			->where($db->qn('type') . ' = ' . $db->q('directory'))
			->where($db->qn('formId') . ' = ' . $db->q($formId));

		$emails = $db->setQuery($query)->loadObjectList();
		if (!empty($emails))
		{
			$translations = RSFormProHelper::getTranslations('emails', $formId, $session->get('com_rsform.form.formId'.$formId.'.lang', $lang->getDefault()));
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

	public function autoGenerateLayout() {
		$formId = $this->_directory->formId;
		$filter = InputFilter::getInstance();

		$layout = JPATH_ADMINISTRATOR.'/components/com_rsform/layouts/'.$filter->clean($this->_directory->ViewLayoutName, 'path').'.php';
		if (!file_exists($layout))
			return false;

		$headers	  = RSFormProHelper::getDirectoryStaticHeaders();
		$fields 	  = RSFormProHelper::getDirectoryFields($formId);
		$quickfields  = $this->getQuickFields();
		$imagefields  = $this->getImagesFields();

		$hideEmptyValues = $this->_directory->HideEmptyValues;
		$showGoogleMap = $this->_directory->ShowGoogleMap;

		$out = include $layout;

		if ($out != $this->_directory->ViewLayout && $this->_directory->formId) {
			// Clean it
			// Update the layout
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__rsform_directory'))
				->set($db->qn('ViewLayout').'='.$db->q($out))
				->where($db->qn('formId').'='.$db->q($this->_directory->formId));

			$db->setQuery($query);
			$db->execute();
		}

		$this->_directory->ViewLayout = $out;
	}

	protected function getStaticPlaceholder($header) {
		if ($header == 'DateSubmitted') {
			return '{global:date_added}';
		} else {
			return '{global:'.strtolower($header).'}';
		}
	}

	public function getQuickFields()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/quickfields.php';
		return RSFormProQuickFields::getFieldNames('all');
	}

	public function getImagesFields() {
		$cids	= array();
		$db     = $this->getDbo();
		$query	= $db->getQuery(true);
		$formId = Factory::getApplication()->input->getInt('formId');
		$fields = RSFormProHelper::getDirectoryFields($formId);

		if (!empty($fields)) {
			foreach ($fields as $field) {
				if ($field->indetails)
					$cids[] = $field->componentId;
			}
		}
		$cids = array_map('intval', $cids);

		if (!empty($cids)) {
			$query->clear()
				->select($db->qn('p.PropertyValue'))
				->from($db->qn('#__rsform_properties','p'))
				->join('LEFT',$db->qn('#__rsform_components','c').' ON '.$db->qn('p.ComponentId').' = '.$db->qn('c.ComponentId'))
				->join('LEFT',$db->qn('#__rsform_directory_fields','d').' ON '.$db->qn('d.ComponentId').' = '.$db->qn('c.ComponentId'))
				->where($db->qn('c.FormId').' = '.(int) $formId)
				->where($db->qn('p.PropertyName').' = '.$db->q('NAME'))
				->where($db->qn('c.ComponentId').' IN ('.implode(',',$cids).')')
				->where($db->qn('c.ComponentTypeId').' = ' . $db->q(RSFORM_FIELD_FILEUPLOAD))
				->where($db->qn('c.Published').' = 1')
				->order($db->qn('d.ordering'));

			$db->setQuery($query);

			return $db->loadColumn();
		}

		return array();
	}

	public function remove($pks) {
		if ($pks) {
			$pks = array_map('intval', $pks);
			$db  = $this->getDbo();

			$query = $db->getQuery(true)
                ->delete('#__rsform_directory')
                ->where($db->qn('formId') . ' IN (' . implode(',', $db->q($pks)) . ')');
			$db->setQuery($query);
			$db->execute();

            $query = $db->getQuery(true)
                ->delete('#__rsform_directory_fields')
                ->where($db->qn('formId') . ' IN (' . implode(',', $db->q($pks)) . ')');
            $db->setQuery($query);
			$db->execute();

            $query = $db->getQuery(true)
                ->delete('#__rsform_emails')
                ->where($db->qn('formId') . ' IN (' . implode(',', $db->q($pks)) . ')')
                ->where($db->qn('type') . ' = ' . $db->q('directory'));
            $db->setQuery($query);
            $db->execute();
		}

		return true;
	}

	public function getAllowedDateFields()
	{
		$types = array(RSFORM_FIELD_CALENDAR, RSFORM_FIELD_JQUERY_CALENDAR, RSFORM_FIELD_BIRTHDAY);

		Factory::getApplication()->triggerEvent('onRsformDefineDateFields', array(&$types));

		return $types;
	}
}