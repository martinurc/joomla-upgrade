<?php
/**
 * @version    4.3.1
 * @package     com_mymaplocations
 * @copyright   JoomUnited (C) 2011. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
//No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Some parameter shortcuts
$required = $field->parameters->get('required', 0);
$required = $required ? ' class="required"' : '';

require_once JPATH_SITE . '/components/com_mymaplocations/helpers/mymaplocations.php';
MyMaplocationsHelper::loadLanguage();
// Add needed JS/CSS
static $js_added = null;
if ($js_added === null)
{
	$js_added = true;
	MyMaplocationsHelper::loadGoogleJs();
}
$params=JComponentHelper::getParams('com_mymaplocations');
	$googleapi=$params->get('googleapi',null);
	if(!$googleapi)
	{
		JFactory::getApplication()->enqueueMessage( JText::_('COM_MYMAPLOCATIONS_MAP_ERROR'), 'error');
	}

$lang = JFactory::getLanguage();
            $extension = 'com_mymaplocations';
            $form = JForm::getInstance('form', JPATH_ADMINISTRATOR . '/components/com_mymaplocations/models/forms/location.xml');
			$html="";
            $db = JFactory::getDBO();
            $sql = "select * from #__mymaplocations_location where component=" . $db->Quote('com_content') . "AND extra_id=" . $db->Quote($item->id);
            $db->setQuery($sql);
            $result = $db->loadObject();
			$googleapi=$params->get('googleapi',null);
			$key="";
			if($googleapi){
				$key='&amp;key='.$googleapi;
			}
            if (!empty($result)) {
                $form->bind($result);
                $latlng = $result->latitude . "," . $result->longitude;
            } else {
                $latlng = "44.824708,-0.615234";
            }
			  $document = JFactory::getDocument();
        $document->addStyleSheet(JURI::root() . '/components/com_mymaplocations/assets/css/materialize.css');
		require_once  (JPATH_SITE.'/components/com_mymaplocations/helpers/mymaplocations.php'); 
            if(method_exists('MyMapLocationsHelper','getBackEndJs'))
               {
                $script="";
                  if ((@$result->latitude)) {
                 MyMaplocationsHelper::getBackEndJs(false,$result->latitude ,$result->longitude);
                } else {
                 MyMaplocationsHelper::getBackEndJs(false);
                              }
               }else {

            $script='<script src="//maps.google.com/maps/api/js?v=3&amp;libraries=places'.$key.'" type="text/javascript"></script><script src="' . JURI::root() . 'administrator/components/com_mymaplocations/assets/site.js" type="text/javascript"></script>';
            $script.= <<<EOF
	      
              <script type="text/javascript">
     
		google.maps.event.addDomListener(window, 'load', initialize)
		function initialize()
		{
		 var latlng = new google.maps.LatLng($latlng);
			var options = {
          zoom: 5,
          center: latlng,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          draggableCursor: "crosshair",
          streetViewControl: false
        };
		var map = new google.maps.Map(document.getElementById("map"), options);
	  	var marker = new google.maps.Marker({
					    position: latlng,
					    map: map
					    });

      jQuery("#zoom").html(5);
	  }
		  google.maps.event.addListener(map,"click", function(location)
      {
        GetlocationInfo(location.latLng);
      });
      google.maps.event.addListener(map,'zoom_changed', function(oldLevel, newLevel)
      {
        jQuery("#zoom").html(map.getZoom());
      });
      var myPano = new google.maps.StreetViewPanorama(document.getElementById("pano"),
            { visible:false});
      myPano.setPov({
        heading: 265,
        zoom:1,
        pitch:0});
      jQuery('#pano').hide();
      google.maps.event.trigger(myPano, 'resize');

			// autocomplete
			var autocomplete = new google.maps.places.Autocomplete(document.getElementById('postcode'), {});
			google.maps.event.addListener(autocomplete, 'place_changed', function() {
				jQuery("#locations").html("");
				jQuery("#error").html("");
				var place = autocomplete.getPlace();
				if (place.place_id ) {
                                                jQuery("#place_id_data").show();
											jQuery("#place_id_div").html("We have found Google Place <a href='"+place.url+"' class='btn btn-primary' target='_blank'>"+place.name+"</a> linked with data.Click here to copy data directly to our fields from Google places API  <input type='button' class='btn btn-inverse' value='insert' onclick='insertGooglePlaces()'/>");
												}
				Gotolocation(place.geometry.location);
			});

      var initListener;
      var marker;
      function StartStreetView() {
        // street view
        if (jQuery("#streetViewBtn").val() == "Start StreetView") {
          initListener = google.maps.event.addListener(myPano, "position_changed", handlePanoMove);
          jQuery('#pano').show();
          myPano.setVisible(true);
          jQuery("#streetViewBtn").val("End StreetView");
          google.maps.event.trigger(myPano, 'resize');
          GotoLatLong();
        }
        else {
          google.maps.event.removeListener(initListener);
          myPano.setVisible(false);
          jQuery('#pano').hide();
          jQuery("#streetViewBtn").val("Start StreetView");
          google.maps.event.trigger(myPano, 'resize');
        }
      }

			function Geocode()
			{
				jQuery("#locations").html("");
                                                jQuery("#error").html("");
                                                var place = autocomplete.getPlace();
												if(typeof place === 'undefined'){
															var localSearch = new google.maps.Geocoder();
                                                var postcode = jQuery("#postcode").val();
                                                localSearch.geocode({ 'address': postcode },
                                                function(results, status) {
                                                    if (results.length == 1) {
                                                        var result = results[0];
                                                        var location = result.geometry.location;
                                                        Gotolocation(location);
                                                    }
                                                    else if (results.length > 1) {
                                                        jQuery("#error").html("Multiple addresses found");
                                                        // build a list of possible addresses
                                                        var html = "";
                                                        for (var i=0; i<results.length; i++) {
                                                            html += '<a href="javascript:Gotolocation(new google.maps.LatLng(' + 
                                                                results[i].geometry.location.lat() + ', ' + results[i].geometry.location.lng() + '))">' + 
                                                                results[i].formatted_address + "</a><br/>";
                                                        }
                                                        jQuery("#locations").html(html);
                                                    }
                                                    else {
                                                        jQuery("#error").html("Address not found");
                                                    }
                                                });	
																}
																else
																{
												if (place.place_id ) {
																
                                                jQuery("#place_id_data").show();
												jQuery("#place_id_div").html("We have found Google Place <a href='"+place.url+"' class='btn btn-primary' target='_blank'>"+place.name+"</a> linked with data.Click here to copy data directly to our fields from Google placs API  <input type='button' class='btn btn-inverse' value='insert' onclick='insertGooglePlaces()'/>");
																}
																Gotolocation(place.geometry.location);
												}
			}
			function useGeocode()
			{
				if (navigator.geolocation)
					{
						navigator.geolocation.getCurrentPosition(showPosition);
					}
				else
				{
					x.innerHTML="Geolocation is not supported by this browser.";
				}
			}
			function showPosition(position)
			{
				document.getElementById('latitude').value= position.coords.latitude;
				document.getElementById('longitude').value=position.coords.longitude;
				ReverseGeocoding(position.coords.latitude, position.coords.longitude, '#address_text');
				Gotolocation(new google.maps.LatLng( position.coords.latitude, position.coords.longitude));
				
			}
			

			function Gotolocation(location) {
				GetlocationInfo(location);
				var options = {
          zoom: 5,
          center: location,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          draggableCursor: "crosshair",
          streetViewControl: false
        };
				var map = new google.maps.Map(document.getElementById("map"), options);
				 marker = new google.maps.Marker({
          position: location,
          map: map});
				map.setCenter(location);
			}

      function GetlocationInfo(latlng)
      {
        if (latlng != null)
        {
          ShowLatLong(latlng);
          UpdateStreetView(latlng);
        }
      }

      function GotoLatLong()
      {
        if (jQuery("#lat").val() != "" && jQuery("#long").val() != "") {
          var lat = jQuery("#lat").val();
          var long = jQuery("#long").val();
          var latLong = new google.maps.LatLng(lat, long);
          ShowLatLong(latLong);
          map.setCenter(latLong);
          UpdateStreetView(latLong);
        }
      }

      function ShowLink(){
        jQuery("#mapLink").html('<a href="https://maps.google.com/?ll=' + jQuery("#lat").val() +
          ',' + jQuery("#long").val() + '&z=' + jQuery("#zoom").html() + '">Link for this map</a>');
      }

			function toDMS(latOrLng) {
				var d = parseInt(latOrLng);
				var md = Math.abs(latOrLng - d) * 60;
				var m = Math.floor(md);
				var sd = (md - m) * 60;
				return Math.abs(d) + "\u00B0 " + m + "' " + roundNumber(sd, 4) + '"';
			}

			function latToDMS(lat) {
				var dms = toDMS(lat);
				if (lat > 0)
					return dms + "N";
				else
					return dms + "S";
			}

			function lngToDMS(lng) {
				var dms = toDMS(lng);
				if (lng > 0)
					return dms + "E";
				else
					return dms + "W";
			}

      function ShowLatLong(latLong)
      {
        // show the lat/long
        if (marker != null) {
          marker.setMap(null);
        }
		var options = {
          zoom: 5,
          center: latLong,
          mapTypeId: google.maps.MapTypeId.ROADMAP,
          draggableCursor: "crosshair",
          streetViewControl: false
        };
		var map = new google.maps.Map(document.getElementById("map"), options);
        marker = new google.maps.Marker({
          position: latLong,
          map: map});
        jQuery("#latitude").val(roundNumber(latLong.lat(), 6));
        jQuery("#longitude").val(roundNumber(latLong.lng(), 6));
				jQuery("#latDMS").html(latToDMS(latLong.lat()));
				jQuery("#lngDMS").html(lngToDMS(latLong.lng()));
        ShowLink();
        GetElevation(latLong.lat(), latLong.lng(), '#elevation');
        ReverseGeocoding(latLong.lat(), latLong.lng(), '#address_text');
      }
function ReverseGeocoding(lat, long, selector){
    var geocoder = new google.maps.Geocoder();
    var latLong = new google.maps.LatLng(lat, long);
    var streetNumber = '';
            var streetName = '';
            var country = '';
            var postalCode = '';
            var city = '';
	    var estate='';
    geocoder.geocode({'latLng': latLong}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        if (results) {
         
          var foundAddress = false;
          for (var i=0; i<results.length; i++) {
            if ((results[i].types[0] == 'street_address') || (results[i].types[0] == 'route')) {
              results[i].address_components.each(function (el) {

                    el.types.each(function (type) {
                        if (type == 'street_number') {
                            streetNumber = el.long_name;
                        }

                        if (type == 'route') {
                            streetName = el.long_name;
                        }
			if (type == 'administrative_area_level_1') {
                            estate = el.long_name;
                        }
                        if (type == 'country') {
                            country = el.long_name;
                        }

                        if (type == 'postal_code') {
                            postalCode = el.long_name;
                        }

                        if (type == 'locality') {
                            city = el.long_name;
                        }
                    })
                });
		 jQuery("#address").val(streetNumber+" "+streetName);
		 jQuery("#town").val(city);
		 jQuery("#country").val(country);
		 jQuery("#postal").val(postalCode);
		 jQuery("#locationstate").val(estate);
              foundAddress = true;
	      break;
            }
          }
          if (!foundAddress) {
             jQuery("#jform_address").html(results[0].formatted_address);
          }
        }
      }
    });
  }

      function UpdateStreetView(latLong)
      {
        // street view
        if (jQuery("#streetViewBtn").val() == "End StreetView") {
          jQuery("#panoError").html("");
          myPano.setVisible(true);
          myPano.setPosition(latLong);
          // also set via the service API so we know if there is a view available
          var service = new google.maps.StreetViewService();
          service.getPanoramaBylocation(latLong, 50,
            function(result, status) {
              if (status != google.maps.StreetViewStatus.OK) {
                jQuery("#panoError").html("No street view available");
                myPano.setVisible(false);
              }
            }
          );
        }
      }

      function handlePanoMove(location)
      {
        ShowLatLong(myPano.getPosition());
      }
      function copyData()
      {
	var lat=document.getElementById('latitude').value;
	var long=document.getElementById('longitude').value;
	document.getElementById('latitude').value=lat;
	document.getElementById('longitude').value=long;
      }
	   function insertGooglePlaces() {
                                                //code
												   var place = autocomplete.getPlace();
												   document.getElementById('place_id').value=place.place_id;
												
												if (typeof place.international_phone_number !== 'undefined') {
												   document.getElementById('phone').value=place.international_phone_number;
												}else
												{
																document.getElementById('phone').value="";
												}
												if (typeof place.website !== 'undefined') {
												   document.getElementById('contactlink').value=place.website;
												}else
												{
																document.getElementById('contactlink').value="";
												}
												
													document.getElementById('hours').value="";
													
													
		
                                            }
											function reinitialize()
						{
                     var script = document.createElement('script');
                    script.type = 'text/javascript';
                    script.src = '//maps.googleapis.com/maps/api/js?v=3$key&' +'callback=initialize';
                    document.body.appendChild(script);
    
					}
	</script>
EOF;
			   }
			   $script.="<script type='text/javascript'>jQuery(document).ready(function(){
	jQuery('.tabbernav a').on('click', function() {
				initialize();
			});
	});</script>
";
            $html.='<style type="text/css">
	  .width100
	  {
	    width:100%;
	    float:left;
	  }
	  .width70
	  {
	    width:70%;
	    float:left;
	    
	  }
	  .width30
	  {
	    float: left;
	    width: 29%;
	  }
	  #mapcatid_chzn,.catid .chzn-drop
	  {
	    min-width:120px;
	  }
	  </style>
	  <div class="width100" id="location-form"><div class="width70"><table class="adminFormK2 table">
			<tbody>
			<tr>
			<td class="adminK2LeftCol"><label>' . $form->getLabel('logo') . '</label></td>
									<td class="adminK2RightCol">
				' . $form->getInput('logo') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('icon') . '
				</label></td>
			<td class="adminK2RightCol">' . $form->getInput('icon') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>

			' . $form->getLabel('description') . '</label></td>
			<td class="adminK2RightCol">
			<div class="clr"></div>
			' . $form->getInput('description') . '</td></tr></table></div><div class="width30">
	<div class="alert alert-success"  id="place_id_data" style="display:none;">
         <button type="button" class="close" data-dismiss="alert">&times;</button>
		 <div id="place_id_div"></div>
		</div>
		 <div id="container_mml">
 <input id="postcode" style="width:250px;" type="text" class="search-query"/><input type="button" onclick="Geocode()" value="' . JTEXT::_("COM_MYMAPLOCATIONS_FORM_LBL_STORE_SEARCH") . '"  class="btn"/>
	</div>
	<div class="navbar-form pull-left">
	<label><span class="label">' . JTEXT::_("COM_MYMAPLOCATIONS_FORM_LBL_STORE_LATITUDE") . '</span></label>' . $form->getInput('latitude') . '
	</div>
							<div class="navbar-form pull-left" style="margin-bottom:10px;">
	<label><span class="label"> ' . JTEXT::_("COM_MYMAPLOCATIONS_FORM_LBL_STORE_LONGITUDE") . '</span></label>' . $form->getInput('longitude') . '
							</div>
	<div id="map" style="width: 100%; height: 300px;clear:both;"></div>' . $script . '<table class="adminFormK2 table"><tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('address') . '</label></td>
									<td class="adminK2RightCol">
			' . $form->getInput('address') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>
                        ' . $form->getLabel('town') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('town') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('locationstate') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('locationstate') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('country') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('country') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('postal') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('postal') . '</td>
								</tr>
                                                                			<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('contactlink') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('contactlink') . '</td>
								</tr>
			<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('phone') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('phone') . '</td>
								</tr><tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('hours') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('hours') . '</td>
								</tr>
								<tr>
                            <td class="adminK2LeftCol"><label>' . $form->getLabel('place_id') . '</label></td>
			<td class="adminK2RightCol">
			' . $form->getInput('place_id') . '</td>
								</tr>
								</table>
								
        </div></div>';
$field->html[0] = $html;
