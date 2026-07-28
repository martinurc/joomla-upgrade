<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

if (version_compare(JVERSION, '4.0', '<'))
{
	HTMLHelper::_('jquery.ui', array('core', 'sortable'));
}
else
{
	HTMLHelper::_('script', 'com_rsform/admin/jquery.ui.core.min.js', array('relative' => true, 'version' => 'auto'));
	HTMLHelper::_('script', 'com_rsform/admin/jquery.ui.sortable.min.js', array('relative' => true, 'version' => 'auto'));
}

HTMLHelper::_('script', 'com_rsform/admin/jquery.ui.resizable.js', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('script', 'com_rsform/admin/jquery.ui.touch-punch.js', array('relative' => true, 'version' => 'auto'));
HTMLHelper::_('stylesheet', 'com_rsform/admin/jquery.ui.resizable.css', array('relative' => true, 'version' => 'auto'));

Text::script('COM_RSFORM_ADD_FIELD');
Text::script('RSFP_ROW_OPTIONS');
Text::script('RSFP_ADD_NEW_ROW');
Text::script('RSFP_DELETE_ROW');
Text::script('RSFP_GRID_CANNOT_REMOVE_ROW');
Text::script('RSFP_GRID_REMOVE_ROW_CONFIRM');
Text::script('RSFP_GRID_CUT');
Text::script('RSFP_GRID_NOTHING_TO_PASTE');
Text::script('RSFP_GRID_PASTE_ITEMS');
Text::script('RSFP_GRID_NOTHING_TO_PUBLISH');
Text::script('RSFP_GRID_PUBLISHED');
Text::script('RSFP_GRID_UNPUBLISHED');
Text::script('RSFP_GRID_CANT_CHANGE_REQUIRED');
Text::script('RSFP_GRID_SET_AS_REQUIRED');
Text::script('RSFP_GRID_SET_AS_NOT_REQUIRED');

list($rows, $hidden) = $this->buildGrid();

echo HTMLHelper::_('bootstrap.renderModal', 'gridModal', array(
	'title' => Text::_('RSFP_GRID_OPTIONS'),
	'footer' => $this->loadTemplate('grid_modal_footer'),
	'closeButton' => false,
	'backdrop' => 'static'
),
$this->loadTemplate('grid_modal_body'));
?>
<div id="rsfp-grid-loader">
	<div class="spinner">
		<div class="rect1"></div>
		<div class="rect2"></div>
		<div class="rect3"></div>
		<div class="rect4"></div>
		<div class="rect5"></div>
	</div>
</div>
<div class="rsfp-grid-check-all">
	<label for="toggleAllFields"><input type="checkbox" id="toggleAllFields" onclick="Joomla.checkAll(this);"/> <?php echo Text::_('RSFP_CHECK_ALL'); ?></label>
</div>
<div id="rsfp-grid-row-container">
	<?php
	$i = 0;
	foreach ($rows as $row_index => $row)
	{
		$has_pagebreak = !empty($row['has_pagebreak']);
		?>
		<div class="rsfp-grid-row<?php if ($has_pagebreak) { ?> rsfp-grid-page-container<?php } ?>">
			<?php
			foreach ($row['columns'] as $column_index => $fields)
			{
				$size = isset($row['sizes'][$column_index]) ? $row['sizes'][$column_index] : 12;
				$last_column = $column_index == count($row['columns']) - 1;
				?>
				<div class="rsfp-grid-column rsfp-grid-column<?php echo $size; ?><?php if ($last_column) { ?> rsfp-grid-column-unresizable<?php } ?><?php if ($has_pagebreak) { ?> rsfp-grid-column-unconnectable<?php } ?>">
				<h3><?php echo $size; ?>/12</h3>
                <?php if (!$has_pagebreak) { ?>
                    <div class="rsfp-grid-add-field">
                        <button type="button" class="btn btn-secondary" onclick="RSFormPro.offcanvas.open(this);"><i class="icon-plus"></i> <?php echo Text::_('COM_RSFORM_ADD_FIELD'); ?></button>
                    </div>
                <?php } ?>
				<?php foreach ($fields as $field) { ?>
					<?php
					$fieldClasses = array();

					if ($field->type_id == RSFORM_FIELD_PAGEBREAK)
					{
						$fieldClasses[] = 'rsfp-grid-field-unsortable';
					}
					if (!$field->published)
					{
						$fieldClasses[] = 'rsfp-grid-unpublished-field';
					}
					if (!empty($field->hasRequired))
					{
						$fieldClasses[] = 'rsfp-grid-can-be-required';
						if ($field->required)
						{
							$fieldClasses[] = 'rsfp-grid-required-field';
						}
						else
						{
							$fieldClasses[] = 'rsfp-grid-unrequired-field';
						}
					}
					?>
					<div id="rsfp-grid-field-id-<?php echo $field->id; ?>" class="rsfp-grid-field<?php echo $fieldClasses ? ' ' . implode(' ', $fieldClasses) : ''; ?>">
						<strong class="pull-left rsfp-grid-field-name"><?php echo HTMLHelper::_('grid.id', $i, $field->id); ?> <?php echo $this->escape($this->show_caption ? $field->caption : $field->name); ?><?php if ($field->required) { ?> (*)<?php } ?></strong>
						<div class="btn-group pull-right rsfp-grid-field-buttons">
							<button type="button" class="btn btn-secondary btn-small btn-sm" onclick="RSFormPro.editModal.display('<?php echo $field->type_id; ?>','<?php echo $field->id; ?>');"><?php echo Text::_('RSFP_EDIT'); ?></button>
							<button type="button" class="btn btn-small btn-sm btn-danger" onclick="if (confirm(Joomla.JText._('RSFP_REMOVE_COMPONENT_CONFIRM').replace('%s', '<?php echo $this->escape($field->name); ?>'))) removeComponent('<?php echo $field->id; ?>');"><?php echo Text::_('RSFP_DELETE'); ?></button>
						</div>
						<div class="clearfix"></div>
						<?php if ($this->show_previews) { ?>
						<hr />
						<?php echo $this->adjustPreview($field->preview); ?>
						<div class="clearfix"></div>
						<?php } ?>
						<input type="hidden" data-rsfpgrid value="<?php echo $field->id; ?>" />
					</div>
					<?php $i++; ?>
				<?php } ?>
				</div>
			<?php
			}
			?>
            <div class="clearfix"></div>
			<div class="rsfp-row-controls">
				<?php if (!$has_pagebreak) { ?>
				<button type="button" class="btn btn-secondary" onclick="RSFormPro.gridModal.open(this);"><?php echo Text::_('RSFP_ROW_OPTIONS'); ?></button>
				<?php } ?>
				<button type="button" class="btn btn-success" onclick="RSFormPro.gridModal.open(this, true);"><?php echo Text::_('RSFP_ADD_NEW_ROW'); ?></button>
				<?php if (!$has_pagebreak) { ?>
				<button type="button" class="btn btn-danger" onclick="RSFormPro.Grid.deleteRow(this);"><?php echo Text::_('RSFP_DELETE_ROW'); ?></button>
				<?php } ?>
			</div>
		</div>
	<?php
	}
	?>
	<?php if ($hidden) { ?>
	<div class="rsfp-grid-row rsfp-grid-row-unsortable">
		<div id="rsfp-grid-hidden-container">
		<h3><?php echo Text::_('RSFP_GRID_HIDDEN_FIELDS'); ?></h3>
		<?php foreach ($hidden as $field) { ?>
		<div id="rsfp-grid-field-id-<?php echo $field->id; ?>" class="rsfp-grid-field<?php if (!$field->published) { ?> rsfp-grid-unpublished-field<?php } ?>">
			<strong class="pull-left rsfp-grid-field-name"><?php echo HTMLHelper::_('grid.id', $i, $field->id); ?> <?php echo $this->escape($this->show_caption ? $field->caption : $field->name); ?><?php if ($field->required) { ?> (*)<?php } ?></strong>
			<div class="btn-group pull-right">
				<button type="button" class="btn btn-secondary btn-small btn-sm" onclick="RSFormPro.editModal.display('<?php echo $field->type_id; ?>','<?php echo $field->id; ?>');"><?php echo Text::_('RSFP_EDIT'); ?></button>
				<button type="button" class="btn btn-small btn-sm btn-danger" onclick="if (confirm(Joomla.JText._('RSFP_REMOVE_COMPONENT_CONFIRM').replace('%s', '<?php echo $this->escape($field->name); ?>'))) removeComponent('<?php echo $field->id; ?>');"><?php echo Text::_('RSFP_DELETE'); ?></button>
			</div>
			<?php if ($this->show_previews) { ?>
				<div class="clearfix"></div>
			<?php echo $this->adjustPreview($field->preview); ?>
			<?php } ?>
			<div class="clearfix"></div>
			<input type="hidden" data-rsfpgrid value="<?php echo $field->id; ?>" />
		</div>
			<?php $i++; ?>
		<?php } ?>
		</div>
	</div>
	<?php } ?>
</div>

<input type="hidden" name="GridLayout" value="<?php echo $this->escape($this->form->GridLayout); ?>" />