<?php
/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

class WFTemplateManagerPluginConfig
{
    public static function getConfig(&$settings)
    {
        $wf = WFApplication::getInstance();

        $config = array();

        $config['selected_content_classes'] = $wf->getParam('templatemanager.selected_content_classes', '');
        $config['cdate_classes'] = $wf->getParam('templatemanager.cdate_classes', 'cdate creationdate', 'cdate creationdate');
        $config['mdate_classes'] = $wf->getParam('templatemanager.mdate_classes', 'mdate modifieddate', 'mdate modifieddate');
        $config['cdate_format'] = $wf->getParam('templatemanager.cdate_format', '%m/%d/%Y : %H:%M:%S', '%m/%d/%Y : %H:%M:%S');
        $config['mdate_format'] = $wf->getParam('templatemanager.mdate_format', '%m/%d/%Y : %H:%M:%S', '%m/%d/%Y : %H:%M:%S');

        $contentUrl = $wf->getParam('templatemanager.content_url', '');

        // check the url is valid and the file exists
        if ($contentUrl) {
            $contentUrl = trim($contentUrl);

            if (strpos($contentUrl, '://') === false && strpos($contentUrl, 'index.php') === false) {
                // remove leading and trailing slashes
                $contentUrl = trim($contentUrl, '/');
                
                if (!file_exists(JPATH_ROOT . '/' . $contentUrl)) {
                    $contentUrl = '';
                }
            }

            if ($contentUrl) {
                $config['content_url'] = $contentUrl;
            }
        }

        require_once __DIR__ . '/templatemanager.php';

        $plugin = new WFTemplateManagerPlugin();

        $config['replace_values'] = $plugin->replaceValuesToArray();

        $config['list'] = (bool) $wf->getParam('templatemanager.template_list', 1);

        $config['dialog'] = (bool) $wf->getParam('templatemanager.template_dialog', 1);

        if ($plugin->getParam('inline_upload', 0)) {
            $config['upload'] = array(
                'max_size' => $plugin->getParam('max_size', 10240),
                'filetypes' => $plugin->getFileTypes(),
                'inline' => true,
            );
        }

        if ($plugin->getParam('text_editor', 0)) {
            $config['text_editor'] = 1;
        }

        $settings['templatemanager'] = $config;
    }
}
