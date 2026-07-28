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

$tags = EventbookingHelperHtml::getSupportedTags('first_reminder_sms');
?>
<div class="row-fluid form form-horizontal">
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_SMS_MESSAGE'); ?>
		</div>
		<div class="controls">
			<textarea class="form-control input-xxlarge" name="sms_message" rows="10" cols="75"></textarea>
		</div>
	</div>
	<div class="control-group">
		<strong><?php echo Text::_('EB_AVAILABLE_TAGS'); ?> : <?php echo implode(', ', $tags); ?></strong>
	</div>
</div>
