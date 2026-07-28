<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class TableRSForm_Conditions extends Table
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id;

	public $form_id;
	public $action;
	public $block;
	public $component_id;
	public $condition;
	public $lang_code;
    public $ordering;

	protected $_jsonEncode = array('component_id');
		
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__rsform_conditions', 'id', $db);
	}

	public function load($keys = null, $reset = true)
	{
		$result = parent::load($keys, $reset);

		if ($result)
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/conditions.php';

			$this->component_id = RSFormProConditions::parseComponentIds($this->component_id);

			if ($this->id)
			{
				$db = $this->getDbo();

				$query = $db->getQuery(true);
				$query->select('*')
					->from($db->qn('#__rsform_condition_details'))
					->where($db->qn('condition_id') . ' = ' . $db->q($this->id));
				$db->setQuery($query);
				$this->details = $db->loadObjectList();
			}
			else
			{
				$this->details = array();
			}
		}

		return $result;
	}

	public function save($src, $orderingFilter = 'form_id', $ignore = '')
	{
		$result = parent::save($src, $orderingFilter, $ignore);

		if ($result)
		{
			$db = $this->getDbo();

			// Delete previous condition details
			$query = $db->getQuery(true)
				->delete($db->qn('#__rsform_condition_details'))
				->where($db->qn('condition_id') . ' = ' . $db->q($this->id));
			$db->setQuery($query)
				->execute();

			if (isset($src['detail_component_id'], $src['operator'], $src['value']))
			{
				$component_ids 	= $src['detail_component_id'];
				$operators 		= $src['operator'];
				$values 		= $src['value'];

				for ($i = 0; $i < count($component_ids); $i++)
				{
					if (!isset($component_ids[$i], $operators[$i], $values[$i]))
					{
						continue;
					}

					$detail = Table::getInstance('RSForm_Condition_Details', 'Table');
					$detail->save(array(
						'condition_id' 	=> $this->id,
						'component_id'	=> $component_ids[$i],
						'operator'		=> $operators[$i],
						'value'			=> $values[$i]
					));
				}
			}
		}

		return $result;
	}

	public function delete($pk = null)
	{
		$result = parent::delete($pk);

		if ($result)
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->delete($db->qn('#__rsform_condition_details'))
				->where($db->qn('condition_id') . ' = ' . $db->q($this->id));
			$db->setQuery($query)
				->execute();
		}

		return $result;
	}

    public function check()
    {
        if (!$this->id && !$this->ordering)
        {
            $db = $this->getDbo();
            $this->ordering = $this->getNextOrder($db->qn('form_id') . ' = ' . $db->q($this->form_id) . ' AND ' . $db->qn('lang_code') . ' = ' . $db->q($this->lang_code));
        }

        return true;
    }
}