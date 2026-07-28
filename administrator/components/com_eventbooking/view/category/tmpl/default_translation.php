<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var \Joomla\CMS\Editor\Editor $editor
 */

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	$tabApiPrefix = 'bootstrap.';
}

$rootUri = Uri::root(true);

echo HTMLHelper::_($tabApiPrefix . 'addTab', 'category', 'translation-page', Text::_('EB_TRANSLATION'));
echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'category-translation',
	['active' => 'translation-page-' . $this->languages[0]->sef, 'recall' => true]);

foreach ($this->languages as $language)
{
	$sef = $language->sef;
	echo HTMLHelper::_($tabApiPrefix . 'addTab', 'category-translation', 'translation-page-' . $sef, $language->title . ' <img src="' . $rootUri . '/media/mod_languages/images/' . $language->image . '.gif" />');
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_NAME'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="name_<?php echo $sef; ?>" id="name_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'name_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_ALIAS'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="alias_<?php echo $sef; ?>" id="alias_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'alias_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_CATEGORY_DETAIL_URL'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="category_detail_url_<?php echo $sef; ?>" id="category_detail_url_<?php echo $sef; ?>" maxlength="250" value="<?php echo $this->item->{'category_detail_url_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_PAGE_TITLE'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="page_title_<?php echo $sef; ?>" id="page_title_<?php echo $sef; ?>" maxlength="250" value="<?php echo $this->item->{'page_title_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_PAGE_HEADING'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="page_heading_<?php echo $sef; ?>" id="page_heading_<?php echo $sef; ?>" maxlength="250" value="<?php echo $this->item->{'page_heading_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_META_KEYWORDS'); ?>
		</div>
		<div class="controls">
			<textarea rows="5" cols="30" class="input-xlarge form-control" name="meta_keywords_<?php echo $sef; ?>"><?php echo $this->item->{'meta_keywords_' . $sef}; ?></textarea>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_META_DESCRIPTION'); ?>
		</div>
		<div class="controls">
			<textarea rows="5" cols="30" class="input-xlarge form-control" name="meta_description_<?php echo $sef; ?>"><?php echo $this->item->{'meta_description_' . $sef}; ?></textarea>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_DESCRIPTION'); ?>
		</div>
		<div class="controls">
			<?php echo $editor->display('description_' . $sef, $this->item->{'description_' . $sef}, '100%', '400', '75', '10'); ?>
		</div>
	</div>

	<?php
	if (!$this->config->activate_simple_multilingual)
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('admin_email_body_' . $sef, Text::_('EB_ADMIN_EMAIL_BODY'), Text::_('EB_AVAILABLE_TAGS') . ': [REGISTRATION_DETAIL], [FIRST_NAME], [LAST_NAME], [ORGANIZATION], [ADDRESS], [ADDRESS2], [CITY], [STATE], [CITY], [ZIP], [COUNTRY], [PHONE], [FAX], [EMAIL], [COMMENT], [AMOUNT]'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('admin_email_body_' . $sef, $this->item->{'admin_email_body_' . $sef}, '100%', '350', '90', '10'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('user_email_body' . $sef, Text::_('EB_USER_EMAIL_BODY'), Text::_('EB_AVAILABLE_TAGS') . ': [REGISTRATION_DETAIL], [FIRST_NAME], [LAST_NAME], [ORGANIZATION], [ADDRESS], [ADDRESS2], [CITY], [STATE], [CITY], [ZIP], [COUNTRY], [PHONE], [FAX], [EMAIL], [COMMENT], [AMOUNT]'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('user_email_body_' . $sef, $this->item->{'user_email_body_' . $sef}, '100%', '350', '90', '10'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('user_email_body_offline_' . $sef, Text::_('EB_USER_EMAIL_BODY_OFFLINE'), Text::_('EB_AVAILABLE_TAGS') . ': [REGISTRATION_DETAIL], [FIRST_NAME], [LAST_NAME], [ORGANIZATION], [ADDRESS], [ADDRESS2], [CITY], [STATE], [CITY], [ZIP], [COUNTRY], [PHONE], [FAX], [EMAIL], [COMMENT], [AMOUNT]'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('user_email_body_offline_' . $sef, $this->item->{'user_email_body_offline_' . $sef}, '100%', '350', '90', '10'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('group_member_email_body_' . $sef, Text::_('EB_GROUP_MEMBER_EMAIL_BODY')); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('group_member_email_body_' . $sef, $this->item->{'group_member_email_body_' . $sef}, '100%', '350', '90', '10'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_THANK_YOU_MESSAGE'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('thanks_message_' . $sef, $this->item->{'thanks_message_' . $sef}, '100%', '350', '90', '6') ; ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_THANK_YOU_MESSAGE_OFFLINE'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('thanks_message_offline_' . $sef, $this->item->{'thanks_message_offline_' . $sef}, '100%', '350', '90', '6') ; ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_REGISTRATION_APPROVED_EMAIL_BODY'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('registration_approved_email_body_' . $sef, $this->item->{'registration_approved_email_body_' . $sef}, '100%', '350', '90', '6') ; ?>
			</div>
		</div>
	<?php
	}

	echo HTMLHelper::_($tabApiPrefix . 'endTab');
}
echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
echo HTMLHelper::_($tabApiPrefix . 'endTab');
