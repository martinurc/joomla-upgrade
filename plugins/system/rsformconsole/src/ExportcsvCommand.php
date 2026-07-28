<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2022 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

namespace Rsjoomla\Plugin\System\Rsformconsole;

use Dompdf\Exception;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

class ExportcsvCommand extends AbstractCommand
{
	protected static $defaultName = 'rsform:export-csv';

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		$symfonyStyle = new SymfonyStyle($input, $output);

		$path           = $input->getOption('path');
		$filename       = $input->getOption('filename');
		$form           = $input->getOption('form');
		$showHeaders    = boolval($input->getOption('headers'));
		$delimiter      = $input->getOption('delimiter');
		$enclosure      = $input->getOption('enclosure');

		$scriptStart = microtime(true);

		try
		{
			$symfonyStyle->title(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_TITLE'));

			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_USING_PATH', $path));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_FORM_ID', $form));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_DELIMITER_SET_TO', $delimiter));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ENCLOSURE_SET_TO', $enclosure));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_INCLUDING_HEADERS', Text::_('PLG_SYSTEM_RSFORMCONSOLE_' . ($showHeaders ? 'TRUE' : 'FALSE'))));
			$symfonyStyle->info(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_PHP_INFO', ini_get('memory_limit'), ini_get('max_execution_time')));

			if (!$form)
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_NO_FORMID_SPECIFIED'));
			}

			if (!is_dir($path))
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_PATH_MUST_BE_A_DIRECTORY', $path));
			}

			if (!is_writable($path))
			{
				throw new \Exception(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_PATH_IS_NOT_WRITABLE', $path));
			}

			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('SubmissionId'))
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('FormId') . ' = ' . $db->q($form))
				->order($db->qn('DateSubmitted') . ' DESC');

			$submissions = $db->setQuery($query)->loadColumn();
			if (!$submissions)
			{
				throw new \Exception(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_NO_SUBMISSIONS_FOUND'));
			}

			$filename = str_replace(
				array('{domain}', '{date}', '{formId}'),
				array(Uri::getInstance()->getHost(), HTMLHelper::_('date', 'now', 'Y-m-d_H-i', false), $form),
				$filename
			);

			$fullpath = $path . '/' . $filename . '.csv';

			$symfonyStyle->createProgressBar(count($submissions));
			$symfonyStyle->progressStart();

			require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';

			$query = $db->getQuery(true)
				->select($db->qn('p.PropertyValue'))
				->from($db->qn('#__rsform_components', 'c'))
				->join('LEFT', $db->qn('#__rsform_properties', 'p') . ' ON (' . $db->qn('c.ComponentId') . '=' . $db->qn('p.ComponentId') . ')')
				->join('left', $db->qn('#__rsform_component_types', 'ct') . ' ON (' . $db->qn('c.ComponentTypeId') . '=' . $db->qn('ct.ComponentTypeId') . ')')
				->where($db->qn('c.FormId') . '=' . $db->q($form))
				->where($db->qn('p.PropertyName') . '=' . $db->q('NAME'))
				->order($db->qn('c.Order') . ' ' . $db->escape('ASC'));
			$query->where($db->qn('ct.ComponentTypeId') . ' NOT IN (' . implode(',', $db->q($this->getSkippedFields())) . ')');

			$fields = $db->setQuery($query)->loadColumn();

			$handle = fopen($fullpath, 'w');
			if ($handle === false)
			{
				throw new \Exception('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_WRITING_TO_FILE');
			}

			$headers = array(
				'SubmissionId' => Text::_('RSFP_SUBMISSIONID'),
				'DateSubmitted' => Text::_('RSFP_DATESUBMITTED'),
				'UserIp' => Text::_('RSFP_USERIP'),
				'Username' => Text::_('RSFP_USERNAME'),
				'UserId' => Text::_('RSFP_USERID'),
				'Lang' => Text::_('RSFP_LANG'),
				'confirmed' => Text::_('RSFP_CONFIRMED')
			);

			list($multipleSeparator, $uploadFields, $multipleFields, $textareaFields, $secret) = \RSFormProHelper::getDirectoryFormProperties($form);

			if ($showHeaders)
			{
				$data = $enclosure . implode($enclosure . $delimiter . $enclosure, str_replace($enclosure, $enclosure . $enclosure, array_merge(array_values($headers), $fields))) . $enclosure . "\n";

				if (!fwrite($handle, $data))
				{
					throw new \Exception('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_WRITING_TO_FILE');
				}
			}

			foreach ($submissions as $submissionId)
			{
				$symfonyStyle->progressAdvance();

				$submission = \RSFormProSubmissionsHelper::getSubmission($submissionId);

				if (!$submission)
				{
					continue;
				}

				$row = array();
				foreach (array_keys($headers) as $header)
				{
					$row[] = $submission->{$header} ?? '';
				}

				foreach ($fields as $field)
				{
					$value = $submission->values[$field] ?? '';

					if (in_array($field, $multipleFields))
					{
						$value = str_replace("\n", $multipleSeparator, $value);
					}

					$row[] = $this->fixValue($value);
				}

				$data = $enclosure . implode($enclosure . $delimiter . $enclosure, str_replace($enclosure, $enclosure . $enclosure, $row)) . $enclosure . "\n";

				if (!fwrite($handle, $data))
				{
					throw new \Exception('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_ERROR_WRITING_TO_FILE');
				}
			}

			$symfonyStyle->progressFinish();
		}
		catch (\Exception $e)
		{
			$symfonyStyle->error($e->getMessage());
			return $e->getCode();
		}

		$time = number_format(microtime(true) - $scriptStart, 2, '.', '');

		$symfonyStyle->writeln(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_FINISHED_IN_SECONDS', $time));
		$symfonyStyle->success(Text::sprintf('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_FILE_STORED_IN', $fullpath));

		return 0;
	}

	protected function configure(): void
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		$this->addOption('path', 'p', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_OPTION_PATH'), JPATH_CLI);
		$this->addOption('filename', '', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_OPTION_FILENAME'), \RSFormProHelper::getConfig('export.mask'));
		$this->addOption('headers', '', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_OPTION_HEADERS'), 1);
		$this->addOption('delimiter', 'd', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_OPTION_DELIMITER'), ',');
		$this->addOption('enclosure', 'e', InputArgument::OPTIONAL, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_OPTION_ENCLOSURE'), '"');
		$this->addOption('form', 'f', InputOption::VALUE_REQUIRED, Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_OPTION_FORM'));

		$this->setDescription(Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_DESCRIPTION'));
		$help_1 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_HELP_1');
		$help_2 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_HELP_2');
		$help_3 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_HELP_3');
		$help_4 = Text::_('PLG_SYSTEM_RSFORMCONSOLE_COMMAND_EXPORT_CSV_HELP_4');
		$this->setHelp(
			<<<EOF
RSForm! Pro
###########

{$help_1}
            
        php joomla.php rsform:export-csv --form=1

{$help_2}

        php joomla.php rsform:export-csv --form=1 --headers=0

{$help_3}

        php joomla.php rsform:export-csv --form=1 --path=/var/www --filename=my_export

{$help_4}

        php joomla.php rsform:export-csv --form=1 --delimiter=; --enclosure=

EOF
		);
	}

	protected function getSkippedFields()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		$skippedFields = array(RSFORM_FIELD_BUTTON, RSFORM_FIELD_CAPTCHA, RSFORM_FIELD_FREETEXT, RSFORM_FIELD_SUBMITBUTTON);
		$skippedFields = array_merge($skippedFields, \RSFormProHelper::$captchaFields);

		Factory::getApplication()->triggerEvent('onRsformBackendGetSkippedFields', array(&$skippedFields));

		return $skippedFields;
	}

	protected function fixValue($string)
	{
		if (is_string($string) && strlen($string) && in_array(substr($string, 0, 1), array('=', '+', '-', '@')))
		{
			$string = ' ' . $string;
		}

		return $string;
	}
}