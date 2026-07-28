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
use Joomla\CMS\Uri\Uri;

class EventbookingViewLocationsHtml extends RADViewHtml
{
	/**
	 * The locations data
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var Pagination
	 */
	protected $pagination;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		if (!Factory::getUser()->authorise('eventbooking.addlocation', 'com_eventbooking'))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('EB_NO_PERMISSION'), 'error');
			$app->redirect(Uri::root(), 403);

			return;
		}

		$this->findAndSetActiveMenuItem();

		$model            = $this->getModel();
		$this->items      = $model->getData();
		$this->pagination = $model->getPagination();

		$this->setLayout('default');
	}
}
