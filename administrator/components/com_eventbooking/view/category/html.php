<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class EventbookingViewCategoryHtml extends RADViewItem
{
	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Prepare view data
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		Factory::getApplication()->getDocument()->addScript(Uri::root(true) . '/media/com_eventbooking/assets/admin/js/colorpicker/jscolor.js');

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', Text::_('Default Layout'));
		$options[] = HTMLHelper::_('select.option', 'table', Text::_('Table Layout'));
		$options[] = HTMLHelper::_('select.option', 'timeline', Text::_('Timeline Layout'));
		$options[] = HTMLHelper::_('select.option', 'columns', Text::_('Columns Layout'));
		$options[] = HTMLHelper::_('select.option', 'grid', Text::_('Grid Layout'));
		$options[] = HTMLHelper::_('select.option', 'list', Text::_('List Layout'));

		$this->lists['layout'] = HTMLHelper::_('select.genericlist', $options, 'layout', 'class="form-select"', 'value', 'text', $this->item->layout);

		$this->lists['submit_event_access'] = HTMLHelper::_(
			'access.level',
			'submit_event_access',
			$this->item->submit_event_access,
			'class="form-select"',
			false
		);
		$this->lists['parent']              = EventbookingHelperHtml::buildCategoryDropdown($this->item->parent, 'parent', 'class="form-select"');

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', Text::_('EB_ALL_PAYMENT_METHODS'), 'id', 'title');

		$db    = $this->model->getDbo();
		$query = $db->getQuery(true)
			->select('id, title')
			->from('#__eb_payment_plugins')
			->where('published = 1');
		$db->setQuery($query);
		$this->lists['payment_methods'] = HTMLHelper::_(
			'select.genericlist',
			array_merge($options, $db->loadObjectList()),
			'payment_methods[]',
			'class="form-select advancedSelect" multiple="multiple"',
			'id',
			'title',
			explode(',', $this->item->payment_methods)
		);

		$this->config = EventbookingHelper::getConfig();
	}
}
