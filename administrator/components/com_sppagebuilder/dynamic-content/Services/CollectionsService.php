<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Services;

defined('_JEXEC') or die;

use Exception;
use Joomla\String\StringHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Exceptions\ValidatorException;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\QueryBuilder;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;
use Throwable;

class CollectionsService
{
    use Validator;

    /**
     * Available number formats
     * 
     * @var array
     * @since 5.5.0
     */
    public const NUMBER_FORMATS = ['any', 'integer', 'decimal'];

    /**
     * Create the collection record
     * 
     * @param array $data The data
     * 
     * @return int
     * @since 5.5.0
     */
    public function createRecord(array $data)
    {
        $this->validateCollectionData($data);

        if ($this->hasErrors()) {
            throw new ValidatorException($this->getErrors());
        }

        $fields = Str::toArray($data['fields']);
        unset($data['fields']);
        $fields = Arr::make($fields);

        QueryBuilder::beginTransaction();

        $data['title'] = $this->createUniqueTitle($data['title']);
        $data['alias'] = $this->createUniqueAlias($data['title'], $data['alias']);

        try
        {
            $collectionId = Collection::create([
                'title'      => $data['title'],
                'alias'      => $data['alias'],
                'published'  => 1,
                'access'     => 1,
                'language'   => '*',
                'created'    => Date::sqlSafeDate(),
                'modified'   => Date::sqlSafeDate(),
                'created_by' => getCurrentLoggedInUser()->id
            ]);

            if (!$collectionId) {
                QueryBuilder::rollback();
                throw new Exception('Failed to create collection', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->createCollectionFieldsSchema($fields, $collectionId);
            QueryBuilder::commit();

            return $collectionId;
        }
        catch (Throwable $error)
        {
            QueryBuilder::rollback();
            throw $error;
        }
    }

    /**
     * Update the collection record
     * 
     * @param array $data The data
     * 
     * @return bool
     * @since 5.5.0
     */
    public function updateRecord(array $data)
    {
        $this->validateCollectionData($data);

        if ($this->hasErrors()) {
            throw new ValidatorException($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $fields = Str::toArray($data['fields']);
        unset($data['fields']);

        $fields = Arr::make($fields);
        $collectionId = $data['id'];

        QueryBuilder::beginTransaction();

        $collection = Collection::where('id', $collectionId)->first();

        if ($collection->isEmpty()) {
            throw new Exception('Collection not found', Response::HTTP_NOT_FOUND);
        }

        // If no alias is provided, that means we are planning to create a alias from the title
        // If the alias is already exists, then we will use the existing alias
        // If the alias is not exists, then we will create a new one
        if ($collection->alias !== $data['alias']) {
            if (empty($data['alias'])) {
                $data['alias'] = $collection->alias ?: $this->createUniqueAlias($data['title'], $data['alias']);
            }
        }

        // If there is no collection alias, and we are not passing any alias
        // Then we will create a new alias from the title
        if (empty($data['alias']) && empty($collection->alias)) {
            $data['alias'] = $this->createUniqueAlias($data['title'], $data['alias']);
        }

        try {
            $data = [
                'id'          => $collectionId,
                'title'       => $data['title'],
                'alias'       => $data['alias'],
                'published'   => $data['published'] ?? 1,
                'access'      => $data['access'] ?? 1, 
                'language'    => $data['language'] ?? '*',
                'modified'    => Date::sqlSafeDate(),
                'modified_by' => getCurrentLoggedInUser()->id,
            ];

            Collection::where('id', $collectionId)->update($data);
            $this->syncCollectionFields($collectionId, $fields);

            QueryBuilder::commit();
            return true;
        } catch (Throwable $error) {
            QueryBuilder::rollback();
            throw $error;
        }
    }

    /**
     * Fetch all the collections
     * 
     * @return array
     * @since 5.5.0
     */
    public function fetchAll()
    {
        $primaryFieldValues = $this->getPrimaryFieldsItemValues();

        try {
            $collections = Collection::where('published', 1)
                ->orderBy('ordering', 'ASC')
                ->get(['id', 'title', 'alias']);
        } catch (Throwable $error) {
            throw $error;
        }

        if (empty($collections)) {
            return [];
        }

        foreach ($collections as $collection) {
            $collection->items = $primaryFieldValues[$collection->id] ?? [];
            $collection->total_items = CollectionItem::where('collection_id', $collection->id)->count();
        }

        return $collections;
    }

    /**
     * Get the collection schema.
     * 
     * @param int $collectionId The collection ID.
     * 
     * @return array
     * @since 5.5.0
     */
    public function fetchCollectionSchema(int $collectionId)
    {
        $fields = CollectionField::where('collection_id', $collectionId)
            ->orderBy('ordering', 'ASC')
            ->get(CollectionField::COMMON_COLUMNS);

        if (empty($fields)) {
            return [];
        }

        $fields = Arr::make($fields)->map(function ($field) {
            $field->options = Str::toArray($field->options);
            $field->file_extensions = Str::toArray($field->file_extensions);
            $field->default_value = Str::process($field->default_value);
            return $field;
        })->toArray();

        return $fields;
    }

    /**
     * Duplicate an existing collection along with the fields and items.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return int
     * @since 5.5.0
     */
    public function duplicateCollection(int $collectionId)
    {
        $collection = Collection::where('id', $collectionId)->with(['fields', 'items'])->first();

        if ($collection->isEmpty()) {
            throw new Exception('Collection not found', Response::HTTP_NOT_FOUND);
        }

        QueryBuilder::beginTransaction();

        $newTitle = $this->createUniqueTitle($collection->title);
        $newAlias = $this->createUniqueAlias($newTitle, $collection->alias);

        $newCollectionData = [
            'title'      => $newTitle,
            'alias'      => $newAlias,
            'published'  => 1,
            'access'     => $collection->access ?? 1,
            'language'   => $collection->language ?? '*', 
            'created'    => Date::sqlSafeDate(),
            'modified'   => Date::sqlSafeDate(),
            'created_by' => getCurrentLoggedInUser()->id,
        ];

        try
        {
            $newCollectionId = Collection::create($newCollectionData);
            $fieldsMap = $this->duplicateCollectionFields($collection->fields, $newCollectionId);
            $itemsMap = $this->duplicateCollectionItems($collection->items, $newCollectionId);
            $this->duplicateCollectionItemValues($itemsMap, $fieldsMap);

            QueryBuilder::commit();
            return $newCollectionId;
        }
        catch (Throwable $error)
        {
            QueryBuilder::rollback();
            throw $error;
        }
    }

    /**
     * Fetch the collection field structure.
     * 
     * @param int $collectionId The collection ID.
     * 
     * @return array
     * @since 5.5.0
     */
    public function fetchCollectionAttributes(int $collectionId, array $allowedTypes = [], int $level = 1, string $parentPath = null)
    {
        $attributes = [];

        $fields = Collection::where('id', $collectionId)
            ->with(['fields' => function ($query) {
                return $query->orderBy('ordering', 'ASC');
            }])
            ->first();

        if (empty($fields)) {
            return [];
        }

        $attributes = [
            'id' => 0,
            'name' => $fields->title,
            'type' => FieldTypes::REFERENCE,
            'fields' => [],
            'level' => $level,
            'selector' => null
        ];

        foreach ($fields->fields as $field) {
            if (!in_array($field->type, $allowedTypes)) {
                continue;
            }

            $path = $parentPath ? $parentPath . '.' . $field->id : (string) $field->id;
            $structuredField = [
                'id' => $field->id,
                'name' => $field->name,
                'fullname' => $fields->title . ' > ' . $field->name,
                'type' => $field->type,
                'fields' => [],
                'level' => $level + 1,
                'path' => $path
            ];

            if (in_array($field->type, [FieldTypes::REFERENCE, FieldTypes::MULTI_REFERENCE], true) && !CollectionHelper::hasCircularReference($field->id)) {
                $structuredField['fields'] = !empty($field->reference_collection_id)
                    ? $this->fetchCollectionAttributes(
                        $field->reference_collection_id, 
                        $allowedTypes, 
                        $level + 1, 
                        $path
                    )['fields']
                    : [];

                // If no allowed types are provided, then we don't need to show the reference fields
                if (empty($structuredField['fields'])) {
                    continue;
                }
            }

            $attributes['fields'][] = $structuredField;
        }

        if (in_array('created', $allowedTypes)) {
            $createdFieldPath = $parentPath ? $parentPath . '.created' : 'created';
            $attributes['fields'][] = [
                'id' => Str::uuid(),
                'name' => 'Creation Date',
                'fullname' => $fields->title . ' > Creation Date',
                'type' => 'date-time',
                'fields' => [],
                'level' => $level + 1,
                'path' => $createdFieldPath
            ];
        }

        return $attributes;
    }

    /**
     * Fetch the collection fields.
     *
     * @param int $collectionId The collection ID.
     * @return array The fetched fields.
     *
     * @since 5.5.0
     */
    public function fetchCollectionFields(int $collectionId)
    {
        $collection = Collection::where('id', $collectionId)
            ->with(['fields' => function ($query) {
                return $query->orderBy('ordering', 'ASC');
            }])
            ->first();

        if (empty($collection)) {
            return [];
        }

        $fields = Arr::make($collection->fields ?? []);
        $fields = $fields->map(function ($field) {
            return [
                'id' => $field->id,
                'name' => $field->name,
                'type' => $field->type,
                'path' => $field->id,
                'reference_items' => $this->getReferenceItems($field->reference_collection_id),
                'reference_collection_name' => $this->getReferenceCollectionName($field->reference_collection_id),
                'option_field_values' => $field->type === FieldTypes::OPTION ? $this->getOptionItems($field->id) : [],
            ];
        });

        $fields->prepend([
            'id' => 0,
            'name' => $collection->title,
            'type' => 'self',
            'path' => 0,
            'reference_items' => $this->getReferenceItems($collectionId),
            'reference_collection_name' => [[
                'id' => $collectionId,
                'title' => $collection->title,
            ]],
            'option_field_values' => []
        ]);

        return $fields->toArray();
    }

    /**
     * Check before deleting the collection.
     *
     * @param int $collectionId The collection ID.
     * @return bool True if the collection can be deleted, false otherwise.
     *
     * @since 5.5.0
     */
    public function checkBeforeDeleting(int $collectionId)
    {
        if ($this->isCollectionHasItems($collectionId)) {
            throw new Exception('Collection has items', Response::HTTP_BAD_REQUEST);
        }

        if ($this->isCollectionUsedInPage($collectionId)) {
            throw new Exception('Collection is used in page', Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Check if the collection has items.
     *
     * @param int $collectionId The collection ID.
     * @return bool True if the collection has items, false otherwise.
     *
     * @since 5.5.0
     */
    public function isCollectionHasItems(int $collectionId)
    {
        $items = CollectionItem::where('collection_id', $collectionId)->count();
        return $items > 0;
    }

    /**
     * Check if the collection is used in a page.
     *
     * @param int $collectionId The collection ID.
     * @return bool True if the collection is used in a page, false otherwise.
     *
     * @since 5.5.0
     */
    public function isCollectionUsedInPage(int $collectionId)
    {
        $pages = Page::where('extension', 'com_sppagebuilder')
            ->whereIn(
                'extension_view', [Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX, Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL]
            )
            ->where('view_id', $collectionId)
            ->count();

        return $pages > 0;
    }

    /**
     * Check if the collection is reference to other collection.
     *
     * @param int $collectionId The collection ID.
     * @return bool True if the collection is reference to other collection, false otherwise.
     *
     * @since 5.5.0
     */
    public function isCollectionReferenceToOtherCollection(int $collectionId)
    {
        $fields = CollectionField::where('reference_collection_id', $collectionId)->count();
        return $fields > 0;
    }

    /**
     * Get the option type field values.
     * 
     * @param int $fieldId The field ID.
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getOptionItems($fieldId)
    {
        $options = CollectionField::where('id', $fieldId)->first(['options']);

        if ($options->isEmpty()) {  
            return [];
        }

        $options = Str::toArray($options->options);

        return $options;
    }

    /**
     * Get the reference collection name.
     * 
     * @param int $collectionId The collection ID.
     * 
     * @return string
     * @since 5.5.0
     */
    protected function getReferenceCollectionName($collectionId)
    {
        if (empty($collectionId)) {
            return null;
        }

        $collection = Collection::where('id', $collectionId)->first();
        $title = $collection->title ?? null;

        if (empty($title)) {
            return [];
        }

        return [[
            'id' => $collectionId,
            'title' => $title,
        ]];
    }

    /**
     * Get the reference items.
     * 
     * @param int $collectionId The collection ID.
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getReferenceItems($collectionId)
    {
        if (empty($collectionId)) {
            return [];
        }

        $service = new CollectionItemsService();
        $items = $service->fetchItemsByCollectionId($collectionId);
        $primaryFieldId = $this->getPrimaryFieldForReferenceCollection($collectionId);

        if (empty($items) || empty($primaryFieldId)) {
            return [];
        }

        $primaryField = CollectionItemsService::createFieldKey($primaryFieldId);
        $items = Arr::make($items);
        $items = $items->map(function ($item) use ($primaryField) {
            return [
                'id' => $item['id'],
                'title' => $item[$primaryField],
            ];
        })->toArray();

        return $items;
    }

    /**
     * Get the primary field for the reference collection.
     * 
     * @param int $collectionId The collection ID.
     * 
     * @return int|null The primary field ID or null if not found.
     * @since 5.5.0
     */
    protected function getPrimaryFieldForReferenceCollection(int $collectionId)
    {
        $field = CollectionField::where('collection_id', $collectionId)
            ->where('type', CollectionItemsService::PRIMARY_FIELD_TYPE)
            ->first();

        return $field->id ?? null;
    }

    /**
     * Get the item values for the primary fields
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getPrimaryFieldsItemValues()
    {
        try {
            $fields = CollectionField::where('type', CollectionItemsService::PRIMARY_FIELD_TYPE)
                ->leftJoin(CollectionItemValue::class, 'field_id', 'id')
                ->orderBy( 'collection_item_value.item_id', 'ASC')
                ->get(['id', 'collection_id', 'value', 'item_id']);
            $primaryFieldsMap = [];
            foreach ($fields as $field) {
                if (empty($field->item_id) || empty($field->value)) {
                    continue;
                }

                $collectionId = $field->collection_id;
                $primaryFieldsMap[$collectionId][] = [
                    'value'    => $field->item_id,
                    'label' => $field->value,
                ];
            }

            return $primaryFieldsMap;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Validate the collection data
     * 
     * @param array $data The data
     * 
     * @return void
     * @since 5.5.0
     */
    protected function validateCollectionData(array $data)
    {
        $data['fields'] = Str::toArray($data['fields']);
        $this->validate($data, [
            'title'     => 'required|string|min:3|max:255',
            'fields'    => 'required|array',
        ]);

        $fields = Arr::make($data['fields']);
        unset($data['fields']);

        foreach ($fields as $field) {
            $this->validate($field, [
                'name'           => 'required|string|min:3|max:255',
                'type'           => 'required|string|in:' . (new FieldTypes)->toString(),
                'required'       => 'in:0,1',
                'description'    => 'string', 
                'max_length'     => 'integer',
                'min_length'     => 'integer',
                'placeholder'    => 'string',
                'allow_negative' => 'integer|in:0,1',
                'number_unit'    => 'string',
                'number_step'    => 'number',
                'number_format'  => 'string|in:' . implode(',', static::NUMBER_FORMATS),
                'options'        => 'array',
                'file_extensions'=> 'array',
            ]);
        }
    }

    /**
     * Create the collection fields schema
     * 
     * @param Arr $fields The fields
     * @param int $collectionId The collection ID
     * 
     * @return array
     * @since 5.5.0
     */
    protected function createCollectionFieldsSchema(Arr $fields, int $collectionId)
    {
        $fieldsArray = $fields->map(function($field) use ($collectionId) {
            return $this->prepareFieldRecord($field, $collectionId);
        })->toArray();

        try
        {
            $response = CollectionField::createMany($fieldsArray);
        }
        catch (Throwable $error)
        {
            throw $error;
        }

        return $response;
    }

    /**
     * Delete the collection fields for the given collection ID
     * 
     * @param int $collectionId The collection ID
     * 
     * @return bool
     * @since 5.5.0
     */
    protected function deleteCollectionFieldsFor(int $collectionId)
    {
        try
        {
            CollectionField::where('collection_id', $collectionId)->delete();
        }
        catch (Throwable $error)
        {
            throw $error;
        }

        return true;
    }

    /**
     * Make prepared the field table's records to store.
     * 
     * @param array $field The field
     * @param int $collectionId The collection ID
     * 
     * @return array
     * @since 5.5.0
     */
    protected function prepareFieldRecord(array $field, int $collectionId = null)
    {
        $options = !empty($field['options']) ? $field['options'] : null;
        $options = is_array($options) ? json_encode($options) : $options;

        $defaultValue = !empty($field['default_value']) ? $field['default_value'] : null;
        $defaultValue = is_array($defaultValue) ? json_encode($defaultValue) : $defaultValue;

        $fileExtensions = !empty($field['file_extensions']) ? $field['file_extensions'] : null;
        $fileExtensions = is_array($fileExtensions) ? json_encode($fileExtensions) : $fileExtensions;

        $fieldId = $field['id'] ?? null;
        $isNew = empty($fieldId);

        $processedData = [
            'id'                        => $fieldId,
            'collection_id'             => (int) $collectionId,
            'name'                      => $field['name'],
            'type'                      => $field['type'],
            'description'               => !empty($field['description']) ? $field['description'] : null,
            'options'                   => $options,
            'max_length'                => !empty($field['max_length']) ? $field['max_length'] : null,
            'min_length'                => !empty($field['min_length']) ? $field['min_length'] : null,
            'default_value'             => $defaultValue,
            'placeholder'               => !empty($field['placeholder']) ? $field['placeholder'] : null,
            'required'                  => !empty($field['required']) ? intval($field['required']) : 0,
            'reference_collection_id'   => !empty($field['reference_collection_id']) ? $field['reference_collection_id'] : null,
            'is_textarea'               => !empty($field['is_textarea']) ? intval($field['is_textarea']) : 0,
            'show_time'                 => !empty($field['show_time']) ? intval($field['show_time']) : 0,
            'file_extensions'           => $fileExtensions,
            'number_format'             => !empty($field['number_format']) ? $field['number_format'] : null,
            'allow_negative'            => !empty($field['allow_negative']) ? intval($field['allow_negative']) : 0,
            'number_unit'               => !empty($field['number_unit']) ? $field['number_unit'] : null,
            'number_step'               => !empty($field['number_step']) ? $field['number_step'] : null,
        ];

        if ($isNew) {
            $processedData['created'] = Date::sqlSafeDate();
            $processedData['modified'] = Date::sqlSafeDate();
            $processedData['created_by'] = getCurrentLoggedInUser()->id;
        } else {
            $processedData['modified'] = Date::sqlSafeDate();
            $processedData['modified_by'] = getCurrentLoggedInUser()->id;
        }

        return $processedData;
    }

    /**
     * Sync the collection fields.
     * This method will make sure the functionalities like-
     * - Delete the fields that are not in the request
     * - Create the new fields
     * - Update the existing fields
     * 
     * @param int $collectionId The collection ID
     * @param Arr $fields The fields
     * 
     * @return void
     * @since 5.5.0
     */
    protected function syncCollectionFields(int $collectionId, Arr $fields)
    {
        $fieldsWithId = $fields->filter(function ($item) {
            return !empty($item['id']);
        });

        $fieldsToUpdate = $fieldsWithId->pluck('id')->toArray();

        $savedFields = CollectionField::where('collection_id', $collectionId)->get(['id']);
        $existingFields = Arr::make($savedFields)->pluck('id')->toArray();

        $fieldsToDelete = Arr::make($existingFields)->diff($fieldsToUpdate)->toArray();
        $fieldsToCreate = $fields->filter(function ($item) {
                return empty($item['id']);
            })->map(function ($field) use ($collectionId) {
                $field['id'] = null;
                return $this->prepareFieldRecord($field, $collectionId);
            })->toArray();

        try
        {
            if (!empty($fieldsToDelete)) {
                CollectionField::whereIn('id', $fieldsToDelete)->delete();
            }

            if (!empty($fieldsToCreate)) {
                CollectionField::createMany($fieldsToCreate);
            }

            if (!empty($fieldsToUpdate)) {
                foreach ($fieldsWithId as $field) {
                    if (in_array($field['id'], $fieldsToUpdate)) {
                        CollectionField::where('id', $field['id'])
                            ->update($this->prepareFieldRecord($field, $collectionId));
                    }
                }
            }
        }
        catch (Throwable $error)
        {
            throw $error;
        }
    }

    /**
     * Duplicate the collection fields
     * 
     * @param array $fields The fields
     * @param int $newCollectionId The new collection ID
     * 
     * @return array
     * @since 5.5.0
     */
    protected function duplicateCollectionFields($fields, int $newCollectionId)
    {
        if (empty($fields) || !is_array($fields)) {
            return [];
        }

        $fields = Arr::make($fields);

        $newFieldsData = $fields->map(function ($field) use ($newCollectionId) {
            return [
                'id'                     => $field->id,
                'collection_id'          => $newCollectionId,
                'name'                   => $field->name,
                'type'                   => $field->type,
                'description'            => !empty($field->description) ? $field->description : null,
                'options'                => !empty($field->options) ? $field->options : null,
                'max_length'             => !empty($field->max_length) ? $field->max_length : null,
                'min_length'             => !empty($field->min_length) ? $field->min_length : null,
                'default_value'          => !empty($field->default_value) ? $field->default_value : null,
                'placeholder'            => !empty($field->placeholder) ? $field->placeholder : null,
                'required'               => !empty($field->required) ? intval($field->required) : 0,
                'reference_collection_id'=> !empty($field->reference_collection_id) ? $field->reference_collection_id : null,
                'is_textarea'            => !empty($field->is_textarea) ? intval($field->is_textarea) : 0,
                'show_time'              => !empty($field->show_time) ? intval($field->show_time) : 0,
                'file_extensions'        => !empty($field->file_extensions) ? $field->file_extensions : null,
                'number_format'          => !empty($field->number_format) ? $field->number_format : null,
                'allow_negative'         => !empty($field->allow_negative) ? intval($field->allow_negative) : 0,
                'number_unit'            => !empty($field->number_unit) ? $field->number_unit : null,
                'number_step'            => !empty($field->number_step) ? $field->number_step : null,
                'created'                => Date::sqlSafeDate(),
                'created_by'             => getCurrentLoggedInUser()->id,
            ];
        });

        $oldNewFieldsMap = [];

        try {
            foreach ($newFieldsData as $field) {
                $oldFieldId = $field['id'];
                unset($field['id']);
                $newFieldId = CollectionField::create($field);
                $oldNewFieldsMap[$oldFieldId] = $newFieldId;
            }

            return $oldNewFieldsMap;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Create a unique title
     * 
     * @param string $title The title
     * 
     * @return string
     * @since 5.5.0
     */
    protected function createUniqueAlias(string $title, string $alias)
    {
        if (empty($alias)) {
            $alias = Str::safeUrl($title);
        }

        while (Collection::where('alias', $alias)->exists()) {
            $alias = StringHelper::increment($alias);
        }

        return $alias;
    }

    /**
     * Create a unique title
     * 
     * @param string $title The title
     * 
     * @return string
     * @since 5.5.0
     */
    protected function createUniqueTitle(string $title)
    {
        while (Collection::where('title', $title)->exists()) {
            $title = StringHelper::increment($title);
        }

        return $title;
    }

    /**
     * Duplicate the collection items
     * 
     * @param array $items The items
     * @param int $newCollectionId The new collection ID
     * 
     * @return array
     * @since 5.5.0
     */
    protected function duplicateCollectionItems($items, int $newCollectionId)
    {
        if (empty($items) || !is_array($items)) {
            return [];
        }

        $items = Arr::make($items)->map(function ($item) use ($newCollectionId) {
            return [
                'id'            => $item->id,
                'collection_id' => $newCollectionId,
                'published'     => $item->published ?? 1,
                'access'        => $item->access ?? 1,
                'language'      => $item->language ?? '*',
                'created'       => Date::sqlSafeDate(),
                'created_by'    => getCurrentLoggedInUser()->id,
            ];
        });

        $oldNewItemsMap = [];

        try {
            foreach ($items as $item) {
                $oldItemId = $item['id'];
                unset($item['id']);
                $newItemId = CollectionItem::create($item);
                $oldNewItemsMap[$oldItemId] = $newItemId;
            }

            return $oldNewItemsMap;
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Duplicate the collection item values
     * 
     * @param array $itemsMap The items map
     * @param array $fieldsMap The fields map
     * 
     * @return bool
     * @since 5.5.0
     */
    protected function duplicateCollectionItemValues(array $itemsMap, array $fieldsMap)
    {
        try {
            foreach ($itemsMap as $oldItemId => $newItemId) {
                $itemValues = CollectionItemValue::where('item_id', $oldItemId)->get();

                if (empty($itemValues)) {
                    continue;
                }

                $itemValues = Arr::make($itemValues)->map(function ($itemValue) use ($newItemId, $fieldsMap) {  
                    $oldFieldId = $itemValue->field_id;
                    $newFieldId = $fieldsMap[$oldFieldId];

                    return [
                        'item_id' => $newItemId,
                        'field_id' => $newFieldId,
                        'value' => $itemValue->value,
                        'reference_item_id' => $itemValue->reference_item_id ?? null,
                    ];
                });

                CollectionItemValue::createMany($itemValues->toArray());
            }

            return true;
        } catch (Throwable $error) {
            throw $error;
        }
    }
}
