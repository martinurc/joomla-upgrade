<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.core');

HTMLHelper::_('script', 'com_rsform/admin/forms.js', array('relative' => true, 'version' => 'auto'));
Text::script('ERROR');
Text::script('WARNING');
Text::script('RSFP_SPECIFY_FORM_NAME');
Text::script('RSFP_COMP_FIELD_VALIDATIONEXTRARESTRICTEDWORDS');
Text::script('RSFP_COMP_FIELD_VALIDATIONEXTRARESTRICTEDTEXT');
Text::script('RSFP_COMP_FIELD_VALIDATIONEXTRAREGEX');
Text::script('RSFP_COMP_FIELD_VALIDATIONEXTRASAMEAS');
Text::script('RSFP_COMP_FIELD_VALIDATIONEXTRALENGTH');
Text::script('RSFP_COMP_FIELD_VALIDATIONEXTRA');
Text::script('RSFP_REMOVE_COMPONENT_CONFIRM');
Text::script('RSFP_AUTOGENERATE_LAYOUT_WARNING_SURE');
Text::script('RSFP_ARE_YOU_SURE_DELETE');
Text::script('RSFP_DELETE_SURE_CALCULATION');
Text::script('COM_RSFORM_PLEASE_TYPE_IN_BEFORE_SAVING_CALCULATION');
Text::script('RSFP_CONDITION_DELETE_SURE');
Text::script('RSFP_AUTOGENERATE_LAYOUT_DISABLED');

