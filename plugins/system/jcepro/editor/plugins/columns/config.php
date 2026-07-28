<?php

/**
 * @copyright     Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses
 */
class WFColumnsPluginConfig
{
    public static function getConfig(&$settings)
    {
        $wf = WFApplication::getInstance();

        $settings['columns_framework']  = $wf->getParam('columns.framework', '', '');
        $settings['columns_stack']      = $wf->getParam('columns.stack', 'medium', 'medium');
        $settings['columns_gap']        = $wf->getParam('columns.gap', 'medium', 'medium');
        $settings['columns_layout']     = $wf->getParam('columns.layout', '', '');

        $settings['columns_classes']    = $wf->getParam('columns.classes', '', '');

        $custom_classes = (array) $wf->getParam('columns.custom_classes', []);
        $settings['columns_custom_classes'] = array_filter($custom_classes);
    }
}