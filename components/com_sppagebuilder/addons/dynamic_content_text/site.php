<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;

class SppagebuilderAddonDynamic_content_text extends SppagebuilderAddons
{
    public function render()
    {        
        $settings = $this->addon->settings;
        $selector = isset($settings->selector) ? $settings->selector : 'p';
        $isDownloadable = isset($settings->is_downloadable) ? $settings->is_downloadable : 0;
        $defaultContent = $settings->default_text ?? '';
        $class = $settings->class ?? '';

        // If the dynamic content value is not come from the parent collection addon,
        // that means this addon placed into a detail page, so we need to get the data from the detail page.
        if (empty($settings->dynamic_item)) {
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

        $content = CollectionHelper::getDynamicContentData($settings->attribute, $settings->dynamic_item) ?? '';
        $attributeType = $settings->attribute->type ?? 'text';

        if ($attributeType === FieldTypes::DATETIME) {
            $content = CollectionHelper::formatDate($content, $settings->attribute);
        } elseif ($attributeType === FieldTypes::LINK) {
            $linkOptions = [
                'url' => $content ?? null,
                'target' => isset($settings->attribute->link) ? ($settings->attribute->link->target ?? null) : null,
                'nofollow' => isset($settings->attribute->link) ? ($settings->attribute->link->nofollow ?? null) : null,
                'noreferrer' => isset($settings->attribute->link) ? ($settings->attribute->link->noreferrer ?? null) : null,
                'noopener' => isset($settings->attribute->link) ? ($settings->attribute->link->noopener ?? null) : null,
            ];
            $linkAttributes = CollectionHelper::generateLinkAttributes($linkOptions);
            $content = isset($settings->attribute->link) ? ($settings->attribute->link->text ?? $content) : $content;
        }

        $output = '';
        $hasLink = $linkAttributes['has_link'] ?? false;

        $downloadAction = $isDownloadable ? ' download="' . $content . '"' : '';

        if ($hasLink) {
            $linkUrl = $linkAttributes['href'] ?? '/';
            $attributes = $linkAttributes['target'] ? ' target="' . $linkAttributes['target'] . '"' : '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-text__link" data-preload-collection ' . $attributes . ' >';
        }

        if ($isDownloadable) {
            $linkUrl = '/' . $content;
            $attributes = '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-file__link" data-preload-collection ' . $attributes . $downloadAction . '" >';
        }

        $icon = $settings->icon ?? null;

        $classNames = 'sppb-dynamic-content-text';

        if ($attributeType === FieldTypes::RICH_TEXT) {
            $selector = 'div';
            $content = '<div class="sppb-dynamic-content__is-rich-text">' . $content . '</div>';
        }

        if (!empty($icon)) {
            $iconPosition = $settings->icon_position ?? 'left';
            $iconContent = '<i class="sppb-dynamic-content-text__icon ' . $icon . '"></i>';

            if ($iconPosition === 'left') {
                $content = $iconContent . $content;
            } else {
                $content = $content . $iconContent;
            }
        }

        if (!empty($class)) {
            $classNames .= ' ' . $class;
        }
        
        $output .= '<' . $selector . ' class="' . $classNames . '"' . '>' . $content . '</' . $selector . '>';

        if ($isDownloadable) {
            $output .= '</a>';
        }

        if ($hasLink) {
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

        $css .= $cssHelper->generateStyle('.sppb-dynamic-content-text, .sppb-dynamic-content-text a', $settings, [
            'color'             => 'color',
            'alignment'         => 'justify-content',
            'title_margin'      => 'margin',
            'title_padding'     => 'padding',
            'title_text_shadow' => 'text-shadow',
        ], false);

        $iconWrapperStyle = $cssHelper->generateStyle('.sppb-dynamic-content-text', $settings, ['icon_gap' => 'gap'], false);
        $iconStyle = $cssHelper->generateStyle('.sppb-dynamic-content-text__icon', $settings, ['icon_color' => 'color', 'icon_size' => 'font-size'], false);

        $css .= $cssHelper->typography('.sppb-dynamic-content-text', $settings, 'typography');
        $css .= $iconWrapperStyle . $iconStyle;

        return $css;
    }

    public static function getTemplate() {
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output  = '<style type="text/css">';

		$output .= $lodash->generateTransformCss('', 'data.transform');
		$output .= $lodash->typography('.sppb-dynamic-content-text', 'data.typography');

		$output .= '</style>';

		return $output;
	}
}
