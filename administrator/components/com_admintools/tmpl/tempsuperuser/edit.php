<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\AdminTools\Administrator\View\Tempsuperuser\HtmlView $this */

/**
 * HTMLHelper's `behavior.formvalidator` is deprecated in Joomla 6.
 *
 * See Joomla PR 45925.
 */
if (version_compare(JVERSION, '5.999.999', 'lt'))
{
	HTMLHelper::_('behavior.formvalidator');
}
else
{
	\Joomla\CMS\Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');
}

?>
<form action="<?php echo Route::_('index.php?option=com_admintools&view=tempsuperuser&layout=edit&user_id=' . $this->item->user_id); ?>"
      aria-label="<?= Text::_('COM_ADMINTOOLS_TITLE_TEMPSUPERUSER_EDIT', true) ?>"
      class="form-validate" id="tempsuperuser-form" method="post" name="adminForm">

	<div class="card card-body mb-2">

		<?= $this->form->getField('user_id')->renderField(); ?>
		<?= $this->form->getField('expiration')->renderField(); ?>

	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>