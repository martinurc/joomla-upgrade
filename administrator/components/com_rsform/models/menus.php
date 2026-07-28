<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;

class RsformModelMenus extends BaseDatabaseModel
{
	public $_data = null;
	public $_total = 0;
	public $_query = '';
	public $_pagination = null;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->_query = $this->_buildQuery();

		$mainframe = Factory::getApplication();

		// Get pagination request variables
		$limit 		= $mainframe->getUserStateFromRequest('com_rsform.menus.limit', 'limit', Factory::getApplication()->get('list_limit'), 'int');
		$limitstart = $mainframe->getUserStateFromRequest('com_rsform.menus.limitstart', 'limitstart', 0, 'int');

		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('com_rsform.menus.limit', 		$limit);
		$this->setState('com_rsform.menus.limitstart', 	$limitstart);
	}
	
	protected function _buildQuery()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__menu_types'))
			->order($db->qn('menutype') . ' ' . $db->escape('asc'));
		
		return $query;
	}
	
	public function getMenus()
	{		
		if (empty($this->_data))
		{
			$this->_data = $this->_getList($this->_query, $this->getState('com_rsform.menus.limitstart'), $this->getState('com_rsform.menus.limit'));
		}

		return $this->_data;
	}
	
	public function getTotal()
	{
		if (empty($this->_total))
		{
			$this->_total = $this->_getListCount($this->_query);
		}

		return $this->_total;
	}
	
	public function getPagination()
	{
		if (empty($this->_pagination))
		{
			$this->_pagination = new Pagination($this->getTotal(), $this->getState('com_rsform.menus.limitstart'), $this->getState('com_rsform.menus.limit'));
		}

		return $this->_pagination;
	}
	
	public function getFormTitle()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		$db     = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('FormTitle'))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));

		return $db->setQuery($query)->loadResult();
	}
}