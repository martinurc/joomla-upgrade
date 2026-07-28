<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

class SppagebuilderAddonDynamic_content_text extends SppagebuilderAddons
{

    function autocorrect_html(string $html): string {
        if (empty($html)) {
            return '';
        }

        libxml_use_internal_errors(true);
    
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
        libxml_clear_errors();
    
        return $dom->saveHTML();
    }

    function renderRatingStars($rating, $attribute, $settings) {
        if (empty($rating) || !is_numeric($rating)) {
            return '';
        }

        $rating = floatval($rating);
        $maxRating = !empty($settings->rating_max_length) ? intval($settings->rating_max_length) : null;
        $maxRating = $maxRating ?? ceil($rating);
        $isInteger = isset($attribute->number_format) && $attribute->number_format === 'integer';
        
        // Check if custom icon is selected
        $customIcon = isset($settings->rating_icon) && !empty($settings->rating_icon) ? $settings->rating_icon : null;
        
        $output = '<div class="sppb-rating-stars">';
        
        for ($i = 1; $i <= $maxRating; $i++) {
            $isFilled = $rating >= $i;
            $isHalfFilled = !$isInteger && $rating >= ($i - 0.5) && $rating < $i;
            
            $starClass = 'sppb-rating-star';
            if ($isFilled) {
                $starClass .= ' sppb-rating-star-filled';
            } elseif ($isHalfFilled) {
                $starClass .= ' sppb-rating-star-half';
            }
            
            if ($customIcon) {
                if ($isHalfFilled) {
                    $starClass = 'sppb-rating-star sppb-rating-custom-half';
                    $output .= '<span class="' . $starClass . '"><i class="' . $customIcon . '"></i><i class="' . $customIcon . ' half-overlay"></i></span>';
                } else {
                    $output .= '<span class="' . $starClass . '"><i class="' . $customIcon . '"></i></span>';
                }
            } else {
                $output .= '<span class="' . $starClass . '">★</span>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }

    private function renderIcon($iconClass) {
        if (empty($iconClass)) {
            return '';
        }

        $output = '<div class="sppb-icon">';
        $output .= '<span class="sppb-icon-inner" style="display:flex;">';
        $output .= '<i class="sppb-dynamic-content-text__field_icon ' . $iconClass . '"></i>';
        $output .= '</span>';
        $output .= '</div>';

        return $output;
    }

    private function wordCount($unicode_string) {
        $unicode_string = preg_replace('/[\p{P}\p{N}]/u', '', $unicode_string);

        $unicode_string = preg_replace('/\s+/u', ' ', $unicode_string);

        $words_array = preg_split('/\s+/u', trim($unicode_string), -1, PREG_SPLIT_NO_EMPTY);

        return count($words_array);
    }

    public function render()
    {        
        $settings = $this->addon->settings;
        $selector = isset($settings->selector) ? $settings->selector : 'p';
        $isDownloadable = isset($settings->is_downloadable) ? $settings->is_downloadable : 0;
        $fileValueOverride = !empty($settings->file_value_override) ? $settings->file_value_override : null;
        $defaultContent = $settings->default_text ?? '';
        $class = $settings->class ?? '';
        $collectionId = isset($settings->dynamic_item['collection_id']) ? $settings->dynamic_item['collection_id'] : null;

        $input = Factory::getApplication()->input;
        $collectionType = $input->get('collection_type') ?? 'normal-source';

        // If the dynamic content value is not come from the parent collection addon,
        // that means this addon placed into a detail page, so we need to get the data from the detail page.
        if (empty($settings->dynamic_item) && $collectionType === 'articles') {
            $settings->dynamic_item = CollectionHelper::getDetailPageDataFromArticles();
        } else if (empty($settings->dynamic_item) && $collectionType === 'tags') {
            $settings->dynamic_item = CollectionHelper::getDetailPageDataFromTags();
        } else if (empty($settings->dynamic_item)) {
            $settings->dynamic_item = CollectionHelper::getDetailPageData();
        }

        if (is_object($settings->dynamic_item)) {
            $settings->dynamic_item = json_decode(json_encode($settings->dynamic_item), true);
        }

        if (empty($settings->dynamic_item) || empty($settings->attribute)) {
            $content = $defaultContent ? $defaultContent : Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_TEXT_NO_DATA');
            return '<' . $selector . ' class="sppb-dynamic-content-text"' . '>' . $content . '</' . $selector . '>';
        }

        $linkAttributes = [
            'href' => '',
            'target' => '',
            'rel' => '',
            'has_link' => false,
        ];

        $link = $settings->link ?? null;
        $hasLink = false;
        $contactSchemeLink = false;

        $hasDynamicLink = isset($settings->dynamic_link_switch) && $settings->dynamic_link_switch ? true : false;
        $dynamicLink = isset($settings->dynamic_link) ? $settings->dynamic_link : null;
        $dynamicLinkUrl = CollectionHelper::getDynamicContentData($dynamicLink, $settings->dynamic_item);

        if (!empty($link)) {
            $linkOptions = [
                'url' => CollectionHelper::createDynamicContentLink(
                    $link, CollectionHelper::prepareItemForLink($settings->dynamic_item, $settings->attribute)
                ),
                'target' => $link->new_tab ? '_blank' : null,
                'nofollow' => $link->nofollow ?? null,
                'noreferrer' => $link->noreferrer ?? null,
                'noopener' => $link->noopener ?? null,
            ];
            $linkAttributes = CollectionHelper::generateLinkAttributes($linkOptions);
        }

        $content = CollectionHelper::getDynamicContentData($settings->attribute, $settings->dynamic_item) ?? $defaultContent ?? '';

        $noLink = false;

        if (empty(CollectionHelper::getDynamicContentData($settings->attribute, $settings->dynamic_item))) {
            $noLink = true;
        }

        if (isset($settings->dynamic_item['collection_id']) && ($settings->dynamic_item['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID || $settings->dynamic_item['collection_id'] === CollectionIds::TAGS_COLLECTION_ID)) {
            $content = $settings->dynamic_item[$settings->attribute->path];
        }

        $attributeType = $settings->attribute->type ?? 'text';

        if ($attributeType === FieldTypes::DATETIME) {
            $content = CollectionHelper::formatDate($content, $settings->attribute);
        } elseif ($attributeType === FieldTypes::RATING) {
            $content = $this->renderRatingStars($content, $settings->attribute, $settings);
        } elseif ($attributeType === FieldTypes::LINK) {
            $linkOptions = [
                'url' => $content ?? null,
                'target' => isset($settings->attribute->link) ? ($settings->attribute->link->target ?? null) : null,
                'nofollow' => isset($settings->attribute->link) ? ($settings->attribute->link->nofollow ?? null) : null,
                'noreferrer' => isset($settings->attribute->link) ? ($settings->attribute->link->noreferrer ?? null) : null,
                'noopener' => isset($settings->attribute->link) ? ($settings->attribute->link->noopener ?? null) : null,
            ];
            $linkAttributes = CollectionHelper::generateLinkAttributes($linkOptions);
            $content = (isset($settings->attribute->link) && $settings->attribute->link->text) ? $settings->attribute->link->text : $linkAttributes['href'];
        } elseif ($attributeType === FieldTypes::ICON) {
            $content = $this->renderIcon($content);
        }

        $plainContact = trim(strip_tags((string) $content));

        if (
            $plainContact !== '' && !$noLink
            && !$hasDynamicLink
            && empty($linkAttributes['has_link'])
            && ($attributeType === FieldTypes::EMAIL || $attributeType === FieldTypes::PHONE)
        ) {
            if ($attributeType === FieldTypes::EMAIL && !empty($settings->enable_mailto)) {
                $linkAttributes = CollectionHelper::generateLinkAttributes([
                    'url' => 'mailto:' . $plainContact,
                ]);
                $contactSchemeLink = true;
            } elseif ($attributeType === FieldTypes::PHONE && !empty($settings->enable_tel)) {
                $telHref = preg_replace('/\s+/u', '', $plainContact);
                $linkAttributes = CollectionHelper::generateLinkAttributes([
                    'url' => 'tel:' . $telHref,
                ]);
                $contactSchemeLink = true;
            }
        }

        if ($settings->attribute->name !== FieldTypes::LAYOUT) {
            if ($attributeType !== FieldTypes::RATING && $attributeType !== FieldTypes::ICON && empty(strip_tags($content))) {
                return '';
            }
        }

        $output = '';
        $hasLink = $linkAttributes['has_link'] ?? false;

        $downloadAction = $isDownloadable ? ' download="' . $content . '"' : '';

        $buttonEnabled = isset($settings->enable_button) && $settings->enable_button ? true : false;
        $buttonClass = '';
        $buttonClass .= (isset($settings->button_type) && $settings->button_type) ? ' sppb-btn-' . $settings->button_type : '';
        $buttonClass .= (isset($settings->button_size) && $settings->button_size) ? ' sppb-btn-' . $settings->button_size : '';
        $buttonClass .= (isset($settings->button_block) && $settings->button_block) ? ' ' . $settings->button_block : '';
        $buttonClass .= (isset($settings->button_shape) && $settings->button_shape) ? ' sppb-btn-' . $settings->button_shape : ' sppb-btn-rounded';
        $buttonClass .= (isset($settings->button_appearance) && $settings->button_appearance) ? ' sppb-btn-' . $settings->button_appearance : '';
        $attribs = ' id="btn-' . $this->addon->id . '"';

        if ($hasLink && !$hasDynamicLink) {
            $linkUrl = $linkAttributes['href'] ?? '/';
            $attributes = $linkAttributes['target'] ? ' target="' . $linkAttributes['target'] . '"' : '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
            $app = Factory::getApplication();
            $option = $app->input->get('option', '', 'string');
            $view = $app->input->get('view', '', 'string');
            
            if (!$contactSchemeLink && $option === 'com_content' && ($view === 'category' || $view === 'archive' || $view === 'featured' || $view === 'article') && !empty($settings->dynamic_item['link'])) {
                $linkUrl = $settings->dynamic_item['link'];
            }
            
            
            $output .= '<a ' . $attribs . ' href="' . $linkUrl . '" class="sppb-dynamic-content-text__link ' . ($buttonEnabled ? 'sppb-btn ' . trim($buttonClass) : '') . '" data-preload-collection ' . $attributes . ' >';
        }

        if($hasDynamicLink && $dynamicLink){
            $dynamicLinkOptions = [
                'url' => $dynamicLinkUrl ?? null,
                'target' => isset($dynamicLink->link) ? ($dynamicLink->link->target ?? null) : null,
                'nofollow' => isset($dynamicLink->link) ? ($dynamicLink->link->nofollow ?? null) : null,
                'noreferrer' => isset($dynamicLink->link) ? ($dynamicLink->link->noreferrer ?? null) : null,
                'noopener' => isset($dynamicLink->link) ? ($dynamicLink->link->noopener ?? null) : null,
            ];

            $dynamicLinkAttributes = CollectionHelper::generateLinkAttributes($dynamicLinkOptions);
            $dynamicAttributes = $dynamicLinkAttributes['target'] ? ' target="' . $dynamicLinkAttributes['target'] . '"' : '';
            $dynamicAttributes .= $dynamicLinkAttributes['rel'] ? ' rel="' . $dynamicLinkAttributes['rel'] . '"' : '';
            $dynamicUrl = $dynamicLinkAttributes['href'] ?? '/';
               
            $output .= '<a href="' . $dynamicUrl . '" class="sppb-dynamic-content-text__link" data-preload-collection ' . $dynamicAttributes . ' >';
        }

        if ($isDownloadable && $attributeType === FieldTypes::FILE) {
            $linkUrl = '/' . $content;
            $attributes = '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-file__link" data-preload-collection ' . $attributes . $downloadAction . '" >';
        }

        $icon = $settings->icon ?? null;

        if (!empty($fileValueOverride) && $attributeType === FieldTypes::FILE) {
            $content = $fileValueOverride;
        }

        $classNames = 'sppb-dynamic-content-text';

        if ($attributeType === FieldTypes::RICH_TEXT) {
            $selector = 'div';
            $content = '<div class="sppb-dynamic-content__is-rich-text">' . $this->autocorrect_html($content) . '</div>';
        }

        if ($attributeType === FieldTypes::RATING) {
            $selector = 'div';
        }

        if (!empty($icon) && ($settings->attribute->name !== FieldTypes::LAYOUT && $attributeType !== FieldTypes::ICON)) {
            if (!empty(strip_tags($content))) {
                $iconPosition = $settings->icon_position ?? 'left';
                $iconContent = '<i class="sppb-dynamic-content-text__icon ' . $icon . '"></i>';
        
            if ($iconPosition === 'left') {
                $content = $iconContent . $content;
            } else {
                $content = $content . $iconContent;
            }
        }
    }

        $shouldTruncate = false;

        if ($settings->attribute->name !== FieldTypes::LAYOUT && $attributeType !== FieldTypes::RATING && $attributeType !== FieldTypes::FILE) {
            // Handle content truncation
            $content_truncation = (isset($settings->content_truncation) && $settings->content_truncation) ? $settings->content_truncation : 0;
            $originalContent = $content;

            $plain_text = strip_tags($content);
            $shouldTruncate = $content_truncation && $attributeType !== FieldTypes::RATING && $attributeType !== FieldTypes::FILE && !empty($settings->content_truncation_max_word) && (int) $this->wordCount($plain_text) > (int) $settings->content_truncation_max_word;

            if ($shouldTruncate) {
                $arrayString = explode(' ', $plain_text);
                $truncated_text = implode(' ', array_slice($arrayString, 0, (int) $settings->content_truncation_max_word));
                $content = $truncated_text;
                $content .= '<template class="sppb-addon-content-full-text">' . $originalContent . '</template>';
            }
        }

        if (!empty($class)) {
            $classNames .= ' ' . $class;
        }

        if (isset($settings->dynamic_item['collection_id']) && $settings->dynamic_item['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID && $settings->attribute->name === FieldTypes::LAYOUT) {
            $layout = $content;
            $layoutContent = !empty($layout->content) ? $layout->content : null;
            $layoutCss = !empty($layout->css) ? $layout->css : null;
                
            if (!empty($layoutContent)) {
                require_once JPATH_ROOT . '/components/com_sppagebuilder/parser/addon-parser.php';
                
                $decodedContent = is_string($layoutContent) ? json_decode($layoutContent) : $layoutContent;
                
                if ($decodedContent) {
                    $layoutHtml = AddonParser::viewAddons($decodedContent, 0, 'article-layout-' . ($settings->dynamic_item['id'] ?? ''));
                    
                    if (!empty($layoutCss)) {
                        $doc = Factory::getDocument();
                        $doc->addStyledeclaration($layoutCss);
                    }
                    $output .= '<div class="sppb-dynamic-content-layout">' . $layoutHtml . '</div>';
                }
            }
        } else {
            $output .= '<' . $selector . ' class="' . $classNames . '"' . '>' . $content . '</' . $selector . '>';
        }

        if ($shouldTruncate) {
            $output .= '<div class="sppb-btn-container sppb-content-truncation-show"><div role="button" class="sppb-btn-show-more">' . ($settings->content_truncation_action_text ?? 'Show More') . '</div></div>';
        }

        if ($isDownloadable && $attributeType === FieldTypes::FILE) {
            $output .= '</a>';
        }

        if ($hasLink || ($hasDynamicLink && $dynamicLink)) {
            $output .= '</a>';
        }

        return $output;
    }

    public function css()
    {
        $css = '';

        $addon_id = '#sppb-addon-' . $this->addon->id;
        $settings = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);
        $settings->title_text_shadow = CSSHelper::parseBoxShadow($settings, 'title_text_shadow', true);

        if(isset($settings->enable_button) && $settings->enable_button){

            $layoutPath = JPATH_ROOT . '/components/com_sppagebuilder/layouts';
            $buttonLayout = new FileLayout('addon.css.button', $layoutPath);

            $options = new stdClass;
            $options->button_type = (isset($settings->button_type) && $settings->button_type) ? $settings->button_type : '';
            $options->button_appearance = (isset($settings->button_appearance) && $settings->button_appearance) ? $settings->button_appearance : '';
            $options->button_color = (isset($settings->button_color) && $settings->button_color) ? $settings->button_color : '';
            $options->button_border_width = (isset($settings->button_border_width) && $settings->button_border_width) ? $settings->button_border_width : '';
            $options->button_color_hover = (isset($settings->button_color_hover) && $settings->button_color_hover) ? $settings->button_color_hover : '';
            $options->button_background_color = (isset($settings->button_background_color) && $settings->button_background_color) ? $settings->button_background_color : '';
            $options->button_background_color_hover = (isset($settings->button_background_color_hover) && $settings->button_background_color_hover) ? $settings->button_background_color_hover : '';
            $options->button_fontstyle = (isset($settings->button_fontstyle) && $settings->button_fontstyle) ? $settings->button_fontstyle : '';
            $options->button_font_style = (isset($settings->button_font_style) && $settings->button_font_style) ? $settings->button_font_style : '';
            $options->button_padding = (isset($settings->button_padding) && $settings->button_padding) ? $settings->button_padding : '';
            $options->button_padding_original = (isset($settings->button_padding_original) && $settings->button_padding_original) ? $settings->button_padding_original : '';

            $options->fontsize = isset($settings->fontsize_original) ? $settings->fontsize_original : ($settings->fontsize ?? null);
            $options->button_size = isset($settings->size) ? $settings->size : null;
            $options->font_family = isset($settings->font_family) ? $settings->font_family : null;
            $options->button_typography = isset($settings->typography) ? $settings->typography : null;

            $options->link_button_color = (isset($settings->link_button_color) && $settings->link_button_color) ? $settings->link_button_color : '';
            $options->link_border_color = (isset($settings->link_button_border_color) && $settings->link_button_border_color) ? $settings->link_button_border_color : '';
            $options->link_button_border_width = (isset($settings->link_button_border_width) && $settings->link_button_border_width) ? $settings->link_button_border_width : '';
            $options->link_button_padding_bottom = (isset($settings->link_button_padding_bottom) && gettype($settings->link_button_padding_bottom) == 'string') ? $settings->link_button_padding_bottom : '';

            $options->link_button_hover_color = (isset($settings->link_button_hover_color) && $settings->link_button_hover_color) ? $settings->link_button_hover_color : '';
            $options->link_button_border_hover_color = (isset($settings->link_button_border_hover_color) && $settings->link_button_border_hover_color) ? $settings->link_button_border_hover_color : '';

            $options->button_letterspace = (isset($settings->letterspace) && $settings->letterspace) ? $settings->letterspace : '';
            $options->button_background_gradient = (isset($settings->background_gradient) && $settings->background_gradient) ? $settings->background_gradient : new stdClass();
            $options->button_background_gradient_hover = (isset($settings->background_gradient_hover) && $settings->background_gradient_hover) ? $settings->background_gradient_hover : new stdClass();

            $css .= $buttonLayout->render(array('addon_id' => $addon_id, 'options' => $options, 'id' => 'btn-' . $this->addon->id));

        }

        $css .= $cssHelper->generateStyle('.sppb-dynamic-content-text, .sppb-dynamic-content-text a', $settings, [
            'color'             => 'color',
            'alignment'         => 'justify-content',
            'title_margin'      => 'margin',
            'title_padding'     => 'padding',
            'title_text_shadow' => 'text-shadow',
        ], false);

        $iconWrapperStyle = $cssHelper->generateStyle('.sppb-dynamic-content-text', $settings, ['icon_gap' => 'gap'], false);
        $iconStyle = $cssHelper->generateStyle('.sppb-dynamic-content-text__icon', $settings, ['icon_color' => 'color', 'icon_size' => 'font-size'], false);
        $fieldIconSize =$cssHelper->generateStyle('.sppb-dynamic-content-text__field_icon', $settings, ['field_icon_size' => 'font-size'], false);

        $css .= $cssHelper->typography('.sppb-dynamic-content-text', $settings, 'typography');
        $css .= $iconWrapperStyle . $iconStyle;
        $css .= $fieldIconSize;

        // Content truncation styles
        $contentTruncation = !empty($settings->content_truncation);
        if ($contentTruncation) {
            $contentTruncationStyle = $cssHelper->generateStyle('.sppb-content-truncation-show', $settings, ['content_truncation_action_text_color' => 'color'], false);
            $contentTruncationStyle .= $cssHelper->typography('.sppb-content-truncation-show', $settings, 'content_truncation_action_typography');
            
            $css .= $contentTruncationStyle;
        }

        if (isset($settings->attribute->type) && $settings->attribute->type === FieldTypes::RATING) {
            $css .= '
                .sppb-rating-stars {
                    display: inline-flex;
                    gap: ' . (!empty($settings->rating_gap) ? $settings->rating_gap : '2px') . ';
                    align-items: center;
                }
                .sppb-rating-star {
                    color: ' . (!empty($settings->rating_empty_color) ? $settings->rating_empty_color : '#d1d5db') . ';
                    font-size: ' . (!empty($settings->rating_size) ? $settings->rating_size : '1.2em') . ';
                    line-height: 1;
                    transition: color 0.2s ease;
                }
                .sppb-rating-star-filled {
                    color: ' . (!empty($settings->rating_color) ? $settings->rating_color : '#fbbf24') . ';
                }
                .sppb-rating-star-half {
                    color: ' . (!empty($settings->rating_empty_color) ? $settings->rating_empty_color : '#d1d5db') . ';
                    position: relative;
                }
                .sppb-rating-star-half::after {
                    content: "★";
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 50%;
                    height: 100%;
                    color: ' . (!empty($settings->rating_color) ? $settings->rating_color : '#fbbf24') . ';
                    overflow: hidden;
                }
                /* Custom icon styles */
                .sppb-rating-star i {
                    font-size: inherit;
                    color: inherit;
                }
                .sppb-rating-custom-half {
                    position: relative;
                }
                .sppb-rating-custom-half i:first-child {
                    color: ' . (!empty($settings->rating_empty_color) ? $settings->rating_empty_color : '#d1d5db') . ';
                    position: relative;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 1;
                }
                .sppb-rating-custom-half i.half-overlay {
                    color: ' . (!empty($settings->rating_color) ? $settings->rating_color : '#fbbf24') . ';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: ' . (!empty($settings->rating_size) ? ('calc(' . $settings->rating_size . ' / 2)') : '0.6em') . ';
                    height: 100%;
                    z-index: 1;
                    overflow: hidden;
                }
            ';
        }
        return $css;
    }

    /**
     * Load external scripts.
     *
     * @return 	array
     * @since 	5.0.0
     */
    public function scripts()
    {
        return [ Uri::base(true) . '/components/com_sppagebuilder/assets/js/addons/dynamic_content_text.js' ];
    }

    public static function getTemplate() {
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output  = '<style type="text/css">';

		$output .= $lodash->generateTransformCss('', 'data.transform');
		$output .= $lodash->typography('.sppb-dynamic-content-text', 'data.typography');

		$output .= $lodash->color('color', '.sppb-content-truncation-show', 'data.content_truncation_action_text_color');
		$output .= $lodash->typography('.sppb-content-truncation-show', 'data.content_truncation_action_typography');
        $output  .=$lodash->unit('font-size', '.sppb-dynamic-content-text__field_icon', 'data.field_icon_size', '');


        $output .= '<# if (data.button_type == "custom") { #>';
		$output .= $lodash->color('color', '#btn-{{ data.id }}', 'data.button_color');
		$output .= $lodash->color('color', '#btn-{{ data.id }}:hover', 'data.button_color_hover');
		$output .= $lodash->color('background-color', '#btn-{{ data.id }}:hover', 'data.button_background_hover_color');
		$output .= $lodash->spacing('padding', '#btn-{{ data.id }}.sppb-btn-custom', 'data.button_padding');
		$output .= '<# if (data.appearance == "outline") { #>';
		$output .= '#btn-{{ data.id }} {background-color: transparent;}';
		$output .= $lodash->unit('border-color', '#btn-{{ data.id }}', 'data.button_border_color', '', false);
		$output .= $lodash->unit('border-color', '#btn-{{ data.id }}:hover', 'data.button_border_hover_color', '', false);
		$output .= '<# } else if (data.appearance == "gradient") { #>';
		$output .= '#btn-{{ data.id }} {border: none;}';
		$output .= $lodash->color('background-color', '#btn-{{ data.id }}', 'data.button_background_gradient');
		$output .= $lodash->color('background-color', '#btn-{{ data.id }}:hover', 'data.button_background_gradient_hover');
		$output .= '<# } else { #>';
		$output .= $lodash->color('background-color', '#btn-{{ data.id }}', 'data.button_background_color');
		$output .= $lodash->color('background-color', '#btn-{{ data.id }}:hover', 'data.button_background_color_hover');
		$output .= '<# } #>';
		$output .= '<# } #>';

		$output .= '<# if (data.button_type == "link") { #>';
		$output .= '#btn-{{ data.id }} {padding: 0; border-width: 0; text-decoration: none; border-radius: 0;}';
		$output .= $lodash->color('color', '#btn-{{ data.id }}', 'data.link_button_color');
		$output .= $lodash->unit('border-color', '#btn-{{ data.id }}', 'data.link_button_border_color', '', false);
		$output .= $lodash->unit('border-bottom-width', '#btn-{{ data.id }}', 'data.link_button_border_width', 'px');
		$output .= $lodash->unit('padding-bottom', '#btn-{{ data.id }}', 'data.link_button_padding_bottom', 'px');
		$output .= $lodash->color('color', '#btn-{{ data.id }}:hover, #btn-{{ data.id }}:focus', 'data.link_button_hover_color');
		$output .= $lodash->unit('border-color', '#btn-{{ data.id }}:hover, #btn-{{ data.id }}:focus', 'data.link_button_border_hover_color', '', false);
		$output .= '<# } #>';

		$output .= '</style>';

		return $output;
	}
}
