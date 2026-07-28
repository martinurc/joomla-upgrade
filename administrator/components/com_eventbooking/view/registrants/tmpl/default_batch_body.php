<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$editor = Editor::getInstance(Factory::getApplication()->get('editor', 'none'));
?>
<div class="row-fluid form form-horizontal">
    <div class="control-group">
        <div class="control-label">
			<?php echo Text::_('EB_REPLY_TO_EMAIL'); ?>
        </div>
        <div class="controls">
            <input type="email" name="reply_to_email" value="" size="70" class="form-control input-xxlarge" />
        </div>
    </div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_BCC_EMAIL'); ?>
		</div>
		<div class="controls">
			<input type="text" name="bcc_email" value="" size="70" class="form-control input-xxlarge" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_EMAIL_SUBJECT'); ?>
		</div>
		<div class="controls">
			<input type="text" name="subject" value="" size="70" class="form-control input-xxlarge" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_ATTACHMENT'); ?>
		</div>
		<div class="controls">
			<input type="file" name="attachment" value="" size="70" class="form-control input-xxlarge" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_SECOND_ATTACHMENT'); ?>
		</div>
		<div class="controls">
			<input type="file" name="second_attachment" value="" size="70" class="form-control input-xxlarge" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_THIRD_ATTACHMENT'); ?>
		</div>
		<div class="controls">
			<input type="file" name="third_attachment" value="" size="70" class="form-control input-xxlarge" />
		</div>
	</div>
	<?php
		if (isset($this->lists['mm_template_id']))
		{
		?>
			<div class="control-group">
				<div class="control-label">
					<?php echo Text::_('EB_MAIL_TEMPLATE'); ?>
				</div>
				<div class="controls">
					<?php echo $this->lists['mm_template_id']; ?>
				</div>
			</div>
		<?php
		}
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_EMAIL_MESSAGE'); ?>
			<p class="eb-available-tags">
				<?php
					$tags = [
						'[FIRST_NAME]',
						'[LAST_NAME]',
						'[ORGANIZATION]',
						'[ADDRESS]',
						'[ADDRESS2]',
						'[CITY]',
						'[STATE]',
						'[EVENT_TITLE]',
						'[EVENT_DATE]',
						'[EVENT_END_DATE]',
						'[SHORT_DESCRIPTION]'
					];
				?>
				<?php echo Text::_('EB_AVAILABLE_TAGS'); ?> : <br /><strong><?php echo implode('<br />', $tags) ?></strong>
			</p>
		</div>
		<div class="controls">
			<?php echo $editor->display('message', $this->message->mass_mail_template, '100%', '400', '75', '10'); ?>
		</div>
	</div>
</div>

