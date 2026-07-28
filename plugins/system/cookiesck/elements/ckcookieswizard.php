<?php

/**
 * @copyright	Copyright (C) 2011 Cedric KEIFLIN alias ced1870
 * http://www.joomlack.fr
 * @license		GNU/GPL
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

//jimport('joomla.form.form');
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class JFormFieldCkcookieswizard extends FormField {

	protected $type = 'ckcookieswizard';

	protected function getLabel() {
		return '';
	}

	protected function getInput() {

		$doc = Factory::getDocument();
		$doc->addScript(Uri::root(true) . '/plugins/system/cookiesck/assets/admin.js');
		$doc->addStylesheet(Uri::root(true) . '/plugins/system/cookiesck/assets/admin.css');


		$html = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
			. htmlspecialchars($this->value) . '"/>';
		$html .= '<div class="alert alert-info"><a href="https://www.joomlack.fr/en/documentation/cookies-ck/326-how-to-use-cookies-ck" target="_blank">' . Text::_('COOKIESCK_DOCUMENTATION') . '</a></div>';
		$html .= '<div id="cookiesckmeter" style="display:none;">
<p class="alert">' . Text::_('COOKIESCK_SCAN_NEED_TIME') . '</p>
<div class="ckmeter ckmeter-animate">
	<span style="width: 20%">
		<span></span>
	</span>
</div>
</div>';
		$html .= '<div class="btn-group">';
		$html .= '<input type="text" name="scan_url" id="scan_url"' . ' value="" placeholder="' . Uri::root() . '" />';
		$html .= '<div class="btn btn-primary" onclick="ckStartCookiesScan()">' . Text::_('COOKIESCK_SCAN') . '</div> ';
		$html .= '</div>
<div id="cookiesckwizardiframe" style="display:none;" data-uriroot="' . Uri::root() . '" src="' . Uri::root() . '"></div>
<div><div class="cookiescklist-category-add cookiesck-control" onclick="ckCookiesAddCategory()">' . Text::_('COOKIESCK_ADD_CATEGORY') . '</div></div>
<div id="cookiesckwizard">
		';


		$cookiesList = json_decode(str_replace('|QQ|', '"', $this->value));
		$categoryhtml = $this->getHtmlTemplateCategoryOpen();
		$platformhtml = $this->getHtmlTemplatePlatformOpen();
		$cookiehtml = $this->getHtmlTemplateItem();
		if (! empty($cookiesList)) {
		foreach ($cookiesList as $category) {
			$html .= str_replace(
				array('[CATEGORY]', '[DESC]')
				, array($category->name, htmlspecialchars($category->desc))
				, $categoryhtml);
			foreach ($category->platforms as $platform) {
				$html .= str_replace(
					array('[CATEGORY]', '[PLATFORM]', '[DESC]', '[LEGAL]', '<option selected="selected">')
					, array($category->name, $platform->name, htmlspecialchars($platform->desc), Text::_('COOKIESCK_LEGAL_' . (isset($platform->legal) ? $platform->legal : '0')), isset($platform->legal) && $platform->legal == '1' ? '<option selected="selected">' : '<option>')
					, $platformhtml);
				foreach ($platform->cookies as $cookie) {
					$html .= str_replace(
					array('[CATEGORY]', '[PLATFORM]', '[COOKIE]', '[KEY]', '[DESC]')
					, array($category->name, $platform->name, $cookie->id, $cookie->key, htmlspecialchars($cookie->desc))
					, $cookiehtml);
				}
				$html .= $this->getHtmlTemplatePlatformClose();
			}
			$html .= $this->getHtmlTemplateCategoryClose();
		}
		}
		// close the wizard
		$html .= '</div>';


		// fix issue with single quote
		$platformhtml = str_replace("'", "|SQ|",  $platformhtml);
		// add the template for JS
		$html .= '<script>';
		$html .= 'var COOKIESCK_TEMPLATE_CATEGORY = \'' . $categoryhtml . $this->getHtmlTemplateCategoryClose() . '\';';
		$html .= 'var COOKIESCK_TEMPLATE_PLATFORM = \'' . str_replace(array('[LEGAL]', '<option selected="selected">'), array(Text::_('COOKIESCK_LEGAL_0'), '<option>'), $platformhtml) . $this->getHtmlTemplatePlatformClose() . '\';';
		$html .= 'var COOKIESCK_TEMPLATE_ITEM = \'' . $cookiehtml . '\';';
		$html .= 'var COOKIESCK_SAVE = Joomla.submitbutton;
Joomla.submitbutton = function(task){ckUpdateCookiesField();
COOKIESCK_SAVE(task);
};';
		$html .= '</script>';

		return $html;



		$paramsEnabled = file_exists(JPATH_SITE . '/administrator/components/com_cookiesck/cookiesck.php');
		$imgpath = Uri::root(true) . '/plugins/system/cookiesck/elements/images/';
		if ($paramsEnabled) {
			$doc = Factory::getDocument();
			$doc->addScript(Uri::root(true) . '/media/com_cookiesck/assets/ckbox.js');
			$doc->addStylesheet(Uri::root(true) . '/media/com_cookiesck/assets/ckbox.css');
			$button = '<input name="' . $this->name . '_button" id="' . $this->name . '_button" class="ckpopupwizardmanager_button" style="background-image:url(' . $imgpath . 'pencil.png);width:100%;min-width: 300px;" type="button" value="' . Text::_('COOKIESCK_STYLES_WIZARD') . '" onclick="CKBox.open({handler:\'iframe\', fullscreen: true, url:\'' . Uri::root(true) . '/administrator/index.php?option=com_cookiesck&view=modules&view=style&&layout=modal&tmpl=component&id=1\'})"/>';
		} else {
			$com_params_text = '<img src="' . $imgpath . 'cross.png" /><a href="https://www.joomlack.fr/en/joomla-extensions/cookies-ck" target="_blank">' . Text::_('COOKIESCK_COMPONENT_PRO_NOT_INSTALLED') . '</a>';
			$button = '';
		}
		$html = '';
		$html .= '<div class="clr"></div>';
		$html .= $button;

		$css = '.ckpopupwizardmanager_button {
	padding: 5px 5px 5px 30px;
	color: #333;
	text-decoration: none;
	font-weight: normal;
	text-align: center;
	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;
	border-radius: 3px;
	border: none;
	cursor: pointer;
	background: #e1e1e1 5px center no-repeat;
	text-indent: 30px;
	height: 65px;
	width: 100%;
	text-align: left;
	min-width: 300px;
}

.ckpopupwizardmanager_button:hover {
	background-color: #0088CC;
	color: #fff;
}

.ckchecking {
	background: #efefef;
	border: none;
	border-radius: 3px;
	color: #333;
	font-weight: normal;
	line-height: 24px;
	padding: 5px;
	margin: 3px 0;
	text-align: left;
	text-decoration: none;
	min-width: 300px;
	box-sizing: border-box;
}

.ckchecking img {
	margin: 5px;
}';
		$doc->addStyleDeclaration($css);
		return $html;
	}

	private function getHtmlTemplateCategoryOpen() {
		$html = '<div class="cookiescklist-category" data-category="[CATEGORY]">' 
					. '<div class="cookiescklist-category-move-up cookiesck-control" onclick="ckCookiesMoveUp(this)">↑</div>'
					. '<div class="cookiescklist-category-move-down cookiesck-control" onclick="ckCookiesMoveDown(this)">↓</div>'
					. '<div class="cookiescklist-category-edit cookiesck-control" onclick="ckCookiesEditCategory(this)">' . Text::_('COOKIESCK_EDIT') . '</div>'
					. '<div class="cookiescklist-save cookiesck-control" onclick="ckCookiesSaveText(this)">' . Text::_('COOKIESCK_SAVE') . '</div>'
					. '<div class="cookiescklist-category-remove cookiesck-control" onclick="ckCookiesRemoveCategory(this)">' . Text::_('COOKIESCK_REMOVE') . '</div>'
						. '<div class="cookiescklist-platform-add cookiesck-control" onclick="ckCookiesAddPlatform(this)">' . Text::_('COOKIESCK_ADD_PLATFORM') . '</div>'
					. '<div class="cookiescklist-category-name cookiesck-editable" data-label="' . Text::_('COOKIESCK_CATEGORY') . '">[CATEGORY]</div>'
					. '<div class="cookiescklist-category-desc cookiesck-editable" data-label="' . Text::_('COOKIESCK_DESCRIPTION') . '">[DESC]</div>'
					;

		return $html;
	}

	private function getHtmlTemplateCategoryClose() {
		$html = '</div>';
		// . '</div>';

		return $html;
	}

	private function getHtmlTemplatePlatformOpen() {
		$html = '<div class="cookiescklist-platform" data-category="[CATEGORY]" data-platform="[PLATFORM]">' 
					. '<div class="cookiescklist-platform-edit cookiesck-control" onclick="ckCookiesEditPlatform(this)">' . Text::_('COOKIESCK_EDIT') . '</div>'
					. '<div class="cookiescklist-save cookiesck-control" onclick="ckCookiesSaveText(this)">' . Text::_('COOKIESCK_SAVE') . '</div>'
					. '<div class="cookiescklist-platform-remove cookiesck-control" onclick="ckCookiesRemovePlatform(this)">' . Text::_('COOKIESCK_REMOVE') . '</div>'
					. '<div class="cookiescklist-item-add cookiesck-control" onclick="ckCookiesAddCookie(this)">' . Text::_('COOKIESCK_ADD_COOKIE') . '</div>'
					. '<div class="cookiescklist-platform-name cookiesck-editable" data-label="' . Text::_('COOKIESCK_PLATFORM') . '">[PLATFORM]</div>'
					. '<div class="cookiescklist-platform-desc cookiesck-editable" data-label="' . Text::_('COOKIESCK_DESCRIPTION') . '">[DESC]</div>'
					. '<div class="cookiescklist-platform-legal cookiesck-dropdown" data-label="' . Text::_('COOKIESCK_LEGAL') . '">[LEGAL]</div>'
					. '<div class="cookiescklist-platform-legal cookiesck-dropdown" data-label="' . Text::_('COOKIESCK_LEGAL') . '"><select><option>' . Text::_('COOKIESCK_LEGAL_0') . '</option><option selected="selected">' . (Text::_('COOKIESCK_LEGAL_1')) . '</option></select><div class="alert alert-warning">' 
					. Text::_('COOKIESCK_LEGAL_WARNING') 
					. '</div></div>'
					. '<div class="cookiescklist-platform-children">';

		return $html;
	}

	private function getHtmlTemplatePlatformClose() {
		$html = '</div>'
		. '</div>';

		return $html;
	}

	private function getHtmlTemplateItem() {
		$html = '<div class="cookiescklist-item" data-category="[CATEGORY]" data-platform="[PLATFORM]" data-item="[COOKIE]" data-key="[KEY]">' 
			. '<div class="cookiescklist-item-edit cookiesck-control" onclick="ckCookiesEditItem(this)">' . Text::_('COOKIESCK_EDIT') . '</div>'
			. '<div class="cookiescklist-save cookiesck-control" onclick="ckCookiesSaveText(this)">' . Text::_('COOKIESCK_SAVE') . '</div>'
			. '<div class="cookiescklist-item-remove cookiesck-control" onclick="ckCookiesRemoveItem(this)">' . Text::_('COOKIESCK_REMOVE') . '</div>'
			. '<div class="cookiescklist-item-key cookiesck-editable" data-label="' . Text::_('COOKIESCK_KEY') . '">[KEY]</div>'
			. '<div class="cookiescklist-item-desc cookiesck-editable" data-label="' . Text::_('COOKIESCK_DESCRIPTION') . '">[DESC]</div>'
		. '</div>';

		return $html;
	}
}
