<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnModelList extends JModelList
{
	protected function populateState($ordering = 'name', $direction = 'ASC')
	{
		$app = JFactory::getApplication();
		$params = $app->getParams();

		// List state information
		$value = $app->input->get('limit', $params->get('display_num',$app->getCfg('list_limit', 0)), 'uint');
		$this->setState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		$orderCol = $app->input->get('filter_order', 'a.name');
		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'a.name';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$params = $app->getParams();
		$this->setState('params', $params);
		/*$user = JFactory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_content')) && (!$user->authorise('core.edit', 'com_content')))
		{
			// filter on published for those who do not have edit or edit.state rights.
			$this->setState('filter.published', 1);
		}

		$this->setState('filter.language', JLanguageMultilang::isEnabled());

		// process show_noauth parameter
		if (!$params->get('show_noauth'))
		{
			$this->setState('filter.access', true);
		}
		else
		{
			$this->setState('filter.access', false);
		}*/

		$this->setState('layout', $app->input->get('layout'));
	}
	
	public $filter=true;
	protected function getListQuery()
	{
		$user=JFactory::getUser();
		$app = JFactory::getApplication();
		$params = $app->getParams();
		
		if($this->filter)
		{
			// Session for pagination
			$session = JFactory::getSession();
			$itemid=JFactory::getApplication()->input->get('Itemid','');
			if((JFactory::getApplication()->input->get('limitstart',null)!=null || JFactory::getApplication()->input->get('start',null)!=null))
			{
				$random=$session->get('jsn_search_'.$itemid.'_random',rand());
			}
			else{
				$random=rand();
				$session->set('jsn_search_'.$itemid.'_random',$random);
			}
		}
		else
		{
			$random=rand();
		}
		
		if($params->get('search_enabled',0) && !$params->get('search_showuser',0) && !JFactory::getApplication()->input->get('search',0) && $this->filter) return null;
		
		$db = JFactory::getDBO();
		
		global $JSNSOCIAL;
		if($JSNSOCIAL && !$user->guest)
		{
			$db->setQuery("SELECT friend_id FROM #__jsnsocial_friends WHERE user_id = ".$user->id);
			$friends = $db->loadColumn();
			if(empty($friends)) $friends=array(0);
		}
		
		$query = $db->getQuery(true);
		$query->select($this->getState('list.select','a.id'))->group('a.id')->from('#__users AS a')->join('left','#__jsn_users as b ON a.id=b.id')->join('left','#__user_usergroup_map as c ON a.id=c.user_id')->where('a.block=0');//->order($db->escape('a.name') . ' ASC');
		$cleanQuery=(string) $query;
		if($this->filter) {
			// Load Default Fields
			if(JFactory::getApplication()->input->get('searchid','')!='') $query->where('a.'.$db->quoteName('id').' = '. (int) JFactory::getApplication()->input->get('searchid',null));
			if(JFactory::getApplication()->input->get('name','','raw')!='') $query->where('a.'.$db->quoteName('name').' LIKE '.$db->quote('%'.JFactory::getApplication()->input->get('name',null,'raw').'%'));
			if(JFactory::getApplication()->input->get('status','')!='') {
				$config = JFactory::getConfig();
				$shared = $config->get('shared_session');
				if($shared) $query->where('a.id IN (SELECT userid FROM #__session GROUP BY userid)');
				else $query->where('a.id IN (SELECT userid FROM #__session WHERE client_id=0 GROUP BY userid)');
			}
			
			// Load Fields
			$queryField = $db->getQuery(true);
			$queryField->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.search = 1')->where('a.published = 1');
			$db->setQuery( $queryField );
			$fields = $db->loadObjectList();
		
			foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
				require_once $filename;
			}
			
			$skipPrivacy=$user->authorise('core.edit', 'com_users');
			
			foreach($fields as $field)
			{
				if(JFactory::getApplication()->input->get($field->alias,'','raw')!=''){
				
					// Load Field Registry
					$registry = new JRegistry;
					$registry->loadString($field->params);
					$field->params = $registry;
				
					$class='Jsn'.ucfirst($field->type).'FieldHelper';
				
					$testPre=(string) $query;
					if(class_exists($class)) $class::getSearchQuery($field,$query);
					$testPost=(string) $query;
				
					// Field Privacy
					if(!$skipPrivacy && $field->params->get('privacy',0) && $testPre!=$testPost){
						if($user->guest)
						{
							if($field->params->get('privacy_default',0)==0) $query->where('(b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').' OR b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').')');
							else $query->where('(b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').')');
						} 
						else
						{
							if($JSNSOCIAL)
							{
								if($field->params->get('privacy_default',0)==0) 
									$query->where('(((b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').' OR b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').') || (b.id IN ('.implode(',',$friends).') AND b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"1"%').')) OR b.id='.$user->id.')');
								if($field->params->get('privacy_default',0)==1) 
									$query->where('(((b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').') || (b.id IN ('.implode(',',$friends).') AND (b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"1"%').' || b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').'))) OR b.id='.$user->id.')');
								if($field->params->get('privacy_default',0)==99) 
									$query->where('(((b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"0"%').') || (b.id IN ('.implode(',',$friends).') AND b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'":"1"%').')) OR b.id='.$user->id.')');
							}
							else
							{
								if($field->params->get('privacy_default',0)==99) $query->where('((b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'":"99"%').' AND b.'.$db->quoteName('privacy').' LIKE '.$db->quote('%"privacy_'.$field->alias.'"%').') OR b.id='.$user->id.')');
								else $query->where('(b.'.$db->quoteName('privacy').' NOT LIKE '.$db->quote('%"privacy_'.$field->alias.'":"99"%').' OR b.id='.$user->id.')');
							}
						}
					}
				}
			}
		}
		
		// Avoid Empty Search
		if($params->get('search_noempty',0) && $cleanQuery== (string) $query && JFactory::getApplication()->input->get('search',0) && $this->filter && !$params->get('search_showuser',0)) {
			$app->enqueueMessage(JText::_('COM_JSN_NOEMPTYSEARCHWARNING'),'warning');
			$app->redirect(JRoute::_('index.php?option=com_jsn&view=list&Itemid='.JFactory::getApplication()->input->get('Itemid',0),false));
			return null;
		}
		
		// Add Custom Where
		if($params->get('where','')!='') {
			JPluginHelper::importPlugin('content');
			$where= JHtml::_('content.prepare', $params->get('where',''), 'customwhere', 'com_finder.indexer');
			$query->where($where);
		}
		
		// Add Order
		$orderCol = $params->get('orderCol','name');
		$orderDir = $params->get('orderDir','ASC');
		
		$orderCol1 = $params->get('orderCol1','');
		$orderDir1 = $params->get('orderDir1','ASC');
		
		$orderCol2 = $params->get('orderCol2','');
		$orderDir2 = $params->get('orderDir2','ASC');
		
		if($orderCol=='random')
		{
			$query->order('RAND('.$random.')');
		}
		else
		{
			$orderFieldsType = $db->getQuery(true);
			$orderFieldsType->select('a.type,a.alias')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.alias IN ('.$db->quote($orderCol) . ($orderCol1 ? ','.$db->quote($orderCol1) : '') . ($orderCol2 ? ','.$db->quote($orderCol2) : '') .')')->where('a.published = 1');
			$db->setQuery( $orderFieldsType );
			$orderFieldsType = $db->loadAssocList('alias');

			
			if($orderCol=='name') $orderCol=$db->quoteName('a.name');
			elseif($orderCol=='registereddate' || $orderCol=='registerdate') $orderCol=$db->quoteName('a.registerDate'); /* registereddate:Compatibility with <1.2.0 */
			elseif($orderCol=='lastvisitdate') $orderCol=$db->quoteName('a.lastvisitDate');
			elseif($orderCol=='email') $orderCol=$db->quoteName('a.email');
			elseif($orderCol=='username') $orderCol=$db->quoteName('a.username');
			elseif(isset($orderFieldsType[$orderCol]) && ($orderFieldsType[$orderCol]['type'] == 'image' || $orderFieldsType[$orderCol]['type'] == 'filetype')) $orderCol='CASE WHEN '.$db->quoteName('b.'.$orderCol).' LIKE "_%" THEN 1 ELSE 0 END';
			else $orderCol=$db->quoteName('b.'.$orderCol);

			if($orderCol1) {
				if($orderCol1=='name') $orderCol1=$db->quoteName('a.name');
				elseif($orderCol1=='registereddate' || $orderCol1=='registerdate') $orderCol1=$db->quoteName('a.registerDate'); /* registereddate:Compatibility with <1.2.0 */
				elseif($orderCol1=='lastvisitdate') $orderCol1=$db->quoteName('a.lastvisitDate');
				elseif($orderCol1=='email') $orderCol1=$db->quoteName('a.email');
				elseif($orderCol1=='username') $orderCol1=$db->quoteName('a.username');
				elseif(isset($orderFieldsType[$orderCol1]) && ($orderFieldsType[$orderCol1]['type'] == 'image' || $orderFieldsType[$orderCol1]['type'] == 'filetype')) $orderCol1='CASE WHEN '.$db->quoteName('b.'.$orderCol1).' LIKE "_%" THEN 1 ELSE 0 END';
				else $orderCol1=$db->quoteName('b.'.$orderCol1);
			}

			if($orderCol2) {
				if($orderCol2=='name') $orderCol2=$db->quoteName('a.name');
				elseif($orderCol2=='registereddate' || $orderCol2=='registerdate') $orderCol2=$db->quoteName('a.registerDate'); /* registereddate:Compatibility with <1.2.0 */
				elseif($orderCol2=='lastvisitdate') $orderCol2=$db->quoteName('a.lastvisitDate');
				elseif($orderCol2=='email') $orderCol2=$db->quoteName('a.email');
				elseif($orderCol2=='username') $orderCol2=$db->quoteName('a.username');
				elseif(isset($orderFieldsType[$orderCol2]) && ($orderFieldsType[$orderCol2]['type'] == 'image' || $orderFieldsType[$orderCol2]['type'] == 'filetype')) $orderCol2='CASE WHEN '.$db->quoteName('b.'.$orderCol2).' LIKE "_%" THEN 1 ELSE 0 END';
				else $orderCol2=$db->quoteName('b.'.$orderCol2);
			}

			
			$order=$orderCol.' '.$orderDir;
			if($orderCol1 && $orderCol1!=$orderCol) $order.=', '.$orderCol1.' '.$orderDir1;
			if($orderCol2 && $orderCol2!=$orderCol && $orderCol2!=$orderCol1) $order.=', '.$orderCol2.' '.$orderDir2;
			
			$query->order($order);
		}
		
		return $query;
	}
	
	public function getItems()
	{
		$items = parent::getItems();
		if($this->filter==true) {
			global $LISTIDS;
			$LISTIDS=array();
			if(is_array($items) && count($items)) foreach($items as $obj_id){
				$LISTIDS[]=$obj_id->id;
			}
		}
		return $items;
	}
	
}


?>