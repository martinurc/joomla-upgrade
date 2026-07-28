<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var string                $existingAttachmentsList
 *
 * @var \Joomla\CMS\Form\Form $form
 */
?>
<table class="adminlist table table-striped">
	<tr>
		<td width="20%">
			<?php echo Text::_('EB_SELECT_EXISTING_ATTACHMENTS'); ?>
		</td>
		<td>
			<?php echo $existingAttachmentsList; ?>
		</td>
	</tr>
</table>

<?php
foreach ($form->getFieldset() as $field)
{
	echo $field->input;
}
?>

<input type="hidden" name="attachments_plugin_rendered" value="1" />
