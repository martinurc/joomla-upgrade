<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class RsformModelRsform extends BaseDatabaseModel
{
	public $params;
	
	public function __construct()
	{
		parent::__construct();

		$this->params = Factory::getApplication()->getParams('com_rsform');
	}

	public function getFormId()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		return $formId ? $formId : $this->params->get('formId');
	}
	
	public function getParams()
	{
		return $this->params;
	}
}