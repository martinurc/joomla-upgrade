<?php

/**
 * @package     JCE
 * @subpackage  Editors.Jce
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\JcePro\PluginTraits;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use WFBrowserHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles the onDisplay event for the JCE editor.
 *
 * @since  3.9.59
 */
trait MediaFieldTrait
{
    /**
     * Flag to set / check if media assests have been loaded
     *
     * @var boolean
     */
    private $mediaLoaded = false;

    /**
     * Flag to set / check if media assets should be loaded
     *
     * @var boolean
     */
    private $hasMedia = false;

    /**
     * Supported field types for media fields
     *
     * @var array
     */
    private $supportedFieldTypes = array('media', 'fcmedia', 'mediajce', 'extendedmedia');

    /**
     * Map of field names to field objects
     *
     * @var array
     */
    private $fieldMap = array();

    private function getMediaRedirectOptions()
    {
        static $options = array();

        $app = Factory::getApplication();

        $id = $app->input->get('fieldid', '');
        $mediatype = $app->input->getVar('mediatype', $app->input->getVar('view', 'images'));
        $context = $app->input->getVar('context', '');
        $plugin = $app->input->getCmd('plugin', '');
        $converted = $app->input->getInt('converted', 0);

        // get the media folder from the input if any
        $mediafolder = $app->input->getVar('mediafolder', '');

        $config = array(
            'element' => $id,
            'mediatype' => $mediatype,
            'context' => $context,
            'plugin' => $plugin,
            'converted' => $converted,
        );

        if ($mediafolder) {
            // normalize the path to remove leading and trailing slashes
            $mediafolder = trim($mediafolder, '/');
            // set the media folder in the config
            $config['mediafolder'] = $mediafolder;
        }

        if ($converted) {
            $path = $app->input->getVar('path', $app->input->getVar('folder', '')); // use folder for Joomla 3

            if ($path) {
                // a converted media field "path" only refers to the folder of an existing value
                // (a configured "Directory" is passed separately as a ":"-prefixed "mediafolder")
                $path = trim($path, '/');
                // set the path in the config
                $config['path'] = $path;
            }
        }

        $signature = md5(serialize($config));

        if (!isset($options[$signature])) {
            $options[$signature] = array(
                'url' => WFBrowserHelper::getMediaFieldUrl($config)
            );
        }

        if (empty($options[$signature]['url'])) {
            return false;
        }

        return $options[$signature];
    }

    private function redirectMedia()
    {
        $options = $this->getMediaRedirectOptions();

        if ($options && isset($options['url'])) {
            Factory::getApplication()->redirect($options['url']);
        }
    }

    private function isEditorEnabled()
    {
        return ComponentHelper::isEnabled('com_jce') && PluginHelper::isEnabled('editors', 'jce') && PluginHelper::isEnabled('system', 'jce');
    }

    private function canRedirectMedia()
    {
        $app = Factory::getApplication();

        // must have fieldid
        if (!$app->input->get('fieldid')) {
            return false;
        }

        // jce converted mediafield
        if ($app->input->getCmd('option') == 'com_jce' && $app->input->getCmd('task') == 'mediafield.display') {
            return true;
        }

        $params = ComponentHelper::getParams('com_jce');

        if ((int) $params->get('replace_media_manager', 1)) {
            $option = $app->input->getCmd('option', '');
            $asset = $app->input->getCmd('asset', '');

            // joomla media field
            if ($option == 'com_media' && ($asset == 'com_content' || $asset == 'com_flexicontent')) {
                // set converted flag
                $app->input->set('converted', 1);
                return true;
            }
        }

        return false;
    }

    public function onAfterRoute()
    {
        if (false == $this->isEditorEnabled()) {
            return false;
        }

        // merge params with component
        $componentParams = ComponentHelper::getParams('com_jce');
        $this->params->merge($componentParams);

        if ($this->canRedirectMedia() && $this->isEditorEnabled()) {
            // redirect to file browser
            $this->redirectMedia();
        }
    }

