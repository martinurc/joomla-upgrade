<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$this->includeTemplate('script');

$isJoomla4 = EventbookingHelper::isJoomla4();
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
		<div id="filter-bar" class="btn-toolbar js-stools<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
			<?php $this->renderSearchBar(); ?>
			<div class="btn-group pull-right">
				<?php
					echo $this->lists['filter_state'];
					echo $this->lists['filter_access'];
					echo $this->pagination->getLimitBox();
				?>
			</div>
		</div>
		<div class="clearfix"></div>
		<table class="adminlist table table-striped" id="categoryList">
			<thead>
			<tr>
				<th width="1%" class="nowrap center hidden-phone">
					<?php echo $this->searchToolsSortHeader(); ?>
				</th>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="title" style="text-align: left;">
					<?php echo $this->searchToolsSort('EB_NAME',  'tbl.name'); ?>
				</th>
				<th class="center title" width="15%">
					<?php echo Text::_('EB_NUMBER_EVENTS'); ?>
				</th>
				<th width="8%" class="nowrap hidden-phone">
					<?php echo $this->searchToolsSort('JGRID_HEADING_ACCESS',  'tbl.access'); ?>
				</th>
				<th width="5%">
					<?php echo $this->searchToolsSort('EB_PUBLISHED',  'tbl.published'); ?>
				</th>
				<th width="2%">
					<?php echo $this->searchToolsSort('EB_ID',  'tbl.id'); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="7">
					<?php echo $this->pagination->getPaginationLinks(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody <?php if ($this->saveOrder) :?> class="js-draggable" data-url="<?php echo $this->saveOrderingUrl; ?>" data-direction="<?php echo strtolower($this->state->filter_order_Dir); ?>" data-nested="false"<?php endif; ?>>
			<?php
			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row       = $this->items[$i];
				$link      = $this->getEditItemLink($row);
				$checked   = HTMLHelper::_('grid.id', $i, $row->id);
				$published = HTMLHelper::_('jgrid.published', $row->published, $i);

				if (EventbookingHelper::isJoomla4())
				{
				?>
					<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $row->parent; ?>" item-id="<?php echo $row->id ?>" parents="<?php echo $row->parentsStr; ?>" level="<?php echo $row->level ?>">
				<?php
				}
				else
				{
				?>
					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $row->parent; ?>" item-id="<?php echo $row->id ?>" parents="<?php echo $row->parentsStr; ?>" level="<?php echo $row->level ?>">
				<?php
				}
				?>
					<td class="order nowrap center hidden-phone">
						<?php $this->reOrderCell($row); ?>
					</td>
					<td>
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->treename; ?>
						</a>
					</td>
					<td class="center">
						<?php echo $row->total_events; ?>
					</td>
					<td>
						<?php echo $row->access_level; ?>
					</td>
					<td class="center">
						<?php echo $published; ?>
					</td>
					<td class="center">
						<?php echo $row->id; ?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
	</div>
	<?php $this->renderFormHiddenVariables(); ?>
</form>