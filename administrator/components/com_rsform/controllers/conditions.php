<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class RsformControllerConditions extends RsformController
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->registerTask('apply', 'save');
	}
	
	public function save()
	{
		// Check for request forgeries
		$this->checkToken();

		$model 	= $this->getModel('conditions');
		$task 	= $this->getTask();
		$formId = $model->getFormId();
		
		// Save
		$cid = $model->save();

        $link = 'index.php?option=com_rsform&view=conditions&layout=edit&formId=' . $formId . '&tmpl=component';

		if ($cid)
        {
            $link .= '&cid=' . $cid;
            $msg = Text::_('RSFP_CONDITION_SAVED');
        }
        else
        {
            $msg = Text::_('RSFP_CONDITION_ERROR');
        }

        if ($task == 'save')
        {
            $link .= '&close=1';
        }

		$this->setRedirect($link, $msg);
	}
	
	public function remove()
	{
		$model  = $this->getModel('conditions');
		$formId = $model->getFormId();
		$app    = Factory::getApplication();
		
		$model->remove();
		
		$app->input->set('view', 'forms');
        $app->input->set('layout', 'edit_conditions');
        $app->input->set('tmpl', 'component');
        $app->input->set('formId', $formId);
		
		parent::display();

		$app->close();
	}
	
	public function showConditions()
	{
		$model  = $this->getModel('conditions');
		$formId = $model->getFormId();
        $app    = Factory::getApplication();

        $app->input->set('view', 'forms');
        $app->input->set('layout', 'edit_conditions');
        $app->input->set('tmpl', 'component');
        $app->input->set('formId', $formId);
		
		parent::display();

		$app->close();
	}

    public function saveOrdering()
    {
        $db		= Factory::getDbo();
        $app	= Factory::getApplication();
        $cids	= $app->input->get('cid', array(), 'array');
        $formId	= $app->input->getInt('formId',0);

        foreach ($cids as $key => $order)
        {
            $query = $db->getQuery(true)
                ->update($db->qn('#__rsform_conditions'))
                ->set($db->qn('ordering') . ' = ' . $db->q($order))
                ->where($db->qn('id') . ' = ' . $db->q($key))
                ->where($db->qn('form_id') . ' = ' . $db->q($formId));

            $db->setQuery($query);
            $db->execute();
        }

        echo 'Ok';

        $app->close();
    }
}