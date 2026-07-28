<?php

/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

final class WFTemplateManagerPlugin extends WFMediaManager
{
    protected $_filetypes = 'html=html,htm;text=txt,md';

    protected $name = 'templatemanager';

    public function __construct($config = array())
    {
        $config = array(
            'base_path' => __DIR__,
        );

        parent::__construct($config);

        // add a request to the stack
        $request = WFRequest::getInstance();
        $request->setRequest(array($this, 'loadTemplate'));
        $request->setRequest(array($this, 'getTemplateList'));

        if ($this->getParam('allow_save', 0)) {
            $request->setRequest(array($this, 'createTemplate'));
            $this->addFileBrowserAction('save', array('action' => 'createTemplate', 'title' => Text::_('WF_TEMPLATEMANAGER_CREATE')));
        }
    }

    /**
     * Display the plugin.
     */
    public function display()
    {
        parent::display();

        // create new tabs instance
        $tabs = WFTabs::getInstance(array(
            'base_path' => __DIR__,
        ));

        // Add tabs
        $tabs->addPanel('default', 1);

        $document = WFDocument::getInstance();

        $document->addScript(
            array(
                'plugins/templatemanager/js/templatemanager',
            ),
            'pro'
        );

        $document->addStyleSheet(
            array(
                'plugins/templatemanager/css/templatemanager',
            ),
            'pro'
        );

        $document->addScriptDeclaration('TemplateManager.settings=' . json_encode($this->getSettings()) . ';');
    }

    private function cleanHtmlData($html)
    {
        require_once WF_EDITOR_PRO_LIBRARIES . '/vendor/wfe/Purify.php';

        $purifier = new WfePurify();

        // Use HTMLPurifier to clean the HTML
        $html = $purifier->purify($html);

        // trim
        $html = trim($html);

        return $html;
    }   

    public function onBeforeUpload(&$file, &$dir, &$name)
    {
        $ext = WFUtility::getExtension($file['name'], true);

        if (in_array($ext, ['htm', 'html'])) {
            $data = @file_get_contents($file['tmp_name']);

            if ($data === false) {
                throw new \RuntimeException('Action Failed: Unable to read the file data.');
            }

            $data = $this->cleanHtmlData($data);

            if (@file_put_contents($file['tmp_name'], $data) === false) {
                throw new \RuntimeException('Action Failed: Unable to write the sanitised file.');
            }
        }

        return parent::onBeforeUpload($file, $dir, $name);
    }

    public function onUpload($file, $relative = '')
    {
        parent::onUpload($file, $relative);

        $app = Factory::getApplication();

        $browser = $this->getFileBrowser();

        // get the relative filesystem path
        $path = $browser->getFileSystem()->toRelative($file);

        if ($app->input->getInt('inline', 0) === 1) {
            $result = array(
                'file' => $relative,
                'name' => WFUtility::mb_basename($file),
            );

            $result['data'] = $this->loadTemplate($path);

            return $result;
        }

        return array();
    }

    public function createTemplate($dir, $name, $type = 'txt')
    {
        if ((int) $this->getParam('allow_save', 0) === 0) {
            throw new RuntimeException('Action Failed: Saving templates is not allowed.');
        }

        $browser = $this->getFileBrowser();

        $app = Factory::getApplication();

        // check path
        WFUtility::checkPath($dir);

        // check name
        WFUtility::checkPath($name);

        // check type
        WFUtility::checkPath($type);

        // validate name
        if (WFUtility::validateFileName($name) === false) {
            throw new InvalidArgumentException('Action Failed: The file name is invalid.');
        }

        if (strtolower($name) == 'index') {
            throw new InvalidArgumentException('Action Failed: The file name is invalid.');
        }

        // get data
        $data = $app->input->post->get('data', '', 'RAW');
        $data = rawurldecode($data);

        // clean data based on Joomla Text Filter settings
        $data = $this->cleanHtmlData($data);

        if (empty($data)) {
            throw new RuntimeException('Action Failed: The template data is empty.');
        }

        $type = strtolower($type);
        $type = trim($type);

        // check type is valid and rewrite to txt if not
        if (!in_array($type, ['txt', 'md'])) {
            $type = 'txt';
        }

        // create file name
        $name = File::makeSafe($name) . '.' . $type;

        // resolve complex path
        $path = $browser->resolvePath($dir);

        // create relative path to file
        $path = WFUtility::makePath($path, $name);

        // Remove any existing template div
        $data = preg_replace('/<div(.*?)class="mceTmpl"([^>]*?)>([\s\S]*?)<\/div>/i', '$3', $data);

        $data = stripslashes($data);

        if (!$browser->getFileSystem()->write($path, $data)) {
            $browser->setResult(Text::_('WF_TEMPLATEMANAGER_WRITE_ERROR'), 'error');
        } else {
            $browser->setResult(WFUtility::cleanPath($path), 'files');
        }

        return $browser->getResult();
    }

    public function replaceValuesToArray()
    {
        $data = array();
        $params = $this->getParam('replace_values');

        if ($params) {
            if (is_string($params)) {
                foreach (explode(',', $params) as $param) {
                    $parts = preg_split('/[:=]/', $param, 2);

                    if (count($parts) !== 2) {
                        continue;
                    }

                    [$key, $value] = $parts;

                    $key = trim($key, chr(0x22) . chr(0x27));
                    $value = trim($value, chr(0x22) . chr(0x27));

                    $data[$key] = trim($value);
                }
            } else {
                foreach ($params as $item) {
                    list($key, $value) = array_values($item);
                    $data[$key] = trim($value);
                }
            }
        }

        return $data;
    }

