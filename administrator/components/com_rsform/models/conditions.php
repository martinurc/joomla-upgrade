<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class RsformModelConditions extends BaseDatabaseModel
{
	public function getFormId()
	{
		return Factory::getApplication()->input->getInt('formId');
	}
	
	public function getAllFields()
	{
	    static $cache;

	    if ($cache === null)
	    {
	    	$db = $this->getDbo();
            $formId = $this->getFormId();

            $query = $db->getQuery(true)
                ->select($db->qn('p.PropertyValue'))
                ->select($db->qn('p.ComponentId'))
                ->select($db->qn('c.ComponentTypeId'))
                ->from($db->qn('#__rsform_components', 'c'))
                ->join('LEFT', $db->qn('#__rsform_properties', 'p') . ' ON (' . $db->qn('c.ComponentId') . '=' . $db->qn('p.ComponentId') . ')')
                ->where($db->qn('c.FormId') . '=' . $db->q($formId))
                ->where($db->qn('p.PropertyName') . '=' . $db->q('NAME'))
                ->order($db->qn('c.Order') . ' ' . $db->escape('ASC'));

            $cache = $db->setQuery($query)->loadObjectList();
        }

        return $cache;
	}
	
	public function getOptionFields()
	{
		$result = array();
		$app 	= Factory::getApplication();
        $formId = $this->getFormId();
		$types 	= array(
            RSFORM_FIELD_SELECTLIST,
            RSFORM_FIELD_CHECKBOXGROUP,
            RSFORM_FIELD_RADIOGROUP,
			RSFORM_FIELD_RANGE_SLIDER
        );
		
		$app->triggerEvent('onRsformBackendCreateConditionOptionFields', array(array('types' => &$types, 'formId' => $formId)));
		$types = array_map('intval', $types);

		$optionFields = array();
		if ($fields = $this->getAllFields())
        {
            foreach ($fields as $field)
            {
                if (in_array($field->ComponentTypeId, $types))
                {
                    $optionFields[] = $field;
                }
            }
        }

        if ($optionFields)
        {
            $properties = RSFormProHelper::getComponentProperties($optionFields);

            require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fielditem.php';
            require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fieldmultiple.php';

            foreach ($optionFields as $optionField)
            {
                // Some cleanup
                $optionField->ComponentName = $optionField->PropertyValue;
                $optionField->items = array();
                unset($optionField->PropertyValue);

                $config = array(
                    'formId' 			=> $formId,
                    'componentId' 		=> $optionField->ComponentId,
                    'data' 				=> $properties[$optionField->ComponentId],
                    'value' 			=> array(),
                    'invalid' 			=> false
                );

				// A workaround to allow Range Slider fields
				if ($optionField->ComponentTypeId == RSFORM_FIELD_RANGE_SLIDER)
				{
					if ($config['data']['USEVALUES'] == 'YES')
					{
						$config['data']['ITEMS'] = $config['data']['VALUES'];
					}
					else
					{
						$config['data']['ITEMS'] = implode("\n", range($config['data']['MINVALUE'], $config['data']['MAXVALUE']));
					}
				}

                $field = new RSFormProFieldMultiple($config);

				$resultItems = array();

                if ($items = $field->getItems())
                {
                    foreach ($items as $item)
                    {
						$item = new RSFormProFieldItem($item);
						
						$app->triggerEvent('onRsformBackendCreateConditionOptionFieldItem', array(array('field' => &$optionField, 'item' => &$item, 'formId' => $formId)));
						
                        $resultItems[] = (object) array('value' => $item->value, 'label' => $item->label);
                    }
                }

                $result[$optionField->ComponentId] = (object) array(
                	'id'	=> $optionField->ComponentId,
                	'name'	=> $optionField->ComponentName,
                	'items' => $resultItems
				);
            }
        }

        return $result;
	}
	
	public function getCondition()
	{
		$cid = Factory::getApplication()->input->getInt('cid');
		$row = Table::getInstance('RSForm_Conditions', 'Table');
		$row->load($cid);
		
		return $row;
	}
	
	public function getLang()
	{
		return RSFormProHelper::getCurrentLanguage($this->getFormId());
	}
	
	public function save()
	{
		$post		= Factory::getApplication()->input->post->getArray(array(), null, 'raw');
		$condition 	= Table::getInstance('RSForm_Conditions', 'Table');

		try
        {
            $condition->save($post);
            return $condition->id;
        }
        catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
	}
	
	public function remove()
	{
		$condition = Table::getInstance('RSForm_Conditions', 'Table');
		$cid	   = Factory::getApplication()->input->getInt('cid');

		try
		{
			return $condition->delete($cid);
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
	}
}