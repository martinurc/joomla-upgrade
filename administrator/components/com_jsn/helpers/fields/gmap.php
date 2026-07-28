<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

global $_FIELDTYPES;
$_FIELDTYPES['gmap'] = 'COM_JSN_FIELDTYPE_GMAP';

class JsnGmapFieldHelper
{
    public static function create($alias)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " ADD " . $db->quoteName($alias) . " VARCHAR(255) NULL DEFAULT ''";
        $db->setQuery($query)->execute();
        
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " ADD " . $db->quoteName($alias . '_lat') . " VARCHAR(255) NULL DEFAULT ''";
        $db->setQuery($query)->execute();
        
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " ADD " . $db->quoteName($alias . '_lng') . " VARCHAR(255) NULL DEFAULT ''";
        $db->setQuery($query)->execute();
    }
    
    public static function delete($alias)
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " DROP COLUMN " . $db->quoteName($alias);
        $db->setQuery($query)->execute();
        
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " DROP COLUMN " . $db->quoteName($alias . '_lat');
        $db->setQuery($query)->execute();
        
        $query = "ALTER TABLE " . $db->quoteName('#__jsn_users') . " DROP COLUMN " . $db->quoteName($alias . '_lng');
        $db->setQuery($query)->execute();
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

        $placeholder = ($item->params->get('gmap_placeholder', '') != '' ? 'hint="' . JsnHelper::xmlentities($item->params->get('gmap_placeholder', '')) . '"' : '');

        if ($item->params->get('field_readonly', '') == 1 && $app->isClient('site')) {
            $readonly = 'readonly="true"';
        } elseif ($item->params->get('field_readonly', '') == 2 && $view != 'registration' && $app->isClient('site')) {
            $readonly = 'readonly="true"';
        } else {
            $readonly = '';
        }
        
        $xml = '';
        
        $xml .= '
            <field
                name="' . $item->alias . '"
                type="gmap"
                id="' . $item->alias . '"
                description="' . JsnHelper::xmlentities(($item->description)) . '"
                filter="string"
                label="' . ($hideTitle ? JsnHelper::xmlentities('<span class="no-title">' . Text::_($item->title) . '</span>') : JsnHelper::xmlentities($item->title)) . '"
                size="30"
                required="' . ($item->required ? ($item->required == 2 ? 'admin' : 'frontend') : 'false') . '"
                ' . $placeholder . '
                ' . $readonly . '
                class="' . $item->params->get('field_cssclass', '') . '"
            />
            <field
                name="' . $item->alias . '_lat"
                type="textfull"
                validate="regex"
                validate_regex="^[-+]?[0-9]*\.?[0-9]*$"
                pattern="^[-+]?[0-9]*\.?[0-9]*$"
                id="' . $item->alias . '_lat"
                ' . $readonly . '
            />
            <field
                name="' . $item->alias . '_lng"
                type="textfull"
                validate="regex"
                validate_regex="^[-+]?[0-9]*\.?[0-9]*$"
                pattern="^[-+]?[0-9]*\.?[0-9]*$"
                id="' . $item->alias . '_lng"
                ' . $readonly . '
            />
        ';

        static $loaded = array();
        
        if (!in_array($item->alias, $loaded)) {
            if ($item->params->get('gmap_positionsearchmode', 0)) {
                $item->params->set('gmap_types', 'geocode');
                $item->params->set('gmap_showmap', 1);
                $position_fields = (array) $item->params->get('gmap_positionsearchmode_fields', array());
                foreach ($position_fields as &$position_field) {
                    $position_field = '#jform_' . $position_field;
                }
            } else {
                $position_fields = array();
            }

            $jsnConfig = ComponentHelper::getParams('com_jsn');
            $doc       = Factory::getApplication()->getDocument();
            HTMLHelper::_('bootstrap.framework');

            $doc->addScript('https://maps.googleapis.com/maps/api/js?libraries=places&key=' . $jsnConfig->get('googlemaps_apikey', ''));
            $doc->addScript(Uri::root() . 'components/com_jsn/assets/js/jquery.geocomplete.min.js');

            $mapstyle        = $item->params->get('gmap_mapstyle', "");
            $mapstyle_custom = $item->params->get('gmap_mapstyle_custom', "");
            $stylecode       = '';

            if ($mapstyle == 'light') {
                $stylecode = 'mapOptions: {styles: [{"featureType":"water","elementType":"geometry","stylers":[{"color":"#e9e9e9"},{"lightness":17}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":20}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffffff"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#ffffff"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":16}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":21}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#dedede"},{"lightness":21}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#ffffff"},{"lightness":16}]},{"elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#333333"},{"lightness":40}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#f2f2f2"},{"lightness":19}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#fefefe"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#fefefe"},{"lightness":17},{"weight":1.2}]}] },';
            }
            if ($mapstyle == 'dark') {
                $stylecode = 'mapOptions: {styles: [{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}] },';
            }
            if ($mapstyle == 'custom' && $mapstyle_custom != '') {
                $stylecode = 'mapOptions: {styles: ' . $mapstyle_custom . '},';
            }

            if ($layout == 'edit' || $view == 'registration') {
                $aliasId = str_replace('-', '_', $item->alias);
                $script  = '
                jQuery(document).ready(function(){
                    jQuery("#jform_' . $aliasId . '_lat").attr("geo-' . $item->alias . '","lat").closest(".control-group,.form-group").hide().addClass("hide");
                    jQuery("#jform_' . $aliasId . '_lng").attr("geo-' . $item->alias . '","lng").closest(".control-group,.form-group").hide().addClass("hide");
                    var lat=jQuery("#jform_' . $aliasId . '_lat").val();
                    var lng=jQuery("#jform_' . $aliasId . '_lng").val();
                    var init_location=false;
                    if(lng!="" && lat!="") init_location=lat+","+lng;
                    jQuery("#jform_' . $aliasId . '")' . ($item->params->get('gmap_showmap', 1) ? '.after(\'<div class="jsn_map" id="map-' . $item->alias . '" ></div>\')' : '') . '.geocomplete({
                        detailsAttribute: "geo-' . $item->alias . '",
                        details: "form",
                        ' . ($readonly == '' ? 'addReset: true, addPosition: ' . ($item->params->get('gmap_geocodelocation', 0) ? 'true' : 'false') . ',' : '') . '
                        types: ["' . $item->params->get('gmap_types', 'geocode') . '"],
                        maxZoom: ' . $item->params->get('gmap_zoom', 15) . ',
                        blur: true,
                        restoreValueAfterBlur: true,
                        ' . ($item->params->get('gmap_showmap', 1) ? 'map: "#map-' . $item->alias . '",
                        markerOptions: {
                            draggable: ' . ($item->params->get('gmap_draggable', 1) ? 'true' : 'false') . '
                          },' . $stylecode : '') . '
                        location: init_location
                    });
                    jQuery("#geo-' . $item->alias . '-reset").click(function(){
                        jQuery("#jform_' . $aliasId . '_lat").val("");
                        jQuery("#jform_' . $aliasId . '_lng").val("");
                        return false;
                    });
                    ' . ($item->params->get('gmap_positionsearchmode', 0) ? '
                    function get_geocodeval_' . $aliasId . '(){
                        check_geocodeval_' . $aliasId . ' = true;
                        return jQuery("' . implode(',', $position_fields) . '").map(function() {
                            if( jQuery(this).is("input") && jQuery(this).val().trim().length)
                                return jQuery( this ).val();
                            else if(jQuery(this).is("input")) check_geocodeval_' . $aliasId . ' = false;
                            if( jQuery(this).is("select") && jQuery(this).val().trim().length)
                                return jQuery( this ).find("option:selected").text();
                            else if(jQuery(this).is("select")) check_geocodeval_' . $aliasId . ' = false;
                        }).get().join( ", " );
                    }
                    jQuery("#jform_' . $aliasId . '").parent(".input-prepend").attr("style","display:none !important");
                    jQuery("#map-' . $item->alias . '").after("<span id=\"message_geocodeval_' . $aliasId . '\">' . ($item->params->get('gmap_placeholder', '') != '' ? str_replace('"', '\"', Text::_($item->params->get('gmap_placeholder', ''))) : str_replace('"', '\"', Text::_('COM_JSN_GMAP_FILLADDRESSDETAILS'))) . '</span>");
                    if(lng!="" && lat!="") jQuery("#message_geocodeval_' . $aliasId . '").hide();
                    var check_geocodeval_' . $aliasId . ';
                    var geocodeval_' . $aliasId . ' = get_geocodeval_' . $aliasId . '();
                    jQuery("' . implode(',', $position_fields) . '").bind("keypress", function(e) {
                      if (e.keyCode == 13) {               
                        e.preventDefault();
                        jQuery(this).change();
                        return false;
                      }
                    });
                    jQuery("' . implode(',', $position_fields) . '").change(function(){
                        var geocodeval_test_' . $aliasId . ' = get_geocodeval_' . $aliasId . '();
                        if(geocodeval_test_' . $aliasId . ' == geocodeval_' . $aliasId . ') return;
                        geocodeval_' . $aliasId . ' = geocodeval_test_' . $aliasId . ';
                        if(geocodeval_' . $aliasId . '.trim().length && check_geocodeval_' . $aliasId . ') {
                            jQuery("#jform_' . $aliasId . '").geocomplete("geocode",{address:geocodeval_' . $aliasId . '.trim()});
                            jQuery("#message_geocodeval_' . $aliasId . '").hide();
                        }
                        else{
                            jQuery("#jform_' . $aliasId . '_lat").val("");
                            jQuery("#jform_' . $aliasId . '_lng").val("");
                            jQuery("#jform_' . $aliasId . '").val("");
                            jQuery("#map-' . $item->alias . '").hide();
                            jQuery("#message_geocodeval_' . $aliasId . '").show();
                        }
                    });' : '') . '
                    jQuery(document).trigger("ajaxStop");
                });
                ';
            } elseif ($option == 'com_jsn' && $view == 'profile') {
                $script = '
                jQuery(document).ready(function(){
                    jQuery(".' . $item->alias . '_latValue,.' . $item->alias . '_lngValue,.' . $item->alias . '_latLabel,.' . $item->alias . '_lngLabel' . ($item->params->get('gmap_positionsearchmode', 0) ? ',.' . $item->alias . 'Value > span' : '') . '").hide();
                    var lat=jQuery(".' . $item->alias . '_latValue").text();
                    var lng=jQuery(".' . $item->alias . '_lngValue").text();
                    var init_location=false;
                    if(jQuery.isNumeric(lng) && lng!="" && lat!="") init_location=lat+","+lng;
                    jQuery(".' . $item->alias . 'Value")' . ($item->params->get('gmap_showmap', 1) ? '.append(\'<div class="jsn_map" id="map-' . $item->alias . '" ></div>\')' : '') . '.geocomplete({
                        ' . ($item->params->get('gmap_showmap', 1) ? 'map: "#map-' . $item->alias . '",' . $stylecode : '') . '
                        maxZoom: ' . $item->params->get('gmap_zoom', 15) . ',
                        location: init_location
                    });
                    ' . ($item->params->get('gmap_route', 0) ? 'if(jQuery.isNumeric(lng) && lng!="" && lat!="") jQuery(".' . $item->alias . 'Value").append("<br /><a class=\"btn btn-info btn-small btn-sm\" target=\"_blank\" href=\"https://www.google.com/maps/dir/Current+Location/" + lat + "," + lng + "\"><i class=\"jsn-icon jsn-icon-globe\"></i> ' . Text::_('COM_JSN_GETDIRECTIONS') . '</a>");' : '') . '
                });
                ';
            } else {
                $script = '';
            }

            if (!empty($script)) {
                $doc->addScriptDeclaration($script);
            }
            $loaded[] = $item->alias;
        }
        
        return $xml;
    }
    
    public static function loadData($field, $user, &$data)
    {
        $alias     = $field->alias;
        $alias_lat = $field->alias . '_lat';
        $alias_lng = $field->alias . '_lng';
        
        if (isset($user->$alias))     $data->$alias     = $user->$alias;
        if (isset($user->$alias_lat)) $data->$alias_lat = $user->$alias_lat;
        if (isset($user->$alias_lng)) $data->$alias_lng = $user->$alias_lng;
    }
    
    public static function storeData($field, $data, &$storeData)
    {
        $alias = $field->alias;
        if (isset($data[$alias])) {
            $storeData[$alias] = $data[$alias];
            if (isset($data[$alias . '_lat']) && isset($data[$alias . '_lng'])) {
                $storeData[$alias . '_lat'] = $data[$alias . '_lat'];
                $storeData[$alias . '_lng'] = $data[$alias . '_lng'];
            } else {
                $api_key = ComponentHelper::getParams('com_jsn')->get('googlemaps_apikey');
                if (!empty($api_key)) {
                    $url      = "https://maps.googleapis.com/maps/api/geocode/json?sensor=false&key=" . $api_key . "&address=" . urlencode($data[$alias]);
                    $lat_long = get_object_vars(json_decode(file_get_contents($url)));
                    if (isset($lat_long['results'][0])) {
                        $storeData[$alias . '_lat'] = $lat_long['results'][0]->geometry->location->lat;
                        $storeData[$alias . '_lng'] = $lat_long['results'][0]->geometry->location->lng;
                    }
                }
            }
        }
    }
    
    public static function gmap($field)
    {
        $value = $field->__get('value');
        if (empty($value)) {
            return HTMLHelper::_('users.value', $value);
        } else {
            return '<span class="jsn_map_address">' . $value . '</span>';
        }
    }
    
    public static function getSearchInput($field)
    {
        $jsnConfig = ComponentHelper::getParams('com_jsn');
        HTMLHelper::_('bootstrap.framework');
        $doc = Factory::getApplication()->getDocument();

        $doc->addScript('https://maps.googleapis.com/maps/api/js?libraries=places&key=' . $jsnConfig->get('googlemaps_apikey', ''));
        $doc->addScript(Uri::root() . 'components/com_jsn/assets/js/jquery.geocomplete.min.js');

        $script = '
            var gmap_script = document.getElementById(\'gmap_script\');
            var geocomplete_script = document.getElementById(\'geocomplete_script\');
            var ep_gmap_loaded = {\'gmap\': false, \'geo\': false, \'callback\': function () {
                jQuery(".' . $field->alias . '_search").geocomplete({
                    detailsAttribute: "search-' . $field->alias . '",
                    details: "form",
                    blur: false
                });
            }};
            ep_gmap_loaded.callback();
            jQuery(window).load(function(){
                if (typeof ep_gmap_loaded !== \'undefined\') {
                    ep_gmap_loaded.callback();
                }
            });';
        $doc->addScriptDeclaration($script);

        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $radius = ($input->get($field->alias . '_radius', '', 'raw') == '' ? $field->params->get('gmap_radius', '10') : $input->get($field->alias . '_radius', '', 'raw'));
        
        $return = '<input id="jform_' . str_replace('-', '_', $field->alias) . '" placeholder="' . Text::_('COM_JSN_SEARCHINRADIUS') . '..." class="' . $field->alias . '_search jsn_map_search" type="text" name="' . $field->alias . '" value="' . htmlspecialchars($input->get($field->alias, '', 'raw'), ENT_QUOTES, 'UTF-8') . '"/>
            <select class="gmap_radius_select" name="' . $field->alias . '_radius">
                <option ' . ($radius == 1 ? 'selected="selected"' : '') . ' value="1">1 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 5 ? 'selected="selected"' : '') . ' value="5">5 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 10 ? 'selected="selected"' : '') . ' value="10">10 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 20 ? 'selected="selected"' : '') . ' value="20">20 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 30 ? 'selected="selected"' : '') . ' value="30">30 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 50 ? 'selected="selected"' : '') . ' value="50">50 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 100 ? 'selected="selected"' : '') . ' value="100">100 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 200 ? 'selected="selected"' : '') . ' value="200">200 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 300 ? 'selected="selected"' : '') . ' value="300">300 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
                <option ' . ($radius == 500 ? 'selected="selected"' : '') . ' value="500">500 ' . Text::_('COM_JSN_' . $field->params->get('gmap_unit', 'Km')) . '</option>
            </select>
            <input type="hidden" name="' . $field->alias . '_lat" value="' . htmlspecialchars($input->get($field->alias . '_lat', '', 'raw'), ENT_QUOTES, 'UTF-8') . '" search-' . $field->alias . '="lat" />
            <input type="hidden" name="' . $field->alias . '_lng" value="' . htmlspecialchars($input->get($field->alias . '_lng', '', 'raw'), ENT_QUOTES, 'UTF-8') . '" search-' . $field->alias . '="lng" />
            <div style="clear:both"></div>
        ';
        return $return;
    }
    
    public static function getSearchQuery($field, &$query)
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();
        $lat   = $input->get($field->alias . '_lat', null, 'raw');
        $lng   = $input->get($field->alias . '_lng', null, 'raw');

        if (is_numeric($lat) && is_numeric($lng)) {
            $radiusInput = $input->get($field->alias . '_radius', '', 'raw');
            if (is_numeric($radiusInput) && $radiusInput > 0) {
                $radius = $radiusInput;
            } else {
                $input->set($field->alias . '_radius', $field->params->get('gmap_radius', '10'));
                $radius = $field->params->get('gmap_radius', '10');
            }
            if ($field->params->get('gmap_unit', 'Km') == 'Km') $const = 6371; else $const = 3959;
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query->where('( ' . $const . ' * acos( cos( radians(' . $lat . ') ) * cos( radians( b.' . $db->quoteName($field->alias . '_lat') . ' ) ) * cos( radians( b.' . $db->quoteName($field->alias . '_lng') . ' ) - radians(' . $lng . ') ) + sin( radians(' . $lat . ') ) * sin( radians( b.' . $db->quoteName($field->alias . '_lat') . ' ) ) ) ) < ' . $radius);
        }
    }

    public static function editScript()
    {
        return '<script>jQuery(document).ready(function(){
            function gmap_show(){
                var val = jQuery("#jform_params_gmap_positionsearchmode input:checked").val();
                if(val == 0) jQuery("#jform_params_gmap_positionsearchmode_fields").closest(".control-group").hide();
                else jQuery("#jform_params_gmap_positionsearchmode_fields").closest(".control-group").show();
                if(val == 1) jQuery("#jform_params_gmap_types,#jform_params_gmap_showmap,#jform_params_gmap_geocodelocation").closest(".control-group").hide();
                else jQuery("#jform_params_gmap_types,#jform_params_gmap_showmap,#jform_params_gmap_geocodelocation").closest(".control-group").show();
            }
            jQuery("#jform_params_gmap_positionsearchmode input").change(gmap_show);
            gmap_show();
        });</script>';
    }
}