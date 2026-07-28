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
$config    = EventbookingHelper::getConfig();
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
		<div id="filter-bar" class="btn-toolbar<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
			<?php $this->renderSearchBar(); ?>
			<div class="btn-group pull-right">
				<?php
					echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_sent_to']);
					echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_email_type']);
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
						<?php echo $this->gridSort('EB_SUBJECT',  'tbl.subject'); ?>
					</th>
					<th class="title">
						<?php echo $this->gridSort('EB_EMAIL',  'tbl.email'); ?>
					</th>
					<th class="center title" width="15%">
						<?php echo $this->gridSort('EB_SENT_TO',  'tbl.sent_to'); ?>
					</th>			
					<th class="center title" width="15%">
						<?php echo $this->gridSort('EB_SENT_AT',  'tbl.sent_at'); ?>
					</th>
					<th class="center title" width="15%">
						<?php echo $this->gridSort('EB_TYPE',  'tbl.email_type'); ?>
					</th>
					<th class="center" width="5%">
						<?php echo $this->gridSort('EB_ID',  'tbl.id'); ?>
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
			<tbody>
			<?php
			$k = 0;

			$sentTos = [
				1 => Text::_('EB_ADMIN'),
				2 => Text::_('EB_REGISTRANTS'),
			];

			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row     = $this->items[$i];
				$link    = $this->getEditItemLink($row);
				$checked = HTMLHelper::_('grid.id', $i, $row->id);
				?>
				<tr class="<?php echo "row$k"; ?>">
					<td>
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->subject; ?>
						</a>
					</td>
					<td>
						<a href="mailto:<?php echo $row->email; ?>"><?php echo $row->email; ?></a>
					</td>
					<td class="center">
						<?php
							if (isset($sentTos[$row->sent_to]))
							{
								echo $sentTos[$row->sent_to];
							}
						?>
					</td>	
					<td class="center">
						<?php echo HTMLHelper::_('date', $row->sent_at, $config->date_format . ' H:i'); ?>
					</td>											
					<td class="center">
						<?php
						if (isset($this->emailTypes[$row->email_type]))
						{
							echo $this->emailTypes[$row->email_type];
						}
						?>
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
		<?php $this->renderFormHiddenVariables(); ?>
	</div>
</form>