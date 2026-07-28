<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsformModelCalculation extends AdminModel
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsform.calculation', 'calculation', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function getItem($pk = null)
	{
		if ($pk === null)
		{
			$pk = Factory::getApplication()->input->getInt('cid');
		}

		return parent::getItem($pk);
	}

	protected function loadFormData()
	{
		$app = Factory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_rsform.edit.calculation.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		if (empty($data->formId))
		{
			$data->formId = $this->getFormId();
		}

		return $data;
	}

	public function getFormId()
	{
		return Factory::getApplication()->input->getInt('formId');
	}

	public function getTable($type = 'Rsform_Calculations', $prefix = 'Table', $options = array())
	{
		return parent::getTable($type, $prefix, $options);
	}

	public function getQuickfields()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/quickfields.php';

		return RSFormProQuickFields::getFieldNames('all');
	}

	public function save($data)
	{
		$row   = $this->getTable();
		$app   = Factory::getApplication();
		$saved = $row->save($data);

		if ($saved)
		{
			$app->enqueueMessage(Text::_('RSFP_CHANGES_SAVED'));
			return $row;
		}
		else
		{
			$app->enqueueMessage($row->getError(), 'error');
			return false;
		}
	}
}