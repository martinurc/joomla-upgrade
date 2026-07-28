<?php

defined('_JEXEC') or die;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class SppagebuilderTableCollection extends Table
{
     public function __construct(&$db)
    {
        parent::__construct('#__sppagebuilder_collections', 'id', $db);
    }

    protected function _getAssetName()
    {
        return 'com_sppagebuilder.collection.' . (int) $this->id;
    }

    protected function _getAssetParentId(Table $table = null, $id = null): int
    {
        $asset = Table::getInstance('Asset');
        $asset->loadByName('com_sppagebuilder');
        return (int) $asset->id;
    }

	protected function _getAssetTitle()
	{
		return $this->title;
	}

    public function store($updateNulls = false)
    {
        $result = parent::store($updateNulls);

        if ($this->id)
        {
            $asset = Table::getInstance('Asset');
            $asset->loadByName($this->_getAssetName());
            
            if ($this->asset_id != $asset->id)
            {
                $this->asset_id = $asset->id;
                parent::store($updateNulls);
            }
        }

        return $result;
    }
}