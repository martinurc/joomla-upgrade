<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

Text::script('ERROR');
Text::script('RSFP_EXPORT_PLEASE_SELECT');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('script', 'com_rsform/admin/export.js', array('relative' => true, 'version' => 'auto'));

$this->document->addScriptDeclaration('jQuery(function(){ updateCSVPreview(); });');
?>
<form action="index.php?option=com_rsform" method="post" id="adminForm" name="adminForm">
	<?php
    if ($this->exportType == 'csv') {
        // prepare the content
        echo $this->loadTemplate('preview');
    }
	// add the tab title
	$this->tabs->addTitle(Text::_('RSFP_EXPORT_SELECT_FIELDS'), 'export-fields');
	// prepare the content
	$content = $this->loadTemplate('fields');
	// add the tab content
	$this->tabs->addContent($content);

	// add the tab title
	$this->tabs->addTitle(Text::_($this->exportType == 'csv' ? 'RSFP_EXPORT_CSV_OPTIONS' : 'RSFP_EXPORT_OPTIONS'), 'export-options');
	// prepare the content
	$content = $this->loadTemplate('options');
	// add the tab content
	$this->tabs->addContent($content);
	
	// render tabs
	$this->tabs->render();
	?>
	
	<input type="hidden" name="task" value="submissions.exporttask" />
	<input type="hidden" name="option" value="com_rsform" />
	<input type="hidden" name="formId" value="<?php echo $this->formId; ?>" />
	<input type="hidden" name="ExportType" value="<?php echo $this->escape($this->exportType); ?>" />
	<input type="hidden" name="ExportFile" value="<?php echo $this->escape($this->exportFile); ?>" />
</form>