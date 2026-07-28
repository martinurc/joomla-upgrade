<?php
/**
* @package RSForm! Pro
* @copyright (C) 2022 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Rsjoomla\Plugin\System\Rsformconsole\BackupformsCommand;
use Rsjoomla\Plugin\System\Rsformconsole\PurgesubmissionsCommand;
use Rsjoomla\Plugin\System\Rsformconsole\ExportcsvCommand;

class plgSystemRsformconsole extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents::BEFORE_EXECUTE => 'registerCommand',
		];
	}

	public function registerCommand(ApplicationEvent $event): void
	{
		$serviceId = 'rsform.backup-forms';

		Factory::getContainer()->share(
			$serviceId,
			function (Container $container) {
				// do stuff to create command class and return it
				return new BackupformsCommand();
			},
			true
		);

		Factory::getContainer()->get(WritableLoaderInterface::class)->add(BackupformsCommand::getDefaultName(), $serviceId);

		$serviceId = 'rsform.purge-submissions';

		Factory::getContainer()->share(
			$serviceId,
			function (Container $container) {
				// do stuff to create command class and return it
				return new PurgesubmissionsCommand();
			},
			true
		);

		Factory::getContainer()->get(WritableLoaderInterface::class)->add(PurgesubmissionsCommand::getDefaultName(), $serviceId);

		$serviceId = 'rsform.export-csv';

		Factory::getContainer()->share(
			$serviceId,
			function (Container $container) {
				// do stuff to create command class and return it
				return new ExportcsvCommand();
			},
			true
		);

		Factory::getContainer()->get(WritableLoaderInterface::class)->add(ExportcsvCommand::getDefaultName(), $serviceId);
	}
}