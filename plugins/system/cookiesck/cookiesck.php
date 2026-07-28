<?php

/**
 * @copyright	Copyright (C) 2015 Cédric KEIFLIN alias ced1870
 * http://www.joomlack.fr
 * @license		GNU/GPL
 * */
 
 /**
 * Cookies management Javascript code from
 * @subpackage		Modules - mod_jbcookies
 * 
 * @author			JoomBall! Project
 * @link			http://www.joomball.com
 * @copyright		Copyright © 2011-2014 JoomBall! Project. All Rights Reserved.
 * @license			GNU/GPL, http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');

JLoader::register('MenusHelper', JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Content\Site\Helper\AssciationHelper;
use Joomla\String\StringHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

class plgSystemCookiesck extends CMSPlugin {

	private $paramsEnabled;

	private $readmoreLink = array();

	private $listCookies;

	function __construct(&$subject, $config) {
		$this->paramsEnabled = file_exists(JPATH_SITE . '/administrator/components/com_cookiesck/cookiesck.php');
		parent :: __construct($subject, $config);
	}

	function onAfterDispatch() {
		global $ckjqueryisloaded;
		$app = Factory::getApplication();
		$document = Factory::getDocument();
		$doctype = $document->getType();

		// si pas en frontend, on sort
		if ($app->isClient('administrator')) {
			return;
		}

		// si pas HTML, on sort
		if ($doctype !== 'html') {
			return;
		}

		// si en mode imbriqué
		if ($app->input->get('tmpl','') != '') {
			return;
		}
		// exit if we are in one of these cases
		if ($app->input->get('option', '', 'string') == 'com_ajax' 
			|| $app->input->get('option', '', 'string') == 'com_media'
			|| $app->input->get('format', '', 'string') == 'raw'
			) {
			return;
		}

		// load jquery
		$jquerycall = "";
		if (version_compare(JVERSION, '5') >= 1 ) {
			$wa = Factory::getDocument()->getWebAssetManager();
			$wa->useScript('jquery');
		} else if (version_compare(JVERSION, '3') >= 1 ) {
			JHTML::_('jquery.framework', true);
		} else if (! $ckjqueryisloaded) {
			$document->addScript(Uri::base(true) . "/plugins/system/cookiesck/assets/jquery.min.js");
		}

		// initiate the scan
		if ($app->input->get('cookiesck','') == 'scan') {
			return $this->scan();
//			die;
		}

		// get the params from the plugin options
		$plugin = PluginHelper::getPlugin('system', 'cookiesck');
		$pluginParams = new Registry($plugin->params);

		// load the language strings of the plugin
		$this->loadLanguage();

		$lifetime = (int) $pluginParams->get('lifetime', 365);
		$reload = (int) $pluginParams->get('reloadafteraccept', '0');
		$debug = (int) $pluginParams->get('debug', '0');
		// set the cookie from the ajax request on click
		if (isset($_POST['set_cookieck'])) {
			if ($_POST['set_cookieck']==1) {
				setcookie("cookiesck", "yes", time()+3600*24*$lifetime, "/");
			}
			if ($_POST['set_cookieck']==0) {
				setcookie("cookiesck", "no", time()+3600*24*$lifetime, "/");
			}
			if ($pluginParams->get('enable_log', '0') === '1') $this->logActions();
			exit;
		}

		// unset cookies if not yet accepted
		$cookiesckValue = isset($_COOKIE['cookiesck']) ? $_COOKIE['cookiesck'] : 'no';
		if (! isset($_COOKIE['cookiesck']) || $_COOKIE['cookiesck'] == 'no' && !isset($_POST['set_cookieck'])) {
//			@header_remove('Set-Cookie');
			// remove external ressources if option set in the plugin
			if ($pluginParams->get('blockingpolicy', '1') == '2')
			{
				@header("Content-Security-Policy: default-src 'self' 'unsafe-inline';");
				@header("X-Content-Security-Policy: default-src 'self' 'unsafe-inline';");
			}
		}

		$readmore_link = '';
		$link_rel = $pluginParams->get('link_rel') ? ' rel=\"' . $pluginParams->get('link_rel') . '\"' : '';
		if ($pluginParams->get('linktype', 'article') == 'article') {
			$id = $pluginParams->get('article_readmore');

			if ($id) {
				if (version_compare(JVERSION, '4') >= 0) {

					$langTag = $app->getLanguage()->getTag();
					$option = 'com_content';
					$component = $app->bootComponent($option);

					if ($component instanceof AssociationServiceInterface)
					{
						$assoc_articles = $component->getAssociationsExtension()->getAssociationsForItem();
					}
					else
					{
						// Load component associations
						$class = str_replace('com_', '', $option) . 'HelperAssociation';
						\JLoader::register($class, JPATH_SITE . '/components/' . $option . '/helpers/association.php');

						if (class_exists($class) && \is_callable(array($class, 'getAssociations')))
						{
							$assoc_articles = \call_user_func(array($class, 'getAssociations'));
						}
					}
					if (isset ($assoc_articles[$langTag])) {
						$readmore_link = Route::_($assoc_articles[$langTag]);
					} else {
						$db = Factory::getDbo();
						$query = "SELECT * FROM #__content WHERE id = " . (int)$pluginParams->get('article_readmore');
						$db->setQuery($query);
						$item = $db->loadObject();
						$item->slug = $item->id.':'.$item->alias;
						// get the article link
						$readmore_link = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));
					}
				} else {
					require_once JPATH_SITE.'/components/com_content/helpers/route.php';
					require_once JPATH_SITE.'/components/com_content/helpers/association.php';
					JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
					$langTag = $app->getLanguage()->getTag();

					$assoc_articles = ContentHelperAssociation ::getAssociations($id);
					if (isset ($assoc_articles[$langTag])) {
						$readmore_link = Route::_($assoc_articles[$langTag]);
					} else {
						// Get an instance of the generic article model
						$model = JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request' => true));
						// Set application parameters in model
						$appParams = Factory::getApplication()->getParams();
						$model->setState('params', $appParams);
						//	Retrieve Content
						$item = $model->getItem($pluginParams->get('article_readmore'));
						$item->slug = $item->id.':'.$item->alias;
						// $item->catslug = $item->catid.':'.$item->category_alias;
						// get the article link
						$readmore_link = Route::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid));
					}
				}

				if ($link_anchor = $pluginParams->get('link_anchor')) {
					$readmore_link = $readmore_link . '#' . trim($link_anchor, '#');
				}
			}
		} else if ($pluginParams->get('linktype', 'article') == 'menuitem') {
			$readmore_link = $pluginParams->get('menuitem_readmore');
			$associations = MenusHelper::getAssociations($readmore_link);
			$langTag = $app->getLanguage()->getTag();
			$link_id = isset($associations[$langTag]) ? $associations[$langTag] : $readmore_link;

			// search for the link
			$db = Factory::getDbo();
			$query = "SELECT link FROM #__menu WHERE id = " . (int)$link_id;
			$db->setQuery($query);
			$menuItem = $db->loadObject();

			$readmore_link = Route::_($menuItem->link);
		} else {
			$readmore_link = $pluginParams->get('link_readmore');
			if (substr($readmore_link, 0,4) != 'http') {
				$readmore_link = Uri::root(true) . '/' . trim($readmore_link, '/');
			}
		}

		// store for use in the interface
		$this->readmoreLink['href'] = $readmore_link;
		$this->readmoreLink['rel'] = $link_rel;
		$this->readmoreLink['target'] = ($pluginParams->get('link_target', 'same') == 'new' ? '_blank' : '');

		$where = 'top';
		switch ($pluginParams->get('position', 'absolute')) {
			case 'absolute':
			default:
				$position = 'absolute';
				break;
			case 'fixed':
				$position = 'fixed';
				break;
			case 'relative':
				$position = 'relative';
				break;
			case 'bottom':
				$position = 'fixed';
				$where = 'bottom';
				break;
		}
		// add styling
		$css = "
			#cookiesck {
				position:" . $position . ";
				left:0;
				right: 0;
				" . $where . ": 0;
				z-index: 1000000;
				min-height: 30px;
				color: " . $pluginParams->get('text_color', '#fff') . ";
				background: " . $this->hex2RGB($pluginParams->get('background_color', '#000000'), $pluginParams->get('background_opacity', '0.5')) . ";
				text-align: center;
				font-size: 14px;
				line-height: 14px;
			}
			#cookiesck_text {
				padding: 10px 0;
				display: inline-block;
			}
			#cookiesck_buttons {
				float: right;
			}
			.cookiesck_button,
			#cookiesck_accept,
			#cookiesck_decline,
			#cookiesck_settings,
			#cookiesck_readmore {
				float:left;
				padding:10px;
				margin: 5px;
				border-radius: 3px;
				text-decoration: none;
				cursor: pointer;
				transition: all 0.2s ease;
			}
			#cookiesck_readmore {
				float:right;
			}
			#cookiesck_accept {
				background: #1176a6;
				border: 2px solid #1176a6;
				color: #f5f5f5;
			}
			#cookiesck_accept:hover {
				background: transparent;
				border: 2px solid darkturquoise;
				color: darkturquoise;
			}
			#cookiesck_decline {
				background: #000;
				border: 2px solid #000;
				color: #f5f5f5;
			}
			#cookiesck_decline:hover {
				background: transparent;
				border: 2px solid #fff;
				color: #fff;
			}
			#cookiesck_settings {
				background: #fff;
				border: 2px solid #fff;
				color: #000;
			}
			#cookiesck_settings:hover {
				background: transparent;
				border: 2px solid #fff;
				color: #fff;
			}
			#cookiesck_options {
				display: " . (isset($_COOKIE['cookiesck']) ? "block" : "none") . ";
				width: " . $this->testUnit($pluginParams->get('cookie_button_width', '30px')) . ";
				height: " . $this->testUnit($pluginParams->get('cookie_button_width', '30px')) . ";
				border-radius: 15px;
				box-sizing: border-box;
				position: fixed;
				bottom: 0;
				left: 0;
				margin: 10px;
				border: 1px solid #ccc;
				cursor: pointer;
				background: " . $this->hex2RGB($pluginParams->get('cookie_button_background_color', '#ffffff'), $pluginParams->get('cookie_button_background_opacity', '1')) . " url(" . Uri::root(true) . '/' . $this->params->get('cookie_button_background_image', "plugins/system/cookiesck/assets/cookies-icon.svg") . ") center center no-repeat;
				background-size: 80% auto;
				z-index: 1000000;
			}
			#cookiesck_options > .inner {
				display: none;
				width: max-content;
				margin-top: -40px;
				background: rgba(0,0,0,0.7);
				position: absolute;
				font-size: 14px;
				color: #fff;
				padding: 4px 7px;
				border-radius: 3px;
			}
			#cookiesck_options:hover > .inner {
				display: block;
			}
			#cookiesck > div {
				display: flex;
				justify-content: space-around;
				align-items: center;
				flex-direction: column;
			}
			" . ($this->params->get('blockiframes_image', '') ? "
			iframe[data-cookiesck-src] {
				background: #ddd url(" . Uri::root(true) . '/' . $this->params->get('blockiframes_image') . ") center center no-repeat;
			}" : "") . "
			" . ($this->params->get('blockiframes_textimage', 'image') === 'text' ? "
			iframe[data-cookiesck-src] {
				background: #333;
				color: #fff;
			}" : "") . "
			.cookiesck-iframe-wrap-text {
				position: absolute;
				width: 100%;
				padding: 10px;
				color: #fff;
				top: 50%;
				transform: translate(0,-60%);
				text-align: center;
			}
			.cookiesck-iframe-wrap:hover .cookiesck-iframe-wrap-text {
				color: #333;
			}
			.cookiesck-iframe-wrap-allowed .cookiesck-iframe-wrap-text {
				display: none;
			}

		";

		$layout = 'layout1';
		if (! $this->paramsEnabled) {
			$document->addStyleDeclaration($css);
		} else {
			$styles = $this->getStylesCss();
			// if no style saved in the interface, then still use the default styles
			if (!isset($styles->layoutcss) || ! $styles->layoutcss) {
				$document->addStyleDeclaration($css);
			} else {
				$this->loadAssets($styles, $where, $position);
				$stylesParams = json_decode($styles->params);
				$layout = isset($stylesParams->barlayout) ? $stylesParams->barlayout : 'layout1';
			}
		}

		$ckcookieswizard = $pluginParams->get('ckcookieswizard', '{}');
		$this->listCookies = $this->listCookies($ckcookieswizard);
		$allowedCookies = $this->getAllowedCookies($ckcookieswizard);

		// remove the existing non allowed cookies
		if (! empty($_COOKIE) && $cookiesckValue != 'yes') {
			$explode = explode('.', $_SERVER['HTTP_HOST'], substr_count($_SERVER['HTTP_HOST'], '.'));
			$domain = '.' . array_pop($explode);
			foreach ($_COOKIE as $name => $value) {

				// check if the cookie exists in the list
				foreach ($allowedCookies as $allowedCookie) {
					if (substr($name, 0, strlen($allowedCookie)) == $allowedCookie) {
						goto cookiesckskip;
					}
				}

				// simple additional check
				if (! in_array($name, $allowedCookies)) {
					setcookie($name, '', time()-3600);
					setcookie($name, '', 1, '', $domain);
				}
				cookiesckskip :
			}
		}

		$defaultValue = $this->getDefaultValue($ckcookieswizard);
		$session = Factory::getSession();

		// setup variables
		$js = '
var COOKIESCK = {
	ALLOWED : ' . json_encode($allowedCookies) . '
	, VALUE : \'' . $defaultValue . '\'
	, UNIQUE_KEY : \'' . $session->getId() . '\'
	, LOG : \'' . $pluginParams->get('enable_log', '0') . '\'
	, LIST : \'' .  addslashes($ckcookieswizard) . '\'
	, LIFETIME : \'' .  $lifetime . '\'
	, DEBUG : \'' .  $debug . '\'
	, TEXT : {
		INFO : \'' . Text::_('COOKIESCK_INFO', true) . '\'
		, ACCEPT_ALL : \'' . Text::_('COOKIESCK_ACCEPT_ALL', true) . '\'
		, ACCEPT_ALL : \'' . Text::_('COOKIESCK_ACCEPT_ALL', true) . '\'
		, DECLINE_ALL : \'' . Text::_('COOKIESCK_DECLINE_ALL', true) . '\'
		, SETTINGS : \'' . Text::_('COOKIESCK_SETTINGS', true) . '\'
		, OPTIONS : \'' . Text::_('COOKIESCK_OPTIONS', true) . '\'
		, CONFIRM_IFRAMES : \'' . Text::_('COOKIESCK_CONFIRM_IFRAMES', true) . '\'
	}
};
';

		// load script if needed
		if ($this->listCookies === false) {
			$js .= 'console.log("COOKIES CK MESSAGE : The list of cookies is empty. Please check the documentation");'
					. 'jQuery(document).ready(function(){ckInitCookiesckIframes();});'
					;
		} else {
			$code = 'new Cookiesck({'
						. 'lifetime: "' . $lifetime . '"'
						. ', layout: "' . $layout . '"'
						. ', reload: "' . $reload . '"'
					. '}); ';
			$js .= '
if( document.readyState !== "loading" ) {
' . $code . '
} else {
	document.addEventListener("DOMContentLoaded", function () {
		' . $code . '
	});
}';

		}

		$document->addScriptDeclaration($js);
		$document->addScript(Uri::base(true) . "/plugins/system/cookiesck/assets/front.js?ver=3.7.1");
		$document->addStylesheet(Uri::base(true) . "/plugins/system/cookiesck/assets/front.css?ver=3.7.1");
	}

	/**
	 * Convert a hexa decimal color code to its RGB equivalent
	 *
	 * @param string $hexStr (hexadecimal color value)
	 * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise returns associative array)
	 * @param string $seperator (to separate RGB values. Applicable only if second parameter is true.)
	 * @return array or string (depending on second parameter. Returns False if invalid hex color value)
	 */
	function hex2RGB($hexStr, $opacity) {
		if ($opacity > 1) $opacity = $opacity/100;
		$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
		$rgbArray = array();
		if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
			$colorVal = hexdec($hexStr);
			$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
			$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
			$rgbArray['blue'] = 0xFF & $colorVal;
		} elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
			$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
			$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
			$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
		} else {
			return false; //Invalid hex color code
		}
		$rgbacolor = "rgba(" . $rgbArray['red'] . "," . $rgbArray['green'] . "," . $rgbArray['blue'] . "," . $opacity . ")";

		return $rgbacolor;
	}

	/**
	 * Load the scripts and styles
	 */
	protected function loadAssets($styles, $where, $position) {
		if (! $this->paramsEnabled) return;

		// loads the helper in any case
		require_once JPATH_SITE . '/administrator/components/com_cookiesck/helpers/helper.php';
		$doc = Factory::getDocument();

		$stylescss = $styles->layoutcss;
		$cssreplacements = CookiesckHelper::getCssReplacement();
		global $ckcustomgooglefontslist;
		foreach ($cssreplacements as $tag => $rep) {
			$stylescss = str_replace($tag, $rep, $stylescss);

			$stylesParams = json_decode($styles->params);
//			$layout = isset($stylesParams->barlayout) ? $stylesParams->barlayout : 'layout1';
			$search = array('[', ']');
			$replace = array('', '');
			$var = str_replace($search, $replace, $tag);

			if (isset($stylesParams->{$var . 'textisgfont'}) && $stylesParams->{$var . 'textisgfont'} == '1' 
				&& isset($stylesParams->{$var . 'textgfont'}) && $stylesParams->{$var . 'textgfont'} != '') {
				$ckcustomgooglefontslist[] = $stylesParams->{$var . 'textgfont'};
				
			}
		}
		

		// $styles = str_replace('|ID|', '.customfieldsck.' . $fieldClass, $styles);
		$stylescss = str_replace('#cookiesck_overlay {', '#cookiesck_overlay { 
position: fixed;
display: block;
content: \"\";
top: 0;
bottom: 0;
left: 0;
right: 0;
z-index: 1000;
background-size: cover !important;', $stylescss);
		$stylescss = str_replace('|ID|', '', $stylescss);

		$stylescss .= "
#cookiesck {
	position:" . $position . ";
	left:0;
	right: 0;
	" . $where . ": 0;
	z-index: 1001;
	min-height: 30px;
	box-sizing: border-box;
}
#cookiesck_text {
	display: inline-block;
}
.cookiesck_button {
	display: inline-block;
	cursor: pointer;
	padding:10px;
	margin: 5px;
	border-radius: 3px;
	text-decoration: none;
	cursor: pointer;
	transition: all 0.2s ease;
}
#cookiesck > .inner {
	display: block;
	flex: 1 1 auto;
	text-align: center;
}
#cookiesck[data-layout=\"layout1\"] #cookiesck_buttons {
	float: right;
}
#cookiesck[data-layout=\"layout2\"] #cookiesck_text,
#cookiesck[data-layout=\"layout2\"] #cookiesck_buttons, 
#cookiesck[data-layout=\"layout3\"] #cookiesck_text,
#cookiesck[data-layout=\"layout3\"] #cookiesck_buttons {
	display: block;
}
#cookiesck[data-layout=\"layout3\"] {
	bottom: 0;
	top: 0;
	display: flex;
	align-items: center;
	margin: auto;
	position: fixed;
}

