<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<form action="<?php echo Route::_('index.php?option=com_rsform&view=deletesubmission'); ?>" method="post">
	<div>
		<p><strong><?php echo Text::_('COM_RSFORM_SUBMISSION_ARE_YOU_SURE_TO_DELETE'); ?></strong></p>
	</div>
	<?php
		echo $this->form->renderFieldset('params');
		echo HTMLHelper::_('form.token');
	?>
	<div>
		<button class="btn btn-primary rsfp-submit-button" type="submit"><?php echo Text::_('JSUBMIT'); ?></button>
	</div>
	<input type="hidden" name="task" value="deletesubmission" />
</form>
