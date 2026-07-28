<?php
/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

class WFIframePluginConfig
{
    public static function getConfig(&$settings)
    {
        require_once __DIR__ . '/iframe.php';
        $plugin = new WFIframePlugin();
        
        $elements = array('iframe');

        // remove iframe
        $settings['invalid_elements'] = array_diff($settings['invalid_elements'], $elements);

        // add the media plugin if needed
        if (!in_array('media', $settings['plugins'])) {
            $settings['plugins'][] = 'media';

            if (empty($settings['media_valid_elements'])) {
                $settings['media_valid_elements'] = $elements;
            }

            $settings['media_valid_elements'] = array_intersect($settings['media_valid_elements'], $elements);
        }

        $custom_classes = (array) $plugin->getParam('custom_classes', []);
        $custom_classes = array_filter($custom_classes);

        $settings['iframe'] = array(
            'attributes' => $plugin->getDefaultAttributes(),
            'custom_classes' => $custom_classes,
        );
    }
}
