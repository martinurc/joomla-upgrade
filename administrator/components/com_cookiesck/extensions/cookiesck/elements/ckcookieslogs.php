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

class JFormFieldCkcookieslogs extends FormField {

	protected $type = 'ckcookieslogs';

	protected function getLabel() {
		return '';
	}

	protected function getInput() {

		if (!file_exists(JPATH_ROOT . '/administrator/components/com_cookiesck/cookiesck.php')) {
			$html = '<a class="ckpopupwizardmanager_button light" href="https://www.joomlack.fr/en/joomla-extensions/cookies-ck" target="_blank">' . Text::_('COOKIESCK_COMPONENT_PRO_NOT_INSTALLED') . '</a>';

		} else {
		$html = '<i-frame id="cookiescklogsiframe" src="' . Uri::root(true) . '/administrator/index.php?option=com_cookiesck&view=logs&tmpl=component" />';
		$html .= '<script>var cookieframe = document.getElementById(\'cookiescklogsiframe\');'
				. 'cookieframe.outerHTML = cookieframe.outerHTML.replace(/i-frame/g,"iframe");'
				. '</script>'; // hack to manage issue in joomla with the iframe
		
		$css = 'iframe#cookiescklogsiframe {
width: 100%;
display: block;
height: 800px;
border: none;
}';
			$doc = Factory::getDocument();
		$doc->addStyleDeclaration($css);
		}


		return $html;
	}
}
