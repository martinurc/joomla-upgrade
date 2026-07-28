<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

global $_FIELDTYPES;
$_FIELDTYPES['text'] = 'COM_JSN_FIELDTYPE_TEXT';

class JsnTextFieldHelper
{
    /**
     * Asegura que el patrón regex tenga delimitadores válidos para Joomla 5 RegexRule
     */
    private static function formatJsnRegex($pattern)
    {
        $pattern = trim((string) $pattern);

        if (empty($pattern)) {
            return '';
        }

        // Si la regex no empieza y termina con delimitadores comunes (/ # ~)
        if (!preg_match('/^([\/#~]).*\1[a-z]*$/i', $pattern)) {
            return '/' . str_replace('/', '\/', $pattern) . '/';
        }

        return $pattern;
    }

    public static function create($alias)
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " ADD " . $db->quoteName($alias) . " VARCHAR(255) NULL DEFAULT ''";
        $db->setQuery($query);
        $db->execute();
    }

    public static function delete($alias)
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " DROP COLUMN " . $db->quoteName($alias);
        $db->setQuery($query);
        $db->execute();
    }

    public static function getXml($item)
    {
        if (file_exists(JPATH_SITE . '/components/com_jsn/helpers/helper.php')) {
            require_once JPATH_SITE . '/components/com_jsn/helpers/helper.php';
        }

        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $option = $input->get('option', '');
        $view   = $input->get('view', 'profile');
        $layout = $input->get('layout', '');

        $hideTitle = ($item->params->get('hidetitle', 0) && $view == 'profile' && $option == 'com_jsn') || 
                     ($item->params->get('hidetitleedit', 0) && ($layout == 'edit' || $view == 'registration'));

        if ($view == 'profile' && $option == 'com_jsn' && $item->params->get('titleprofile', '') != '') {
            $item->title = $item->params->get('titleprofile', '');
        }

        $defaultvalue = ($item->params->get('text_defaultvalue', '') != '' ? 'default="' . JsnHelper::xmlentities($item->params->get('text_defaultvalue', '')) . '"' : '');
        $maxlength    = ($item->params->get('text_maxlength', '') != '' ? 'maxlength="' . $item->params->get('text_maxlength', '') . '"' : '');
        $placeholder  = ($item->params->get('text_placeholder', '') != '' ? 'hint="' . JsnHelper::xmlentities($item->params->get('text_placeholder', '')) . '"' : '');

        // Formatear regex con delimitadores compatibles con Joomla 5
        $rawRegex = ($item->params->get('text_regex', '') != 'custom') ? $item->params->get('text_regex', '') : $item->params->get('text_customregex', '');
        $cleanRegex = self::formatJsnRegex($rawRegex);

        if (!empty($cleanRegex)) {
            $regex = 'class="validate-pattern ' . $item->params->get('field_cssclass', '') . '" validate="regex" validate_regex="' . htmlspecialchars($cleanRegex, ENT_QUOTES, 'UTF-8') . '" pattern="' . htmlspecialchars($cleanRegex, ENT_QUOTES, 'UTF-8') . '"';
        } else {
            $regex = 'class="' . $item->params->get('field_cssclass', '') . '"';
        }

        if ($item->params->get('field_readonly', '') == 1 && $app->isClient('site')) {
            $readonly = 'readonly="true"';
        } elseif ($item->params->get('field_readonly', '') == 2 && $view != 'registration' && $app->isClient('site')) {
            $readonly = 'readonly="true"';
        } else {
            $readonly = '';
        }

        $type = 'textfull';
        $xml  = '';

        $xml .= '
            <field
                name="' . $item->alias . '"
                type="' . $type . '"
                id="' . $item->alias . '"
                label="' . ($hideTitle ? JsnHelper::xmlentities('<span class="no-title">' . Text::_($item->title) . '</span>') : JsnHelper::xmlentities($item->title)) . '"
                description="' . JsnHelper::xmlentities($item->description) . '"
                size="30"
                ' . $defaultvalue . '
                ' . $maxlength . '
                ' . $placeholder . '
                ' . $regex . '
                ' . $readonly . '
                required="' . ($item->required ? ($item->required == 2 ? 'admin' : 'frontend') : 'false') . '"
                message-regex="' . JsnHelper::xmlentities($item->params->get('text_messageregex', '')) . '"
            />
        ';

        return $xml;
    }

    public static function loadData($field, $user, &$data)
    {
        $alias = $field->alias;
        if (isset($user->$alias)) {
            $data->$alias = $user->$alias;
        }
    }

    public static function storeData($field, $data, &$storeData)
    {
        $alias = $field->alias;
        if (isset($data[$alias])) {
            $storeData[$alias] = $data[$alias];
        }
    }

    public static function getSearchInput($field)
    {
        $app    = Factory::getApplication();
        $val    = $app->getInput()->get($field->alias, '', 'raw');
        $return = '<input id="jform_' . str_replace('-', '_', $field->alias) . '" type="text" placeholder="' . Text::_('COM_JSN_SEARCHFOR') . ' ' . Text::_($field->title) . '..." name="' . $field->alias . '" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"/>';
        
        return $return;
    }

    public static function getSearchQuery($field, &$query)
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $app   = Factory::getApplication();
        $input = $app->getInput()->get($field->alias, null, 'raw');

        if ($field->params->get('text_searchmode', 'like') == 'like') {
            $query->where('b.' . $db->quoteName($field->alias) . ' LIKE ' . $db->quote('%' . $input . '%'));
        } else {
            $query->where('LOWER(b.' . $db->quoteName($field->alias) . ') = LOWER(' . $db->quote($input) . ')');
        }
    }

    public static function editScript()
    {
        return '<script>jQuery(document).ready(function(){
            function text_show(){
                var val = jQuery("#jform_params_text_regex").val();
                if(val == "custom") jQuery("#jform_params_text_customregex").closest(".control-group").show();
                else jQuery("#jform_params_text_customregex").closest(".control-group").hide();
                if(val && val.length) jQuery("#jform_params_text_messageregex").closest(".control-group").show();
                else jQuery("#jform_params_text_messageregex").closest(".control-group").hide();
            }
            jQuery("#jform_params_text_regex").change(text_show);
            text_show();
        });</script>';
    }
}