Text::script('COM_RSFORM_EMAIL_FIELD_ERROR_WRONG_PLACEHOLDER');
Text::script('COM_RSFORM_EMAIL_FIELD_ERROR_NOT_AN_EMAIL');
Text::script('COM_RSFORM_EMAIL_FIELD_ERROR_WRONG_DELIMITER');
?>
	<form action="<?php echo Route::_('index.php?option=com_rsform&view=forms&layout=edit&formId=' . $this->form->FormId); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off">
		<?php
		echo HTMLHelper::_('bootstrap.renderModal', 'editModal', array(
			'title' => Text::_('RSFP_FORM_FIELD'),
			'footer' => $this->loadTemplate('modal_footer'),
			'bodyHeight' => 75,
            'modalWidth' => RSFormProHelper::getConfig('global.modal_width'),
			'closeButton' => false,
			'backdrop' => 'static'
		),
		$this->loadTemplate('modal_body'));
		?>
        <?php if (!RSFormProHelper::getConfig('global.disable_multilanguage')) { ?>
	        <p>
            <span><?php echo $this->jform->getField('Language')->input; ?></span>
            <span><?php echo Text::sprintf('RSFP_YOU_ARE_EDITING_IN', $this->lang, RSFormProHelper::translateIcon()); ?></span>
	        </p>
        <?php } else { ?>
			<p><span><?php echo Text::sprintf('RSFP_YOU_ARE_EDITING_IN_SHORT', $this->lang); ?></span></p>
		<?php } ?>

		<div id="rsform_container">
			<div id="state" style="display: none;"><?php echo HTMLHelper::_('image', 'com_rsform/admin/load.gif', Text::_('RSFP_PROCESSING'), null, true); ?><?php echo Text::_('RSFP_PROCESSING'); ?></div>
			<p>
				<a href="javascript: void(0);" id="components" class="btn btn-large btn-lg"><span class="rsficon rsficon-grid"></span><span class="inner-text"><?php echo Text::_('RSFP_COMPONENTS_TAB_TITLE'); ?></span></a>
				<a href="javascript: void(0);" id="properties" class="btn btn-large btn-lg"><span class="rsficon rsficon-cogs"></span><span class="inner-text"><?php echo Text::_('RSFP_PROPERTIES_TAB_TITLE'); ?></span></a>
			</p>
			<div id="rsform_components_tab">
				<?php echo $this->loadTemplate('components'); ?>
			</div>

			<div id="rsform_properties_tab">
				<ul class="rsform_leftnav" id="rsform_secondleftnav">
					<li class="rsform_navtitle"><?php echo Text::_('RSFP_DESIGN_TAB'); ?></li>
					<li><a href="javascript: void(0);" id="formlayout"><span class="rsficon rsficon-list-alt"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_LAYOUT'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="cssandjavascript"><span class="rsficon rsficon-file-code-o"></span><span class="inner-text"><?php echo Text::_('RSFP_CSS_JS'); ?></span></a></li>
					<?php $this->triggerEvent('onRsformBackendAfterShowFormDesignTabsTab'); ?>
					<li class="rsform_navtitle"><?php echo Text::_('RSFP_FORM_TAB'); ?></li>
					<li><a href="javascript: void(0);" id="editform"><span class="rsficon rsficon-info-circle"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_EDIT'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="editformattributes"><span class="rsficon rsficon-grain"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_EDIT_ATTRIBUTES'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="metatags"><span class="rsficon rsficon-earth"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_META_TAGS'); ?></span></a></li>
					<?php $this->triggerEvent('onRsformBackendAfterShowFormFormTabsTab'); ?>
					<li class="rsform_navtitle"><?php echo Text::_('RSFP_EMAILS_TAB'); ?></li>
					<li><a href="javascript: void(0);" id="useremails"><span class="rsficon rsficon-envelope-o"></span><span class="inner-text"><?php echo Text::_('RSFP_USER_EMAILS'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="adminemails"><span class="rsficon rsficon-envelope"></span><span class="inner-text"><?php echo Text::_('RSFP_ADMIN_EMAILS'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="emails"><span class="rsficon rsficon-envelope-square"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_EMAILS'); ?></span></a></li>
                    <li><a href="javascript: void(0);" id="deletionemail"><span class="rsficon rsficon-bell"></span><span class="inner-text"><?php echo Text::_('COM_RSFORM_FORM_DELETION_EMAIL'); ?></span></a></li>
					<?php $this->triggerEvent('onRsformBackendAfterShowFormEmailsTabsTab'); ?>
					<li class="rsform_navtitle"><?php echo Text::_('RSFP_SCRIPTS_TAB'); ?></li>
					<li><a href="javascript: void(0);" id="scripts"><span class="rsficon rsficon-code"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_SCRIPTS'); ?></span></a></li>
                    <li><a href="javascript: void(0);" id="beforescripts"><span class="rsficon rsficon-code"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_BEFORE_SCRIPTS'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="emailscripts"><span class="rsficon rsficon-file-code-o"></span><span class="inner-text"><?php echo Text::_('RSFP_EMAIL_SCRIPTS'); ?></span></a></li>
					<?php $this->triggerEvent('onRsformBackendAfterShowFormScriptsTabsTab'); ?>
					<li class="rsform_navtitle"><?php echo Text::_('RSFP_EXTRAS_TAB'); ?></li>
					<li><a href="javascript: void(0);" id="mappings"><span class="rsficon rsficon-database"></span><span class="inner-text"><?php echo Text::_('RSFP_FORM_MAPPINGS'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="conditions"><span class="rsficon rsficon-rotate"></span><span class="inner-text"><?php echo Text::_('RSFP_CONDITIONAL_FIELDS'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="postscript"><span class="rsficon rsficon-envelope"></span><span class="inner-text"><?php echo Text::_('RSFP_POST_TO_LOCATION'); ?></span></a></li>
					<li><a href="javascript: void(0);" id="calculations"><span class="rsficon rsficon-calculator"></span><span class="inner-text"><?php echo Text::_('RSFP_CALCULATIONS'); ?></span></a></li>
					<?php $this->triggerEvent('onRsformBackendAfterShowFormEditTabsTab'); ?>
				</ul>

				<div id="propertiescontent">
					<div id="formlayoutdiv">
						<?php echo $this->loadTemplate('layout'); ?>
					</div><!-- formlayout -->
					<div id="cssandjavascriptdiv">
						<?php echo $this->loadTemplate('cssjs'); ?>
					</div><!-- cssandjavascript -->
					<?php $this->triggerEvent('onRsformBackendAfterShowFormDesignTabs'); ?>
					<div id="editformdiv">
						<?php echo $this->loadTemplate('form'); ?>
					</div><!-- editform -->
					<div id="editformattributesdiv">
						<?php echo $this->loadTemplate('formattr'); ?>
					</div><!-- editformattributes -->
					<div id="metatagsdiv">
						<?php echo $this->loadTemplate('meta'); ?>
					</div><!-- metatags -->
					<?php $this->triggerEvent('onRsformBackendAfterShowFormFormTabs'); ?>
					<div id="useremailsdiv">
						<?php echo $this->loadTemplate('user'); ?>
					</div><!-- useremails -->
					<div id="adminemailsdiv">
						<?php echo $this->loadTemplate('admin'); ?>
					</div><!-- adminemails -->
					<div id="emailsdiv">
						<h3 class="rsfp-legend"><?php echo Text::_('RSFP_FORM_EMAILS'); ?></h3>
						<p><button type="button" onclick="openRSModal('<?php echo Route::_('index.php?option=com_rsform&task=emails.edit&type=additional&tmpl=component&formId='.$this->formId); ?>', 'Emails', '800x750');" class="btn btn-primary"><?php echo Text::_('RSFP_FORM_EMAILS_NEW'); ?></button></p>
						<div id="emailsContent">
							<?php echo $this->loadTemplate('emails'); ?>
						</div>
					</div><!-- emails -->
                    <div id="deletionemaildiv">
                        <?php echo $this->loadTemplate('deletionemail'); ?>
                    </div><!-- emails -->
					<?php $this->triggerEvent('onRsformBackendAfterShowFormEmailsTabs'); ?>
					<div id="scriptsdiv">
						<?php echo $this->loadTemplate('scripts'); ?>
					</div><!-- scripts -->
                    <div id="beforescriptsdiv">
                        <?php echo $this->loadTemplate('beforescripts'); ?>
                    </div><!-- scripts -->
					<div id="emailscriptsdiv">
						<?php echo $this->loadTemplate('emailscripts'); ?>
					</div><!-- emailscripts -->
					<?php $this->triggerEvent('onRsformBackendAfterShowFormScriptsTabs'); ?>
					<div id="mappingsdiv">
						<p>
							<button type="button" class="btn btn-primary" onclick="openRSModal('<?php echo Route::_('index.php?option=com_rsform&view=mappings&formId='.$this->formId.'&tmpl=component'); ?>', 'Mappings', '1000x800')"><?php echo Text::_('RSFP_FORM_MAPPINGS_NEW'); ?></button>
						</p>
						<div id="mappingsContents" style="overflow: auto;">
							<?php echo $this->loadTemplate('mappings'); ?>
						</div>
					</div><!-- mappings -->
					<div id="conditionsdiv">
						<?php if (!RSFormProHelper::getConfig('global.disable_multilanguage')) { ?>
							<div class="alert alert-warning"><?php echo Text::_('RSFP_CONDITION_MULTILANGUAGE_WARNING'); ?></div>
						<?php } ?>
						<p>
							<button type="button" class="btn btn-primary" onclick="openRSModal('<?php echo Route::_('index.php?option=com_rsform&view=conditions&layout=edit&formId=' . $this->formId .'&tmpl=component'); ?>', 'Conditions', '1024x768')"><?php echo Text::_('RSFP_FORM_CONDITION_NEW'); ?></button>
						</p>
						<div id="conditionsContent" style="overflow: auto;">
							<?php echo $this->loadTemplate('conditions'); ?>
						</div>
					</div>
					<div id="postscriptdiv">
						<?php echo $this->loadTemplate('post'); ?>
					</div><!-- postscriptdiv -->
					<div id="calculationsdiv">
						<p>
							<button type="button" class="btn btn-primary" onclick="openRSModal('<?php echo Route::_('index.php?option=com_rsform&view=calculation&formId='.$this->formId.'&tmpl=component'); ?>', 'Calculations', '1000x800')"><?php echo Text::_('COM_RSFORM_NEW_CALCULATION'); ?></button>
						</p>

						<div id="calculationsContents">
							<?php echo $this->loadTemplate('calculations'); ?>
						</div>
					</div><!-- calculationsdiv -->
					<?php $this->triggerEvent('onRsformBackendAfterShowFormEditTabs'); ?>
				</div>
			</div>
			<div class="rsform_clear_both"></div>
		</div>

		<input type="hidden" name="tabposition" id="tabposition" value="<?php echo $this->tabposition; ?>" />
		<input type="hidden" name="tab" id="ptab" value="<?php echo $this->tab; ?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="formId" id="formId" value="<?php echo $this->form->FormId; ?>" />
		<input type="hidden" name="FormId" value="<?php echo $this->form->FormId; ?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="option" value="com_rsform" />
		<input type="hidden" name="Lang" value="<?php echo $this->form->Lang; ?>" />
		<input type="hidden" name="Backendmenu" value="<?php echo (int) $this->form->Backendmenu; ?>" />
		<?php if (Factory::getApplication()->input->getCmd('tmpl') == 'component') { ?>
			<input type="hidden" name="tmpl" value="component" />
		<?php } ?>
	</form>