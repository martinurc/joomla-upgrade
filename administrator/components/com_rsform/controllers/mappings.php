<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class RsformControllerMappings extends RsformController
{
	public function getTables()
	{
		$app    = Factory::getApplication();
		$model	= $this->getModel('mappings');
		$config	= $app->input->get('jform', array(), 'array');
		
		try
		{
			$tables = $model->getTables($config);

			echo json_encode(array('tables' => $tables));
		}
		catch (Exception $e)
		{
			echo json_encode(array('message' => $e->getMessage()));
		}
		
		$app->close();
	}
	
	public function getColumns()
	{
		try
		{
			$app    = Factory::getApplication();
			$cid    = $app->input->getInt('cid');
			$config	= $app->input->get('jform', array(), 'array');
			$type   = $app->input->get('type', 'set');
			$row    = null;
			
			if ($cid)
			{
				$row = Table::getInstance('RSForm_Mappings', 'Table');
				$row->load($cid);
			}

			echo RSFormProHelper::mappingsColumns($config, $type, $row);
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
		}
		
		$app->close();
	}
	
	public function save()
	{
		$app    = Factory::getApplication();
		$data   = $app->input->post->getArray(array(), null, 'raw');
		$config	= $app->input->get('jform', array(), 'array');
		$data   = array_merge($data, $config);

		unset($data['jform']);

		$model = $this->getModel('mappings');
		$model->save($data);

		Factory::getDocument()->addScriptDeclaration("window.opener.mappingsShow(); window.close();");
	}
	
	public function saveOrdering()
	{
		$db   = Factory::getDbo();
		$data = Factory::getApplication()->input->post->get('cid', array(), 'array');
		
		foreach ($data as $id => $val)
		{
			$query = $db->getQuery(true)
						->update($db->qn('#__rsform_mappings'))
						->set($db->qn('ordering') . '=' . $db->q($val))
						->where($db->qn('id') . '=' . $db->q($id));

			$db->setQuery($query)
			   ->execute();
		}
		
		Factory::getApplication()->close();
	}
	
	public function remove()
	{
		$input  = Factory::getApplication()->input;
		$model  = $this->getModel('mappings');
		$formId = $input->getInt('formId');
		
		$model->remove();
		
		$input->set('view', 	'forms');
		$input->set('layout', 	'edit_mappings');
		$input->set('tmpl', 	'component');
		$input->set('formId', 	$formId);
		
		parent::display();
		
		Factory::getApplication()->close();
	}
	
	public function showMappings()
	{
		$input  = Factory::getApplication()->input;
		$formId = $input->getInt('formId');
		
		$input->set('view', 	'forms');
		$input->set('layout', 	'edit_mappings');
		$input->set('tmpl', 	'component');
		$input->set('formId', 	$formId);
		
		parent::display();
		
		Factory::getApplication()->close();
	}
}