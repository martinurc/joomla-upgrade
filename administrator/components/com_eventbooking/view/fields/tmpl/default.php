<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$this->includeTemplate('script');

$isJoomla4 = EventbookingHelper::isJoomla4();
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
		<?php
		if ($isJoomla4)
		{
			echo $this->loadTemplate('filter');
		}
		else
		{
		?>
			<div id="filter-bar" class="btn-toolbar js-stools">
				<div class="filter-search btn-group pull-left">
					<label for="filter_search" class="element-invisible"><?php echo Text::_('EB_FILTER_SEARCH_FIELDS_DESC');?></label>
					<input type="text" name="filter_search" id="filter_search" inputmode="search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->filter_search); ?>" class="hasTooltip input-medium form-control" title="<?php echo HTMLHelper::tooltipText('EB_SEARCH_FIELDS_DESC'); ?>" />
				</div>
				<div class="btn-group pull-left">
					<button type="submit" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
					<button type="button" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><span class="icon-remove"></span></button>
				</div>
				<div class="btn-group pull-right">
					<?php
					echo $this->lists['filter_category_id'];
					echo $this->lists['filter_event_id'];
					echo $this->lists['filter_show_core_fields'];
					echo $this->lists['filter_fieldtype'];
					echo $this->lists['filter_fee_field'];
					echo $this->lists['filter_quantity_field'];
					echo $this->lists['filter_state'];
					echo $this->pagination->getLimitBox();
					?>
				</div>
			</div>
		<?php
		}
		?>
		<div class="clearfix"></div>
		<table class="adminlist table table-striped" id="fieldList">
			<thead>
			<tr>
				<th width="1%" class="nowrap center hidden-phone">
					<?php echo $this->searchToolsSortHeader(); ?>
				</th>
				<th width="2%" class="center">
					<?php echo HTMLHelper::_('grid.checkall'); ?>
				</th>
				<th class="title">
					<?php echo $this->searchToolsSort('EB_NAME',  'tbl.name'); ?>
				</th>
				<th class="title">
					<?php echo $this->searchToolsSort('EB_TITLE',  'tbl.title'); ?>
				</th>
				<th class="title">
					<?php echo $this->searchToolsSort('EB_FIELD_TYPE',  'tbl.field_type'); ?>
				</th>
				<th class="title center">
					<?php echo $this->searchToolsSort('EB_REQUIRE',  'tbl.required'); ?>
				</th>
				<th class="title center">
					<?php echo $this->searchToolsSort('EB_PUBLISHED',  'tbl.published'); ?>
				</th>
				<th width="1%" class="center" nowrap="nowrap">
					<?php echo $this->searchToolsSort('EB_ID',  'tbl.id'); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="8">
					<?php echo $this->pagination->getPaginationLinks(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody <?php if ($this->saveOrder) :?> class="js-draggable" data-url="<?php echo $this->saveOrderingUrl; ?>" data-direction="<?php echo strtolower($this->state->filter_order_Dir); ?>" <?php endif; ?>>
			<?php
			$k               = 0;
			$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
			$iconPublish     = $bootstrapHelper->getClassMapping('icon-publish');
			$iconUnPublish   = $bootstrapHelper->getClassMapping('icon-unpublish');

			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row       = $this->items[$i];
				$link      = $this->getEditItemLink($row);
				$checked   = HTMLHelper::_('grid.id', $i, $row->id);
				$published = HTMLHelper::_('jgrid.published', $row->published, $i);
				?>
				<tr class="<?php echo "row$k"; ?>">
					<td class="order nowrap center hidden-phone">
						<?php $this->reOrderCell($row); ?>
					</td>
					<td class="center">
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->name; ?>
						</a>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->title; ?>
						</a>
					</td>
					<td>
						<?php
							echo $row->fieldtype;
						?>
					</td>
					<td class="center">
						<a class="tbody-icon"><span class="<?php echo $row->required ? $iconPublish : $iconUnPublish; ?>"></span></a>
					</td>
					<td class="center">
						<?php echo $published ; ?>
					</td>
					<td class="center">
						<?php echo $row->id; ?>
					</td>
				</tr>
				<?php
				$k = 1 - $k;
			}
			?>
			</tbody>
		</table>
	</div>
	<?php $this->renderFormHiddenVariables(); ?>
</form>