<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

SpAddonsConfig::addonConfig([
    'type'       => 'dynamic-content',
    'addon_name' => 'dynamic_content_text',
    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT'),
    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_DESC'),
    'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_DYNAMIC_CONTENT'),
    'icon'       => '<svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.611 4.444a2.167 2.167 0 1 0 0 4.334 2.167 2.167 0 0 0 0-4.334ZM3 6.611a3.611 3.611 0 1 1 7.222 0 3.611 3.611 0 0 1-7.222 0ZM25.389 23.222a2.167 2.167 0 1 0 0 4.334 2.167 2.167 0 0 0 0-4.334Zm-3.611 2.167a3.611 3.611 0 1 1 7.222 0 3.611 3.611 0 0 1-7.222 0Z" fill="#6F7CA3"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.778 6.611c0-.399.323-.722.722-.722h14.444c1.197 0 2.167.97 2.167 2.167v10.833a.722.722 0 1 1-1.444 0V8.056a.722.722 0 0 0-.723-.723H9.5a.722.722 0 0 1-.722-.722ZM23.222 25.389a.722.722 0 0 1-.722.722H8.056a2.167 2.167 0 0 1-2.167-2.167V13.111a.722.722 0 1 1 1.444 0v10.833c0 .4.324.723.723.723H22.5c.399 0 .722.323.722.722Z" fill="#6F7CA3"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.99 16.212a.722.722 0 0 1 1.02 0l2.379 2.378 2.378-2.378a.722.722 0 0 1 1.021 1.02l-2.684 2.686a1.011 1.011 0 0 1-1.43 0l-2.685-2.685a.722.722 0 0 1 0-1.021ZM10.01 15.789a.722.722 0 0 1-1.02 0L6.61 13.41 4.233 15.79a.722.722 0 0 1-1.021-1.022l2.684-2.684a1.011 1.011 0 0 1 1.43 0l2.685 2.684a.722.722 0 0 1 0 1.022ZM16 11.667c.399 0 .722.323.722.722v8.667a.722.722 0 0 1-1.444 0v-8.667c0-.399.323-.722.722-.722Z" fill="#6F7CA3"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.056 11.667a.722.722 0 0 1-.723.722h-8.666a.722.722 0 1 1 0-1.445h8.666c.4 0 .723.324.723.723Z" fill="currentColor"/></svg>',
    'settings'   => [
        'content' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_CONTENT'),
            'fields' => [
                'attribute' => [
                    'type'   => 'attribute',
                    'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_FIELD_SOURCE'),
                    'allowed_types' => ['title', 'alias', 'text', 'rich-text', 'phone', 'email', 'number', 'option', 'date-time', 'link', 'file', 'rating', 'created', 'icon'],
                    'placeholder' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_FIELD_SOURCE_PLACEHOLDER'),
                ],
                'field_icon_size' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                    'std' => '16px',
                    'depends' => [['attribute?.type', '=', 'icon']],
                ],
                'default_text' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_FIELD_FALLBACK_TEXT'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_FIELD_FALLBACK_TEXT_DESC'),
                    'placeholder' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_FIELD_FALLBACK_TEXT_PLACEHOLDER'),
                    'std' => '',
                ]
            ],
        ],
        'link' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
            'fields' => [
                'dynamic_link_switch' => [
                    'type'  => 'checkbox',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_DYNAMIC_LINK_SWITCH'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_DYNAMIC_LINK_SWITCH_DESC'),
                    'depends' => [['attribute?.type', '!=', 'link']],
                ],

                'link' => [
                    'type'  => 'link',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                    'default_tab' => 'page',
                    'depends' => [['attribute?.type', '!=', 'link'], ['dynamic_link_switch', '!=', 1]],
                ],

                'dynamic_link' => [
                    'type'  => 'attribute',
                    'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_DYNAMIC_LINK_FIELD'),
                    'allowed_types' => ['link'],
                    'hide_link_text' => true,
                    'placeholder' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_DYNAMIC_LINK_FIELD_PLACEHOLDER'),
                    'depends' => [
                        ['dynamic_link_switch', '=', 1],
                        ['attribute?.type', '!=', 'link'],
                    ],
                ],

                'enable_mailto' => [
                    'type'  => 'checkbox',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_ENABLE_MAILTO'),
                    'desc'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_ENABLE_MAILTO_DESC'),
                    'depends' => [
                        ['attribute?.type', '=', 'email'],
                        ['dynamic_link_switch', '!=', 1],
                    ],
                ],

                'enable_tel' => [
                    'type'  => 'checkbox',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_ENABLE_TEL'),
                    'desc'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_ENABLE_TEL_DESC'),
                    'depends' => [
                        ['attribute?.type', '=', 'phone'],
                        ['dynamic_link_switch', '!=', 1],
                    ],
                ],
            ],
        ],
        'general' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
            'fields' => [
                'is_downloadable' => [
                    'type'  => 'checkbox',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_DOWNLOADABLE'),
                    'depends' => [['attribute?.type', '=', 'file']]
                ],
                'file_value_override' => [
                    'type'  => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_FILE_VALUE_OVERRIDE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_GLOBAL_FILE_VALUE_OVERRIDE_DESC'),
                    'depends' => [['attribute?.type', '=', 'file']],
                ],
                'rating_icon' => [
                    'type'  => 'icon',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                    'depends' => [['attribute?.type', '=', 'rating']],
                ],
                'rating_max_length' => [
                    'type'  => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RATING_MAX_LENGTH'),
                    'min' => 1,
                    'depends' => [['attribute?.type', '=', 'rating']],
                ],
                'color' => [
                    'type'  => 'color',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    'depends' => [['attribute?.type', '!=', 'rating']],
                ],
                'rating_color' => [
                    'type'  => 'color',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RATING_COLOR'),
                    'depends' => [['attribute?.type', '=', 'rating']],
                ],
                'rating_empty_color' => [
                    'type'  => 'color',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RATING_EMPTY_COLOR'),
                    'depends' => [['attribute?.type', '=', 'rating']],
                ],
                'rating_size' => [
                    'type'  => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                    'depends' => [['attribute?.type', '=', 'rating']],
                ],
                'rating_gap' => [
                    'type'  => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                    'depends' => [['attribute?.type', '=', 'rating']],
                ],
                'typography' => [
                    'type'  => 'typography',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    'depends' => [['attribute?.type', '!=', 'rating']],
                ],
                'selector' => [
                    'type'  => 'headings',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HTML_ELEMENT'),
                    'std'   => 'p',
                    'depends' => [['attribute?.type', '!=', 'rating']],
                ],
                'alignment' => [
                    'type'              => 'alignment',
                    'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                    'responsive'        => true,
                    'available_options' => ['left', 'center', 'right'],
                ],
                'alignment_separator' => [
                    'type' => 'separator',
                ],
                'title_text_shadow' => [
                    'type'   => 'boxshadow',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
                    'std'    => '0 0 0 transparent',
                    'config' => ['spread' => false],
                    'depends' => [['attribute?.type', '!=', 'rating']],
                ],
            ],
        ],
        'button' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON'),
            'depends' => [['attribute?.type', '=', 'link']],
            'fields' => [
                'enable_button' => [
                    'type'  => 'checkbox',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ENABLE_BUTTON'),
                    'is_header' => 1,
                    'depends' => [['attribute?.type', '=', 'link']],
                ],
                'button_type' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE_DESC'),
                    'values' => [
                        'default'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_DEFAULT'),
                        'primary'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_PRIMARY'),
                        'secondary' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SECONDARY'),
                        'success'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_SUCCESS'),
                        'info'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_INFO'),
                        'warning'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_WARNING'),
                        'danger'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_DANGER'),
                        'dark'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_DARK'),
                        'link'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                        'custom'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
                    ],
                    'std'    => 'custom',
                    'depends' => [['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],
                'link_button_padding_bottom' => [
                    'type'    => 'slider',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_PADDING_BOTTOM'),
                    'max'     => 100,
                    'depends' => [['button_type', '=', 'link'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],

                'button_appearance' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_DESC'),
                    'values' => [
                        ''         => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_FLAT'),
                        'gradient' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_GRADIENT'),
                        'outline'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_OUTLINE'),
                    ],
                    'std'     => '',
                    'depends' => [['button_type', '!=', 'link'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],
                'button_shape' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_DESC'),
                    'values' => [
                        'rounded' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUNDED'),
                        'square'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_SQUARE'),
                        'round'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUND'),
                    ],
                    'std'   => 'rounded',
                    'depends' => [['button_type', '!=', 'link'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],
                'button_size' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DESC'),
                    'values' => [
                        ''       => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DEFAULT'),
                        'lg'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_LARGE'),
                        'xlg'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_XLARGE'),
                        'sm'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_SMALL'),
                        'xs'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_EXTRA_SAMLL'),
                        'custom' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
                    ],
                    'depends' => [['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],
                'button_padding' => [
                    'type'       => 'padding',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    'std'        => '',
                    'responsive' => true,
                    'depends'    => [['button_size', '=', 'custom'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],

                'button_block' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_BLOCK'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_BLOCK_DESC'),
                    'values' => [
                        ''               => Text::_('JNO'),
                        'sppb-btn-block' => Text::_('JYES'),
                    ],
                    'depends' => [['button_type', '!=', 'link'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],
            ],
        ],
        'button_style' => [
            'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_STYLE'),
            'depends' => [['button_type', '=', 'custom']],
            'fields' => [
                'button_style_state' => [
                    'type'   => 'radio',
                    'values' => [
                        'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                        'hover' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
                    ],
                    'std' => 'normal',
                    'depends' => [['button_type', '=', 'custom'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],

                'button_color' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    'std'    => '#FFFFFF',
                    'depends' => [['button_style_state', '=', 'normal'],['button_type', '=', 'custom'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],

                'button_color_hover' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    'std'    => '#FFFFFF',
                    'depends' => [['button_style_state', '=', 'hover'], ['button_type', '=', 'custom'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
                ],

                'button_background_color' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                    'std'    => '#3366FF',
                    'depends' => [
                        ['button_style_state', '=', 'normal'],
                        ['appearance', '!=', 'gradient'],
                        ['button_type', '=', 'custom'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],

                'button_background_color_hover' => [
                    'type'    => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                    'std'     => '#0037DD',
                    'depends' => [
                        ['button_style_state', '=', 'hover'],
                        ['appearance', '!=', 'gradient'],
                        ['button_type', '=', 'custom'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],

                'button_background_gradient' => [
                    'type' => 'gradient',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                    'std'  => [
                        "color"  => "#3366FF",
                        "color2" => "#0037DD",
                        "deg"    => "45",
                        "type"   => "linear"
                    ],
                    'depends' => [
                        ['button_style_state', '=', 'normal'],
                        ['appearance', '=', 'gradient'],
                        ['button_type', '=', 'custom'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],

                'button_background_gradient_hover' => [
                    'type'  => 'gradient',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                    'std'   => [
                        "color"  => "#0037DD",
                        "color2" => "#3366FF",
                        "deg"    => "45",
                        "type"   => "linear"
                    ],
                    'depends' => [
                        ['button_style_state', '=', 'hover'],
                        ['appearance', '=', 'gradient'],
                        ['button_type', '=', 'custom'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],
            ],
        ],

        'link_type_style' => [
            'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_STYLE'),
            'depends' => [['button_type', '=', 'link'], ['enable_button', '=', 1], ['attribute?.type', '=', 'link']],
            'fields' => [
                'button_link_style_state' => [
                    'type'   => 'radio',
                    'values' => [
                        'normal' => Text::_('Normal'),
                        'hover' => Text::_('Hover'),
                    ],
                    'std' => 'normal',
                ],

                'link_button_color' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    'std'    => '#3366FF',
                    'depends' => [
                        ['button_link_style_state', '=', 'normal'],
                        ['button_type', '=', 'link'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],

                'link_button_border_width' => [
                    'type'    => 'slider',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_WIDTH'),
                    'max'     => 10,
                    'std'     => 1,
                    'depends' => [
                        ['button_link_style_state', '=', 'normal'],
                        ['button_type', '=', 'link'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ]
                ],

                'link_button_border_color' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_COLOR'),
                    'std'    => '#3366FF',
                    'depends' => [
                        ['button_link_style_state', '=', 'normal'],
                        ['button_type', '=', 'link'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],

                'link_button_hover_color' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    'std'    => '#0037DD',
                    'depends' => [
                        ['button_link_style_state', '=', 'hover'],
                        ['button_type', '=', 'link'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],

                'link_button_border_hover_color' => [
                    'type'   => 'color',
                    'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_COLOR'),
                    'std'    => '#0037DD',
                    'depends' => [
                        ['button_link_style_state', '=', 'hover'],
                        ['button_type', '=', 'link'],
                        ['enable_button', '=', 1],
                        ['attribute?.type', '=', 'link'],
                    ],
                ],
            ],
        ],
        'icon' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
            'depends' => [['attribute?.type', '!=', 'icon']],
            'fields' => [
                'icon' => [
                    'type'  => 'icon',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                ],
                'icon_position' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_ICON_POSITION'),
                    'values' => [
                        'left' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LEFT'),
                        'right' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RIGHT'),
                    ],
                    'std' => 'left',
                ],
                'icon_gap' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                    'std' => '8px',
                ],
                'icon_color' => [
                    'type' => 'color',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                ],
                'icon_size' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                    'std' => '16px',
                ],
            ],
        ],
        'title_spacing' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE_SPACING'),
            'fields' => [
                'title_margin' => [
                    'type'       => 'margin',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                    'desc'       => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN_DESC'),
                    'std'        => ['xl' => '0px 0px 0px 0px', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                    'responsive' => true,
                ],

                'title_padding' => [
                    'type'       => 'padding',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    'desc'       => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING_DESC'),
                    'std'        => ['xl' => '0px 0px 0px 0px', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                    'responsive' => true,
                ],
            ],
        ],
        'content_truncation' => [
            'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CONTENT_TRUNCATION'),
            'fields' => [
                'content_truncation' => [
                    'type'      => 'checkbox',
                    'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_CONTENT_TRUNCATION'),
                    'std'       => 0,
                    'is_header' => 1,
                    'depends'   => [['attribute?.type', '!=', 'rating'], ['attribute?.type', '!=', 'file']],
                ],

                'content_truncation_max_word' => [
                    'type'    => 'number',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_MAX_WORD_TO_SHOW'),
                    'depends' => [['content_truncation', '=', 1], ['attribute?.type', '!=', 'rating'], ['attribute?.type', '!=', 'file']],
                    'std'     => 30
                ],

                'content_truncation_action_text' => [
                    'type'    => 'text',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTION_TEXT'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTION_TEXT_DESC'),
                    'depends' => [['content_truncation', '=', 1], ['attribute?.type', '!=', 'rating'], ['attribute?.type', '!=', 'file']],
                    'std'     => 'Show More',
                ],

                'content_truncation_action_typography' => [
                    'type'      => 'typography',
                    'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    'depends'   => [['content_truncation', '=', 1], ['attribute?.type', '!=', 'rating'], ['attribute?.type', '!=', 'file']],
                ],

                'content_truncation_action_text_color' => [
                    'type'    => 'color',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTION_COLOR'),
                    'depends' => [['content_truncation', '=', 1], ['attribute?.type', '!=', 'rating'], ['attribute?.type', '!=', 'file']],
                    'std'     => '#3366FF'
                ],
            ]
        ],
    ],
]);

