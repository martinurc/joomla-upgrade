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
$_FIELDTYPES['selectlist']='COM_JSN_FIELDTYPE_SELECT';

class JsnSelectlistFieldHelper
{
	public static function create($alias)
	{
		$db = JFactory::getDbo();
		$query = "ALTER TABLE #__jsn_users ADD ".$db->quoteName($alias)." VARCHAR(255)";
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
		$hideTitle= ($item->params->get('hidetitle',0) && JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn') || ($item->params->get('hidetitleedit',0) && (JFactory::getApplication()->input->get('layout','')=='edit' || JFactory::getApplication()->input->get('view','')=='registration'));
		if(JFactory::getApplication()->input->get('view','profile')=='profile' && JFactory::getApplication()->input->get('option','')=='com_jsn' && $item->params->get('titleprofile','')!='') $item->title=$item->params->get('titleprofile','');
		$defaultvalue=($item->params->get('select_defaultvalue','')!='' ? 'default="'.JsnHelper::xmlentities($item->params->get('select_defaultvalue','')).'"' : '');//(isset($item->params['text_defaultvalue']) && $item->params['text_defaultvalue']!='' ? 'default="'.$item->params['text_defaultvalue'].'"' : '');
		$multiple=($item->params->get('select_multiple',0) ? 'multiple="true"' : '');
		$dbtable=($item->params->get('select_dbopttable','')=='' ? '' : 'dbopttable="'.$item->params->get('select_dbopttable','').'"');
		$dbvalue=($item->params->get('select_dboptvalue','')=='' ? '' : 'dboptvalue="'.$item->params->get('select_dboptvalue','').'"');
		$dbtext=($item->params->get('select_dbopttext','')=='' ? '' : 'dbopttext="'.$item->params->get('select_dbopttext','').'"');
		$dbwhere=($item->params->get('select_dboptwhere','')=='' ? '' : 'dboptwhere="'.JsnHelper::xmlentities($item->params->get('select_dboptwhere','')).'"');
		$dbfiltervalue=($item->params->get('select_dboptfiltervalue','')=='' ? '' : 'dboptfiltervalue="'.JsnHelper::xmlentities($item->params->get('select_dboptfiltervalue','')).'"');
		$dbfiltercolumn=($item->params->get('select_dboptfiltercolumn','')=='' ? '' : 'dboptfiltercolumn="'.JsnHelper::xmlentities($item->params->get('select_dboptfiltercolumn','')).'"');
		$placeholder=($item->params->get('select_placeholder','')!='' ? 'hint="'.JsnHelper::xmlentities($item->params->get('select_placeholder','')).'"' : '');
		
		if($item->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		elseif($item->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('view')!='registration' && JFactory::getApplication()->isSite()) $readonly='readonly="true"';
		else $readonly='';

		$options=array();
		$optTxt=explode("\n",$item->params->get('select_options',''));
		foreach($optTxt as $opt)
		{
			$opt=trim($opt);
			if($opt!=''){
				$opt=explode('|',$opt);
				if(count($opt)==1) $options[]='<option value="'.trim(JsnHelper::xmlentities($opt[0])).'">'.trim(JsnHelper::xmlentities($opt[0])).'</option>';
				if(count($opt)==2) $options[]='<option value="'.trim(JsnHelper::xmlentities($opt[0])).'">'.trim(JsnHelper::xmlentities($opt[1])).'</option>';
			}
		}
		
		$xml='';
		
		if(!$item->params->get('select_multiple',0)) {
			if($item->params->get('select_placeholder','')=='') $noval='<option value="">COM_JSN_NOSELECTION</option>';
			else $noval='<option value="">'.JsnHelper::xmlentities($item->params->get('select_placeholder','')).'</option>';
		}
		else $noval='';
		
		$xml.='
			
			<field
				name="'.$item->alias.'"
				type="selectlist"
				id="'.$item->alias.'"
				description="'.JsnHelper::xmlentities(($item->description)).'"
				label="'.($hideTitle ? JsnHelper::xmlentities('<span class="no-title">'.JText::_($item->title).'</span>') : htmlspecialchars($item->title)).'"
				'.$defaultvalue.'
				'.$multiple.'
				'.$dbtable.'
				'.$dbvalue.'
				'.$dbtext.'
				'.$dbwhere.'
				'.$placeholder.'
				'.$dbfiltervalue.'
				'.$dbfiltercolumn.'
				'.$readonly.'
				class="'.$item->params->get('field_cssclass','').'"
				required="'.($item->required ? ($item->required==2 ? 'admin' : 'frontend' ) : 'false' ).'"
			>
			'.$noval.'
			'.implode(' ',$options).'
			</field>
		';
		return $xml;
	}
	
	public static function loadData($field, $user, &$data)
	{
		//if($field->params->get('field_readonly','')==1 && JFactory::getApplication()->isSite()) return;
		//if($field->params->get('field_readonly','')==2 && JFactory::getApplication()->input->get('task')=='profile.save' && JFactory::getApplication()->isSite()) return;
		$alias=$field->alias;
		if(isset($user->$alias)) $data->$alias=($field->params->get('select_multiple',0) ? json_decode($user->$alias) : $user->$alias);
	}
	
	public static function storeData($field, $data, &$storeData)
	{
		$alias=$field->alias;
		if(isset($data[$alias])) $storeData[$alias]=($field->params->get('select_multiple',0) ? json_encode($data[$alias]) : $data[$alias]);
		elseif(JFactory::getApplication()->input->get('jform_'.$alias,null,'raw')) $storeData[$alias]='';
	}
	
	public static function selectlist($field)
	{
		$value=$field->__get('value');
		if (empty($value) && (string) $value != '0')
		{
			return JHtml::_('users.value', $value);
		}
		else
		{
			$options=$field->getOptions();
			if(is_array($value))
			{
				$result=array();
				foreach($options as $option)
				{
					if(in_array($option->value,$value)) $result[]=JText::_($option->text);
				}
				return implode(', ',$result);
			}
			else
			{
				$result='';
				foreach($options as $option)
				{
					if($option->value==$value) $result=JText::_($option->text);
				}
				return $result;
			}
		}
	}
	
	public static function getSearchInput($field)
	{
		JHtml::_('formbehavior.chosen', 'select');
		$script = '';
		$selectedOptions=JFactory::getApplication()->input->get($field->alias,null,'raw');
		$options=array();
		$optTxt=explode("\n",$field->params->get('select_options',''));
		//if(!$field->params->get('select_multiple',0)) $options[]=JHtml::_('select.option', '', JText::_('COM_JSN_NOSELECTION'));
		foreach($optTxt as $opt)
		{
			$opt=trim($opt);
			if($opt!=''){
				$opt=explode('|',$opt);
				if(count($opt)==1) $options[]=JHtml::_('select.option', trim($opt[0]), JText::_(trim(htmlspecialchars($opt[0]))));
				if(count($opt)==2) $options[]=JHtml::_('select.option', trim($opt[0]), JText::_(trim(htmlspecialchars($opt[1]))));
			}
		}
		
		$dbOptTable=$field->params->get('select_dbopttable','');
		$dbOptValue=$field->params->get('select_dboptvalue','');
		$dbOptText=$field->params->get('select_dbopttext','');
		$dbOptWhere=$field->params->get('select_dboptwhere','');
		$dbOptFilterColumn=$field->params->get('select_dboptfiltercolumn','');
		$dbOptFilterValue=$field->params->get('select_dboptfiltervalue','');
		if(!empty($dbOptTable) && !empty($dbOptValue) && !empty($dbOptText)) // set the alias of your field (width this conditions the select type work normally for all field except for this field)
		{	
			$db		= JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($dbOptValue.' AS value, '.$dbOptText.' AS text')->from($dbOptTable);
			
			if(!empty($dbOptWhere)) $query->where($dbOptWhere);

			
			if(!empty($dbOptFilterColumn) && !empty($dbOptFilterValue))
			{
				$script = JsnSelectlistFieldHelper::getAjaxScript($dbOptFilterValue,$field->alias,'jform_',true);
				$optionParent=JFactory::getApplication()->input->get($dbOptFilterValue,'','raw');
				if(empty($optionParent))
				{
					$query->where('FALSE');
				}
				elseif(is_array($optionParent))
				{
					$value=$optionParent;
					foreach($value as &$val)
						$val=$db->quote($val);
					$value=implode(',',$value);
					$query->where($dbOptFilterColumn.' IN ('.$value.')');
				}
				else{
					$query->where($dbOptFilterColumn.' = '.$db->quote($optionParent));
				}
			}
			
			$query->order('text');
			
			$db->setQuery($query);

			try
			{
				$dbOptions = $db->loadObjectList();
				foreach($dbOptions as $option)
				{
					$options[]=JHtml::_('select.option', trim($option->value), JText::_(trim(htmlspecialchars($option->text))));
				}
			}
			catch (RuntimeException $e)
			{
			}
			
			
		}
		
		$from=array();
		$to=array();
		//if($field->params->get('select_multiple',0))
		//{
			$return=JHtml::_('select.genericlist', $options, $field->alias.'[]', 'multiple="multiple"', 'value', 'text', null, 'jform_'.str_replace('-','_',$field->alias));
			
			if($selectedOptions!=null)
			{
				foreach($selectedOptions as $selectedOption)
				{
					$from[]='value="'.$selectedOption.'"';
					$to[]='value="'.$selectedOption.'" selected="selected"';
				}
			}
		/*}
		else
		{
			$return=JHtml::_('select.genericlist', $options, $field->alias, null, 'value', 'text', null, 'jform_'.str_replace('-','_',$field->alias));
			$from[]='value="'.$selectedOptions.'"';
			$to[]='value="'.$selectedOptions.'" selected="selected"';
		}*/
		
		return str_replace($from,$to,$return).$script;
	}
	
	public static function getSearchQuery($field, &$query)
	{
		$option=JFactory::getApplication()->input->get($field->alias,null,'raw');
		$db=JFactory::getDbo();
		//if($field->params->get('select_multiple',0))
		//{
			$where='';
			foreach($option as $opt)
			{
				$where.='b.'.$db->quoteName($field->alias).' LIKE '.$db->quote('%"'.$opt.'"%').' OR '.'b.'.$db->quoteName($field->alias).' = '.$db->quote($opt).' OR ';
			}
			$where=substr($where, 0,-4);
			$query->where('('.$where.')');
		/*}
		else
		{
			$query->where('b.'.$db->quoteName($field->alias).' = '.$db->quote($option));
		}*/
		
	}

	public static function operations()
	{
		JFactory::getConfig()->set('gzip',false);
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$field=JFactory::getApplication()->input->get('field','','raw');
		$value=JFactory::getApplication()->input->get('value','','raw');
		$multi=(int) JFactory::getApplication()->input->get('multi',0,'raw');
		$query->select('params')->from('#__jsn_fields')->where('alias='.$db->quote($field));
		$params = new JRegistry;
		$db->setQuery($query);
		$params->loadString($db->loadResult());

		JHtml::_('formbehavior.chosen', 'select');
		$options=array();
		$optTxt=explode("\n",$params->get('select_options',''));
		if(!$params->get('select_multiple',0) && !$multi) $options[]=JHtml::_('select.option', '', JText::_('COM_JSN_NOSELECTION'));
		foreach($optTxt as $opt)
		{
			$opt=trim($opt);
			if($opt!=''){
				$opt=explode('|',$opt);
				if(count($opt)==1) $options[]=JHtml::_('select.option', trim($opt[0]), JText::_(trim(htmlspecialchars($opt[0]))));
				if(count($opt)==2) $options[]=JHtml::_('select.option', trim($opt[0]), JText::_(trim(htmlspecialchars($opt[1]))));
			}
		}
		
		$dbOptTable=$params->get('select_dbopttable','');
		$dbOptValue=$params->get('select_dboptvalue','');
		$dbOptText=$params->get('select_dbopttext','');
		$dbOptWhere=$params->get('select_dboptwhere','');
		if(!empty($value) && !empty($dbOptTable) && !empty($dbOptValue) && !empty($dbOptText)) // set the alias of your field (width this conditions the select type work normally for all field except for this field)
		{	
			$db		= JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($dbOptValue.' AS value, '.$dbOptText.' AS text')->from($dbOptTable);
			
			if(!empty($dbOptWhere)) $query->where($dbOptWhere);
			
			$select_dboptfiltercolumn=$params->get('select_dboptfiltercolumn','');
			if(!empty($select_dboptfiltercolumn) && !empty($value))
			{
				$value = explode(',', $value);

				$filter_value = array();
				foreach($value as $v)
					$filter_value[] = $db->quote($v);

				$where=$db->quoteName($params->get('select_dboptfiltercolumn','')).' IN ('.implode(',',$filter_value).')';
				
				$query->where($where);
			}

			$query->order('text');
			
			$db->setQuery($query);

			try
			{
				$dbOptions = $db->loadObjectList();
				foreach($dbOptions as $option)
				{
					$options[]=JHtml::_('select.option', trim($option->value), JText::_(trim(htmlspecialchars($option->text))));
				}
			}
			catch (RuntimeException $e)
			{
			}
			
			
		}
		
		echo json_encode($options);exit();

		
	}

	public static function getAjaxScript($parentField,$thisField,$prefix='jform_',$force_multiple = false)
	{	$rand=rand(0,10000);
		if($force_multiple) $multi = '&multi=1';
		else $multi = '&multi=0';
		$script='<script>
		jQuery(window).load(function(){
			form'.$rand.'=jQuery("#'.$prefix.$parentField.'").closest("form");
			if(!form'.$rand.'.length) form'.$rand.'=jQuery("#'.$prefix.$parentField.'").parent();
			field'.$rand.'=form'.$rand.'.find("#'.$prefix.$thisField.'").get(0);
			value'.$rand.'=jQuery(field'.$rand.').val();
			if((jQuery("#'.$prefix.$parentField.'").val()!="") || (jQuery("#'.$prefix.$parentField.'").is("fieldset") && jQuery("#'.$prefix.$parentField.' input:checked").length))
			{
				jQuery(field'.$rand.').attr("disabled","disabled");
				jQuery(field'.$rand.').trigger("liszt:updated.chosen");
				var v;
				if(jQuery("#'.$prefix.$parentField.'").is("fieldset")) v = jQuery("#'.$prefix.$parentField.' input:checked").val();
				else v = jQuery("#'.$prefix.$parentField.'").val();
				jQuery.ajax({
				    url:"'.JURI::base().'index.php?option=com_jsn&view=opField'.$multi.'&type=selectlist&field='.$thisField.'&value="+v+"&format=raw",
				    type:"GET",
				    data: "",
				    dataType: "json",
				    success: function( json ) {
				    	jQuery(field'.$rand.').find("option").remove();
				        jQuery.each(json, function(i, k) {
				        		jQuery(field'.$rand.').append(jQuery("<option>").text(k.text).attr("value", k.value));     
				        });
						jQuery(field'.$rand.').val(value'.$rand.');
						jQuery(field'.$rand.').removeAttr("disabled");
						jQuery(field'.$rand.').trigger("liszt:updated.chosen");
				    }
				});
			}


			jQuery("#'.$prefix.$parentField.'").change(function(){
				form=jQuery(this).closest("form");
				field=form.find("#'.$prefix.$thisField.'").get(0);
				value=jQuery(field).val();
				jQuery(field).attr("disabled","disabled");
				jQuery(field).trigger("liszt:updated.chosen");
				var v;
				if(jQuery("#'.$prefix.$parentField.'").is("fieldset")) v = jQuery("#'.$prefix.$parentField.' input:checked").val();
				else v = jQuery("#'.$prefix.$parentField.'").val();
				jQuery.ajax({
				    url:"'.JURI::base().'index.php?option=com_jsn&view=opField'.$multi.'&type=selectlist&field='.$thisField.'&value="+v+"&format=raw",
				    type:"GET",
				    data: "",
				    dataType: "json",
				    success: function( json ) {
				    	jQuery(field).find("option").remove();
				        jQuery.each(json, function(i, k) {
				        		jQuery(field).append(jQuery("<option>").text(k.text).attr("value", k.value));     
				        });
						jQuery(field).val("");
						jQuery(field).removeAttr("disabled");
						jQuery(field).trigger("liszt:updated.chosen");
						jQuery(field).change();
				    }
				});

			});
		});
		</script>
		';
		return $script;
	}
		

}
