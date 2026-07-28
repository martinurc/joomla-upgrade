<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;




JFormHelper::loadFieldClass('hidden');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package     Joomla.Libraries
 * @subpackage  format
 * @since       3.1
 */
class JFormFieldImagefull extends JFormFieldHidden
{
	public $type = 'Image';
	
	protected function getInput()
	{
		$doc = JFactory::getDocument();
		$dir = $doc->direction;

		$jsn_config = JComponentHelper::getParams('com_jsn');
		if($jsn_config->get('avatar',1) == 2 && $this->element['name'] == 'avatar') // Gravatar
		{
			return JText::_('COM_JSN_AVATAR_WITH_GRAVATAR');
		}
			
		$this->element['class']='';
		$attribs=array(
			'style' => 'float:left;width:50px;margin-right:10px;border-radius:2px;margin-bottom:5px;',
			'class' => 'img_'.$this->element['name']
		);
		if($dir=='rtl')
		{
			$attribs=array(
				'style' => 'float:right;width:50px;margin-left:10px;border-radius:2px;margin-bottom:5px;',
				'class' => 'img_'.$this->element['name']
			);
		}
		$html=array();

		// Check Mobile
		$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
		$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
		$palmpre = strpos($_SERVER['HTTP_USER_AGENT'],"webOS");
		$berry = strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
		$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
		$windowsphone = strpos($_SERVER['HTTP_USER_AGENT'],"Windows Phone");
		if($windowsphone || $iphone || $android || $palmpre || $ipod || $berry == true) $mobile=true;
		else $mobile=false;

		// Add Bootstrap JS
		JHtml::_('bootstrap.framework');

		if(isset($this->element['readonly']) && $this->element['readonly']) {$readonly = 'readonly="readonly" disabled="disabled"';$this->element['cropwebcam']=0;}
		else $readonly = '';

		switch($this->element['cropwebcam']){
			default:
			case '0': /* INPUT */

				// Set Default
				if($this->element['name']=='avatar') $default_image='components/com_jsn/assets/img/default.jpg';
				else $default_image='components/com_jsn/assets/img/no_image.gif';

				if(isset($this->element['default'])) $default_image=$this->element['default'];
				if(isset($this->element['removed_default'])) $default_image=$this->element['removed_default'];

				// Clear value inherit by default value
				if($this->value==$default_image) $this->value='';

				// Image Preview
				if(empty($this->value) || $this->value=='true') 
				{
					$this->value='';
					$session = JFactory::getSession();
					$session->set('_tmp_img_'.$this->element['name'],'');
					$img_src=$default_image;
					$html[]=JHtml::_('image', $default_image ,$this->element['alt'],$attribs);
				}
				else 
				{
					$session = JFactory::getSession();
					$session->set('_tmp_img_'.$this->element['name'],'');
					$img_src=$this->value;
					$html[]=JHtml::_('image', preg_replace('~_(?!.*_)~', 'mini_', $this->value) ,$this->element['alt'],$attribs);
				}
				$script='
				jQuery(function($) {
					$(\'input[name="'.str_replace(']', '_delete]', $this->name).'"]\').change(function(){
						if($(this).is(":checked")){
							$(\'#'.$this->id.'\').attr(\'oldvalue\',$(\'#'.$this->id.'\').val());
							$(\'#'.$this->id.'\').val(\'\');
							$(\'#'.$this->id.'\').change();
						} 
						else 
						{
							$(\'#'.$this->id.'\').val($(\'#'.$this->id.'\').attr(\'oldvalue\'));
							$(\'#'.$this->id.'\').change();
						} 
					});
					$(\'#jform_upload_'.$this->element['name'].'\').change(function(){
						$(\'#'.$this->id.'\').val(\'true\');
						$(\'#'.$this->id.'\').change();
					});
				});';
				$doc->addScriptDeclaration( $script );

				$html[]='<input '.$readonly.' type="file" name="jform[upload_'.$this->element['name'].']" id="jform_upload_'.$this->element['name'].'" accept="image/*">';
				$html[]=parent::getInput();
				if($this->element['required']!='true' && $this->value) $html[]='<fieldset class="checkboxes" style="clear:both;"><label class="checkbox"><input type="checkbox" name="'.str_replace(']', '_delete]', $this->name).'"/><span>'.JText::_('COM_JSN_DELETE_IMAGE').'</span></label></fieldset>';
				$html[]='<div style="clear:both"></div>';

			break;
			case '1': /* CROP & WEBCAM */

				// Set Default
				if($this->element['name']=='avatar') $default_image='components/com_jsn/assets/img/default.jpg';
				else $default_image='components/com_jsn/assets/img/no_image.gif';

				if(isset($this->element['default'])) $default_image=$this->element['default'];
				if(isset($this->element['removed_default'])) $default_image=$this->element['removed_default'];

				// Clear value inherit by default value
				if($this->value==$default_image) $this->value='';

				// Image Preview
				$isTmp=strpos($this->value, '_tmp');
				if(empty($this->value) || $this->value=='true') 
				{
					$this->value='';
					$session = JFactory::getSession();
					$session->set('_tmp_img_'.$this->element['name'],'');
					$img_src=$default_image;
					$html[]=JHtml::_('image', $default_image ,$this->element['alt'],$attribs);
				}
				elseif($isTmp)
				{
					$img_src=$this->value;
					$img_src=substr($img_src,strpos($img_src,'images/_tmp/'));
					$img_src=substr($img_src,0,strrpos($img_src,'?'));
					$html[]=JHtml::_('image',$img_src ,$this->element['alt'],$attribs);
				}
				else 
				{
					$session = JFactory::getSession();
					$session->set('_tmp_img_'.$this->element['name'],'');
					$img_src=$this->value;
					$html[]=JHtml::_('image', preg_replace('~_(?!.*_)~', 'mini_', $this->value) ,$this->element['alt'],$attribs);
				}

				$doc->addScript(JURI::root().'components/com_jsn/assets/js/jquery.Jcrop.min.js');
				$doc->addScript(JURI::root().'components/com_jsn/assets/js/jquery.imgpicker.min.js');
				$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/imgpicker.min.css');

				if(JFactory::getApplication()->isClient('administrator')) $admin_path='administrator/';
				else $admin_path='';
				
				$width=$this->element['width'];
				$height=$this->element['height'];
				
				$html[]='
					<div class="jsn_'.$this->element['name'].'-container">
						<button type="button" class="btn btn-danger" data-ip-modal="#jsn_'.$this->element['name'].'Modal"><i class="jsn-icon jsn-icon-pencil"></i> '.JText::_('COM_JSN_IMAGE_CHANGE').'</button>
					</div>';
				$modal='
					<div class="ip-modal" id="jsn_'.$this->element['name'].'Modal">
						<div class="ip-modal-dialog">
							<div class="ip-modal-content">
								<div class="ip-modal-header">
									<a class="ip-close" title="'.JText::_('JLIB_HTML_BEHAVIOR_CLOSE').'">&times;</a>
									<h4 class="ip-modal-title">'.JText::sprintf('COM_JSN_IMAGE_MODALTITLE',JText::_($this->element['label'])).'</h4>
								</div>
								<div class="ip-modal-body">
									<div class="btn btn-primary ip-upload">'.JText::_('COM_JSN_IMAGE_UPLOAD').' <input type="file" name="file" class="ip-file"></div>
									'.($mobile || $this->element['cropwebcam']=='2' ? '' : '<button type="button" class="btn btn-primary ip-webcam">'.JText::_('COM_JSN_IMAGE_WEBCAM').'</button>').'
									<button type="button" class="btn btn-info ip-edit">'.JText::_('JACTION_EDIT').'</button>
									<button type="button" class="btn btn-danger ip-delete">'.JText::_('JACTION_DELETE').'</button>
									
									<div class="alert ip-alert"></div>
									<div class="ip-info">'.JText::_('COM_JSN_CROPWEBCAM_TIP').'</div>
									<div class="ip-preview"></div>
									<div class="ip-rotate">
										<button type="button" class="btn btn-default ip-rotate-ccw" title="Rotate counter-clockwise"><i class="icon-ccw"></i></button>
										<button type="button" class="btn btn-default ip-rotate-cw" title="Rotate clockwise"><i class="icon-cw"></i></button>
									</div>
									<div class="ip-progress">
										<!-- <div class="text">Uploading</div> -->
										<div class="progress progress-striped active"><div class="progress-bar bar"></div></div>
									</div>
								</div>
								<div class="ip-modal-footer">
									<div class="ip-actions">
										<button type="button" class="btn btn-success ip-save">'.JText::_('COM_JSN_IMAGE_SAVEIMAGE').'</button>
										<button type="button" class="btn btn-primary ip-capture">'.JText::_('COM_JSN_IMAGE_CAPTURE').'</button>
										<button type="button" class="btn btn-default ip-cancel">'.JText::_('COM_JSN_IMAGE_CANCEL').'</button>
									</div>
									<button type="button" class="btn btn-default ip-close">'.JText::_('JLIB_HTML_BEHAVIOR_CLOSE').'</button>
								</div>
							</div>
						</div>
					</div>';
				$script='
				jQuery(function($) {
					$(\'body\').append(\''.str_replace(array("\n","\r","'"),array("","","\'"),$modal).'\');
					$(\'input[name="'.str_replace(']', '_delete]', $this->name).'"]\').change(function(){
						if($(this).is(":checked")){
							$(\'#'.$this->id.'\').attr(\'oldvalue\',$(\'#'.$this->id.'\').val());
							$(\'#'.$this->id.'\').val(\'\');
							$(\'#'.$this->id.'\').change();
						} 
						else 
						{
							$(\'#'.$this->id.'\').val($(\'#'.$this->id.'\').attr(\'oldvalue\'));
							$(\'#'.$this->id.'\').change();
						} 
					});
					$(\'#jsn_'.$this->element['name'].'Modal\').imgPicker({
						url: \''.JURI::root(true).'/'.$admin_path.'index.php?option=com_jsn&view=opField&type=image&format=raw&field='.$this->element['name'].'\',
						aspectRatio: '.$width.'/'.$height.', /* Crop aspect ratio */
						setSelect: [0,0,1000,1000],
						swf: \''.JURI::root().'components/com_jsn/assets/webcam.swf\',
						/* Delete callback */
						deleteComplete: function() {
							$(\'.img_'.$this->element['name'].'\').attr(\'src\', \''.JURI::root(true).'/'.$img_src.'\');
							$(\'#'.$this->id.'\').val(\''.$this->value.'\');
							$(\'#'.$this->id.'\').change();
							$(\'input[name="'.str_replace(']', '_delete]', $this->name).'"]\').prop(\'checked\', false);
							this.modal(\'hide\');
						},
						/* Crop success callback */
						cropSuccess: function(image) {
							$(\'.img_'.$this->element['name'].'\').attr(\'src\', image.versions.mini.url + time());
							$(\'#'.$this->id.'\').val(image.versions.mini.url + time());
							$(\'#'.$this->id.'\').change();
							$(\'input[name="'.str_replace(']', '_delete]', $this->name).'"]\').prop(\'checked\', false); 
							this.modal(\'hide\');
						},
						/* Send some custom data to server */
						data: {
							key: \'value\',
						},
						/* Translated Messages */
						messages: {
							selectimg: \''.JText::_('COM_JSN_IMAGE_ALERT_SELECTIMG').'\',
							uploading: \''.JText::_('COM_JSN_IMAGE_ALERT_UPLOADING').'\',
	            			loading: \''.JText::_('COM_JSN_IMAGE_ALERT_LOADING').'\',
	            			saving: \''.JText::_('COM_JSN_IMAGE_ALERT_SAVING').'\',
						}
					});
				});';
				$doc->addScriptDeclaration( $script );
				$html[]=parent::getInput();

				if($this->element['required']!='true' && $this->value) $html[]='<fieldset class="checkboxes" style="clear:both;"><label class="checkbox"><input type="checkbox" name="'.str_replace(']', '_delete]', $this->name).'"/><span>'.JText::_('COM_JSN_DELETE_IMAGE').'</span></label></fieldset>';
				$html[]='<div style="clear:both"></div>';

			break;
			case '2': /* CROP & DD */

				// Reset Value
				if($this->value=='true') 
				{
					$this->value='';
				}

				// Set Default
				if($this->element['name']=='avatar') $default_image='components/com_jsn/assets/img/default.jpg';
				else $default_image='components/com_jsn/assets/img/no_image.gif';

				if(isset($this->element['default'])) $default_image=$this->element['default'];
				if(isset($this->element['removed_default'])) $default_image=$this->element['removed_default'];

				// Clear value inherit by default value
				if($this->value==$default_image) $this->value='';

				// Clear Session
				$session = JFactory::getSession();
				$session->set('_tmp_img_'.$this->element['name'],'');

				$doc->addScript(JURI::root().'components/com_jsn/assets/js/jquery.slim.min.js');
				$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/slim.min.css');
				
				$width=$this->element['width'];
				$height=$this->element['height'];
				
				$json=json_decode($this->value);
				$isJSON=(json_last_error() == JSON_ERROR_NONE);

				$html[]='<div id="imgupload_'.$this->element['name'].'" class="slim" style="width:120px;border-radius:5px;overflow:hidden;background-image:url('.JURI::root(true).'/'.$default_image.');background-repeat:no-repeat;background-size:cover;">';
				$html[]='<input type="file" name="imgupload_'.$this->element['name'].'"/>';
				if(!empty($this->value)) $html[] = '<img id="imgpreview_'.$this->element['name'].'" src="" />';
				$html[]='</div>';
				$html[]=parent::getInput();
				
				$script='jQuery(document).ready(function($){
					var init_'.$this->id.'='.(empty($this->value) ? 'false' : 'true').';
					'.(!empty($this->value) && $isJSON ? '$("#imgpreview_'.$this->element['name'].'").attr("src",$.parseJSON($("#'.$this->id.'").val()).output.image);' : '').'
					'.(!empty($this->value) && !$isJSON ? '$("#imgpreview_'.$this->element['name'].'").attr("src","'.JURI::root(true).'/"+$("#'.$this->id.'").val());' : '').'
					$("#imgupload_'.$this->element['name'].'").slim({
						label: "<p>'.str_replace("'","\'",JText::_('COM_JSN_IMAGE_HERE')).'</p>",
						buttonConfirmLabel: "'.str_replace("'","\'",JText::_('JSUBMIT')).'",
						buttonCancelLabel: "'.str_replace("'","\'",JText::_('JCANCEL')).'",
						ratio: "'.$width.':'.$height.'",
						size: {width: '.$width.',height: '.$height.'},
						didRemove: function(){$("#'.$this->id.'").val(\'\').change();},
						didTransform: function(){
							if(init_'.$this->id.') {
								$("#imgupload_'.$this->element['name'].' .slim-btn.slim-btn-edit").hide();
								'.(!empty($this->value) && $isJSON ? '$(\'input[name="imgupload_'.$this->element['name'].'"]\').val($("#'.$this->id.'").val());' : '').'
							}
							else {
								$(\'input[name="imgupload_'.$this->element['name'].'"]\').val(JSON.stringify(this.dataBase64));
								$("#'.$this->id.'").val(JSON.stringify(this.dataBase64)).change();
								$("#imgupload_'.$this->element['name'].' .slim-btn.slim-btn-edit").show();
							}
							init_'.$this->id.'=false;
						},
					});
				});';

				$doc->addScriptDeclaration( $script );
			break;
		}
		
		return implode('', $html);
		
	}
	
	public function getImage($user)
	{
		$attribs=array(
			'class' => $this->element['imageclass']
		);

		if($this->name == 'avatar' && empty($user->avatar_clean))
		{
			$attribs['avatar'] = strip_tags(JsnHelper::getFormatName($user));
		}

		if(empty($this->value)){
			if($this->element['name']=='avatar') $default_image='components/com_jsn/assets/img/default.jpg';
			else $default_image='components/com_jsn/assets/img/no_image.gif';

			if(isset($this->element['default'])) $default_image=$this->element['default'];
			if(isset($this->element['removed_default'])) $default_image=$this->element['removed_default'];
			$this->value = $default_image;
		}
		
		$html=array();
		
		if($this->value) $html[]=JHtml::_('image', $this->value,$this->element['alt'],$attribs);
		return implode('', $html);
	}
	
	public function getValue()
	{
		return $this->value;
	}

	public function getElement()
	{
		return $this->element;
	}
	
	public function setElement($element)
	{
		$this->element = $element;
	}
	
	
	
}
