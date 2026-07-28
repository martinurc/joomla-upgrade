<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

FormHelper::loadFieldClass('list');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\ListField', 'JFormFieldList');
}

class JFormFieldSubmissionordering extends ListField
{
    protected function getOptions()
    {
        $options = parent::getOptions();

        /* @var $model RsformModelSubmissions */
	    $model = BaseDatabaseModel::getInstance('Submissions', 'RsformModel');
	    if ($headers = array_merge($model->getStaticHeaders(), $model->getHeaders()))
	    {
	    	foreach ($headers as $header)
		    {
			    $options[] = HTMLHelper::_('select.option', $header->value . ' ASC', Text::sprintf('COM_RSFORM_SUBMISSIONS_HEADER_ORDERING_ASC', $header->label));
			    $options[] = HTMLHelper::_('select.option', $header->value . ' DESC', Text::sprintf('COM_RSFORM_SUBMISSIONS_HEADER_ORDERING_DESC', $header->label));
		    }
	    }

	    return $options;
    }
}
