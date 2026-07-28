<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class Pkg_EventbookingInstallerScript
{
	/**
	 * Minimum PHP version
	 */
	private const MIN_PHP_VERSION = '7.2.0';

	/**
	 * Minimum Joomla version
	 */
	private const MIN_JOOMLA_VERSION = '3.9.0';

	/**
	 * Minimum Events Booking version to allow update
	 */
	private const MIN_EVENTS_BOOKING_VERSION = '3.7.0';

	/**
	 * The original version, use for update process
	 *
	 * @var string
	 */
	protected $installedVersion = '3.7.0';

	/**
	 * Perform basic system requirements check before installing the package
	 *
	 * @param   string    $type
	 * @param   JAdapter  $parent
	 *
	 * @return bool
	 */
	public function preflight($type, $parent)
	{
		if (version_compare(JVERSION, self::MIN_JOOMLA_VERSION, '<'))
		{
			Factory::getApplication()->enqueueMessage(
				'Cannot install Events Booking in a Joomla! release prior to ' . self::MIN_JOOMLA_VERSION,
				'error'
			);

			return false;
		}

		if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<'))
		{
			Factory::getApplication()->enqueueMessage(
				'Events Booking requires PHP ' . self::MIN_PHP_VERSION . '+ to work. Please contact your hosting provider, ask them to update PHP version for your hosting account.',
				'error'
			);

			return false;
		}

		$this->getInstalledVersion();

		if (version_compare($this->installedVersion, self::MIN_EVENTS_BOOKING_VERSION, '<'))
		{
			Factory::getApplication()->enqueueMessage(
				'Update from older version than ' . self::MIN_EVENTS_BOOKING_VERSION . ' is not supported! You need to update to version 3.17.6 first. Please contact support to get that old Events Booking version.',
				'error'
			);

			return false;
		}

		if (version_compare($this->installedVersion, '4.0.0', '<'))
		{
			$installer = new Installer;

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$plugins = [
				['eventbooking', 'spout'],
			];

			foreach ($plugins as $plugin)
			{
				$query->clear()
					->select('extension_id')
					->from('#__extensions')
					->where($db->quoteName('folder') . ' = ' . $db->quote($plugin[0]))
					->where($db->quoteName('element') . ' = ' . $db->quote($plugin[1]));
				$db->setQuery($query);
				$id = $db->loadResult();

				if ($id)
				{
					try
					{
						$installer->uninstall('plugin', $id, 0);
					}
					catch (\Exception $e)
					{
					}
				}
			}
		}
	}

	/**
	 * Finalize package installation
	 *
	 * @param   string    $type
	 * @param   JAdapter  $parent
	 *
	 * @return bool
	 */
	public function postflight($type, $parent)
	{
		// Do not perform redirection anymore if installed version is greater than or equal 3.8.3
		if (strtolower($type) == 'install' || version_compare($this->installedVersion, '3.8.3', '>='))
		{
			return true;
		}

		$app = Factory::getApplication();
		$app->setUserState('com_installer.redirect_url', 'index.php?option=com_eventbooking&task=update.update&install_type=' . strtolower($type));
		$app->input->set('return', base64_encode('index.php?option=com_eventbooking&task=update.update&install_type=' . strtolower($type)));
	}

	/**
	 * Get installed version of the component
	 *
	 * @return void
	 */
	private function getInstalledVersion()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('manifest_cache')
			->from('#__extensions')
			->where($db->quoteName('element') . ' = ' . $db->quote('com_eventbooking'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'));
		$db->setQuery($query);
		$manifestCache = $db->loadResult();

		if ($manifestCache)
		{
			$manifest = json_decode($manifestCache);

			$this->installedVersion = $manifest->version;
		}
	}
}