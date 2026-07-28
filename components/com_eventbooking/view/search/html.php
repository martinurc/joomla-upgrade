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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;

class EventbookingViewSearchHtml extends RADViewHtml
{
	/**
	 * Events search result
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Pagination
	 *
	 * @var Pagination
	 */
	protected $pagination;

	/**
	 * The current user view levels
	 *
	 * @var array
	 */
	protected $viewLevels;

	/**
	 * The null date string
	 *
	 * @var string
	 */
	protected $nullDate;

	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Bootstrap helper
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * ID of active category, needed because we are using shared layout
	 *
	 * @var int
	 */
	protected $categoryId = 0;

	/**
	 * Active category, it is needed because we are using shared layout with category
	 *
	 * @var stdClass
	 */
	protected $category = null;

	/**
	 * Prepare view data
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$app      = Factory::getApplication();
		$document = $app->getDocument();
		$document->setTitle(Text::_('EB_SEARCH_RESULT'));

		$config = EventbookingHelper::getConfig();

		$active = $app->getMenu()->getActive();
		$layout = $this->getLayout();

		// Handle layout
		if ($active && isset($active->query['view']) && $active->query['view'] == $this->getName())
		{
			// This is direct menu link to category view, so use the layout from menu item setup
		}
		elseif ($this->input->getInt('hmvc_call') && $this->input->getCmd('layout'))
		{
			// Use layout from the HMVC call, in this case, it's from EB view module
		}
		elseif (in_array($layout, ['default', 'table', 'columns', 'timeline', 'grid', 'list']))
		{
			// One of the supported layout
		}
		else
		{
			// Use default layout
			$this->setLayout('default');
		}

		if ($config->multiple_booking)
		{
			if ($this->deviceType == 'mobile')
			{
				EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '100%', '450px', 'false', 'false');
			}
			else
			{
				EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '800px', 'false', 'false', 'false', 'false');
			}
		}

		if ($config->show_list_of_registrants)
		{
			EventbookingHelperModal::iframeModal('a.eb-colorbox-register-lists', 'eb-registrant-lists-modal');
		}

		if ($config->show_location_in_category_view || $this->getLayout() == 'timeline')
		{
			EventbookingHelperModal::iframeModal('a.eb-colorbox-map', 'eb-map-modal');
		}

		$this->viewLevels      = Factory::getUser()->getAuthorisedViewLevels();
		$this->items           = $this->model->getData();
		$this->pagination      = $this->model->getPagination();
		$this->config          = $config;
		$this->nullDate        = Factory::getDbo()->getNullDate();
		$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();

		// Prepare display data
		EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [$this->items, $this->categoryId, $this->config, $this->Itemid]
		);

		// Add cancelRegistration method
		$document->addScriptDeclaration(
			'
			function cancelRegistration(registrantId)
			{
				var form = document.adminForm ;
		
				if (confirm("' . Text::_('EB_CANCEL_REGISTRATION_CONFIRM') . '"))
				{
					form.task.value = "registrant.cancel" ;
					form.id.value = registrantId ;
					form.submit() ;
				}
			}
		'
		);
	}
}
