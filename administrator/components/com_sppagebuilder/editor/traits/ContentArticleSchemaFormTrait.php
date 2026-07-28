<?php

/**
 * @package SP Page Builder
 * @subpackage  com_sppagebuilder
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

trait ContentArticleSchemaFormTrait
{
	/**
	 * Build com_content.article form with correct paths when this request is not in com_content
	 * (Joomla FormBehaviorTrait registers paths from JPATH_COMPONENT — see joomla/joomla-cms#43347).
	 *
	 * @param   object  $articleModel  Administrator Article model (preprocessForm must run for schema plugins).
	 *
	 * @return  Form
	 */
	private function loadContentArticleFormForSchemaExport(object $articleModel): Form
	{
		$base = JPATH_ADMINISTRATOR . '/components/com_content';

		if (!\is_dir($base)) {
			throw new \RuntimeException('com_content administrator files not found');
		}

		$formFactory = Factory::getContainer()->get(FormFactoryInterface::class);
		$form        = $formFactory->createForm(
			'com_content.article',
			['control' => 'jform', 'load_data' => false]
		);

		if ($form instanceof CurrentUserInterface) {
			$form->setCurrentUser(Factory::getApplication()->getIdentity());
		}

		// Same path registration order as FormBehaviorTrait::loadForm(), but for com_content.
		Form::addFormPath($base . '/forms');
		Form::addFormPath($base . '/models/forms');
		Form::addFieldPath($base . '/models/fields');
		Form::addFormPath($base . '/model/form');
		Form::addFieldPath($base . '/model/field');

		if (!$form->loadFile('article', false)) {
			throw new \RuntimeException(Text::_('COM_SPPAGEBUILDER_EDITOR_SCHEMA_FORM_LOAD_FAILED'));
		}

		$data = [];
		$prep = new \ReflectionMethod($articleModel, 'preprocessForm');
		$prep->setAccessible(true);
		$prep->invoke($articleModel, $form, $data, 'content');
		$form->bind($data);

		$preparer = JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/editor/helpers/ContentArticleSchemaFormPreparer.php';

		if (\is_file($preparer))
		{
			require_once $preparer;
			ContentArticleSchemaFormPreparer::ensureSchemaorgTab($form);
		}

		return $form;
	}

	/**
	 * Return JSON field tree for com_content.article Schema.org tab (from core + schemaorg plugins).
	 *
	 * @return void
	 */
	public function contentArticleSchemaForm(): void
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'PUT', 'DELETE', 'PATCH'], $method);

		if (!\interface_exists(\Joomla\CMS\Schemaorg\SchemaorgServiceInterface::class)) {
			$this->sendResponse(
				[
					'available' => false,
					'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_SCHEMA_FORM_JOOMLA_VERSION'),
				],
				200
			);

			return;
		}

		$helper = JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/editor/helpers/ContentArticleSchemaFormExporter.php';

		if (!\is_file($helper)) {
			$this->sendResponse(['available' => false, 'message' => 'Exporter missing'], 500);

			return;
		}

		require_once $helper;

		try {
			$component = Factory::getApplication()->bootComponent('com_content');

			if (!$component instanceof \Joomla\CMS\Schemaorg\SchemaorgServiceInterface) {
				$this->sendResponse(
					[
						'available' => false,
						'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_SCHEMA_FORM_COM_CONTENT'),
					],
					200
				);

				return;
			}

			$model = $component->getMVCFactory()->createModel('Article', 'Administrator', ['ignore_request' => true]);
			$form = $this->loadContentArticleFormForSchemaExport($model);

			$exporter = new ContentArticleSchemaFormExporter();
			$data = $exporter->export($form);
			$link = $this->getSchemaorgPluginConfigLinkIfNotConfigured();
			$data['link'] = $link;
			$data['form_empty'] = !empty($link) || empty($data['fields']);
			$this->sendResponse($data, 200);
		} catch (\Throwable $e) {
			$this->sendResponse(
				[
					'available' => false,
					'message' => $e->getMessage(),
				],
				200
			);
		}
	}

	private function getSchemaorgPluginConfigLinkIfNotConfigured(): ?string
	{
		$plugin = PluginHelper::getPlugin('system', 'schemaorg');

		if (!$plugin) {
			return null;
		}

		$params = new Registry($plugin->params);

		if ($params->get('baseType')) {
			return null;
		}

		return Uri::root() . 'administrator/index.php?option=com_plugins&task=plugin.edit&extension_id=' . (int) $plugin->id;
	}
}
