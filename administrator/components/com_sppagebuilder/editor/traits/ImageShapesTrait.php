<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Image Shapes traits
 */
trait ImageShapesTrait
{
    public function imageShapes()
    {
        $method = $this->getInputMethod();
        $this->checkNotAllowedMethods(['PUT'], $method);

        switch ($method) {
            case 'POST':
                $this->addImageShape();
                break;

            case 'PATCH':
                $this->updateImageShape();
                break;

            case 'DELETE':
                $this->deleteImageShape();
                break;

            default:
                $this->getImageShapes();
                break;
        }
    }

    private function getImageShapes()
    {
        $response = $this->processGetImageShapes();

        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function processGetImageShapes()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select(['id', 'name', 'shape'])
            ->from($db->quoteName('#__sppagebuilder_image_shapes'));

        $db->setQuery($query);

        $shapes = [];

        try {
            $shapes = $db->loadObjectList();
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
            return ['response' => $response, 'statusCode' => 500];
        }

        return ['response' => $shapes, 'statusCode' => 200];
    }

    private function addImageShape()
    {
        $shape = $this->getInput('shape', '', 'base64');

        if (empty($shape)) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_SVG_INFORMATION_MISSING');
            $this->sendResponse($response, 400);
        }

        $response = $this->processAddImageShape($shape);
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function processAddImageShape($shape)
    {
        $decodedShape = base64_decode($shape, true);
        $maxShapeSize = 64 * 1024; // 64 KB

        if ($decodedShape === false || trim($shape) === '') {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
            return ['response' => $response, 'statusCode' => 400];
        }

        if (strlen($decodedShape) === 0 || strlen($decodedShape) > $maxShapeSize) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
            return ['response' => $response, 'statusCode' => 400];
        }

        if (!preg_match('/<svg\b[^>]*>[\s\S]*<\/svg>/i', $decodedShape)) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
            return ['response' => $response, 'statusCode' => 400];
        }

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($decodedShape, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        if (!$loaded || !$dom->documentElement) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
            return ['response' => $response, 'statusCode' => 400];
        }

        if (strtolower($dom->documentElement->localName) !== 'svg') {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
            return ['response' => $response, 'statusCode' => 400];
        }

        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*');
        $pathCount = 0;

        foreach ($elements as $element) {
            if ($element->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($element->localName);

            if ($tag === 'path') {
                $pathCount++;
                continue;
            }

            if ($tag !== 'svg') {
                $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
                return ['response' => $response, 'statusCode' => 400];
            }
        }

        if ($pathCount !== 1) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_SVG_SHAPE');
            return ['response' => $response, 'statusCode' => 400];
        }

        $sanitizedSvg = $dom->saveXML($dom->documentElement);
        $shape = base64_encode($sanitizedSvg);

        $random_id = uniqid(mt_rand(), true);

        $data = new stdClass;
        $data->name = $random_id;
        $data->shape = $shape;
        $data->created = Factory::getDate()->toSql();
        $data->created_by = Factory::getUser()->id;

        try {
            $db = Factory::getDbo();
            $db->insertObject('#__sppagebuilder_image_shapes', $data, 'id');

            return [
                'response' => $data,
                'statusCode' => 201
            ];
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
            return [
                'response' => $response,
                'statusCode' => 500
            ];
        }
    }

    private function updateImageShape()
    {
        $id = $this->getInput('id', '', 'STRING');
        $shape = $this->getInput('shape', '', 'STRING');

        if (empty($id) || empty($shape)) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_SVG_INFORMATION_MISSING');
            $this->sendResponse($response, 404);
        }

        $response = $this->processUpdateImageShape($id, $shape);
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function processUpdateImageShape($id, $shape) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__sppagebuilder_image_shapes'))
            ->set($db->quoteName('shape') . ' = ' . $db->quote($shape))
            ->where($db->quoteName('id') . ' = ' . $db->quote($id));

        $db->setQuery($query);

        try {
            $db->execute();
            return [
                'response' => Text::_('COM_SPPAGEBUILDER_EDITOR_SVG_IMAGE_SHAPE_UPDATED_SUCCESSFULLY'),
                'statusCode' => 200
            ];
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
            return [
                'response' => $response,
                'statusCode' => 500
            ];
        }
    }

    private function deleteImageShape()
    {
        $id = $this->getInput('id', '', 'INT');

        if (empty($id)) {
            $response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_SVG_INFORMATION_MISSING');
            $this->sendResponse($response, 404);
        }

        $response = $this->processDeleteImageShape($id);
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function processDeleteImageShape($id) {
        try {
            $db = Factory::getDbo();

            $query = $db->getQuery(true);
            $query->delete($db->quoteName('#__sppagebuilder_image_shapes'));
            $query->where($db->quoteName('id') . ' = ' . $db->quote($id));

            $db->setQuery($query);
            $db->execute();

            return ['response' => Text::_('COM_SPPAGEBUILDER_EDITOR_SVG_IMAGE_SHAPE_DELETED_SUCCESSFULLY'),  'statusCode' => 200];
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            return ['response' => $response,  'statusCode' => 500];
        }
    }
}
