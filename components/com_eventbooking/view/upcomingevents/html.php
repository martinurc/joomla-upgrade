<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class EventbookingViewUpcomingeventsHtml extends EventbookingViewCategoryHtml
{
	/**
	 * Override this property from parent class to allow this class to have right menu item parameters
	 *
	 * @var array
	 */
	protected $paramsViews = [];

	/**
	 * Method to prepare document before it is rendered
	 *
	 * @return void
	 */
	protected function prepareDocument()
	{
		// Correct active menu item in case the URL is typed directly on browser
		$this->findAndSetActiveMenuItem();

		// Page title
		if (!$this->params->get('page_title'))
		{
			$pageTitle = Text::_('EB_UPCOMING_EVENTS_PAGE_TITLE');

			if ($this->category)
			{
				$pageTitle = str_replace('[CATEGORY_NAME]', $this->category->name, $pageTitle);
			}

			$this->params->set('page_title', $pageTitle);
		}

		// Page heading
		$this->params->def('page_heading', Text::_('EB_UPCOMING_EVENTS'));

		// Meta keywords and description
		$this->params->def('menu-meta_keywords', $this->category ? $this->category->meta_keywords : '');
		$this->params->def('menu-meta_description', $this->category ? $this->category->meta_description : '');

		// Load required assets for the view
		$this->loadAssets();

		// Set page meta data
		$this->setDocumentMetadata();

		// Add Feed links to document
		if ($this->config->get('show_feed_link', 1))
		{
			$this->addFeedLinks();
		}

		// Intro text
		if (EventbookingHelper::isValidMessage($this->params->get('intro_text')))
		{
			$this->introText = HTMLHelper::_('content.prepare', $this->params->get('intro_text'));
		}

		// Add filter variables to pagination links if configured
		if ($this->config->get('show_search_bar', 0))
		{
			$this->setPaginationAdditionalParams();
		}
	}
}
