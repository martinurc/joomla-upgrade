<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;

/*
	preflight which is executed before install and update
	install
	update
	uninstall
	postflight which is executed after install and update
	*/
if (version_compare(JVERSION, 6, '>=')) {
	return new class () implements InstallerScriptInterface {

		public function install(InstallerAdapter $adapter): bool
		{
			// not used
			return true;
		}

		public function update(InstallerAdapter $adapter): bool
		{
			// not used
			return true;
		}

		public function uninstall(InstallerAdapter $adapter): bool
		{
			return CK_installer_uninstall($adapter);
		}

		public function preflight(string $type, InstallerAdapter $adapter): bool
		{
			return CK_installer_preflight($type, $adapter);
		}

		public function postflight(string $type, InstallerAdapter $adapter): bool
		{
			return CK_installer_postflight($type, $adapter);
		}
	};

} else {
	class com_cookiesckInstallerScript {

		function install($parent) {
			// not used
		}
		
		function update($parent) {
			// not used
		}

		function uninstall($parent) {
			return CK_installer_uninstall($parent);
		}

		function preflight($type, $parent) {
			return CK_installer_preflight($type, $parent);
		}

		// run on install and update
		function postflight($type, $parent) {
			jimport('joomla.installer.installer');
			return CK_installer_postflight($type, $parent);
		}
	}
}

function CK_installer_uninstall($parent) {
	$db = Factory::getDbo();
	// Check first that the plugin exist
	$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "cookiesck" AND `type` = "plugin"');
	$id = $db->loadResult();

	if($id)
	{
		return CK_installer_uninstallExtension('plugin', $id);
	}
	return true;
}

function CK_installer_preflight($type, $parent) {
	// disable the install on Joomla 4
	if (version_compare(JVERSION, '5', '<')) {
		Factory::getApplication()->enqueueMessage('This version of Cookies CK can not be installed on Joomla 4. Please use the version 3.7.4.', 'error');
		return false;
	}

	// check if a pro version already installed
	$xmlPath = JPATH_ROOT . '/administrator/components/com_cookiesck/cookiesck.xml';
	
	// if no file already exists
	if (! file_exists($xmlPath)) return true;

	$xmlData = CK_installer_getXmlData($xmlPath);
	$isProInstalled = ((int)$xmlData->ckpro);
	
	if ($isProInstalled) {
		throw new RuntimeException('Cookies CK Light cannot be installed over Cookies CK Pro. Please install Cookies CK Pro. To downgrade, please first uninstall Cookies CK Pro.');
		// return false;
	}
	$showMessage = false;

	// check if a v2 is already installed
	$xmlPath = JPATH_ROOT . '/plugins/system/cookiesck/cookiesck.xml';

	// if no file already exists
	if (file_exists($xmlPath)) {
		$xmlData = CK_installer_getXmlData($xmlPath);
		$version = ((int)$xmlData->version);

		if (version_compare($version, '3', '<')) {
			$showMessage = true;
		}
	// if not yes installed
	} else if (! file_exists($xmlPath)) {
		$showMessage = true;
	}

	if ($showMessage) {
		echo '<div class="alert alert-danger">ATTENTION : you must check the documentation and setup your cookies to make it work correctly.</div>';
		echo '<div class="alert alert-info"><a href="https://www.joomlack.fr/en/documentation/cookies-ck/326-how-to-use-cookies-ck" target="_blank">Click here to read the documentation.</a></div>';
	}

	return true;
}

function CK_installer_getXmlData($file) {
	if ( ! is_file($file))
	{
		return '';
	}

	$xml = simplexml_load_file($file);

	if ( ! $xml || ! isset($xml['version']))
	{
		return '';
	}

	return $xml;
}

// run on install and update
function CK_installer_postflight($type, $parent) {
	// install modules and plugins
	jimport('joomla.installer.installer');
	$db = Factory::getDbo();
	$status = array();
	$src_ext = dirname(__FILE__).'/administrator/extensions';

	// install the plugin
	$result = CK_installer_installExtension($src_ext.'/cookiesck');
	// auto enable the plugin
	$db->setQuery("UPDATE #__extensions SET enabled = '1' WHERE `element` = 'cookiesck' AND `type` = 'plugin'");
	$result = $db->execute();
	$status[] = array('name'=>'Cookies CK - Plugin','type'=>'plugin', 'result'=>$result);

	// disable the old update site
	$db->setQuery("UPDATE #__update_sites SET enabled = '0' WHERE `location` = 'http://update.joomlack.fr/plg_cookiesck_update.xml'");
	$result3 = $db->execute();
	// disable the old update site
	$db->setQuery("UPDATE #__update_sites SET enabled = '0' WHERE `location` = 'http://update.joomlack.fr/com_cookiesck_update.xml'");
	$result4 = $db->execute();

	foreach ($status as $statu) {
		if ($statu['result'] == true) {
			$alert = 'success';
			$icon = 'icon-ok';
			$text = 'Successful';
		} else {
			$alert = 'warning';
			$icon = 'icon-cancel';
			$text = 'Failed';
		}
		echo '<div class="alert alert-' . $alert . '"><i class="icon ' . $icon . '"></i>Installation and activation of the <b>' . $statu['type'] . ' ' . $statu['name'] . '</b> : ' . $text . '</div>';
	}

	return true;
}

function CK_installer_installExtension($path) {
	$installer = new Installer();
	$installer->setDatabase(Factory::getDbo());

	return $installer->install($path);
}

function CK_installer_uninstallExtension($type, $id) {
	$installer = new Installer();
	$installer->setDatabase(Factory::getDbo());

	return $installer->uninstall($type, $id);
}

