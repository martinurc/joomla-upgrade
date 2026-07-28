<?php

/**
 * @version    4.3.1
 * @package     com_mymaplocations
 * @copyright   JoomUnited (C) 2011. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');
jimport('cms.plugin.plugin');
jimport('joomla.form.form');
require_once(JPATH_SITE .'/components/com_mymaplocations/helpers/mymaplocations.php');
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_mymaplocations/tables');
JLoader::register('FCField', JPATH_ADMINISTRATOR . '/components/com_flexicontent/helpers/fcfield/parentfield.php');
class plgFlexicontent_fieldsMyMaplocations extends FCField {

    static $field_types = array('mymaplocations');
	
	function plgFlexicontent_fieldsMyMaplocations( &$subject, $params )
	{
		parent::__construct( $subject, $params );
	}
	

    function onDisplayField(&$field, &$item)
	{
		
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		$field->label = JText::_($field->label);
		$this->setField($field);
		$this->setItem($item);
		
		$formlayout = $field->parameters->get('formlayout', '');
		$formlayout = $formlayout ? 'field_'.$formlayout : 'field';
		
		$this->displayField( $formlayout );
		
    }
	function onAfterSaveField( &$field, &$post, &$file, &$item ) {

		$this->saveItemMyMapLocations($field,$item);
		return;
	}
	// Method to create (insert) advanced search index DB records for the field values
	function onIndexAdvSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		if ( !$field->isadvsearch && !$field->isadvfilter ) return;
		/*
		 *to do list to add advsearch
		 *if($post===null)
		{
			$db = JFactory::getDBO();
			$sql = "select id from #__mymaplocations_location where component=" . $db->Quote('com_content') . "AND extra_id=" . $db->Quote($item->id);
			$db->setQuery($sql);
			$mapitem = $db->loadObject();
			$values[0]['address']=$mapitem->address;
			$values[0]['town']=$mapitem->town;
			$values[0]['locationstate']=$mapitem->locationstate;
			$values[0]['country']=$mapitem->country;
			$values[0]['postal']=$mapitem->postal;
			$values[0]['description']=$mapitem->description;
			
		}
		else
		{
			$mapitem= JFactory::getApplication()->input->getArray();
			$values[0]['address']=$mapitem->address;
			$values[0]['town']=$mapitem->town;
			$values[0]['locationstate']=$mapitem->locationstate;
			$values[0]['country']=$mapitem->country;
			$values[0]['postal']=$mapitem->postal;
			$values[0]['description']=$mapitem->description;
		}
		FlexicontentFields::onIndexAdvSearch($field, $values, $item, $required_properties=array(), $search_properties=array('address','town','locationstate','country','postal','description'), $properties_spacer=' ', $filter_func='strip_tags');*/
		return true;
	}

    function saveItemMyMapLocations($field,$row) {
		
		$db = JFactory::getDBO();
        $sql = "select id from #__mymaplocations_location where component=" . $db->Quote('com_content') . "AND extra_id=" . $db->Quote($row->id);
        $db->setQuery($sql);
        $mapitem = $db->loadObject();

        $type = 'location';
        $prefix = 'MyMaplocationsTable';
        $config = array();
        $table = JTable::getInstance($type, $prefix, $config);
        $mapdata = JFactory::getApplication()->input->getArray();
		$mapdata['description']=JFactory::getApplication()->input->post->get('description',null,'RAW');
		$mapdata['hours']=JFactory::getApplication()->input->post->get('hours',null,'RAW');
	   if((!$mapdata['latitude'])||(!$mapdata['longitude']))
        {
            return;
        }
        if (!empty($mapitem->id)) {
            $mapdata['id'] = $mapitem->id;
        } else {
            unset($mapdata['id']);
        }
        $mapdata['name'] = $row->id . '-' . $row->title;
        $mapdata['alias'] = $row->id . '-' . $row->title;
        $mapdata['catid'] = $mapdata['mapcatid'];
        $mapdata['component'] = 'com_content';
        $mapdata['extra_id'] = $row->id;
        $table->bind($mapdata);
        if (!$table->check()) {

            JFactory::getApplication()->enqueueMessage($table->getError(), 'error');
        }
        if (!$table->store()) {
            JFactory::getApplication()->enqueueMessage($table->getError(), 'error');
        }
    }

   function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		
		$db = JFactory::getDBO();
		$sql = "select * from #__itemrating_context where context=" . $db->Quote('com_content') . "AND context_id=" . $db->Quote($item->id);
		$db->setQuery($sql);
		
		$pluginParams = $field->parameters;
        $catids = $pluginParams->get('category_id', 0);
        $height = $pluginParams->get('height', 400);
        $width = $pluginParams->get('width', '70%');
        $zoom = $pluginParams->get('zoom', '10');
        $type = $pluginParams->get('type', 'google');
        $output = '';
        $img = '';
        $db = JFactory::getDBO();
        $sql = "SELECT a.* FROM #__mymaplocations_location AS a where a.component=" . $db->Quote('com_content') . " AND a.extra_id=" . $db->Quote($item->id);
        $db->setQuery($sql);
        $mapitem = $db->loadObject();
		if(!($mapitem))
		{
		    return;
		}
		if(($mapitem->latitude==0)&&($mapitem->longitude==0))
		{
		    return;
		}
		$mapitem->slug=$item->slug;
		$mapitem->categoryslug=$item->categoryslug;
	$mapitem->google_maptype = $pluginParams->get('google_maptype', 'ROADMAP');
	$mapitem->bing_maptype = $pluginParams->get('bing_maptype', 'road');
	$mapitem->map_design = $pluginParams->get('map_design',1);
	 $mapitem->openmapstyle=$pluginParams->get('openmapstyle', 1);
	$mapitem->name=$item->title;
        if (!empty($mapitem)) {
            $mapitems[0] = $mapitem;
            $map = MyMaplocationsHelper::createMap($mapitems, $width, $height, $zoom, $type);
            $output = '<div class="row-fluid mymaplocation itemAuthorBlock" style="width:100%;float:left;"><div class="span12"><div class="locationsearch" id="locationsearch">';
            $address = "";
            if (!empty($mapitem->address)) {
                $address.='<span property="v:street-address"><i class="icon-home"></i>' . $mapitem->address . '</span> <br/>';
            }
            if (!empty($mapitem->town)) {
                $address.='<span property="v:locality">' . $mapitem->town . ',</span> ';
            }
            if (!empty($mapitem->locationstate)) {
                $address.='<span property="v:region">' . $mapitem->locationstate . '</span>.<br/>';
            }
            if (!empty($mapitem->country)) {
                $address.='<span>' . $mapitem->country . '</span> ,';
            }
            if (!empty($mapitem->postal)) {
                $address.='<span>' . $mapitem->postal . '</span> ';
            }
	    
        if (!empty($mapitem->phone)) {
            $address.='<br/><span><i class="icon-phone"></i><span itemprop="telephone">' . $mapitem->phone . '</span>';
        }
        if (!empty($mapitem->hours)) {
            $address.='<br/><span><i class="icon-calendar"></i>' . $mapitem->hours . '</span>';
        }
            if (!empty($mapitem->logo)) {
                $img = '<img src="' . $mapitem->logo . '"  class="mml_logo"/>';
            }
            $output.= $map . $img . '
    <div xmlns:v="http://rdf.data-vocabulary.org/#" typeof="v:Organization" style="margin-left: 10px; float: left;"  class="mmladdress">
   <br/>
   <div rel="v:address">
      <div typeof="v:Address">
	' . $address . '
      </div>
   </div>
   <div rel="v:geo">
      <span typeof="v:Geo">
         <span property="v:latitude" content="' . $mapitem->latitude . '"></span>
         <span property="v:longitude" content="' . $mapitem->longitude . '"></span>
      </span>
   </div>
 </div>
    <div class="description" style="margin-left: 10px; float: left;width:100%;">
      ' . $mapitem->description . '
      </div>
    </div> </div></div><div class="clr"></div>';
	$output.="<script type='text/javascript'>jQuery(document).ready(function(){
	jQuery('.tabbernav a').on('click', function() {
				reinitialize_com_content_".$mapitem->id."();
			});
				});
			
			</script>";
        }
        $field->{$prop} = $output;
    }

}

?>
