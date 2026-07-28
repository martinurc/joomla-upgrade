<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$controlGroupClass = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass = $bootstrapHelper->getClassMapping('control-label');
$controlsClass     = $bootstrapHelper->getClassMapping('controls');
$user              = Factory::getUser();

foreach ($this->form->getFieldset('basic') as $field)
{
	$submitEventAccess = $field->getAttribute('submit_event_access');

	if ($submitEventAccess !== null && !in_array($submitEventAccess, $user->getAuthorisedViewLevels()))
	{
		continue;
	}
?>
	<div class="<?php echo $controlGroupClass; ?>">
		<div class="<?php echo $controlLabelClass; ?>">
			<?php echo $field->label; ?>
		</div>
		<div class="<?php echo $controlsClass; ?>">
			<?php echo $field->input; ?>
		</div>
	</div>
<?php
}
