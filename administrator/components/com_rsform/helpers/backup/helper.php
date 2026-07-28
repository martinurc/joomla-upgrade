<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class RSFormProBackupHelper
{
	public static function qi($values) {
		static $db;
		if (!$db) {
			$db = Factory::getDbo();
		}
		
		$results = array();
		foreach ($values as $value) {
			$results[] = $db->q($value);
		}
		
		return implode(',', $results);
	}
	
	public static function getHash($id) {
		return substr(md5($id), 0, 10);
	}
}