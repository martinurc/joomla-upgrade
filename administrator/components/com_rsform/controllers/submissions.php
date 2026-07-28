<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

class RsformControllerSubmissions extends RsformController
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->registerTask('apply', 			'save');
		$this->registerTask('exportCSV', 		'export');
		$this->registerTask('exportODS', 		'export');
		$this->registerTask('exportExcel', 		'export');
		$this->registerTask('exportExcelXML', 	'export');
		$this->registerTask('exportXML', 		'export');
	}

	public function manage()
	{
		$app	= Factory::getApplication();
		$model	= $this->getModel('submissions');
		$formId = $model->getFormId();
		
		// if the form is changed we need to reset the limitstart
		$app->setUserState('com_rsform.submissions.limitstart', 0);
		
		$app->redirect('index.php?option=com_rsform&view=submissions'.($formId ? '&formId='.$formId : ''));
	}
	
	public function back() {
		$app	= Factory::getApplication();
		$formId = $app->input->getInt('formId');
		$app->redirect('index.php?option=com_rsform&view=submissions&formId='.$formId);
	}
	
	public function edit()
	{
		$model = $this->getModel('submissions');
		$cid   = $model->getSubmissionId();
        Factory::getApplication()->redirect('index.php?option=com_rsform&view=submissions&layout=edit&cid='.$cid);
	}
	
	public function columns()
	{
		$app 	        = Factory::getApplication();
		$formId         = $app->input->getInt('formId');
        $staticcolumns  = $app->input->get('staticcolumns', array(), 'raw');
        $columns        = $app->input->get('columns', array(), 'raw');

        /* @var $model RsformModelSubmissions */
        $model = $this->getModel('submissions');
        $model->saveColumns($formId, $staticcolumns, $columns);

		$this->setRedirect('index.php?option=com_rsform&view=submissions&formId=' . $formId);
	}
	
	public function save()
	{
		// Get the model
		$model = $this->getModel('submissions');
		
		// Save
		$model->save();
		
		$task = $this->getTask();
		switch ($task)
		{
			case 'apply':
				$cid  = $model->getSubmissionId();
				$link = 'index.php?option=com_rsform&view=submissions&layout=edit&cid='.$cid;
			break;
		
			case 'save':
				$link = 'index.php?option=com_rsform&view=submissions';
			break;
		}
		
		$this->setRedirect($link, Text::_('RSFP_SUBMISSION_SAVED'));
	}
	
	public function resend()
	{
		$app 	= Factory::getApplication();
		$formId = $app->input->getInt('formId');
        $cid	= $app->input->post->get('cid', array(), 'array');
		$cid 	= array_map('intval', $cid);
		
		foreach ($cid as $SubmissionId)
		{
            RSFormProHelper::sendSubmissionEmails($SubmissionId);
        }
		
		$this->setRedirect('index.php?option=com_rsform&view=submissions&formId='.$formId, Text::_('RSFP_SUBMISSION_MAILS_RESENT'));
	}

	public function confirm()
	{
		$app 	= Factory::getApplication();
		$formId = $app->input->getInt('formId');
		$cid	= $app->input->post->get('cid', array(), 'array');
		$cid 	= array_map('intval', $cid);
		$model 	= $this->getModel('submissions');

		$model->confirm($formId, $cid);

		$this->setRedirect('index.php?option=com_rsform&view=submissions&formId=' . $formId, Text::_('COM_RSFORM_SUBMISSIONS_CONFIRMED'));
	}
	
	public function cancel()
	{
		Factory::getApplication()->redirect('index.php?option=com_rsform');
	}
	
	public function cancelForm()
	{
		$app 	= Factory::getApplication();
		$formId = $app->input->getInt('formId');
		$app->redirect('index.php?option=com_rsform&view=forms&layout=edit&formId='.$formId);
	}
	
	public function clear()
	{
	    $this->checkToken('get');

        if (!Factory::getUser()->authorise('submissions.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';

		$app    = Factory::getApplication();
		$formId = $app->input->getInt('formId');
		$total  = RSFormProSubmissionsHelper::deleteAllSubmissions($formId);

		$app->triggerEvent('onRsformSubmissionsClear', array('formId' => $formId));
		
		$this->setRedirect('index.php?option=com_rsform&view=forms', Text::sprintf('RSFP_SUBMISSIONS_CLEARED', $total));
	}
	
	public function delete()
	{
        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';

		$app 	= Factory::getApplication();
		$formId = $app->input->getInt('formId');
        $cid	= $app->input->post->get('cid', array(), 'array');
		$cid    = array_map('intval', $cid);

		RSFormProSubmissionsHelper::deleteSubmissions($cid);

		$app->triggerEvent('onRsformSubmissionsDelete', array('formId' => $formId, 'ids' => $cid));
		
		$app->redirect('index.php?option=com_rsform&view=submissions&formId='.$formId);
	}
	
	public function export()
	{
		$app 	  = Factory::getApplication();
		$tmp_path = Factory::getApplication()->get('tmp_path');
		if (!is_writable($tmp_path))
		{
			$app->enqueueMessage(Text::sprintf('RSFP_EXPORT_ERROR_MSG', $tmp_path), 'warning');
			$app->redirect('index.php?option=com_rsform&view=submissions');
		}

		$app->input->set('view', 'submissions');
		$app->input->set('layout', 'export');

		parent::display();
	}

	public function importCsv()
    {
        $app        = Factory::getApplication();
        $tmp_path   = $app->get('tmp_path');
		$secret     = $app->get('secret');
        $files      = $app->input->files->get('import');
        $options    = $app->input->get('import', array(), 'array');
        $session    = Factory::getSession();

        $session->set('com_rsform.import.options', $options);

        try
        {
            if (!is_writable($tmp_path))
            {
                throw new Exception(Text::sprintf('COM_RSFORM_IMPORT_ERROR_MSG', $tmp_path));
            }

            if (!isset($files['file']))
            {
	            throw new Exception(Text::_('RSFP_FILE_HAS_NOT_BEEN_UPLOADED_DUE_TO_AN_UNKNOWN_ERROR'));
            }

            $file = $files['file'];

            if ($file['error'] != UPLOAD_ERR_OK)
            {
                // Parse the error message
                switch ($file['error'])
                {
                    default:
                        // File has not been uploaded correctly
                        throw new Exception(Text::_('RSFP_FILE_HAS_NOT_BEEN_UPLOADED_DUE_TO_AN_UNKNOWN_ERROR'));
                        break;

                    case UPLOAD_ERR_INI_SIZE:
                        throw new Exception(Text::_('RSFP_UPLOAD_ERR_INI_SIZE'));
                        break;

                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception(Text::_('RSFP_UPLOAD_ERR_FORM_SIZE'));
                        break;

                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception(Text::_('RSFP_UPLOAD_ERR_PARTIAL'));
                        break;

                    case UPLOAD_ERR_NO_TMP_DIR:
                        throw new Exception(Text::_('RSFP_UPLOAD_ERR_NO_TMP_DIR'));
                        break;

                    case UPLOAD_ERR_CANT_WRITE:
                        throw new Exception(Text::_('RSFP_UPLOAD_ERR_CANT_WRITE'));
                        break;

                    case UPLOAD_ERR_EXTENSION:
                        throw new Exception(Text::_('RSFP_UPLOAD_ERR_EXTENSION'));
                        break;

                    case UPLOAD_ERR_NO_FILE:
                        throw new Exception(Text::_('COM_RSFORM_PLEASE_UPLOAD_A_FILE'));
                        break;
                }
            }

            $extParts   = explode('.', $file['name']);
            $ext 	    = strtolower(end($extParts));

            if ($ext !== 'csv')
            {
                throw new Exception(Text::_('COM_RSFORM_PLEASE_UPLOAD_ONLY_CSV_FILES'));
            }

            if (!File::upload($file['tmp_name'], $tmp_path . '/' . md5($secret)))
            {
                throw new Exception(Text::_('COM_RSFORM_COULD_NOT_MOVE_FILE'));
            }
        }
        catch (Exception $e)
        {
            $app->enqueueMessage($e->getMessage(), 'error');
            $app->redirect('index.php?option=com_rsform&view=submissions');
        }

		$app->input->set('view', 'submissions');
		$app->input->set('layout', 'import');

		parent::display();
    }

    public function importProcess()
    {
        $session    = Factory::getSession();
        $db         = Factory::getDbo();
        $app        = Factory::getApplication();
        $model      = $this->getModel('submissions');
        $file       = $app->get('tmp_path') . '/' . md5($app->get('secret'));
        $options    = $session->get('com_rsform.import.options', array());

        $defaultLang    = Factory::getLanguage()->getDefault();
        $defaultDate    = Factory::getDate()->toSql();
        $skipHeaders    = !empty($options['skipHeaders']);
        $delimiter      = empty($options['delimiter']) ? ',' : $options['delimiter'];
        $enclosure      = empty($options['enclosure']) ? '"' : $options['enclosure'];
        $headers        = empty($options['headers']) ? array() : $options['headers'];
        $staticHeaders  = array_keys($model->getStaticHeaders());

        $start  = $app->input->getInt('importStart');
        $limit  = $app->input->getInt('importLimit', 500);
        $formId = $app->input->getInt('formId');

	    if (version_compare(PHP_VERSION, '8.1', '<'))
	    {
		    ini_set('auto_detect_line_endings', true);
	    }
        setlocale(LC_ALL, 'en_US.UTF-8');

        if (!file_exists($file) || !is_readable($file))
		{
			echo 'ERROR';
			$app->close();
		}

        $h = fopen($file, 'r');

        if (is_resource($h))
        {
            if ($start)
            {
                fseek($h, $start);
            }
            for ($i = 0; $i < $limit; $i++)
            {
                $data = fgetcsv($h, 0, $delimiter, $enclosure, '');

                if ($data !== false)
                {
                    if ($skipHeaders && !$start && $i == 0)
                    {
                        continue;
                    }

                    $tmpHeaders = $headers;
                    $submission = new stdClass();
                    $submission->FormId         = $formId;
                    $submission->DateSubmitted  = $defaultDate;
                    $submission->Lang           = $defaultLang;
                    $submission->UserId			= 0;
                    $submission->UserIp			= '';
                    $submission->confirmed		= 1;
                    $submission->ConfirmedDate  = null;
                    $submission->ConfirmedIp    = '';
                    $submission->SubmissionHash	= ApplicationHelper::getHash(UserHelper::genRandomPassword());
                    foreach ($staticHeaders as $staticHeader)
                    {
                        if (($position = array_search($staticHeader, $tmpHeaders)) !== false)
                        {
                            $submission->{$staticHeader} = isset($data[$position]) ? $data[$position] : '';

                            unset($tmpHeaders[$position]);
                            unset($data[$position]);

							if ($staticHeader === 'DateSubmitted' || $staticHeader === 'ConfirmedDate')
							{
								try
								{
									$tmpDate = Factory::getDate($submission->{$staticHeader}, $app->get('offset'))->toSql();
									$submission->{$staticHeader} = $tmpDate;
								}
								catch (Exception $e)
								{
									// Reverts automatically because getDate() throws an exception
									$submission->{$staticHeader} = Factory::getDate()->toSql();
								}
							}
							elseif ($staticHeader === 'confirmed')
							{
								if ($submission->confirmed === Text::_('RSFP_YES'))
								{
									$submission->confirmed = 1;
								}
								elseif ($submission->confirmed === Text::_('RSFP_NO'))
								{
									$submission->confirmed = 0;
								}
								else
								{
									$submission->confirmed = (int) $submission->confirmed;
								}
							}
                        }
                    }

                    // We've mapped a Submission ID, this means we should update values based on this, if it exists
                    $exists = false;
                    if (!empty($submission->SubmissionId))
                    {
                        $query = $db->getQuery(true)
                            ->select($db->qn('SubmissionId'))
                            ->select($db->qn('FormId'))
                            ->from($db->qn('#__rsform_submissions'))
                            ->where($db->qn('SubmissionId') . ' = ' . $db->q($submission->SubmissionId));
                        $exists = $db->setQuery($query)->loadObject();
                    }

                    if ($exists)
                    {
                    	// Same form, update
                    	if ($exists->FormId == $formId)
						{
							$db->updateObject('#__rsform_submissions', $submission, array('SubmissionId'));
						}
						else
						{
							// Different form, submission ID can't be reused to avoid modifying the wrong submission, unset $submission->SubmissionId and insert new row
							unset($submission->SubmissionId);
							$db->insertObject('#__rsform_submissions', $submission, 'SubmissionId');
						}
                    }
                    else
                    {
                        $db->insertObject('#__rsform_submissions', $submission, 'SubmissionId');
                    }

                    foreach ($tmpHeaders as $position => $header)
                    {
                        $submissionValue = new stdClass();
                        $submissionValue->FormId        = $formId;
                        $submissionValue->SubmissionId  = $submission->SubmissionId;
                        $submissionValue->FieldName     = $header;
                        $submissionValue->FieldValue    = isset($data[$position]) ? $data[$position] : '';

                        if ($exists)
                        {
                            $query = $db->getQuery(true)
                                ->delete($db->qn('#__rsform_submission_values'))
                                ->where($db->qn('FieldName') . ' = ' . $db->q($submissionValue->FieldName))
                                ->where($db->qn('SubmissionId') . ' = ' . $db->q($submissionValue->SubmissionId));
                            $db->setQuery($query)->execute();
                        }

                        $db->insertObject('#__rsform_submission_values', $submissionValue);
                    }
                }
            }

            $offset = ftell($h);
            $end = feof($h) || $offset === false;

            if ($end)
            {
				$app->triggerEvent('onRsformSubmissionsImport', array('formId' => $formId));
                echo 'END';
            }
            else
            {
                echo $offset;
            }

            fclose($h);

            if ($end && file_exists($file))
			{
				unlink($file);
			}
        }

        $app->close();
    }

    protected function fixValue($string)
	{
		if (is_string($string) && strlen($string) && in_array(substr($string, 0, 1), array('=', '+', '-', '@')))
		{
			$string = ' ' . $string;
		}

		return $string;
	}
	
	public function exportProcess()
	{
		$mainframe 	= Factory::getApplication();
		$session 	= Factory::getSession();
		$model 		= $this->getModel('submissions');

		// Get post
		$post = $session->get('com_rsform.export.data', serialize(array()));
		$post = unserialize($post);
		
		// Tmp path
		$tmp_path = Factory::getApplication()->get('tmp_path');
		$file = $tmp_path.'/'.$post['ExportFile'];
		
		// Type
		$type = strtolower($post['ExportType']);
		
		// Use headers
		$use_headers = !empty($post['ExportHeaders']);
		
		// Headers and ordering
		$staticHeaders 	= $post['ExportSubmission'];
		$headers 		= $post['ExportComponent'];
		$order 			= $post['ExportOrder'];
		
		// Remove headers that we're not going to export
		foreach ($order as $name => $id)
		{
			if (!isset($staticHeaders[$name]) && !isset($headers[$name]))
			{
				unset($order[$name]);
			}
		}
		
		// Adjust order array
		$order = array_flip($order);
		ksort($order);

		$model->exportType = $type;
		$model->stripLines = !empty($post['StripLines']);
		$model->multipleSeparator = $post['ExportMultipleSeparator'];

		switch ($post['ExportRows'])
		{
			// All rows
			case '0':
				$model->export = true;
				$model->rows = null;
				break;

			// Filtered rows
			case '-1':
				$model->export = false;
				$model->rows = null;
				break;

			// Selected rows
			default:
				$model->export = true;
				$model->rows = explode(',', $post['ExportRows']);
				break;
		}

		// Limit
		$start = $mainframe->input->getInt('exportStart');
		$limit = $mainframe->input->getInt('exportLimit', RSFormProHelper::getConfig('export.limit'));

		// Need to call this so the state gets populated
		$model->getStart();

		$model->setState('list.start', $start);
		$model->setState('list.limit', $limit);

		$mainframe->setUserState('com_rsform.submissions.limitstart', $start);
		$mainframe->setUserState('com_rsform.submissions.limit', $limit);

		$done = $model->getTotal() <= $model->getStart() + $limit;

		$submissions = $model->getSubmissions();
		
		// CSV Options
		if ($type == 'csv')
		{
			$delimiter = str_replace(array('\t', '\n', '\r'), array("\t","\n","\r"), $post['ExportDelimiter']);
			$enclosure = str_replace(array('\t', '\n', '\r'), array("\t","\n","\r"), $post['ExportFieldEnclosure']);
			
			// Create and open file for writing if this is the first call
			// If not, just append to the file
			// Using fopen() because File::write() lacks such options
			$handle = fopen($file, $start == 0 ? 'w' : 'a');
			
			if ($start == 0 && $use_headers)
			{
				fwrite($handle, $enclosure.implode($enclosure.$delimiter.$enclosure,$order).$enclosure);
				fwrite($handle, "\n");
			}

			$lastHeader = end($order);

			foreach ($submissions as $submissionId => $submission)
			{
				foreach ($order as $orderId => $header)
				{
					fwrite($handle, $enclosure.(isset($submission['SubmissionValues'][$header]) ? str_replace($enclosure, $enclosure.$enclosure, $this->fixValue($submission['SubmissionValues'][$header]['Value'])) : (isset($submission[$header]) ? $this->fixValue($submission[$header]) : '')).$enclosure.($header != $lastHeader ? $delimiter : ""));
				}
				fwrite($handle, "\n");
			}

			if ($done)
			{
				// Adjust pagination
				$mainframe->setUserState('com_rsform.submissions.limitstart', 0);
				$mainframe->setUserState('com_rsform.submissions.limit', Factory::getApplication()->get('list_limit'));
				echo 'END';
			}

			fclose($handle);
		}
		// Excel XML Options
		elseif ($type == 'excelxml')
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/excelxml.php';
			
			$xls = new RSFormProXLS($model->getFormTitle());
			$xls->open($file, $start == 0 ? 'w' : 'a');
			
			if ($start == 0 && $use_headers)
				$xls->writeHeaders($order);

			$array = array();
			foreach ($submissions as $submissionId => $submission)
			{
				$item = array();
				foreach ($order as $orderId => $header)
				{
					if (isset($submission['SubmissionValues'][$header]))
						$item[$header] = $this->fixValue($submission['SubmissionValues'][$header]['Value']);
					elseif (isset($submission[$header]))
						$item[$header] = $this->fixValue($submission[$header]);
					else
						$item[$header] = '';
				}

				$array[] = $item;
			}
			$xls->write($array);

			if ($done)
			{
				$xls->writeFooter();
				// Adjust pagination
				$mainframe->setUserState('com_rsform.submissions.limitstart', 0);
				$mainframe->setUserState('com_rsform.submissions.limit', Factory::getApplication()->get('list_limit'));
				echo 'END';
			}

			$xls->close();
		}
		// Excel Options
		elseif ($type == 'excel')
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/excel.php';
			
			$xls = new RSFormProXLSX();
			$xls->name 			= $model->getFormTitle();
			$xls->useHeaders 	= $use_headers;
			
			if ($start == 0) {
				$xls->open($file, 'w', $start, $model->getTotal(), count($order) - 1);
			} else {
				$xls->open($file, 'a', $start);
			}
			
			if ($start == 0 && $use_headers) {
				$xls->writeHeaders($order);
			}

			$array = array();
			foreach ($submissions as $submissionId => $submission)
			{
				$item = array();
				foreach ($order as $orderId => $header)
				{
					if (isset($submission['SubmissionValues'][$header]))
						$item[$header] = $this->fixValue($submission['SubmissionValues'][$header]['Value']);
					elseif (isset($submission[$header]))
						$item[$header] = $this->fixValue($submission[$header]);
					else
						$item[$header] = '';
				}

				$array[] = $item;
			}
			$xls->write($array);

			if ($done)
			{
				$xls->close();
				// Adjust pagination
				$mainframe->setUserState('com_rsform.submissions.limitstart', 0);
				$mainframe->setUserState('com_rsform.submissions.limit', Factory::getApplication()->get('list_limit'));
				echo 'END';
			}
		}
		// XML Options
		elseif ($type == 'xml')
		{
			$handle = fopen($file, $start == 0 ? 'w' : 'a');
			
			if ($start == 0)
			{
				$buffer = '';
				$buffer .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
				$buffer .= '<form>'."\n";
				$buffer .= '<title><![CDATA['.$model->getFormTitle().']]></title>'."\n";
				$buffer .= "\t".'<submissions>'."\n";
				fwrite($handle, $buffer);
			}

			foreach ($submissions as $submissionId => $submission)
			{
				fwrite($handle, "\t\t".'<submission>'."\n");
				$buffer = '';
				foreach ($order as $orderId => $header)
				{
					if (isset($submission['SubmissionValues'][$header]))
						$item = $submission['SubmissionValues'][$header]['Value'];
					elseif (isset($submission[$header]))
						$item = $submission[$header];
					else
						$item = '';

					if (!is_numeric($item))
						$item = '<![CDATA['.$item.']]>';

					$header = preg_replace('#\s+#', '', $header);

					$buffer .= "\t\t\t".'<'.$header.'>'.$item.'</'.$header.'>'."\n";
				}
				fwrite($handle, $buffer);
				fwrite($handle, "\t\t".'</submission>'."\n");
			}

			if ($done)
			{
				$buffer = '';
				$buffer .= "\t".'</submissions>'."\n";
				$buffer .= '</form>';
				fwrite($handle, $buffer);
				fclose($handle);
				// Adjust pagination
				$mainframe->setUserState('com_rsform.submissions.limitstart', 0);
				$mainframe->setUserState('com_rsform.submissions.limit', Factory::getApplication()->get('list_limit'));
				echo 'END';
			}
			else
			{
				fclose($handle);
			}
		} elseif ($type == 'ods') {
			require_once JPATH_COMPONENT.'/helpers/ods.php';
			
			$ods = new RSFormProODS($file);
			if ($start == 0) {
				$ods->startDoc();
				$ods->startSheet();
				if ($use_headers) {
					foreach ($order as $orderId => $header) {
						$ods->addCell($orderId, $header, 'string');
					}
					$ods->saveRow();
				}
			}

			foreach ($submissions as $submissionId => $submission) {
				foreach ($order as $orderId => $header) {
					if (isset($submission['SubmissionValues'][$header]))
						$item = $submission['SubmissionValues'][$header]['Value'];
					elseif (isset($submission[$header]))
						$item = $submission[$header];
					else
						$item = '';

					if (is_numeric($item)) {
						$ods->addCell($orderId, (float) $item, 'float');
					} else {
						$ods->addCell($orderId, $this->fixValue($item), 'string');
					}
				}
				$ods->saveRow();
			}
			
			if ($done)
			{
				$ods->endSheet();
				$ods->endDoc();
				$ods->saveOds();
				
				// Adjust pagination
				$mainframe->setUserState('com_rsform.submissions.limitstart', 0);
				$mainframe->setUserState('com_rsform.submissions.limit', Factory::getApplication()->get('list_limit'));
				echo 'END';
			}
		}
		
		exit();
	}
	
	public function exportTask()
	{
		$app = Factory::getApplication();

		$data = array(
			'ExportFile'            => $app->input->post->get('ExportFile', '', 'alnum'),
			'ExportType'            => $app->input->post->get('ExportType', '', 'cmd'),
			'ExportHeaders'         => $app->input->post->get('ExportHeaders', 0, 'int'),
			'ExportMultipleSeparator' => $app->input->post->get('ExportMultipleSeparator', '', 'raw'),
			'ExportSubmission'      => $app->input->post->get('ExportSubmission', array(), 'array'),
			'ExportComponent'       => $app->input->post->get('ExportComponent', array(), 'array'),
			'ExportOrder'           => $app->input->post->get('ExportOrder', array(), 'array'),
			'ExportRows'            => $app->input->post->get('ExportRows', 0, 'raw'),
			'ExportDelimiter'       => $app->input->post->get('ExportDelimiter', '', 'raw'),
			'ExportFieldEnclosure'  => $app->input->post->get('ExportFieldEnclosure', '', 'raw')
		);

		Factory::getSession()->set('com_rsform.export.data', serialize($data));

		$app->input->set('view', 'submissions');
		$app->input->set('layout', 'exportprocess');

		parent::display();
	}

    public function importTask()
    {
        $session = Factory::getSession();
        $app	 = Factory::getApplication();
        $headers = $app->input->get('header', array(), 'array');

        $options = (array) $session->get('com_rsform.import.options', array());
        $options['headers'] = array_filter($headers);

        $session->set('com_rsform.import.options', $options);

		$app->input->set('view', 'submissions');
		$app->input->set('layout', 'importprocess');

		parent::display();
    }
	
	public function exportFile()
	{
		$app = Factory::getApplication();
		$file = $app->get('tmp_path').'/'.$app->input->getCmd('ExportFile');
		$formId = $app->input->getInt('formId');
		$original = $file;
		
		$type = $app->input->getCmd('ExportType');
		
		switch ($type) {
			default:
				$extension = $type;
			break;
			
			case 'ods':	
				$extension = 'ods';
				$file = $file.'.ods';
			break;
			
			case 'excelxml':
				$extension = 'xml';
			break;
			
			case 'excel':
				$file .= '.zip';
				$extension = 'xlsx';
			break;
		}

		$filename = str_replace(
			array('{domain}', '{date}', '{formId}'),
			array(Uri::getInstance()->getHost(), HTMLHelper::_('date', 'now', 'Y-m-d_H-i'), $formId),
			RSFormProHelper::getConfig('export.mask')
		);
		
		RSFormProHelper::readFile($file, $filename . '.' . $extension, false);

		if (file_exists($file))
		{
			unlink($file);
		}

		if (file_exists($original))
		{
			unlink($original);
		}

		$app->triggerEvent('onRsformSubmissionsExport', array('formId' => $formId, 'file' => $file, 'filename' => $filename . '.' . $extension));

		exit();
	}
	
	public function viewFile()
	{
		$app	= Factory::getApplication();
		$db		= Factory::getDbo();
		$id 	= $app->input->getInt('id');
		$file   = $app->input->getCmd('file');
		
		$query = $db->getQuery(true);
		$query->select('*')
			  ->from($db->qn('#__rsform_submission_values'))
			  ->where($db->qn('SubmissionValueId').'='.$db->q($id));
		$result = $db->setQuery($query)->loadObject();
		
		// Not found
		if (empty($result))
		{
			$app->redirect('index.php?option=com_rsform&view=submissions');
		}

		$allowedTypes = array(RSFORM_FIELD_FILEUPLOAD);
		
		$query->clear()
			  ->select($db->qn('c.ComponentTypeId'))
			  ->from($db->qn('#__rsform_properties', 'p'))
			  ->leftJoin($db->qn('#__rsform_components', 'c').' ON ('.$db->qn('p.ComponentId').' = '.$db->qn('c.ComponentId').')')
			  ->where($db->qn('p.PropertyName').' = '.$db->q('NAME'))
			  ->where($db->qn('p.PropertyValue').' = '.$db->q($result->FieldName))
			  ->where($db->qn('c.FormId').' = '.$db->q($result->FormId));
		$type = $db->setQuery($query)->loadResult();

		$app->triggerEvent('onRsformSubmissionsViewFile', array(&$allowedTypes, &$result));
		
		// Not an upload field
		if (!in_array($type, $allowedTypes))
		{
			return $this->setRedirect('index.php?option=com_rsform&view=submissions', Text::_('RSFP_VIEW_FILE_NOT_UPLOAD'));
		}

		$foundFile = false;
		if ($file && strlen($file) == 32)
		{
			$values = RSFormProHelper::explode($result->FieldValue);

			foreach ($values as $value)
			{
				if (md5($value) == $file)
				{
					$foundFile = $value;
					break;
				}
			}
		}
		else
		{
			$foundFile = $result->FieldValue;
		}

		if (!$foundFile || !file_exists($foundFile))
		{
			return $this->setRedirect('index.php?option=com_rsform&view=submissions', Text::_('RSFP_VIEW_FILE_NOT_FOUND'));
		}

		RSFormProHelper::readFile($foundFile);
	}

	public function exportPdf()
	{
		$cid = Factory::getApplication()->input->getInt('cid');
		$this->setRedirect('index.php?option=com_rsform&view=submissions&layout=edit&cid='.$cid.'&format=pdf');
	}
}