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

$this->includeTemplate('script');

$isJoomla4 = EventbookingHelper::isJoomla4();
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
    <div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
        <div id="filter-bar" class="btn-toolbar js-stools<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
	        <?php $this->renderSearchBar(); ?>
        </div>
        <div class="clearfix"></div>
        <table class="adminlist table table-striped" id="exporttmplList">
            <thead>
            <tr>
                <th width="1%" class="nowrap center hidden-phone">
	                <?php echo $this->searchToolsSortHeader(); ?>
                </th>
                <th width="20">
                    <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                </th>
                <th class="title">
	                <?php echo $this->searchToolsSort('EB_NAME',  'tbl.title'); ?>
                </th>
                <th class="center">
	                <?php echo $this->searchToolsSort('EB_PUBLISHED',  'tbl.published'); ?>
                </th>
                <th class="center" width="5%">
	                <?php echo $this->searchToolsSort('EB_ID',  'tbl.id'); ?>
                </th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <td colspan="5">
					<?php echo $this->pagination->getPaginationLinks(); ?>
                </td>
            </tr>
            </tfoot>
            <tbody <?php if ($this->saveOrder) :?> class="js-draggable" data-url="<?php echo $this->saveOrderingUrl; ?>" data-direction="<?php echo strtolower($this->state->filter_order_Dir); ?>" <?php endif; ?>>
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
                    <td class="order nowrap center hidden-phone">
	                    <?php $this->reOrderCell($row); ?>
                    </td>
                    <td>
						<?php echo $checked; ?>
                    </td>
                    <td>
                        <a href="<?php echo $link; ?>">
							<?php echo $row->title; ?>
                        </a>
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