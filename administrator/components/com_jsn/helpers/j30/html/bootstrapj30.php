<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for Bootstrap elements.
 *
 * @package     Joomla.Libraries
 * @subpackage  HTML
 * @since       3.0
 */
abstract class JHtmlBootstrapJ30 extends JHtmlBootstrap
{
	protected static $loaded = array();

	public static function startTabSet($selector = 'myTab', $params = array())
	{
		$sig = md5(serialize(array($selector, $params)));

		if (!isset(self::$loaded[__METHOD__][$sig]))
		{
			// Include Bootstrap framework
			self::framework();

			// Setup options object
			$opt['active'] = (isset($params['active']) && ($params['active'])) ? (string) $params['active'] : '';

			$options = JHtml::getJSObject($opt);

			// Attach tabs to document
			JFactory::getDocument()
				->addScriptDeclaration("(function($){
									$('#$selector a').click(function (e)
									{
										e.preventDefault();
										$(this).tab('show');
									});
								})(jQuery);");

			// Set static array
			self::$loaded[__METHOD__][$sig] = true;
			self::$loaded[__METHOD__][$selector]['active'] = $opt['active'];
		}

		$html = '<ul class="nav nav-tabs" id="'.$selector.'Tabs"></ul>
		<div class="tab-content" id="'.$selector.'Content">';

		return $html;
	}

	public static function endTabSet()
	{
		$html = '</div>';

		return $html;
	}

	public static function addTab($selector, $id, $title)
	{
		static $tabScriptLayout = null;
		static $tabLayout = null;

		$tabScriptLayout = is_null($tabScriptLayout) ? new JLayoutFile('addtabscript') : $tabScriptLayout;
		$tabLayout = is_null($tabLayout) ? new JLayoutFile('addtab') : $tabLayout;

		$active = (self::$loaded['JHtmlBootstrapJ30::startTabSet'][$selector]['active'] == $id) ? ' active' : '';

		// Inject tab into UL
		JFactory::getDocument()
		->addScriptDeclaration("(function($){
						$(document).ready(function() {
							// Handler for .ready() called.
							var tab = $('<li class=\"$active\"><a href=\"#$id\" data-toggle=\"tab\">$title</a></li>');
							$('#" . $selector . "Tabs').append(tab);
						});
					})(jQuery);");

		$html = '<div id="'.$id.'" class="tab-pane'.$active.'">';

		return $html;
	}

	public static function endTab()
	{
		$html = '</div>';

		return $html;
	}
	
}

?>
