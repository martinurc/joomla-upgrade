<?php
/**
 * @package   acymailing
 * @copyright Copyright (c)2009-2024 Acyba SAS
 * @license   GNU General Public License version 3, or later
 */

use AcyMailing\Helpers\UpdateHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

defined('_JEXEC') or die();

class Pkg_AcymailingInstallerScript extends InstallerScript
{
    public function preflight($type, $parent)
    {
        if (!parent::preflight($type, $parent)) {
            return false;
        }

        if (!in_array($type, ['install', 'update'])) {
            return true;
        }

        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $app = Factory::getApplication();
            $app->enqueueMessage('This version of AcyMailing requires at least PHP 7.4.0, it is time to update the PHP version of your server!', 'error');

            return false;
        }

        $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
        if (version_compare($jversion, '3.0.0', '<')) {
            $app = Factory::getApplication();
            $app->enqueueMessage('This version of AcyMailing requires at least Joomla 3, please upgrade your website first!', 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        if (in_array($type, ['install', 'update'])) {
            $this->installAcym();
            $this->addUpdateSite();

            if ($type === 'install') {
                $this->activePlugins();
            }
        }

        return true;
    }

    public function uninstall($parent)
    {
        ?>
		AcyMailing successfully uninstalled.<br /><br />
		If you want to completely uninstall AcyMailing and remove its data, run the following query on your database manager:
		<br /><br />
        <?php

        $tables = [
            'mail_archive',
            'mailbox_action',
            'custom_zone',
            'mail_override',
            'followup_has_mail',
            'followup',
            'segment',
            'form',
            'plugin',
            'action',
            'condition',
            'history',
            'rule',
            'user_has_field',
            'field',
            'url_click',
            'url',
            'user_stat',
            'mail_stat',
            'queue',
            'mail_has_list',
            'tag',
            'step',
            'automation',
            'user_has_list',
            'campaign',
            'list',
            'mail',
            'configuration',
            'user',
        ];

        $db = Factory::getDbo();
        $prefix = $db->getPrefix().'acym_';
        echo 'DROP TABLE '.$prefix.implode(', '.$prefix, $tables).';';

        ?>
		<br /><br />
		If you don't do this, you will be able to install AcyMailing again without losing your data.<br />
		Please note that you don't have to uninstall AcyMailing to install a new version, simply install it over the current version.<br /><br />
        <?php
    }

    private function installAcym()
    {
        try {
            $this->loadAcyMailingLibrary();
        } catch (Exception $e) {
            echo 'Initialization error, please re-install';

            return;
        }

        // The installation steps may take a while when updating from an old version
        acym_increasePerf();

        $updateHelper = new UpdateHelper();
        $updateHelper->installTables();
        $updateHelper->addPref();
        $updateHelper->updatePref();
        acym_config(true);
        $updateHelper->updateSQL();
        $updateHelper->checkDB();

        $updateHelper->installList();
        $updateHelper->installNotifications();

        if ($updateHelper->firstInstallation) {
            $updateHelper->installTemplates();
            $updateHelper->installDefaultAutomations();
        }

        $updateHelper->deleteNewSplashScreenInstall();

        $updateHelper->installFields();
        $updateHelper->installLanguages();
        $updateHelper->installBackLanguages();
        $updateHelper->installBounceRules();
        $updateHelper->installAddons();
        $updateHelper->installOverrideEmails();
        $updateHelper->updateAddons();

        $config = acym_config();
        $config->save(['installcomplete' => 1]);
    }

    private function addUpdateSite(): void
    {
        if (!$this->loadAcyMailingLibrary()) {
            return;
        }

        $extensionId = acym_loadResult(
            'SELECT `extension_id` 
            FROM #__extensions 
            WHERE `element` = "pkg_acymailing" 
                AND type = "package"'
        );

        if (empty($extensionId)) {
            return;
        }

        $acymailingExtensions = acym_loadResultArray(
            'SELECT `extension_id` 
            FROM #__extensions 
            WHERE `package_id` = '.intval($extensionId)
        );

        if (empty($acymailingExtensions)) {
            $acymailingExtensions = [];
        }
        $acymailingExtensions[] = $extensionId;

        // 1 - Clear existing updates on AcyMailing extensions
        acym_query('DELETE FROM #__updates WHERE extension_id IN ('.implode(',', $acymailingExtensions).')');
        acym_query(
            'DELETE update_sites 
            FROM #__update_sites AS update_sites 
            JOIN #__update_sites_extensions AS update_sites_extensions 
                ON update_sites.update_site_id = update_sites_extensions.update_site_id
            WHERE update_sites_extensions.extension_id IN ('.implode(',', $acymailingExtensions).')'
        );
        acym_query('DELETE FROM #__update_sites_extensions WHERE extension_id IN ('.implode(',', $acymailingExtensions).')');


        // 2 - Add the new update XML
        $updateSiteDefinition = new \stdClass();
        $updateSiteDefinition->enabled = 1;
        $updateSiteDefinition->type = 'extension';
        $updateSiteDefinition->name = 'AcyMailing';
        $updateSiteDefinition->location = ACYM_UPDATEME_API_URL.'public/updatexml/component?extension=acymailing&cms=joomla&version=latest&level=enterprise&type=package';

        //__START__essential_
        if (acym_level(ACYM_ESSENTIAL)) {
            $updateSiteDefinition->location .= '&website='.urlencode(ACYM_LIVE);
        }
        //__END__essential_

        $updateSiteId = acym_insertObject('#__update_sites', $updateSiteDefinition);
        if (empty($updateSiteId)) {
            return;
        }

        acym_query(
            'INSERT IGNORE INTO #__update_sites_extensions (update_site_id, extension_id) 
            VALUES ('.intval($updateSiteId).','.intval($extensionId).')'
        );
    }

    private function activePlugins()
    {
        $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
        $method = version_compare($jversion, '4.0.0', '>=') ? 'execute' : 'query';

        $db = Factory::getDbo();
        $db->setQuery(
            'UPDATE `#__extensions` 
            SET enabled = 1 
            WHERE type = "plugin" 
              AND element IN ("acymtriggers", "jceacym")'
        );
        $db->$method();
    }

    private function loadAcyMailingLibrary(): bool
    {
        $ds = DIRECTORY_SEPARATOR;

        if (!include_once rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php') {
            return false;
        }

        return true;
    }
}
