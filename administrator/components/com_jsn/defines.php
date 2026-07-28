<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

define('JSN_TYPE', 'pro');

define('JSN_ENV', true);

// Joomla 4 Compatibility Shims for JSN Easy Profile
if (!class_exists('JRequest')) {
    class JRequest {
        public static function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0) {
            $app = \Joomla\CMS\Factory::getApplication();
            if (!$app) {
                return $default;
            }
            $input = $app->input;
            if (strtoupper((string)$hash) === 'COOKIE') {
                return $input->cookie->get($name, $default, $type);
            }
            return $input->get($name, $default, $type);
        }
        public static function getInt($name, $default = 0, $hash = 'default') {
            return (int) static::getVar($name, $default, $hash, 'int');
        }
        public static function getBool($name, $default = false, $hash = 'default') {
            return (bool) static::getVar($name, $default, $hash, 'bool');
        }
        public static function getString($name, $default = '', $hash = 'default') {
            return (string) static::getVar($name, $default, $hash, 'string');
        }
        public static function setVar($name, $value = null, $hash = 'method', $overwrite = true) {
            return \Joomla\CMS\Factory::getApplication()->input->set($name, $value);
        }
    }
}

if (!class_exists('JEventDispatcher')) {
    class JEventDispatcher {
        private static $instance = null;
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        public function trigger($eventName, $args = array()) {
            $app = \Joomla\CMS\Factory::getApplication();
            if (!$app) {
                return array();
            }
            $eventObj = new \Joomla\Event\Event($eventName, [
                'context' => $args[0] ?? null,
                'data'    => $args[1] ?? null,
                'subject' => $args[1] ?? null,
            ]);
            $res = $app->getDispatcher()->dispatch($eventName, $eventObj);
            return (array) ($res->getArgument('result') ?? []);
        }
    }
}

if (!class_exists('JHtmlUsers')) {
    class JHtmlUsers {
        public static function spacer($value) {
            return '';
        }
        public static function helpsite($value) {
            return '';
        }
        public static function templatestyle($value) {
            return '';
        }
        public static function admin_language($value) {
            return '';
        }
        public static function language($value) {
            return '';
        }
        public static function editor($value) {
            return '';
        }
    }
}

if (!class_exists('UsersModelUser') && class_exists('\Joomla\Component\Users\Administrator\Model\UserModel')) {
    class_alias(\Joomla\Component\Users\Administrator\Model\UserModel::class, 'UsersModelUser');
}
if (!class_exists('UsersModelUsers') && class_exists('\Joomla\Component\Users\Administrator\Model\UsersModel')) {
    class_alias(\Joomla\Component\Users\Administrator\Model\UsersModel::class, 'UsersModelUsers');
}
if (!class_exists('UsersModelProfile') && class_exists('\Joomla\Component\Users\Site\Model\ProfileModel')) {
    class_alias(\Joomla\Component\Users\Site\Model\ProfileModel::class, 'UsersModelProfile');
}

if (!class_exists('UsersViewUsers') && class_exists('\Joomla\Component\Users\Administrator\View\Users\HtmlView')) {
    class_alias(\Joomla\Component\Users\Administrator\View\Users\HtmlView::class, 'UsersViewUsers');
}
if (!class_exists('UsersViewUser') && class_exists('\Joomla\Component\Users\Administrator\View\User\HtmlView')) {
    class_alias(\Joomla\Component\Users\Administrator\View\User\HtmlView::class, 'UsersViewUser');
}
if (!class_exists('UsersViewProfile') && class_exists('\Joomla\Component\Users\Site\View\Profile\HtmlView')) {
    class_alias(\Joomla\Component\Users\Site\View\Profile\HtmlView::class, 'UsersViewProfile');
}