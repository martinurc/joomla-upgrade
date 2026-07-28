<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$app		= JFactory::getApplication();
$user		= JFactory::getUser();
$userId		= $user->get('id');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$ordering 	= ($listOrder == 'a.lft');
$canOrder	= $user->authorise('core.edit.state',	'com_jsn');
$saveOrder 	= ($listOrder == 'a.lft' && $listDirn == 'asc');
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_jsn&task=fields.saveOrderAjax';
	JHtml::_('sortablelist.sortable', 'categoryList', 'adminForm', strtolower($listDirn), $saveOrderingUrl, false, true);
}
$sortFields = $this->getSortFields();
?>
<style>
.dndlist-sortable{display:table-row !important;}
.icon.icon-eye-close:before{color:inherit;}
</style>
<script type="text/javascript">
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>')
		{
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_jsn&view=fields');?>" method="post" name="adminForm" id="adminForm">
<?php if (!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
		<?php
		if($user->authorise('core.admin',     'com_jsn')) :
			$xml = simplexml_load_file(JPATH_ADMINISTRATOR .'/components/com_jsn/jsn.xml');
			$version = (string)$xml->version;
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('version')->from('#__updates')->where('element = "com_jsn"');
			try {
				$db->setQuery($query);
				if($latest = $db->loadResult()) $update = '<br /><b style="color:#942a25">Installed version is outdated!</b>';
				else $update = '';
			}
			 catch (Exception $e) {
				$update = '';
			}
			if(JSN_TYPE == 'free') $free_message = '<br /><br /><b>This is a free version, go to <a target="_blank" href="http://www.easy-profile.com/" >Easy Profile Website</a> to see our plan.</b>';
			else $free_message = '';
		?>
			<div style="text-align:center"><b>Easy Profile <?php echo ucfirst(JSN_TYPE) ?> <?php echo $version.$update.$free_message; ?></b></div>
		<?php endif; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible"><?php echo JText::_('COM_JSN_ITEMS_SEARCH_FILTER');?></label>
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_JSN_ITEMS_SEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_JSN_ITEMS_SEARCH_FILTER'); ?>" />
			</div>
			<div class="btn-group hidden-phone">
				<button class="btn tip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn tip" type="button" onclick="jQuery('#filter_search').val('');this.form.submit();" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC');?></label>
				<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC');?></option>
					<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING');?></option>
					<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');?></option>
				</select>
			</div>
			<div class="btn-group pull-right">
				<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
				<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
					<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
				</select>
			</div>
			<div class="clearfix"></div>
		</div>

		<table class="table notable-striped" id="categoryList">
			<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
					</th>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
					<th width="1%" class="nowrap center">
						<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap hidden-phone">
						<?php echo JText::_('COM_JSN_HEADING_PROPERTIES'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_CORE'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_REQUIRED'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_PROFILE'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_EDIT'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_REGISTER'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_BACKEND'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_SEARCHABLE'); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JSN_HEADING_TYPE'); ?>
					</th>
				<th width="10%" class="nowrap hidden-phone">
					<?php echo JHtml::_('grid.sort',  'COM_JSN_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap hidden-phone">
					<?php echo JText::_('COM_JSN_HEADING_ACCESSVIEW'); ?>
				</th>
				<!-- <th width="5%" class="nowrap hidden-phone">
					<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'language', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?>
				</th> -->
				
					<th width="1%" class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="15">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			$originalOrders = array();
			foreach ($this->items as $i => $item) :
				$orderkey   = array_search($item->id, $this->ordering[$item->parent_id]);
				$canCreate  = $user->authorise('core.create',     'com_jsn');
				$canEdit    = $user->authorise('core.edit',       'com_jsn');
				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $user->get('id')|| $item->checked_out == 0;
				$canChange  = $user->authorise('core.edit.state', 'com_jsn') && $canCheckin;
				// Get the parents of item for sorting
				if ($item->level > 1)
				{
					$parentsStr = "";
					$_currentParentId = $item->parent_id;
					$parentsStr = " ".$_currentParentId;
					for ($j = 0; $j < $item->level; $j++)
					{
						foreach ($this->ordering as $k => $v)
						{
							$v = implode("-", $v);
							$v = "-" . $v . "-";
							if (strpos($v, "-" . $_currentParentId . "-") !== false)
							{
								$parentsStr .= " " . $k;
								$_currentParentId = $k;
								break;
							}
						}
					}
				}
				else
				{
					$parentsStr = "";
				}
				?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->parent_id;?>" item-id="<?php echo $item->id?>" parents="<?php echo $parentsStr?>" level="<?php echo $item->level?>" <?php if($item->level==1) echo('style="background:#f9f9f9"'); ?> >
						<td class="order nowrap center hidden-phone">
						<?php if ($canChange) :
							$disableClassName = '';
							$disabledLabel    = '';
							if (!$saveOrder) :
								$disabledLabel    = JText::_('JORDERINGDISABLED');
								$disableClassName = 'inactive tip-top';
							endif; ?>
							<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
								<i class="icon-menu"></i>
							</span>

						<?php else : ?>
							<span class="sortable-handler inactive">
								<i class="icon-menu"></i>
							</span>
						<?php endif; ?>
							<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $orderkey + 1;?>" />
						</td>
						<td class="center hidden-phone">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<?php echo JHtml::_('jgrid.published', $item->published, $i, 'fields.', ($item->core ? false : $canChange) );?>
						</td>
						<td>
							<?php if ($item->level > 0): ?>
							<?php echo str_repeat('<span class="gi">&mdash;</span>', $item->level - 1) ?>
							<?php endif; ?>
							<?php if ($item->checked_out) : ?>
								<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'fields.', $canCheckin); ?>
							<?php endif; ?>
							<?php if (($canEdit || $canEditOwn) && $item->level==1) : ?>
								<a href="<?php echo JRoute::_('index.php?option=com_jsn&task=field.editgroup&id='.$item->id);?>">
									<?php echo JText::_($this->escape($item->title)); ?></a>
							<?php elseif ($canEdit || $canEditOwn) : ?>
								<?php if($item->type!='delimeter' && !$item->core && !in_array($item->alias,$this->columns)) echo('<i class="hasTooltip icon-warning" data-original-title="'.JText::_('COM_JSN_FIELD_WITHOUTCOLUMN').'"> </i> '); ?>
								<a href="<?php echo JRoute::_('index.php?option=com_jsn&task=field.edit&id='.$item->id);?>">
									<?php echo JText::_($this->escape($item->title)); ?></a>
							<?php else : ?>
								<?php echo JText::_($this->escape($item->title)); ?>
							<?php endif; ?>
							<span class="small" title="<?php echo $this->escape($item->path); ?>">
								<?php if (empty($item->note)) : ?>
									<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
								<?php else : ?>
									<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->note));?>
								<?php endif; ?>
							</span>
						</td>
						<td class=" hidden-phone">
						<?php 
							if($item->level==2)
							{
								if(!empty($item->conditions)) $conditions=json_decode($item->conditions);
								else $conditions = array();
								$hasConditions = false;
								$conditionsDisplay = '<ul>';
								foreach($conditions as $condition)
								{
									$hasConditions = true;
									if(empty($condition->title)) $condition->title = 'Condition Description';
									$conditionsDisplay .= '<li>'.$condition->title.'</li>';
								}
								$conditionsDisplay .= '</ul>';
								if($hasConditions) echo '<i  data-content="'.$this->escape($conditionsDisplay).'" data-placement="left" class="hasPopover icon icon-shuffle"></i>';
								$privacy=$item->params->get('privacy',0);
								$privacy_defaults=array(0 => 'COM_JSN_PUBLIC', 1 => 'COM_JSN_REGISTERED', 99 => 'COM_JSN_PRIVATE');
								$privacy_default=JText::_($privacy_defaults[$item->params->get('privacy_default',0)]);
								if($privacy) echo '<i data-content="'.$this->escape(JText::sprintf('COM_JSN_FIELDS_PROPERTIES_PRIVACY',$privacy_default)).'" data-placement="left" class="hasPopover icon icon-eye-close"></i>';
								$default_value=$item->params->get(str_replace(array('list_','usermail_'),array('_','mail_'),$item->type.'_defaultvalue'),'');
								if($default_value!='') echo '<i  data-content="'.$this->escape(JText::_('COM_JSN_FIELDS_PROPERTIES_DEFAULTVALUE')).'" data-placement="left" class="hasPopover icon icon-asterisk"></i>';
								$custom_title_profile=$item->params->get('titleprofile','');
								$custom_title_search=$item->params->get('titlesearch','');
								$hide_profile_title=$item->params->get('hidetitle',0);
								$hide_editprofile_title=$item->params->get('hidetitleedit',0);

								if($custom_title_profile!='' || $custom_title_search!='' || $hide_profile_title || $hide_editprofile_title)
								{
									$extra_message=array();
									if($custom_title_profile!='' && !$hide_profile_title) $extra_message[]=JText::_('COM_JSN_TITLEPROFILE').': '.$custom_title_profile;
									if($custom_title_search!='') $extra_message[]=JText::_('COM_JSN_TITLESEARCH').': '.$custom_title_profile;
									if($hide_profile_title) $extra_message[]=JText::_('COM_JSN_HIDETITLEPROFILE').': '.JText::_('JYES');
									if($hide_editprofile_title) $extra_message[]=JText::_('COM_JSN_HIDETITLEFORM').': '.JText::_('JYES');
									echo '<i data-content="'.$this->escape('<ul><li>'.implode('</li><li>',$extra_message).'</li></ul>').'" data-placement="left" class="hasPopover icon icon-brush"></i>';
								} 
							}
						?>
					</td>
						<td>
							<?php echo JHtml::_('field.core', $item->core, $i, false);?>
						</td>
						<td>
							<?php if($item->level==2) echo JHtml::_('field.required', $item->required, $i, ($item->core && $item->alias!='avatar' && $item->alias!='secondname' ? false : $canChange) );?>
						</td>
						<td>
							<?php if($item->level==2) echo JHtml::_('field.profile', $item->profile, $i,$canChange );?>
						</td>
						<td>
							<?php if($item->level==2) echo JHtml::_('field.edit', $item->edit, $i, ($item->core && $item->alias!='avatar' ? false : $canChange) );?>
						</td>
						<td>
							<?php if($item->level==2) echo JHtml::_('field.register', $item->register, $i, ($item->core && $item->alias!='avatar' ? false : $canChange) );?>
						</td>
						<td>
							<?php if($item->level==2) echo JHtml::_('field.editbackend', $item->editbackend, $i, ($item->core && $item->alias!='avatar' ? false : $canChange) );?>
						</td>
						<td>
							<?php if($item->level==2 && $item->type!='delimeter' && $item->type!='password') echo JHtml::_('field.search', $item->search, $i, $canChange );?>
						</td>
						<td>
							<?php if($item->level==2) echo $item->type; ?>
						</td>
					<td class="small hidden-phone">
						<?php echo $this->escape($item->access_title); ?>
					</td>
					<td class="small hidden-phone">
						<?php echo $this->escape($item->accessview_title); ?>
					</td>
					
					<!-- <td class="small nowrap hidden-phone">
					<?php if ($item->language == '*') : ?>
						<?php echo JText::alt('JALL', 'language'); ?>
						<?php else:?>
							<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
						<?php endif;?>
						</td> -->
						<td class="center hidden-phone">
							<span title="<?php echo sprintf('%d-%d', $item->lft, $item->rgt); ?>">
								<?php echo (int) $item->id; ?></span>
						</td> 
						
					</tr>

			<?php endforeach; ?>
			</tbody>
		</table>
		<?php //Load the batch processing form. ?>
		<?php echo $this->loadTemplate('batch'); ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<input type="hidden" name="original_order_values" value="<?php if(is_array($originalOrders) && !empty($originalOrders)) echo implode($originalOrders, ','); ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
