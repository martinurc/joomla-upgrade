<?php

/**
 * @copyright	Copyright (C) 2011 Cedric KEIFLIN alias ced1870
 * http://www.joomlack.fr
 * @license		GNU/GPL
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.form');
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class JFormFieldCkstyleswizard extends FormField {

	protected $type = 'ckstyleswizard';

	protected function getInput() {
		return '';
	}

	protected function getLabel() {

		$doc = Factory::getDocument();
		$paramsEnabled = file_exists(JPATH_SITE . '/administrator/components/com_cookiesck/cookiesck.php');
		$imgpath = Uri::root(true) . '/plugins/system/cookiesck/elements/images/';
		if ($paramsEnabled) {
			$doc = Factory::getDocument();
			$doc->addScript(Uri::root(true) . '/media/com_cookiesck/assets/ckbox.js');
			$doc->addStylesheet(Uri::root(true) . '/media/com_cookiesck/assets/ckbox.css');
//			$com_params_text = '<img src="' . $imgpath . 'accept.png" />' . Text::_('COOKIESCK_COMPONENT_PARAMS_INSTALLED');
			//$button = '<input name="' . $this->name . '_button" id="' . $this->name . '_button" class="ckpopupwizardmanager_button" style="background-image:url(' . $imgpath . 'pencil.png);width:100%;" type="button" value="' . Text::_('MAXIMENUCK_STYLES_WIZARD') . '" onclick="SqueezeBox.fromElement(this, {handler:\'iframe\', size: {x: 800, y: 500}, url:\'' . Uri::root(true) . '/administrator/index.php?option=com_maximenuck&view=modules&view=styles&&layout=modal&id=' . $input->get('id',0,'int') .'\'})"/>';
			$button = '<input name="' . $this->name . '_button" id="' . $this->name . '_button" class="ckpopupwizardmanager_button" style="background-image:url(' . $imgpath . 'pencil.png);width:100%;min-width: 300px;" type="button" value="' . Text::_('COOKIESCK_STYLES_WIZARD') . '" onclick="CKBox.open({handler:\'iframe\', fullscreen: true, url:\'' . Uri::root(true) . '/administrator/index.php?option=com_cookiesck&view=modules&view=style&tmpl=component&id=1\'})"/>';
		} else {
			$button = '<a class="ckpopupwizardmanager_button light" href="https://www.joomlack.fr/en/joomla-extensions/cookies-ck" target="_blank">' . Text::_('COOKIESCK_COMPONENT_PRO_NOT_INSTALLED') . '</a>';
//			$button = '';
		}
		$html = '';
		// css styles already loaded into the ckmaximenuchecking field
//		$html .= $com_params_text ? '<div class="ckchecking">' . $com_params_text . '</div>' : '';
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
	display: inline-block;
}

.ckpopupwizardmanager_button.light {
	text-align: center;
	text-indent: 0px;
	padding: 20px;
	height: auto;
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
}
