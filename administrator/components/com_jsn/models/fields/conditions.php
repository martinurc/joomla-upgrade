<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


JFormHelper::loadFieldClass('textarea');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package     Joomla.Libraries
 * @subpackage  Form
 * @since       3.1
 */
class JFormFieldConditions extends JFormFieldTextarea
{
	public $type = 'Conditions';

	//public $isNested = null;
	
	//public $table = null;

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
		$db = JFactory::getDbo();
		$query	= $db->getQuery(true)
			->select('a.alias AS value, a.path, a.title AS text, a.alias, a.type, a.level, a.published')
			->from('#__jsn_fields AS a')
			->join('LEFT', '#__jsn_fields AS b ON a.lft > b.lft AND a.rgt < b.rgt');
		$query->where('a.level = 2');
		$query->where('a.published IN (0,1)');
		$query->group('a.id, a.title, a.level, a.lft, a.rgt, a.parent_id, a.published, a.path')
			->order('a.lft ASC');
		$db->setQuery($query);
		$fields = $db->loadObjectList();
		$fields_opt = '';
		foreach($fields as &$value){
			$fields_opt.='<option value="'. $value->alias .'">'.JText::_($value->text) . " (" . $value->alias . ")</option>";
		}

		$html='
			<div class="dd" id="sortable'.$this->id.'" style="width:100%;">
    <a id="sortable-add-item-btn" class="dd-new-item">+</a>
    <li class="dd-item-blueprint">
      <button class="collapse" data-action="collapse" type="button" style="display: none;">–</button>
      <button class="expand" data-action="expand" type="button" style="display: none;">+</button>
      <div class="dd-handle dd3-handle">Drag</div>
      <div class="dd3-content">
        <span class="item-name">[item_name]</span>
        <div class="dd-button-container">
          <button class="custom-button-example">&#x270E;</button>
          <button class="item-remove">&times;</button>
        </div>
        <div class="dd-edit-box" style="display: none;">
          <button class="btn btn-primary btn-small pull-right end-edit"><b>'.JText::_('COM_JSN_CONDITION_SAVE').'</b></button>
          <input type="text" name="title" autocomplete="off" placeholder="Item"
                 data-placeholder="'.JText::_('COM_JSN_CONDITION_TITLE').'"
                 data-default-value="Condition Description"/>
                 <hr />
          '.JText::_('COM_JSN_CONDITION_IF').'
          <span><select name="operator">
			<option value="1">'.JText::_('COM_JSN_CONDITION_EQUAL').'</option>
			<option value="2">'.JText::_('COM_JSN_CONDITION_GREATER').'</option>
			<option value="3">'.JText::_('COM_JSN_CONDITION_LESS').'</option>
			<option value="4">'.JText::_('COM_JSN_CONDITION_CONTAINS').'</option>
			<option value="5">'.JText::_('COM_JSN_CONDITION_NOTEQUAL').'</option>
			<option value="6">'.JText::_('COM_JSN_CONDITION_NOTCONTAINS').'</option>
          </select></span> 
          <span><select name="to" class="select_to" onchange="if(jQuery(this).val()==\'_custom\') jQuery(this).parent().next().show(); else jQuery(this).parent().next().hide();">
            <option value="_custom">'.JText::_('COM_JSN_CONDITION_CUSTOMVALUE').'</option>
            '.$fields_opt.'
          </select></span> 
          <span><input type="text" class="custom_value" name="custom_value" autocomplete="off" placeholder="Item"
                 data-placeholder="Custom Value"
                 data-default-value=""/></span> 
          <hr />
          '.JText::_('COM_JSN_CONDITION_THEN').'
            <span><select class="select_action" name="action" onchange="if(jQuery(this).val()==\'fields_show\' || jQuery(this).val()==\'fields_hide\' ) jQuery(this).parent().next().show().next().hide(); else jQuery(this).parent().next().hide().next().show();">
              <option value="fields_show">'.JText::_('COM_JSN_CONDITION_SHOWFIELDS').'</option>
              <option value="fields_hide">'.JText::_('COM_JSN_CONDITION_HIDEFIELDS').'</option>
              <option value="usergroups_add" '.(JSN_TYPE=='free' ? 'disabled="disabled"' : '').'>'.JText::_('COM_JSN_CONDITION_ADDUSERGROUP').'</option>
              <option value="usergroups_remove" '.(JSN_TYPE=='free' ? 'disabled="disabled"' : '').'>'.JText::_('COM_JSN_CONDITION_REMOVEUSERGROUP').'</option>
            </select></span>
            <span><select name="fields_target" multiple="multiple">
              '.$fields_opt.'
            </select></span>
            <span>'.JHtml::_('access.usergroup', 'usergroups_target', null, 'multiple', null, null).'</span>
          <hr />
          '.JText::_('COM_JSN_CONDITION_INVERSE').' <select name="two_ways"/><option selected="selected" value="1">'.JText::_('JYES').'</option><option value="0">'.JText::_('JNO').'</option></select>          
        </div>
      </div>
    </li>

    <ol class="dd-list"></ol>
  </div>
  <script>
  jQuery(document).ready(function($) {
  	jQuery(\'#sortable'.$this->id.' select\').chosen("destroy");
  	
    var $sortable            = $(\'#sortable'.$this->id.'\'),
        sortable             = $(\'#sortable'.$this->id.'\').sortable(),
        $jsonOutput        = $(\'#'.$this->id.'\');

    $sortable.sortable({
        slideAnimationDuration: 0,
        maxDepth: 0,
        data: $(\'#'.$this->id.'\').val() || \'[]\'
      })
      .onCreateItem(function(blueprint) {
        var customButton = $(blueprint).find(\'.custom-button-example\');
        customButton.click(function() {
          blueprint.find(\'.dd3-content span\').first().click();
        });
      })
      .parseJson()
      .on([\'onItemCollapsed\', \'onItemExpanded\', \'onItemAdded\', \'onSaveEditBoxInput\', \'onItemDrop\', \'onItemDrag\', \'onItemRemoved\', \'onItemEndEdit\'], function(a, b, c) {
        $jsonOutput.val(sortable.toJson());
      });

    sortable.on(\'*\', function(a, b, c) {
    	jQuery(\'#sortable'.$this->id.' button\').click(function(){return false;});
      })
      .onItemStartEdit(function() {
      	jQuery(\'#sortable'.$this->id.' .dd-list select\').chosen();
        jQuery(\'.select_to,.select_action\').change();
      });
      jQuery(\'#sortable'.$this->id.' button\').click(function(){return false;});
     	$jsonOutput.hide();
  });
</script>
		';
		return $html . parent::getInput();
	}
}
