<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Version;
use Joomla\CMS\Uri\Uri;

class com_rsformInstallerScript
{
	protected $source;

	protected static $legacy = array('inline', '2lines', '2colsinline', '2cols2lines', 'inline-xhtml', '2lines-xhtml');

	protected $warnPlugins = false;

	protected $migrateResponsiveLayoutFramework = false;

	private static $minJoomla = '3.10.0';
    private static $minPHP = '5.5';
	
	public function update($parent) {
		$db = Factory::getDbo();
		$this->source = $parent->getParent()->getPath('source');

		/**
		 * Create column here, so we can run the SQL immediately after
		 */
		$columns = $db->getTableColumns('#__rsform_component_type_fields', false);
		if (!isset($columns['Properties']))
		{
			$db->setQuery("ALTER TABLE `#__rsform_component_type_fields` ADD `Properties` TEXT NOT NULL AFTER `FieldValues`");
			$db->execute();
		}
		if ($columns['FieldType']->Type != "varchar(32)") {
			$db->setQuery("ALTER TABLE `#__rsform_component_type_fields` CHANGE `FieldType` `FieldType` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'hidden'");
			$db->execute();
		}

		$columns = $db->getTableColumns('#__rsform_component_types');
		if (!isset($columns['CanBeDuplicated']))
		{
			$db->setQuery("ALTER TABLE `#__rsform_component_types` ADD `CanBeDuplicated` tinyint(1) NOT NULL DEFAULT '1' AFTER `ComponentTypeName`");
			$db->execute();

			$query = $db->getQuery(true);
			$query->update('#__rsform_component_types')
				->set($db->qn('CanBeDuplicated') . ' = ' . $db->q(0))
				->where($db->qn('ComponentTypeId') . ' = '. $db->q(8));

			$db->setQuery($query)->execute();
		}

		// Add config data
		$this->runSQL('config.data.sql');
		
		// Run all SQL queries to create missing data
		$this->runSQL('component_type_fields.data.sql');
		$this->runSQL('component_types.data.sql');
		$this->runSQL('conditions.sql');
		$this->runSQL('condition_details.sql');
		$this->runSQL('emails.sql');
		$this->runSQL('posts.sql');
		$this->runSQL('submission_columns.sql');
		$this->runSQL('translations.sql');
		$this->runSQL('calculations.sql');
		$this->runSQL('directory.sql');
		$this->runSQL('directory_fields.sql');
		
		// #__rsform_forms updates
		$columns = $db->getTableColumns('#__rsform_forms', false);
		// Change varchar to text
		$textColumns = array('UserEmailAttachFile', 'UserEmailCC', 'UserEmailBCC', 'UserEmailReplyTo', 'UserEmailReplyToName', 'AdminEmailCC', 'AdminEmailBCC', 'AdminEmailReplyTo', 'AdminEmailReplyToName', 'DeletionEmailCC', 'DeletionEmailBCC', 'DeletionEmailFrom', 'DeletionEmailReplyTo', 'DeletionEmailReplyToName', 'DeletionEmailFromName', 'DeletionEmailSubject');
		foreach ($textColumns as $textColumn)
		{
			if (isset($columns[$textColumn]))
			{
				if (strpos($columns[$textColumn]->Type, 'text') === false)
				{
					$db->setQuery("ALTER TABLE #__rsform_forms CHANGE " . $db->qn($textColumn) . " " . $db->qn($textColumn) . " text NOT NULL");
					$db->execute();
				}
			}
		}

		$columns = $db->getTableColumns('#__rsform_forms');
		if (!isset($columns['UserEmailAttach'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailAttach` TINYINT NOT NULL AFTER `UserEmailMode`");
			$db->execute();
		}
		if (!isset($columns['UserEmailAttachFile'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailAttachFile` TEXT NOT NULL AFTER `UserEmailAttach`");
			$db->execute();
		}
		if (!isset($columns['ScriptProcess2'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `ScriptProcess2` mediumtext NOT NULL AFTER `ScriptProcess`");
			$db->execute();
		}
		if (!isset($columns['UserEmailCC'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailCC` TEXT NOT NULL AFTER `UserEmailTo`");
			$db->execute();
		}
		if (!isset($columns['UserEmailBCC'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailBCC` TEXT NOT NULL AFTER `UserEmailCC`");
			$db->execute();
		}
		if (!isset($columns['UserEmailReplyTo'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailReplyTo` TEXT NOT NULL AFTER `UserEmailBCC`");
			$db->execute();
		}
		if (!isset($columns['UserEmailReplyToName'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailReplyToName` TEXT NOT NULL AFTER `UserEmailReplyTo`");
			$db->execute();
		}
		if (!isset($columns['AdminEmailCC'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `AdminEmailCC` TEXT NOT NULL AFTER `AdminEmailTo`");
			$db->execute();
		}
		if (!isset($columns['AdminEmailBCC'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `AdminEmailBCC` TEXT NOT NULL AFTER `AdminEmailCC`");
			$db->execute();
		}
		if (!isset($columns['AdminEmailReplyTo'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `AdminEmailReplyTo` TEXT NOT NULL AFTER `AdminEmailBCC`");
			$db->execute();
		}
		if (!isset($columns['AdminEmailReplyToName'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `AdminEmailReplyToName` TEXT NOT NULL AFTER `AdminEmailReplyTo`");
			$db->execute();
		}
		if (!isset($columns['LoadFormLayoutFramework'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `LoadFormLayoutFramework` TINYINT( 1 ) NOT NULL default '1' AFTER `FormLayoutName`");
			$db->execute();
		}
		if (!isset($columns['FormLayoutFlow'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `FormLayoutFlow` TINYINT( 1 ) NOT NULL default '0' AFTER `FormLayoutAutogenerate`");
			$db->execute();
		}
		if (!isset($columns['MetaTitle'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `MetaTitle` TINYINT( 1 ) NOT NULL");
			$db->execute();
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `MetaDesc` TEXT NOT NULL");
			$db->execute();
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `MetaKeywords` TEXT NOT NULL");
			$db->execute();
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `Required` VARCHAR( 255 ) NOT NULL DEFAULT '(*)'");
			$db->execute();
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ErrorMessage` TEXT NOT NULL");
			$db->execute();
			
			$db->setQuery("SELECT FormId FROM #__rsform_forms WHERE FormId='1' AND FormName='RSformPro example' AND ErrorMessage=''");
			if ($db->loadResult())
			{
				$db->setQuery("UPDATE #__rsform_forms SET MetaTitle=0, MetaDesc='This is the meta description of your form. You can use it for SEO purposes.', MetaKeywords='rsform, contact, form, joomla', Required='(*)', ErrorMessage='<p class=\"formRed\">Please complete all required fields!</p>' WHERE FormId='1' LIMIT 1");
				$db->execute();
			}
		}
		if (!isset($columns['CSS'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `CSS` mediumtext NOT NULL AFTER `FormLayoutAutogenerate` ,".
						  " ADD `JS` mediumtext NOT NULL AFTER `CSS` ,".
						  " ADD `ShowThankyou` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `ReturnUrl` ,".
						  " ADD `UserEmailScript` mediumtext NOT NULL AFTER `ScriptDisplay` ,".
						  " ADD `AdminEmailScript` mediumtext NOT NULL AFTER `UserEmailScript` ,".
						  " ADD `MultipleSeparator` VARCHAR( 64 ) NOT NULL AFTER `ErrorMessage` ,".
						  " ADD `TextareaNewLines` TINYINT( 1 ) NOT NULL AFTER `MultipleSeparator`");
			$db->execute();
		}
		if (!isset($columns['CSSClass'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `CSSClass` VARCHAR( 255 ) NOT NULL AFTER `TextareaNewLines` ,".
						  " ADD `CSSId` VARCHAR( 255 ) NOT NULL DEFAULT 'userForm' AFTER `CSSClass` ,".
						  " ADD `CSSName` VARCHAR( 255 ) NOT NULL AFTER `CSSId` ,".
						  " ADD `CSSAction` TEXT NOT NULL AFTER `CSSName` ,".
						  " ADD `CSSAdditionalAttributes` TEXT NOT NULL AFTER `CSSAction`,".
						  " ADD `AjaxValidation` TINYINT( 1 ) NOT NULL AFTER `CSSAdditionalAttributes`");
			$db->execute();
		}
		if (isset($columns['UserEmailConfirmation'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` DROP `UserEmailConfirmation`");
			$db->execute();
		}
		if (isset($columns['ThemeParams']))
		{
			$db->setQuery("ALTER TABLE `#__rsform_forms` DROP `ThemeParams`");
			$db->execute();
		}
		if (!isset($columns['ShowContinue'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ShowContinue` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `Thankyou`");
			$db->execute();
		}
		if (!isset($columns['ShowSystemMessage'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ShowSystemMessage` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `ReturnUrl`");
			$db->execute();
		}
		if (!isset($columns['Keepdata'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `Keepdata` TINYINT( 1 ) NOT NULL DEFAULT '1'");
			$db->execute();
			$db->setQuery("UPDATE `#__rsform_forms` SET `Keepdata` = 1");
			$db->execute();
		} else {
			$db->setQuery("ALTER TABLE `#__rsform_forms` CHANGE `Keepdata` `Keepdata` TINYINT( 1 ) NOT NULL DEFAULT '1'");
			$db->execute();
		}
		if (!isset($columns['KeepIP'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `KeepIP` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `Keepdata`");
			$db->execute();
		}
        if (!isset($columns['DeleteSubmissionsAfter'])) {
            $db->setQuery("ALTER TABLE `#__rsform_forms` ADD `DeleteSubmissionsAfter` INT( 11 ) NOT NULL DEFAULT '0' AFTER `KeepIP`");
            $db->execute();
        }
		if (!isset($columns['Backendmenu'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `Backendmenu` TINYINT( 1 ) NOT NULL");
			$db->execute();
		}
		if (!isset($columns['ConfirmSubmission'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ConfirmSubmission` TINYINT( 1 ) NOT NULL DEFAULT '0'");
			$db->execute();
		}
		if (!isset($columns['ConfirmSubmissionDefer'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ConfirmSubmissionDefer` TEXT NOT NULL AFTER `ConfirmSubmission`");
			$db->execute();
		}
		if (!isset($columns['ConfirmSubmissionUrl'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ConfirmSubmissionUrl` TEXT NOT NULL AFTER `ConfirmSubmission`");
			$db->execute();
		}
		if (!isset($columns['AdditionalEmailsScript'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `AdditionalEmailsScript` mediumtext NOT NULL AFTER `AdminEmailScript`");
			$db->execute();
		}
		if (!isset($columns['ShowFormTitle'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `ShowFormTitle` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `FormTitle`");
			$db->execute();
		}
		if (!isset($columns['Access'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `Access` VARCHAR( 5 ) NOT NULL");
			$db->execute();
		}
		if (!isset($columns['LimitSubmissions'])) {
			$db->setQuery("ALTER TABLE `#__rsform_forms` ADD `LimitSubmissions` INT( 11 ) NOT NULL default '0'");
			$db->execute();
		}
		if (!isset($columns['ScrollToThankYou'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `ScrollToThankYou` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `ShowThankyou`");
			$db->execute();
		}
		if (!isset($columns['ThankYouMessagePopUp'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `ThankYouMessagePopUp` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `ScrollToThankYou`");
			$db->execute();
		}
		if (!isset($columns['ScrollToError'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `ScrollToError` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `AjaxValidation`");
			$db->execute();
		}
		if (!isset($columns['DisableSubmitButton'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `DisableSubmitButton` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `FormLayoutAutogenerate`");
			$db->execute();
		}
		if (!isset($columns['RemoveCaptchaLogged'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `RemoveCaptchaLogged` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `DisableSubmitButton`");
			$db->execute();
		}
		if (!isset($columns['GridLayout'])) {
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `GridLayout` MEDIUMTEXT NOT NULL AFTER `FormLayout`");
			$db->execute();
		}
        if (!isset($columns['DeletionEmailText']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailText` mediumtext NOT NULL AFTER `AdminEmailMode`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailTo']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailTo` text NOT NULL AFTER `DeletionEmailText`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailCC']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD  `DeletionEmailCC` TEXT NOT NULL AFTER `DeletionEmailTo`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailBCC']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailBCC` TEXT NOT NULL AFTER `DeletionEmailCC`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailFrom']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailFrom` TEXT NOT NULL AFTER `DeletionEmailBCC`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailReplyTo']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailReplyTo` TEXT NOT NULL AFTER `DeletionEmailFrom`");
            $db->execute();
        }
		if (!isset($columns['DeletionEmailReplyToName']))
		{
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailReplyToName` TEXT NOT NULL AFTER `DeletionEmailReplyTo`");
			$db->execute();
		}
        if (!isset($columns['DeletionEmailFromName']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailFromName` TEXT NOT NULL AFTER `DeletionEmailReplyTo`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailSubject']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailSubject` TEXT NOT NULL AFTER `DeletionEmailFromName`");
            $db->execute();
        }
        if (!isset($columns['DeletionEmailMode']))
        {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `DeletionEmailMode` tinyint(1) NOT NULL default '1' AFTER `DeletionEmailSubject`");
            $db->execute();
        }
        if (!isset($columns['ScriptBeforeDisplay'])) {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `ScriptBeforeDisplay` mediumtext NOT NULL AFTER `ScriptProcess2`");
            $db->execute();
        }
        if (!isset($columns['ScriptBeforeValidation'])) {
            $db->setQuery("ALTER TABLE #__rsform_forms ADD `ScriptBeforeValidation` mediumtext NOT NULL AFTER `ScriptBeforeDisplay`");
            $db->execute();
        }
		if ($columns['FormLayout'] == 'text') {
			$db->setQuery("ALTER TABLE `#__rsform_forms` CHANGE `FormLayout` `FormLayout` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
			$db->execute();
		}
		if (!isset($columns['UserEmailGenerate']))
		{
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `UserEmailGenerate` TINYINT(1) NOT NULL DEFAULT '0' AFTER `UserEmailAttachFile`");
			$db->execute();
		}
		if (!isset($columns['AdminEmailGenerate']))
		{
			$db->setQuery("ALTER TABLE #__rsform_forms ADD `AdminEmailGenerate` TINYINT(1) NOT NULL DEFAULT '0' AFTER `AdminEmailMode`");
			$db->execute();
		}
		
		// #__rsform_emails updates
		$columns = $db->getTableColumns('#__rsform_emails');
		if (!isset($columns['type'])) {
			$db->setQuery("ALTER TABLE `#__rsform_emails` ADD `type` VARCHAR( 255 ) NOT NULL AFTER `formId`");
			$db->execute();
			$db->setQuery("UPDATE `#__rsform_emails` SET `type` = 'additional'");
			$db->execute();
		}
		if (!isset($columns['replytoname'])) {
			$db->setQuery("ALTER TABLE `#__rsform_emails` ADD `replytoname` VARCHAR( 255 ) NOT NULL AFTER `replyto`");
			$db->execute();
		}
		if ($columns['message'] == 'text')
		{
			$db->setQuery("ALTER TABLE #__rsform_emails CHANGE " . $db->qn('message') . " " . $db->qn('message') . ' mediumtext');
			$db->execute();
		}

		// Let's make some columns mediumtext
		$columns = $db->getTableColumns('#__rsform_forms');
		$changed = array('CSS', 'JS', 'ScriptProcess', 'ScriptProcess2', 'ScriptBeforeDisplay', 'ScriptBeforeValidation', 'ScriptDisplay', 'UserEmailScript', 'AdminEmailScript', 'AdditionalEmailsScript', 'Thankyou', 'UserEmailText', 'AdminEmailText', 'DeletionEmailText');
		foreach ($changed as $column)
		{
			if (isset($columns[$column]) && $columns[$column] == 'text')
			{
				$db->setQuery("ALTER TABLE #__rsform_forms CHANGE " . $db->qn($column) . " " . $db->qn($column) . ' mediumtext');
				$db->execute();
			}
		}
		$columns = $db->getTableColumns('#__rsform_submission_values');
		$changed = array('FieldValue');
		foreach ($changed as $column)
		{
			if (isset($columns[$column]) && $columns[$column] == 'text')
			{
				$db->setQuery("ALTER TABLE #__rsform_submission_values CHANGE " . $db->qn($column) . " " . $db->qn($column) . ' mediumtext');
				$db->execute();
			}
		}
		$columns = $db->getTableColumns('#__rsform_properties');
		$changed = array('PropertyValue');
		foreach ($changed as $column)
		{
			if (isset($columns[$column]) && $columns[$column] == 'text')
			{
				$db->setQuery("ALTER TABLE #__rsform_properties CHANGE " . $db->qn($column) . " " . $db->qn($column) . ' mediumtext');
				$db->execute();
			}
		}
		
		// #__rsform_config updates
		$columns = $db->getTableColumns('#__rsform_config', false);
		if (isset($columns['ConfigId'])) {
			$db->setQuery("ALTER TABLE `#__rsform_config` DROP `ConfigId`");
			$db->execute();
		}
		if (!$columns['SettingName']->Key) {
			// remove duplicates
			$query = $db->getQuery(true);
			$query->select($db->qn('SettingName'))->from('#__rsform_config');
			$db->setQuery($query);
			$results = $db->loadColumn();
			
			$counts = array_count_values($results);
			foreach ($counts as $key => $num) {
				if ($num > 1) {
					$db->setQuery("DELETE FROM #__rsform_config WHERE ".$db->qn('SettingName').'='.$db->q($key)." LIMIT ".($num-1));
					$db->execute();
				}
			}
			
			$db->setQuery("ALTER TABLE `#__rsform_config` ADD PRIMARY KEY (`SettingName`)");
			$db->execute();
		}
		
		// #__rsform_submission_values updates
		$columns = $db->getTableColumns('#__rsform_submission_values', false);
		if ($columns['FormId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_submission_values ADD INDEX (`FormId`)"); 
			$db->execute();
		}
		if ($columns['SubmissionId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_submission_values ADD INDEX (`SubmissionId`)");
			$db->execute();
		}
		if (!isset($columns['FormId'])) {
			$db->setQuery("ALTER TABLE #__rsform_submission_values ADD `FormId` INT NOT NULL AFTER `SubmissionValueId`");
			$db->execute();
			$db->setQuery("UPDATE #__rsform_submission_values sv, #__rsform_submissions s SET sv.FormId=s.FormId WHERE sv.SubmissionId = s.SubmissionId");
			$db->execute();
		}
		if ($db->hasUTF8mb4Support())
		{
			if (strpos(strtolower($columns['FieldValue']->Collation), 'utf8mb4') === false)
			{
				$db->setQuery('ALTER TABLE '.$db->qn('#__rsform_submission_values').' CHANGE '.$db->qn('FieldValue').' '.$db->qn('FieldValue').' '.$columns['FieldValue']->Type.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
				$db->execute();
			}
		}
		
		// #__rsform_submissions updates
		$columns = $db->getTableColumns('#__rsform_submissions', false);
		if ($columns['FormId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_submissions ADD INDEX (`FormId`)");
			$db->execute();
		}
		if (!isset($columns['Lang'])) {
			$db->setQuery("ALTER TABLE `#__rsform_submissions` ADD `Lang` VARCHAR( 255 ) NOT NULL AFTER `UserId`");
			$db->execute();
		}
		if (!isset($columns['confirmed'])) {
			$db->setQuery("ALTER TABLE `#__rsform_submissions` ADD `confirmed` TINYINT( 1 ) NOT NULL");
			$db->execute();
		}
		if (!isset($columns['ConfirmedIp']))
		{
			$db->setQuery("ALTER TABLE `#__rsform_submissions` ADD `ConfirmedIp` varchar(255) NOT NULL default ''");
			$db->execute();
			$db->setQuery("UPDATE #__rsform_submissions SET ConfirmedIp = UserIp WHERE confirmed = 1;");
			$db->execute();
		}
		if (!isset($columns['ConfirmedDate']))
		{
			$db->setQuery("ALTER TABLE `#__rsform_submissions` ADD `ConfirmedDate` datetime");
			$db->execute();
			$db->setQuery("UPDATE #__rsform_submissions SET ConfirmedDate = DateSubmitted WHERE confirmed = 1;");
			$db->execute();
		}
        if (!isset($columns['SubmissionHash'])) {
            $db->setQuery("ALTER TABLE `#__rsform_submissions` ADD `SubmissionHash` VARCHAR( 32 ) NOT NULL," .
                "ADD KEY `SubmissionId` (`SubmissionId`,`FormId`,`DateSubmitted`)," .
                "ADD KEY `SubmissionHash` (`SubmissionHash`)");
            $db->execute();
        }
		$columns = $db->getTableColumns('#__rsform_submissions', false);
		if ($columns['UserIp']->Type == 'varchar(15)') {
			$db->setQuery("ALTER TABLE `#__rsform_submissions` CHANGE `UserIp` `UserIp` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");
			$db->execute();
		}
		if ($columns['UserId']->Type == 'text') {
			$db->setQuery("UPDATE `#__rsform_submissions` SET `UserId` = '0' WHERE `UserId` = ''");
			$db->execute();
			$db->setQuery("ALTER TABLE `#__rsform_submissions` CHANGE `UserId` `UserId` INT( 11 ) NOT NULL DEFAULT '0'");
			$db->execute();
		}
		// #__rsform_component_type_fields updates
		$query = $db->getQuery(true);
		$query->update('#__rsform_component_type_fields')
			  ->set($db->qn('FieldType').'='.$db->q('textarea'))
			  ->where($db->qn('FieldName').'='.$db->q('DEFAULTVALUE'))
			  ->where($db->qn('ComponentTypeId').'='.$db->q(1));
		$db->setQuery($query);
		$db->execute();

		$columns = $db->getTableColumns('#__rsform_component_type_fields', false);
		if (isset($columns['ComponentTypeFieldId'])) {
			$db->setQuery("ALTER TABLE `#__rsform_component_type_fields` DROP `ComponentTypeFieldId`");
			$db->execute();
		}
		if ($columns['ComponentTypeId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_component_type_fields ADD INDEX (`ComponentTypeId`)");
			$db->execute();
		}

		// #__rsform_translations updates
		$columns = $db->getTableColumns('#__rsform_translations', false);
		if ($columns['lang_code']->Key != 'MUL')
		{
			try
			{
				$db->setQuery("ALTER TABLE #__rsform_translations ADD KEY `lang_code` (`lang_code`)")->execute();
				$db->setQuery("ALTER TABLE #__rsform_translations ADD KEY `reference` (`reference`)")->execute();
				$db->setQuery("ALTER TABLE #__rsform_translations ADD KEY `lang_search` (`form_id`,`lang_code`,`reference`)")->execute();
			}
			catch (Exception $e)
			{
				// Do nothing
			}
		}
		$columns = $db->getTableColumns('#__rsform_translations');
		$changed = array('value');
		foreach ($changed as $column)
		{
			if (isset($columns[$column]) && $columns[$column] == 'text')
			{
				$db->setQuery("ALTER TABLE #__rsform_translations CHANGE " . $db->qn($column) . " " . $db->qn($column) . ' mediumtext');
				$db->execute();
			}
		}

		$columns = $db->getTableColumns('#__rsform_conditions', false);
		if ($columns['component_id']->Type != 'text')
		{
			try
			{
				$db->setQuery("ALTER TABLE `#__rsform_conditions` DROP INDEX `component_id`")->execute();
			}
			catch (Exception $e)
			{

			}

			try
			{
				$db->setQuery("ALTER TABLE `#__rsform_conditions` CHANGE `component_id` `component_id` TEXT NOT NULL")->execute();
			}
			catch (Exception $e)
			{

			}
		}
        if (!isset($columns['ordering']))
        {
            $db->setQuery("ALTER TABLE `#__rsform_conditions` ADD `ordering` int(11) NOT NULL DEFAULT '0' AFTER `lang_code`")->execute();
            $db->setQuery('ALTER TABLE `#__rsform_conditions` ADD INDEX (`ordering`), ADD INDEX (`form_id`, `ordering`)')->execute();
        }

		// add the VALIDATIONMULTIPLE to the textBox field
		$db->setQuery("SELECT COUNT(`FieldName`) FROM #__rsform_component_type_fields  WHERE `ComponentTypeId` = 1 AND `FieldName` = 'VALIDATIONMULTIPLE'");
		if (!$db->loadResult()) {
			$db->setQuery("INSERT INTO #__rsform_component_type_fields SET `ComponentTypeId` = 1, `FieldName` = 'VALIDATIONMULTIPLE' , `FieldType` = 'selectmultiple', `FieldValues` = '".$db->escape("//<code>\r\nreturn RSFormProHelper::getValidationRules(false, true);\r\n//</code>")."', `Ordering`= 6");
			$db->execute();
		}
		// add the VALIDATIONMULTIPLE to the textArea field
		$db->setQuery("SELECT COUNT(`FieldName`) FROM #__rsform_component_type_fields  WHERE `ComponentTypeId` = 2 AND `FieldName` = 'VALIDATIONMULTIPLE'");
		if (!$db->loadResult()) {
			$db->setQuery("INSERT INTO #__rsform_component_type_fields SET `ComponentTypeId` = 2, `FieldName` = 'VALIDATIONMULTIPLE' , `FieldType` = 'selectmultiple', `FieldValues` = '".$db->escape("//<code>\r\nreturn RSFormProHelper::getValidationRules(false, true);\r\n//</code>")."', `Ordering`= 6");
			$db->execute();
		}
		
		// add the VALIDATIONMULTIPLE to the password field
		$db->setQuery("SELECT COUNT(`FieldName`) FROM #__rsform_component_type_fields  WHERE `ComponentTypeId` = 14 AND `FieldName` = 'VALIDATIONMULTIPLE'");
		if (!$db->loadResult()) {
			$db->setQuery("INSERT INTO #__rsform_component_type_fields SET `ComponentTypeId` = 14, `FieldName` = 'VALIDATIONMULTIPLE' , `FieldType` = 'selectmultiple', `FieldValues` = '".$db->escape("//<code>\r\nreturn RSFormProHelper::getValidationRules(false, true);\r\n//</code>")."', `Ordering`= 9");
			$db->execute();
		}
		
		// rename old RSadapter function to new one
		$db->setQuery("UPDATE #__rsform_component_type_fields SET FieldValues='".$db->escape("//<code>\r\nreturn JPATH_SITE.'/components/com_rsform/uploads/';\r\n//</code>")."' WHERE FieldName='DESTINATION' AND ComponentTypeId=9 AND FieldValues LIKE '%RSadapter%'");
		$db->execute();
		// remove old "ATTACHUSEREMAIL" and "ATTACHADMINEMAIL" fields
		$db->setQuery("SELECT * FROM #__rsform_component_type_fields WHERE `ComponentTypeId` = 9 AND `FieldName`='ATTACHUSEREMAIL' OR `FieldName`='ATTACHADMINEMAIL'");
		if ($db->loadResult()) {
			$db->setQuery("DELETE FROM `#__rsform_component_type_fields` WHERE `ComponentTypeId` = 9 AND `FieldName` ='ATTACHUSEREMAIL'");
			$db->execute();
			$db->setQuery("DELETE FROM `#__rsform_component_type_fields` WHERE `ComponentTypeId` = 9 AND `FieldName` ='ATTACHADMINEMAIL'");
			$db->execute();
			
			// if we deleted the fields, then we need to migrate the old information
			$db->setQuery("SELECT `ComponentId` FROM `#__rsform_components` WHERE `ComponentTypeId` = 9 ");
			if ($uploadcomponents = $db->loadColumn()) {
				$db->setQuery("SELECT * FROM #__rsform_properties WHERE ComponentId IN (".implode(",", $uploadcomponents).") AND PropertyName IN ('ATTACHADMINEMAIL', 'ATTACHUSEREMAIL') AND PropertyValue='YES'");
				$properties = array();
				if ($tmp = $db->loadObject()) {
					if (!isset($properties[$tmp->ComponentId])) {
						$properties[$tmp->ComponentId] = array();
					}
					$properties[$tmp->ComponentId][$tmp->PropertyName] = 1;
				}
				
				foreach ($properties as $ComponentId => $property) {
					$updateemailattach = array();
					
					if (isset($property['ATTACHADMINEMAIL'])) {
						$updateemailattach[] = 'adminemail';
					}
					if (isset($property['ATTACHUSEREMAIL'])) {
						$updateemailattach[] = 'useremail';
					}
					
					if ($updateemailattach) {
						$db->setQuery("INSERT INTO #__rsform_properties SET ComponentId = '".$ComponentId."' , PropertyName = 'EMAILATTACH', PropertyValue = '".$db->escape(implode(",", $updateemailattach))."' ");
						$db->execute();
					}
				}
				
				// delete them
				$db->setQuery("DELETE FROM #__rsform_properties WHERE PropertyName IN ('ATTACHADMINEMAIL', 'ATTACHUSEREMAIL')");
				$db->execute();
			}
		}
		$db->setQuery("UPDATE `#__rsform_component_type_fields` SET `FieldType` = 'textarea' WHERE `ComponentTypeId` = 6 AND `FieldName` IN ('MINDATE', 'MAXDATE') AND `FieldType` = 'textbox'");
		$db->execute();
		
		$db->setQuery("UPDATE `#__rsform_component_type_fields` SET `FieldValues` = '//<code>\r\nreturn RSFormProHelper::getOtherCalendars(6);\r\n//</code>' WHERE `ComponentTypeId` = 6 AND `FieldName` = 'VALIDATIONCALENDAR'");
		$db->execute();
		
		// replace old ImageButton with Submits buttons fields
		$db->setQuery("SELECT `ComponentId` FROM `#__rsform_components` WHERE `ComponentTypeId` = 12 ");
		if ($imagebuttons = $db->loadColumn()) {
			$db->setQuery("SELECT `FieldName` FROM `#__rsform_component_type_fields` WHERE `ComponentTypeId` = 13 ");
			$submitButtonProperties = $db->loadColumn();
			
			$db->setQuery("SELECT * FROM #__rsform_properties WHERE ComponentId IN (".implode(",", $imagebuttons).")");
			if ($tmp = $db->loadObjectList()) {
				$newProperties = array();
				// handle common properties
				foreach ($tmp as $property) {
					if (!isset($newProperties[$property->ComponentId])) {
						$newProperties[$property->ComponentId] = array();
					}
					if (in_array($property->PropertyName, $submitButtonProperties)) {
						if ($property->PropertyName == 'ADDITIONALATTRIBUTES' && isset($newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES'])) {
							$newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES'] = $property->PropertyValue."\r\n".$newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES'];
						} else {
							$newProperties[$property->ComponentId][$property->PropertyName] = $property->PropertyValue;
						}
					} else if ($property->PropertyName == 'IMAGEBUTTON' && !empty($property->PropertyValue)) {
						$additional = 'type="image"'."\r\n".'src="'.$property->PropertyValue.'"';
						if (isset($newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES']) && !empty($newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES'])) {
							$additional = $newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES']."\r\n".$additional;
						}
						$newProperties[$property->ComponentId]['ADDITIONALATTRIBUTES'] = $additional;
					}
				}
				// add the submit button extra properties
				foreach ($newProperties as $ComponentId => $property) {
					foreach ($submitButtonProperties as $submitProperty) {
						$value = '';
						switch ($submitProperty) {
							case 'DISPLAYPROGRESS':
								$value = 'NO';
							break;
							case 'DISPLAYPROGRESSMSG':
								$value = '<div>'."\r\n".' <p><em>Page <strong>{page}</strong> of {total}</em></p>'."\r\n".' <div class="rsformProgressContainer">'."\r\n".'  <div class="rsformProgressBar" style="width: {percent}%;"></div>'."\r\n".' </div>'."\r\n".'</div>';
							break;
						}
						
						if (!empty($value)) {
							$newProperties[$ComponentId][$submitProperty] = $value;
						}
					}
				}
				
				foreach ($newProperties as $ComponentId => $property) {
					// delete the old image button specific properties
					$db->setQuery("DELETE FROM `#__rsform_properties` WHERE `ComponentId` = '".$ComponentId."'");
					$db->execute();
					
					// add the new submit button properties
					foreach ($property as $propertyName => $propertyValue) {
						$db->setQuery("INSERT INTO #__rsform_properties SET ComponentId = '".$ComponentId."' , PropertyName = '".$db->escape($propertyName)."', PropertyValue = '".$db->escape($propertyValue)."'");
						$db->execute();
					}
				}
			}
			
			// change the ComponentTypeId from the image button to the submit one
			$db->setQuery("UPDATE `#__rsform_components` SET `ComponentTypeId` = 13 WHERE `ComponentTypeId` = 12");
			$db->execute();
			
			// delete the image button component type
			$db->setQuery("DELETE FROM #__rsform_component_types WHERE `ComponentTypeId` = 12");
			$db->execute();
		}
	
		
		// #__rsform_components updates
		$columns = $db->getTableColumns('#__rsform_components', false);
		if ($columns['FormId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_components ADD INDEX (`FormId`)");
			$db->execute();
		}
		if ($columns['ComponentTypeId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_components ADD INDEX (`ComponentTypeId`)");
			$db->execute();
		}
		
		// #__rsform_properties
		$columns = $db->getTableColumns('#__rsform_properties', false);
		if ($columns['ComponentId']->Key != 'MUL') {
			$db->setQuery("ALTER TABLE #__rsform_properties ADD INDEX (`ComponentId`)");
			$db->execute();
		}
		if ($db->hasUTF8mb4Support())
		{
			if (strpos(strtolower($columns['PropertyValue']->Collation), 'utf8mb4') === false)
			{
				$db->setQuery('ALTER TABLE '.$db->qn('#__rsform_properties').' CHANGE '.$db->qn('PropertyValue').' '.$db->qn('PropertyValue').' '.$columns['PropertyValue']->Type.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
				$db->execute();
			}
		}
		
		// #__rsform_mappings migration
		$columns = $db->getTableColumns('#__rsform_mappings');
		if (isset($columns['MappingTable'])) {
			$db->setQuery("SELECT * FROM #__rsform_mappings");
			$mappings = $db->loadObjectList();

			$mtables = array();
			if (!empty($mappings))
			{
				foreach ($mappings as $mapping)
				{		
					$db->setQuery("SELECT p.PropertyValue FROM #__rsform_properties p LEFT JOIN #__rsform_components c ON (p.ComponentId = c.ComponentId) WHERE c.ComponentId='".$mapping->ComponentId."' AND p.PropertyName='NAME' AND c.Published='1' ORDER BY c.Order");
					$component = $db->loadResult();
					
					$db->setQuery("SELECT FormId FROM #__rsform_components WHERE ComponentId = '".$mapping->ComponentId."'");
					$formId = $db->loadResult();
					
					if (!empty($component))
					{
						$object = new stdClass();
						$object->column = $mapping->MappingColumn;
						$object->component = '{'.$component.':value}';
						$mtables[$mapping->MappingTable][$formId][] = $object;
					}
				}
			}
			
			$db->setQuery("DROP TABLE `#__rsform_mappings`");
			$db->execute();
			
			$this->runSQL('mappings.sql');

			$data = array();
			if (!empty($mtables))
			{
				foreach ($mtables as $table => $details)
				{
					if (!empty($details))
					foreach ($details as $formId => $columns)
					{
						if (!empty($columns))
						foreach ($columns as $column)
						{
							$data[$column->column] = $column->component;
						}
						
						if (!empty($data))
						{
							$data = serialize($data);
							
							$db->setQuery("INSERT INTO `#__rsform_mappings` SET `formId` = '".$db->escape($formId)."', `connection` = 0, `port` = '3306', `method` = 0, `table` = '".$db->escape($table)."', `data` = '".$db->escape($data)."' ");
							$db->execute();
						}
						unset($data);
					}
				}
			}
		}
		
		if (!isset($columns['driver'])) {
			$db->setQuery('ALTER TABLE `#__rsform_mappings` ADD `driver` VARCHAR( 16 ) NOT NULL AFTER `host`');
			$db->execute();
			
			$query = $db->getQuery(true)
						->update($db->qn('#__rsform_mappings'))
						->set($db->qn('driver').'='.$db->q(Factory::getApplication()->get('dbtype')))
						->where($db->qn('driver').'='.$db->q(''));
			$db->setQuery($query)->execute();
		}

		$changed = array('data', 'wheredata', 'extra');
		foreach ($changed as $column)
		{
			if (isset($columns[$column]) && $columns[$column] == 'text')
			{
				$db->setQuery("ALTER TABLE #__rsform_mappings CHANGE " . $db->qn($column) . " " . $db->qn($column) . ' mediumtext');
				$db->execute();
			}
		}

		// Add filename field to #__rsform_directory table
		$columns = $db->getTableColumns('#__rsform_directory');
		if (!isset($columns['filename'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `filename` VARCHAR(255) NOT NULL DEFAULT 'export.pdf' AFTER `formId`");
			$db->execute();
		}
		if (!isset($columns['csvfilename'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `csvfilename` VARCHAR(255) NOT NULL DEFAULT '{alias}.csv' AFTER `filename`");
			$db->execute();
		}
		if (!isset($columns['EmailsCreatedScript'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `EmailsCreatedScript` TEXT NOT NULL AFTER `EmailsScript`");
			$db->execute();
		}
        if (!isset($columns['DeletionGroups'])) {
            $db->setQuery("ALTER TABLE `#__rsform_directory` ADD `DeletionGroups` TEXT NOT NULL AFTER `groups`");
            $db->execute();
        }
		if (!isset($columns['HideEmptyValues'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `HideEmptyValues` tinyint(1) NOT NULL AFTER `enablecsv`");
			$db->execute();
		}
		if (!isset($columns['ShowGoogleMap'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `ShowGoogleMap` tinyint(1) NOT NULL AFTER `HideEmptyValues`");
			$db->execute();
		}
		if (!isset($columns['EditScript'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `EditScript` MEDIUMTEXT NOT NULL AFTER `ListScript`");
			$db->execute();
		}
		if (!isset($columns['SaveScript'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `SaveScript` MEDIUMTEXT NOT NULL AFTER `EditScript`");
			$db->execute();
		}
		if (!isset($columns['AllowCSVFullDownload'])) {
			$db->setQuery("ALTER TABLE `#__rsform_directory` ADD `AllowCSVFullDownload` tinyint(1) NOT NULL AFTER `enablecsv`");
			$db->execute();
		}
		$changed = array('CSS', 'JS', 'ListScript', 'DetailsScript', 'EmailsScript', 'EmailsCreatedScript');
		foreach ($changed as $column)
		{
			if (isset($columns[$column]) && $columns[$column] == 'text')
			{
				$db->setQuery("ALTER TABLE #__rsform_directory CHANGE " . $db->qn($column) . " " . $db->qn($column) . ' mediumtext');
				$db->execute();
			}
		}

		// #__rsform_posts updates
		$columns = $db->getTableColumns('#__rsform_posts');
		if (!isset($columns['fields'])) {
			$db->setQuery("ALTER TABLE `#__rsform_posts` ADD `fields` MEDIUMTEXT NOT NULL AFTER `method`");
			$db->execute();
		}
		if (!isset($columns['headers'])) {
			$db->setQuery("ALTER TABLE `#__rsform_posts` ADD `headers` MEDIUMTEXT NOT NULL AFTER `fields`");
			$db->execute();
		}
		
		// Update DESTINATION to relative path format.
		$query = $db->getQuery(true);
		$query->update($db->qn('#__rsform_component_type_fields'))
			  ->set($db->qn('FieldValues').' = '.$db->q("//<code>\r\nreturn 'components/com_rsform/uploads/';\r\n//</code>"))
			  ->where($db->qn('FieldName').' = '.$db->q('DESTINATION'))
			  ->where($db->qn('ComponentTypeId').' = '.$db->q(9))
			  ->where($db->qn('FieldValues').' = '.$db->q('%JPATH_SITE%'));
		$db->setQuery($query);
		$db->execute();
		
		// Change RSgetValidationRules() to the new format
		$query = $db->getQuery(true);
		$query->update($db->qn('#__rsform_component_type_fields'))
			  ->set($db->qn('FieldValues').' = '.$db->q("//<code>\r\nreturn RSFormProHelper::getValidationRules();\r\n//</code>"))
			  ->where($db->qn('FieldName').' = '.$db->q('VALIDATIONRULE'))
			  ->where($db->qn('FieldValues').' = '.$db->q('%RSgetValidationRules%'));
		$db->setQuery($query);
		$db->execute();

		$columns = $db->getTableColumns('#__rsform_calculations', false);
		if (!$columns['formId']->Key)
		{
			$db->setQuery('ALTER TABLE `#__rsform_calculations` ADD INDEX(`formId`), ADD INDEX (`ordering`), ADD INDEX (`formId`, `ordering`)')->execute();
		}

		$columns = $db->getTableColumns('#__rsform_directory_fields');
        if (!isset($columns['sort']))
        {
	        $db->setQuery("ALTER TABLE `#__rsform_directory_fields` ADD `sort` tinyint(1) NOT NULL DEFAULT '0' AFTER `incsv`");
	        $db->execute();
        }

		if (!empty($this->migrateResponsiveLayoutFramework)) {
			$query = $db->getQuery(true);
			$query->update($db->qn('#__rsform_forms'))
				->set($db->qn('LoadFormLayoutFramework').'='.$db->q(1))
				->where($db->qn('FormLayoutName').'='.$db->q('responsive'));

			$db->setQuery($query)
				->execute();
		}

		// Let's see if we have legacy layouts
		$query = $db->getQuery(true)
			->select('FormId')
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormLayoutName') . ' IN (' . implode(',', $db->q(self::$legacy)) . ')');
		if ($forms = $db->setQuery($query)->loadColumn())
		{
			$query = $db->getQuery(true)
				->update($db->qn('#__rsform_forms'))
				->set($db->qn('GridLayout') . ' = ' . $db->q(''))
				->where($db->qn('FormId') . ' IN (' . implode(',', $db->q($forms)) . ')');
			$db->setQuery($query)->execute();
		}

		// Update legacy params
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__menu'))
			->where($db->qn('client_id') . ' = ' . $db->q(0))
			->where($db->qn('link') . ' IN (' . implode(',', $db->q(array('index.php?option=com_rsform&view=directory', 'index.php?option=com_rsform&view=submissions'))) . ')');
		if ($items = $db->setQuery($query)->loadObjectList())
		{
			foreach ($items as $item)
			{
				if ($item->params)
				{
					$params = new Registry($item->params);
					$userId	= $params->get('userId');
					if ($userId === '0')
					{
						$params->set('show_all_submissions', 1);
						$params->set('show_logged_in_submissions', 0);
						$params->set('userId', '');
					}
					elseif ($userId == 'login')
					{
						$params->set('show_all_submissions', 0);
						$params->set('show_logged_in_submissions', 1);
						$params->set('userId', '');
					}
					else
					{
						continue;
					}

					$updateObject = (object) array(
						'id' => $item->id,
						'params' => $params->toString()
					);

					$db->updateObject('#__menu', $updateObject, array('id'));
				}
			}
		}
	}
	
	public function uninstall($parent) {
		$db = Factory::getDbo();

		// Uninstall the Installer - RSForm! Pro Plugin
		$query = $db->getQuery(true);
		$query->select($db->qn('extension_id'))
			  ->from($db->qn('#__extensions'))
			  ->where($db->qn('element').'='.$db->q('rsform'))
			  ->where($db->qn('type').'='.$db->q('plugin'))
			  ->where($db->qn('folder').'='.$db->q('installer'));
		$db->setQuery($query);
		$plg_installer_id = (int) $db->loadResult();
		
		if (!empty($plg_installer_id)) {
			// Get a new installer
			$installer = $this->getJoomlaInstaller();
			$installer->uninstall('plugin', $plg_installer_id);
		}

		// Uninstall the System - RSForm! Pro Delete Submissions Plugin
        $query = $db->getQuery(true);
        $query->select($db->qn('extension_id'))
            ->from($db->qn('#__extensions'))
            ->where($db->qn('element').'='.$db->q('rsformdeletesubmissions'))
            ->where($db->qn('type').'='.$db->q('plugin'))
            ->where($db->qn('folder').'='.$db->q('system'));
        $db->setQuery($query);
        $plg_installer_id = (int) $db->loadResult();

        if (!empty($plg_installer_id)) {
            // Get a new installer
            $installer = $this->getJoomlaInstaller();
            $installer->uninstall('plugin', $plg_installer_id);
        }

		// Uninstall the System - RSForm! Pro Console Plugin
		$query = $db->getQuery(true);
		$query->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element').'='.$db->q('rsformconsole'))
			->where($db->qn('type').'='.$db->q('plugin'))
			->where($db->qn('folder').'='.$db->q('system'));
		$db->setQuery($query);
		$plg_installer_id = (int) $db->loadResult();

		if (!empty($plg_installer_id)) {
			// Get a new installer
			$installer = $this->getJoomlaInstaller();
			$installer->uninstall('plugin', $plg_installer_id);
		}

        // Uninstall the Action Log - RSForm! Pro Plugin
        $query = $db->getQuery(true);
        $query->select($db->qn('extension_id'))
                ->from($db->qn('#__extensions'))
                ->where($db->qn('element').'='.$db->q('rsform'))
                ->where($db->qn('type').'='.$db->q('plugin'))
                ->where($db->qn('folder').'='.$db->q('actionlog'));
        $db->setQuery($query);
        $plg_installer_id = (int) $db->loadResult();

        if (!empty($plg_installer_id)) {
            // Get a new installer
            $installer = $this->getJoomlaInstaller();
            $installer->uninstall('plugin', $plg_installer_id);
        }

		// Uninstall the Page Cache - RSForm! Pro Plugin
		$query = $db->getQuery(true);
		$query->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('element').'='.$db->q('rsform'))
			->where($db->qn('type').'='.$db->q('plugin'))
			->where($db->qn('folder').'='.$db->q('pagecache'));
		$db->setQuery($query);
		$plg_installer_id = (int) $db->loadResult();

		if (!empty($plg_installer_id)) {
			// Get a new installer
			$installer = $this->getJoomlaInstaller();
			$installer->uninstall('plugin', $plg_installer_id);
		}
	}
	
	public function preflight($type, $parent) {
        try
        {
            if (!class_exists('\\Joomla\\CMS\\Version'))
            {
	            throw new Exception(sprintf('Please upgrade to at least Joomla! %s before continuing!', static::$minJoomla));
            }

	        $jversion = new \Joomla\CMS\Version;
	        if (!$jversion->isCompatible(static::$minJoomla))
	        {
		        throw new Exception(sprintf('Please upgrade to at least Joomla! %s before continuing!', static::$minJoomla));
	        }

	        if (version_compare(PHP_VERSION, static::$minPHP, '<'))
	        {
		        throw new Exception('Please upgrade PHP to at least version ' . static::$minPHP . ' before continuing!');
            }

	        // Flag to check if we should set 'Load Layout Framework' to 'Yes' for 'Responsive' layout forms now that front.css is missing responsive declarations
	        if ($type == 'update' && !file_exists(JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/formlayouts/responsive.php'))
	        {
		        $this->migrateResponsiveLayoutFramework = true;
	        }

	        // This has been added in 3.0.0, so it's an update from an older version
	        if ($type == 'update' && !is_dir(JPATH_ADMINISTRATOR . '/components/com_rsform/views/calculation'))
	        {
		        $this->warnPlugins = true;
	        }
        }
        catch (Exception $e)
        {
            if (class_exists('\\Joomla\\CMS\\Factory'))
            {
                $app = \Joomla\CMS\Factory::getApplication();
            }
            elseif (class_exists('JFactory'))
            {
	            $app = JFactory::getApplication();
            }

            if (!empty($app))
            {
	            $app->enqueueMessage($e->getMessage(), 'error');
            }

            return false;
        }
		
		return true;
	}
	
	public function postflight($type, $parent) {
		if ($type == 'uninstall') {
			return true;
		}
		
		$this->source = $parent->getParent()->getPath('source');
		
		$db = Factory::getDbo();
		
		$messages = array(
			'plg_installer' 				=> false,
			'plg_rsformdeletesubmissions' 	=> false,
            'plg_pagecache'                 => false,
			'plugins' 						=> array(),
			'modules' 						=> array()
		);
		// update plugins, modules as necessary
		
		// Get a new installer
		$installer = $this->getJoomlaInstaller();
		if ($installer->install($this->source.'/other/plg_installer')) {
			$query = $db->getQuery(true);
			$query->update('#__extensions')
				  ->set($db->qn('enabled').'='.$db->q(1))
				  ->where($db->qn('element').'='.$db->q('rsform'))
				  ->where($db->qn('type').'='.$db->q('plugin'))
				  ->where($db->qn('folder').'='.$db->q('installer'));
			$db->setQuery($query);
			$db->execute();
			
			$messages['plg_installer'] = true;
		}
		
		// Get a new installer
		$installer = $this->getJoomlaInstaller();
		if ($installer->install($this->source.'/other/plg_rsformdeletesubmissions')) {
			$query = $db->getQuery(true);
			$query->update('#__extensions')
				  ->set($db->qn('enabled').'='.$db->q(1))
				  ->where($db->qn('element').'='.$db->q('rsformdeletesubmissions'))
				  ->where($db->qn('type').'='.$db->q('plugin'))
				  ->where($db->qn('folder').'='.$db->q('system'));
			$db->setQuery($query);
			$db->execute();
			
			$messages['plg_rsformdeletesubmissions'] = true;
		}

		// Get a new installer
		$installer = $this->getJoomlaInstaller();
		if ($installer->install($this->source.'/other/plg_pagecache')) {
			$query = $db->getQuery(true);
			$query->update('#__extensions')
				->set($db->qn('enabled').'='.$db->q(1))
				->where($db->qn('element').'='.$db->q('rsform'))
				->where($db->qn('type').'='.$db->q('plugin'))
				->where($db->qn('folder').'='.$db->q('pagecache'));
			$db->setQuery($query);
			$db->execute();

			$messages['plg_pagecache'] = true;
		}

		// Get a new installer
		if (version_compare(JVERSION, '4.0', '>=')) {
			$installer = $this->getJoomlaInstaller();
			if ($installer->install($this->source.'/other/plg_console'))
			{
				$messages['plg_console'] = true;
            }
            $installer = $this->getJoomlaInstaller();
            if ($installer->install($this->source.'/other/plg_actionlog'))
            {
                $messages['plg_actionlog'] = true;
            }
		}

        if ($type === 'install')
        {
            // Set defaults to Bootstrap 5 on Joomla! 4
            if (version_compare(JVERSION, '4.0', '>='))
            {
                $query = $db->getQuery(true)
                    ->update('#__rsform_config')
                    ->set($db->qn('SettingValue') . ' = ' . $db->q('bootstrap5'))
                    ->where($db->qn('SettingName') . ' = ' . $db->q('global.default_layout'));

                $db->setQuery($query)->execute();

	            $query = $db->getQuery(true)
		            ->update('#__rsform_forms')
		            ->set($db->qn('FormLayoutName') . ' = ' . $db->q('bootstrap5'))
		            ->where($db->qn('FormLayoutName') . ' = ' . $db->q('responsive'));
	            $db->setQuery($query)->execute();
            }
        }
		
		$this->showInstallMessage($messages);
	}

    private function getJoomlaInstaller()
    {
        $installer = new Installer();
        if (is_callable(array($installer, 'setDatabase')))
        {
            $installer->setDatabase(Joomla\CMS\Factory::getDbo());
        }
        return $installer;
    }
	
	protected function runSQL($file) {
		$db = Factory::getDbo();
		$driver = strtolower($db->name);
		if (strpos($driver, 'mysql') !== false) {
			$driver = 'mysql';
		} elseif ($driver == 'sqlsrv') {
			$driver = 'sqlazure';
		}
		
		$sqlfile = $this->source.'/admin/sql/'.$driver.'/'.$file;
		
		if (file_exists($sqlfile)) {
			$buffer = file_get_contents($sqlfile);
			if ($buffer !== false) {
				$queries = $db->splitSql($buffer);
				foreach ($queries as $query) {
					$query = trim($query);
					if ($query != '') {
						$db->setQuery($query);
						try
                        {
                            $db->execute();
                        }
                        catch (Exception $e)
                        {
                            Factory::getApplication()->enqueueMessage($e->getMessage());
                        }
					}
				}
			}
		}
	}
	
	protected function escape($string) {
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}
	
	protected function showInstallMessage($messages=array()) {
?>
<style type="text/css">
.version-history {
	margin: 0 0 2em 0;
	padding: 0;
	list-style-type: none;
}
.version-history > li {
	margin: 0 0 0.5em 0;
	padding: 0 0 0 4em;
}
.version-new,
.version-fixed,
.version-upgraded {
	float: left;
	font-size: 0.8em;
	margin-left: -4.9em;
	width: 4.5em;
	color: white;
	text-align: center;
	font-weight: bold;
	text-transform: uppercase;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
}

.version-new {
	background: #7dc35b;
}
.version-fixed {
	background: #e9a130;
}
.version-upgraded {
	background: #61b3de;
}

.install-ok {
	background: #7dc35b;
	color: #fff;
	padding: 3px;
}

.install-not-ok {
	background: #E9452F;
	color: #fff;
	padding: 3px;
}

.install-warning {
	background: #EFBB67;
	color: #fff;
	padding: 3px;
}
.big-warning {
	background: #FAF0DB;
	border: solid 1px #EBC46F;
	padding: 5px;
	font-size: 22px;
	line-height: 22px;
}

.big-warning b {
	color: red;
}

.red {
	color: red;
}

	.rsform-row {
		width: 100%;
		display: block;
		margin-bottom: 2%;
	}

	.rsform-row:after {
		clear: both;
		display: block;
		content: "";
	}

	.rsform-column-2 {
		width: 19%;
		margin-right: 1%;
		float: left;
	}

	.rsform-column-10 {
		width: 80%;
		float: left;
	}
</style>
	<div class="rsform-row">
	<div class="rsform-column-2">
		<img src="<?php echo Uri::root(true); ?>/media/com_rsform/images/admin/box.png" alt="RSForm! Pro Box" />
	</div>
	<div class="rsform-column-10">
		<p>Installer Plugin ...
			<?php if ($messages['plg_installer']) { ?>
			<b class="install-ok">Installed</b>
			<?php } else { ?>
			<b class="install-not-ok">Error installing!</b>
			<?php } ?>
		</p>
		<p>System - RSForm! Pro Delete Submissions Plugin ...
			<?php if ($messages['plg_rsformdeletesubmissions']) { ?>
			<b class="install-ok">Installed</b>
			<?php } else { ?>
			<b class="install-not-ok">Error installing!</b>
			<?php } ?>
		</p>
        <p>Page Cache - RSForm! Pro Plugin ...
			<?php if ($messages['plg_pagecache']) { ?>
                <b class="install-ok">Installed</b>
			<?php } else { ?>
                <b class="install-not-ok">Error installing!</b>
			<?php } ?>
        </p>
        <?php
        if (isset($messages['plg_console']))
        {
            ?>
            <p>System - RSForm! Pro CLI Plugin ...
                <?php if ($messages['plg_console']) { ?>
                    <b class="install-ok">Installed</b>. Please enable manually if you wish to use the CLI.
                <?php } else { ?>
                    <b class="install-not-ok">Error installing!</b>
                <?php } ?>
            </p>
            <?php
        }
        if (isset($messages['plg_actionlog']))
        {
            ?>
            <p>Action Log - RSForm! Pro Plugin ...
                <?php if ($messages['plg_actionlog']) { ?>
                    <b class="install-ok">Installed</b>. Please enable manually if you wish to log actions in the User Action Log.
                <?php } else { ?>
                    <b class="install-not-ok">Error installing!</b>
                <?php } ?>
            </p>
            <?php
        }
        ?>
		<h2>Changelog v3.4.2</h2>
		<ul class="version-history">
            <li><span class="version-upgraded">Upg</span> Replaced instances of legacy 'Chosen' dropdowns with 'Joomla! Fancy Select' on Joomla! 4 and 5.</li>
            <li><span class="version-fixed">Fix</span> 'Birthday Field' no longer generates a label for better accessibility compliance.</li>
            <li><span class="version-fixed">Fix</span> Pico CSS 'Checkbox Group' and 'Radio Group' fields were not showing up correctly if they contained longer text.</li>
		</ul>
		<a class="btn btn-large btn-lg btn-primary text-white" href="index.php?option=com_rsform">Start using RSForm! Pro</a>
		<a class="btn btn-secondary text-white" href="https://www.rsjoomla.com/support/documentation/rsform-pro.html" target="_blank">Read the RSForm! Pro User Guide</a>
		<a class="btn btn-secondary text-white" href="https://www.rsjoomla.com/support.html" target="_blank">Get Support!</a>
	</div>
	</div>
		<?php
	}
}