#cookiesck_options {
	display: " . (isset($_COOKIE['cookiesck']) ? "block" : "none") . ";
	width: " . $this->testUnit($this->params->get('cookie_button_width', '30px')) . ";
	height: " . $this->testUnit($this->params->get('cookie_button_width', '30px')) . ";
	border-radius: 15px;
	box-sizing: border-box;
	position: fixed;
	bottom: 0;
	left: 0;
	margin: 10px;
	border: 1px solid #ccc;
	cursor: pointer;
	background: " . $this->hex2RGB($this->params->get('cookie_button_background_color', '#ffffff'), $this->params->get('cookie_button_background_opacity', '1')) . " url(" . Uri::root(true) . '/' . $this->params->get('cookie_button_background_image', "plugins/system/cookiesck/assets/cookies-icon.svg") . ") center center no-repeat;
	background-size: 80% auto;
	z-index: 9999;
}
#cookiesck_options > .inner {
	display: none;
	width: max-content;
	margin-top: -40px;
	background: rgba(0,0,0,0.7);
	position: absolute;
	font-size: 14px;
	color: #fff;
	padding: 4px 7px;
	border-radius: 3px;
}
#cookiesck_options:hover > .inner {
	display: block;
}
" . ($this->params->get('blockiframes_image', '') && $this->params->get('blockiframes_textimage', 'image') === 'image' ? "
iframe[data-cookiesck-src] {
	background: #ddd url(" . Uri::root(true) . '/' . $this->params->get('blockiframes_image') . ") center center no-repeat;
}" : "") . "
" . ($this->params->get('blockiframes_textimage', 'image') === 'text' ? "
iframe[data-cookiesck-src] {
	background: #333;
	color: #fff;
}" : "") . "
.cookiesck-iframe-wrap-text {
	position: absolute;
	width: 100%;
	padding: 10px;
	color: #fff;
	top: 50%;
	transform: translate(0,-60%);
	text-align: center;
}

