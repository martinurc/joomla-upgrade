<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

class EventbookingViewPluginHtml extends RADViewItem
{
	/**
	 * The form object
	 *
	 * @var Form
	 */
	protected $form;

	/**
	 * Prepare view data
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$registry     = new Registry($this->item->params ?? '{}');
		$data         = new stdClass();
		$data->params = $registry->toArray();
		$form         = Form::getInstance(
			'pmform',
			JPATH_ROOT . '/components/com_eventbooking/payments/' . $this->item->name . '.xml',
			[],
			false,
			'//config'
		);
		$form->bind($data);
		$this->form = $form;
	}

	/**
	 * Build custom toolbar
	 *
	 * @see RADViewItem::addToolbar()
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('EB_PLUGIN') . ': <small><small>[edit]</small></small>');
		ToolbarHelper::apply('apply', 'JTOOLBAR_APPLY');
		ToolbarHelper::save('save');
		ToolbarHelper::cancel('cancel');
	}
}
