<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Services;

use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

/**
 * Collection Filter Service
 * This class is responsible for filtering and processing collection items for displaying in the frontend.
 *
 * @since 5.5.8
 */
class FilterService
{

    /**
     * Fetch field values from all items in a collection by field ID.
     *
     * @param int $fieldId The field ID.
     * 
     * @since 5.5.8
     */
    public function fetchFieldValuesById(int $fieldId)
    {
       $values = CollectionItemValue::where('field_id', $fieldId)->get(['value']);

       return Arr::make($values)->map(function ($item) {
           return $item->toArray()['value'];
       })->toArray('value');
    }

    /**
     * Get the collection option fields data.
     *
     * @param int $fieldId The field ID.
     * @return array The collection option fields data.
     *
     * @since 5.5.0
     */
    public function getCollectionOptionFieldsData(int $fieldId)
    {
        $field = CollectionField::where('id', $fieldId)->where('type', FieldTypes::OPTION)->first(['options']);
        if ($field->isEmpty()) {
            return [];
        }

        if (empty($field->options)) {
            return [];
        }

        $options = Str::toArray($field->options);

        if (empty($options)) {
            return [];
        }

        return Arr::make($options)->reduce(function ($carry, $option) {
            $carry[$option['value']] = $option['label'];
            return $carry;
        }, [])->toArray();

        return $options ?? [];
    }


}