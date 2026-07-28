<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package     Joomla.Libraries
 * @subpackage  Form
 * @since       3.1
 */
class JFormFieldParent extends JFormFieldList
{
	public $type = 'Parent';

	public $isNested = null;
	
	public $table = null;

	protected $comParams = null;

	/**
	 * Constructor
	 *
	 * @since  3.1
	 */
	public function __construct()
	{
		parent::__construct();

		// Load com_jsn config
		$this->comParams = JComponentHelper::getParams('com_jsn');
	}

	
	protected function getOptions()
	{
		$this->table=$this->element['table'];
		//die($this->table);
		$options = array();

		$published = $this->element['published']? $this->element['published'] : array(0,1);
		$name = (string) $this->element['name'];

		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true)
			->select('a.'.(isset($this->element['field_id']) ? $this->element['field_id'] : 'id').' AS value, a.path, a.'.(isset($this->element['field_title']) ? $this->element['field_title'] : 'title').' AS text, a.alias, a.type, a.level, a.published')
			->from('#__'.$this->table.' AS a')
			->join('LEFT', $db->quoteName('#__'.$this->table) . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt');

		if (isset($this->element['level'])) $query->where('a.level = ' . (int) $this->element['level']);

		if (isset($this->element['where'])) $query->where($this->element['where']);

		// Filter language
		if (!empty($this->element['language']))
		{
			$query->where('a.language = ' . $db->quote($this->element['language']));
		}

		$query->where($db->quoteName('a.alias') . ' <> ' . $db->quote('root'));

		// Filter to only load active items

		// Filter on the published state
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif (is_array($published))
		{
			\Joomla\Utilities\ArrayHelper::toInteger($published);
			$query->where('a.published IN (' . implode(',', $published) . ')');
		}

		$query->group('a.id, a.title, a.level, a.lft, a.rgt, a.parent_id, a.published, a.path')
			->order('a.lft ASC');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		// Translate Options Text
		foreach($options as &$value){
			$value->text=JText::_($value->text) . " (" . $value->alias . ")";
			if(isset($this->element['mini_img']) && $this->element['mini_img'] && $value->type=='image') $value->value= $value->value.'_mini';
		}

		// Merge any additional options in the XML definition.
		if (isset($this->element['level']) || isset($this->element['enableoptions'])) $options = array_merge(parent::getOptions(), $options);

		// Prepare nested data
		if ($this->isNested())
		{
			$this->prepareOptionsNested($options);
		}
		//print_r($options);
		return $options;
	}

	/**
	 * Add "-" before nested fields, depending on level
	 *
	 * @param   array  &$options  Array of fields
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.1
	 */
	protected function prepareOptionsNested(&$options)
	{
		if ($options)
		{
			foreach ($options as &$option)
			{
				$repeat = (isset($option->level) && $option->level - 1 >= 0) ? $option->level - 1 : 0;
				$option->text = str_repeat('- ', $repeat) . $option->text;
			}
		}

		return $options;
	}

	public function isNested()
	{
		if (is_null($this->isNested))
		{
			// If mode="nested" || ( mode not set & config = nested )
			if ((isset($this->element['mode']) && $this->element['mode'] == 'nested') && !isset($this->element['level'])
				/*|| (!isset($this->element['mode']) && $this->comParams->get('jsn_field_nested', 1) == 0)*/)
			{
				$this->isNested = true;
			}
		}

		return $this->isNested;
	}

}
