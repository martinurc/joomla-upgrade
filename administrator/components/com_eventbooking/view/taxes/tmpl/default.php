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
$config    = EventbookingHelper::getConfig();
$cols      = 7;
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
        <div id="filter-bar" class="btn-toolbar<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
	        <?php $this->renderSearchBar(); ?>
			<div class="btn-group pull-right">
				<?php
					echo $this->lists['filter_category_id'];
					echo $this->lists['filter_event_id'];
					echo $this->lists['filter_country'];

					if ($this->showVies)
					{
						echo $this->lists['filter_vies'];
					}

					echo $this->pagination->getLimitBox();
				?>
			</div>
		</div>
		<div class="clearfix"></div>
		<table class="adminlist table table-striped">
			<thead>
				<tr>
					<th width="20">
						<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
					</th>
					<th class="title" style="text-align: left;">
						<?php echo $this->gridSort('EB_CATEGORY',  'c.name'); ?>
					</th>
					<th class="title" style="text-align: left;">
						<?php echo $this->gridSort('EB_EVENT',  'b.title'); ?>
					</th>
					<th width="10%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_COUNTRY',  'tbl.country'); ?>
					</th>
					<th width="10%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_STATE',  'tbl.state'); ?>
					</th>
					<th width="10%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_TAX_RATE',  'tbl.rate'); ?>
					</th>
					<?php
						if ($this->showVies)
						{
							$cols++;
						?>
							<th width="10%" class="title" nowrap="nowrap">
								<?php echo $this->gridSort('EB_VIES',  'tbl.rate'); ?>
							</th>
						<?php
						}
					?>
					<th width="5%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_PUBLISHED',  'tbl.published'); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php echo $cols; ?>">
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
				$published = HTMLHelper::_('jgrid.published', $row->published, $i, 'tax.');
				?>
				<tr class="<?php echo "row$k"; ?>">
					<td>
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->category_name ?: Text::_('EB_ALL');?>
						</a>
					</td>
					<td>
						<?php echo $row->title ? $row->title . ' (' . HTMLHelper::_('date', $row->event_date, $config->date_format . ' H:i', null) . ')' : Text::_('EB_ALL'); ?>
					</td>
					<td>
						<?php echo $row->country ?: Text::_('EB_ALL');?>
					</td>
					<td>
						<?php echo $row->state ?: Text::_('EB_ALL');?>
					</td>
					<td>
						<?php echo $row->rate; ?>
					</td>
					<?php
						if ($this->showVies)
						{
						?>
							<td>
								<?php echo $row->vies ? Text::_('JYES') : Text::_('JNO');?>
							</td>
						<?php
						}
					?>
					<td class="center">
						<?php echo $published; ?>
					</td>
				</tr>
				<?php
				$k = 1 - $k;
			}
			?>
			</tbody>
		</table>
	    <?php $this->renderFormHiddenVariables(); ?>
	</div>
</form>