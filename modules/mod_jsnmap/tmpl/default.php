<?php
/**
* @copyright  Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license    http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package    Easy Profile
* website   www.easy-profile.com
* Technical Support : Forum - http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

$jsnConfig=JComponentHelper::getParams('com_jsn');
$cluster = $params->def('mapcluster', 0);
$mapstyle = $params->def('mapstyle', "");
$mapstyle_custom = $params->def('mapstyle_custom', "");
$mapdisablescroll = $params->def('mapdisablescroll', 0);

$doc = JFactory::getDocument();
if($jsnConfig->get('bootstrap',0)) {
  $doc->addStylesheet(JURI::root().'media/jui/css/bootstrap.min.css');
  $dir = $doc->direction;
  if($dir=='rtl')
  {
    $doc->addStylesheet(JURI::root().'media/jui/css/bootstrap-rtl.css');
  }
}



$cluster = $params->def('mapcluster', 0) && is_array($list) && count($list)>1;

$infowindow = function($user,$params,$jsnConfig,$fields_title){
  $return=array();
  $return[]='<div class="jsn-l-map">';
  $return[]='<div class="jsn-l-top '.($jsnConfig->get('avatar',1) ? 'jsn-l-top-a' : '').'">';
  if($jsnConfig->get('avatar',1)) {
    $return[]='<div class="jsn-l-avatar"><a href="'.$user->getLink(array('Itemid'=>$params->get('profile_menuid', ''))).'">'.$user->getField('avatar_mini').'</a></div>';
  }
  $return[]='<div class="jsn-l-title"><h3><a href="'.$user->getLink(array('Itemid'=>$params->get('profile_menuid', ''))).'">'.$user->getField('formatname').'</a></h3>';
  if($jsnConfig->get('status',1)) {
    $return[]=$user->getField('status');
  }
  $return[]='</div>';
  $return[]='<div class="jsn-l-fields">';
  $fields=$params->def('list_fields', array());
  if(is_array($fields)) foreach($fields as $field) {
    $value=$user->getField($field,true);
    if(!empty($value)){
      $return[]='<div class="'.$field.'">';
      if($params->def('show_titles', 0)) {
        $return[]='<span class="jsn-l-field-title">'.JText::_($fields_title[$field]['title']).': </span>';
      }
      $return[]='<span class="jsn-l-field-value">'.$value.'</span>';
      $return[]='</div>';
    }
  }
  $return[]='</div></div></div>';
  $replace_from=array("'","\n","\t","\r");
  $replace_to=array("\'","","","");
  return str_replace($replace_from,$replace_to,implode('',$return));
};

if(is_array($list) && count($list)) : 

?>
<script>
function initialize_jsnmap<?php echo $module->id; ?>() {
  jQuery('#jsnmap<?php echo $module->id; ?>-canvas').removeClass('empty');
<?php if($cluster) : ?>
  MarkerClusterer.prototype.MARKER_CLUSTER_IMAGE_PATH_ =
    '<?php echo JURI::root(); ?>components/com_jsn/assets/img/m';
<?php endif; ?>
  var mapOptions = {
    zoom: 4,
    <?php 
      if($mapdisablescroll) : ?>scrollwheel: false,<?php endif; 
      if($mapstyle=='light') : ?>styles: [{"featureType":"water","elementType":"geometry","stylers":[{"color":"#e9e9e9"},{"lightness":17}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":20}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffffff"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#ffffff"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":16}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":21}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#dedede"},{"lightness":21}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#ffffff"},{"lightness":16}]},{"elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#333333"},{"lightness":40}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#f2f2f2"},{"lightness":19}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#fefefe"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#fefefe"},{"lightness":17},{"weight":1.2}]}],<?php endif; 
      if($mapstyle=='dark') : ?>styles: [{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}],<?php endif;
      if($mapstyle=='custom' && $mapstyle_custom!='') : ?>styles: <?php echo $mapstyle_custom; ?>,<?php endif; ?>
    mapTypeId: google.maps.MapTypeId.ROADMAP
  };

  var jsnmap<?php echo $module->id; ?> = new google.maps.Map(document.getElementById('jsnmap<?php echo $module->id; ?>-canvas'), mapOptions);
  var markers<?php echo $module->id; ?> = [];
  var bounds<?php echo $module->id; ?> = new google.maps.LatLngBounds();
  var size=new google.maps.Size(<?php echo $params->def('mapimagewidth', 25); ?>, <?php echo $params->def('mapimageheight', 25); ?>);
  var infowindow<?php echo $module->id; ?> = new google.maps.InfoWindow();
  <?php if($params->def('mapimage', 'pin')=='avatar') : ?>
  var myoverlay = new google.maps.OverlayView();
    myoverlay.draw = function () {
        this.getPanes().markerLayer.id='markerLayer<?php echo $module->id; ?>';
    };
  myoverlay.setMap(jsnmap<?php echo $module->id; ?>);
  <?php endif; ?>
<?php 
$tot=0;
foreach($list as $item) : 
  $user = JsnHelper::getUser($item->id);

  $map_aliases=$params->def('mapfield', false);
  if(is_string($map_aliases)) $map_aliases=array($map_aliases); // Compatibility with old gmap module

  foreach($map_aliases as $map_alias) :
    $map_lat=$map_alias.'_lat';
    $map_lng=$map_alias.'_lng';

    if(isset($user->$map_lat) && $user->$map_lat && isset($user->$map_lng) && $user->$map_lng) :
      $tot+=1;
?>
  var contentString<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?> = '<?php echo $infowindow($user,$params,$jsnConfig,$fields_title); ?>';
  var marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?> = new google.maps.Marker({
      position: new google.maps.LatLng(<?php echo $user->$map_lat; ?>,<?php echo $user->$map_lng; ?>),<?php if(!$cluster) : ?>
      map: jsnmap<?php echo $module->id; ?>,<?php endif; ?>
      title: '<?php echo str_replace("'","\'",$user->getField('name')); ?>'<?php if($params->def('mapimage', 'pin')=='avatar') : ?>,
      optimized:false,
      icon: {
      url: '<?php echo (strpos($user->getValue('avatar_mini'),'://') === false ? JURI::root(true).'/' : '' ) . $user->getValue('avatar_mini'); ?>',
      scaledSize: size
    }<?php endif; ?>
  });
  google.maps.event.addListener(marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>, 'click', function() {
    infowindow<?php echo $module->id; ?>.close();
    infowindow<?php echo $module->id; ?>.setContent(contentString<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>);
    infowindow<?php echo $module->id; ?>.setPosition(marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>.getPosition());
    infowindow<?php echo $module->id; ?>.open(jsnmap<?php echo $module->id; ?>,marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>);
    <?php if($jsnConfig->get('avatarletters',0) && $jsnConfig->get('avatar',1)==1) : ?>LetterAvatar.transform();<?php endif; ?>
  });
  function jsnmap_link<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>(){
    jQuery('.profile<?php echo substr(md5($user->username),0,10); ?>').mouseover(function(event){
        google.maps.event.trigger(marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>, 'click' );
    });
  }
  jsnmap_link<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>();
  jQuery(document).ajaxStop(function(){jsnmap_link<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>();});

  bounds<?php echo $module->id; ?>.extend(marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>.getPosition());
  <?php if($cluster) : ?>markers<?php echo $module->id; ?>.push(marker<?php echo $user->id.'_'.$map_alias.'_'.$module->id; ?>);<?php endif; ?>

<?php
    endif;
  endforeach; 
endforeach;
?>
jsnmap<?php echo $module->id; ?>.fitBounds(bounds<?php echo $module->id; ?>);
<?php if($cluster) : ?>var markerCluster<?php echo $module->id; ?> = new MarkerClusterer(jsnmap<?php echo $module->id; ?>,markers<?php echo $module->id; ?>);<?php endif; ?>
<?php if($tot==1 && !$params->def('current_location', 0)) : ?>google.maps.event.addListenerOnce(jsnmap<?php echo $module->id; ?>, 'bounds_changed', function(event) {
        if (jsnmap<?php echo $module->id; ?>.getZoom()){
            jsnmap<?php echo $module->id; ?>.setZoom(17);
        }
 });<?php endif; ?>
<?php if($params->def('current_location', 0)) : ?>
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(function(position) {
            var pos = {
              lat: position.coords.latitude,
              lng: position.coords.longitude
            }; 
            var marker0<?php echo '_'.$module->id; ?> = new google.maps.Marker({
                position: new google.maps.LatLng(pos),
                map: jsnmap<?php echo $module->id; ?>,
                icon: {
                  url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'                  
                }
            });
            bounds<?php echo $module->id; ?>.extend(marker0<?php echo '_'.$module->id; ?>.getPosition());
            jsnmap<?php echo $module->id; ?>.fitBounds(bounds<?php echo $module->id; ?>);
          });
        }
<?php endif; ?>
}
jQuery(window).load(function(){initialize_jsnmap<?php echo $module->id; ?>();});
//google.maps.event.addDomListener(window, 'load', initialize_jsnmap<?php echo $module->id; ?>);
</script>
<div id="jsnmap<?php echo $module->id; ?>-canvas" class="jsn-map empty" style="height:<?php echo $params->def('mapheight', 400) ?>px;"></div>
<style>
  #jsnmap<?php echo $module->id; ?>-canvas .jsn_listprofile{padding-right:20px;}
  <?php if($params->def('mapimage', 'pin')=='avatar') : ?>
  #markerLayer<?php echo $module->id; ?> img {
    border: 2px solid <?php echo ($mapstyle=='dark' ? '#555' : 'white'); ?> !important;
    border-radius: 4px;
  }
  #markerLayer<?php echo $module->id; ?> img {
    animation: jsnpulse .5s infinite alternate;
    -webkit-animation: jsnpulse .5s infinite alternate;
    transform-origin: center;
    -webkit-transform-origin: center;
  }
  <?php endif; ?>
</style>
<?php endif; ?>


