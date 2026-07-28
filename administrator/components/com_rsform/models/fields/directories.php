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
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

FormHelper::loadFieldClass('list');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\ListField', 'JFormFieldList');
}

require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

class JFormFieldDirectories extends ListField
{
	protected $type = 'Directories';

	protected $firstValue;
	
	protected function getOptions()
    {
        $app        = Factory::getApplication();
        $db         = Factory::getDbo();
        $sortColumn = $app->getUserState('com_rsform.forms.filter_order', 'FormId');
        $sortOrder  = $app->getUserState('com_rsform.forms.filter_order_Dir', 'ASC');
        $options    = array();

        $query = $db->getQuery(true)
            ->select($db->qn('FormId'))
            ->select($db->qn('FormTitle'))
            ->select($db->qn('Lang'))
            ->from($db->qn('#__rsform_forms'))
            ->order($db->qn($sortColumn) . ' ' . $db->escape($sortOrder));
        if ($results = $db->setQuery($query)->loadObjectList())
        {
            $query->clear()
                ->select($db->qn('formId'))
                ->from($db->qn('#__rsform_directory'));

            $directories = $db->setQuery($query)->loadColumn();

            foreach ($results as $result)
            {
                $lang = RSFormProHelper::getCurrentLanguage($result->FormId);

                if ($lang != $result->Lang)
                {
                    if ($translations = RSFormProHelper::getTranslations('forms', $result->FormId, $lang))
                    {
                        foreach ($translations as $field => $value)
                        {
                            if (isset($result->$field))
                            {
                                $result->$field = $value;
                            }
                        }
                    }
                }

                $options[] = HTMLHelper::_('select.option', $result->FormId, sprintf('(%d) %s', $result->FormId, $result->FormTitle), 'value', 'text', !in_array($result->FormId, $directories));
            }

            $first = reset($results);

            $this->firstValue = $first->FormId;
        }

		reset($options);
		
		return $options;
	}

	public function getInput()
	{
		$html = parent::getInput();

		if ($this->value)
		{
			$url = Route::_('index.php?option=com_rsform&view=directory&layout=edit&formId=' . $this->value);
		}
		elseif ($this->firstValue)
		{
			$url = Route::_('index.php?option=com_rsform&view=directory&layout=edit&formId=' . $this->firstValue);
		}
		else
		{
			$url = '#';
		}

		$html .= ' <a id="directoryLink" target="_blank" href="' . $url . '" class="btn btn-primary">' . Text::_('COM_RSFORM_EDIT_DIRECTORY') . '</a>';

		Factory::getDocument()->addScriptDeclaration("function generateDirectoryLink() { document.getElementById('directoryLink').setAttribute('href', 'index.php?option=com_rsform&view=directory&layout=edit&formId=' + document.getElementById('{$this->id}').value); }");

		return $html;
	}
}
