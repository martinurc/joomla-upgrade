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

class plgContentJsn_Author extends JPlugin {
	
	public function onContentBeforeDisplay( $context, &$article ) {

		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if (JSN_TYPE == 'free') return;
		
		$app=JFactory::getApplication();
		if ($app->get('MetaAuthor') == '1' && $context=='com_content.article')
		{
			$app->set('MetaAuthor',0);
			$app->getDocument()->setMetaData('author', $article->author);
		}
		
		$show_avatar=$this->params->get('show_avatar',0);
		if(isset($article->created_by) && isset($article->author) && $article->created_by && !is_object($article->author)){
			require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
			$config = JComponentHelper::getParams('com_jsn');
			$user=JsnHelper::getUser($article->created_by);
			if($config->get('profileACL',2)==0){
				if($config->get('avatar',1) && $show_avatar) $article->author=$user->getFormatName().' <img src=\''.JUri::root().$user->getValue('avatar_mini').'\' class=\'avatar author_avatar\'/>';
				else $article->author=$user->getFormatName();
			}
			else
			{
				$article->contact_link = $user->getLink();
				if($config->get('avatar',1) && $show_avatar) $article->author= $user->getFormatName().' <img src=\''.JUri::root().$user->getValue('avatar_mini').'\' class=\'avatar author_avatar\'/>';
				else $article->author=$user->getFormatName();
			}
			
		}
		
	}
	
	

}
?>