";

		$doc->addStyleDeclaration($stylescss);
	}

	/**
	 * Check if we need to load the styles in the page
	 */
	public function onBeforeRender() {
		$this->loadCustomGoogleFontsList();
	}

	/**
	 * Load the fonts only if not already registered by another extension
	 */
	public function loadCustomGoogleFontsList() {
		global $ckcustomgooglefontslist;

		if (! empty($ckcustomgooglefontslist)) {
			$doc = Factory::getDocument();
			foreach ($ckcustomgooglefontslist as $ckcustomgooglefont) {
				$ckcustomgooglefont = str_replace(' ', '+', $ckcustomgooglefont);
				$doc->addStylesheet('//fonts.googleapis.com/css?family=' . $ckcustomgooglefont);
			}
		}
	}

	/**
	 * Get the css rules from the styles
	 *
	 * @param int $id
	 * @return string
	 */
	protected function getStylesCss() {
		$this->searchTable('#__cookiesck_styles');

		$db = Factory::getDbo();
		$q = "SELECT params,layoutcss,state from #__cookiesck_styles ORDER BY id DESC LIMIT 1";
		$db->setQuery($q);
		$styles = $db->loadObject();

		return $styles;
	}

	/**
	 * Look if the table exists, if not then create it
	 * 
	 * @param type $tableName
	 */
	private static function searchTable($tableName) {
		$db = Factory::getDbo();
		$tablesList = $db->getTableList();
		$tableExists = in_array($db->getPrefix() . $tableName, $tablesList);
		// test if the table not exists

		if (! $tableExists) {
			self::createTable($tableName);
		}
	}

	private static function createTable($tableName) {
				$query = "CREATE TABLE IF NOT EXISTS `#__cookiesck_styles` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `state` int(10) NOT NULL DEFAULT '1',
  `params` longtext NOT NULL,
  `checked_out` varchar(10) NOT NULL,
  `layoutcss` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

		$db = Factory::getDbo();
		$db->setQuery($query);
		if (! $db->execute($query)) {
			// echo '<p class="alert alert-danger">Error during table ' . $tableName . ' creation process !</p>';
		} else {
			// echo '<p class="alert alert-success">Table ' . $tableName . ' created with success !</p>';
		}
	}

	public function onAfterRender() {
		$app = Factory::getApplication();
		$document = Factory::getDocument();
		$doctype = $document->getType();

		// stop if we are in admin
		if ($app->isClient('administrator') || $doctype !== 'html') {
			return;
		}

		// get the page code
		if (version_compare(JVERSION, '4') >= 0) {
			$body = Factory::getApplication()->getBody(); 
		} else {
			$body = JResponse::getBody();
		}

		// look for the tags and replace
		if ($this->lookForIframes($body)) {
			$regex = "#<iframe(.*?)>(.*?)</iframe>#s"; // masque de recherche pour le tag
			$body = preg_replace_callback($regex, array($this, 'replaceIframe'), $body);
		}

		// insert the interface html code
		$body = str_replace('</body>', '<div id="cookiesck_interface">' . $this->listCookies . '</div></body>', $body);

		// get the user settings from the cookies, else use the plugin options
		$cookiesckParams = $this->getCookiesckParams();
		if (isset($cookiesckParams['cookiesckgoogleanalytics'])) {
			$cookiesckgoogleanalytics = $cookiesckParams['cookiesckgoogleanalytics'] === '1' ? 'granted' : 'denied';
		} else {
			$cookiesckgoogleanalytics = $this->params->get('google_consent_analytics', 'denied');
		}
		if (isset($cookiesckParams['cookiesckgooglead'])) {
			$cookiesckgooglead = $cookiesckParams['cookiesckgooglead'] === '1' ? 'granted' : 'denied';
		} else {
			$cookiesckgooglead = $this->params->get('google_consent_ad', 'denied');
		}

		// look for Google Tag manager
		if ($this->params->get('enable_google_consent', '0') === '1') {
			$GTAGscript = "<script>
// Define dataLayer and the gtag function.
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}

// Determine actual values based on your own requirements
gtag('consent', 'default', {";
	if ($cookiesckgooglead !== 'disabled') {
		$GTAGscript .= "
	'ad_storage': '" . $cookiesckgooglead . "',
	'ad_user_data': '" . $cookiesckgooglead . "',
	'ad_personalization': '" . $cookiesckgooglead . "',";
	}
	if ($cookiesckgoogleanalytics !== 'disabled') {
		$GTAGscript .= "
	'analytics_storage': '" . $cookiesckgoogleanalytics . "'";
	}
	$GTAGscript .= "
});
</script>";
			// manage Google analytics
		if ($this->params->get('enable_google_analytics', '0') === '1') {
			$GaId = $this->params->get('google_analytics_id', '');
			$GTAGscript .= "
<!-- Google tag (gtag.js) -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . $GaId . "\"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', '" . $GaId . "');
</script>
";
		}

			$body = str_replace('<head>', '<head>' . $GTAGscript, $body);
		}

		if (version_compare(JVERSION, '4') >= 0) {
			Factory::getApplication()->setBody($body); 
		} else {
			JResponse::setBody($body);
		}
	}

	private function lookForIframes($body) {
		// don't block iframes if already accepted
		if (isset($_COOKIE['cookiesckiframes']) && $_COOKIE['cookiesckiframes'] === '1') {
			return false;
		}

		// don't block iframes if all cookies accepted
		if (isset($_COOKIE['cookiesck']) && $_COOKIE['cookiesck'] === 'yes') {
			return false;
		}

		// get the params from the plugin options
		if ($this->params->get('blockiframes', '0') == '0') {
			return false;
		}

		$app = Factory::getApplication();
		$input = $app->input;

		if ($input->get('layout') == 'edit' || $input->get('task') == 'edit' || $input->get('func') == 'edit') {
			return false;
		}

		// test if there is no iframe, then exit immediately
		if (!stristr($body, "<iframe"))
			return;

		return true;
	}

	private function replaceIframe($matches) {
		$iframe = $matches[0];
		$iframeText = $this->params->get('blockiframes_textimage', 'image') === 'text' ? '<div class="cookiesck-iframe-wrap-text">' . Text::_($this->params->get('blockiframes_text', 'COOKIESCK_IFRAME_TEXT')) . '</div>' : '';
		$iframe = '<div class="cookiesck-iframe-wrap">' . $iframeText . str_replace('src=', 'data-cookiesck-src=', $iframe) . '</div>';

		return $iframe;
	}

	/**
	* Provide a list of known cookies
	*/
	private function getList() {
		$key = 'database';
		$cache = Factory::getCache('cookiesck', '');
		if ($cache->contains($key))
		{
			return $list = $cache->get($key);
		}
		
		$listFile = JPATH_SITE . '/plugins/system/cookiesck/assets/open-cookie-database.csv';

		$list = file_get_contents($listFile);
		$Data = str_getcsv($list, "\n");

		$Rows = array();
		foreach($Data as &$Row) {
			$Row = str_getcsv($Row, ",");
			if (isset($Row[3])) $Rows[$Row[3]] = $Row;
		}
		$cache->store($Rows, $key);

		return $Rows;

	}

	// scan the website to find the cookies, only for admin
	private function scan() {
		// get the list of existing cookies
		$cookiesList = $this->getList();

		$session = Factory::getSession();

		$list = array();

		foreach ($_COOKIE as $key => $value) {
			$string = '';
			// do not list the session, nor the cookiesck
			if (
				$key != 'cookiesck'
				&& $value != $session->getId()
				&& $key != $session->getName()
				)
			{
				if (array_key_exists($key, $cookiesList)) {
					$string = addslashes(implode('|CK|', $cookiesList[$key]));
					$list[] = $string;
				} else if (
					$key != 'cookiesck'
					&& $value != $session->getId()
					&& $key != $session->getName()
					)
				{ 
					foreach ($cookiesList as $name => $cookie) {
						if (substr($key, 0, strlen($name)) == $name) {
							$list[] = addslashes(implode('|CK|', $cookiesList[$name]));
						}
					}

					// if the cookie does not exist in the list, set as unknown
					$string = addslashes(implode('|CK|', array('', 'Unknown', 'Unknown', $key, '', '')));
					if (strlen($key) !== 32 && ! in_array($string, $list)) {
						$list[] = $string;
					}
				}
			}
		}

		// close the list
		$js = "var COOKIESCK_LIST = ['" . implode("','", $list) . "'];";
		$js .= "
window.addEventListener('load', function(event) {
	window.parent.ckGetScannedCookies(COOKIESCK_LIST);
});";
		$doc = Factory::getDocument();
		$doc->addScript(Uri::root(true) . '/plugins/system/cookiesck/assets/admin.js');
		$doc->addScriptDeclaration($js);
	}

	private function getTree($value) {
		$tree = json_decode(str_replace('|QQ|', '"', $value));

		// manage Google Consent
		if ($this->params->get('enable_google_consent', '0') === '1') {
			$createAnalytics = $this->params->get('google_consent_analytics', 'denied') !== 'disabled';
			$createAd = $this->params->get('google_consent_ad', 'denied') !== 'disabled';
			$legalAnalytics = $this->params->get('google_consent_analytics', 'denied') === 'granted' ? '1' : '0';
			$legalAd = $this->params->get('google_consent_ad', 'denied') === 'granted' ? '1' : '0';

			foreach ($tree as $name => $obj) {
				if (strtolower($name) === 'analytics' && $this->params->get('google_consent_analytics', 'denied') !== 'disabled') {
					$tree->$name->platforms->cookiesckgoogleanalytics = new stdClass();
					$tree->$name->platforms->cookiesckgoogleanalytics->name = 'cookiesckgoogleanalytics';
					$tree->$name->platforms->cookiesckgoogleanalytics->desc = '';
					$tree->$name->platforms->cookiesckgoogleanalytics->legal = $legalAnalytics;
					$tree->$name->platforms->cookiesckgoogleanalytics->attrs = array('func' => 'updateGtag');
					$createAnalytics = false;
				}
				if (strtolower($name) === 'advertisement' && $this->params->get('google_consent_ad', 'denied') !== 'disabled') {
					$tree->$name->platforms->cookiesckgooglead = new stdClass();
					$tree->$name->platforms->cookiesckgooglead->name = 'cookiesckgooglead';
					$tree->$name->platforms->cookiesckgooglead->desc = '';
					$tree->$name->platforms->cookiesckgooglead->legal = $legalAd;
					$tree->$name->platforms->cookiesckgooglead->attrs = array('func' => 'updateGtag');
					$createAd = false;
				}
			}

			if ($createAnalytics === true) {
				$tree->analytics = new stdClass();
				$tree->analytics->name = 'analytics';
				$tree->analytics->desc = '';
				$tree->analytics->platforms = new stdClass();
				$tree->analytics->platforms->cookiesckgoogleanalytics = new stdClass();
				$tree->analytics->platforms->cookiesckgoogleanalytics->name = 'cookiesckgoogleanalytics';
				$tree->analytics->platforms->cookiesckgoogleanalytics->desc = '';
				$tree->analytics->platforms->cookiesckgoogleanalytics->legal = $legalAnalytics;
				$tree->analytics->platforms->cookiesckgoogleanalytics->attrs = array('func' => 'updateGtag');
			}
			if ($createAd === true) {
				$tree->ad = new stdClass();
				$tree->ad->name = 'advertisement';
				$tree->ad->desc = '';
				$tree->ad->platforms = new stdClass();
				$tree->ad->platforms->cookiesckgooglead = new stdClass();
				$tree->ad->platforms->cookiesckgooglead->name = 'cookiesckgooglead';
				$tree->ad->platforms->cookiesckgooglead->desc = '';
				$tree->ad->platforms->cookiesckgooglead->legal = $legalAd;
				$tree->ad->platforms->cookiesckgooglead->attrs = array('func' => 'updateGtag');
			}
			}

		return $tree;
	}

	private function listCookies($value) {
		$defaultCats = ['functional', 'analytics', 'marketing', 'essential', 'advertisement'];
		$hasCookies = false;
		$tree = $this->getTree($value);
		$html = '<div class="cookiesck-main">'
//				. '<div class="cookiesck-main-close">×</div>'
				. '<div class="cookiesck-main-title">' . (Text::_('COOKIESCK_USER_INTERFACE')) . '</div>'
				. '<div class="cookiesck-main-desc">' . (Text::_('COOKIESCK_INFO2')) . '</div>'
				. '<div class="cookiesck-main-buttons">'
				. '<div class="cookiesck-accept cookiesck_button" role="button" tabindex="0">' . Text::_('COOKIESCK_ACCEPT_ALL') . '</div>'
				. '<div class="cookiesck-decline cookiesck_button" role="button" tabindex="0">' . Text::_('COOKIESCK_DECLINE_ALL') . '</div>'
				. ($this->params->get('enable_readmore','1') === '1' && $this->readmoreLink['href'] ? '<a class="cookiesck_button" href="' . $this->readmoreLink['href'] . '" ' . $this->readmoreLink['rel'] . ' target="' . $this->readmoreLink['target'] . '" id="cookiesck_readmore">' . addslashes(Text::_('COOKIESCK_MORE')) . '</a>' : '')
				. '</div>'
				;

		$i = 0;
		foreach ($tree as $category) {
			$hasCookies = true;
			if (in_array(strtolower($category->name), $defaultCats) && ! trim($category->desc)) {
				$desc = trim($category->desc) ? Text::_($category->desc) : Text::_('COOKIESCK_CATEGORY_' . strtoupper($category->name) . '_DESC');
			} else {
				$desc = trim($category->desc) ? Text::_($category->desc) : '';
			}
			$html .= '<div class="cookiesck-category" data-category="' . htmlspecialchars(strtolower($category->name)) . '">'
			. '<div class="cookiesck-category-name">' . (Text::_('COOKIESCK_CATEGORY_' . strtoupper($category->name)) !== 'COOKIESCK_CATEGORY_' . strtoupper($category->name) ? Text::_('COOKIESCK_CATEGORY_' . strtoupper($category->name)) : Text::_($category->name)) . '</div>'
			. '<div class="cookiesck-category-desc">' . $desc . '</div>';

			$useSwitchers = $this->params->get('buttontype', 'button') === 'switcher';
			$buttonStyle = $useSwitchers ? ' style="display:none;" ' : '';
			foreach ($category->platforms as $platform) {
				$attrs = '';
				if (isset($platform->attrs)) {
					foreach ($platform->attrs as $name => $value) {
						$attrs .= ' data-' . $name . '="' . $value . '"';
					}
				}
				$html .= '<div class="cookiesck-platform" data-platform="' . htmlspecialchars($platform->name) . '" ' . $attrs . '>'
				. '<div class="cookiesck-platform-name">' . Text::_($platform->name) . '</div>'
				. '<div class="cookiesck-platform-desc">' . Text::_($platform->desc) . '</div>'
				. '<div ' . $buttonStyle . ' class="cookiesck-accept cookiesck_button" role="button" tabindex="0" aria-label="' . Text::_('COOKIESCK_ACCEPT') . ' : ' . htmlspecialchars($platform->name) . '">' . Text::_('COOKIESCK_ACCEPT') . '</div>'
				. (strtolower($category->name) !== 'essential' ? '<div ' . $buttonStyle . ' class="cookiesck-decline cookiesck_button" role="button" tabindex="0" aria-label="' . Text::_('COOKIESCK_DECLINE') . ' : ' . htmlspecialchars($platform->name) . '">' . Text::_('COOKIESCK_DECLINE') . '</div>' : '')
				. ($useSwitchers && strtolower($category->name) !== 'essential' ? '<input type="checkbox" id="cookiesck-switch-' . $i . '" style="display: none;" /><label role="button" tabindex="0" for="cookiesck-switch-' . $i . '" class="cookiesck_button_switch"><span class="cookiesck_button_switcher"></span></label>' : '')
				. '</div>'
				;
				$i++;
			}
			$html .= '</div>';
			$i++;
		}

		if ($this->params->get('blockiframes', '0') == '1') {
			// add buttons to manage the iframes choice @TODO
		}

		// add the save button at the end to have the correct order when navigating with tab
		$html .= '<div class="cookiesck-main-close" role="button" tabindex="0">' . (Text::_('COOKIESCK_SAVE')) . '</div>';
		// close the main section
		$html .= '</div>';

		// echo'<pre>';var_dump($html);echo'</pre>';
		return $hasCookies ? $html : false;
	}

	private function getAllowedCookies($value) {
		$tree = $this->getTree($value);
		$session = Factory::getSession();
		// get the user settings from the cookies, else use the plugin options
		$cookiesckParams = $this->getCookiesckParams();

		if (isset($cookiesckParams['cookiesckgoogleanalytics'])) {
			$cookiesckgoogleanalytics = $cookiesckParams['cookiesckgoogleanalytics'] === '1' ? 'granted' : 'denied';
		} else {
			$cookiesckgoogleanalytics = $this->params->get('google_consent_analytics', 'denied');
		}
		if (isset($cookiesckParams['cookiesckgooglead'])) {
			$cookiesckgooglead = $cookiesckParams['cookiesckgooglead'] === '1' ? 'granted' : 'denied';
		} else {
			$cookiesckgooglead = $this->params->get('google_consent_ad', 'denied');
		}

		// setup default allowed cookies
		$allowed = array('cookiesck', 'cookiesckiframes', 'cookiesckuniquekey', 'jform_captchacookie', $session->getName());

		if ($cookiesckgoogleanalytics === 'granted') {
			$allowed[] = '_ga';
			$allowed[] = '_ga_' . str_replace('G-', '', $this->params->get('google_analytics_id', ''));
		}

		$platforms = array();

		foreach ($tree as $category) {
			// special needs for essential cookies
			if (strtolower($category->name) == 'essential') {
				foreach($category->platforms as $platform) {
					foreach ($platform->cookies as $cookie) {
						$allowed[] = $cookie->key;
					}
				}
			} else {
				// check if the cookie has a legitimate interest, add it to the list
				foreach($category->platforms as $platform) {
					if (isset($platform->legal) && $platform->legal === 1) {
						foreach ($platform->cookies as $cookie) {
							$allowed[] = $cookie->key;
						}
					}
				}
			}

			// construct the platforms
			foreach ($category->platforms as $platform) {
				$platforms[$platform->name] = $platform;
			}
		}

		if (isset($_COOKIE['cookiesck'])) {
			$cookiesckvalue = $_COOKIE['cookiesck'];
			$cookiesckvalue = urldecode($cookiesckvalue);
			// check if the new value from V3 has been set, or if value = yes or no
			if (strpos($cookiesckvalue, '|ck|')) {
				$cookiesckrows = explode('|ck|', $cookiesckvalue);

				foreach ($cookiesckrows as $cookiesckrow) {
					$values = explode('|val|', $cookiesckrow);
					if ($values[1] === '1') {
						if (isset($platforms[$values[0]])) {
							if (isset($platforms[$values[0]]->cookies)) {
								foreach($platforms[$values[0]]->cookies as $cookie) {
									$allowed[] = $cookie->key;
								}
							}
						}
					} else {
						// check if the cookies has been refused, remove it from the allowed list
						if (isset($platforms[$values[0]])) {
							if (isset($platforms[$values[0]]->cookies)) {
								foreach($platforms[$values[0]]->cookies as $cookie) {
									$arraypos = array_search($cookie->key, $allowed);
									if ($arraypos) unset($allowed[$arraypos]);
								}
							}
						}
					}
				}
			}
		}

		// hack for admin session, else it will be lost
		if (! empty($_COOKIE)) {
			foreach ($_COOKIE as $name => $key) {
				if (strlen($name) === 32) $allowed[] = $name;
			}
		}

		return array_values($allowed);
	}

	private function getDefaultValue($value) {
		$tree = $this->getTree($value);

		$value = '';
		$count = 0;
		foreach ($tree as $category) {
			foreach($category->platforms as $platform) {

				$value .= '|ck|' . $platform->name . '|val|';
				if (strtolower($category->name) == 'essential') {
					$value .= '1';
				} elseif (isset($platform->legal)) {
					$value .= $platform->legal;
					$count += $platform->legal;
				} else {
					$value .= '0';
				}
			}
		}

		$value = substr($value, 4);

		

		if (substr_count($value, '|val|0') == 0) {
			$value = 'yes';
		} elseif ($count == 0) {
			$value = 'no';
		}

		return $value;
	}

	private function logActions() {
		return; // is not enabled in the light version)
		include_once JPATH_ROOT . '/administrator/components/com_cookiesck/helpers/ckfof.php';

		$input = Factory::getApplication()->input;
		$data = array();
		$data['id'] = 0;

		// unique id : from the cookie value, or by default the session ID
		$session = Factory::getSession();
		$uniquekey = isset($_COOKIE['cookiesckuniquekey']) ? $_COOKIE['cookiesckuniquekey'] : $session->getId();
		$data['uniquekey'] = $uniquekey;

		// user action
		$set_cookieck = $input->get('set_cookieck', '');
		switch ($set_cookieck) {
			case '0' :
				$data['action'] = 'decline all';
				break;
			case '1' :
				$data['action'] = 'accept all';
				break;
			case 'update' :
				$data['action'] = 'update';
				break;
			default :
				exit('error no cookie action here');;
				break;
		}

		// IP
		$ip = NULL;
		$deep_detect = true;
		if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
			$ip = $_SERVER["REMOTE_ADDR"];
			if ($deep_detect) {
				if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
					$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
		}
		// mask the last characters to anonymize it
		$array_ip = explode(".", $ip);
		if (count($array_ip) && count($array_ip) == 4) {
			list($s1,$s2,$s3,$s4) = $array_ip;
		} else {
			$s1 = $ip;
			$s2 = $s3 = '';
		}
		$masked_ip = $s1.".".$s2.".".$s3.".x"; // 128.123.1.x
		$data['IP'] = $masked_ip;

		// date
//		$timezone = +1; //(GMT +1:00) Europe 
		// date('Y-m-d H:i:s') ;
//		$data['date'] = gmdate('Y-m-d H:i:s', time() + 3600*($timezone+date("I")));
		$data['date'] = date('Y-m-d H:i:s');

		// url
		$data['url_from'] = trim(Uri::root(), '/') . $_SERVER['REQUEST_URI'];
		// $uri = Uri::getInstance();
		// $url = $uri->getQuery();

		// form ID
		$data['form_id'] = 'cookiesck_interface';

		// categories
		$selection = $input->get('cookiesck_vars', '', 'string');
		$selection = str_replace('|ck|', '&', $selection);
		$selection = str_replace('|val|', '=', $selection);
		$data['selection'] = $selection;

		// text
		$data['text_agreed'] = ''; // to complete

		// get user ID 
		$user = Factory::getUser();
		$data['user_id'] = $user->id;

		// search for existing data
		$query = "SELECT id FROM #__cookiesck_logs WHERE uniquekey = '" . $data['uniquekey'] . "'";
		$id = Cookiesck\CKFof::dbLoadResult($query);
		if (! $id) $id = 0;
		$data['id'] = $id;

		// save the data
		Cookiesck\CKFof::dbStore('#__cookiesck_logs', $data);

		// end of the ajax request
		exit;
	}

	// decode the cookiesck cookie to access the properties
	private function getCookiesckParams() {
		$cookies = array();

		if (! isset($_COOKIE['cookiesck'])) return false;
		$cookiesckvalue = $_COOKIE['cookiesck'];
			$cookiesckvalue = urldecode($cookiesckvalue);
			// check if the new value from V3 has been set, or if value = yes or no
			if (strpos($cookiesckvalue, '|ck|') || strpos($cookiesckvalue, '|val|')) {
				$cookiesckrows = explode('|ck|', $cookiesckvalue);

				foreach ($cookiesckrows as $cookiesckrow) {
					$values = explode('|val|', $cookiesckrow);
						$cookies[$values[0]] = $values[1];
}
			}

			return $cookies;
	}

	/**
	 * Test if there is already a unit, else add the px
	 *
	 * @param string $value
	 * @return string
	 */
	function testUnit($value, $defaultunit = "px") {

		if ((stristr($value, 'px')) OR (stristr($value, 'em')) OR (stristr($value, '%')) OR $value == 'auto')
			return $value;

		return $value . $defaultunit;
	}
}