    protected function replaceVars($matches)
    {
        $key = $matches[1];

        switch ($key) {
            case 'modified':
                return WFUtility::formatDate($this->getParam('mdate_format', 'Y-m-d H:i:s'));
            case 'created':
                return WFUtility::formatDate($this->getParam('cdate_format', 'Y-m-d H:i:s'));
            case 'username':
            case 'usertype':
            case 'name':
            case 'email':
                $user = Factory::getUser();

                return isset($user->$key) ? $user->$key : $key;
            default:
                $values = $this->replaceValuesToArray();

                if (isset($values[$key])) {
                    return $values[$key];
                }

                return $matches[0];
        }
    }

    private function processTemplate($file)
    {
        $browser = $this->getFileBrowser();

        // check path
        WFUtility::checkPath($file);

        $file = $browser->resolvePath($file);

        // read content
        $content = $browser->getFileSystem()->read($file);

        if (empty($content)) {
            return '';
        }

        // quick pre-trim
        $content = trim($content);

        // extract body content if it exists
        if (preg_match('~<body[^>]*>(.*)</body>~is', $content, $matches)) {
            $content = $matches[1];
        }

        return $content;
    }

    public function loadTemplate($file)
    {
        $content = $this->processTemplate($file);

        $ext = WFUtility::getExtension($file, true);

        // process markdown
        if ($ext === 'md') {
            require_once WF_EDITOR_PRO_LIBRARIES . '/vendor/wfe/Markdown.php';

            $content = WfeMarkdownParser::defaultTransform($content);
        }

        // normalize variables to use ${var} syntax
        $content = preg_replace('/\{\$(.*?)\}/', '${$1}', $content);

        // Replace variables
        $content = preg_replace_callback('/\$\{(.+?)\}/i', array($this, 'replaceVars'), $content);

        return $content;
    }

    public function getViewable()
    {
        return $this->getFileTypes('list');
    }

    protected function getFileBrowserConfig($config = array())
    {
        $config = parent::getFileBrowserConfig($config);
        
        // ensure upload is disabled by default
        $config['features']['upload'] = $this->getParam('upload', 0);

        return $config;
    }

    public function getTemplateList()
    {
        $list = array();

        $templates = $this->getParam('templates', array());

        if (is_string($templates)) {
            $templates = json_decode(htmlspecialchars_decode($templates), true);
        }

        if (!empty($templates)) {

            foreach ($templates as $template) {
                // ensure an array
                $template = (array) $template;

                // must have a name
                if (!isset($template['name'])) {
                    continue;
                }

                // check for thumbnail (optional)
                if (!isset($template['thumbnail'])) {
                    $template['thumbnail'] = '';
                }

                // check for url (optional)
                if (!isset($template['url'])) {
                    $template['url'] = '';
                }

                // check for html (optional)
                if (!isset($template['html'])) {
                    $template['html'] = '';
                }

                if (!isset($template['description'])) {
                    $template['description'] = '';
                }

                // clean up template description so that it only contains text
                $template['description'] = strip_tags($template['description']);
                // encode
                $template['description'] = htmlspecialchars($template['description'], ENT_QUOTES, 'UTF-8');
                // trim
                $template['description'] = trim($template['description']);

                $name        = $template['name'];
                $url         = trim($template['url']);
                $html        = trim($template['html']);
                $thumbnail   = $template['thumbnail'];
                $description = $template['description'];
                $value       = '';

                // some values must be set
                if (empty($url) && empty($html)) {
                    continue;
                }

                if (!empty($url)) {
                    if (preg_match("#\.(htm|html|txt)$#", $url) && strpos($url, '://') === false) {
                        $url = trim($url, '/');

                        WFUtility::checkPath($url);

                        $file = JPATH_SITE . '/' . $url;

                        if (is_file($file)) {
                            $value = Uri::root() . $url;

                            $filename = WFUtility::stripExtension($url);

                            if (!$thumbnail && is_file(JPATH_SITE . '/' . $filename . '.jpg')) {
                                $thumbnail = $filename . '.jpg';
                            }
                        }
                    }
                } else if (!empty($html)) {
                    $value = htmlspecialchars_decode($html);
                }

                if ($thumbnail) {
                    $thumbnail = Uri::root(true) . '/' . $thumbnail;
                }

                $list[$name] = array(
                    'data' => $value,
                    'image' => $thumbnail,
                    'description' => $description,
                );
            }
        }

        // try files list
        if (empty($list)) {
            $browser = $this->getFileBrowser();

            // skip for external filesystems
            if (!$browser->getFileSystem()->get('local')) {
                return $list;
            }

            // search only the root folder
            $browser->set('search_depth', 0);

            $limit = (int) $this->getParam('templates_limit', 25);

            // get items
            $items = $browser->searchItems('', $limit, 0, '*.html OR *.htm OR *.txt OR *.md', '');

            foreach ($items['files'] as $item) {
                if ($item['name'] === "index.html") {
                    continue;
                }

                $name = WFUtility::getFilename($item['name']);
                $value = $item['properties']['preview'];

                $list[$name] = array(
                    'data' => $value,
                    'image' => '',
                );
            }
        }

        // sort list by name ignoring case
        ksort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }
}
