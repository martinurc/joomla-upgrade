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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('behavior.tabstate');

	$tabApiPrefix = 'bootstrap.';
}

$document = Factory::getApplication()->getDocument();
$document->addScript(Uri::root(true) . '/media/com_eventbooking/js/admin-category-default.min.js');
$document->addStyleDeclaration('.hasTip{display:block !important}');

$editor          = Editor::getInstance(Factory::getApplication()->get('editor'));
$translatable    = Multilanguage::isEnabled() && count($this->languages);
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();

$hasCustomSettings = file_exists(__DIR__ . '/default_custom_settings.php');

Text::script('EB_ENTER_CATEGORY_TITLE', true);
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
<?php
	echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'category', ['active' => 'general-page', 'recall' => true]);
	echo HTMLHelper::_($tabApiPrefix . 'addTab', 'category', 'general-page', Text::_('EB_GENERAL'));
?>
	<div class="<?php echo $bootstrapHelper->getClassMapping('row-fluid'); ?>">
		<div class="<?php echo $bootstrapHelper->getClassMapping('span8'); ?>">
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_NAME'); ?>
				</div>
				<div class="controls">
					<input class="form-control" type="text" name="name" id="name" size="40" maxlength="250" value="<?php echo $this->item->name;?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_ALIAS'); ?>
				</div>
				<div class="controls">
					<input class="form-control" type="text" name="alias" id="alias" maxlength="250" value="<?php echo $this->item->alias;?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_PARENT'); ?>
				</div>
				<div class="controls">
					<?php echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['parent'], Text::_('EB_TYPE_OR_SELECT_ONE_CATEGORY')); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_LAYOUT'); ?>
				</div>
				<div class="controls">
					<?php echo $this->lists['layout']; ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo Text::_('EB_IMAGE'); ?></div>
				<div class="controls">
					<?php echo EventbookingHelperHtml::getMediaInput($this->item->image, 'image', null); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo Text::_('EB_IMAGE_ALT'); ?></div>
				<div class="controls">
					<input class="form-control" type="text" name="image_alt" id="alias" maxlength="250" value="<?php echo $this->item->image_alt;?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo EventbookingHelperHtml::getFieldLabel('category_detail_url', Text::_('EB_CATEGORY_DETAIL_URL'), Text::_('EB_CATEGORY_DETAIL_URL_EXPLAIN')); ?>
				</div>
				<div class="controls">
					<input class="form-control" type="url" name="category_detail_url" id="category_detail_url" maxlength="250" value="<?php echo $this->item->category_detail_url;?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo Text::_('EB_DESCRIPTION'); ?>
				</div>
				<div class="controls">
					<?php echo $editor->display('description', $this->item->description, '100%', '400', '75', '10') ; ?>
				</div>
			</div>
		</div>
		<div class="<?php echo $bootstrapHelper->getClassMapping('span4'); ?>">
			<?php
			if ($this->config->activate_simple_tax)
			{
			?>
				<div class="control-group">
					<div class="control-label">
						<?php echo EventbookingHelperHtml::getFieldLabel('tax_rate', Text::_('EB_TAX_RATE'), Text::_('EB_CATEGORY_TAX_RATE_EXPLAIN')) ?>
					</div>
					<div class="controls">
						<input type="number" min="0" step="0.01" name="tax_rate" id="tax_rate" class="form-control input-medium" size="10" value="<?php echo $this->item->tax_rate; ?>"/>
					</div>
				</div>
			<?php
			}
			?>
			<div class="control-group">
				<div class="control-label">
					<?php echo EventbookingHelperHtml::getFieldLabel('payment_methods', Text::_('EB_PAYMENT_METHODS'), Text::_('EB_CATEGORY_PAYMENT_METHODS_EXPLAIN')); ?>
				</div>
				<div class="controls">
					<?php echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['payment_methods']); ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo EventbookingHelperHtml::getFieldLabel('paypal_email', Text::_('EB_PAYPAL_EMAIL'), Text::_('EB_CATEGORY_PAYPAL_EMAIL_EXPLAIN')); ?>
				</div>
				<div class="controls">
					<input type="email" name="paypal_email" class="form-control" size="50" value="<?php echo $this->item->paypal_email ; ?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo EventbookingHelperHtml::getFieldLabel('notification_emails', Text::_('EB_NOTIFICATION_EMAILS'), Text::_('EB_CATEGORY_NOTIFICATION_EMAILS_EXPLAIN')); ?>
				</div>
				<div class="controls">
					<input type="text" name="notification_emails" class="form-control" size="70" value="<?php echo $this->item->notification_emails ; ?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo EventbookingHelperHtml::getFieldLabel('text_color', Text::_('EB_TEXT_COLOR'), Text::_('EB_TEXT_COLOR_EXPLAIN')); ?>
				</div>
				<div class="controls">
					<input type="text" name="text_color" class="form-control color {required:false}" value="<?php echo $this->item->text_color; ?>" size="10" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo EventbookingHelperHtml::getFieldLabel('color_code', Text::_('EB_COLOR'), Text::_('EB_COLOR_EXPLAIN')); ?>
				</div>
				<div class="controls">
					<input type="text" name="color_code" class="form-control color {required:false}" value="<?php echo $this->item->color_code; ?>" size="10" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_ACCESS_LEVEL'); ?>
				</div>
				<div class="controls">
					<?php echo $this->lists['access']; ?>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_SUBMIT_EVENT_ACCESS_LEVEL'); ?>
				</div>
				<div class="controls">
					<?php echo $this->lists['submit_event_access']; ?>
				</div>
			</div>
			<?php
			if (Multilanguage::isEnabled())
			{
			?>
				<div class="control-group">
					<div class="control-label">
						<?php echo Text::_('EB_LANGUAGE'); ?>
					</div>
					<div class="controls">
						<?php echo $this->lists['language'] ; ?>
					</div>
				</div>
			<?php
			}
			?>
			<div class="control-group">
				<div class="control-label">
					<?php echo Text::_('EB_PUBLISHED'); ?>
				</div>
				<div class="controls">
					<?php echo $this->lists['published']; ?>
				</div>
			</div>
		</div>
	</div>
<?php
echo HTMLHelper::_($tabApiPrefix . 'endTab');

echo HTMLHelper::_($tabApiPrefix . 'addTab', 'category', 'meta-data-page', Text::_('EB_META_DATA'));
echo $this->loadTemplate('seo_options');
echo HTMLHelper::_($tabApiPrefix . 'endTab');

echo HTMLHelper::_($tabApiPrefix . 'addTab', 'category', 'messages-page', Text::_('EB_MESSAGES'));
echo $this->loadTemplate('messages', ['editor' => $editor]);
echo HTMLHelper::_($tabApiPrefix . 'endTab');

// Add support for custom settings layout
if ($hasCustomSettings)
{
	echo HTMLHelper::_($tabApiPrefix . 'addTab', 'category', 'custom-settings-page', Text::_('EB_CATEGORY_CUSTOM_SETTINGS'));
	echo $this->loadTemplate('custom_settings', ['editor' => $editor]);
	echo HTMLHelper::_($tabApiPrefix . 'endTab');
}

if ($translatable)
{
	echo $this->loadTemplate('translation', ['editor' => $editor]);
}

echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
?>
<div class="clearfix"></div>
<?php echo HTMLHelper::_('form.token'); ?>
<input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
<input type="hidden" name="task" value="" />
</form>