<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Services;

use JoomShaper\SPPageBuilder\DynamicContent\Constants\Conditions;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Model;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;
use Throwable;

/**
 * Collection Data Service
 * This class is responsible for fetching and preparing collection items for displaying in the frontend.
 *
 * @since 5.5.0
 */
class CollectionDataService
{
    /**
     * Fetch collection items for the given collection id.
     *
     * @param int $collectionId
     * @return array
     * @throws Throwable
     *
     * @since 5.5.0
     */
    public function fetchCollectionItems(int $collectionId, string $direction = 'ASC')
    {
        try {
            $items = $this->getCollectionItemsByCollectionId($collectionId, $direction);
            $items = Arr::make($items);
            return $this->prepareCollectionItems($items);
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Fetch collection item by its id.
     *
     * @param int $itemId
     * @return array|null
     *
     * @since 5.5.0
     */
    public function fetchCollectionItemById(int $itemId)
    {
        $item = CollectionItem::where('id', $itemId)
            ->with([
                'values' => function ($query) {
                    $query = $query->select([
                        'field_name' => 'collection_field.name',
                        'field_type' => 'collection_field.type'
                    ]);

                    return $query->leftJoin(
                        CollectionField::class,
                        'collection_item_value.field_id',
                        'collection_field.id'
                    );
                }
            ])->first();

        if ($item->isEmpty()) {
            return null;
        }

        return $this->prepareCollectionIndividualItem($item, true);
    }

    /**
     * Fetch collection items by their IDs.
     *
     * @param array $itemIds The IDs of the items to fetch.
     * @param string $direction The direction of the order.
     * @return array The fetched items.
     *
     * @since 5.5.0
     */
    public function fetchCollectionItemsByItemIds(array $itemIds, string $direction = 'ASC')
    {
        $items = CollectionItem::whereIn('id', $itemIds)
            ->where('published', 1)
            ->orderBy('ordering', $direction)
            ->with([
                'values' => function ($query) {
                    $query = $query->select([
                        'field_name' => 'collection_field.name',
                        'field_type' => 'collection_field.type'
                    ]);

                    return $query->leftJoin(
                        CollectionField::class,
                        'collection_item_value.field_id',
                        'collection_field.id'
                    );
                }
            ])->get();
        
        if (empty($items)) {
            return [];
        }

        $items = Arr::make($items);

        return $this->prepareCollectionItems($items);
    }

    /**
     * Get collection reference items on demand.
     *
     * @param array $item The item to get reference items for.
     * @param object $filters The filters to apply.
     * @param string $direction The direction of the order.
     * @return array The fetched items.
     *
     * @since 5.5.0
     */
    public function getCollectionReferenceItemsOnDemand($item, $filters, string $direction = 'ASC')
    {
        if (empty($item) || empty($filters) || empty($filters->conditions)) {
            return [];
        }

        $parentItemId = $item['id'];
        $conditions = $filters->conditions;
        $conditions = Arr::make($conditions);
        $itemIds = $conditions->map(function ( $condition) use ($parentItemId) {
            $fieldId = $condition->variable;
            return $this->getReferenceItemIds($parentItemId, $fieldId);
        })->toArray();

        $itemIds = $filters->match === Conditions::MATCH_ALL
            ? array_intersect(...$itemIds)
            : array_unique(array_merge(...$itemIds));

        if (empty($itemIds)) {
            return [];
        }

        return $this->fetchCollectionItemsByItemIds($itemIds, $direction);
    }

    /**
     * Get the reference item IDs.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return array The reference item IDs.
     *
     * @since 5.5.0
     */
    protected function getReferenceItemIds($itemId, $fieldId)
    {
        $itemIds = CollectionItemValue::where('field_id', $fieldId)
            ->where('item_id', $itemId)
            ->get(['reference_item_id']);

        if (empty($itemIds)) {
            return [];
        }

        return Arr::make($itemIds)->pluck('reference_item_id')->toArray();
    }

    /**
     * Prepare item values by handling reference values.
     * This method visits each value and checks if it has a reference item id.
     * If it does, it sets the is_reference flag to true and assigns the reference item id to the value.
     *
     * @param Arr $values
     * @return Arr
     *
     * @since 5.5.0
     */
    protected function checkReferenceValues(Arr $values)
    {
        return $values->map(function ($value) {
            $value['is_reference']          = $value['field_type'] === FieldTypes::REFERENCE;
            $value['is_multi_reference']    = $value['field_type'] === FieldTypes::MULTI_REFERENCE;

            if (!empty($value['reference_item_id'])) {
                $value['value'] = [$value['reference_item_id']];
            }

            return $value;
        });
    }

    /**
     * Structure collection item values.
     * This method visits each value and checks if it has a reference item id.
     * If it does, it sets the is_reference flag to true and assigns the reference item id to the value.
     *
     * @param Arr $values
     * @return Arr
     *
     * @since 5.5.0
     */
    protected function structureCollectionItem(Arr $values)
    {
        return $values->reduce(function ($carry, $value) {
            $key = CollectionItemsService::FIELD_KEY_PREFIX . $value['field_id'];

            if (!in_array($value['field_type'], [FieldTypes::REFERENCE, FieldTypes::MULTI_REFERENCE])) {
                $carry[$key] = $value['value'];
            }

            return $carry;
        }, []);
    }

    /**
     * Prepare collection items for displaying in the frontend.
     *
     * @param Arr $items
     * @return array
     *
     * @since 5.5.0
     */
    protected function prepareCollectionItems(Arr $items)
    {
        return $items->map(function ($item) {
            $item = $this->prepareCollectionIndividualItem($item);
            $item['url'] = CollectionHelper::createRouteUrl($item);
            return $item;
        })->toArray();
    }

    /**
     * Get the collection option fields data.
     *
     * @param int $fieldId The field ID.
     * @return array The collection option fields data.
     *
     * @since 5.5.0
     */
    protected function getCollectionOptionFieldsData(int $fieldId)
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

    /**
     * Prepare a single collection item for displaying in the frontend.
     *
     * @param mixed $item
     * @return array
     *
     * @since 5.5.0
     */
    protected function prepareCollectionIndividualItem($item)
    {
        if ($item instanceof Model) {
            $item = $item->toArray();
        }

        $values = Arr::make($item['values']);
        $item['option_store'] ??= [];

        foreach ($values as $value) {
            if ($value['field_type'] === FieldTypes::OPTION) {
                $item['option_store'] = array_merge($item['option_store'], $this->getCollectionOptionFieldsData($value['field_id'], $value['value']));
            }
        }

        $values = $this->structureCollectionItem($this->checkReferenceValues($values))->toArray();
        unset($item['values']);

        return array_merge($item, $values);
    }

    /**
     * Get collection items by collection id.
     *
     * @param int $collectionId
     * @return array
     *
     * @since 5.5.0
     */
    protected function getCollectionItemsByCollectionId(int $collectionId, string $direction = 'ASC')
    {
        $items = CollectionItem::where('collection_id', $collectionId)
            ->where('published', 1)
            ->orderBy('ordering', $direction)
            ->with([
                'values' => function ($query) {
                    $query = $query->select([
                        'field_name' => 'collection_field.name',
                        'field_type' => 'collection_field.type'
                    ]);

                    return $query->leftJoin(
                        CollectionField::class,
                        'collection_item_value.field_id',
                        'collection_field.id'
                    );
                }
            ])->get();

        return $items;
    }
}
