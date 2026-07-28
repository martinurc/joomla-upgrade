<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */


use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;

// No direct access
defined('_JEXEC') or die('Restricted access');

JLoader::register('SppagebuilderHelperImage', JPATH_ROOT . '/components/com_sppagebuilder/helpers/image.php');

/**
 * Media trait files for managing the CRUD operation.
 * 
 * @version 4.1.0
 */
trait AiContentUploadTrait
{
	/**
	 * OpenAi endpoint for the API.
	 * 
	 * @return void
	 * @version 4.1.0
	 */
	public function aiContentUpload()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['GET', 'PATCH', 'PUT', 'DELETE'], $method);

		switch ($method)
		{
			case 'POST':
				$this->uploadAiGeneratedImageFromUrl();
				break;
		}
	}

	/**
	 * Upload image from url
	 * 
	 * @return void
	 * @version 4.1.0
	 */
	private function uploadAiGeneratedImageFromUrl()
	{
		$model  = $this->getModel('Media');
		$user 	= Factory::getUser();
		$imageUrl = $this->getInput('url', '', 'STRING');
		$aspectRatio = $this->getInput('aspect_ratio', '', 'STRING');
		$canCreate = $user->authorise('core.create', 'com_sppagebuilder');

		$report = [];

		if (!$canCreate)
		{
			$report['status'] = false;
			$report['message'] = Text::_('JERROR_ALERTNOAUTHOR');
			$this->sendResponse($report, 403);
		}

		$imageUrlTrim = trim($imageUrl);
		$temporaryDecodedFile = null;

		if (strpos($imageUrlTrim, 'data:image/') === 0) {
			$temporaryDecodedFile = $this->aiWriteDataUriImageToTemp($imageUrlTrim);

			if ($temporaryDecodedFile === null) {
				$report['status'] = false;
				$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_UPLOAD_FAILED');
				$this->sendResponse($report, 400);
			}

			$conversionSource = $temporaryDecodedFile;
		} elseif (!filter_var($imageUrlTrim, FILTER_VALIDATE_URL)) {
			$report['status'] = false;
			$report['message'] = Text::_('Invalid url');
			$this->sendResponse($report, 400);
		} else {
			$conversionSource = $imageUrlTrim;
		}

		try {
			$extension = 'webp';
			$base_name = uniqid('ai_img_', true);
			$filename = $base_name . '.' . $extension;

			// Save the image to the server
			$mediaParams = ComponentHelper::getParams('com_media');
			$folder_root = $mediaParams->get('file_path', 'images') . '/';
			$date = Factory::getDate();
			$folder = $folder_root . HTMLHelper::_('date', $date, 'Y') . '/' . HTMLHelper::_('date', $date, 'm') . '/' . HTMLHelper::_('date', $date, 'd');

			if (!Folder::exists(JPATH_ROOT . '/' . $folder))
			{
				Folder::create(JPATH_ROOT . '/' . $folder, 0755);
			}

			if (!Folder::exists(JPATH_ROOT . '/' . $folder . '/_spmedia_thumbs'))
			{
				Folder::create(JPATH_ROOT . '/' . $folder . '/_spmedia_thumbs', 0755);
			}

			$src = Path::clean($folder . '/' . $filename);
			$dest = Path::clean(JPATH_ROOT . '/' . $src);

			$isImageSaved = !empty($aspectRatio) ? $this->changeAspectRatio($aspectRatio, $conversionSource, $dest) : $this->convertImageToWebp($conversionSource, $dest);

			if (!$isImageSaved)
			{
				$report['status'] = false;
				$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_UPLOAD_FAILED');
				$this->sendResponse($report, 400);
			}
		} finally {
			if ($temporaryDecodedFile !== null && is_file($temporaryDecodedFile)) {
				@unlink($temporaryDecodedFile);
			}
		}
		
		$media_attr = [];
		$thumb = '';

		$image = new SppagebuilderHelperImage($dest);
		$media_attr['full'] = ['height' => $image->height, 'width' => $image->width];

		if (($image->width > 300) || ($image->height > 225))
		{
			$thumbDestPath = dirname($dest) . '/_spmedia_thumbs';
			$created = $image->createThumb(array('300', '300'), $thumbDestPath, $base_name, $extension);

			if ($created == false)
			{
				$report['status'] = false;
				$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_FILE_NOT_SUPPORTED');
			}

			$report['src'] = Uri::root(true) . '/' . $folder . '/_spmedia_thumbs/' . $base_name . '.' . $extension;
			$thumb = $folder . '/_spmedia_thumbs/'  . $base_name . '.' . $extension;
			$thumb_dest = Path::clean($thumbDestPath . '/' . $base_name . '.' . $extension);
			list($width, $height) = getimagesize($thumb_dest);
			$media_attr['thumbnail'] = ['height' => $height, 'width' => $width];
			$report['thumb'] = $thumb;
		}
		else
		{
			$report['src'] = Uri::root(true) . '/' . $src;
			$report['thumb'] = $src;
		}

		// Create placeholder for lazy load
		$this->createAiGeneratedMediaPlaceholder($dest, $base_name, $extension);

		$insert_id = $model->insertMedia($base_name, $src, json_encode($media_attr), $thumb, 'image');
		$report['media_attr'] = $media_attr;
		$report['status'] = true;
		$report['title'] = $base_name;
		$report['id'] = $insert_id;
		$report['path'] = $src;

		$layout_path = JPATH_ROOT . '/administrator/components/com_sppagebuilder/layouts';
		$format_layout = new FileLayout('media.format', $layout_path);
		$report['message'] = $format_layout->render(array('media' => $model->getMediaByID($insert_id), 'innerHTML' => true));

		$this->sendResponse($report, 200);
	}

	/**
	 * Decode data:image MIME;base64 payload into a temp file for GD pipelines.
	 *
	 * @param   string  $dataUri  Full data URI
	 *
	 * @return  string|null  Temp path or null on failure
	 */
	private function aiWriteDataUriImageToTemp(string $dataUri): ?string
	{
		if (!preg_match('/^data:image\/([\w+.+-]+);base64,([\s\S]+)$/i', $dataUri, $matches))
		{
			return null;
		}

		$data = preg_replace('/\s+/', '', $matches[2]);
		$binary = base64_decode($data, true);

		if ($binary === false || $binary === '')
		{
			return null;
		}

		$tmp = tempnam(sys_get_temp_dir(), 'ai_b64_');

		if (@file_put_contents($tmp, $binary) === false)
		{
			return null;
		}

		return $tmp;
	}

	private static function is_in_array($needle, $haystack)
	{

		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

		foreach ($it as $element)
		{
			if ($element == $needle)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 2020
	 * Create light weight image placeholder for lazy load feature
	 */
	private function createAiGeneratedMediaPlaceholder($dest, $base_name, $ext)
	{
		$placeholder_folder_path = JPATH_ROOT . '/media/com_sppagebuilder/placeholder';

		if (!Folder::exists($placeholder_folder_path))
		{
			Folder::create($placeholder_folder_path, 0755);
		}

		$image = new SppagebuilderHelperImage($dest);
		list($srcWidth, $srcHeight) = $image->getDimension();
		$width = 60;
		$height = $width / ($srcWidth / $srcHeight);
		$image->createThumb(array('60', $height), $placeholder_folder_path, $base_name, $ext, 20);
	}

	private function getImageSource($image_path)
	{
		$image_properties   = @getimagesize($image_path);
		$image_type         = $image_properties[2];
		$imageSource 		= false;

		switch ($image_type) 
		{
			case 1:
				$imageSource  = @imagecreatefromgif($image_path);
				break;
			case 2:
				$imageSource  = @imagecreatefromjpeg($image_path);
				break;
			case 3:
				$imageSource  = @imagecreatefrompng($image_path);
				break;
		}

		return $imageSource;
	}

	private function convertImageToWebp($image_path, $destination)
	{
		$image = $this->getImageSource($image_path);

		if(!$image)
		{
			return false;
		}

		// get dimensions of image
		$width = imagesx($image);
		$height = imagesy($image);

		// create a canvas
		$canvas = imagecreatetruecolor ($width, $height);
		imageAlphaBlending($canvas, false);
		imageSaveAlpha($canvas, true);

		// By default, the canvas is black, so make it transparent
		$transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
		imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $transparent);

		// copy image to canvas
		imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

		// save canvas as a webp
		$is_image_saved = imagewebp($canvas, $destination, 80); // 80 is the quality parameter (0-100)

		// clean up memory
		imagedestroy($canvas);

		return $is_image_saved;
	}

	private function changeAspectRatio($aspect_ratio, $image_path, $destination)
	{
		$sourceImage = $this->getImageSource($image_path);
		if(!$sourceImage)
		{
			return false;
		}

		// Calculate the new dimensions while maintaining the aspect ratio
		$sourceWidth = imagesx($sourceImage);
		$sourceHeight = imagesy($sourceImage);

		$parts = explode(':', $aspect_ratio);
		$aspectRatio = $parts[0] / $parts[1];

		// Calculate the target width and height based on the aspect ratio
		if ($sourceWidth / $sourceHeight > $aspectRatio) {
			$newWidth = round($sourceHeight * $aspectRatio);
			$newHeight = $sourceHeight;
		} else {
			$newWidth = $sourceWidth;
			$newHeight = round($sourceWidth / $aspectRatio);
		}

		// Create a new image with the desired dimensions
		$newImage = @imagecreatetruecolor($newWidth, $newHeight);
		if(!$newImage)
		{
			return false;
		}

		// Resize the source image to fit the new dimensions
		$isSuccess = @imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
		if(!$isSuccess){
			return false;
		}

		// save canvas as a webp
		$isSuccess = @imagewebp($newImage, $destination, 80); // 80 is the quality parameter (0-100)

		// Clean up resources
		imagedestroy($sourceImage);
		imagedestroy($newImage);

		return $isSuccess;
	}
}
