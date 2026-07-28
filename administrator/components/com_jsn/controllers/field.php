<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnControllerField extends JControllerForm
{
	public function addgroup()
	{
		parent::add();
		$this->setRedirect('index.php?option=com_jsn&view=field&layout=editgroup');
	}
	public function editgroup()
	{
		parent::edit();
		$id=$this->input->get('id');
		$this->setRedirect('index.php?option=com_jsn&view=field&layout=editgroup&id='.$id);
	}
	/**
	 * Method to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 */
	protected function allowAdd($data = array())
	{
		$user = JFactory::getUser();
		return ($user->authorise('core.create', 'com_jsn'));
	}

	/**
	 * Method to check if you can edit a record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		$user = JFactory::getUser();
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;

		// Since there is no asset tracking and no categories, revert to the component permissions.
		return parent::allowEdit($data, $key);

	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean	 True if successful, false otherwise and internal error is set.
	 *
	 * @since   3.1
	 */
	public function batch($model = null)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Field');

		// Preset the redirect
		$this->setRedirect('index.php?option=com_jsn&view=fields');

		return parent::batch($model);
	}
}
