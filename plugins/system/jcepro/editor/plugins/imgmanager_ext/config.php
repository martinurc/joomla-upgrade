<?php
/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

class WFImgManagerExtPluginConfig
{
    public static function getConfig(&$settings)
    {
        require_once __DIR__ . '/imgmanager_ext.php';

        $plugin = new WFImgManagerExtPlugin();

        $config = $plugin->getImageProperties();

        $config['filetypes'] = $plugin->getFileTypes();

        if ($plugin->getParam('inline_upload', 1) && $plugin->getParam('upload', 1)) {

            $config['upload'] = array(
                'max_size' => $plugin->getParam('max_size', 10240),
                'filetypes' => $plugin->getFileTypes(),
                'inline' => true,
            );
        }

        $config['always_include_dimensions'] = (bool) $plugin->getParam('always_include_dimensions', 1);

        $custom_classes = (array) $plugin->getParam('custom_classes', []);
        $config['custom_classes'] = array_filter($custom_classes);

        if ((int) $plugin->getParam('basic_dialog_filebrowser', 1) == 0) {
            $config['basic_dialog_filebrowser'] = false;
        }

        if ($plugin->getParam('basic_dialog', 0) == 1) {
            $config['basic_dialog'] = true;

            if (!isset($config['basic_dialog_filebrowser'])) {
                $config['filetypes'] = $plugin->getFileTypes();
            }
        }

        $settings['imgmanager_ext'] = $config;
    }
}
