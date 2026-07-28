<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnRegisterdateFieldHelper
{
	public static function create($alias)
	{
		
	}
	
	public static function delete($alias)
	{
		
	}
	
	public static function getXml($item)
	{
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$xml='';
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		if(JFactory::getApplication()->isSite())
			$xml='
				<field
					name="registerdate"
					type="registerdate"
					class="readonly '.$item->params->get('field_cssclass','').'"
					label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
					description=""
					readonly="true"
					format="%Y-%m-%d %H:%M:%S"
					size="22"
					filter="user_utc"
				/>
			';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		if(isset($data->registerDate)) $data->registerdate=$data->registerDate;
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		
	}
	
	public static function registerdate($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			return JHtml::_('date', $value);
		}
	}
	
	public static function getSearchInput($field)
	{
		if(JText::_('COM_JSN_STARTMONDAY')=='1') $date_weekstart=' data-date-weekstart="1"';
		else $date_weekstart='';
		
		$doc=JFactory::getDocument();
		JHtml::_('bootstrap.framework');
		$doc->addScript(JURI::root().'components/com_jsn/assets/js/bootstrap-datepicker.js');
		$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/datepicker.css'); 
		$return=array();
		$return[]='<input id="jform_'.str_replace('-','_',$field->alias).'" type="hidden" name="'.$field->alias.'" value="1" /><div class=""><div class="bsdatesearch">';
		$return[]='<div'.$date_weekstart.' data-date-viewmode="'.$field->params->get('date_viewmode','days').'" data-date-format="'.JText::_('COM_JSN_DATE_INPUT_FORMAT').'" data-date="'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').'" id="'.$field->alias.'_from" class="input-prepend date bsdate"><span class="btn btn-success"><i class="icon icon-calendar"></i></span><span class="btn btn-danger"><i class="icon icon-remove jsndateremove"></i></span><input placeholder="'.JText::_('COM_JSN_STARTDATE').'" type="text" readonly="readonly" value="'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').'" name="'.$field->alias.'_from" /></div>';
		$return[]='</div><div class="bsdatesearch">';
		$return[]='<div'.$date_weekstart.' data-date-viewmode="'.$field->params->get('date_viewmode','days').'" data-date-format="'.JText::_('COM_JSN_DATE_INPUT_FORMAT').'" data-date="'.JFactory::getApplication()->input->get($field->alias.'_to','','raw').'" id="'.$field->alias.'_to" class="input-prepend date bsdate"><span class="btn btn-success"><i class="icon icon-calendar"></i></span><span class="btn btn-danger"><i class="icon icon-remove jsndateremove"></i></span><input placeholder="'.JText::_('COM_JSN_ENDDATE').'" type="text" readonly="readonly" value="'.JFactory::getApplication()->input->get($field->alias.'_to','','raw').'" name="'.$field->alias.'_to" /></div>';
		$return[]='</div><div class="bsdatesearchtip"><span class="label label-default">'.JText::_('COM_JSN_CHOOSEDATEINTERVAL').'</span></div></div>';
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
		$script.='
		jQuery(document).ready(function($){
			var nowTemp = new Date();
			var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);
			$("#'.$field->alias.'_from").jsndatepicker();
			$("#'.$field->alias.'_to").jsndatepicker();
		
		
			$("#'.$field->alias.'_from .jsndateremove").click(function(){
				//'.$field->alias.'_from.setValue(newDate);
				$("#'.$field->alias.'_from input").val("");
				return false;
			});
			$("#'.$field->alias.'_to .jsndateremove").click(function(){
				//'.$field->alias.'_to.setValue(newDate);
				//$("#'.$field->alias.'_to").attr("data-date","");
				$("#'.$field->alias.'_to input").val("");
				return false;
			});
		});
		';
		$doc->addScriptDeclaration( $script );
		return implode('',$return);
		
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$date_from=new JDate(str_replace('/','-',JFactory::getApplication()->input->get($field->alias.'_from','','raw')));
		$date_to=new JDate(str_replace('/','-',JFactory::getApplication()->input->get($field->alias.'_to','','raw')));
		$db=JFactory::getDbo();
		//$from=JDate::getInstance((JFactory::getApplication()->input->get($field->alias.'_from','')=='' ? '0000-00-00' : $date_from->toSql()));
		//$to=JDate::getInstance((JFactory::getApplication()->input->get($field->alias.'_to','')=='' ? '9999-00-00' : $date_to->toSql()));
		if(JFactory::getApplication()->input->get($field->alias.'_from','','raw')!='') $query->where('a.'.$db->quoteName('registerDate').' > '.$db->quote($date_from->toSql()));
		if(JFactory::getApplication()->input->get($field->alias.'_to','','raw')!='') $query->where('a.'.$db->quoteName('registerDate').' < '.$db->quote($date_to->toSql()));
	}

}
