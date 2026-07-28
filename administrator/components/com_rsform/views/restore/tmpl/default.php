<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

Text::script('RSFP_ERROR');
Text::script('RSFP_STATUS');
Text::script('RSFP_DECOMPRESSING_ARCHIVE');
Text::script('RSFP_READING_METADATA_INFORMATION');
Text::script('RSFP_REMOVING_OLD_FORMS');
Text::script('RSFP_RESTORING_FORM_STRUCTURE');
Text::script('RSFP_RESTORE_COMPLETE');
Text::script('RSFP_RESTORING_FORM_SUBMISSIONS');

Text::script('RSFP_DELETING_TEMPORARY_FOLDER');
Text::script('RSFP_TMP_FOLDER_REMOVED');
Text::script('RSFP_JSON_DECODING_ERROR');

$script = 'RSFormPro.Restore.requestTimeOut.Seconds = ' . (float) $this->config->get('request_timeout') . ';
RSFormPro.Restore.overwrite = ' . (int) $this->overwrite . ';
RSFormPro.Restore.keepId = ' . (int) $this->keepId . ';
RSFormPro.Restore.key = ' . json_encode($this->key) . ';

document.addEventListener(\'DOMContentLoaded\', function(){
	RSFormPro.Restore.start();
});';

$this->document->addScriptDeclaration($script);

HTMLHelper::_('script', 'com_rsform/admin/restore.js', array('relative' => true, 'version' => 'auto'));
?>
<form action="index.php?option=com_rsform" method="post" name="adminForm" id="adminForm">
	<?php echo RSFormProAdapterGrid::sidebar(); ?>
		<div class="progressWrapper"><div class="progressBar" id="progressBar">0%</div></div>
		<div id="backup-info-container" style="display: none;">
			<h3><?php echo Text::_('RSFP_BACKUP_INFORMATION'); ?></h3>
			<ul>
				<li>RSForm! Pro <strong id="backup-rsform-pro-version"></strong></li>
				<li>Joomla! <strong id="backup-joomla-version"></strong></li>
				<li>PHP <strong id="backup-php-version"></strong></li>
				<li><?php echo Text::_('RSFP_BACKUP_OS'); ?> <strong id="backup-os"></strong></li>
				<li><?php echo Text::_('RSFP_BACKUP_WEBSITE'); ?> <strong id="backup-url"></strong></li>
				<li><?php echo Text::_('RSFP_BACKUP_AUTHOR'); ?> <strong id="backup-author"></strong></li>
				<li><?php echo Text::_('RSFP_BACKUP_DATE'); ?> <strong id="backup-date"></strong></li>
			</ul>
		</div>

		<div id="backup-contents-container" style="display: none;">
			<table class="restoreForms table table-striped">
				<thead>
					<tr>
						<th style="width:2%;">#</th>
						<th><?php echo Text::_('RSFP_RESTORE_FORM'); ?></th>
						<th class="center text-center" nowrap="nowrap" width="1%"><?php echo Text::_('RSFP_RESTORE_STRUCTURE'); ?></th>
						<th class="center text-center" nowrap="nowrap" width="1%"><?php echo Text::_('RSFP_RESTORE_SUBMISSIONS'); ?></th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>

		<a class="btn btn-primary" style="display: none;" id="viewForms" href="<?php echo Route::_('index.php?option=com_rsform&view=forms'); ?>"><?php echo Text::_('RSFP_MANAGE_FORMS'); ?></a>
	</div>
	<div>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="option" value="com_rsform"/>
		<input type="hidden" name="boxchecked" value="0"/>
	</div>
</form>