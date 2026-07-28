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
		<div id="filter-bar" class="btn-toolbar<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
			<?php $this->renderSearchBar(); ?>
			<div class="btn-group pull-right">
				<?php
					echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_event_id'], Text::_('EB_TYPE_OR_SELECT_ONE_EVENT'));
					echo $this->lists['filter_state'];
					echo $this->pagination->getLimitBox();
				?>
			</div>
		</div>
		<div class="clearfix"></div>
		<table class="adminlist table table-striped">
			<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="title" style="text-align: left;">
					<?php echo $this->gridSort('EB_CODE',  'tbl.code'); ?>
				</th>
				<th width="15%" class="center title">
					<?php echo Text::_('Discount'); ?>
				</th>
				<th width="10%" class="center title" nowrap="nowrap">
					<?php echo $this->gridSort('EB_TIMES',  'tbl.times'); ?>
				</th>
				<th width="10%" class="center title" nowrap="nowrap">
					<?php echo $this->gridSort('EB_USED',  'tbl.used'); ?>
				</th>
				<th width="10%" class="center title" nowrap="nowrap">
					<?php echo $this->gridSort('EB_VALID_FROM',  'tbl.valid_from'); ?>
				</th>
				<th width="10%" class="center title" nowrap="nowrap">
					<?php echo $this->gridSort('EB_VALID_TO',  'tbl.valid_to'); ?>
				</th>
				<th width="15%" class="center title" nowrap="nowrap">
					<?php echo $this->gridSort('EB_NOTE',  'tbl.note'); ?>
				</th>
				<th width="5%" class="center title" nowrap="nowrap">
					<?php echo $this->gridSort('EB_PUBLISHED',  'tbl.published'); ?>
				</th>
				<th width="2%">
					<?php echo $this->gridSort('EB_ID',  'tbl.id'); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="10">
					<?php echo $this->pagination->getPaginationLinks(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
			<?php
			$k = 0;
			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row       = $this->items[$i];
				$link      = $this->getEditItemLink($row);
				$checked   = HTMLHelper::_('grid.id', $i, $row->id);
				$published = HTMLHelper::_('jgrid.published', $row->published, $i);
				?>
				<tr class="<?php echo "row$k"; ?>">
					<td>
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->code; ?>
						</a>
					</td>
					<td class="center">
						<?php echo EventbookingHelper::formatAmount($row->discount, $this->config) . $this->discountTypes[$row->coupon_type]; ?>
					</td>
					<td class="center">
						<?php echo $row->times; ?>
					</td>
					<td class="center">
						<?php echo $row->used; ?>
					</td>
					<td class="center">
						<?php
						if ((int) $row->valid_from)
						{
							echo HTMLHelper::_('date', $row->valid_from, $this->dateFormat, null);
						}
						?>
					</td>
					<td class="center">
						<?php
						if ((int) $row->valid_to)
						{
							echo HTMLHelper::_('date', $row->valid_to, $this->dateFormat, null);
						}
						?>
					</td>
					<td>
						<?php echo $row->note; ?>
					</td>
					<td class="center">
						<?php echo $published; ?>
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