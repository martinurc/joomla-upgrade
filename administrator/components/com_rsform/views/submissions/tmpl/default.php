<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('script', 'com_rsform/admin/submissions.js', array('relative' => true, 'version' => 'auto'));

if (version_compare(JVERSION, '4.0', '<'))
{
    HTMLHelper::_('formbehavior.chosen', '.advancedSelect', null, array('disable_search_threshold' => 0));
}
?>
<form action="<?php echo Route::_('index.php?option=com_rsform&view=submissions'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

		if ($field = $this->filterForm->getField('formId'))
		{
			?>
			<div class="btn-toolbar">
				<span class="sr-only"><?php echo $field->label; ?></span>
				<?php echo $field->input; ?>
			</div>
			<?php
		}

		// Export Modal
		echo HTMLHelper::_('bootstrap.renderModal', 'exportModal', array(
			'title' => Text::_('RSFP_CHOOSE_EXPORT_FORMAT')
		), $this->loadTemplate('modal_export'));

		// Import Modal
		echo HTMLHelper::_('bootstrap.renderModal', 'importModal', array(
			'title' => Text::_('COM_RSFORM_IMPORT_SUBMISSIONS'),
			'footer' => $this->loadTemplate('modal_import_footer')
		), $this->loadTemplate('modal_import'));

		// Choose columns Modal
		echo HTMLHelper::_('bootstrap.renderModal', 'columnsModal', array(
			'title' => Text::_('RSFP_CUSTOMIZE_COLUMNS'),
			'footer' => $this->loadTemplate('modal_columns_footer')
		), $this->loadTemplate('modal_columns'));
	?>
    <div class="table-responsive">
	<table class="table table-striped table-responsive">
		<caption id="captionTable" class="sr-only">
			<?php echo Text::_('COM_RSFORM_SUBMISSIONS_TABLE_CAPTION'); ?>,
			<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
			<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
		</caption>
		<thead>
		<tr>
			<th style="width:1%" class="text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
			<th style="width:1%" class="text-center"><?php echo Text::_('#'); ?></th>
			<?php
			foreach ($this->staticHeaders as $header)
			{
				?>
				<th width="1%" nowrap="nowrap" <?php if (!$header->enabled) { ?>style="display: none"<?php } ?> class="title">
					<?php echo HTMLHelper::_('searchtools.sort', $header->label, $header->value, $this->sortOrder, $this->sortColumn); ?>
				</th>
				<?php
			}

			foreach ($this->headers as $header)
			{
				?>
				<th <?php if (!$header->enabled) { ?>style="display: none"<?php } ?> class="title">
					<?php echo HTMLHelper::_('searchtools.sort', $header->label, $header->value, $this->sortOrder, $this->sortColumn); ?>
				</th>
				<?php
			}
			?>
		</tr>
		</thead>
		<?php
		$i = 0;
		foreach ($this->submissions as $submissionId => $submission)
		{
			?>
			<tr>
				<td width="1%" nowrap="nowrap" class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $submissionId); ?></td>
				<td class="text-center"><?php echo $this->pagination->getRowOffset($i); ?></td>
				<?php
				foreach ($this->staticHeaders as $header)
				{
					?>
					<td width="1%" nowrap="nowrap" <?php if (!$header->enabled) { ?>style="display: none"<?php } ?>><?php echo $this->escape($submission[$header->value]); ?></td>
					<?php
				}

				foreach ($this->headers as $header)
				{
					?>
					<td <?php if (!$header->enabled) { ?>style="display: none"<?php } ?>>
						<?php
						if (isset($submission['SubmissionValues'][$header->value]['Value']))
						{
							if (in_array($header->value, $this->unescapedFields))
							{
								echo $submission['SubmissionValues'][$header->value]['Value'];
							}
							elseif (in_array($header->value, $this->specialFields['multipleFields']))
							{
                                echo str_replace("\n",
                                    str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $this->form->MultipleSeparator),
                                    $this->escape($submission['SubmissionValues'][$header->value]['Value']));
							}
                            else
							{
								$escapedValue = $this->escape($submission['SubmissionValues'][$header->value]['Value']);

								if ($this->form->TextareaNewLines && in_array($header->value, $this->specialFields['textareaFields']))
								{
									$escapedValue = nl2br($escapedValue);
								}

								echo $escapedValue;
							}
						}
						?>
					</td>
					<?php
				}
				?>
			</tr>
		<?php
			$i++;
		}
		?>
	</table>
    </div>
		<?php echo $this->pagination->getListFooter(); ?>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_rsform" />
	<input type="hidden" name="boxchecked" value="0" />
</form>