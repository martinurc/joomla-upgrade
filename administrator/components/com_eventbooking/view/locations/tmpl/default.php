<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;

$this->includeTemplate('script');

if (!function_exists('curl_init'))
{
	Factory::getApplication()->enqueueMessage(Text::_('EB_CURL_NOT_INSTALLED'), 'warning');
}

$isJoomla4 = EventbookingHelper::isJoomla4();
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
		<div id="filter-bar" class="btn-toolbar<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
			<?php $this->renderSearchBar(); ?>
			<div class="btn-group pull-right">
				<?php
				if (Multilanguage::isEnabled())
				{
					echo $this->lists['filter_language'];
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
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="text_left">
					<?php echo $this->gridSort('EB_NAME',  'tbl.name'); ?>
				</th>
				<th class="text_left">
					<?php echo $this->gridSort('EB_ADDRESS',  'tbl.address'); ?>
				</th>
				<th class="title center">
					<?php echo $this->gridSort('EB_LATITUDE',  'tbl.lat'); ?>
				</th>
				<th class="title center">
					<?php echo $this->gridSort('EB_LONGITUDE',  'tbl.long'); ?>
				</th>
				<th class="title center">
					<?php echo $this->gridSort('EB_PUBLISHED',  'tbl.published'); ?>
				</th>
				<th width="1%" nowrap="nowrap">
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
							<?php echo $row->name; ?>
						</a>
					</td>
					<td>
						<?php echo $row->address ; ?>
					</td>
					<td class="center">
						<?php echo $row->lat ; ?>
					</td>
					<td class="center">
						<?php echo $row->long ; ?>
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