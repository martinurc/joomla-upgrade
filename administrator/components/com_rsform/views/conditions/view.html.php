<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

class RsformViewConditions extends HtmlView
{
	public function display($tpl = null)
	{
        if (!Factory::getUser()->authorise('forms.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

		$lists 			= array();
		$condition		= $this->get('condition');
		$allFields 		= $this->get('allFields');

        $lists['allfields'] = HTMLHelper::_('select.genericlist', $allFields, 'component_id[]', array('multiple' => 'multiple', 'size' => 5, 'class' => 'advancedSelect conditionsSelect'), 'ComponentId', 'PropertyValue', $condition->component_id);

		if (version_compare(JVERSION, '4.0', '>='))
		{
			Text::script('JGLOBAL_SELECT_NO_RESULTS_MATCH');
			Text::script('JGLOBAL_SELECT_PRESS_TO_SELECT');

			Factory::getDocument()->getWebAssetManager()->usePreset('choicesjs')->useScript('webcomponent.field-fancy-select');

			$lists['allfields'] = '<joomla-field-fancy-select placeholder="' . Text::_('JGLOBAL_TYPE_OR_SELECT_SOME_OPTIONS',true) . '">' . $lists['allfields'] . '</joomla-field-fancy-select>';
		}

		$actions = array(
			HTMLHelper::_('select.option', 'show', Text::_('RSFP_CONDITION_SHOW')),
			HTMLHelper::_('select.option', 'hide', Text::_('RSFP_CONDITION_HIDE'))
		);
		$lists['action'] = HTMLHelper::_('select.genericlist', $actions, 'action', array('class' => 'form-select d-inline-block w-auto'), 'value', 'text', $condition->action);
		
		$blocks = array(
			HTMLHelper::_('select.option', 1, Text::_('RSFP_CONDITION_BLOCK')),
			HTMLHelper::_('select.option', 0, Text::_('RSFP_CONDITION_FIELD'))
		);
		$lists['block'] = HTMLHelper::_('select.genericlist', $blocks, 'block', array('class' => 'form-select d-inline-block w-auto'), 'value', 'text', $condition->block);
		
		$conditions = array(
			HTMLHelper::_('select.option', 'all', Text::_('RSFP_CONDITION_ALL')),
			HTMLHelper::_('select.option', 'any', Text::_('RSFP_CONDITION_ANY'))
		);
		$lists['condition'] = HTMLHelper::_('select.genericlist', $conditions, 'condition', array('class' => 'form-select d-inline-block w-auto'), 'value', 'text', $condition->condition);
		
		$operators = array(
			HTMLHelper::_('select.option', 'is', Text::_('RSFP_CONDITION_IS')),
			HTMLHelper::_('select.option', 'is_not', Text::_('RSFP_CONDITION_IS_NOT'))
		);

        $this->lang         = $this->get('lang');
        $this->operators    = $operators;
        $this->allFields    = $allFields;
        $this->optionFields = $this->get('optionFields');
        $this->formId       = $this->get('formId');
        $this->close        = Factory::getApplication()->input->getInt('close');
        $this->condition    = $condition;
        $this->lists        = $lists;
		
		parent::display($tpl);
	}
}