    /**
     * Process the Joomla Media Field
     *
     * @param JForm $form The form to be altered
     * @param mixed $data The associated data for the form
     *
     * @return bool
     *
     * @since   2.5.20
     */
    public function processMediaFieldForm($form, $data)
    {
        $app = Factory::getApplication();
        $docType = Factory::getDocument()->getType();

        // must be an html doctype
        if ($docType !== 'html') {
            return true;
        }

        // Update MediaField parameters
        if (strpos($form->getName(), 'com_fields.field') === 0) {
            $tmpData = new Registry($data);

            // only for JCE Media Fields!
            if ($tmpData->get('type', '') == 'mediajce') {
                $mediafield = JPATH_PLUGINS . '/system/jcepro/forms/mediajce.xml';

                if (is_file($mediafield)) {
                    if ($mediafield_xml = simplexml_load_file($mediafield)) {
                        $form->setField($mediafield_xml);
                    }
                }
            }
        }

        // editor not enabled
        if (false == $this->isEditorEnabled()) {
            return true;
        }

        // Get File Browser options
        $options = WFBrowserHelper::getMediaFieldOptions();

        // not enabled
        if (empty($options)) {
            return true;
        }

        $fields = $form->getFieldset();

        $form->addFieldPath(JPATH_PLUGINS . '/fields/mediajce/fields');

        foreach ($fields as $field) {
            $type = $field->getAttribute('type');

            if (!$type) {
                continue;
            }

            // check if field type is supported
            if (!in_array(strtolower($type), $this->supportedFieldTypes)) {
                continue;
            }

            $name = $field->getAttribute('name');

            // avoid processing twice
            if ($form->getFieldAttribute($name, 'class') && strpos($form->getFieldAttribute($name, 'class'), 'wf-media-input') !== false) {
                continue;
            }

            if ($type == 'media' || $type == 'fcmedia') {
                // media replacement disabled, skip...
                if ((bool) $options['convert'] === false) {
                    continue;
                }

                // don't convert directory only fields (Joomla 6+)
                if ((string) $field->types == 'directories') {
                    continue;
                }

                $group = (string) $field->group;
                $form->setFieldAttribute($name, 'type', 'mediajce', $group);
                $form->setFieldAttribute($name, 'converted', '1', $group);

                // set converted attribute flag instead of class attribute (extension conflict?)
                $form->setFieldAttribute($name, 'data-wf-converted', '1', $group);
            }

            $this->hasMedia = true;
        }

        return true;
    }

    /**
     * Process custom media fields
     *
     * @param   \stdClass    $field    The field.
     * @param   \DOMElement  $fieldset The fieldset parent node.
     * @param   Form        $form     The form.
     *
     * @return void
     */
    public function onCustomFieldsPrepareDom($field, \DOMElement $fieldset, Form $form)
    {
        // check if field type is supported
        if (!in_array(strtolower($field->type), $this->supportedFieldTypes)) {
            return;
        }

        // is the editor enabled?
        if (!$this->isEditorEnabled()) {
            return;
        }

        $this->hasMedia = true;

        // mediafields enabled?
        if (!WfBrowserHelper::isMediaFieldEnabled()) {
            $field->disabled = true;
            return;
        }

        $this->fieldMap[$field->name] = $field;
    }

    /**
     * Add data-path attribute to Media Field input elements storing the directory parameter
     *
     * @return void
     */
    public function onBeforeRender()
    {
        if ($this->hasMedia) {

            $options = WfBrowserHelper::getMediaFieldOptions();

            $document = Factory::getDocument();

            // Include jQuery
            HTMLHelper::_('jquery.framework');

            $document = Factory::getDocument();
            $document->addScript(Uri::root(true) . '/media/plg_system_jcepro/site/js/media.min.js', array('version' => 'auto'));
            // load core css files
            $document->addStyleSheet(Uri::root(true) . '/media/com_jce/site/css/media.min.css', array('version' => 'auto'));

            // no options set, return
            if (empty($options)) {
                return;
            }

            foreach ($this->fieldMap as $name => $field) {
                if ($field->type == 'mediajce' || $field->type == 'extendedmedia') {
                    continue;
                }

                $directory = trim((string) $field->fieldparams->get('directory', ''), '/');

                // normalize a local-* adapter scheme to a JCE path, preserving the adapter root, eg: local-images:/foo => images/foo
                if (strpos($directory, 'local-') === 0 && ($pos = strpos($directory, ':')) !== false) {
                    $adapter = substr($directory, strlen('local-'), $pos - strlen('local-'));
                    $sub = trim(substr($directory, $pos + 1), '/');
                    $directory = $sub !== '' ? $adapter . '/' . $sub : $adapter;
                }

                $options['mediafields'][$name] = array(
                    'directory' => $directory
                );
            }

            $document->addScriptOptions('plg_system_jce', $options, true);
        }
    }

    /**
     * Proxy function for PlgFieldsMediaJce::onCustomFieldsPrepareDom
     * Allows the JCE Pro System Plugin to edit the field before it is rendered
     *
     * @param  $field
     * @param  $fieldNode
     * @param Form $form
     * @return void
     */
    public function onWfCustomFieldsPrepareDom($field, $fieldNode, Form $form)
    {
        // is the field disabled? This value is set in the onCustomFieldsPrepareDom event based on the media options
        if (isset($field->disabled)) {
            return;
        }

        $form->addFieldPath(JPATH_PLUGINS . '/system/jcepro/fields');

        // Joomla 3 requires the fieldtype to be loaded
        FormHelper::loadFieldType('extendedmedia', false);

        // Set type as extendedmedia. This is the default type for JCE Pro and includes support for a simple media field
        $fieldNode->setAttribute('type', 'extendedmedia');

        // set extendedmedia flag
        if ((int) $field->fieldparams->get('extendedmedia', 0) == 1) {
            $fieldNode->setAttribute('data-extendedmedia', '1');
        }

        // allow for legacy media support, which removes the Description field
        if ((int) $field->fieldparams->get('legacymedia', 0) == 1) {
            $fieldNode->setAttribute('type', 'mediajce');
        }
    }
}
