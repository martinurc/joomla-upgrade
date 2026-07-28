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
use Joomla\CMS\Router\Route;

$isJoomla4 = EventbookingHelper::isJoomla4();

if ($this->params->get('show_page_heading', 1))
{
	if ($this->input->getInt('hmvc_call'))
	{
		$hTag = 'h2';
	}
	else
	{
		$hTag = 'h1';
	}
	?>
	<<?php echo $hTag; ?> class="eb-page-heading"><?php echo $this->params->get('page_heading') ?: $this->escape(Text::_('EB_MY_COUPONS')); ?></<?php echo $hTag; ?>>
	<?php
}

if (EventbookingHelper::isValidMessage($this->params->get('intro_text')))
{
?>
	<div class="eb-description eb-user-coupons-intro-text"><?php echo $this->params->get('intro_text');?></div>
<?php
}
?>
<div id="eb-user-coupons-page" class="eb-container<?php if ($isJoomla4) echo ' eb-joomla4-container'; ?>">
	<form action="<?php echo Route::_('index.php?option=com_eventbooking&view=usercoupons&Itemid=' . $this->Itemid, false) ?>" method="post" name="adminForm" id="adminForm">
		<div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
			<div id="filter-bar" class="btn-toolbar<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
				<div class="filter-search btn-group pull-left">
					<input type="text" name="filter_search" id="filter_search" inputmode="search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->filter_search); ?>" class="hasTooltip form-control" title="<?php echo HTMLHelper::tooltipText('EB_SEARCH_USER_COUPONS_DESC'); ?>" />
				</div>
				<div class="btn-group pull-left">
					<button type="submit" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
					<button type="button" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><span class="icon-remove"></span></button>
				</div>
			</div>
			<div class="clearfix"></div>
			<?php
				if (count($this->items))
				{
				?>
					<table class="adminlist table table-striped">
						<thead>
						<tr>
							<th class="title" style="text-align: left;">
								<?php echo HTMLHelper::_('grid.sort', Text::_('EB_CODE'), 'tbl.code', $this->state->filter_order_Dir, $this->state->filter_order); ?>
							</th>
							<th class="center title">
								<?php echo Text::_('EB_DISCOUNT'); ?>
							</th>
							<th class="center title" nowrap="nowrap">
								<?php echo HTMLHelper::_('grid.sort', Text::_('EB_TIMES'), 'tbl.times', $this->state->filter_order_Dir, $this->state->filter_order); ?>
							</th>
							<th class="center title" nowrap="nowrap">
								<?php echo HTMLHelper::_('grid.sort', Text::_('EB_USED'), 'tbl.used', $this->state->filter_order_Dir, $this->state->filter_order); ?>
							</th>
							<th class="center title" nowrap="nowrap">
								<?php echo HTMLHelper::_('grid.sort', Text::_('EB_VALID_FROM'), 'tbl.valid_from', $this->state->filter_order_Dir, $this->state->filter_order); ?>
							</th>
							<th class="center title" nowrap="nowrap">
								<?php echo HTMLHelper::_('grid.sort', Text::_('EB_VALID_TO'), 'tbl.valid_to', $this->state->filter_order_Dir, $this->state->filter_order); ?>
							</th>
							<th width="5%" class="center title" nowrap="nowrap">
								<?php echo HTMLHelper::_('grid.sort', Text::_('EB_PUBLISHED'), 'tbl.published', $this->state->filter_order_Dir, $this->state->filter_order); ?>
							</th>
							<th width="2%">
								<?php echo HTMLHelper::_('searchtools.sort', Text::_('EB_ID'), 'tbl.id', $this->state->filter_order_Dir, $this->state->filter_order); ?>
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
						<tbody>
						<?php
						$k = 0;
						for ($i = 0, $n = count($this->items); $i < $n; $i++)
						{
							$row       = $this->items[$i];
							?>
							<tr class="<?php echo "row$k"; ?>">
								<td>
									<?php echo $row->code; ?>
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
										echo HTMLHelper::_('date', $row->valid_from, $this->config->date_format, null);
									}
									?>
								</td>
								<td class="center">
									<?php
									if ((int) $row->valid_to)
									{
										echo HTMLHelper::_('date', $row->valid_to, $this->config->date_format, null);
									}
									?>
								</td>
								<td class="center">
									<?php echo $row->published ? Text::_('JYES') : Text::_('JNO'); ?>
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
				<?php
				}
				else
				{
				?>
					<p class="text-info"><?php echo Text::_('EB_NO_COUPONS_AVAILABLE_FOR_YOU'); ?></p>
				<?php
				}
			?>
		</div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $this->state->filter_order; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->state->filter_order_Dir; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>