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
class JFormFieldSelectlist extends JFormFieldList
{
	public $type = 'Selectlist';

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

	
	protected function getInput()
	{
		JHtml::_('formbehavior.chosen', 'select');
		/* Placeholder for multiple with Javascript */
		if(isset($this->element['hint']) && isset($this->element['multiple']) && $this->element['multiple']=='true') {
			$script="jQuery(document).ready(function(){jQuery('#".$this->id."_chzn input').attr('value','".str_replace("'","\\'",$this->element['hint'])."')});";
			$doc=JFactory::getDocument();
			$doc->addScriptDeclaration( $script );
		}
		if(is_object($this->value)) $this->value = (array) $this->value;
		if(is_array($this->value)) $value=implode(',',$this->value);
		else $value=$this->value;
		/* Multiple empty fix */
		if(isset($this->element['multiple']) && $this->element['multiple']=='true')
			$multiple_fix='<input name="'.$this->id.'" type="hidden" value="1" />';
		else
			$multiple_fix='';
		/* --- */
		$script = '';
		if(isset($this->element['dboptfiltercolumn']) && !empty($this->element['dboptfiltercolumn']) && isset($this->element['dboptfiltervalue']) && !empty($this->element['dboptfiltervalue']))
		{
			$script = JsnSelectlistFieldHelper::getAjaxScript($this->element['dboptfiltervalue'],$this->element['name']);
		}
		return str_replace('<select','<select data-val="'.$value.'"',parent::getInput()).$multiple_fix.$script;
	}
	public function getOptions()
	{
		if(isset($this->element['dbopttable']) && !empty($this->element['dbopttable']) && isset($this->element['dboptvalue']) && !empty($this->element['dboptvalue']) && isset($this->element['dbopttext']) && !empty($this->element['dbopttext'])) // set the alias of your field (width this conditions the select type work normally for all field except for this field)
		{	
			$db		= JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($this->element['dboptvalue'].' AS value, '.$this->element['dbopttext'].' AS text')->from($this->element['dbopttable']);

			if(isset($this->element['dboptfiltercolumn']) && !empty($this->element['dboptfiltercolumn']) && isset($this->element['dboptfiltervalue']) && !empty($this->element['dboptfiltervalue']))
			{
				if(empty($this->value))
				{
					return parent::getOptions();
				}
				elseif(is_array($this->value))
				{
					$value=$this->value;
					foreach($value as &$val)
						$val=$db->quote($val);
					$value=implode(',',$value);
					$query->where($this->element['dboptvalue'].' IN ('.$value.')');
				}
				else{
					$query->where($this->element['dboptvalue'].' = '.$db->quote($this->value));
				}

			}
			
			elseif(isset($this->element['dboptwhere']) && !empty($this->element['dboptwhere']))
			{
				JPluginHelper::importPlugin('content');
				$where= JHtml::_('content.prepare', $this->element['dboptwhere'], 'customwhere', 'com_finder.indexer');
				if(empty($this->value))
				{
					$query->where($where);
				}
				elseif(is_array($this->value))
				{
					$value=$this->value;
					foreach($value as &$val)
						$val=$db->quote($val);
					$value=implode(',',$value);
					$query->where($where.' OR '.$this->element['dboptvalue'].' IN ('.$value.')');
				}
				else{
					$query->where($where.' OR '.$this->element['dboptvalue'].' = '.$db->quote($this->value));
				}
			}
			
			$query->order('text');
			
			$db->setQuery($query);

			try
			{
				$options = $db->loadObjectList();
			}
			catch (RuntimeException $e)
			{
				return false;
			}
			
			foreach($options as &$option)
			{
				$option->text=JText::_($option->text);
			}

			$options = array_merge(parent::getOptions(), $options); // Merge any additional options in the fields params (you can remove this).

			return $options;
		}
		else 
		{
			return parent::getOptions();
		}
	}

}
