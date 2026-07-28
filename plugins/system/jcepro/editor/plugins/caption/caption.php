<?php
/**
 * @package     JCE
 * @subpackage  Editor
*
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

class WFCaptionPlugin extends WFEditorPlugin
{
    public function __construct($config = array())
    {
        $config = array(
            'base_path' => __DIR__
        );

        parent::__construct($config);
    }
    
    public function display()
    {
        parent::display();

        $document = WFDocument::getInstance();
        $settings = $this->getSettings();

        $settings['custom_classes'] = $this->getParam('custom_classes', array());

        $document->addScriptDeclaration('CaptionDialog.settings=' . json_encode($settings) . ';');

        $tabs = WFTabs::getInstance(
            array(
                'base_path' => __DIR__
            )
        );

        // Add tabs
        $tabs->addTab('text', 1);
        $tabs->addTab('container', 1);

        // add link stylesheet
        $document->addStyleSheet(array(
            'plugins/caption/css/caption'
        ), 
        'pro');

        // add link scripts last
        $document->addScript(array(
            'plugins/caption/js/caption'
        ), 
        'pro');
    }
}
