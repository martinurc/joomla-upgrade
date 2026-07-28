<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnModelField extends JModelAdmin
{
	/**
	 * @var    string  The prefix to use with controller messages.
	 * @since  3.1
	 */
	protected $text_prefix = 'COM_JSN';

	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 * @see        JController
	 * @since      3.0.3
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		// Include Field Class
		foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
			require_once $filename;
		}
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   3.1
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			if ($record->published != -2)
			{
				return;
			}
			$user = JFactory::getUser();

			return parent::canDelete($record);
		}
	}

	/**
	 * Method to test whether a record can have its state changed.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   3.1
	 */
	protected function canEditState($record,$nameField = null)
	{
		$user = JFactory::getUser();

		// Prevent Delete and Unpublish Core Fields
		if(($record->core && $nameField!='profile' && $nameField!='search') && !($record->alias=='avatar' && ($nameField=='edit' || $nameField=='required' || $nameField=='register' || $nameField=='editbackend')) && !($record->alias=='secondname' && ($nameField=='required' || $nameField=='editbackend')))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED').' - '.JText::_('COM_JSN_VIEWCONFIG'));
			return false;
		}

		return parent::canEditState($record);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   3.1
	*/
	public function getTable($type = 'Field', $prefix = 'JsnTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication('administrator');

		$parentId = $app->input->getInt('parent_id');
		$this->setState('field.parent_id', $parentId);

		// Load the User state.
		$pk = $app->input->getInt('id');
		$this->setState($this->getName() . '.id', $pk);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_jsn');
		$this->setState('params', $params);
	}

	/**
	 * Method to get a field.
	 *
	 * @param   integer  $pk  An optional id of the object to get, otherwise the id from the model state is used.
	 *
	 * @return  mixed  Field data object on success, false on failure.
	 *
	 * @since   3.1
	 */
	public function getItem($pk = null)
	{
		if ($result = parent::getItem($pk))
		{

			// Prime required properties.
			if (empty($result->id))
			{
				$result->parent_id = $this->getState('field.parent_id');
			}

			// Convert the metadata field to an array.
			$registry = new JRegistry;
			$registry->loadString($result->metadata);
			$result->metadata = $registry->toArray();

			// Convert the images field to an array.
			$registry = new JRegistry;
			$registry->loadString($result->images);
			$result->images = $registry->toArray();

			// Convert the created and modified dates to local user time for display in the form.
			$tz = new DateTimeZone(JFactory::getApplication()->getCfg('offset'));

			if ((int) $result->created_time)
			{
				$date = new JDate($result->created_time);
				$date->setTimezone($tz);
				$result->created_time = $date->toSql(true);
			}
			else
			{
				$result->created_time = null;
			}

			if ((int) $result->modified_time)
			{
				$date = new JDate($result->modified_time);
				$date->setTimezone($tz);
				$result->modified_time = $date->toSql(true);
			}
			else
			{
				$result->modified_time = null;
			}
		}

		return $result;
	}

	/**
	 * Method to get the row form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since   3.1
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$extension = $this->getState('field');
		$jinput = JFactory::getApplication()->input;

		// Get the form.
		$form = $this->loadForm('com_jsn.field', 'field', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$user = JFactory::getUser();
		if (!$user->authorise('core.edit.state', 'com_jsn' . $jinput->get('id')))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		// Load Fields Params
		foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.xml') as $filename)
		{
			$form->loadFile($filename);
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   3.1
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_jsn.edit.field.data', array());
		
		if (empty($data))
		{
			$data = $this->getItem();
		}

		$version=new JVersion();
		if($version->RELEASE=='3.0') $this->preprocessDataJ30('com_jsn.field', $data);
		else $this->preprocessData('com_jsn.field', $data);

		return $data;
	}

	/**
	 * Method to preprocess the form.
	 *
	 * @param   JForm   $form    A JForm object.
	 * @param   mixed   $data    The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import.
	 *
	 * @return  void
	 *
	 * @see     JFormField
	 * @since   3.1
	 * @throws  Exception if there is an error in the form event.
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		// Trigger the default form events.
		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function save($data)
	{
		$dispatcher = JEventDispatcher::getInstance();
		$table = $this->getTable();
		$input = JFactory::getApplication()->input;
		$pk = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;

		// Include the content plugins for the on save events.
		JPluginHelper::importPlugin('content');

		// Load the row if saving an existing field.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		// Set the new parent id if parent id not matched OR while New/Save as Copy .
		if ($table->parent_id != $data['parent_id'] || $data['id'] == 0)
		{
			$table->setLocation($data['parent_id'], 'last-child');
		}

		if (isset($data['images']) && is_array($data['images']))
		{
			$registry = new JRegistry;
			$registry->loadArray($data['images']);
			$data['images'] = (string) $registry;
		}

		// Alter the title for save as copy
		if ($input->get('task') == 'save2copy')
		{
			list($title, $alias) = $this->generateNewTitle($data['parent_id'], $data['alias'], $data['title']);
			$data['title'] = $title;
			$data['alias'] = $alias;
		}

		// Remove Search flag for some field type
		$removeSearch = array('filetype','image','delimeter');
		if($isNew && in_array($data['type'],$removeSearch))
		{
			$data['search'] = 0;
		}

		// Block Modify of Alias, Type and Core / Block Modify of core parameter
		if(!$isNew)
		{
			unset($data['type']);
			unset($data['alias']);
			unset($data['core']);
			if($table->core && $table->alias != 'avatar' && $table->alias != 'secondname'){
				unset($data['required']);
				//unset($data['profile']);
				unset($data['register']);
				unset($data['edit']);
				unset($data['editbackend']);
			}
		}
		
		// Set Tos
		/*if($data['type']=='tos')
		{
			$data['required']=1;
			$data['register']=1;
			$data['profile']=0;
		}*/
		
		// Block Reserved Alias
		$reserved=array('id','name','email1','email2','password1','password2','params','privacy','facebook_id','google_id','twitter_id','linkedin_id','instagram_id','search');
		if($isNew && in_array( strtolower($data['alias']) , $reserved ))
		{
			$data['alias']='jsn_'.$data['alias'];
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());
			return false;
		}

		// Bind the rules.
		if (isset($data['rules']))
		{
			$rules = new JAccessRules($data['rules']);
			$table->setRules($rules);
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());
			return false;
		}

		// Trigger the onContentBeforeSave event.
		$result = $dispatcher->trigger($this->event_before_save, array($this->option . '.' . $this->name, &$table, $isNew));
		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());
			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());
			return false;
		}

		// Trigger the onContentAfterSave event.
		$dispatcher->trigger($this->event_after_save, array($this->option . '.' . $this->name, &$table, $isNew));
		
		// Create function for field
		if($isNew && $table->level==2){
			$class='Jsn'.ucfirst($table->type).'FieldHelper';
			if(class_exists($class)) $class::create($table->alias);
		}
		
		// Rebuild the path for the field:
		if (!$table->rebuildPath($table->id))
		{
			$this->setError($table->getError());
			return false;
		}

		// Rebuild the paths of the field's children:
		if (!$table->rebuild($table->id, $table->lft, $table->level, $table->path))
		{
			$this->setError($table->getError());

			return false;
		}

		$this->setState($this->getName() . '.id', $table->id);

		// Clear the cache
		$this->cleanCache();

		return true;
	}
	
	public function delete(&$pks){
		foreach($pks as $pk){
			$item=$this->getItem($pk);
			if($item->level==2){
				try{
					$class='Jsn'.ucfirst($item->type).'FieldHelper';
					if(class_exists($class)) $class::delete($item->alias);
				}
				catch (Exception $e) {}
			}
		}
		return parent::delete($pks);
	}

	/**
	 * Method rebuild the entire nested set tree.
	 *
	 * @return  boolean  False on failure or error, true otherwise.
	 *
	 * @since   3.1
	 */
	public function rebuild()
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if (!$table->rebuild())
		{
			$this->setError($table->getError());
			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array    $idArray    An array of primary key ids.
	 * @param   integer  $lft_array  The lft value
	 *
	 * @return  boolean  False on failure or error, True otherwise
	 *
	 * @since   3.1
	*/
	public function saveorder($idArray = null, $lft_array = null)
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if (!$table->saveorder($idArray, $lft_array))
		{
			$this->setError($table->getError());
			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $parent_id  The id of the parent.
	 * @param   string   $alias      The alias.
	 * @param   string   $title      The title.
	 *
	 * @return  array  Contains the modified title and alias.
	 *
	 * @since   3.1
	 */
	protected function generateNewTitle($parent_id, $alias, $title)
	{
		// Alter the title & alias
		$table = $this->getTable();
		while ($table->load(array('alias' => $alias, 'parent_id' => $parent_id)))
		{
			$title = ($table->title != $title) ? $title : JString::increment($title);
			$alias = JString::increment($alias, 'dash');
		}

		return array($title, $alias);
	}
	
	
	public function toggle(&$pks, $value = 1, $nameField)
	{
		$user = JFactory::getUser();
		$table = $this->getTable();
		$pks = (array) $pks;

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				
				if (!$this->canEditState($table,$nameField))
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED').' - '.JText::_('COM_JSN_VIEWCONFIG'));
					return false;
				}
			}
		}

		// Attempt to change the state of the records.
		if (!$table->toggle($pks, $value, $user->get('id'),$nameField))
		{
			$this->setError($table->getError());
			return false;
		}

		return true;
	}
	
	protected function preprocessDataJ30($context, &$data)
	{
		// Get the dispatcher and load the users plugins.
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		// Trigger the data preparation event.
		$results = $dispatcher->trigger('onContentPrepareData', array($context, $data));

		// Check for errors encountered while preparing the data.
		if (count($results) > 0 && in_array(false, $results, true))
		{
			$this->setError($dispatcher->getError());
		}
	}
	
	protected function batchAccess($value, $pks, $contexts)
	{
		$new_pk=array();
		foreach($pks as $key => $pk)
		{
			// Remove Core Fields
			if($pk>11) $new_pk[]=$pk;
		}
		parent::batchAccess($value, $new_pk, $contexts);
		return true;
	}
	
	public function batch($commands, $pks, $contexts)
	{
		parent::batch($commands, $pks, $contexts);
		if (!empty($commands['assetgroupview_id']))
		{
			if (!$this->batchAccessView($commands['assetgroupview_id'], $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}
		if (!empty($commands['fieldgroup_id']))
		{
			if (!$this->batchFieldGroup($commands['fieldgroup_id'], $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}
		return true;
	}
	
	protected function batchAccessView($value, $pks, $contexts)
	{
		if (empty($this->batchSet))
		{
			// Set some needed variables.
			$this->user = JFactory::getUser();
			$this->table = $this->getTable();
			$this->tableClassName = get_class($this->table);
			$this->contentType = new JUcmType;
			$this->type = $this->contentType->getTypeByTable($this->tableClassName);
		}

		foreach ($pks as $pk)
		{
			if ($this->user->authorise('core.edit', $contexts[$pk]))
			{
				$this->table->reset();
				$this->table->load($pk);
				$this->table->accessview = (int) $value;

				if (!empty($this->type))
				{
					$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
				}

				if (!$this->table->store())
				{
					$this->setError($this->table->getError());

					return false;
				}
			}
			else
			{
				$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

				return false;
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}
	
	protected function batchFieldGroup($value, $pks, $contexts)
	{
		if (empty($this->batchSet))
		{
			// Set some needed variables.
			$this->user = JFactory::getUser();
			$this->table = $this->getTable();
			$this->tableClassName = get_class($this->table);
			$this->contentType = new JUcmType;
			$this->type = $this->contentType->getTypeByTable($this->tableClassName);
		}

		foreach ($pks as $pk)
		{
			if ($this->user->authorise('core.edit', $contexts[$pk]))
			{
				$this->table->reset();
				$this->table->load($pk);
				if($this->table->parent_id==1) continue;
				$this->table->parent_id = (int) $value;
				$this->table->lft = 1000;
				$this->table->rgt = 1000;

				if (!empty($this->type))
				{
					$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
				}

				if (!$this->table->store())
				{
					$this->setError($this->table->getError());

					return false;
				}
			}
			else
			{
				$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

				return false;
			}
		}

		$this->rebuild();

		// Clean the cache
		$this->cleanCache();

		return true;
	}
	
	
}
