<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Component\ComponentHelper;

use function PHPSTORM_META\type;

// No direct access
defined('_JEXEC') or die('Restricted access');


/**
 * Media trait files for managing the CRUD operation.
 * 
 * @version 4.1.0
 */
trait AiContentTrait
{
    /**
     * OpenAi endpoint for the API.
     * 
     * @return void
     * @version 4.1.0
     */
    public function aiContent()
    {
        $method = $this->getInputMethod();
        $this->checkNotAllowedMethods(['PUT', 'DELETE'], $method);

        switch ($method) {
            case 'POST':
                $this->getAiGeneratedContent();
                break;
            case 'PATCH':
                $this->getUrlToBase64Image();
                break;
        }
    }

    /**
     * Get AI generated Content
     * 
     * @return void
     * @version 5.0.8
     */
    private function getUrlToBase64Image()
    {
        $input = Factory::getApplication()->input;
        $imageUrl = $input->get('image_uri', '', 'STRING');

        if (!isset($imageUrl) || empty($imageUrl)) {
            $this->sendResponse([
                'status' => false,
                'message' => 'Image URL is missing.'
            ], 400);
        }

        $base64Image = $this->imageUrlToBase64($imageUrl);

        $this->sendResponse([
            'status' => true,
            'dataUrl' => $base64Image
        ], 200);
    }

