<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class SppagebuilderTableVersion extends Table
{
	/**
	 * The table constructor
	 *
	 * @param Joomla\Database\DatabaseDriver $db
	 */
	function __construct(&$db)
	{
		parent::__construct('#__sppagebuilder_versions', 'id', $db);
	}

	public function bind($array, $ignore = '')
	{
		if (isset($array['id']))
		{
			$date = Factory::getDate();
			$user = Factory::getUser();
			if (!$array['id'])
			{
				if (!(int) $array['created_on'])
				{
					$array['created_on'] = $date->toSql();
				}

				if (empty($array['created_by']))
				{
					$array['created_by'] = $user->get('id');
				}
			}
		}
		else
		{
			$date = Factory::getDate();
			$user = Factory::getUser();
			if (!isset($array['created_on']))
			{
				$array['created_on'] = $date->toSql();
			}

			if (empty($array['created_by']))
			{
				$array['created_by'] = $user->get('id');
			}
		}

		return parent::bind($array, $ignore);
	}
}
