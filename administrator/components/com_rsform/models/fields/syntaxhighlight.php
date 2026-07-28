<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\TextareaField;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

FormHelper::loadFieldClass('textarea');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\TextareaField', 'JFormFieldTextarea');
}

class JFormFieldSyntaxhighlight extends TextareaField
{
	protected function getInput()
	{
		$useEditor 	= RSFormProHelper::getConfig('global.codemirror');
		$editor 	= 'codemirror';

		if ($useEditor)
		{
			$plugin = PluginHelper::getPlugin('editors', $editor);

			if (empty($plugin))
			{
				$useEditor = false;
			}
			elseif (!is_string($plugin->params) && is_callable(array($plugin->params, 'toString')))
			{
				$plugin->params = $plugin->params->toString();
			}
		}

		if ($useEditor)
		{
			$syntax 	= !empty($this->element['syntax']) ? (string) $this->element['syntax'] : 'html';
			$readonly 	= $this->readonly;
			$instance 	= Editor::getInstance($editor);

			// Inline PHP
			if ($syntax === 'php')
			{
				Factory::getDocument()->addScriptDeclaration("window.addEventListener('load', function(){ RSFormPro.fixCodeMirror(" . json_encode($this->id) . "); });");
			}

			return $instance->display($this->name, $this->escape($this->value), '100%', 300, 75, 20, $buttons = false, $this->id, $asset = null, $author = null, array('syntax' => $syntax, 'readonly' => $readonly));
		}
		else
		{
			return parent::getInput();
		}
	}

	protected function escape($string)
	{
		return htmlspecialchars((string) $string, ENT_COMPAT, 'utf-8');
	}
}
