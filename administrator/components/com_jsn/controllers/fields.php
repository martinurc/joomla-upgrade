<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnControllerFields extends JControllerAdmin
{
	/**
	 * Constructor.
	 *
	 * @param   array An optional associative array of configuration settings.
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unpublish',	'publish');
		$this->registerTask('required_unpublish',	'required_publish');
		$this->registerTask('profile_unpublish',	'profile_publish');
		$this->registerTask('register_unpublish',	'register_publish');
		$this->registerTask('search_unpublish',	'search_publish');
		$this->registerTask('edit_unpublish',	'edit_publish');
		$this->registerTask('editbackend_unpublish',	'editbackend_publish');
	}
	
	public function required_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('required_publish' => 1, 'required_unpublish' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NO_FIELDS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model	= $this->getModel();

			// Change the state of the records.
			if (!$model->toggle($ids, $value, 'required'))
			{
				JFactory::getApplication()->enqueueMessage($model->getError());
			} else {
				if ($value == 1)
				{
					$ntext = 'COM_JSN_N_FIELDS_REQUIRED';
				} else {
					$ntext = 'COM_JSN_N_FIELDS_UNREQUIRED';
				}
				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_jsn&view=fields');
	}
	public function profile_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('profile_publish' => 1, 'profile_unpublish' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NO_FIELDS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model	= $this->getModel();

			// Change the state of the records.
			if (!$model->toggle($ids, $value, 'profile'))
			{
				JFactory::getApplication()->enqueueMessage($model->getError());
			} else {
				if ($value == 1)
				{
					$ntext = 'COM_JSN_N_FIELDS_PROFILE';
				} else {
					$ntext = 'COM_JSN_N_FIELDS_UNPROFILE';
				}
				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_jsn&view=fields');
	}
	public function register_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('register_publish' => 1, 'register_unpublish' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NO_FIELDS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model	= $this->getModel();

			// Change the state of the records.
			if (!$model->toggle($ids, $value, 'register'))
			{
				JFactory::getApplication()->enqueueMessage($model->getError());
			} else {
				if ($value == 1)
				{
					$ntext = 'COM_JSN_N_FIELDS_REGISTER';
				} else {
					$ntext = 'COM_JSN_N_FIELDS_UNREGISTER';
				}
				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_jsn&view=fields');
	}
	public function edit_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('edit_publish' => 1, 'edit_unpublish' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NO_FIELDS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model	= $this->getModel();

			// Change the state of the records.
			if (!$model->toggle($ids, $value, 'edit'))
			{
				JFactory::getApplication()->enqueueMessage($model->getError());
			} else {
				if ($value == 1)
				{
					$ntext = 'COM_JSN_N_FIELDS_EDIT';
				} else {
					$ntext = 'COM_JSN_N_FIELDS_UNEDIT';
				}
				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_jsn&view=fields');
	}

	public function editbackend_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('editbackend_publish' => 1, 'editbackend_unpublish' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NO_FIELDS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model	= $this->getModel();

			// Change the state of the records.
			if (!$model->toggle($ids, $value, 'editbackend'))
			{
				JFactory::getApplication()->enqueueMessage($model->getError());
			} else {
				if ($value == 1)
				{
					$ntext = 'COM_JSN_N_FIELDS_EDIT';
				} else {
					$ntext = 'COM_JSN_N_FIELDS_UNEDIT';
				}
				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_jsn&view=fields');
	}
	
	public function search_publish()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('search_publish' => 1, 'search_unpublish' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NO_FIELDS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model	= $this->getModel();

			// Change the state of the records.
			if (!$model->toggle($ids, $value, 'search'))
			{
				JFactory::getApplication()->enqueueMessage($model->getError());
			} else {
				if ($value == 1)
				{
					$ntext = 'COM_JSN_N_FIELDS_SERCHABLE';
				} else {
					$ntext = 'COM_JSN_N_FIELDS_UNSERCHABLE';
				}
				$this->setMessage(JText::plural($ntext, count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_jsn&view=fields');
	}

	/**
	 * Proxy for getModel
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 *
	 * @return  JModelLegacy  The model.
	 * @since   3.1
	 */
	public function getModel($name = 'Field', $prefix = 'JsnModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	/**
	 * Rebuild the nested set tree.
	 *
	 * @return  boolean  False on failure or error, true on success.
	 *
	 * @since   3.1
	 */
	public function rebuild()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$extension = $this->input->get('extension');
		$this->setRedirect(JRoute::_('index.php?option=com_jsn&view=fields', false));

		$model = $this->getModel();

		if ($model->rebuild()) {
			// Rebuild succeeded.
			$this->setMessage(JText::_('COM_JSN_REBUILD_SUCCESS'));
			return true;
		} else {
			// Rebuild failed.
			$this->setMessage(JText::_('COM_JSN_REBUILD_FAILURE'));
			return false;
		}
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}
}
