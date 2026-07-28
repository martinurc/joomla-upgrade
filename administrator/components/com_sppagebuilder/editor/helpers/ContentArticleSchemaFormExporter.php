<?php

/**
 * @package SP Page Builder
 * @subpackage  com_sppagebuilder
 *
 * Exports Joomla com_content.article Schema tab form (Schema.org plugins) as JSON for the React editor.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Language\Text;

/**
 * Builds a portable JSON tree from the live Joomla article schema form (after plugin events).
 */
class ContentArticleSchemaFormExporter
{
	/**
	 * @param   Form  $form  Form instance (e.g. com_content.article from ArticleModel::getForm).
	 *
	 * @return  array<string,mixed>
	 */
	public function export(Form $form): array
	{
		$fields = [];

		foreach ($form->getFieldsets() as $fieldsetName => $_fs) {
			foreach ($form->getFieldset($fieldsetName) as $field) {
				$group = $field->group ?? '';

				if ($group !== 'schema') {
					continue;
				}

				$fields[] = $this->exportField($field, $form, 'schema');
			}
		}

		return [
			'available' => true,
			'fields' => $fields,
		];
	}

	/**
	 * @param   FormField  $field       Field instance.
	 * @param   Form       $ownerForm   Form that owns this field.
	 * @param   string     $dotGroup    Dot-path group under attribs.schema (e.g. schema, schema.Article).
	 *
	 * @return  array<string,mixed>
	 */
	private function exportField(FormField $field, Form $ownerForm, string $dotGroup): array
	{
		$name = (string) $field->getAttribute('name');
		// FormField::$type / XML `type` may be mixed case (e.g. Note, Hidden); React expects lowercase.
		$type = strtolower((string) $field->type);
		$valuePath = $dotGroup === 'schema' ? $name : $dotGroup . '.' . $name;

		$showonRaw = (string) $field->getAttribute('showon');
		$formControl = 'attribs';
		$showon = FormHelper::parseShowOnConditions($showonRaw, $formControl, $dotGroup);

		foreach ($showon as &$row) {
			$row['path'] = $this->normalizeSchemaShowOnPath($this->bracketPathToDot((string) $row['field']));
		}
		unset($row);

		$node = [
			'name' => $name,
			'type' => $type,
			'valuePath' => $valuePath,
			'label' => $this->translate((string) $field->getAttribute('label')),
			'description' => $this->translate((string) $field->getAttribute('description')),
			'default' => $field->getAttribute('default') !== null ? (string) $field->getAttribute('default') : null,
			'showon' => $showon,
			'class' => (string) $field->getAttribute('class'),
		];

		if ($type === 'list' && $field instanceof ListField) {
			$node['options'] = $this->listFieldOptions($field);
		}

		if ($type === 'calendar') {
			$node['showTime'] = ((string) $field->getAttribute('showtime')) === 'true';
		}

		if ($type === 'subform' && $field instanceof SubformField) {
			$node['multiple'] = (bool) $field->multiple;
			$node['min'] = (int) $field->min;
			$node['max'] = (int) $field->max;
			$childGroup = $dotGroup . '.' . $name;

			try {
				$subForm = $field->loadSubForm();
				$node['fields'] = $this->exportSubformFields($subForm, $childGroup);
			} catch (\Throwable $e) {
				$node['fields'] = [];
				$node['loadError'] = $e->getMessage();
			}
		}

		return $node;
	}

	/**
	 * @param   Form  $subForm    Inner form from SubformField::loadSubForm().
	 * @param   string  $dotGroup  Group under attribs.schema (e.g. schema.Article).
	 *
	 * @return  array<int,array<string,mixed>>
	 */
	private function exportSubformFields(Form $subForm, string $dotGroup): array
	{
		$out = [];

		// Subform inner <form> XML (e.g. Schema.org plugins) often has no <fieldset>, so
		// getFieldsets() is empty and no fields would export. getFieldset() with no argument
		// returns all fields in that form (see Joomla\Form\Form::getFieldset).
		foreach ($subForm->getFieldset() as $field) {
			$out[] = $this->exportField($field, $subForm, $dotGroup);
		}

		return $out;
	}

	/**
	 * @param   ListField  $field
	 *
	 * @return  array<int,array{value:string,label:string}>
	 */
	private function listFieldOptions(ListField $field): array
	{
		$method = (new \ReflectionClass(ListField::class))->getMethod('getOptions');
		$method->setAccessible(true);
		/** @var array<int,object> $opts */
		$opts = $method->invoke($field);
		$result = [];

		foreach ($opts as $opt) {
			if (\is_array($opt)) {
				$result[] = [
					'value' => (string) ($opt['value'] ?? ''),
					'label' => $this->translate((string) ($opt['text'] ?? '')),
				];
			} else {
				$result[] = [
					'value' => (string) ($opt->value ?? ''),
					'label' => $this->translate((string) ($opt->text ?? '')),
				];
			}
		}

		return $result;
	}

	private function translate(string $key): string
	{
		if ($key === '') {
			return '';
		}

		return Text::_($key);
	}

	/**
	 * Turn attribs[schema][schemaType] into attribs.schema.schemaType for react-hook-form getValues().
	 */
	private function bracketPathToDot(string $field): string
	{
		$field = trim($field);

		if ($field === '') {
			return '';
		}

		$segments = preg_split('/\[|\]/', $field, -1, PREG_SPLIT_NO_EMPTY);

		return $segments ? implode('.', $segments) : $field;
	}

	/**
	 * parseShowOnConditions() prefixes the active subform group (e.g. schema.Article), so
	 * "schemaType:Article" becomes attribs.schema.Article.schemaType. Stored data and RHF use
	 * attribs.schema.schemaType. Normalize for exporters / both editors.
	 *
	 * @param   string  $dotPath  Dot path after bracketPathToDot()
	 *
	 * @return  string
	 */
	private function normalizeSchemaShowOnPath(string $dotPath): string
	{
		if (preg_match('#^attribs\.schema\.([^.]+)\.schemaType$#', $dotPath, $m)) {
			if ($m[1] !== 'schema') {
				return 'attribs.schema.schemaType';
			}
		}

		return $dotPath;
	}
}
