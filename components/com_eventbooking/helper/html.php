<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

class EventbookingHelperHtml
{
	/**
	 * Method to get customized language item
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   string                  $languageItem
	 *
	 * @return string
	 */
	public static function getCustomizedLanguageItem($event, $languageItem)
	{
		$language = Factory::getLanguage();

		if ($language->hasKey($languageItem . '_EVENT_' . $event->id))
		{
			$languageItem = $languageItem . '_EVENT_' . $event->id;
		}
		elseif ($language->hasKey($languageItem . '_CAT_' . $event->main_category_id))
		{
			$languageItem = $languageItem . '_CAT_' . $event->main_category_id;
		}

		return $languageItem;
	}

	/**
	 * Get supported tags for given message
	 *
	 * @param   string  $name
	 *
	 * @return array
	 */
	public static function getSupportedTags($name)
	{
		static $eventTags, $registrationTags;

		if ($eventTags === null)
		{
			$eventTags = EventbookingHelperEmailtags::getEventTags();
		}

		if ($registrationTags === null)
		{
			$registrationTags = EventbookingHelperEmailtags::getRegistrationTags();
		}

		$tags = [];

		$commonEmailTags = array_merge($eventTags, $registrationTags);

		switch ($name)
		{
			case 'registration_form_message':
			case 'registration_form_message_group':
			case 'waitinglist_form_message':
				$tags = $eventTags;
				break;
			case 'number_members_form_message':
				$tags = [
					'MIN_NUMBER_REGISTRANTS',
					'MAX_NUMBER_REGISTRANTS',
				];
				break;
			case 'submit_event_form_message':
				break;
			case 'mass_mail_template':
			case 'thanks_message':
			case 'thanks_message_offline':
			case 'cancel_message':
				$tags = $commonEmailTags;
				break;
			case 'admin_email_subject':
			case 'admin_email_body':
				$tags = $commonEmailTags;
				break;
			case 'user_email_subject':
			case 'user_email_body':
			case 'user_email_body_offline':
				$tags = $commonEmailTags;
				break;
			case 'group_member_email_subject':
			case 'group_member_email_body':
				$tags = EventbookingHelperEmailtags::getGroupMemberEmailTags();
				break;
			case 'registration_approved_email_subject':
			case 'registration_approved_email_body':
				$tags = $commonEmailTags;
				break;
			case 'admin_registration_approved_email_subject':
			case 'admin_registration_approved_email_body':
				$tags = array_merge($commonEmailTags, [
					'APPROVAL_USER_USERNAME',
					'APPROVAL_USER_NAME',
					'APPROVAL_USER_EMAIL',
					'APPROVAL_USER_ID',
				]);
				break;
			case 'certificate_email_subject':
			case 'certificate_email_body':
				$tags = $commonEmailTags;
				break;
			case 'event_cancel_email_subject':
			case 'event_cancel_email_body':
				$tags = $commonEmailTags;
				break;
			case 'send_registrants_list_email_subject':
			case 'send_registrants_list_email_body':
				$tags = $eventTags;
				break;
			case 'reminder_email_subject':
			case 'reminder_email_body':
			case 'second_reminder_email_subject':
			case 'second_reminder_email_body':
			case 'third_reminder_email_subject':
			case 'third_reminder_email_body':
				$tags = $registrationTags;
				break;
			case 'offline_payment_reminder_email_subject':
			case 'offline_payment_reminder_email_body':
				$tags = $registrationTags;
				break;
			case 'registration_cancel_confirmation_message':
			case 'waiting_list_cancel_confirmation_message':
				$tags = $registrationTags;
				break;
			case 'registration_cancel_message_paid':
			case 'registration_cancel_message_free':
			case 'waiting_list_cancel_complete_message':
				$tags = $registrationTags;
				break;
			case 'registration_cancel_confirmation_email_subject':
			case 'registration_cancel_confirmation_email_body':
			case 'waiting_list_cancel_confirmation_email_subject':
			case 'waiting_list_cancel_confirmation_email_body':
				$tags = $commonEmailTags;
				break;
			case 'registration_cancel_email_subject':
			case 'registration_cancel_email_body':
			case 'waiting_list_cancel_notification_email_subject':
			case 'waiting_list_cancel_notification_email_body':
				$tags = $commonEmailTags;
				break;
			case 'user_registration_cancel_subject':
			case 'user_registration_cancel_message':
				$tags = $commonEmailTags;
				break;
			case 'admin_cancel_registration_notification_email_subject':
			case 'admin_cancel_registration_notification_email_body':
				$tags = array_merge($commonEmailTags, [
					'CANCELLED_BY_USER_USERNAME',
					'CANCELLED_BY_USER_NAME',
					'CANCELLED_BY_USER_EMAIL',
					'CANCELLED_BY_USER_ID',
				]);
				break;
			case 'submit_event_user_email_subject':
			case 'submit_event_user_email_body':
			case 'submit_event_admin_email_subject':
			case 'submit_event_admin_email_body':
			case 'event_approved_email_subject':
			case 'event_approved_email_body':
			case 'event_update_email_subject':
			case 'event_update_email_body':
				$tags = [
					'USER_ID',
					'USERNAME',
					'EMAIL',
					'NAME',
					'EVENT_ID',
					'EVENT_TITLE',
					'EVENT_DATE',
					'EVENT_LINK',
				];
				break;
			case 'invitation_email_subject':
			case 'invitation_email_body':
				$tags = array_merge($eventTags, [
					'SENDER_NAME',
					'PERSONAL_MESSAGE',
					'EVENT_DETAIL_LINK',
				]);
				break;
			case 'waitinglist_complete_message':
				$tags = $commonEmailTags;
				break;
			case 'watinglist_confirmation_subject':
			case 'watinglist_confirmation_body':
			case 'watinglist_notification_subject':
			case 'watinglist_notification_body':
				$tags = $commonEmailTags;
				break;
			case 'registrant_waitinglist_notification_subject':
			case 'registrant_waitinglist_notification_body':
				$tags = [
					'registrant_first_name',
					'registrant_last_name',
					'event_link',
					'event_title',
					'event_date',
					'event_end_date',
					'first_name',
					'last_name',
				];
				break;
			case 'request_payment_email_subject':
			case 'request_payment_email_body':
			case 'request_payment_email_subject_pdr':
			case 'request_payment_email_body_pdr':
			case 'deposit_payment_reminder_email_subject':
			case 'deposit_payment_reminder_email_body':
				$tags = $commonEmailTags;
				break;
			case 'registration_payment_form_message':
				$tags = array_merge(['AMOUNT', 'REGISTRATION_ID'], $eventTags);
				break;
			case 'deposit_payment_form_message':
				$tags = array_merge(['AMOUNT', 'REGISTRATION_ID'], $eventTags);
				break;
			case 'deposit_payment_thanks_message':
			case 'deposit_payment_admin_email_subject':
			case 'deposit_payment_admin_email_body':
			case 'deposit_payment_user_email_subject':
			case 'deposit_payment_user_email_body':
				$tags = EventbookingHelperEmailtags::getDepositPaymentTags();
				break;
			case 'new_registration_admin_sms':
			case 'first_reminder_sms':
			case 'second_reminder_sms':
				$tags = EventbookingHelperEmailtags::getSMSTags();
				break;
			case 'invoice_format':
				$tags = $commonEmailTags;

				// Remove the tags not used from common email tags
				unset($tags['total_amount'], $tags['discount_amount'], $tags['tax_amount'], $tags['item_name']);

				$tags = array_merge($tags, [
					'invoice_number',
					'invoice_date',
					'INVOICE_STATUS',
					'ITEM_QUANTITY',
					'ITEM_AMOUNT',
					'ITEM_SUB_TOTAL',
					'DISCOUNT_AMOUNT',
					'SUB_TOTAL',
					'TAX_AMOUNT',
					'PAYMENT_PROCESSING_FEE',
					'TOTAL_AMOUNT',
					'PAID_AMOUNT',
					'ITEM_NAME',
					'ITEM_RATE',
				]);
				break;
			case 'invoice_format_cart':
				$tags = $commonEmailTags;

				// Remove the tags not used from common email tags
				unset($tags['total_amount'], $tags['discount_amount'], $tags['tax_amount']);

				$tags = array_merge($commonEmailTags, [
					'invoice_number',
					'invoice_date',
					'INVOICE_STATUS',
					'ALL_GROUP_MEMBERS_NAMES',
					'TOTAL_NUMBER_REGISTRANTS',
					'EVENTS_LIST',
					'SUB_TOTAL',
					'DISCOUNT_AMOUNT',
					'TAX_AMOUNT',
					'PAYMENT_PROCESSING_FEE',
					'TOTAL_AMOUNT',
					'DEPOSIT_AMOUNT',
					'DUE_AMOUNT',
				]);
				break;
		}

		return array_map('strtoupper', $tags);
	}

