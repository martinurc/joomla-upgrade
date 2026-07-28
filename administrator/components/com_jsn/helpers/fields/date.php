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
$_FIELDTYPES['date']='COM_JSN_FIELDTYPE_DATE';

class JsnDateFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." DATETIME DEFAULT NULL";
		$db->setQuery($query);
		$db->query();
	}
	
	public static function delete($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users DROP COLUMN ".$db->quoteName($alias);
		$db->setQuery($query);
		$db->query();
	}
	
	public static function getXml($item)
	{
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$xml='';
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		if($item->params->get('date_default','')!="") $default=date('Y-m-d',strtotime($item->params->get('date_default','')));
		else $default="";
		$placeholder=($item->params->get('date_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('date_placeholder','')).'"' : 'hint="COM_JSN_CLICKONCALENDAR"');
		$formformat=($item->params->get('date_formformat','')!='' ? 'formformat="'.JsnHelper::xmlentities($item->params->get('date_formformat','')).'"' : 'formformat="d MM yyyy"');
		
		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonlydate="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonlydate="true"';
		else $readonly='';

		$xml='
			<field
				name="'.$item->alias.'"
				type="datefull"
				id="'.$item->alias.'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : JsnHelper::xmlentities($item->title)).'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				dateformat="'.$item->params->get('date_format','j F Y').'"
				size="22"
				datetype="'.$item->params->get('date_type',0).'"
				default="'.$default.'"
				required="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
				readonly="true"
				viewmode="'.$item->params->get('date_viewmode','days').'"
				span="'.$item->params->get('date_span',0).'"
				spanyear="'.$item->params->get('date_span_year',0).'"
				spanmonth="'.$item->params->get('date_span_month',0).'"
				spanday="'.$item->params->get('date_span_day',0).'"
				'.$placeholder.'
				'.$formformat.'
				'.$readonly.'
				class="'.$item->params->get('field_cssclass','').'"
			/>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		$alias=$field->alias;
		if(isset($user->$alias) && $user->$alias!='0000-00-00 00:00:00' && $user->$alias!='0000-00-00')
		{
			$date=new JDate($user->$alias);
			$data->$alias=$date->toSql();
		}
		else $data->$alias='';
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isSite()) return;
		$alias=$field->alias;
		if(isset($data[$alias]) && $data[$alias]!='0000-00-00 00:00:00' && $data[$alias]!='0000-00-00')
		{
			if($data[$alias]=='')
				$storeData[$alias]='';//'0000-00-00 00:00:00';
			else
			{
				$p1 = strpos($data[$alias],'-');
				$p2 = strrpos($data[$alias],'-');
				if( $p1 == 4 && $p2 == 7 ) $storeData[$alias] = $data[$alias];
			}
		}
	}
	
	public static function date($field)
	{
		$value=$field->__get('value');
		if (empty($value))
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			if ($value != '0000-00-00 00:00:00' && $value != '0000-00-00')
				return $field->getDate();
			else
				return JHtml::_('users.value', null);
		}
	}
	
	public static function getSearchInput($field)
	{
		if($field->params->get('date_type',0)!=1)
		{
			if(JText::_('COM_JSN_STARTMONDAY')=='1') $date_weekstart=' data-date-weekstart="1"';
			else $date_weekstart='';
			
			$doc=JFactory::getDocument();
			JHtml::_('bootstrap.framework');
			$doc->addScript(JURI::root().'components/com_jsn/assets/js/bootstrap-datepicker.min.js');
			$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/datepicker.min.css'); 
			$return=array();
			$return[]='<input id="jform_'.str_replace('-','_',$field->alias).'" type="hidden" name="'.$field->alias.'" value="1" /><div class=""><div class="bsdatesearch">';
			$return[]='<div'.$date_weekstart.' data-date-viewmode="'.$field->params->get('date_viewmode','days').'" data-date-format="'.JText::_('COM_JSN_DATE_INPUT_FORMAT').'" data-date="'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').'" id="'.$field->alias.'_from" class="input-prepend date bsdate"><span class="btn btn-success"><i class="jsn-icon jsn-icon-calendar"></i></span><span class="btn btn-danger"><i class="jsn-icon jsn-icon-remove jsndateremove"></i></span><input placeholder="'.JText::_('COM_JSN_STARTDATE').'" type="text" readonly="readonly" value="'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').'" name="'.$field->alias.'_from" /></div>';
			$return[]='</div> <div class="bsdatesearch">';
			$return[]='<div'.$date_weekstart.' data-date-viewmode="'.$field->params->get('date_viewmode','days').'" data-date-format="'.JText::_('COM_JSN_DATE_INPUT_FORMAT').'" data-date="'.JFactory::getApplication()->input->get($field->alias.'_to','','raw').'" id="'.$field->alias.'_to" class="input-prepend date bsdate"><span class="btn btn-success"><i class="jsn-icon jsn-icon-calendar"></i></span><span class="btn btn-danger"><i class="jsn-icon jsn-icon-remove jsndateremove"></i></span><input placeholder="'.JText::_('COM_JSN_ENDDATE').'" type="text" readonly="readonly" value="'.JFactory::getApplication()->input->get($field->alias.'_to','','raw').'" name="'.$field->alias.'_to" /></div>';
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
		}
		else
		{
			$return=array();
			$return[]='<input id="jform_'.str_replace('-','_',$field->alias).'" type="hidden" name="'.$field->alias.'" value="1" /><div class=""><div class="numericsearch">';		
			$return[]='<input min="0" type="number" placeholder="'.JText::_('COM_JSN_STARTAGE').'" name="'.$field->alias.'_from" value="'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').'"/>';
			$return[]='</div> <div class="numericsearch">';
			$return[]='<input min="0" type="number" placeholder="'.JText::_('COM_JSN_ENDAGE').'" name="'.$field->alias.'_to" value="'.JFactory::getApplication()->input->get($field->alias.'_to','','raw').'"/>';
			$return[]='</div><div class="numericsearchtip"><span class="label label-default">'.JText::_('COM_JSN_CHOOSEAGEINTERVAL').'</span></div></div>';
		}
		return implode('',$return);
	}
	
	public static function getSearchQuery($field, &$query)
	{	if($field->params->get('date_type',0)!=1)
		{
			$date_from=new JDate(str_replace('/','-',JFactory::getApplication()->input->get($field->alias.'_from','','raw')));
			$date_to=new JDate(str_replace('/','-',JFactory::getApplication()->input->get($field->alias.'_to','','raw')));
			$db=JFactory::getDbo();
			if(JFactory::getApplication()->input->get($field->alias.'_from','','raw')!='') $query->where('b.'.$db->quoteName($field->alias).' >= '.$db->quote($date_from->toSql()));
			if(JFactory::getApplication()->input->get($field->alias.'_to','','raw')!='') $query->where('b.'.$db->quoteName($field->alias).' <= '.$db->quote($date_to->toSql()));
		}
		else
		{
			if(is_numeric(JFactory::getApplication()->input->get($field->alias.'_from','','raw')) && JFactory::getApplication()->input->get($field->alias.'_from','','raw')>0){
				$date_from=date('Y-m-d',strtotime('-'.JFactory::getApplication()->input->get($field->alias.'_from','','raw').' years'));
				$db=JFactory::getDbo();
				$query->where('b.'.$db->quoteName($field->alias).' <= '.$db->quote($date_from));
			}
			else JFactory::getApplication()->input->set($field->alias.'_from','');
			if(is_numeric(JFactory::getApplication()->input->get($field->alias.'_to','','raw')) && JFactory::getApplication()->input->get($field->alias.'_to','','raw')>0){
				$date_to=date('Y-m-d',strtotime('-'.(JFactory::getApplication()->input->get($field->alias.'_to','','raw')+1).' years'));
				$db=JFactory::getDbo();
				$query->where('b.'.$db->quoteName($field->alias).' >= '.$db->quote($date_to));
			}
			else JFactory::getApplication()->input->set($field->alias.'_to','');
		}
		
	}

}
