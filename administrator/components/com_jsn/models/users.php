<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

require_once(JPATH_ADMINISTRATOR . '/components/com_jsn/defines.php');

if (file_exists(JPATH_COMPONENT . '/../com_users/models/users.php')) {
	require_once(JPATH_COMPONENT . '/../com_users/models/users.php');
}

/**
 * Methods supporting a list of user records.
 *
 * @since  1.6
 */
class JsnModelUsers extends UsersModelUsers
{
	
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);

		$query->from($db->quoteName('#__users') . ' AS a');

		// If the model is set to check item state, add to the query.
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('a.block = ' . (int) $state);
		}

		// If the model is set to check the activated state, add to the query.
		$active = $this->getState('filter.active');

		if (is_numeric($active))
		{
			if ($active == '0')
			{
				$query->where('a.activation IN (' . $db->quote('') . ', ' . $db->quote('0') . ')');
			}
			elseif ($active == '1')
			{
				$query->where($query->length('a.activation') . ' > 1');
			}
		}

		// Filter the items over the group id if set.
		$groupId = $this->getState('filter.group_id');
		$groups  = $this->getState('filter.groups');

		if ($groupId || isset($groups))
		{
			$query->join('LEFT', '#__user_usergroup_map AS map2 ON map2.user_id = a.id')
				->group(
					$db->quoteName(
						array(
							'a.id',
							'a.name',
							'a.username',
							'a.password',
							'a.block',
							'a.sendEmail',
							'a.registerDate',
							'a.lastvisitDate',
							'a.activation',
							'a.params',
							'a.email'
						)
					)
				);

			if ($groupId)
			{
				$query->where('map2.group_id = ' . (int) $groupId);
			}

			if (isset($groups))
			{
				$query->where('map2.group_id IN (' . implode(',', $groups) . ')');
			}
		}

		// Filter the items over the search string if set.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'username:') === 0)
			{
				$search = $db->quote('%' . $db->escape(substr($search, 9), true) . '%');
				$query->where('a.username LIKE ' . $search);
			}
			else
			{
				// Escape the search token.
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));

				// Compile the different search clauses.
				$searches   = array();
				$searches[] = 'a.name LIKE ' . $search;
				$searches[] = 'a.username LIKE ' . $search;
				$searches[] = 'a.email LIKE ' . $search;

				// Add the clauses to the query.
				$query->where('(' . implode(' OR ', $searches) . ')');
			}
		}

		// Add filter for registration ranges select list
		$range = $this->getState('filter.range');

		// Apply the range filter.
		if ($range)
		{
			$dates = $this->buildDateRange($range);

			if ($dates['dNow'] === false)
			{
				$query->where(
					$db->qn('a.registerDate') . ' < ' . $db->quote($dates['dStart']->format('Y-m-d H:i:s'))
				);
			}
			else
			{
				$query->where(
					$db->qn('a.registerDate') . ' >= ' . $db->quote($dates['dStart']->format('Y-m-d H:i:s')) .
					' AND ' . $db->qn('a.registerDate') . ' <= ' . $db->quote($dates['dNow']->format('Y-m-d H:i:s'))
				);
			}
		}

		// Add filter for registration ranges select list
		$lastvisitrange = $this->getState('filter.lastvisitrange');

		// Apply the range filter.
		if ($lastvisitrange)
		{
			$dates = $this->buildDateRange($lastvisitrange);

			if (is_string($dates['dStart']))
			{
				$query->where(
					$db->qn('a.lastvisitDate') . ' = ' . $db->quote($dates['dStart'])
				);
			}
			elseif ($dates['dNow'] === false)
			{
				$query->where(
					$db->qn('a.lastvisitDate') . ' < ' . $db->quote($dates['dStart']->format('Y-m-d H:i:s'))
				);
			}
			else
			{
				$query->where(
					$db->qn('a.lastvisitDate') . ' >= ' . $db->quote($dates['dStart']->format('Y-m-d H:i:s')) .
					' AND ' . $db->qn('a.lastvisitDate') . ' <= ' . $db->quote($dates['dNow']->format('Y-m-d H:i:s'))
				);
			}
		}

		// Filter by excluded users
		$excluded = $this->getState('filter.excluded');
		//$excluded= array('0) AND id IN (113,112');

		if (!empty($excluded))
		{
			$query->where('id NOT IN (' . implode(',', $excluded) . ')');
		}

		// Add the list ordering clause.
		$query->order($db->qn($db->escape($this->getState('list.ordering', 'a.name'))) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		if($this->filter)
		{
			$session = JFactory::getSession();
			$itemid=JFactory::getApplication()->input->get('Itemid','admin');
			if(count(JFactory::getApplication()->input->post->getArray()))
				$session->set('jsn_search_'.$itemid,JFactory::getApplication()->input->post->getArray());
			elseif($session->get('jsn_search_'.$itemid,null)){
				foreach($session->get('jsn_search_'.$itemid) as $key=>$value){
					JFactory::getApplication()->input->set($key,$value);
				}
				
			}
		}

		$query->join('left','#__jsn_users as b ON a.id=b.id');

		if($this->filter) {
			// Load Fields
			$queryField = $db->getQuery(true);
			$queryField->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.search = 1')->where('a.published = 1');
			$db->setQuery( $queryField );
			$fields = $db->loadObjectList();
		
			foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
				require_once $filename;
			}
			
			foreach($fields as $field)
			{
				if(JFactory::getApplication()->input->get($field->alias,'','raw')!=''){
				
					// Load Field Registry
					$registry = new JRegistry;
					$registry->loadString($field->params);
					$field->params = $registry;
				
					$class='Jsn'.ucfirst($field->type).'FieldHelper';
				
					if(class_exists($class)) $class::getSearchQuery($field,$query);
				}
			}
		}

		return $query;
	}
	public $filter=true;

	private function buildDateRange($range)
	{
		// Get UTC for now.
		$dNow   = new JDate;
		$dStart = clone $dNow;

		switch ($range)
		{
			case 'past_week':
				$dStart->modify('-7 day');
				break;

			case 'past_1month':
				$dStart->modify('-1 month');
				break;

			case 'past_3month':
				$dStart->modify('-3 month');
				break;

			case 'past_6month':
				$dStart->modify('-6 month');
				break;

			case 'post_year':
				$dNow = false;
			case 'past_year':
				$dStart->modify('-1 year');
				break;

			case 'today':
				// Ranges that need to align with local 'days' need special treatment.
				$app    = JFactory::getApplication();
				$offset = $app->get('offset');

				// Reset the start time to be the beginning of today, local time.
				$dStart = new JDate('now', $offset);
				$dStart->setTime(0, 0, 0);

				// Now change the timezone back to UTC.
				$tz = new DateTimeZone('GMT');
				$dStart->setTimezone($tz);
				break;
			case 'never':
				$dNow = false;
				$dStart = $this->_db->getNullDate();
				break;
		}

		return array('dNow' => $dNow, 'dStart' => $dStart);
	}
}
