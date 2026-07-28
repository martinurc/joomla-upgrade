<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

jimport( 'joomla.plugin.plugin' );

class plgContentJsn_Content extends JPlugin {
	
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if (JSN_TYPE == 'free') return;
		
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer' && is_string($params) && $params!='customwhere')
		{
			return true;
		}
		if(isset($article->title))
		{
			$this->substitution($article->title);
		}
		if(isset($article->metakey))
		{
			$this->substitution($article->metakey);
		}
		if(isset($article->metadesc))
		{
			$this->substitution($article->metadesc);
		}
		if(isset($article->text))
		{
			$this->substitution($article->text);
		}
		
	}
	
	private function substitution(&$page)
	{
		global $JSNLIST_DISPLAYED_ID;
		if (strpos(' '.$page, '{user') > 0) {
			$regex		= '/{user\s+(.*?)}/i';
			preg_match_all($regex, $page, $matches, PREG_SET_ORDER);
			$loadLang=false;
			foreach ($matches as $match) {
				if(!$loadLang)
				{
					$lang = JFactory::getLanguage();
					$lang->load('com_jsn');
					$lang->load('com_users');
					$loadLang=true;
				}
				$data=explode(' ',trim($match[1]));
				if(isset($data[0])){
					require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
					if(isset($data[1]) && $data[1]=='raw')
					{
						$data[1]='me';
						$data[2]='raw';
					}
					if(isset($data[1]) && $data[1]=='displayedOrMe')
					{
						if(JFactory::getApplication()->input->get('option','')=='com_jsn' && JFactory::getApplication()->input->get('view','profile')=='profile') $data[1]=JFactory::getApplication()->input->get('id',null);
						elseif(JFactory::getApplication()->input->get('option','')=='com_users' && JFactory::getApplication()->input->get('view','')=='profile' && JFactory::getApplication()->input->get('layout','')=='edit') $data[1]=JFactory::getApplication()->input->get('user_id',null);
						elseif($JSNLIST_DISPLAYED_ID) $data[1]=$JSNLIST_DISPLAYED_ID;
						else unset($data[1]);
					}
					if(isset($data[1]) && $data[1]=='displayed')
					{
						if(JFactory::getApplication()->input->get('option','')=='com_jsn' && JFactory::getApplication()->input->get('view','profile')=='profile') $data[1]=JFactory::getApplication()->input->get('id',null);
						elseif(JFactory::getApplication()->input->get('option','')=='com_users' && JFactory::getApplication()->input->get('view','')=='profile' && JFactory::getApplication()->input->get('layout','')=='edit') $data[1]=JFactory::getApplication()->input->get('user_id',null);
						elseif($JSNLIST_DISPLAYED_ID) $data[1]=$JSNLIST_DISPLAYED_ID;
						else unset($data[0]);
					}
					if(isset($data[1]) && $data[1]=='me')
					{
						unset($data[1]);
					}
					if(isset($data[0])){
						$page_displayed = $JSNLIST_DISPLAYED_ID;
						if(isset($data[1])) {$JSNLIST_DISPLAYED_ID=$data[1];$user=JsnHelper::getUser($data[1]);}
						else $user=JsnHelper::getUser();
						if($user->id && isset($data[2]) && $data[2]=='raw') $page = preg_replace("|$match[0]|", (is_array($user->getValue($data[0])) ? implode(', ',$user->getValue($data[0])) : $user->getValue($data[0])), $page, 1);
						elseif($user->id) $page = preg_replace("|$match[0]|", $user->getField($data[0]), $page, 1);
						else $page = preg_replace("|$match[0]|", '', $page, 1);
						$JSNLIST_DISPLAYED_ID = $page_displayed;
					}
					else $page = preg_replace("|$match[0]|", '', $page, 1);
				}
			}
		}
	}
	

}
?>