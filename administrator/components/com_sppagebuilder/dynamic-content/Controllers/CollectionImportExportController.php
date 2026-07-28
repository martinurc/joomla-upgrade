<?php
/*
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Controllers;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Language\Text;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Controller;
use JoomShaper\SPPageBuilder\DynamicContent\Exceptions\ValidatorException;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\QueryBuilder;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

class CollectionImportExportController extends Controller
{
    use Validator;

    /**
     * Export the collection items.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function export(Request $request)
    {
    }

    /**
     * Import the collection items.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function import(Request $request)
    {
        $importStructure = $request->getRaw('data');

        if (empty($importStructure)) {
            return response()->json(['message' => 'No data provided'], Response::HTTP_BAD_REQUEST);
        }

        $importStructure = Str::toArray($importStructure);

        $this->validate($importStructure, [
            'title' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'fields' => 'required|array',
            'items' => 'required|array',
        ]);

        if ($this->hasErrors()) {
            return response()->json($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $collectionData = [
            'title' => $importStructure['title'],
            'alias' => $importStructure['alias'],
            'fields' => $importStructure['fields'],
            'published' => 1,
            'access'    => 1,
            'language'  => '*',
        ];

        $collectionsService = new CollectionsService();
        $collectionItemsService = new CollectionItemsService();
        $items = $importStructure['items'];

        QueryBuilder::beginTransaction();

        try
        {
            $collectionId = $collectionsService->createRecord($collectionData);

            if (!$collectionId) {
                QueryBuilder::rollback();
                return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_IMPORT_EXPORT_FAILED_TO_CREATE_COLLECTION')], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $createdFields = CollectionField::where('collection_id', $collectionId)
                ->orderBy('id', 'ASC')
                ->get(['id']);

            if (empty($createdFields)) {
                QueryBuilder::rollback();
                return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_IMPORT_EXPORT_FAILED_TO_CREATE_COLLECTION_FIELDS')], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $fieldIds = Arr::make($createdFields)->pluck('id');

            if (empty($fieldIds->toArray()) || !is_array($fieldIds->toArray())) {
                QueryBuilder::rollback();
                return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_IMPORT_EXPORT_INVALID_IMPORT_DATA_PROVIDED')], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            foreach ($items as $item) {
                if (!is_array($item) || (count($item) !== count($fieldIds->toArray()))) {
                    continue;
                }

                $values = [];
                foreach ($fieldIds as $index => $fieldId) {
                    if (isset($item[$index])) {
                        $values[] = array_merge($item[$index], ['field_id' => $fieldId]);
                    }
                }

                $itemData = [
                    'collection_id' => $collectionId,
                    'values'        => $values,
                    'published'     => 1,
                    'access'        => 1,
                    'language'      => '*',
                ];

                $collectionItemsService->createItem($itemData);
            }

            QueryBuilder::commit();

            return response()->json(true, Response::HTTP_OK);
        }
        catch (Exception $error)
        {
            QueryBuilder::rollback();

            if ($error instanceof ValidatorException) {
                return response()->json($error->getData(), $error->getCode());
            }

            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
