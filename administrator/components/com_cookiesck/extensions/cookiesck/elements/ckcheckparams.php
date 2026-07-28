<?php
/**
 * @copyright	Copyright (C) 2017 Cedric KEIFLIN alias ced1870
 * http://www.joomlack.fr
 * @license		GNU/GPL
 * */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class JFormFieldCkcheckparams extends FormField
{

	protected $type = 'ckcheckparams';

	function __construct($form = null) {
		parent::__construct($form);
	}

	protected function getInput() {
		$paramsEnabled = file_exists(JPATH_SITE . '/administrator/components/com_cookiesck/cookiesck.php');
		$html = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
			. (int)$paramsEnabled . '"/>';

		return $html;
	}

	protected function getLabel() {
		return '';
	}
}
