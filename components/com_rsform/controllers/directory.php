<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

class RsformControllerDirectory extends RsformController
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->registerTask('apply', 'save');
	}
	
	public function download()
	{
		$app 		= Factory::getApplication();
		$model		= $this->getModel('directory');
		$directory	= $model->getDirectory();
		$isAjax     = $app->input->getInt('ajax');

		try
		{
			if (!$directory->enablecsv)
			{
				throw new Exception(Text::_('RSFP_VIEW_DIRECTORY_NO_CSV'));
			}

			if (!$model->isValid())
			{
				throw new Exception($model->getError());
			}

			if ($isAjax)
			{
				$this->checkToken();
			}

			$db 	= Factory::getDbo();
			$params = $app->getParams('com_rsform');
			$menu	= $app->getMenu();
			$formId = $params->get('formId');
			$filename = str_replace(array('{alias}', '{formid}', '{domain}', '{date}'), array($menu->getActive()->alias, $formId, Uri::getInstance()->getHost(), HTMLHelper::_('date', 'now', 'Y-m-d_H-i')), $directory->csvfilename);
			$cids 	= $app->input->get('cid', array(), 'array');
			$cids 	= array_map('intval', $cids);
			$root 	= Uri::getInstance()->toString(array('scheme', 'host', 'port'));

			if (!$cids && !$isAjax)
			{
				throw new Exception(Text::_('COM_RSFORM_SUBMISSIONS_DIRECTORY_NO_SUBMISSIONS_SELECTED'));
			}

			$fields  = RSFormProHelper::getDirectoryFields($formId);
			$headers = RSFormProHelper::getDirectoryStaticHeaders();

			$downloadableFields 		= array();
			$downloadableFieldCaptions 	= array();
			foreach ($fields as $field)
			{
				if ($field->incsv)
				{
					$downloadableFields[] = (object) array(
						'name'	 => $field->FieldName,
						'static' => $field->componentId < 0 && isset($headers[$field->componentId]) ? 1 : 0
					);
					$downloadableFieldCaptions[] = $field->FieldCaption;
				}
			}

			list($multipleSeparator, $uploadFields, $multipleFields, $textareaFields, $secret) = RSFormProHelper::getDirectoryFormProperties($formId);

			// Get submissions
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('FormId') . ' = ' . $db->q($formId));
			if (!$isAjax)
			{
				$query->where($db->qn('SubmissionId') . ' IN (' . implode(',', $db->q($cids)) . ')');
				$submissions = $db->setQuery($query)->loadObjectList('SubmissionId');
				$cids = array_keys($submissions);

				$showHeaders = true;
			}
			else
			{
				$limitstart = $app->input->getInt('limitstart');
				$limit = $app->input->getInt('limit');

				/* @var $model RsformModelDirectory */
				$model = $this->getModel('directory');
				$model->setGroupConcatLimit();
				$model->setNeedsSelect($downloadableFields);
				if ($modelQuery = $model->getListQuery())
				{
					$submissions = $db->setQuery($modelQuery, $limitstart, $limit)->loadObjectList('SubmissionId');
					$cids = array_keys($submissions);
				}
				else
				{
					$cids = array();
				}

				$showHeaders = !($limitstart > 0);
			}

			// Double check
			if (!$cids)
			{
				throw new Exception(Text::_('COM_RSFORM_SUBMISSIONS_DIRECTORY_NO_SUBMISSIONS_SELECTED'));
			}

			// Get values
			$names = array();
			foreach ($downloadableFields as $field)
			{
				if (!$field->static)
				{
					$names[] = $db->q($field->name);
				}
			}

			$query = $db->getQuery(true);
			$query->select($db->qn('SubmissionId'))
				->select($db->qn('FieldName'))
				->select($db->qn('FieldValue'))
				->from($db->qn('#__rsform_submission_values'))
				->where($db->qn('FormId').'='.$db->q($formId));
			if ($cids)
			{
				$query->where($db->qn('SubmissionId').' IN ('.implode(',', $cids).')');
			}
			if ($names)
			{
				$query->where($db->qn('FieldName').' IN ('.implode(',', $names).')');
			}

			$db->setQuery($query);
			$values = $db->loadObjectList();

			// Combine them
			foreach ($values as $item)
			{
				if (!isset($submissions[$item->SubmissionId]->values))
				{
					$submissions[$item->SubmissionId]->values = array();
				}

				// process here
				if (in_array($item->FieldName, $uploadFields))
				{
					if ($item->FieldValue)
					{
						$files = RSFormProHelper::explode($item->FieldValue);
						$actualValues = array();
						foreach ($files as $file)
						{
							$actualValues[] = '<a href="' . $root . Route::_('index.php?option=com_rsform&task=submissions.viewfile&hash=' . md5($item->SubmissionId.$secret.$item->FieldName) . '&file=' . md5($file)) . '">' . RSFormProHelper::htmlEscape(basename($file)) . '</a>';
						}

						$item->FieldValue = implode("\n", $actualValues);
					}
				}
				elseif (in_array($item->FieldName, $multipleFields))
				{
					$item->FieldValue = str_replace("\n", $multipleSeparator, $item->FieldValue);
				}

				$submissions[$item->SubmissionId]->values[$item->FieldName] = $item->FieldValue;
			}

			$app->triggerEvent('onRsformFrontendDownloadCSV', array(&$submissions, $formId));

			$enclosure = $params->get('enclosure', '"');
			$delimiter = $params->get('delimiter', ',');

			$app->setHeader('Cache-Control', 'public, must-revalidate');
			$app->setHeader('Cache-Control', 'pre-check=0, post-check=0, max-age=0');
			$app->setHeader('Pragma', 'no-cache');
			$app->setHeader('Expires', '0');
			$app->setHeader('Content-Description', 'File Transfer');
			$app->setHeader('Expires', 'Sat, 01 Jan 2000 01:00:00 GMT');
			$app->setHeader('Content-Type', 'text/csv');
			$app->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
			$app->setHeader('Content-Transfer-Encoding', 'binary');
			$app->sendHeaders();
			ob_end_clean();

			if ($showHeaders)
			{
				echo $enclosure . implode($enclosure . $delimiter . $enclosure, str_replace($enclosure, $enclosure . $enclosure, $downloadableFieldCaptions)) . $enclosure . "\n";
			}

			foreach ($cids as $cid) {
				$row = array();
				foreach ($downloadableFields as $field) {
					$value = '';
					if (!$field->static && isset($submissions[$cid]->values[$field->name])) {
						$value = $submissions[$cid]->values[$field->name];
					} elseif ($field->static && isset($submissions[$cid]->{$field->name})) {
						// Show a text for the "confirmed" column.
						if ($field->name == 'confirmed') {
							$value = $submissions[$cid]->{$field->name} ? Text::_('RSFP_YES') : Text::_('RSFP_NO');
						} else if ($field->name == 'DateSubmitted') {
							$value = RSFormProHelper::getDate($submissions[$cid]->{$field->name});
						} else {
							$value = $submissions[$cid]->{$field->name};
						}
					}

					$row[] = $this->fixValue($value);
				}

				echo $enclosure.implode($enclosure.$delimiter.$enclosure, str_replace($enclosure, $enclosure.$enclosure, $row)).$enclosure."\n";
			}
		}
		catch (Exception $e)
		{
			if (!$isAjax)
			{
				$app->enqueueMessage($e->getMessage(), 'warning');
				$app->redirect(Uri::root());
			}
			else
			{
				echo $e->getMessage();
			}
		}

		$app->close();
	}

	protected function fixValue($string)
	{
		if (is_string($string) && strlen($string) && in_array($string[0], array('=', '+', '-', '@')))
		{
			$string = ' ' . $string;
		}

		return $string;
	}
	
	public function save()
	{
		$app 	= Factory::getApplication();
		$formId	= $app->input->getInt('formId',0);
		$id		= $app->input->getInt('id',0);
		$task	= $this->getTask();
		
		// Get the model
		$model = $this->getModel('directory');
		
		// Save
		if (!RSFormProHelper::canEdit($formId, $id))
		{
			$this->setMessage(Text::_('COM_RSFORM_SUBMISSIONS_DIRECTORY_CANNOT_SAVE'),'error');
			$this->setRedirect(Route::_('index.php?option=com_rsform&view=directory',false));

			return false;
		}

		if (!$model->save())
		{
			$app->enqueueMessage(Text::_('RSFP_SUBM_DIR_SAVE_ERROR'),'error');

			$app->input->set('view', 'directory');
			$app->input->set('layout', 'edit');
			$app->input->set('id', $id);

			parent::display();

			return false;
		}

		$this->setMessage(Text::_('RSFP_SUBM_DIR_SAVE_OK'));

		if ($task == 'apply')
		{
			$this->setRedirect(Route::_('index.php?option=com_rsform&view=directory&layout=edit&id='.$id,false));
		}
		else
		{
			$this->setRedirect(Route::_('index.php?option=com_rsform&view=directory',false));
		}
	}

    public function delete()
	{
        $app 	= Factory::getApplication();
        $formId	= $app->getParams('com_rsform')->get('formId');
        $id		= $app->input->getInt('id',0);

        // Get the model
        $model = $this->getModel('directory');

		// Check if we can delete
		if (!RSFormProHelper::canDelete($formId, $id))
		{
			$this->setMessage(Text::_('COM_RSFORM_SUBMISSIONS_DIRECTORY_CANNOT_DELETE'),'error');
		}
		else
		{
			// Set message
			$this->setMessage(Text::sprintf('RSFP_SUBM_DIR_DELETE_OK', $id));

			// Delete
			$model->delete($id);
		}

		// Set the redirect
		$this->setRedirect(Route::_('index.php?option=com_rsform&view=directory',false));
    }
	
	public function back() {
		$this->setRedirect(Route::_('index.php?option=com_rsform&view=directory', false));
	}
}