	/* Get link to article, multilingual association is supported
	 *
	 * @param   int  $articleId
	 *
	 * @return string
	 */
	public static function getArticleUrl($articleId)
	{
		$config = EventbookingHelper::getConfig();

		if (Multilanguage::isEnabled())
		{
			$associations = Associations::getAssociations('com_content', '#__content', 'com_content.item', $articleId);
			$langCode     = Factory::getLanguage()->getTag();

			if (isset($associations[$langCode]))
			{
				$article = $associations[$langCode];
			}
		}

		if (!isset($article))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id, catid')
				->from('#__content')
				->where('id = ' . (int) $articleId);
			$db->setQuery($query);
			$article = $db->loadObject();
		}

		if (!$article)
		{
			return '';
		}

		if (EventbookingHelper::isJoomla4())
		{
			$articleUrl = RouteHelper::getArticleRoute($article->id, $article->catid);
		}
		else
		{
			JLoader::register('ContentHelperRoute', JPATH_ROOT . '/components/com_content/helpers/route.php');

			$articleUrl = ContentHelperRoute::getArticleRoute($article->id, $article->catid);
		}

		if ($config->open_article_on_new_window)
		{
			return $articleUrl . '&format=html';
		}

		return $articleUrl . '&tmpl=component&format=html';
	}

	/**
	 * Method to add overridable js files to document
	 *
	 * @param   string|array  $files
	 * @param   array         $options
	 * @param   array         $attribs
	 */
	public static function addOverridableScript($files, $options = [], $attribs = [])
	{
		$config   = EventbookingHelper::getConfig();
		$document = Factory::getApplication()->getDocument();
		$rootUri  = Uri::root(true);
		$files    = (array) $files;

		foreach ($files as $file)
		{
			if ($config->debug)
			{
				$file = str_replace('.min.js', '.js', $file);
			}

			$parts             = explode('/', $file);
			$count             = count($parts);
			$parts[$count - 1] = 'override.' . $parts[$count - 1];
			$overridableFile   = implode('/', $parts);

			if (File::exists(JPATH_ROOT . '/' . $overridableFile))
			{
				$file = $overridableFile;
			}

			$document->addScript($rootUri . '/' . $file, $options, $attribs);
		}
	}

	/**
	 * Get option for calendar tooltip
	 *
	 * @param   array  $params
	 *
	 * @return string
	 */
	public static function getCalendarTooltipOptions($params = [])
	{
		// Setup options object
		$opt['animation'] = isset($params['animation']) ? (bool) $params['animation'] : null;
		$opt['html']      = isset($params['html']) ? (bool) $params['html'] : true;
		$opt['placement'] = isset($params['placement']) ? (string) $params['placement'] : null;
		$opt['selector']  = isset($params['selector']) ? (string) $params['selector'] : null;
		$opt['title']     = isset($params['title']) ? (string) $params['title'] : null;
		$opt['trigger']   = isset($params['trigger']) ? (string) $params['trigger'] : null;
		$opt['delay']     = isset($params['delay']) ? (is_array($params['delay']) ? $params['delay'] : (int) $params['delay']) : null;
		$opt['container'] = $params['container'] ?? 'body';
		$opt['template']  = isset($params['template']) ? (string) $params['template'] : null;
		$opt['sanitize']  = isset($params['sanitize']) ? (string) $params['sanitize'] : false;

		if (EventbookingHelper::isJoomla4())
		{
			return EventbookingHelperJquery::getJSObject($opt);
		}

		return HTMLHelper::getJSObject($opt);
	}

	public static function renderaddEventsToCartHiddenForm($Itemid = 0)
	{
		static $rendered = false;

		if (!$rendered)
		{
			$rendered = true;
			?>
			<form name="addEventsToCart" id="addEventsToCart"
			      action="<?php
			      echo Route::_('index.php?option=com_eventbooking&task=cart.add_events_to_cart&Itemid=' . $Itemid); ?>"
			      method="post">
				<input type="hidden" name="event_ids" id="selected_event_ids" value=""/>
			</form>
			<script language="javascript">
				(function ($) {
					addSelectedEventsToCart = function () {
						var selectedEventIds = $('input[name="event_ids[]"]:checked').map(
							function () {
								return this.value;
							}).get().join(",");


						if (selectedEventIds.length == 0) {
							alert("<?php echo Text::_('EB_PLEASE_SELECT_EVENTS', true); ?>");

							return;
						}

						var form = document.addEventsToCart;
						form.selected_event_ids.value = selectedEventIds;
						form.submit();
					}
				})(Eb.jQuery);
			</script>
			<?php
		}
	}

	/**
	 * Helper method to add multiple language strings to JS
	 *
	 * @param   array  $items
	 */
	public static function addJSStrings($items = [])
	{
		foreach ($items as $item)
		{
			Text::script($item, true);
		}
	}

	/**
	 * Render ShowOn string
	 *
	 * @param   array  $fields
	 *
	 * @return string
	 */
	public static function renderShowOn($fields)
	{
		$output = [];

		$i = 0;

		foreach ($fields as $name => $values)
		{
			$i++;

			$values = (array) $values;

			$data = [
				'field'  => $name,
				'values' => $values,
				'sign'   => '=',
			];

			$data['op'] = $i > 1 ? 'AND' : '';

			$output[] = json_encode($data);
		}

		return '[' . implode(',', $output) . ']';
	}

	/***
	 * Get javascript code for showing calendar form field on ajax request result
	 *
	 * @param $fields
	 *
	 * @return string
	 */
	public static function getCalendarSetupJs($fields = [])
	{
		return 'calendarElements = document.querySelectorAll(".field-calendar");
                    for (i = 0; i < calendarElements.length; i++) {
                    JoomlaCalendar.init(calendarElements[i]);
                    }';
	}

	/**
	 * Get category tree
	 *
	 * @param   array       $rows
	 * @param   string|int  $selectCategoryValue
	 * @param   string      $selectCategoryText
	 *
	 * @return array
	 */
	public static function getCategoryOptions($rows, $selectCategoryValue = 0, $selectCategoryText = 'EB_SELECT_CATEGORY')
	{
		$children = [];

		// first pass - collect children
		foreach ($rows as $v)
		{
			$pt            = $v->parent_id;
			$list          = $children[$pt] ?? [];
			$list[]        = $v;
			$children[$pt] = $list;
		}

		$list      = HTMLHelper::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);
		$options   = [];
		$options[] = HTMLHelper::_('select.option', $selectCategoryValue, Text::_($selectCategoryText));

		foreach ($list as $item)
		{
			$options[] = HTMLHelper::_('select.option', $item->id, $item->treename);
		}

		return $options;
	}

	/**
	 * Get categories filter dropdown
	 *
	 * @param   string  $name
	 * @param   int     $selected
	 * @param   string  $attributes
	 * @param   string  $fieldSuffix
	 * @param   array   $filters
	 * @param   int     $selectCategoryValue
	 * @param   string  $selectCategoryText
	 */
	public static function getCategoryListDropdown(
		$name,
		$selected,
		$attributes = null,
		$fieldSuffix = null,
		$filters = [],
		$selectCategoryValue = 0,
		$selectCategoryText = 'EB_SELECT_CATEGORY'
	) {
		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select('id, parent AS parent_id')
			->select($db->quoteName('name' . $fieldSuffix, 'title'))
			->from('#__eb_categories')
			->where('published = 1')
			->order($config->get('category_dropdown_ordering', $db->quoteName('name' . $fieldSuffix)));

		foreach ($filters as $filter)
		{
			$query->where($filter);
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$options = EventbookingHelper::callOverridableHelperMethod('Html', 'getCategoryOptions', [$rows, $selectCategoryValue, $selectCategoryText]);

		return HTMLHelper::_('select.genericlist', $options, $name, $attributes, 'value', 'text', $selected);
	}

	/**
	 * Build category dropdown
	 *
	 * @param   int     $selected
	 * @param   string  $name
	 * @param   string  $attr  Extra attributes need to be passed to the dropdown
	 * @param   string  $fieldSuffix
	 *
	 * @return string
	 */
	public static function buildCategoryDropdown($selected, $name = 'parent', $attr = null, $fieldSuffix = null)
	{
		return static::getCategoryListDropdown($name, $selected, $attr, $fieldSuffix);
	}

	/**
	 * Function to render a common layout which is used in different views
	 *
	 * @param   string  $layout
	 * @param   array   $data
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function loadCommonLayout($layout, $data = [])
	{
		$app        = Factory::getApplication();
		$deviceType = EventbookingHelper::getDeviceType();
		$theme      = EventbookingHelper::getDefaultTheme();
		$layout     = str_replace('/tmpl', '', $layout);
		$filename   = basename($layout);
		$filePath   = substr($layout, 0, strlen($layout) - strlen($filename));
		$layouts    = EventbookingHelperHtml::getPossibleLayouts($filename);

		if ($deviceType !== 'desktop')
		{
			$deviceLayouts = [];

			foreach ($layouts as $layout)
			{
				$deviceLayouts[] = $layout . '.' . $deviceType;
			}

			$layouts = array_merge($deviceLayouts, $layouts);
		}

		// Build paths array to get layout from
		$paths   = [];
		$paths[] = JPATH_THEMES . '/' . $app->getTemplate() . '/html/com_eventbooking';
		$paths[] = JPATH_ROOT . '/components/com_eventbooking/themes/' . $theme->name;

		if ($theme->name != 'default')
		{
			$paths[] = JPATH_ROOT . '/components/com_eventbooking/themes/default';
		}

		$foundLayout = '';

		foreach ($layouts as $layout)
		{
			if ($filePath)
			{
				$layout = $filePath . $layout;
			}

			foreach ($paths as $path)
			{
				if (file_exists($path . '/' . $layout))
				{
					$foundLayout = $path . '/' . $layout;
					break;
				}
			}
		}

		if (empty($foundLayout))
		{
			throw new RuntimeException(Text::sprintf('The given common layout %s does not exist', $layout));
		}

		// Start an output buffer.
		ob_start();
		extract($data);

		// Load the layout.
		include $foundLayout;

		// Get the layout contents.
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Function to render a shared layout which is used for both frontend and backend, for example email templates
	 *
	 * or invoice layout
	 *
	 * @param   string  $layout
	 * @param   array   $data
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public static function loadSharedLayout($layout, $data = [])
	{
		$app       = Factory::getApplication();
		$themeFile = str_replace('/tmpl', '', $layout);

		$paths = [JPATH_THEMES . '/' . $app->getTemplate() . '/html/com_eventbooking'];

		if ($app->isClient('administrator'))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('template')
				->from('#__template_styles')
				->where('client_id = 0')
				->order('home DESC');
			$db->setQuery($query);

			foreach ($db->loadColumn() as $template)
			{
				$paths[] = JPATH_ROOT . '/templates/' . $template . '/html/com_eventbooking';
			}
		}

		$path = '';

		// Find override layout first
		foreach ($paths as $overridePath)
		{
			if (File::exists($overridePath . '/' . $themeFile))
			{
				$path = $overridePath . '/' . $themeFile;
				break;
			}
		}

		// If there is no override layout, use component layout from themes
		if (!$path)
		{
			$theme = EventbookingHelper::getDefaultTheme();
			$paths = [];

			$paths[] = JPATH_ROOT . '/components/com_eventbooking/themes/' . $theme->name;

			if ($theme->name != 'default')
			{
				$paths[] = JPATH_ROOT . '/components/com_eventbooking/themes/default';
			}

			foreach ($paths as $componentThemePath)
			{
				if (file_exists($componentThemePath . '/' . $themeFile))
				{
					$path = $componentThemePath . '/' . $themeFile;
					break;
				}
			}
		}

		if (!$path)
		{
			throw new RuntimeException(Text::sprintf('The given shared layout %s does not exist', $layout));
		}

		// Start an output buffer.
		ob_start();
		extract($data);

		// Load the layout.
		include $path;

		// Get the layout contents.
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get label of the field (including tooltip)
	 *
	 * @param           $name
	 * @param           $title
	 * @param   string  $tooltip
	 *
	 * @return string
	 */
	public static function getFieldLabel($name, $title, $tooltip = '')
	{
		$label = '';
		$text  = $title;

		// Build the class for the label.
		$class = !empty($tooltip) ? ' class="hasTooltip hasTip"' : '';

		// Add the opening label tag and main attributes attributes.
		$label .= '<label id="' . $name . '-lbl" for="' . $name . '"' . $class;

		// If a description is specified, use it to build a tooltip.
		if (!empty($tooltip))
		{
			$label .= ' title="' . HTMLHelper::tooltipText(trim($text, ':'), $tooltip, 0) . '"';
		}

		$label .= '>' . $text . '</label>';

		return $label;
	}

	/**
	 * Get bootstrapped style boolean input
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return string
	 */
	public static function getBooleanInput($name, $value)
	{
		$value = (int) $value;
		$field = FormHelper::loadFieldType('Radio');

		$element = new SimpleXMLElement('<field />');
		$element->addAttribute('name', $name);

		if (EventbookingHelper::isJoomla4())
		{
			$element->addAttribute('layout', 'joomla.form.field.radio.switcher');
		}
		else
		{
			$element->addAttribute('class', 'radio btn-group btn-group-yesno');
		}

		$element->addAttribute('default', '0');

		$node = $element->addChild('option', 'JNO');
		$node->addAttribute('value', '0');

		$node = $element->addChild('option', 'JYES');
		$node->addAttribute('value', '1');

		$field->setup($element, $value);

		return $field->input;
	}

	/**
	 * Render radio group input
	 *
	 * @param $name
	 * @param $options
	 * @param $value
	 *
	 * @return string
	 */
	public static function getRadioGroupInput($name, $options, $value)
	{
		$html = [];

		// Start the radio field output.
		$html[] = '<fieldset id="' . $name . '" class="radio btn-group btn-group-yesno">';

		$count = 0;

		foreach ($options as $optionValue => $optionText)
		{
			$checked = ($optionValue == $value) ? ' checked="checked"' : '';
			$html[]  = '<input type="radio" id="' . $name . $count . '" name="' . $name . '" value="' . $optionValue . '"' . $checked . ' />';
			$html[]  = '<label for="' . $name . $count . '">' . $optionText . '</label>';

			$count++;
		}

		// End the radio field output.
		$html[] = '</fieldset>';

		return implode($html);
	}

	/**
	 * Get available fields tags using in the email messages & invoice
	 *
	 * @param   bool  $defaultTags
	 *
	 * @return array|string
	 */
	public static function getAvailableMessagesTags($defaultTags = true)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('name')
			->from('#__eb_fields')
			->where('published = 1')
			->order('ordering');
		$db->setQuery($query);

		if ($defaultTags)
		{
			$fields = [
				'registration_detail',
				'date',
				'event_title',
				'event_date',
				'event_end_date',
				'short_description',
				'description',
				'total_amount',
				'tax_amount',
				'discount_amount',
				'late_fee',
				'payment_processing_fee',
				'amount',
				'location',
				'number_registrants',
				'invoice_number',
				'transaction_id',
				'id',
				'payment_method',
			];
		}
		else
		{
			$fields = [];
		}

		$fields = array_merge($fields, $db->loadColumn());

		$fields = array_map('strtoupper', $fields);
		$fields = '[' . implode('], [', $fields) . ']';

		return $fields;
	}

	/**
	 * Get URL to add the given event to Google Calendar
	 *
	 * @param $row
	 *
	 * @return string
	 */
	public static function getAddToGoogleCalendarUrl($row)
	{
		$eventData = self::getEventDataArray($row);

		$queryString['title']       = 'text=' . $eventData['title'];
		$queryString['dates']       = 'dates=' . $eventData['dates'];
		$queryString['location']    = 'location=' . $eventData['location'];
		$queryString['trp']         = 'trp=false';
		$queryString['websiteName'] = 'sprop=' . $eventData['sitename'];
		$queryString['websiteURL']  = 'sprop=name:' . $eventData['siteurl'];
		$queryString['details']     = 'details=' . $eventData['details'];

		return 'https://www.google.com/calendar/event?action=TEMPLATE&' . implode('&', $queryString);
	}

	/**
	 * Get URL to add the given event to Yahoo Calendar
	 *
	 * @param $row
	 *
	 * @return string
	 */
	public static function getAddToYahooCalendarUrl($row)
	{
		$eventData = self::getEventDataArray($row);

		$urlString['title']      = 'title=' . $eventData['title'];
		$urlString['st']         = 'st=' . $eventData['st'];
		$urlString['et']         = 'et=' . $eventData['et'];
		$urlString['rawdetails'] = 'desc=' . $eventData['details'];
		$urlString['location']   = 'in_loc=' . $eventData['location'];

		return 'https://calendar.yahoo.com/?v=60&view=d&type=20&' . implode('&', $urlString);
	}

	/**
	 * Get event data
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return mixed
	 */
	public static function getEventDataArray($row)
	{
		static $cache = [];

		if (!isset($cache[$row->id]))
		{
			$app          = Factory::getApplication();
			$dateFormat   = "Ymd\THis\Z";
			$eventDate    = Factory::getDate($row->event_date, new DateTimeZone($app->get('offset')));
			$eventEndDate = Factory::getDate($row->event_end_date, new DateTimeZone($app->get('offset')));

			$data['title']    = urlencode($row->title);
			$data['dates']    = $eventDate->format($dateFormat) . '/' . $eventEndDate->format($dateFormat);
			$data['st']       = $eventDate->format($dateFormat);
			$data['et']       = $eventEndDate->format($dateFormat);
			$data['duration'] = abs(strtotime($row->event_end_date) - strtotime($row->event_date));

			$locationInformation = [];

			if (property_exists($row, 'location_name'))
			{
				if ($row->location_name)
				{
					$locationInformation[] = $row->location_name;

					if ($row->location_address)
					{
						$locationInformation[] = $row->location_address;
					}
				}
			}
			else
			{
				// Get location data
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('a.*')
					->from('#__eb_locations AS a')
					->innerJoin('#__eb_events AS b ON a.id=b.location_id')
					->where('b.id=' . $row->id);

				$db->setQuery($query);
				$rowLocation = $db->loadObject();

				if ($rowLocation)
				{
					$locationInformation[] = $rowLocation->name;

					if ($rowLocation->address)
					{
						$locationInformation[] = $rowLocation->address;
					}
				}
			}

			if (count($locationInformation) > 0)
			{
				$data['location'] = implode(', ', $locationInformation);
			}
			else
			{
				$data['location'] = '';
			}

			$data['sitename']   = urlencode($app->get('sitename'));
			$data['siteurl']    = urlencode(Uri::root());
			$data['rawdetails'] = urlencode($row->description);
			$data['details']    = strip_tags($row->description);

			if (strlen($data['details']) > 100)
			{
				$data['details'] = StringHelper::substr($data['details'], 0, 100) . ' ...';
			}

			$data['details'] = urlencode($data['details']);

			$cache[$row->id] = $data;
		}

		return $cache[$row->id];
	}

	/**
	 * Filter and only return the available options for a quantity field
	 *
	 * @param   array  $values
	 * @param   array  $quantityValues
	 * @param   int    $eventId
	 * @param   int    $fieldId
	 * @param   bool   $multiple
	 * @param   array  $multilingualValues
	 *
	 * @return array
	 */
	public static function getAvailableQuantityOptions(&$values, &$quantityValues, $eventId, $fieldId, $multiple = false, $multilingualValues = [])
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// First, we need to get list of registration records of this event
		$query->select('id')
			->from('#__eb_registrants')
			->where('event_id = ' . $eventId)
			->where('(published = 1 OR (published = 0 AND payment_method LIKE "os_offline%"))');
		$db->setQuery($query);
		$registrantIds = $db->loadColumn();

		if (count($registrantIds))
		{
			$registrantIds = implode(',', $registrantIds);

			if ($multiple)
			{
				$fieldValuesQuantity = [];
				$query->clear()
					->select('field_value')
					->from('#__eb_field_values')
					->where('field_id = ' . $fieldId)
					->where('registrant_id IN (' . $registrantIds . ')');
				$db->setQuery($query);
				$rowFieldValues = $db->loadObjectList();

				if (count($rowFieldValues))
				{
					foreach ($rowFieldValues as $rowFieldValue)
					{
						$fieldValue = $rowFieldValue->field_value;

						if ($fieldValue)
						{
							if (is_string($fieldValue) && is_array(json_decode($fieldValue)))
							{
								$selectedOptions = json_decode($fieldValue);
							}
							else
							{
								$selectedOptions = [$fieldValue];
							}

							foreach ($selectedOptions as $selectedOption)
							{
								if (isset($fieldValuesQuantity[$selectedOption]))
								{
									$fieldValuesQuantity[$selectedOption]++;
								}
								else
								{
									$fieldValuesQuantity[$selectedOption] = 1;
								}
							}
						}
					}
				}
			}

			for ($i = 0, $n = count($values); $i < $n; $i++)
			{
				$value = trim($values[$i]);

				if ($multiple)
				{
					$total = $fieldValuesQuantity[$value] ?? 0;
				}
				else
				{
					$query->clear()
						->select('COUNT(*)')
						->from('#__eb_field_values')
						->where('field_id = ' . $fieldId)
						->where('registrant_id IN (' . $registrantIds . ')');

					if (!empty($multilingualValues))
					{
						$allValues = array_map([$db, 'quote'], $multilingualValues[$i]);
						$query->where('field_value IN (' . implode(',', $allValues) . ')');
					}
					else
					{
						$query->where('field_value=' . $db->quote($value));
					}

					$db->setQuery($query);
					$total = $db->loadResult();
				}

				if (!empty($quantityValues[$value]))
				{
					$quantityValues[$value] -= $total;

					if ($quantityValues[$value] <= 0)
					{
						unset($values[$i]);
					}
				}
			}
		}

		return $values;
	}

	/**
	 * Helper method to prepare meta data for the document
	 *
	 * @param   Registry  $params
	 *
	 * @param   null      $item
	 */
	public static function prepareDocument($params, $item = null)
	{
		$app              = Factory::getApplication();
		$document         = $app->getDocument();
		$siteNamePosition = $app->get('sitename_pagetitles');
		$pageTitle        = $params->get('page_title');

		if ($pageTitle)
		{
			if ($siteNamePosition == 0)
			{
				$document->setTitle($pageTitle);
			}
			elseif ($siteNamePosition == 1)
			{
				$document->setTitle($app->get('sitename') . ' - ' . $pageTitle);
			}
			else
			{
				$document->setTitle($pageTitle . ' - ' . $app->get('sitename'));
			}
		}

		if (!empty($item->meta_keywords))
		{
			$document->setMetaData('keywords', $item->meta_keywords);
		}
		elseif ($params->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $params->get('menu-meta_keywords'));
		}

		if (!empty($item->meta_description))
		{
			$document->setMetaData('description', $item->meta_description);
		}
		elseif ($params->get('menu-meta_description'))
		{
			$document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('robots'))
		{
			$document->setMetadata('robots', $params->get('robots'));
		}
	}

	/**
	 * Method to escape field data before displaying to avoid xss attack
	 *
	 * @param   array  $rows
	 * @param   array  $fields
	 */
	public static function antiXSS($rows, $fields)
	{
		$config = EventbookingHelper::getConfig();

		// Do not escape data if the system is configured to allow using HTML code on event title
		if ($config->allow_using_html_on_title)
		{
			return;
		}

		if (is_object($rows))
		{
			$rows = [$rows];
		}

		$fields = (array) $fields;

		foreach ($rows as $row)
		{
			foreach ($fields as $field)
			{
				$row->{$field} = htmlspecialchars($row->{$field} ?? '', ENT_COMPAT);
			}
		}
	}

	/**
	 * Function to add dropdown menu
	 *
	 * @param   string  $vName
	 */
	public static function renderSubmenu($vName = 'dashboard')
	{
		HTMLHelper::_('bootstrap.dropdown');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_menus')
			->where('published = 1')
			->where('menu_parent_id = 0')
			->order('ordering');
		$db->setQuery($query);
		$menus = $db->loadObjectList();
		$html  = '';

		if (EventbookingHelper::isJoomla4())
		{
			$html .= '<ul id="eb-dropdown-menu" class="nav nav-tabs nav-hover eb-joomla4">';
		}
		else
		{
			$html .= '<ul id="eb-dropdown-menu" class="nav nav-tabs nav-hover">';
		}

		$currentLink = 'index.php' . Uri::getInstance()->toString(['query']);
		$isJoomla4   = EventbookingHelper::isJoomla4();

		for ($i = 0; $n = count($menus), $i < $n; $i++)
		{
			$menu = $menus[$i];
			$query->clear();
			$query->select('*')
				->from('#__eb_menus')
				->where('published = 1')
				->where('menu_parent_id = ' . intval($menu->id))
				->order('ordering');
			$db->setQuery($query);
			$subMenus = $db->loadObjectList();

			if (!count($subMenus))
			{
				$class      = '';
				$extraClass = '';

				if ($menu->menu_link == $currentLink)
				{
					$class      = ' class="active"';
					$extraClass = 'active';
				}

				$html .= '<li' . $class . '><a class="nav-link dropdown-item ' . $extraClass . '" href="' . $menu->menu_link . '"><span class="icon-' . $menu->menu_class . '"></span> ' . Text::_(
						$menu->menu_name
					) .
					'</a></li>';
			}
			else
			{
				$class = ' class="dropdown"';

				for ($j = 0; $m = count($subMenus), $j < $m; $j++)
				{
					$subMenu = $subMenus[$j];

					if ($subMenu->menu_link == $currentLink)
					{
						$class = ' class="dropdown active"';
						break;
					}
				}

				$html .= '<li' . $class . '>';

				if ($isJoomla4)
				{
					$html .= '<a id="drop_' . $menu->id . '" href="#" data-bs-toggle="dropdown" role="button" class="dropdown-toggle nav-link dropdown-toggle"><span class="icon-' . $menu->menu_class . '"></span> ' .
						Text::_($menu->menu_name) . ' <b class="caret"></b></a>';
				}
				else
				{
					$html .= '<a id="drop_' . $menu->id . '" href="#" data-toggle="dropdown" role="button" class="dropdown-toggle nav-link dropdown-toggle"><span class="icon-' . $menu->menu_class . '"></span> ' .
						Text::_($menu->menu_name) . ' <b class="caret"></b></a>';
				}

				$html .= '<ul aria-labelledby="drop_' . $menu->id . '" role="menu" class="dropdown-menu" id="menu_' . $menu->id . '">';

				for ($j = 0; $m = count($subMenus), $j < $m; $j++)
				{
					$subMenu    = $subMenus[$j];
					$class      = '';
					$extraClass = '';

					if ($subMenu->menu_link == $currentLink)
					{
						$class      = ' class="active"';
						$extraClass = 'active';
					}

					$html .= '<li' . $class . '><a class="nav-link dropdown-item ' . $extraClass . '" href="' . $subMenu->menu_link .
						'" tabindex="-1"><span class="icon-' . $subMenu->menu_class . '"></span> ' . Text::_($subMenu->menu_name) . '</a></li>';
				}
				$html .= '</ul>';
				$html .= '</li>';
			}
		}

		$html .= '</ul>';
		echo $html;
	}

	/**
	 * Get media input field type
	 *
	 * @param   string  $value
	 * @param   string  $fieldName
	 *
	 * @return string
	 */
	public static function getMediaInput($value, $fieldName = 'image', $groupName = 'images', $label = false, $description = false)
	{
		PluginHelper::importPlugin('content');

		if ($fieldName === 'image' && $groupName === 'images' && EventbookingHelper::useStipEasyImage())
		{
			$form = Form::getInstance(
				'com_eventbooking.' . $fieldName,
				JPATH_ADMINISTRATOR . '/components/com_eventbooking/forms/mediaInput_stipeasyimage.xml'
			);

			if ($label)
			{
				$form->getField($fieldName, $groupName)->__set('label', $label);
			}

			if ($description)
			{
				$form->getField($fieldName, $groupName)->__set('description', $description);
			}

			$data = [
				'images' => [
					$fieldName => $value,
				],
			];

			$form->bind($data);

			Factory::getApplication()->triggerEvent('onContentPrepareForm', [$form, $data]);
		}
		else
		{
			if (File::exists(JPATH_ADMINISTRATOR . '/components/com_eventbooking/forms/override_mediaInput.xml'))
			{
				$xml = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_eventbooking/forms/override_mediaInput.xml');
			}
			else
			{
				$xml = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_eventbooking/forms/mediaInput.xml');
			}

			$xml  = str_replace('name="image"', 'name="' . $fieldName . '"', $xml);
			$form = Form::getInstance('com_eventbooking.' . $fieldName, $xml);

			$data[$fieldName] = $value;

			Factory::getApplication()->triggerEvent('onContentPrepareForm', [$form, $data]);

			$form->bind($data);

			return $form->getField($fieldName)->input;
		}
	}

	/**
	 * Get events list dropdown
	 *
	 * @param   array   $rows
	 * @param   string  $name
	 * @param   string  $attributes
	 * @param   mixed   $selected
	 * @param   bool    $prompt
	 *
	 * @return string
	 */
	public static function getEventsDropdown($rows, $name, $attributes = '', $selected = 0, $prompt = true)
	{
		$config  = EventbookingHelper::getConfig();
		$options = [];

		if ($prompt)
		{
			$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_SELECT_EVENT'), 'id', 'title');
		}

		if ($config->show_event_date)
		{
			foreach ($rows as $row)
			{
				if (strpos($row->event_date, '00:00:00') !== false)
				{
					$eventDate = HTMLHelper::_('date', $row->event_date, $config->date_format, null);
				}
				else
				{
					$eventDate = HTMLHelper::_('date', $row->event_date, $config->date_format . ' H:i', null);
				}

				$options[] = HTMLHelper::_('select.option', $row->id, $row->title . ' (' . $eventDate . ')', 'id', 'title');
			}
		}
		else
		{
			$options = array_merge($options, $rows);
		}

		return HTMLHelper::_('select.genericlist', $options, $name, $attributes, 'id', 'title', $selected);
	}

	/**
	 * Basic implement for conditional text
	 *
	 * @param   string  $text
	 *
	 * @return string mixed
	 */
	public static function processConditionalText($text)
	{
		$regex = '#{ebShowText (.*?)(=|>|gt|<|lt|~|@)(.*?)}(.*?){/ebShowText}#s';

		return preg_replace_callback($regex, [static::class, 'processCondition'], $text);
	}

	/**
	 * Process conditional text, for now, we support = , >, and < operator
	 *
	 * @param   array  $matches
	 *
	 * @return string
	 */
	public static function processCondition($matches)
	{
		$a        = trim($matches[1]);
		$operator = $matches[2];
		$b        = trim($matches[3]);
		$text     = $matches[4];
		switch ($operator)
		{
			case '=':
				if ($a == $b)
				{
					return $text;
				}
				break;
			case '>':
			case 'gt':
				if ($a > $b)
				{
					return $text;
				}
				break;
			case '<':
			case 'lt':
				if ($a < $b)
				{
					return $text;
				}
				break;
			case '~':
				if ($a != $b)
				{
					return $text;
				}
				break;
			case '@': // Include
				if (strpos($a, $b) !== false)
				{
					return $text;
				}
		}

		return;
	}

	/**
	 * Get list of possible layouts, base on the used UI framework
	 *
	 * @param   string  $layout
	 *
	 * @return array
	 */
	public static function getPossibleLayouts($layout)
	{
		$layouts = [$layout];

		$config = EventbookingHelper::getConfig();

		if (empty($config->twitter_bootstrap_version))
		{
			$twitterBootstrapVersion = 2;
		}
		else
		{
			$twitterBootstrapVersion = $config->twitter_bootstrap_version;
		}

		switch ($twitterBootstrapVersion)
		{
			case 2:
				break;
			case 3:
				break;
			case 4:
				array_unshift($layouts, $layout . '.bootstrap' . $twitterBootstrapVersion);
				break;
			default:
				array_unshift($layouts, $layout . '.' . $twitterBootstrapVersion);
				break;
		}

		return $layouts;
	}

	/**
	 * Get BootstrapHelper class for admin UI
	 *
	 * @return EventbookingHelperBootstrap
	 */
	public static function getAdminBootstrapHelper()
	{
		return EventbookingHelperBootstrap::getInstance();
	}

	/**
	 * Get clean image path
	 *
	 * @param   string  $image
	 */
	public static function getCleanImagePath($image)
	{
		// This command is needed to make sure image contains space still being displayed properly on Joomla 4
		if (EventbookingHelper::isJoomla4())
		{
			$image = str_replace('%20', ' ', $image);
		}

		$pos = strrpos($image, '#');

		if ($pos !== false)
		{
			$image = substr($image, 0, $pos);
		}

		return $image;
	}

	/**
	 * Add choices JS to dropdown
	 *
	 * @param   string  $html
	 * @param   string  $hint
	 */
	public static function getChoicesJsSelect($html, $hint = '')
	{
		static $isJoomla4;

		if ($isJoomla4 === null)
		{
			$isJoomla4 = EventbookingHelper::isJoomla4();
		}

		if ($isJoomla4)
		{
			Text::script('JGLOBAL_SELECT_NO_RESULTS_MATCH');
			Text::script('JGLOBAL_SELECT_PRESS_TO_SELECT');

			Factory::getApplication()->getDocument()->getWebAssetManager()
				->usePreset('choicesjs')
				->useScript('webcomponent.field-fancy-select');

			$attributes = [];

			$hint = $hint ?: Text::_('JGLOBAL_TYPE_OR_SELECT_SOME_OPTIONS');

			$attributes[] = 'placeholder="' . $hint . '"';
			$attributes[] = 'search-placeholder="' . $hint . '"';

			return '<joomla-field-fancy-select ' . implode(' ', $attributes) . '>' . $html . '</joomla-field-fancy-select>';
		}

		return $html;
	}

	/**
	 * Generate user selection box
	 *
	 * @param   int     $userId
	 * @param   string  $fieldName
	 * @param   int     $registrantId
	 *
	 * @return string
	 */
	public static function getUserInput($userId, $fieldName = 'user_id', $registrantId = 0)
	{
		$app = Factory::getApplication();

		if ($app->isClient('site') && !EventbookingHelper::isJoomla4())
		{
			// Initialize variables.
			$html = [];
			$link = Uri::root(true) . '/index.php?option=com_eventbooking&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=user_id';
			// Initialize some field attributes.
			$attr = ' class="inputbox"';
			// Load the modal behavior script.

			if (EventbookingHelper::isJoomla4())
			{
				EventbookingHelperJquery::colorbox('a.modal_user_id');
			}
			else
			{
				HTMLHelper::_('behavior.modal', 'a.modal_user_id');
			}

			// Build the script.
			$script   = [];
			$script[] = '	function jSelectUser_user_id(id, title) {';
			$script[] = '			document.getElementById("jform_user_id").value = title; ';
			$script[] = '			document.getElementById("user_id").value = id; ';

			if (!$registrantId)
			{
				$script[] = 'populateRegistrantData()';
			}

			$script[] = '		SqueezeBox.close();';
			$script[] = '	}';

			// Add the script to the document head.
			Factory::getApplication()->getDocument()->addScriptDeclaration(implode("\n", $script));
			// Load the current username if available.
			$table = Table::getInstance('user');

			if ($userId)
			{
				$table->load($userId);
			}
			else
			{
				$table->name = '';
			}

			// Create a dummy text field with the username.
			$html[] = '<div class="input-append">';
			$html[] = '	<input type="text" readonly="" name="jform[user_id]" id="jform_user_id" value="' . $table->name . '"' . $attr . ' />';
			$html[] = '	<input type="hidden" name="user_id" id="user_id" value="' . $userId . '"' . $attr . ' />';
			// Create the user select button.
			$html[] = '<a class="btn btn-primary button-select modal_user_id" title="' . Text::_(
					'JLIB_FORM_CHANGE_USER'
				) . '" href="' . $link . '"' .
				' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
			$html[] = ' <span class="icon-user"></span></a>';
			$html[] = '</div>';

			return implode("\n", $html);
		}

		// Prevent notices on PHP 8.1 due to a bug on user form field
		if (!$userId)
		{
			$userId = '';
		}

		HTMLHelper::_('jquery.framework');
		$field = FormHelper::loadFieldType('User');

		$element = new SimpleXMLElement('<field />');
		$element->addAttribute('name', $fieldName);
		$element->addAttribute('class', 'readonly input-medium');

		if (!$registrantId)
		{
			$element->addAttribute('onchange', 'populateRegistrantData();');
		}

		$field->setup($element, $userId);

		$input = $field->input;

		if ($app->isClient('site'))
		{
			// This line here is for backward compatible purpose only, should be removed in the future
			Factory::getApplication()->getDocument()->addScriptDeclaration('function jSelectUser_user_id(id, title) {}');

			if (strpos($input, '"index.php?option=com_users') !== false)
			{
				$input = str_replace('index.php?option=com_users', Uri::root(true) . '/index.php?option=com_eventbooking', $input);
			}
			else
			{
				$input = str_replace('com_users', 'com_eventbooking', $input);
			}
		}

		return $input;
	}

	/**
	 * Method to get a timezone select
	 *
	 * @param   string  $name
	 * @param   string  $attributes
	 * @param   string  $selected
	 *
	 * @return string
	 */
	public static function getTimezoneInput($name, $attributes, $selected = '')
	{
		$groups = [];

		if (!$selected)
		{
			$selected = Factory::getApplication()->get('offset');
		}

		$groups[0][] = HTMLHelper::_('select.option', 'UTC', Text::_('JLIB_FORM_VALUE_TIMEZONE_UTC'));

		$zoneGroups = [
			'Africa',
			'America',
			'Antarctica',
			'Arctic',
			'Asia',
			'Atlantic',
			'Australia',
			'Europe',
			'Indian',
			'Pacific',
		];

		// Get the list of time zones from the server.
		$zones = DateTimeZone::listIdentifiers();

		// Build the group lists.
		foreach ($zones as $zone)
		{
			// Time zones not in a group we will ignore.
			if (strpos($zone, '/') === false)
			{
				continue;
			}

			// Get the group/locale from the timezone.
			[$group, $locale] = explode('/', $zone, 2);

			// Only use known groups.
			if (in_array($group, $zoneGroups))
			{
				// Initialize the group if necessary.
				if (!isset($groups[$group]))
				{
					$groups[$group] = [];
				}

				// Only add options where a locale exists.
				if (!empty($locale))
				{
					$groups[$group][$zone] = HTMLHelper::_('select.option', $zone, str_replace('_', ' ', $locale));
				}
			}
		}

		// Sort the group lists.
		ksort($groups);

		foreach ($groups as &$location)
		{
			sort($location);
		}

		return HTMLHelper::_(
			'select.groupedlist',
			$groups,
			$name,
			[
				'list.attr'          => $attributes,
				'list.select'        => $selected,
				'group.items'        => null,
				'option.key.toHtml'  => false,
				'option.text.toHtml' => false,
			]
		);
	}

	/**
	 * Render the given datetime in the required timezone
	 *
	 * @param   string  $date
	 * @param   string  $format
	 * @param   string  $sTimezone
	 * @param   string  $dTimezone
	 */
	public static function renderDateTime($date, $format = '', $sTimezone = '', $dTimezone = '')
	{
		$app    = Factory::getApplication();
		$config = EventbookingHelper::getConfig();

		if (!$format)
		{
			$format = $config->event_date_format;
		}

		if (!$sTimezone)
		{
			$sTimezone = $app->get('offset');
		}

		if (!$dTimezone)
		{
			$dTimezone = $app->get('offset');
		}

		$date = Factory::getDate($date, $sTimezone);
		$date->setTimezone(new DateTimeZone($dTimezone));

		return $date->format($format, true);
	}

	/**
	 * Generate article selection box
	 *
	 * @param   int     $fieldValue
	 * @param   string  $fieldName
	 *
	 * @return string
	 */
	public static function getArticleInput($fieldValue, $fieldName = 'article_id')
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_content/models/fields');

		if (EventbookingHelper::isJoomla4())
		{
			FormHelper::addFieldPrefix('Joomla\Component\Content\Administrator\Field');
		}

		$field = FormHelper::loadFieldType('Modal_Article');

		if (version_compare(JVERSION, '4.2.0-dev', 'ge'))
		{
			$field->setDatabase(Factory::getDbo());
		}

		$element = new SimpleXMLElement('<field />');
		$element->addAttribute('name', $fieldName);
		$element->addAttribute('select', 'true');
		$element->addAttribute('clear', 'true');

		$field->setup($element, $fieldValue);

		return $field->input;
	}

	/**
	 * Method to check if layout was overridden in the current template
	 *
	 * @param $layout
	 *
	 * @return bool
	 */
	public static function isLayoutOverridden($layout)
	{
		if (file_exists(JPATH_THEMES . '/' . Factory::getApplication()->getTemplate() . '/html/com_eventbooking/' . $layout))
		{
			return true;
		}

		return false;
	}
}
