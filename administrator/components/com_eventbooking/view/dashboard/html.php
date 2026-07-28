<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Updater\Updater;
use Joomla\Component\Installer\Administrator\Model\UpdateModel;

class EventbookingViewDashboardHtml extends RADViewHtml
{
	/**
	 * This view doesn't have a model associated to it
	 * @var bool
	 */
	public $hasModel = false;

	/**
	 * Latest registrants
	 *
	 * @var array
	 */
	protected $latestRegistrants;

	/**
	 * Upcoming events
	 *
	 * @var array
	 */
	protected $upcomingEvents;

	/**
	 * Statistic data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Update result
	 *
	 * @var array
	 */
	protected $updateResult = [];

	/**
	 * Component config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Display dashboard view
	 */
	public function display()
	{
		$this->latestRegistrants = RADModel::getTempInstance('Registrants', 'EventbookingModel', ['table_prefix' => '#__eb_'])
			->setState('limitstart', 0)
			->setState('limit', 5)
			->setState('filter_order', 'tbl.id')
			->setState('filter_order_Dir', 'DESC')
			->getData();

		$this->upcomingEvents = RADModel::getTempInstance('Events', 'EventbookingModel', ['table_prefix' => '#__eb_'])
			->setState('limitstart', 0)
			->setState('limit', 5)
			->setState('filter_order', 'tbl.event_date')
			->setState('filter_order_Dir', 'ASC')
			->setState('filter_upcoming_events', 1)
			->getData();

		$this->config = EventbookingHelper::getConfig();
		$this->data   = EventbookingModelRegistrants::getStatisticsData();

		// Render sub-menus
		EventbookingHelperHtml::renderSubmenu('dashboard');

		$this->updateResult = $this->checkUpdate();

		if ($this->updateResult['status'] == 2 && $this->config->get('show_update_available_message_in_dashboard', 1))
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('EB_UPDATE_AVAILABLE', 'index.php?option=com_installer&view=update', $this->updateResult['version'])
			);
		}

		EventbookingHelper::displayPHPVersionWarning();

		parent::display();
	}

	/**
	 * Function to create the buttons view.
	 *
	 * @param   string  $link   target url
	 * @param   string  $image  path to image
	 * @param   string  $text   image description
	 */
	protected function quickIconButton($link, $image, $text, $id = null)
	{
		$language = Factory::getLanguage();
		?>
		<div
				style="float:<?php
				echo ($language->isRTL()) ? 'right' : 'left'; ?>;" <?php
		if ($id)
		{
			echo 'id="' . $id . '"';
		} ?>>
			<div class="icon">
				<a href="<?php
				echo $link; ?>">
					<?php
					echo HTMLHelper::_('image', 'media/com_eventbooking/assets/admin/icons/' . $image, $text); ?>
					<span><?php
						echo $text; ?></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Check to see the installed version is up to date or not
	 *
	 * @return int 0 : error, 1 : Up to date, 2 : outof date
	 */
	public function checkUpdate()
	{
		// Get the caching duration.
		$params        = ComponentHelper::getComponent('com_installer')->getParams();
		$cache_timeout = (int) $params->get('cachetimeout', 6);
		$cache_timeout = 3600 * $cache_timeout;

		// Get the minimum stability.
		$minimum_stability = (int) $params->get('minimum_stability', Updater::STABILITY_STABLE);

		if (EventbookingHelper::isJoomla4())
		{
			/* @var UpdateModel $model */
			$model = Factory::getApplication()->bootComponent('com_installer')->getMVCFactory()
				->createModel('Update', 'Administrator', ['ignore_request' => true]);
		}
		else
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');

			/** @var InstallerModelUpdate $model */
			$model = BaseDatabaseModel::getInstance('Update', 'InstallerModel');
		}

		$model->purge();

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('extension_id')
			->from('#__extensions')
			->where('`type` = "package"')
			->where('`element` = "pkg_eventbooking"');
		$db->setQuery($query);
		$eid = (int) $db->loadResult();

		$result['status']  = 0;
		$result['version'] = '';

		if ($eid)
		{
			$ret = Updater::getInstance()->findUpdates($eid, $cache_timeout, $minimum_stability);

			if ($ret)
			{
				$model->setState('list.start', 0);
				$model->setState('list.limit', 0);
				$model->setState('filter.extension_id', $eid);
				$updates          = $model->getItems();
				$result['status'] = 2;

				if (count($updates))
				{
					$result['message'] = Text::sprintf('EB_UPDATE_CHECKING_UPDATE_FOUND', $updates[0]->version);
					$result['version'] = $updates[0]->version;
				}
				else
				{
					$result['message'] = Text::sprintf('EB_UPDATE_CHECKING_UPDATE_FOUND', null);
				}
			}
			else
			{
				$result['status']  = 1;
				$result['message'] = Text::_('EB_UPDATE_CHECKING_UPTODATE');
			}
		}

		return $result;
	}
}
