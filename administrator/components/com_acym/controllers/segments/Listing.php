<?php

namespace AcyMailing\Controllers\Segments;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing()
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

        if (acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'listing');
            $pagination = new PaginationHelper();
            $searchFilter = $this->getVarFiltersListing('string', 'segments_search', '');
            $status = $this->getVarFiltersListing('string', 'segments_status', '');
            $ordering = $this->getVarFiltersListing('string', 'segments_ordering', 'id');
            $orderingSortOrder = $this->getVarFiltersListing('string', 'segments_ordering_sort_order', 'asc');

            $formsPerPage = $pagination->getListLimit();
            $page = $this->getVarFiltersListing('int', 'forms_pagination_page', 1);


            $requestData = [
                'ordering' => $ordering,
                'search' => $searchFilter,
                'elementsPerPage' => $formsPerPage,
                'offset' => ($page - 1) * $formsPerPage,
                'ordering_sort_order' => $orderingSortOrder,
                'status' => $status,
            ];

            $matchingSegments = $this->getMatchingElementsFromData($requestData, $status, $page);

            $pagination->setStatus($matchingSegments['total']->total, $page, $formsPerPage);


            $filters = [
                'all' => $matchingSegments['total']->total,
                'active' => $matchingSegments['total']->totalActive,
                'inactive' => $matchingSegments['total']->total - $matchingSegments['total']->totalActive,
            ];

            $data = [
                'segments' => $matchingSegments['elements'],
                'pagination' => $pagination,
                'search' => $searchFilter,
                'ordering' => $ordering,
                'status' => $status,
                'orderingSortOrder' => $orderingSortOrder,
                'segmentsNumberPerStatus' => $filters,
            ];

            $this->prepareToolbar($data);

            parent::display($data);
        }
    }

    private function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'segments_search');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

}
