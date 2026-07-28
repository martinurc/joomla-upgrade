<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use JoomShaper\SPPageBuilder\DynamicContent\Constants\Conditions;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use Throwable;

class CollectionData
{
    /**
     * The collection items.
     *
     * @var array
     *
     * @since 5.5.0
     */
    protected $items;

    /**
     * The limit of the collection items.
     *
     * @var int
     *
     * @since 5.5.0
     */
    protected $limit = 20;

    /**
     * The page of the collection items.
     *
     * @var int
     *
     * @since 5.5.0
     */
    protected $page = 1;

    /**
     * The direction of the collection items.
     *
     * @var string
     *
     * @since 5.5.0
     */
    protected $direction = 'ASC';

    /**
     * The current item ID.
     *
     * @var int|null
     *
     * @since 5.5.0
     */
    protected $currentItemId = null;

    /**
     * The total items.
     *
     * @var int
     *
     * @since 5.5.0
     */
    protected $totalItems = 0;

    /**
     * The primary key of the collection items.
     *
     * @var string
     *
     * @since 5.5.0
     */
    protected const PRIMARY_KEY = 'id';

    /**
     * Class Constructor.
     *
     * @since 5.5.0
     */
    public function __construct()
    {
        $this->currentItemId = CollectionHelper::getCollectionItemIdFromUrl();
    }

    /**
     * Set the current item ID.
     * This item id is use for the reference filters.
     *
     * @param int $itemId The item ID to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setCurrentItemId($itemId)
    {
        $this->currentItemId = $itemId;
        return $this;
    }

    /**
     * Set data from outside.
     *
     * @param array $data The data to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setData($data)
    {
        $this->items = $data;
        return $this;
    }

    /**
     * Partition the filters by reference filters.
     *
     * @param object $filters The filters to partition.
     * @return array
     *
     * @since 5.5.0
     */
    public static function partitionByReferenceFilters($filters)
    {
        if (empty($filters) || empty($filters->conditions)) {
            return [null, null, false];
        }

        $hasReferenceFilters = false;
        $conditions = Arr::make($filters->conditions);
        $referenceConditions = $conditions->filter(function ($condition) {
            return $condition->condition === Conditions::IS_INCLUDE_PARENT && !empty($condition->variable);
        })->toArray();

        $regularConditions = $conditions->filter(function ($condition) {
            return $condition->condition !== Conditions::IS_INCLUDE_PARENT;
        })->toArray();

        $referenceFilters = (object) [
            'match' => $filters->match,
            'conditions' => $referenceConditions,
        ];

        $regularFilters = (object) [
            'match' => $filters->match,
            'conditions' => $regularConditions,
        ];

        $hasReferenceFilters = !empty($referenceConditions);

        return [$referenceFilters, $regularFilters, $hasReferenceFilters];
    }

    /**
     * Apply reference filters for all conditions.
     *
     * @param object $filters The filters to apply.
     * @param array $parentItem The parent item to apply the filters to.
     * @return array
     *
     * @since 5.5.0
     */
    public static function applyReferenceFiltersForMatchingAllConditions($filters, $parentItem)
    {
        if (empty($filters) || empty($filters->conditions) || empty($parentItem)) {
            return [];
        }

        $conditions = Arr::make($filters->conditions);
        $variables = $conditions->pluck('variable')->map(function($variable) {
            return CollectionItemsService::createFieldKey($variable);
        });

        $referenceValues = $variables->reduce(function ($carry, $variable) use ($parentItem) {
            $carry[$variable] = $parentItem[$variable] ?? [];
            return $carry;
        }, []);

        $length = $referenceValues->count();
        $counter = $referenceValues->reduce(function ($carry, $value) {
            foreach ($value as $item) {
                $carry[$item['id']] ??= 0;
                $carry[$item['id']]++;
            }
            return $carry;
        }, []);

        $referenceValues = $referenceValues->reduce(function ($carry, $value) {
            return array_merge($carry, $value);
        }, [])->filter(function ($value) use ($counter, $length) {
            return $counter[$value['id']] === $length;
        })->reduce(function ($carry, $value) {
            $carry[$value['id']] = $value;
            return $carry;
        }, [])->toArray();

        return array_values($referenceValues);
    }

    /**
     * Apply reference filters for any conditions.
     *
     * @param object $filters The filters to apply.
     * @param array $parentItem The parent item to apply the filters to.
     * @return array
     *
     * @since 5.5.0
     */
    public static function applyReferenceFiltersForMatchingAnyConditions($filters, $parentItem)
    {
        if (empty($filters) || empty($filters->conditions) || empty($parentItem)) {
            return [];
        }

        $conditions = Arr::make($filters->conditions);
        $variables = $conditions->pluck('variable')->map(function($variable) {
            return CollectionItemsService::createFieldKey($variable);
        });

        $referenceValues = $variables->reduce(function ($carry, $variable) use ($parentItem) {
            $carry[$variable] = $parentItem[$variable] ?? [];
            return $carry;
        }, []);

        $counter = $referenceValues->reduce(function ($carry, $value) {
            foreach ($value as $item) {
                $carry[$item['id']] ??= 0;
                $carry[$item['id']]++;
            }
            return $carry;
        }, []);

        $referenceValues = $referenceValues->reduce(function ($carry, $value) {
            return array_merge($carry, $value);
        }, [])->filter(function ($value) use ($counter) {
            return $counter[$value['id']] > 0;
        })->reduce(function ($carry, $value) {
            $carry[$value['id']] = $value;
            return $carry;
        }, [])->toArray();

        return array_values($referenceValues);
    }

