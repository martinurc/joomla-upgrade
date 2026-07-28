<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/editor/traits/ImageShapesTrait.php';

class SppagebuilderControllerImage_shapes extends FormController
{
    use ImageShapesTrait;

    public function __construct($config = [])
	{
		parent::__construct($config);

		$user = Factory::getUser();
		$authorised = $user->authorise('core.admin', 'com_sppagebuilder') || $user->authorise('core.manage', 'com_sppagebuilder');

		if (!$authorised)
		{
			$response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_ADMIN_ACCESS_REQUIRED');
			$this->sendResponse($response, 403, true);
		}

		if (!$user->id)
		{
			$response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_LOGIN_SESSION_EXPIRED');
			$this->sendResponse($response, 401, true);
		}

		if (!Session::checkToken())
		{
			$response['message'] = Text::_('COM_SPPAGEBUILDER_EDITOR_SESSION_MISMATCHED');
			$this->sendResponse($response, 403, true);
		}
	}

    public function getImageShapesAPI()
    {
        $response = $this->processGetImageShapes();
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function addImageShapeAPI()
    {
        $input = Factory::getApplication()->input;
        $shape = $input->json->get('shape', '', 'base64');

        if (empty($shape)) {
            $response['message'] = 'Information missing';
            $this->sendResponse($response, 400);
        }

        $response = $this->processAddImageShape($shape);
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function updateImageShapeAPI()
    {
        $input = Factory::getApplication()->input;
        $id = $input->json->get('id', '', 'STRING');
        $name = $input->json->get('name', '', 'STRING');
        $shape = $input->json->get('shape', '', 'STRING');

        if (empty($id) || empty($name) || empty($shape)) {
            $response['message'] = 'Information missing';
            $this->sendResponse($response, 404);
        }

        $response = $this->processUpdateImageShape($id, $name, $shape);
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    public function deleteImageShapeAPI()
    {
        $input = Factory::getApplication()->input;
        $id = $input->json->get('id', '', 'INT');

        if (empty($id)) {
            $response['message'] = 'Information missing';
            $this->sendResponse($response, 404);
        }

        $response = $this->processDeleteImageShape($id);
        $this->sendResponse($response['response'], $response['statusCode']);
    }

    /**
     * Send JSON Response to the client.
     *
     * @param	array	$response	The response array or data.
     * @param	int		$statusCode	The status code of the HTTP response.
     *
     * @return	void
     * @since	5.0.0
     */
    private function sendResponse($response, int $statusCode = 200): void
    {
        /** @var CMSAPplication */
        $app = Factory::getApplication();
        $app->setHeader('status', $statusCode, true);
        $app->sendHeaders();
        echo new JsonResponse($response);
        $app->close();
    }
}
