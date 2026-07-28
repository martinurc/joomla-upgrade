<?php

/**
 * @package SP Page Builder
 * @subpackage  com_sppagebuilder
 *
 * Joomla's plg_system_schemaorg only injects the article schema form in the administrator
 * application (see Schemaorg::onContentPrepareForm). Frontend editor requests run in the site
 * application, so we replicate that injection here when the schema fieldset is still empty.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

/**
 * Ensures com_content.article has the Schema tab fields (core system plugin + schemaorg type plugins).
 */
class ContentArticleSchemaFormPreparer
{
	/**
	 * @param   Form  $form  Article form instance (com_content.article).
	 *
	 * @return  void
	 */
	public static function ensureSchemaorgTab(Form $form): void
	{
		$context = $form->getName();

		if (!self::isSchemaContextSupported($context))
		{
			return;
		}

		if ($form->getField('schemaType', 'schema'))
		{
			return;
		}

		$schemaorgXml = JPATH_PLUGINS . '/system/schemaorg/forms/schemaorg.xml';

		if (!\is_file($schemaorgXml))
		{
			return;
		}

		$plugin = PluginHelper::getPlugin('system', 'schemaorg');

		if (!$plugin)
		{
			return;
		}

		$lang = Factory::getLanguage();
		$lang->load('plg_system_schemaorg', JPATH_ADMINISTRATOR)
			|| $lang->load('plg_system_schemaorg', JPATH_PLUGINS . '/system/schemaorg');

		$form->loadFile($schemaorgXml);

		$params = new Registry($plugin->params);

		if (!$params->get('baseType'))
		{
			$form->removeField('schemaType', 'schema');

			$user = Factory::getApplication()->getIdentity();
			$infoText = Text::_('PLG_SYSTEM_SCHEMAORG_FIELD_SCHEMA_DESCRIPTION_NOT_CONFIGURATED');

			if ($user->authorise('core.edit', 'com_plugins'))
			{
				$infoText = Text::sprintf(
					'PLG_SYSTEM_SCHEMAORG_FIELD_SCHEMA_DESCRIPTION_NOT_CONFIGURATED_ADMIN',
					(int) $plugin->id
				);
			}

			$form->setFieldAttribute('schemainfo', 'description', $infoText, 'schema');
			$form->setFieldAttribute('extendJed', 'type', 'hidden', 'schema');
			$form->setFieldAttribute('extendJed', 'class', 'hidden', 'schema');

			return;
		}

		if (!\class_exists(\Joomla\CMS\Event\Plugin\System\Schemaorg\PrepareFormEvent::class))
		{
			return;
		}

		$dispatcher = Factory::getApplication()->getDispatcher();
		$event      = new \Joomla\CMS\Event\Plugin\System\Schemaorg\PrepareFormEvent('onSchemaPrepareForm', ['subject' => $form]);

		PluginHelper::importPlugin('schemaorg', null, true, $dispatcher);
		$dispatcher->dispatch('onSchemaPrepareForm', $event);
	}

	/**
	 * @param   string  $context  Form name e.g. com_content.article
	 */
	private static function isSchemaContextSupported(string $context): bool
	{
		if (!\str_contains($context, '.'))
		{
			return false;
		}

		$parts     = \explode('.', $context, 2);
		$component = Factory::getApplication()->bootComponent($parts[0]);

		if ($component instanceof \Joomla\CMS\Schemaorg\SchemaorgServiceInterface)
		{
			return \in_array($context, \array_keys($component->getSchemaorgContexts()), true);
		}

		return false;
	}
}
