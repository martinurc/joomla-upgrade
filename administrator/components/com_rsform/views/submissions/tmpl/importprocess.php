<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('script', 'com_rsform/admin/import.js', array('relative' => true, 'version' => 'auto'));

Text::script('COM_RSFORM_AN_ERROR_HAS_OCCURRED_DURING_IMPORT');
Text::script('COM_RSFORM_IMPORT_HAS_FINISHED');
Text::script('MESSAGE');
Text::script('ERROR');

$this->document->addScriptDeclaration(sprintf('document.addEventListener(\'DOMContentLoaded\', function() { importProcess(0, %d, %d); });', $this->limit, $this->total));
?>
<div class="progressWrapper"><div class="progressBar" id="progressBar">0%</div></div>

<form action="<?php echo Route::_('index.php?option=com_rsform&view=submissions'); ?>" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="formId" value="<?php echo $this->formId; ?>" id="formId" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_rsform" />
</form>