<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


global $_FIELDTYPES;
$_FIELDTYPES['gmap']='COM_JSN_FIELDTYPE_GMAP';

class JsnGmapFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." VARCHAR(255)";
		$db->setQuery($query);
		$db->query();
		
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias.'_lat')." VARCHAR(255)";
		$db->setQuery($query);
		$db->query();
		
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias.'_lng')." VARCHAR(255)";
		$db->setQuery($query);
		$db->query();
	}
	
	public static function delete($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users DROP COLUMN ".$db->quoteName($alias);
		$db->setQuery($query);
		$db->query();
		
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users DROP COLUMN ".$db->quoteName($alias.'_lat');
		$db->setQuery($query);
		$db->query();
		
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users DROP COLUMN ".$db->quoteName($alias.'_lng');
		$db->setQuery($query);
		$db->query();
	}
	
	public static function getXml($item)
	{
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		$placeholder=($item->params->get('gmap_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('gmap_placeholder','')).'"' : '');

		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		else $readonly='';
		
		$xml='';
		
		$xml.='
			
			<field
				name="'.$item->alias.'"
				type="gmap"
				id="'.$item->alias.'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				filter="string"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				size="30"
				required="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				'.$placeholder.'
				'.$readonly.'
				class="'.$item->params->get('field_cssclass','').'"
			/>
			<field
				name="'.$item->alias.'_lat"
				type="textfull"
				validate="regex"
				pattern="[-+]?[0-9]*\.?[0-9]*"
				id="'.$item->alias.'_lat"
				'.$readonly.'
			/>
			<field
				name="'.$item->alias.'_lng"
				type="textfull"
				validate="regex"
				pattern="[-+]?[0-9]*\.?[0-9]*"
				id="'.$item->alias.'_lng"
				'.$readonly.'
			/>
		';
		static $loaded=array();
		
		if(!in_array($item->alias,$loaded)/* && JFactory::getApplication()->isSite()*/)
		{
			if($item->params->get('gmap_positionsearchmode',0)){
				$item->params->set('gmap_types','geocode');
				$item->params->set('gmap_showmap',1);
				$position_fields=$item->params->get('gmap_positionsearchmode_fields',0);
				foreach($position_fields as &$position_field){
					$position_field = '#jform_'.$position_field;
				}
			}
			$jsnConfig=JComponentHelper::getParams('com_jsn');
			$doc = JFactory::getDocument();
			JHtml::_('bootstrap.framework');
			$doc->addScript('https://maps.googleapis.com/maps/api/js?libraries=places&key='.$jsnConfig->get('googlemaps_apikey',''));
			$doc->addScript(JURI::root().'components/com_jsn/assets/js/jquery.geocomplete.min.js');
			$mapstyle = $item->params->get('gmap_mapstyle', "");
			$mapstyle_custom = $item->params->get('gmap_mapstyle_custom', "");
			$stylecode='';
			if($mapstyle == 'light') $stylecode='mapOptions: {styles: [{"featureType":"water","elementType":"geometry","stylers":[{"color":"#e9e9e9"},{"lightness":17}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":20}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffffff"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#ffffff"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":16}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":21}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#dedede"},{"lightness":21}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#ffffff"},{"lightness":16}]},{"elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#333333"},{"lightness":40}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#f2f2f2"},{"lightness":19}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#fefefe"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#fefefe"},{"lightness":17},{"weight":1.2}]}] },';
			if($mapstyle == 'dark') $stylecode='mapOptions: {styles: [{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}] },';
			if($mapstyle == 'custom' && $mapstyle_custom!='') $stylecode='mapOptions: {styles: '.$mapstyle_custom.'},';
			if(JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration') 
			{
				$aliasId=str_replace('-','_',$item->alias);
				$script='
				jQuery(document).ready(function(){
					jQuery("#jform_'.$aliasId.'_lat").attr("geo-'.$item->alias.'","lat").closest(".control-group,.form-group").hide().addClass("hide");
					jQuery("#jform_'.$aliasId.'_lng").attr("geo-'.$item->alias.'","lng").closest(".control-group,.form-group").hide().addClass("hide");
					var lat=jQuery("#jform_'.$aliasId.'_lat").val();
					var lng=jQuery("#jform_'.$aliasId.'_lng").val();
					var init_location=false;
					if(lng!="" && lat!="") init_location=lat+","+lng;
					jQuery("#jform_'.$aliasId.'")'.($item->params->get('gmap_showmap',1) ? '.after(\'<div class="jsn_map" id="map-'.$item->alias.'" ></div>\')' : '').'.geocomplete({
						detailsAttribute: "geo-'.$item->alias.'",
						details: "form",
						'.($readonly == '' ? 'addReset: true, addPosition: '.($item->params->get('gmap_geocodelocation',0) ? 'true' : 'false' ).',' : '').'
						types: ["'.$item->params->get('gmap_types','geocode').'"],
						maxZoom: '.$item->params->get('gmap_zoom',15).',
						blur: true,
						restoreValueAfterBlur: true,
						'.($item->params->get('gmap_showmap',1) ? 'map: "#map-'.$item->alias.'",
						markerOptions: {
						    draggable: '.($item->params->get('gmap_draggable',1) ? 'true' : 'false' ).'
						  },'.$stylecode : '').'
						location: init_location
					});
					jQuery("#geo-'.$item->alias.'-reset").click(function(){
						jQuery("#jform_'.$aliasId.'_lat").val("");
						jQuery("#jform_'.$aliasId.'_lng").val("");
						return false;
					});
					'.($item->params->get('gmap_positionsearchmode',0) ? '
					function get_geocodeval_'.$aliasId.'(){
						check_geocodeval_'.$aliasId.' = true;
						return jQuery("'.implode(',',$position_fields).'").map(function() {
							if( jQuery(this).is("input") && jQuery(this).val().trim().length)
								return jQuery( this ).val();
							else if(jQuery(this).is("input")) check_geocodeval_'.$aliasId.' = false;
							if( jQuery(this).is("select") && jQuery(this).val().trim().length)
								return jQuery( this ).find("option:selected").text();
							else if(jQuery(this).is("select")) check_geocodeval_'.$aliasId.' = false;
						}).get().join( ", " );
					}
					jQuery("#jform_'.$aliasId.'").parent(".input-prepend").attr("style","display:none !important");
					jQuery("#map-'.$item->alias.'").after("<span id=\"message_geocodeval_'.$aliasId.'\">'.($item->params->get('gmap_placeholder','')!='' ? str_replace('"','\"',JText::_($item->params->get('gmap_placeholder',''))) : str_replace('"','\"',JText::_('COM_JSN_GMAP_FILLADDRESSDETAILS'))).'</span>");
					if(lng!="" && lat!="") jQuery("#message_geocodeval_'.$aliasId.'").hide();
					var check_geocodeval_'.$aliasId.';
					var geocodeval_'.$aliasId.' = get_geocodeval_'.$aliasId.'();
					jQuery("'.implode(',',$position_fields).'").bind("keypress", function(e) {
					  if (e.keyCode == 13) {               
					    e.preventDefault();
					    jQuery(this).change();
					    return false;
					  }
					});
					jQuery("'.implode(',',$position_fields).'").change(function(){
						var geocodeval_test_'.$aliasId.' = get_geocodeval_'.$aliasId.'();
						if(geocodeval_test_'.$aliasId.' == geocodeval_'.$aliasId.') return;
						geocodeval_'.$aliasId.' = geocodeval_test_'.$aliasId.';
						if(geocodeval_'.$aliasId.'.trim().length && check_geocodeval_'.$aliasId.') {
							jQuery("#jform_'.$aliasId.'").geocomplete("geocode",{address:geocodeval_'.$aliasId.'.trim()});
							jQuery("#message_geocodeval_'.$aliasId.'").hide();
						}
						else{
							jQuery("#jform_'.$aliasId.'_lat").val("");
							jQuery("#jform_'.$aliasId.'_lng").val("");
							jQuery("#jform_'.$aliasId.'").val("");
							jQuery("#map-'.$item->alias.'").hide();
							jQuery("#message_geocodeval_'.$aliasId.'").show();
						}
					});' : '').'
					jQuery(document).trigger("ajaxStop");
				});
				';
			}
			elseif(JFactory::getApplication()->input->get('option','')=='com_jsn' && JFactory::getApplication()->input->get('view','')=='profile') $script='
			jQuery(document).ready(function(){
				jQuery(".'.$item->alias.'_latValue,.'.$item->alias.'_lngValue,.'.$item->alias.'_latLabel,.'.$item->alias.'_lngLabel'.($item->params->get('gmap_positionsearchmode',0) ? ',.'.$item->alias.'Value > span' : '').'").hide();
				var lat=jQuery(".'.$item->alias.'_latValue").text();
				var lng=jQuery(".'.$item->alias.'_lngValue").text();
				var init_location=false;
				if(jQuery.isNumeric(lng) && lng!="" && lat!="") init_location=lat+","+lng;
				jQuery(".'.$item->alias.'Value")'.($item->params->get('gmap_showmap',1) ? '.append(\'<div class="jsn_map" id="map-'.$item->alias.'" ></div>\')' : '').'.geocomplete({
					'.($item->params->get('gmap_showmap',1) ? 'map: "#map-'.$item->alias.'",'.$stylecode : '').'
					maxZoom: '.$item->params->get('gmap_zoom',15).',
					location: init_location
				});
				'.($item->params->get('gmap_route',0) ? 'if(jQuery.isNumeric(lng) && lng!="" && lat!="") jQuery(".'.$item->alias.'Value").append("<br /><a class=\"btn btn-info btn-small btn-sm\" target=\"_blank\" href=\"https://www.google.com/maps/dir/Current+Location/"+lat+","+lng+"\"><i class=\"jsn-icon jsn-icon-globe\"></i> '.JText::_('COM_JSN_GETDIRECTIONS').'</a>");' : '').
			'});
			';
			$doc->addScriptDeclaration( $script );
			$loaded[]=$item->alias;
		}
		
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		$alias_lat=$field->alias.'_lat';
		$alias_lng=$field->alias.'_lng';
		if(isset($user->$alias)) $data->$alias=$user->$alias;
		if(isset($user->$alias_lat)) $data->$alias_lat=$user->$alias_lat;
		if(isset($user->$alias_lng)) $data->$alias_lng=$user->$alias_lng;
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		$alias=$field->alias;
		if(isset($data[$alias])) {
			$storeData[$alias]=$data[$alias];
			if(isset($data[$alias.'_lat']) && isset($data[$alias.'_lng'])) {
				$storeData[$alias.'_lat']=$data[$alias.'_lat'];
				$storeData[$alias.'_lng']=$data[$alias.'_lng'];
			}
			else {
				$api_key = JComponentHelper::getParams('com_jsn')->get('googlemaps_apikey');
				if(!empty($api_key)){
					$url="https://maps.googleapis.com/maps/api/geocode/json?sensor=false&key=".$api_key."&address=".urlencode($data[$alias]);
                    $lat_long = get_object_vars(json_decode(file_get_contents($url)));
                    if(isset($lat_long['results'][0])) {
                    	$storeData[$alias.'_lat'] = $lat_long['results'][0]->geometry->location->lat;
                    	$storeData[$alias.'_lng'] = $lat_long['results'][0]->geometry->location->lng;
                    }
				}
			}
		}
	}
	
	public static function gmap($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			return '<span class="jsn_map_address">'.$value.'</span>';
		}
	}
	
	public static function getSearchInput($field)
	{
		$jsnConfig=JComponentHelper::getParams('com_jsn');
		JHtml::_('bootstrap.framework');
		$doc = JFactory::getDocument();
	$doc->addScript('https://maps.googleapis.com/maps/api/js?libraries=places&key='.$jsnConfig->get('googlemaps_apikey','')/*, array(), array('id' => 'gmap_script', 'async' => 'async')*/);
$doc->addScript(JURI::root().'components/com_jsn/assets/js/jquery.geocomplete.min.js'/*, array(), array('id' => 'geocomplete_script', 'async' => 'async')*/);
		$script='
		
			var gmap_script = document.getElementById(\'gmap_script\');
			var geocomplete_script = document.getElementById(\'geocomplete_script\');
			var ep_gmap_loaded = {\'gmap\': false, \'geo\': false, \'callback\': function () {
				jQuery(".'.$field->alias.'_search").geocomplete({
					detailsAttribute: "search-'.$field->alias.'",
					details: "form",
					blur: false
				});
			}};'.
			/*gmap_script.addEventListener(\'load\', function() {
				if (ep_gmap_loaded.geo) ep_gmap_loaded.callback();
				ep_gmap_loaded.gmap = true;
			});
			geocomplete_script.addEventListener(\'load\', function() {
				if (ep_gmap_loaded.gmap) ep_gmap_loaded.callback();
				ep_gmap_loaded.geo = true;
			});*/
			'ep_gmap_loaded.callback();
			jQuery(window).load(function(){
				if (typeof ep_gmap_loaded !== \'undefined\') {
					ep_gmap_loaded.callback();
				}
			});';
		$doc->addScriptDeclaration( $script );
		$radius=(JFactory::getApplication()->input->get($field->alias.'_radius','','raw')=='' ? $field->params->get('gmap_radius','10') : JFactory::getApplication()->input->get($field->alias.'_radius','','raw'));
		
		$return='<input id="jform_'.str_replace('-','_',$field->alias).'" placeholder="'.JText::_('COM_JSN_SEARCHINRADIUS').'..." class="'.$field->alias.'_search jsn_map_search" type="text" name="'.$field->alias.'" value="'.JFactory::getApplication()->input->get($field->alias,'','raw').'"/>
			<select class="gmap_radius_select" name="'.$field->alias.'_radius">
				<option '.($radius==1 ? 'selected="selected"' : '').' value="1">1 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==5 ? 'selected="selected"' : '').' value="5">5 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==10 ? 'selected="selected"' : '').' value="10">10 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==20 ? 'selected="selected"' : '').' value="20">20 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==30 ? 'selected="selected"' : '').' value="30">30 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==50 ? 'selected="selected"' : '').' value="50">50 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==100 ? 'selected="selected"' : '').' value="100">100 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==200 ? 'selected="selected"' : '').' value="200">200 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==300 ? 'selected="selected"' : '').' value="300">300 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
				<option '.($radius==500 ? 'selected="selected"' : '').' value="500">500 '.JText::_('COM_JSN_'.$field->params->get('gmap_unit','Km')).'</option>
			</select>
			<input type="hidden" name="'.$field->alias.'_lat" value="'.JFactory::getApplication()->input->get($field->alias.'_lat','','raw').'" search-'.$field->alias.'="lat" />
			<input type="hidden" name="'.$field->alias.'_lng" value="'.JFactory::getApplication()->input->get($field->alias.'_lng','','raw').'" search-'.$field->alias.'="lng" />
			<div style="clear:both"></div>
		';
		return $return;
	}
	
	public static function getSearchQuery($field, &$query)
	{
		if(is_numeric(JFactory::getApplication()->input->get($field->alias.'_lat',null,'raw')) && is_numeric(JFactory::getApplication()->input->get($field->alias.'_lng',null,'raw')))
		{
			if(is_numeric(JFactory::getApplication()->input->get($field->alias.'_radius','','raw')) && JFactory::getApplication()->input->get($field->alias.'_radius','','raw')>0){
				$radius=JFactory::getApplication()->input->get($field->alias.'_radius',$field->params->get('gmap_radius','10'),'raw');
			}
			else
			{
				JFactory::getApplication()->input->set($field->alias.'_radius',$field->params->get('gmap_radius','10'));
				$radius=$field->params->get('gmap_radius','10');
			}
			if($field->params->get('gmap_unit','Km')=='Km') $const=6371; else $const=3959;
			$db=JFactory::getDbo();
			$query->where('( '.$const.' * acos( cos( radians('.JFactory::getApplication()->input->get($field->alias.'_lat',null,'raw').') ) * cos( radians( b.'.$db->quoteName($field->alias.'_lat').' ) ) * cos( radians( b.'.$db->quoteName($field->alias.'_lng').' ) - radians('.JFactory::getApplication()->input->get($field->alias.'_lng',null,'raw').') ) + sin( radians('.JFactory::getApplication()->input->get($field->alias.'_lat',null,'raw').') ) * sin( radians( b.'.$db->quoteName($field->alias.'_lat').' ) ) ) ) < '.$radius);
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
