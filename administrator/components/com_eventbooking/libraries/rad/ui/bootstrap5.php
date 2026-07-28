<?php
/**
 * @package     MPF
 * @subpackage  UI
 *
 * @copyright   Copyright (C) 2016 - 2018 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Base class for a Joomla Administrator Controller. It handles add, edit, delete, publish, unpublish records....
 *
 * @package       MPF
 * @subpackage    UI
 * @since         2.0
 */
class RADUiBootstrap5 extends RADUiAbstract implements RADUiInterface
{
	/**
	 * UIKit framework classes
	 *
	 * @var array
	 */
	protected $frameworkClasses = [
		'form-control',
		'form-check-input',
		'form-select',
	];

	/**
	 * Constructor
	 *
	 * @param   array  $classMaps
	 */
	public function __construct($classMaps = [])
	{
		if (empty($classMaps))
		{
			$classMaps = [
				// Grid
				'row-fluid'         => 'row',
				'span1'             => 'col-md-1',
				'span2'             => 'col-md-2',
				'span3'             => 'col-md-3',
				'span4'             => 'col-md-4',
				'span5'             => 'col-md-5',
				'span6'             => 'col-md-6',
				'span7'             => 'col-md-7',
				'span8'             => 'col-md-8',
				'span9'             => 'col-md-9',
				'span10'            => 'col-md-10',
				'span11'            => 'col-md-11',
				'span12'            => 'col-md-12',
				// Form classes
				'form'              => 'form',
				'form-horizontal'   => 'form-horizontal',
				'control-group'     => 'row form-group form-row',
				'control-label'     => 'col-md-3 form-control-label',
				'controls'          => 'col-md-9 eb-form-control',
				// Button classes
				'btn'               => 'btn btn-secondary',
				'btn-primary'       => 'btn-primary',
				'btn-info'          => 'btn-info',
				'btn-success'       => 'btn-success',
				'btn-warning'       => 'btn-warning',
				'btn-danger'        => 'btn-danger',
				'btn-inverse'       => 'btn-primary',
				'btn-link'          => 'btn-link',
				// Image classes
				'thumbnail'         => 'img-thumbnail',
				'img-polaroid'      => 'img-thumbnail',
				'img-rounded'       => 'img-thumbnail rounded',
				'img-circle'        => 'img-thumbnail rounded-circle',
				'img-responsive'    => 'img-fluid',
				// Table classes
				'table'             => 'table',
				'table-striped'     => 'table-striped',
				'table-bordered'    => 'table-bordered',
				'table-condensed'   => 'table-sm',
				'table-hover'       => 'table-hover',
				// Badge classes
				'badge'             => 'badge',
				'badge-success'     => 'bg-success',
				'badge-warning'     => 'bg-warning',
				'badge-info'        => 'bg-info',
				'badge-danger'      => 'bg-danger',
				// Text classes
				'text-muted'        => 'text-muted',
				'text-warning'      => 'text-warning',
				'text-error'        => 'text-error',
				'text-info'         => 'text-info',
				'text-success'      => 'text-success',
				// Text Alignment
				'text-left'         => 'text-start',
				'text-center'       => 'text-center',
				'text-right'        => 'text-end',
				// Form input sizes
				'input-mini'        => 'input-mini',
				'input-small'       => 'input-small',
				'input-medium'      => 'input-medium',
				'input-large'       => 'input-large',
				'input-xlarge'      => 'input-xlarge',
				'input-xxlarge'     => 'input-xxlarge',
				// Button sizes
				'btn-mini'          => 'btn-xs',
				'btn-small'         => 'btn-sm',
				'btn-large'         => 'btn-lg',
				// Responsive utilities
				'visible-phone'     => 'd-block d-sm-none',
				'visible-tablet'    => 'visible-sm',
				'visible-desktop'   => 'd-block d-md-none',
				'hidden-phone'      => 'd-none d-sm-block d-md-table-cell',
				'hidden-tablet'     => 'd-sm-none',
				'hidden-desktop'    => 'd-md-none hidden-lg',
				// Utility classes
				'pull-left'         => 'float-start',
				'pull-right'        => 'float-end',
				'clearfix'          => 'clearfix',
				'input-prepend'     => 'input-group-prepend',
				'input-append'      => 'input-group-append',
				'add-on'            => 'input-group-text',
				'nav'               => 'nav',
				'nav-pills'         => 'nav-pills',
				'nav-stacked'       => 'nav-stacked',
				'nav-tabs'          => 'nav-tabs',
				'icon-publish'      => 'fa fa-check',
				'icon-unpublish'    => 'fa fa-times',
				'eb-one-one'        => 'col-md-12',
				'eb-one-half'       => 'col-md-6',
				'eb-one-third'      => 'col-md-4',
				'eb-two-thirds'     => 'col-md-8',
				'eb-one-quarter'    => 'col-md-3',
				'eb-two-quarters'   => 'col-md-6',
				'eb-three-quarters' => 'col-md-9',
				// Background Color classes
				'bg-primary'        => 'bg-primary',
			];
		}

		$this->classMaps = $classMaps;
	}

	/**
	 * Method to render input with prepend add-on
	 *
	 * @param   string  $input
	 * @param   string  $addOn
	 *
	 * @return string
	 */
	public function getPrependAddon($input, $addOn)
	{
		$html   = [];
		$html[] = '<div class="eb-addon-container input-group">';
		$html[] = '<span class="input-group-text">' . $addOn . '</span>';
		$html[] = $input;
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Method to render input with append add-on
	 *
	 * @param   string  $input
	 * @param   string  $addOn
	 *
	 * @return string
	 */
	public function getAppendAddon($input, $addOn)
	{
		$html   = [];
		$html[] = '<div class="eb-addon-container input-group">';
		$html[] = $input;
		$html[] = '<span class="input-group-text">' . $addOn . '</span>';
		$html[] = '</div>';

		return implode("\n", $html);
	}
}