    /**
     * Set the limit.
     *
     * @param int $limit The limit to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setLimit($limit)
    {
        $this->limit = intval($limit ?: -1);
        return $this;
    }

    /**
     * Set the page.
     *
     * @param int $page The page to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setPage($page)
    {
        $this->page = (int) $page;
        return $this;
    }

    /**
     * Set the direction.
     *
     * @param string $direction The direction to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Check a linear condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkLinearCondition($item, $condition)
    {
        $key = $condition->field->path ?? '';
        $key = !empty($key) ? CollectionItemsService::createFieldKey($key) : $key;
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $isCaseSensitive = $condition->is_case_sensitive ?? 0;
        $value = $item[$key] ?? null;
        

        if (!$isCaseSensitive) {
            $value = !empty($value) ? strtolower($value) : $value;
            $conditionValue = !empty($conditionValue) ? strtolower($conditionValue) : $conditionValue;
        }

        switch ($checker) {
            case Conditions::IS_SET:
                return isset($item[$key]);
            case Conditions::IS_NOT_SET:
                return !isset($item[$key]);
            case Conditions::IS_YES:
                return (int) $value === 1;
            case Conditions::IS_NO:
                return (int) $value === 0;
            case Conditions::EQUALS:
                return $value === $conditionValue;
            case Conditions::NOT_EQUALS:
                return $value !== $conditionValue;
            case Conditions::CONTAINS:
                return strpos($value, $conditionValue) !== false;
            case Conditions::NOT_CONTAINS:
                return strpos($value, $conditionValue) === false;
            case Conditions::STARTS_WITH:
                return strpos($value, $conditionValue) === 0;
            case Conditions::NOT_STARTS_WITH:
                return strpos($value, $conditionValue) !== 0;
            case Conditions::ENDS_WITH:
                return substr($value, -strlen($conditionValue)) === $conditionValue;
            case Conditions::NOT_ENDS_WITH:
                return substr($value, -strlen($conditionValue)) !== $conditionValue;
            case Conditions::IS_GREATER_THAN:
                return $value > $conditionValue;
            case Conditions::IS_LESS_THAN:
                return $value < $conditionValue;
            case Conditions::IS_GREATER_THAN_OR_EQUAL_TO:
                return $value >= $conditionValue;
            case Conditions::IS_LESS_THAN_OR_EQUAL_TO:
                return $value <= $conditionValue;
            case Conditions::IS_BEFORE:
                return strtotime($value) < strtotime($conditionValue);
            case Conditions::IS_AFTER:
                return strtotime($value) > strtotime($conditionValue);
            case Conditions::IS_BETWEEN_DATE:
                return strtotime($value) >= strtotime($conditionValue[0]) && strtotime($value) <= strtotime($conditionValue[1]);
            case Conditions::IS_NOT_BETWEEN_DATE:
                return strtotime($value) < strtotime($conditionValue[0]) || strtotime($value) > strtotime($conditionValue[1]);
            default:
                return true;
        }
    }

    /**
     * Check a non-linear condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkNonLinearCondition($item, $condition)
    {
        $fieldType = $condition->field->type ?? '';

        if (empty($fieldType) || !in_array($fieldType, ['self', 'reference', 'multi-reference'])) {
            return false;
        }

        if ($fieldType === 'self') {
            return $this->checkForSelfReference($item, $condition);
        }

        if ($fieldType === 'multi-reference') {
            return $this->checkForMultiReference($item, $condition);
        }

        return $this->checkForSingleReference($item, $condition);
    }

    /**
     * Check for multi-reference condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkForMultiReference($item, $condition)
    {
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $referenceItemIds = $this->getReferenceItemIdList($item['id'], $condition->field->id);

        if (empty($referenceItemIds)) {
            return false;
        }

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                return count(array_intersect($conditionValue, $referenceItemIds)) > 0;
            case Conditions::IS_NOT_INCLUDE:
                return count(array_intersect($conditionValue, $referenceItemIds)) === 0;
            case Conditions::EQUALS_IN_REFERENCE:
                return in_array($conditionValue, $referenceItemIds);
            case Conditions::NOT_EQUALS_IN_REFERENCE:
                return !in_array($conditionValue, $referenceItemIds);
            case Conditions::IS_ASSOCIATED_WITH:
                return in_array($this->currentItemId, $referenceItemIds);
        }
    }

    /**
     * Get the reference item ID.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return int|null
     *
     * @since 5.5.0
     */
    protected function getReferenceItemId($itemId, $fieldId)
    {
        $value = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->first(['reference_item_id']);

        if ($value->isEmpty()) {
            return null;
        }

        return $value->reference_item_id ?? null;
    }

