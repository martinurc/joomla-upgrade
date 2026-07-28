<?php
/**
 * @package     RAD
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2015 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

trait RADViewForm
{
	/**
	 * Quick method to add text input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $description
	 * @param   string  $class
	 * @param   array   $attributes
	 *
	 * @return void
	 */
	protected function text(string $name, string $title, string $description = '', string $class = 'form-control', array $attributes = []): void
	{
		$type = 'text';

		include __DIR__ . '/tmpl/text.php';
	}

	/**
	 * Quick method to add password input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $description
	 * @param   string  $class
	 * @param   array   $attributes
	 *
	 * @return void
	 */
	protected function password(string $name, string $title, string $description = '', string $class = 'form-control', array $attributes = []): void
	{
		$type = 'password';

		include __DIR__ . '/tmpl/text.php';
	}

	/**
	 * Quick method to add password input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $description
	 * @param   string  $class
	 * @param   array   $attributes
	 *
	 * @return void
	 */
	protected function number(string $name, string $title, string $description = '', string $class = 'form-control', array $attributes = []): void
	{
		$type = 'number';

		include __DIR__ . '/tmpl/text.php';
	}

	/**
	 * Quick method to add textarea input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $description
	 * @param   string  $class
	 * @param   int     $rows
	 * @param   int     $cols
	 *
	 * @return void
	 */
	protected function textarea(
		string $name,
		string $title,
		string $description = '',
		string $class = 'form-control',
		int $rows = 10,
		int $cols = 70
	): void {
		include __DIR__ . '/tmpl/textarea.php';
	}

	/**
	 * Quick method to add boolean input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $description
	 *
	 * @return void
	 */
	protected function boolean(string $name, string $title, string $description = ''): void
	{
		include __DIR__ . '/tmpl/boolean.php';
	}

	/**
	 * Quick method to add calendar input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $format
	 * @param   array   $attribs
	 * @param   string  $description
	 *
	 * @return void
	 */
	protected function calendar(string $name, string $title, string $format = '%Y-%m-%d', $attribs = [], string $description = ''): void
	{
		include __DIR__ . '/tmpl/calendar.php';
	}

	/**
	 * Quick method to allow adding editor input to the form
	 *
	 * @param   string  $name
	 * @param   string  $title
	 * @param   string  $description
	 * @param   string  $width
	 * @param   int     $height
	 * @param   int     $cols
	 * @param   int     $rows
	 *
	 * @return void
	 */
	protected function editor(
		string $name,
		string $title,
		string $description = '',
		string $width = '100%',
		int $height = 400,
		int $cols = 75,
		int $rows = 10
	): void {
		include __DIR__ . '/tmpl/editor.php';
	}

	/**
	 * Build an HTML attribute string from an array.
	 *
	 * @param   array  $attributes
	 *
	 * @return string
	 */
	protected function getAttributesString(array $attributes)
	{
		$html = [];

		foreach ($attributes as $key => $value)
		{
			if (is_bool($value))
			{
				$html[] = " $key ";
			}
			else
			{
				$html[] = $key . '="' . htmlentities($value, ENT_QUOTES, 'UTF-8', false) . '"';
			}
		}

		return count($html) > 0 ? ' ' . implode(' ', $html) : '';
	}
}