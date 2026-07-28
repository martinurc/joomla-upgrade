<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;

class RsformModelSubmissions extends BaseDatabaseModel
{
	public $_form;
	public $_data = array();
	public $_total;
	public $_query;
	public $_pagination;
	
	public $formId;

	/* @var $params Joomla\Registry\Registry */
	public $params;

	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$app 			= Factory::getApplication();
		$this->params 	= $app->getParams('com_rsform');
		$this->formId 	= (int) $this->params->get('formId');
		
		// The parameter is not enabled, throw an error to prevent other people from crafting a link and seeing submissions
		if (!$this->params->get('enable_submissions', 0))
		{
		    throw new Exception(Text::_('RSFP_VIEW_SUBMISSIONS_NOT_ENABLED_FORGOT'), 403);
		}
		
		// Get pagination request variables
		$limit		= $app->input->get('limit', $this->params->get('display_num', Factory::getApplication()->get('list_limit')), 'int');
		$limitstart	= $app->input->get('limitstart', 0, 'int');

		$previousFiltersHash = $app->getUserState('com_rsform.submissions.currentfilterhash', '');

		// get the current filters hashes
		$currentFiltersHash = $this->getFiltersHash();

		// reset the pagination if the filters are not the same
		if ($previousFiltersHash != $currentFiltersHash)
		{
			$limitstart = 0;
		}

		// In case limit has been changed, adjust it
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState('com_rsform.submissions.formId'.$this->formId.'.limit', $limit);
		$this->setState('com_rsform.submissions.formId'.$this->formId.'.limitstart', $limitstart);
		