    /**
     * Get AI generated Content
     * 
     * @return void
     * @version 5.0.8
     */
    private function getAiGeneratedContent()
    {
        $cParams = ComponentHelper::getParams('com_sppagebuilder');
        $input = Factory::getApplication()->input;
        $prompt = $input->get('prompt', '', 'STRING');
        $type = $input->get('type', 'text', 'STRING');
        $imageUri = $input->get('image_uri', '', 'STRING');
        $numberOfImages = $input->get('number_of_images', 4, 'NUMBER');
        $imageSize = $input->get('image_size', '512x512', 'STRING');
        $maxTokens = (int) $input->get('max_tokens', '250', 'STRING');

        // Set your OpenAI API key here
        $apiKey = $cParams->get('openai_api_key', '');
        $model = $cParams->get('openai_model', 'gpt-3.5-turbo');

        if (!isset($apiKey) || empty($apiKey)) {
            $this->sendResponse([
                'status' => false,
                'message' => Text::_('COM_SPPAGEBUILDER_AI_API_KEY_MISSING_MESSAGE')
            ], 400);
        }

        $modelStr = (string) $model;
        $isGeminiImageModel = (bool) preg_match('/^gemini-.+-flash-image(-preview)?$/i', $modelStr);
        $isGeminiTextModel = !$isGeminiImageModel && (bool) preg_match('/^gemini-/i', $modelStr);

        if ($isGeminiImageModel && $type !== 'image') {
            $this->sendResponse([
                'status' => false,
                'message' => Text::_('COM_SPPAGEBUILDER_AI_GEMINI_IMAGE_ONLY_MODEL'),
            ], 400);
        }

        if ($isGeminiImageModel && $type === 'image') {
            $this->getGeminiImageContent($apiKey, $model, $prompt);

            return;
        }

        if ($isGeminiTextModel && $type !== 'text') {
            $this->sendResponse([
                'status' => false,
                'message' => Text::_('COM_SPPAGEBUILDER_AI_GEMINI_TEXT_ONLY_MODEL'),
            ], 400);
        }

        if ($type === 'text' && $isGeminiTextModel) {
            $this->getGeminiTextContent($apiKey, $model, $prompt, $maxTokens);

            return;
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';

        // Request data
        $data = [
            "model" => $model,
            'max_tokens' => $maxTokens,
            "messages" => [
                [
                  "role" => "user",
                  "content" => $prompt
                ],
              ]
        ];


        if ($type === 'image') {
            $endpoint = 'https://api.openai.com/v1/images/generations';

            $data = [
                'prompt' => $prompt,
                'size' => $imageSize,
                'n' => (int) $numberOfImages,
                'user' => 'username'
            ];
        }

        // Create JSON payload
        $payload = json_encode($data);

        if ($type === 'variation') {
            if (empty($imageUri)) {
                $this->sendResponse([
                    'status' => false,
                    'message' => 'Image url is Required.'
                ], 400);
            }

            $endpoint = 'https://api.openai.com/v1/images/variations';

            if (str_starts_with($imageUri, 'http')) {
                $base64Image = $this->imageUrlToBase64($imageUri);
                $finalImageFile = $this->dataUrlToCurlFile($base64Image);
            } else {
                $finalImageFile = $this->processImageForOpenAi($imageUri);
            }

            $data = [
                'image' => $finalImageFile,
                'size' => $imageSize,
                'n' => (int) $numberOfImages,
                'user' => 'username'
            ];

            $payload = $data;
        }

        if ($type === 'generative_fill') {
            if (empty($imageUri) || empty($prompt)) {
                $this->sendResponse([
                    'status' => false,
                    'message' => 'Image or Prompt is missing.'
                ], 400);
            }

            $endpoint = 'https://api.openai.com/v1/images/edits';

            $finalMaskImage = $this->dataUrlToCurlFile($imageUri);

            $data = [
                'prompt' => $prompt,
                'image' => $finalMaskImage,
                'size' => '512x512',
                'n' => 4,
                'user' => 'username'
            ];

            $payload = $data;
        }

        // Initialize cURL session
        $ch = curl_init();

        $httpHeader = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $type === 'text' || $type === 'image' ? $httpHeader : ['Authorization: Bearer ' . $apiKey]);

        // Execute cURL session and get the response
        $response = curl_exec($ch);
        $error = null;

        if ($response === false) {
            $error = curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        if ($error !== null) {
            $this->sendResponse([
                'status' => false,
                'message' => $error
            ], 500);
        }

        // Decode and print the response
        $responseArray = json_decode($response, true);

        if (($type !== 'text') && $responseArray && isset($responseArray['data'])) {
            $this->sendResponse($responseArray);
        }

        if ($type === 'text' && $responseArray && isset($responseArray['choices'][0]['message']['content'])) {
            $this->sendResponse($responseArray);
        }

        if ($responseArray && isset($responseArray['error']) && isset($responseArray['error']['message'])) {
            $message = $responseArray['error']['message'];

            if(strpos($message, 'maximum context length')) {
                $message = Text::_('COM_SPPAGEBUILDER_AI_MAXIMUM_CHARACTER_LIMIT_EXCEEDED_MESSAGE');
            }

            $this->sendResponse([
                'status' => false,
                'message' => $message,
            ], 400);
        }

        $this->sendResponse([
            'status' => false,
            'message' => Text::_("COM_SPPAGEBUILDER_GLOBAL_SOMETHING_WENT_WRONG")
        ], 500);
    }

    /**
     * Text generation via Google Generative Language API (Gemini).
     *
     * @param   string  $apiKey     Google AI API key (same settings field as OpenAI; use a Google AI Studio key for Gemini models).
     * @param   string  $model      Model id, e.g. gemini-2.5-flash
     * @param   string  $prompt     User prompt
     * @param   int     $maxTokens  Max output tokens
     *
     * @return  void
     */
    private function getGeminiTextContent($apiKey, $model, $prompt, $maxTokens)
    {
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model)
            . ':generateContent?key=' . rawurlencode($apiKey);

        $maxOut = $maxTokens > 0 ? $maxTokens : 8192;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxOut,
            ],
        ];

        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $error = null;

        if ($response === false) {
            $error = curl_error($ch);
        }

        curl_close($ch);

        if ($error !== null) {
            $this->sendResponse([
                'status' => false,
                'message' => $error,
            ], 500);

            return;
        }

        $responseArray = json_decode($response, true);

        if ($responseArray && isset($responseArray['error']['message'])) {
            $this->sendResponse([
                'status' => false,
                'message' => $responseArray['error']['message'],
            ], 400);

            return;
        }

        $text = '';

        if (!empty($responseArray['candidates'][0]['content']['parts']) && is_array($responseArray['candidates'][0]['content']['parts'])) {
            foreach ($responseArray['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        if ($text === '') {
            $this->sendResponse([
                'status' => false,
                'message' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SOMETHING_WENT_WRONG'),
            ], 500);

            return;
        }

        // Same shape as OpenAI chat completion so the editor can read choices[0].message.content
        $this->sendResponse([
            'choices' => [
                [
                    'message' => [
                        'content' => $text,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Image generation via Google Generative Language API (Gemini native image models, e.g. Nano Banana).
     *
     * @param   string  $apiKey   Google AI API key
     * @param   string  $model    Model id (e.g. gemini-2.5-flash-image)
     * @param   string  $prompt   Text prompt
     *
     * @return  void
     */
    private function getGeminiImageContent($apiKey, $model, $prompt)
    {
        if ($prompt === '') {
            $this->sendResponse([
                'status' => false,
                'message' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SOMETHING_WENT_WRONG'),
            ], 400);

            return;
        }

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model)
            . ':generateContent?key=' . rawurlencode($apiKey);

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
            ],
        ];

        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $error = null;

        if ($response === false) {
            $error = curl_error($ch);
        }

        curl_close($ch);

        if ($error !== null) {
            $this->sendResponse([
                'status' => false,
                'message' => $error,
            ], 500);

            return;
        }

        $responseArray = json_decode($response, true);

        if ($responseArray && isset($responseArray['error']['message'])) {
            $this->sendResponse([
                'status' => false,
                'message' => $responseArray['error']['message'],
            ], 400);

            return;
        }

        $openAiStyleData = [];

        foreach ($responseArray['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                if (!is_array($part)) {
                    continue;
                }

                $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;

                if (!is_array($inline) || empty($inline['data'])) {
                    continue;
                }

                $mime = $inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png';

                // Editor expects HTTP or data URLs in data[].url (same as OpenAI url field).
                $openAiStyleData[] = [
                    'url' => 'data:' . $mime . ';base64,' . $inline['data'],
                ];
            }
        }

        if ($openAiStyleData === []) {
            $this->sendResponse([
                'status' => false,
                'message' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SOMETHING_WENT_WRONG'),
            ], 500);

            return;
        }

        $this->sendResponse([
            'created' => time(),
            'data' => $openAiStyleData,
        ]);
    }

    private function makeCurlFile($file)
    {
        $mime = mime_content_type($file);
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);
        return $output;
    }

    private function processImageForOpenAi($image_uri)
    {
        $imagePath = Path::clean(JPATH_ROOT . '/' . $image_uri);

        // Create a temporary directory
        $formattedTempSquareImagePath = tempnam(sys_get_temp_dir(), time());
        // $tempFile = fopen($formattedTempSquareImagePath, 'wb');

        $imageMimeType = mime_content_type($imagePath);

        if ($imageMimeType !== 'image/png') {
            $isConvertSuccess = BuilderMediaHelper::changeAspectRatio('1:1', $imagePath, $formattedTempSquareImagePath, 'png');

            if (!$isConvertSuccess) {
                $this->sendResponse([
                    'status' => false,
                    'message' => 'Image conversion failed. Please try again.'
                ], 500);
            }

            $finalImageFile = $this->makeCurlFile($formattedTempSquareImagePath);
        } else {
            $finalImageFile = $this->makeCurlFile($imagePath);
        }


        return $finalImageFile;
    }

    private function imageUrlToBase64($imageUrl)
    {
        $imageData = @file_get_contents($imageUrl);
        
        if ($imageData === false) {
            $this->sendResponse([
                'status' => false,
                'message' => 'Unable to fetch the image. Please try again.'
            ], 500); // Unable to fetch the image
        }

        // Encode the image data as Base64
        $base64Data = base64_encode($imageData);

        if ($base64Data === false) {
            $this->sendResponse([
                'status' => false,
                'message' => 'Failed to encode as Base64. Please try again.'
            ], 500); // Failed to encode as Base64
        }

        // Create a data URL with the Base64-encoded image data
        $dataUrl = 'data:image/png;base64,' . $base64Data;

        return $dataUrl;
    }

    private function dataUrlToCurlFile($dataUrlImage)
    {
        $mimeRegex = '/^data:([^;]+);base64,([a-zA-Z0-9\/+]+=*)$/';

        if (!preg_match($mimeRegex, $dataUrlImage, $matches)) {
            $this->sendResponse(['message' => "Invalid data URL format."], 400);
        }

        $base64Data = $matches[2];

        $decodedData = base64_decode($base64Data);

        // Create a temporary file
        $tempFileName = tempnam(sys_get_temp_dir(), 'maskimage');
        $tempFile = fopen($tempFileName, 'wb');
        fwrite($tempFile, $decodedData);
        fclose($tempFile);

        $tempSquareImageDest = tempnam(sys_get_temp_dir(), 'square');

        $isConvertSuccess = BuilderMediaHelper::changeAspectRatio('1:1', $tempFileName, $tempSquareImageDest, 'png');

        if (!$isConvertSuccess) {
            $this->sendResponse([
                'status' => false,
                'message' => 'Image conversion failed. Please try again.'
            ], 500);
        }

        $finalImageFile = $this->makeCurlFile($tempSquareImageDest);

        return $finalImageFile;
    }
}