    /**
     * Get the reference item ID list.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return array
     *
     * @since 5.5.0
     */
    protected function getReferenceItemIdList($itemId, $fieldId)
    {
        $values = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->get(['reference_item_id']);

        if (empty($values)) {
            return [];
        }

        return Arr::make($values)->pluck('reference_item_id')->toArray();
    }

    /**
     * Check for single-reference condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkForSingleReference($item, $condition)
    {
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $referenceItemId = $this->getReferenceItemId($item['id'], $condition->field->id);

        if (empty($referenceItemId)) {
            return false;
        }

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                return in_array($referenceItemId, $conditionValue);
            case Conditions::IS_NOT_INCLUDE:
                return !in_array($referenceItemId, $conditionValue);
            case Conditions::EQUALS_IN_REFERENCE:
                return (int) $referenceItemId === (int) $conditionValue;
            case Conditions::NOT_EQUALS_IN_REFERENCE:
                return (int) $referenceItemId !== (int) $conditionValue;
            case Conditions::IS_ASSOCIATED_WITH:
                return (int) $referenceItemId === (int) $this->currentItemId;
        }
    }

    /**
     * Check for self-reference condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkForSelfReference($item, $condition)
    {
        $key = static::PRIMARY_KEY;
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $value = $item[$key] ?? null;

        if (empty($value) || !is_array($conditionValue)) {
            return false;
        }

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                return in_array($value, $conditionValue);
            case Conditions::IS_NOT_INCLUDE:
                return !in_array($value, $conditionValue);
        }
    }

    /**
     * Check a condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function check($item, $condition)
    {
        $checker = $condition->condition ?? '';

        if (in_array($checker, Conditions::getLinearConditions())) {
            return $this->checkLinearCondition($item, $condition);
        }

        return $this->checkNonLinearCondition($item, $condition);
    }

    /**
     * Check if the item matches all conditions.
     *
     * @param array $item The item to check.
     * @param array $conditions The conditions to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function isMatchForAllConditions($item, $conditions)
    {
        $shouldPick = true;
        foreach ($conditions as $condition) {
            $shouldPick = $shouldPick && static::check($item, $condition);
        }

        return $shouldPick;
    }

    /**
     * Check if the item matches any conditions.
     *
     * @param array $item The item to check.
     * @param array $conditions The conditions to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function isMatchForAnyConditions($item, $conditions)
    {
        $shouldPick = false;
        foreach ($conditions as $condition) {
            $shouldPick = $shouldPick || static::check($item, $condition);
        }

        return $shouldPick;
    }

    /**
     * Load data by source.
     *
     * @param int $collectionId The collection ID to load data from.
     * @return self
     *
     * @since 5.5.0
     */
    public function loadDataBySource($collectionId)
    {
        try {
            $items = (new CollectionDataService)->fetchCollectionItems($collectionId, $this->direction);
        } catch (Throwable $error) {
            $items = [];
        }

        $this->items = $items;

        // Set the item count before slicing by limit.
        $this->totalItems = count($items);

        return $this;
    }

    /**
     * Apply filters to the data.
     *
     * @param object $filters The filters to apply.
     * @return self
     *
     * @since 5.5.0
     */
    public function applyFilters($filters)
    {
        $items = $this->items;

        if (empty($filters)) {
            return $this;
        }

        $match = $filters->match ?? Conditions::MATCH_ALL;
        $conditions = $filters->conditions ?? [];

        if (empty($conditions)) {
            return $this;
        }

        $items = Arr::make($items)->filter(function ($item) use ($conditions, $match) {
            return $match === Conditions::MATCH_ALL
                ? $this->isMatchForAllConditions($item, $conditions)
                : $this->isMatchForAnyConditions($item, $conditions);
        })->toArray();

        $this->items = $items;

        // Set the item count before slicing by limit.
        $this->totalItems = count($items);

        return $this;
    }

    /**
     * Get the data.
     *
     * @return array
     *
     * @since 5.5.0
     */
    public function getData()
    {
        return $this->applyPagination($this->items);
    }

    /**
     * Apply pagination to the items.
     *
     * @param array $items The items to apply pagination to.
     * @return array
     *
     * @since 5.5.0
     */
    public function applyPagination($items)
    {
        $limit = $this->limit;

        if ($limit < 0) {
            return $items;
        }

        $page = $this->page;
        $offset = $limit * ($page - 1);

        return array_slice($items, $offset, $limit);
    }

    /**
     * Get the total items.
     *
     * @return int
     *
     * @since 5.5.0
     */
    public function getItemCount()
    {
        return $this->totalItems;
    }

    /**
     * Get the total pages.
     *
     * @return int
     *
     * @since 5.5.0
     */
    public function getTotalPages()
    {
        return ceil($this->totalItems / $this->limit);
    }
}
