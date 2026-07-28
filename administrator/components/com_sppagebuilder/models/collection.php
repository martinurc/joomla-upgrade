<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;


JLoader::register('SppagebuilderHelperRoute', JPATH_ROOT . '/components/com_sppagebuilder/helpers/route.php');

class SppagebuilderModelCollection extends AdminModel
{
    public function getForm($data = array(), $loadData = true)
    {
        $form = $this->loadForm(
            'com_sppagebuilder.collection',
            'collection',
            [
                'control' => 'jform',
                'load_data' => $loadData
            ]
        );

        return $form;
    }

   public function getItem($pk = null)
{
    $item = parent::getItem($pk);

    if ($item && $item->id)
    {
        $table = $this->getTable();
        $table->load($item->id);

        $item->asset_id = $table->asset_id;

        if ($item->asset_id)
        {
            $asset = \Joomla\CMS\Table\Table::getInstance('Asset');
            $asset->load($item->asset_id);
            $item->rules = new \Joomla\CMS\Access\Rules(json_decode($asset->rules, true));
        }
    }

    return $item;
}


    protected function populateState()
    {
        $app = Factory::getApplication();

        $id = $app->input->getInt('id', 0);
        $this->setState('collection.id', $id);
    }

    public function getTable($name = 'Collection', $prefix = 'SppagebuilderTable', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    protected function loadFormData()
    {
        return $this->getItem();
    }
    
}
