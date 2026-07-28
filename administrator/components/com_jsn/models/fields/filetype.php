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
 * @subpackage  Form
 * @since       3.1
 */
class JFormFieldFiletype extends JFormFieldHidden
{
	public $type = 'Filetype';
	
	protected function getInput()
	{
		
		$html=array();

		// Add Bootstrap JS
		$doc = JFactory::getDocument();
		JHtml::_('bootstrap.framework');
		
		$max_post     = ini_get('post_max_size');
		$max_upload   = ini_get('upload_max_filesize');
		$memory_limit = ini_get('memory_limit');
		$upload_limit = min(JFormFieldFiletype::convertPHPSizeToBytes($max_upload), JFormFieldFiletype::convertPHPSizeToBytes($max_post), JFormFieldFiletype::convertPHPSizeToBytes($memory_limit));
		
		$script='
			jQuery(function($) {
				$(\'input[name="'.str_replace(']', '_delete]', $this->name).'"]\').change(function(){
					if($(this).is(":checked")){
						$(\'#jform_'.$this->element['name'].'\').attr(\'oldvalue\',$(\'#jform_'.$this->element['name'].'\').val());
						$(\'#jform_'.$this->element['name'].'\').val(\'\');
						$(\'#jform_'.$this->element['name'].'\').change();
					} 
					else 
					{
						$(\'#jform_'.$this->element['name'].'\').val($(\'#jform_'.$this->element['name'].'\').attr(\'oldvalue\'));
						$(\'#jform_'.$this->element['name'].'\').change();
					} 
				});
				$(\'#jform_upload_'.$this->element['name'].'\').change(function(){
					$(\'#jform_'.$this->element['name'].'\').val(\'true\');
					$(\'#jform_'.$this->element['name'].'\').change();
				});
			});';
		$doc->addScriptDeclaration( $script );

		if(isset($this->element['readonly']) && $this->element['readonly']) $readonly = 'readonly="readonly" disabled="disabled"';
		else $readonly = '';

		$html[]='<input '.$readonly.' jsn-extensions="'.$this->element['mime'].'" type="file" name="jform[upload_'.$this->element['name'].']" id="jform_upload_'.$this->element['name'].'" class="validate-fileext">';
		$html[]='<div style="margin-bottom:5px;">'.JText::_('COM_JSN_ALLOWED_EXTENSIONS').' <b>'.str_replace('|',', ',$this->element['mime']).'</b>. '.JText::_('COM_JSN_ALLOWED_SIZE').' <b>'.JFormFieldFiletype::formatBytes($upload_limit).'</b>.</div>';
		
		$html[]=parent::getInput();

		if($this->element['required']!='true' && $this->value && file_exists(JPATH_SITE.'/'.$this->value)) $html[]='<fieldset class="checkboxes" style="clear:both;"><label class="checkbox"><input type="checkbox" name="'.str_replace(']', '_delete]', $this->name).'"/><span>'.JText::_('JACTION_DELETE').': <a href="'.JURI::root(true).'/'.$this->value.'" target="_blank">'.substr($this->value, strrpos($this->value, '/')+1).'</a></span></label></fieldset>';
		if($this->element['required']=='true' && $this->value && file_exists(JPATH_SITE.'/'.$this->value)) $html[]='<a href="'.JURI::root(true).'/'.$this->value.'" target="_blank">'.substr($this->value, strrpos($this->value, '/')+1).'</a>';
		
		return implode('', $html);
		
	}
	
	public function getFile()
	{
		$attribs=array(
			'class' => 'file '.$this->element['class']
		);
		$html=array();
		
		if($this->value) $html[]='<div><a target="_blank" class="btn btn-mini btn-sm btn-default" href="'.$this->value.'"><i class="icon-download"></i> '.JText::_($this->element['downloadtext']).'</a></div>';
		return implode('', $html);
	}
	
	public function getValue()
	{
		return $this->value;
	}

	/*public function getLabel()
	{
		return str_replace('jform_','jform_upload_',parent::getLabel());
	}*/
	
	static function convertPHPSizeToBytes($sSize)  
	{  
	    if ( is_numeric( $sSize) ) {
	       return $sSize;
	    }
	    $sSuffix = substr($sSize, -1);  
	    $iValue = substr($sSize, 0, -1);  
	    switch(strtoupper($sSuffix)){  
	    case 'P':  
	        $iValue *= 1024;  
	    case 'T':  
	        $iValue *= 1024;  
	    case 'G':  
	        $iValue *= 1024;  
	    case 'M':  
	        $iValue *= 1024;  
	    case 'K':  
	        $iValue *= 1024;  
	        break;  
	    }  
	    return $iValue;  
	}
	
	static function formatBytes($bytes, $precision = 2) { 
	    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

	    $bytes = max($bytes, 0); 
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	    $pow = min($pow, count($units) - 1); 

	    $bytes /= pow(1024, $pow); 

	    return round($bytes, $precision) . ' ' . $units[$pow]; 
	}
	
	
}