		$this->_query = $this->_buildQuery();
	}
	
	public function _buildQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('s.SubmissionId'))
			->select($db->qn('s.confirmed'))
			->from($db->qn('#__rsform_submissions', 's'))
			->where($db->qn('s.FormId') . ' = ' . $db->q($this->formId));

		if ($this->params->get('show_confirmed', 0))
		{
			$query->where($db->qn('s.confirmed') . ' = ' . $db->q(1));
		}

		if ($lang = $this->params->get('lang', ''))
		{
			$query->where($db->qn('s.Lang') . ' = ' . $db->q($lang));
		}

		// If we're filtering results
		$filter = $this->getFilter();
		$areas 	= $this->params->def('search_in', array('DateSubmitted', 'Username', 'UserIp', 'FieldValue'));
		if ($this->params->get('show_search') && $filter !== '' && $areas)
		{
			$or = array();

			$escapedFilter = $db->q('%' . $db->escape($filter) . '%', false);

			if (in_array('DateSubmitted', $areas) && !preg_match('#([^0-9\-: ])#', $filter))
			{
				$or[] = $db->qn('s.DateSubmitted') . ' LIKE ' . $escapedFilter;
			}
			if (in_array('Username', $areas))
			{
				$or[] = $db->qn('s.Username') . ' LIKE ' . $escapedFilter;
			}
			if (in_array('UserIp', $areas))
			{
				$or[] = $db->qn('s.UserIp') . ' LIKE ' . $escapedFilter;
			}
			if (in_array('FieldValue', $areas))
			{
				$or[] = $db->qn('sv.FieldValue') . ' LIKE ' . $escapedFilter;

				$query->join('left', $db->qn('#__rsform_submission_values', 'sv') . ' ON (' . $db->qn('s.SubmissionId') . ' = ' . $db->qn('sv.SubmissionId') . ')')
					->group(array($db->qn('s.SubmissionId')));
			}

			if ($or)
			{
				$query->where('(' . implode(' OR ', $or) . ')');
			}
		}
		
		$userId 		= $this->params->get('userId');
		$show_logged_in = $this->params->get('show_logged_in_submissions');
		$show_all 		= $this->params->get('show_all_submissions');

		if (!$show_all)
		{
			if ($show_logged_in)
			{
				$user = Factory::getUser();
				if ($user->guest)
				{
					return false;
				}
				else
				{
					$query->where($db->qn('s.UserId') . ' = ' . $db->q($user->id));
				}
			}
			else
			{
				$userId = explode(',', $userId);
				$userId = array_map('intval', $userId);

				if ($userId)
				{
					$query->where($db->qn('s.UserId') . ' IN (' . implode(',', $db->q($userId)) . ')');
				}
			}
		}

		// Set ordering
		$dir = $this->params->get('sort_submissions') ? 'ASC' : 'DESC';
		$query->order($db->qn('s.DateSubmitted') . ' ' . $db->escape($dir));

		// set the current filters hash
		Factory::getApplication()->setUserState('com_rsform.submissions.currentfilterhash', $this->getFiltersHash());
		
		return $query;
	}

	protected function getFiltersHash()
	{
		static $hash;

		if (is_null($hash))
		{
			$filter  = $this->getFilter();
			$lang    = $this->params->get('lang', '');
			$hash 	 = md5($filter . $lang);
		}

		return $hash;
	}
	
	public function getPagination()
	{
		if (empty($this->_pagination))
		{
			$this->_pagination = new Pagination($this->getTotal(), $this->getState('com_rsform.submissions.formId'.$this->formId.'.limitstart'), $this->getState('com_rsform.submissions.formId'.$this->formId.'.limit'));
		}
		
		return $this->_pagination;
	}
	
	public function getTotal()
	{
		if ($this->_total === null)
		{
			$this->_total = 0;

			if ($this->_query)
			{
				$this->_total = $this->_getListCount($this->_query);
			}
		}

		return $this->_total;
	}
	
	public function getSubmissions()
	{
		if (empty($this->_data) && $this->_query)
		{
			$db = $this->getDbo();

			try
			{
				$db->setQuery('SET SQL_BIG_SELECTS=1')->execute();
			}
			catch (Exception $e)
			{

			}
			
			$db->setQuery($this->_query, $this->getState('com_rsform.submissions.formId'.$this->formId.'.limitstart'), $this->getState('com_rsform.submissions.formId'.$this->formId.'.limit'));
			$this->_data = $db->loadObjectList();
		}
		
		return $this->_data;
	}

	public function getListingTemplate()
	{
		// Templates
		$template_module      = $this->params->def('template_module', '');
		$template_formdatarow = $this->params->def('template_formdatarow', '');

		$formdata 		= '';
		$submissions 	= $this->getSubmissions();
		$pagination 	= $this->getPagination();

		$i = 0;
		foreach ($submissions as $submission)
		{
			$pdfLink 		= Route::_('index.php?option=com_rsform&view=submissions&layout=view&cid=' . $submission->SubmissionId . '&format=pdf');
			$detailsLink 	= Route::_('index.php?option=com_rsform&view=submissions&layout=view&cid=' . $submission->SubmissionId);

			// Get global placeholders
			list($replace, $with) = RSFormProHelper::getReplacements($submission->SubmissionId);

			$replacements = array(
				// Global specific placeholders
				'{global:counter}'		 => $pagination->getRowOffset($i),
				'{global:naturalcounter}'=> $this->params->get('sort_submissions') ? $pagination->getRowOffset($i) : ($pagination->total + 1 - $pagination->getRowOffset($i)),
				'{global:confirmed}'	 => $submission->confirmed ? Text::_('RSFP_YES') : Text::_('RSFP_NO'),
				// Details links
				'{details}'				 => '<a href="'.$detailsLink.'">',
				'{details_link}'		 => $detailsLink,
				// PDF links
				'{detailspdf}'			 => '<a href="'.$pdfLink.'">',
				'{detailspdf_link}'		 => $pdfLink,
				'{/details}'			 => '</a>',
				'{/detailspdf}'			 => '</a>'
			);

			// Add our own placeholders
			$replace = array_merge($replace, array_keys($replacements));
			$with 	 = array_merge($with, array_values($replacements));

			$rowdata = $template_formdatarow;

			// Add scripting
			if (strpos($rowdata, '{/if}') !== false)
			{
				require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/scripting.php';
				RSFormProScripting::compile($rowdata, $replace, $with);
			}

			$formdata .= str_replace($replace, $with, $rowdata);

			$i++;
		}

		return str_replace('{formdata}', $formdata, $template_module);
	}

	public function getDetailTemplate()
	{
		$app			= Factory::getApplication();
		$db             = $this->getDbo();
		$cid 			= $app->input->getInt('cid');
		$format 		= $app->input->getCmd('format');
		$user   		= Factory::getUser();
		$userId 		= $this->params->get('userId');
		$show_logged_in = $this->params->get('show_logged_in_submissions');
		$show_all 		= $this->params->get('show_all_submissions');

		$template_formdetail = $this->params->def('template_formdetail', '');

		if (!$show_all && !$show_logged_in)
		{
			$userId = explode(',', $userId);
			$userId = array_map('intval', $userId);
		}

		// Grab submission
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__rsform_submissions'))
			->where($db->qn('SubmissionId') . ' = ' . $db->q($cid));
		$submission = $db->setQuery($query)->loadObject();

		// Submission doesn't exist
		if (!$submission)
		{
			throw new Exception(Text::sprintf('RSFP_SUBMISSION_DOES_NOT_EXIST', $cid), 404);
		}

		// Submission doesn't belong to the configured form ID OR
		// can view only own submissions and not his own OR
		// can view only specified user IDs and this doesn't belong to any of the IDs
		if ($submission->FormId != $this->formId || ($show_logged_in && ($user->guest || $submission->UserId != $user->id)) || (is_array($userId) && !in_array($user->id, $userId)))
		{
			throw new Exception(Text::sprintf('RSFP_SUBMISSION_NOT_ALLOWED', $cid), 403);
		}

		if ($this->params->get('show_confirmed', 0) && !$submission->confirmed)
		{
			throw new Exception(Text::sprintf('RSFP_SUBMISSION_NOT_CONFIRMED', $cid), 403);
		}

		$pdfLink 		= Route::_('index.php?option=com_rsform&view=submissions&layout=view&cid=' . $submission->SubmissionId . '&format=pdf');
		$detailsLink 	= Route::_('index.php?option=com_rsform&view=submissions&layout=view&cid=' . $submission->SubmissionId);

		list($replace, $with) = RSFormProHelper::getReplacements($submission->SubmissionId);

		$replacements = array(
			// Details links
			'{details}'			=> '<a href="' . $detailsLink . '">',
			'{details_link}'	=> $detailsLink,
			// PDF links
			'{detailspdf}'		=> '<a href="' . $pdfLink . '">',
			'{detailspdf_link}'	=> $pdfLink,
			'{/details}'		=> '</a>',
			'{/detailspdf}'		=> '</a>',
			'{global:confirmed}' => $submission->confirmed ? Text::_('RSFP_YES') : Text::_('RSFP_NO')
		);

		$replace = array_merge($replace, array_keys($replacements));
		$with 	 = array_merge($with, array_values($replacements));

		if ($format == 'pdf' && preg_match_all('#{detailspdf}(.*?){\/detailspdf}#is', $template_formdetail, $matches))
		{
			foreach ($matches[0] as $fullmatch)
			{
				$template_formdetail = str_replace($fullmatch, '', $template_formdetail);
			}
		}

		// Add scripting
		if (strpos($template_formdetail, '{/if}') !== false)
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/scripting.php';
			RSFormProScripting::compile($template_formdetail, $replace, $with);
		}

		return str_replace($replace, $with, $template_formdetail);
	}
	
	public function getFilter()
	{
		return Factory::getApplication()->getUserStateFromRequest('com_rsform.submissions.formId' . $this->formId . '.filter', 'filter', '');
	}
}