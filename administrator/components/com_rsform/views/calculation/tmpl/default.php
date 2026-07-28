<?php
/**
 * @package    RSForm! Pro
 * @copyright  (c) 2007-2019 www.rsjoomla.com
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');

if (Factory::getApplication()->input->getInt('update'))
{
	$this->document->addScriptDeclaration("window.opener.showCalculations();");
}

Text::script('ERROR');

$this->document->addScriptDeclaration('Joomla.submitbutton = function(task) {
	if (document.formvalidator.isValid(document.getElementById(\'adminForm\'))) {
		Joomla.submitform(task);
	}
};')
?>

<div class="alert alert-info">
	<?php echo Text::_('RSFP_CALCULATION_INFO'); ?>
</div>
<form method="post" action="index.php?option=com_rsform" name="adminForm" id="adminForm" class="form-validate">
<p>
	<button class="btn btn-success" type="button" onclick="Joomla.submitbutton('calculations.apply');"><?php echo Text::_('JAPPLY'); ?></button>
	<button class="btn btn-success" type="button" onclick="Joomla.submitbutton('calculations.save');"><?php echo Text::_('JSAVE'); ?></button>
	<button class="btn btn-secondary" type="button" onclick="window.close();"><?php echo Text::_('JCANCEL'); ?></button>
</p>

<fieldset class="form-horizontal">
	<?php
	foreach ($this->form->getFieldsets() as $fieldset)
	{
		?>
		<legend class="rsfp-legend"><?php echo Text::_($fieldset->label); ?></legend>
		<?php
		if ($fields = $this->form->getFieldset($fieldset->name))
		{
			foreach ($fields as $field)
			{
				echo $field->renderField();
			}
		}
	}
	?>
</fieldset>

	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="option" value="com_rsform" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="formId" value="<?php echo $this->formId; ?>" />
</form>

<style type="text/css">
body {
	padding: 20px !important;
}
</style>