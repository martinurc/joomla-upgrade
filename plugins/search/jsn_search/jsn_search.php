<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

/**
 * Contacts search plugin.
 *
 * @since  1.6
 */
class PlgSearchJsn_search extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Determine areas searchable by this plugin.
	 *
	 * @return  array  An array of search areas.
	 *
	 * @since   1.6
	 */
	public function onContentSearchAreas()
	{
		$lang=JFactory::getLanguage();
		$lang->load('mod_stats');
		static $areas = array(
			'jsnusers' => 'MOD_STATS_USERS'
		);

		return $areas;
	}

	/**
	 * Search content (contacts).
	 *
	 * The SQL must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav.
	 *
	 * @param   string  $text      Target search string.
	 * @param   string  $phrase    Matching option (possible values: exact|any|all).  Default is "any".
	 * @param   string  $ordering  Ordering option (possible values: newest|oldest|popular|alpha|category).  Default is "newest".
	 * @param   string  $areas     An array if the search is to be restricted to areas or null to search all areas.
	 *
	 * @return  array  Search results.
	 *
	 * @since   1.6
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if (JSN_TYPE == 'free') return;
		
		$out = array();
		$text = trim($text);

		if($text == '')
		{
			return $out;
		}

		if (is_array($areas))
		{
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas())))
			{
				return $out;
			}
		}

		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');

		$db = JFactory::getDbo();
		$limit = $this->params->def('search_limit', 50);

		$query = $db->getQuery(TRUE);
		$query->select('u.id,u.name,u.registerDate');
		$query->from('#__users AS u');
		$query->where('u.block = 0');

		//$search = $db->quote();

		switch ($phrase)
		{
			case 'exact':
				$query->where("u.name LIKE '%{".$db->escape($text)."}%'");
				break;

			case 'all':
			case 'any':
			default:
				$words = explode(' ', $text);
				$wheres = array();

				foreach ($words as $word)
				{
					$word = $db->quote('%' . $db->escape($word, true) . '%', false);
					$wheres2 = array();
					$wheres2[] = 'u.name LIKE ' . $word;
					$wheres[] = implode(' OR ', $wheres2);
				}

				$where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
				$query->where($where);
				break;
		}

		switch($ordering)
		{
			case 'oldest':
				$order = 'u.registerDate ASC';
			break;

			case 'alpha':
				$order = 'u.name ASC';
			break;

			case 'newest':
			default:
				$order = 'u.registerDate DESC';
		}
		$query->order($order);

		$db->setQuery($query, 0, $limit);
		$result = $db->loadObjectList();

		settype($result, 'array');

		$lang=JFactory::getLanguage();
		$lang->load('mod_stats');
		$profileMenu=JFactory::getApplication()->getMenu()->getItems('link','index.php?option=com_jsn&view=profile',true);
		if(isset($profileMenu->id)) $Itemid=$profileMenu->id;
		else $Itemid=''; 

		foreach($result as $key => $user)
		{
			$out[$key] = new stdClass();
			$out[$key]->title = $user->name;
			$out[$key]->text = '';
			$out[$key]->created = $user->registerDate;
			$out[$key]->href = JRoute::_('index.php?option=com_jsn&view=profile&Itemid='.$Itemid.'&id='.$user->id,false);
  			$out[$key]->section = JText::_('MOD_STATS_USERS');
			$out[$key]->browsernav = 0;
		}

		return $out;
	}
}
