<?php
/**
 * @version 3.0.0
 * @package RSForm! Pro
 * @copyright (C) 2007-2021 www.rsjoomla.com
 * @license GPL, https://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

class mod_rsformInstallerScript
{
	protected static $minJoomla = '3.10.0';
	protected static $minComponent = '3.0.0';

	public function preflight($type, $parent)
	{
		if ($type == 'uninstall')
		{
			return true;
		}

		try
		{
			if (!class_exists('\\Joomla\\CMS\\Version'))
			{
				throw new Exception(sprintf('Please upgrade to at least Joomla! %s before continuing!', static::$minJoomla));
			}
			$jversion = new \Joomla\CMS\Version();

			if (!$jversion->isCompatible(static::$minJoomla))
			{
				throw new Exception('Please upgrade to at least Joomla! ' . static::$minJoomla . ' before continuing!');
			}

			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rsform.php'))
			{
				throw new Exception('Please install the RSForm! Pro component before continuing.');
			}

			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/assets.php') || !file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/version.php'))
			{
				throw new Exception('Please upgrade RSForm! Pro to at least version ' . static::$minComponent . ' before continuing!');
			}

			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/version.php';

			if (!class_exists('RSFormProVersion') || version_compare((string) new RSFormProVersion, static::$minComponent, '<'))
			{
				throw new Exception('Please upgrade RSForm! Pro to at least version ' . static::$minComponent . ' before continuing!');
			}
		}
		catch (Exception $e)
		{
			if (class_exists('\Joomla\CMS\Factory'))
			{
				$app = \Joomla\CMS\Factory::getApplication();
			}
			elseif (class_exists('JFactory'))
			{
				$app = JFactory::getApplication();
			}

			if (!empty($app))
			{
				$app->enqueueMessage($e->getMessage(), 'error');
			}
			return false;
		}

		return true;
	}
}