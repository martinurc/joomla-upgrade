<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$listOrder	= $this->escape($this->filter_order);
$listDirn	= $this->escape($this->filter_order_Dir);

Text::script('RSFP_SUBM_DIR_DELETE_SURE');
HTMLHelper::_('stylesheet', 'com_rsform/rsicons.css', array('relative' => true, 'version' => 'auto'));

$alignmentClass = $this->escape('directoryAlign' . ucfirst($this->params->get('alignment', 'center')));
?>
<div class="table-responsive directoryTableContainer">
<table class="table table-condensed table-striped table-responsive directoryTable">
	<thead>
		<tr>
			<?php if ($this->directory->enablecsv) { ?>
				<th class="center directoryAlignCenter" width="1%"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
			<?php } ?>
			<?php foreach ($this->viewableFields as $field) { ?>
				<th class="<?php echo $alignmentClass; ?> directoryHead directoryHead<?php echo $this->getFilteredName($field->FieldName); ?>"><?php echo HTMLHelper::_('grid.sort', $field->FieldCaption, $field->FieldName, $listDirn, $listOrder); ?></th>
			<?php } ?>
			<th><?php echo Text::_('COM_RSFORM_SUBMISSIONS_DIRECTORY_ACTIONS'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ($this->items) { ?>
		<?php foreach ($this->items as $i => $item) { ?>
		<tr class="row<?php echo $i % 2; ?> directoryRow">
			<?php if ($this->directory->enablecsv) { ?>
				<td class="center directoryAlignCenter directoryGrid"><?php echo HTMLHelper::_('grid.id', $i, $item->SubmissionId); ?></td>
			<?php } ?>
			<?php foreach ($this->viewableFields as $field) { ?>
				<td class="<?php echo $alignmentClass; ?> directoryCol directoryCol<?php echo $this->getFilteredName($field->FieldName); ?>"><?php echo $this->getValue($item, $field); ?></td>
			<?php } ?>
			<td class="center directoryAlignCenter directoryActions" nowrap="nowrap">
				<?php if ($this->hasDetailFields) { ?>
				<a class="<?php echo $this->tooltipClass; ?> directoryDetail" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_SUBM_DIR_VIEW')); ?>" href="<?php echo Route::_('index.php?option=com_rsform&view=directory&layout=view&id='.$item->SubmissionId); ?>">
					<span class="rsficon rsficon-zoom-in"></span>
				</a>
				<?php } ?>
				<?php if (RSFormProHelper::canEdit($this->params->get('formId'), $item->SubmissionId)) { ?>
				<a class="<?php echo $this->tooltipClass; ?> directoryEdit" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_SUBM_DIR_EDIT')); ?>" href="<?php echo Route::_('index.php?option=com_rsform&view=directory&layout=edit&id='.$item->SubmissionId); ?>">
					<span class="rsficon rsficon-edit"></span>
				</a>
				<?php } ?>
                <?php if (RSFormProHelper::canDelete($this->params->get('formId'), $item->SubmissionId)) { ?>
                    <a onclick="return confirm(Joomla.JText._('RSFP_SUBM_DIR_DELETE_SURE'));" class="<?php echo $this->tooltipClass; ?> directoryDelete" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_SUBM_DIR_DELETE')); ?>" href="<?php echo Route::_('index.php?option=com_rsform&controller=directory&task=delete&id='.$item->SubmissionId); ?>">
	                    <span class="rsficon rsficon-remove"></span>
                    </a>
                <?php } ?>
				<?php if ($this->directory->enablepdf) { ?>
				<a class="<?php echo $this->tooltipClass; ?> directoryPdf" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_SUBM_DIR_PDF')); ?>" href="<?php echo $this->pdfLink($item->SubmissionId); ?>">
					<span class="rsficon rsficon-file-pdf"></span>
				</a>
				<?php } ?>
			</td>
		</tr>
		<?php } ?>
		<?php } ?>
	</tbody>
</table>
</div>