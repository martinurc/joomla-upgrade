<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/surveytable.php';

class RSFormProFieldBootstrap4SurveyTable extends RSFormProFieldSurveyTable
{
	protected function getTableClasses()
	{
		$classes = parent::getTableClasses();
		$classes[] = 'table table-striped';

		return $classes;
	}
}