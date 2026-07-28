<?php
/**
 * @name		CK Loader
 * @copyright	Copyright (C) 2025. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @author		Cedric Keiflin - https://www.template-creator.com - https://www.joomlack.fr
 */

namespace Cookiesck;

// No direct access
defined('_JEXEC') or die;

$path = dirname(__FILE__);

// include the classes
class CKRegistry extends \Joomla\Registry\Registry { }
class CKText extends \Joomla\CMS\Language\Text { }
class CKUri extends \Joomla\CMS\Uri\Uri { }

if (version_compare(JVERSION, '6', '>=')) {
	class CKFile extends \Joomla\Filesystem\File { }
	class CKFolder extends \Joomla\Filesystem\Folder { }
	class CKInput extends \Joomla\Input\Input { }
	class CKPath extends \Joomla\Filesystem\Path { }
} else {
	jimport('joomla.filesystem.file');
	class CKFile extends \Joomla\CMS\Filesystem\File { }
	jimport('joomla.filesystem.folder');
	class CKFolder extends \Joomla\CMS\Filesystem\Folder { }
	class CKInput extends \Joomla\CMS\Input\Input { }
	class CKPath extends \Joomla\CMS\Filesystem\Path { }
}

if (class_exists('JPagination')) {
	class CKPagination extends \JPagination {
		
	}
} else {
	class CKPagination extends \Joomla\CMS\Pagination\Pagination {
		
	}
}

require_once $path . '/ckfof.php';
require_once $path . '/ckobject.php';