<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2022 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

namespace Rsjoomla\Plugin\System\Rsformconsole;

use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class BackupformsCommand extends AbstractCommand
{
	protected static $defaultName = 'rsform:backup-forms';

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		$symfonyStyle = new SymfonyStyle($input, $output);

		$path           = $input->getOption('path');
		$submissions    = boolval($input->getOption('submissions'));
		$forms          = $input->getOption('form');

		$scriptStart = microtime(true);

		if (!$forms)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('FormId'))
				->from($db->qn('#__rsform_forms'));

			$forms = $db->setQuery($query)->loadColumn();
		}

		try
		{
			$symfonyStyle->title(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_TITLE'));

			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_USING_PATH', $path));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_FORM_IDS', implode(',', $forms)));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_INCLUDING_SUBMISSIONS', Text::_('PLG_SYSTEM_RSFORMCONSOLE_' . ($submissions ? 'TRUE' : 'FALSE'))));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_PHP_INFO', ini_get('memory_limit'), ini_get('max_execution_time')));

			if (!is_dir($path))
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_ERROR_PATH_MUST_BE_A_DIRECTORY', $path));
			}

			if (!is_writable($path))
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_ERROR_PATH_IS_NOT_WRITABLE', $path));
			}

			require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/backup/backup.php';

			$backup = new \RSFormProBackup(['forms' => $forms, 'submissions' => $submissions, 'tmp' => $path, 'key' => Factory::getDate()->format('Y_m_d_H_i_s_v')]);

			$symfonyStyle->createProgressBar();
			$symfonyStyle->progressStart();
			$backup->storeMetaData();
			$symfonyStyle->progressAdvance();
			$backup->storeForms($symfonyStyle);

			if ($submissions)
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/backup/submissions.php';

				$limit = 100;

				foreach ($forms as $formId)
				{
					$done = false;
					$header = 0;
					$start = 0;
					while (!$done)
					{
						$backupSubmission = new \RSFormProBackupSubmissions(array(
							'path'			=> $backup->getPath(),
							'form' 			=> $formId,
							'start' 		=> $start,
							'limit'         => $limit,
							'header'		=> $header
						));

						$result = $backupSubmission->store();

						$done = $result->done;
						$header = $result->header;
						$start += $limit;

						$symfonyStyle->progressAdvance();
					}
				}
			}

			$symfonyStyle->progressFinish();

			$archive = new \RSFormProTar($backup->getPath());
			$archive->addFooter();

			// Need this otherwise getSize() is 0
			clearstatcache();

			// Only one chunk
			$archive->setChunkSize($archive->getSize());

			$symfonyStyle->write(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_COMPRESSING_BACKUP'));
			$archive->compress();
			$symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFORMCONSOLE_DONE'));

			$symfonyStyle->write(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_CLEANING_TEMPORARY_FILES'));
			$backup->clean();
			$symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFORMCONSOLE_DONE'));

			// Deny access
			file_put_contents(dirname($archive->getGzipPath()) . '/.htaccess', '# Apache 2.4+
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

# Apache 2.0-2.2
<IfModule !mod_authz_core.c>
  Deny from all
</IfModule>');
		}
		catch (\Exception $e)
		{
			$symfonyStyle->error($e->getMessage());
			return $e->getCode();
		}

		$time = number_format(microtime(true) - $scriptStart, 2, '.', '');

		$symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_FINISHED_IN_SECONDS', $time));
		$symfonyStyle->success(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_BACKUP_STORED_IN_PATH', $archive->getGzipPath()));

		return 0;
	}

	protected function configure(): void
	{
		$this->addOption('path', 'p', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_OPTION_PATH'), JPATH_CLI);
		$this->addOption('submissions', 's', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_OPTION_SUBMISSIONS'), 1);
		$this->addOption('form', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_OPTION_FORM'), array());

		$this->setDescription(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_DESCRIPTION'));
		$help_1 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_HELP_1');
		$help_2 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_HELP_2');
		$help_3 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_BACKUP_FORMS_HELP_3');
		$this->setHelp(
			<<<EOF
RSForm! Pro
###########

{$help_1}
            
        php joomla.php rsform:backup-forms

{$help_2}

        php joomla.php rsform:backup-forms --submissions=0

{$help_3}

        php joomla.php rsform:backup-forms --form=1 --form=5 --form=23

EOF
		);
	}
}