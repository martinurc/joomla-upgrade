<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
?>

<form action="index.php?option=com_rsform&amp;view=directory" method="post" name="adminForm" id="adminForm">
	<?php
	echo RSFormProAdapterGrid::sidebar();

	echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

	if (empty($this->items)) { ?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php } else { ?>
		<table class="table table-striped">
			<caption id="captionTable" class="sr-only">
				<?php echo Text::_('COM_RSFORM_DIRECTORIES_TABLE_CAPTION'); ?>,
				<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
				<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
			</caption>
			<thead>
				<tr>
					<th width="1%" nowrap="nowrap"><?php echo Text::_('#'); ?></th>
					<th style="width:1%" class="text-center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</th>
					<th width="1%" nowrap="nowrap"><?php echo Text::_('JSTATUS'); ?></th>
					<th class="title"><?php echo HTMLHelper::_('searchtools.sort', Text::_('RSFP_FORM_TITLE'), 'FormTitle', $this->sortOrder, $this->sortColumn, 'directory.manage'); ?></th>
					<th class="title"><?php echo HTMLHelper::_('searchtools.sort', Text::_('RSFP_FORM_NAME'), 'FormName', $this->sortOrder, $this->sortColumn, 'directory.manage'); ?></th>
					<th width="1%" nowrap="nowrap" class="title"><?php echo HTMLHelper::_('searchtools.sort', Text::_('RSFP_FORM_ID'), 'FormId', $this->sortOrder, $this->sortColumn, 'directory.manage'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($this->items as $i => $row)
				{
					$row->FormTitle = strip_tags($row->FormTitle);
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td width="1%" nowrap="nowrap"><?php echo $this->pagination->getRowOffset($i); ?></td>
					<td width="1%" nowrap="nowrap"><?php echo HTMLHelper::_('grid.id', $i, $row->FormId); ?></td>
					<td width="1%" nowrap="nowrap">
						<?php if (!empty($row->DirectoryFormId)) { ?>
							<span class="badge bg-success badge-success"><?php echo Text::_('RSFP_SUBM_DIR_ENABLED'); ?></span>
						<?php } else { ?>
							<span class="badge badge-important bg-danger"><?php echo Text::_('RSFP_SUBM_DIR_DISABLED'); ?></span>
						<?php } ?>
					</td>
					<td><a href="index.php?option=com_rsform&amp;view=directory&amp;layout=edit&amp;formId=<?php echo $row->FormId; ?>"><?php echo !empty($row->FormTitle) ? $row->FormTitle : '<em>' . Text::_('RSFP_FORM_DEFAULT_TITLE') . '</em>'; ?></a></td>
					<td><?php echo $this->escape($row->FormName); ?></td>
					<td width="1%" nowrap="nowrap"><?php echo $row->FormId; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>

		<?php echo $this->pagination->getListFooter(); ?>
	<?php } ?>
	</div>

	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="option" value="com_rsform" />
	<input type="hidden" name="task" value="" />
</form>