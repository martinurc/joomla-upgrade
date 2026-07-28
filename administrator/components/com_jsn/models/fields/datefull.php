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

class JFormFieldDatefull extends JFormFieldHidden
{
	public $type = 'Date';
	
	public function getDate(){
		if($this->element['datetype']==0)
			return JHtml::_('date', $this->value, $this->element['dateformat'],'UTC');
			
		if($this->element['datetype']==1)
		{
			$birthDate=JHtml::_('date', $this->value, 'm/d/Y','UTC');
			$birthDate = explode("/", $birthDate);
			$age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md") ? ((date("Y")-$birthDate[2])-1):(date("Y")-$birthDate[2]));
			
			if( $age == 1 ) return $age.' '.JText::_('COM_JSN_YEAR');
			else return $age.' '.JText::_('COM_JSN_YEARS');
		}
			
		if($this->element['datetype']==2)
		{
			$years=date('Y')-JHtml::_('date', $this->value, 'Y','UTC');
			if( $years == 0 ) return JText::_('COM_JSN_THISYEAR');
			if( $years == 1 ) return JText::_('COM_JSN_YEARAGO');
			else return $years.' '.JText::_('COM_JSN_YEARSAGO');
		}
			
	}
	
	public function getInput()
	{
		$doc=JFactory::getDocument();
		JHtml::_('bootstrap.framework');
		$doc->addScript(JURI::root().'components/com_jsn/assets/js/bootstrap-datepicker.min.js');
		$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/datepicker.min.css');
		$span='';
		if((int)$this->element['span'])
		{
			$span='
			{
			  onRender: function(date) {
			    return date.valueOf() '.((int)$this->element['span']==1 ? '>' : '<').' now.valueOf() ? "disabled" : "";
			  }
			}
			';
		}
		static $init=0;
		if($init==0){
			$script='
			var DPGlobalDates = {
				days: ["'.JText::_("SUNDAY").'", "'.JText::_("MONDAY").'", "'.JText::_("TUESDAY").'", "'.JText::_("WEDNESDAY").'", "'.JText::_("THURSDAY").'", "'.JText::_("FRIDAY").'", "'.JText::_("SATURDAY").'", "'.JText::_("SUNDAY").'"],
				daysShort: ["'.JText::_("SUN").'", "'.JText::_("MON").'", "'.JText::_("TUE").'", "'.JText::_("WED").'", "'.JText::_("THU").'", "'.JText::_("FRI").'", "'.JText::_("SAT").'", "'.JText::_("SUN").'"],
				daysMin: ["'.JText::_("SUN").'", "'.JText::_("MON").'", "'.JText::_("TUE").'", "'.JText::_("WED").'", "'.JText::_("THU").'", "'.JText::_("FRI").'", "'.JText::_("SAT").'", "'.JText::_("SUN").'"],
				months: ["'.JText::_("JANUARY").'", "'.JText::_("FEBRUARY").'", "'.JText::_("MARCH").'", "'.JText::_("APRIL").'", "'.JText::_("MAY").'", "'.JText::_("JUNE").'", "'.JText::_("JULY").'", "'.JText::_("AUGUST").'", "'.JText::_("SEPTEMBER").'", "'.JText::_("OCTOBER").'", "'.JText::_("NOVEMBER").'", "'.JText::_("DECEMBER").'"],
				monthsShort: ["'.JText::_("JANUARY_SHORT").'", "'.JText::_("FEBRUARY_SHORT").'", "'.JText::_("MARCH_SHORT").'", "'.JText::_("APRIL_SHORT").'", "'.JText::_("MAY_SHORT").'", "'.JText::_("JUNE_SHORT").'", "'.JText::_("JULY_SHORT").'", "'.JText::_("AUGUST_SHORT").'", "'.JText::_("SEPTEMBER_SHORT").'", "'.JText::_("OCTOBER_SHORT").'", "'.JText::_("NOVEMBER_SHORT").'", "'.JText::_("DECEMBER_SHORT").'"]
			};
			';
		}
		else $script='';
		$init=1;
		if(isset($this->element['readonlydate']) && $this->element['readonlydate']) $script.='';
		else $script.='
		jQuery(document).ready(function($){
			var nowTemp = new Date();
			var now = new Date(nowTemp.getFullYear()'.((int)$this->element['spanyear']>=0 ? '+'.$this->element['spanyear'] : $this->element['spanyear']).', nowTemp.getMonth()'.((int)$this->element['spanmonth']>=0 ? '+'.$this->element['spanmonth'] : $this->element['spanmonth']).', nowTemp.getDate()'.((int)$this->element['spanday']>=0 ? '+'.$this->element['spanday'] : $this->element['spanday']).', 0, 0, 0, 0);
			$("#wrapper'.$this->id.'").jsndatepicker('.$span.');
			$("#wrapper'.$this->id.' .jsndateremove").click(function(e){
				$("#wrapper'.$this->id.' input").val("").change();
				$("#wrapper'.$this->id.'").jsndatepicker(\'hide\');
				return false;
			});
		});
		';
		$format=$this->element['formformat'];

		$value='';
		if($this->value!='') {
			$format_date=str_replace('dd','z',$format);
			$format_date=str_replace('d','j',$format_date);
			$format_date=str_replace('z','d',$format_date);
			$format_date=str_replace('mm','m',$format_date);
			$format_date=str_replace('MM','F',$format_date);
			$format_date=str_replace('yyyy','Y',$format_date);
			$date=new JDate($this->value);
			$value=$date->format($format_date);//date($format_date,strtotime($this->value));
		}
		if(JText::_('COM_JSN_STARTMONDAY')=='1') $date_weekstart=' data-date-weekstart="1"';
		else $date_weekstart='';
		
		if(isset($this->element['readonlydate']) && $this->element['readonlydate']) $readonly = true;
		else $readonly = false;

		$doc->addScriptDeclaration( $script );
		return '<div'.$date_weekstart.' data-date-viewmode="'.$this->element['viewmode'].'" data-date-format="'.$format.'" data-date="'.$value.'" id="wrapper'.$this->id.'" class="input-prepend date bsdate">'.($readonly ? '' : '<span class="btn btn-success"><i class="jsn-icon jsn-icon-calendar"></i></span><span class="btn btn-danger jsndateremove"><i class="jsn-icon jsn-icon-remove "></i></span>').'<input type="text" readonly="readonly" value="'.$value.'" placeholder="'.($readonly ? JText::_('COM_USERS_PROFILE_VALUE_NOT_FOUND') : JText::_($this->element['hint'])).'"/>'.parent::getInput().'</div>';
	}

}
