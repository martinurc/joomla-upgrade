<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

/**
 * RSForm! Pro Delete Submissions System Plugin
 */
class plgSystemRsformdeletesubmissions extends CMSPlugin
{
    public function onAfterInitialise()
    {
        if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php'))
        {
            return false;
        }

        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		if (!class_exists('RSFormProConfig'))
		{
			return false;
		}

        $now        = Factory::getDate()->toUnix();
        $config     = RSFormProConfig::getInstance();
        $last_run   = $config->get('deleteafter.last_run', 0);
        $interval   = $config->get('deleteafter.interval', 10);
        
        if ($last_run + ($interval * 60) > $now)
        {
            return false;
        }

        $config->set('deleteafter.last_run', $now);

		$db = Factory::getDbo();
		
		$query = $db->getQuery(true)
			->select($db->qn('FormId'))
			->select($db->qn('DeleteSubmissionsAfter'))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('DeleteSubmissionsAfter') . ' > ' . $db->q(0));
		
		if ($forms = $db->setQuery($query)->loadObjectList())
		{
			foreach ($forms as $form)
			{
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

                    RSFormProSubmissionsHelper::deleteSubmissions($submissions);
				}
			}
		}
    }

    public function onPreprocessMenuItems($context, &$items, $params = null, $enabled = true)
    {
	    if ($context != 'com_menus.administrator.module' )
	    {
		    return;
	    }

        $user = Factory::getUser();
		$remove = array();
        foreach ($items as $i => $item)
        {
            if (isset($item->element) && $item->element == 'com_rsform')
            {
                if (
                    ($item->link === 'index.php?option=com_rsform&view=forms' && !$user->authorise('forms.manage', 'com_rsform')) ||
                    ($item->link === 'index.php?option=com_rsform&view=submissions' && !$user->authorise('submissions.manage', 'com_rsform')) ||
                    ($item->link === 'index.php?option=com_rsform&view=directory' && !$user->authorise('directory.manage', 'com_rsform')) ||
                    ($item->link === 'index.php?option=com_rsform&view=configuration' && !$user->authorise('core.admin', 'com_rsform')) ||
                    ($item->link === 'index.php?option=com_rsform&view=backupscreen' && !$user->authorise('backuprestore.manage', 'com_rsform')) ||
					($item->link === 'index.php?option=com_rsform&view=restorescreen' && !$user->authorise('backuprestore.manage', 'com_rsform'))
                )
                {
					if (is_callable(array($item, 'getParams')))
					{
						$params = $item->getParams();
						$params->set('menu_show', 0);
						$item->setParams($params);
					}
					else
					{
						$remove[] = $i;
					}
                }
            }
        }

		if ($remove)
		{
			foreach ($remove as $key)
			{
				unset($items[$key]);
			}
		}
    }
}