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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class PurgesubmissionsCommand extends AbstractCommand
{
	protected static $defaultName = 'rsform:purge-submissions';

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		$symfonyStyle = new SymfonyStyle($input, $output);
		$scriptStart = microtime(true);

		try
		{
			$symfonyStyle->title(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_TITLE'));

			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_PHP_INFO', ini_get('memory_limit'), ini_get('max_execution_time')));

			$db = Factory::getDbo();

			$query = $db->getQuery(true)
				->select($db->qn('FormId'))
				->select($db->qn('DeleteSubmissionsAfter'))
				->from($db->qn('#__rsform_forms'))
				->where($db->qn('DeleteSubmissionsAfter') . ' > ' . $db->q(0));

			if ($forms = $db->setQuery($query)->loadObjectList())
			{
				$symfonyStyle->writeln('Found ' . count($forms) . ' forms.');
				foreach ($forms as $form)
				{
					$symfonyStyle->write(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_DELETING_SUBMISSIONS_OLDER_THAN_FOR_FORM', $form->DeleteSubmissionsAfter, $form->FormId));

					$date = Factory::getDate()->modify("-{$form->DeleteSubmissionsAfter} days")->toSql();
					// Find all Submission IDs that need to get removed
					$query->clear()
						->select($db->qn('SubmissionId'))
						->from($db->qn('#__rsform_submissions'))
						->where($db->qn('FormId') . ' = ' . $db->q($form->FormId))
						->where($db->qn('DateSubmitted') . ' < ' . $db->q($date));

					if ($submissions = $db->setQuery($query)->loadColumn())
					{
						require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';

						\RSFormProSubmissionsHelper::deleteSubmissions($submissions);

						$symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_REMOVED_SUBMISSIONS', count($submissions)));
					}
					else
					{
						$symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_NO_SUBMISSIONS_MATCH'));
					}
				}
			}
			else
			{
				$symfonyStyle->writeln(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_NO_FORMS_HAVE_BEEN_CONFIGURED'));
			}
		}
		catch (\Exception $e)
		{
			$symfonyStyle->error($e->getMessage());
			return $e->getCode();
		}

		$time = number_format(microtime(true) - $scriptStart, 2, '.', '');

		$symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_FINISHED_IN_SECONDS', $time));

		return 0;
	}

	protected function configure(): void
	{
		$this->setDescription(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_DESCRIPTION'));
		$help = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_PURGE_SUBMISSIONS_HELP');
		$this->setHelp(
			<<<EOF
RSForm! Pro
###########

{$help}

        php joomla.php rsform:purge-submissions

EOF
		);
	}
}