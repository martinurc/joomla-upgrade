<?php
/**
 * @package RSForm!Pro
 * @copyright (C) 2007-2023 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class plgPagecacheRsform extends CMSPlugin
{
    public function onPageCacheIsExcluded()
    {
		if (class_exists('RSFormProHelper'))
		{
			return RSFormProHelper::$formShown === true;
		}

		return false;
    }
}