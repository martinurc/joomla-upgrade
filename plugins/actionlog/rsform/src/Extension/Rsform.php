<?php
/**
 * @package        RSForm! Pro
 * @copyright  (c) 2025 RSJoomla!
 * @link           https://www.rsjoomla.com
 * @license        GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace Rsjoomla\Plugin\Actionlog\Rsform\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Rsform extends ActionLogPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use UserFactoryAwareTrait;

    /**
     * Array of loggable extensions.
     *
     * @var    array
     * @since  3.9.0
     */
    protected $loggableExtensions = [];

    /**
     * Constructor.
     *
     * @param   DispatcherInterface  $dispatcher  The dispatcher
     * @param   array                $config      An optional associative array of configuration settings
     *
     * @since   3.9.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config)
    {
        parent::__construct($dispatcher, $config);

        $params = ComponentHelper::getComponent('com_actionlogs')->getParams();

        $this->loggableExtensions = $params->get('loggable_extensions', []);
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return array
     *
     * @since   5.2.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
	        'onRsformFormChangeStatus' => 'onRsformFormChangeStatus',
	        'onRsformFormDelete' => 'onRsformFormDelete',
	        'onRsformBackendFormCopy' => 'onRsformBackendFormCopy',
	        'onRsformFormNew' => 'onRsformFormNew',
	        'onRsformFormRestore' => 'onRsformFormRestore',
	        'onRsformBackendFormRestoreTruncate' => 'onRsformBackendFormRestoreTruncate',
	        'onRsformFormSave' => 'onRsformFormSave',
	        'onRsformFormBackup' => 'onRsformFormBackup',
	        'onRsformComponentSave' => 'onRsformComponentSave',
	        'onRsformComponentCopy' => 'onRsformComponentCopy',
	        'onRsformComponentChangeStatus' => 'onRsformComponentChangeStatus',
	        'onRsformBackendAfterComponentDeleted' => 'onRsformBackendAfterComponentDeleted',
	        'onRsformSubmissionsDelete' => 'onRsformSubmissionsDelete',
	        'onRsformSubmissionsClear' => 'onRsformSubmissionsClear',
	        'onRsformSubmissionsExport' => 'onRsformSubmissionsExport',
	        'onRsformSubmissionsImport' => 'onRsformSubmissionsImport',
	        'onRsformSubmissionSave' => 'onRsformSubmissionSave',
	        'onRsformDirectorySave' => 'onRsformDirectorySave',
        ];
    }

	private function getComponentName($componentId)
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->qn('PropertyValue'))
			->from($db->qn('#__rsform_properties'))
			->where($db->qn('ComponentId') . ' = ' . $db->q($componentId))
			->where($db->qn('PropertyName') . ' = ' . $db->q('NAME'));
		return $db->setQuery($query)->loadResult() ?? Text::_('PLG_ACTIONLOG_RSFORM_UNKNOWN_COMPONENT');
	}

	public function onRsformFormChangeStatus($event): void
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formIds    = (array) $event->getArgument('formIds');
		$value      = (int) $event->getArgument('value');

		foreach ($formIds as $formId)
		{
			$message = [
				'id'          => $formId,
				'itemlink'    => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_PUBLISHED_' . $value, 'com_rsform');
		}
	}

	public function onRsformFormDelete($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId    = (int) $event->getArgument('formId');
		$message = [
			'id'          => $formId,
			'itemlink'    => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_DELETED', 'com_rsform');
	}

	public function onRsformBackendFormCopy($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$args           = $event->getArgument(0);
		$formId         = (int) $args['formId'];
		$newFormId      = (int) $args['newFormId'];
		$message = [
			'id'            => $formId,
			'newitemlink'   => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $newFormId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_COPIED', 'com_rsform');
	}

	public function onRsformFormNew($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId    = (int) $event->getArgument('formId');
		$message = [
			'id'          => $formId,
			'itemlink'    => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_CREATED', 'com_rsform');
	}

	public function onRsformComponentSave($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$componentId    = (int) $event->getArgument('id');
		$isNew          = (int) $event->getArgument('isNew');
		$type           = (int) $event->getArgument('type');
		$formId         = (int) $event->getArgument('formId');
		$value          = $event->getArgument('value');

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->qn('ComponentTypeName'))
			->from($db->qn('#__rsform_component_types'))
			->where($db->qn('ComponentTypeId') . ' = ' . $db->q($type));
		$typeName = $db->setQuery($query)->loadResult() ?? Text::_('PLG_ACTIONLOG_RSFORM_UNKNOWN_COMPONENT');

		if ($isNew)
		{
			$message = [
				'id'          => $formId,
				'type'        => $typeName,
				'name'        => $this->getComponentName($componentId),
				'itemlink'    => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_COMPONENT_ADDED', 'com_rsform');
		}

		if ($value !== null)
		{
			$message = [
				'id'          => $formId,
				'name'        => $this->getComponentName($componentId),
				'itemlink'    => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_COMPONENT_PUBLISHED_' . $value, 'com_rsform');
		}
	}

	public function onRsformComponentCopy($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$newFormId      = (int) $event->getArgument('newFormId');
		$formId         = (int) $event->getArgument('formId');
		$componentId    = (int) $event->getArgument('id');
		$message = [
			'id'            => $newFormId,
			'sourceid'      => $formId,
			'name'          => $this->getComponentName($componentId),
			'newitemlink'   => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $newFormId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], $newFormId === $formId ? 'PLG_ACTIONLOG_RSFORM_COMPONENT_DUPLICATED' : 'PLG_ACTIONLOG_RSFORM_COMPONENT_COPIED', 'com_rsform');
	}

	public function onRsformBackendAfterComponentDeleted($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$componentIds   = (array) $event->getArgument(0);
		$formId         = (int) $event->getArgument(1);
		$names          = (array) $event->getArgument(2);

		foreach ($componentIds as $componentId)
		{
			$message = [
				'id'            => $formId,
				'name'          => isset($names[$componentId]) ? $names[$componentId]->PropertyValue : Text::_('PLG_ACTIONLOG_RSFORM_UNKNOWN_COMPONENT'),
				'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_COMPONENT_DELETED', 'com_rsform');
		}
	}

	public function onRsformFormRestore($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$form = $event->getArgument(0);

		$message = [
			'id'            => $form->FormId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $form->FormId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_RESTORED', 'com_rsform');
	}

	public function onRsformBackendFormRestoreTruncate($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$message = [];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_RESTORE_TRUNCATED', 'com_rsform');
	}

	public function onRsformFormBackup($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$form = $event->getArgument(0);

		$message = [
			'id'            => $form->FormId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $form->FormId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_BACKED_UP', 'com_rsform');
	}

	public function onRsformSubmissionsDelete($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId = (int) $event->getArgument('formId');
		$ids    = (array) $event->getArgument('ids');

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$message = [
					'id'            => $formId,
					'submission'    => $id,
					'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
				];

				$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_SUBMISSION_DELETED', 'com_rsform');
			}
		}
	}

	public function onRsformSubmissionsClear($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId = (int) $event->getArgument('formId');

		$message = [
			'id'            => $formId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_SUBMISSIONS_CLEAR', 'com_rsform');
	}

	public function onRsformSubmissionsExport($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId = (int) $event->getArgument('formId');
		$filename = $event->getArgument('filename');

		$message = [
			'id'            => $formId,
			'filename'      => $filename,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_SUBMISSIONS_EXPORTED', 'com_rsform');
	}

	public function onRsformSubmissionsImport($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId = (int) $event->getArgument('formId');

		$message = [
			'id'            => $formId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_SUBMISSIONS_IMPORTED', 'com_rsform');
	}

	public function onRsformFormSave($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$form = $event->getArgument(0);
		$original = $event->getArgument(1);

		if ($form->Published != $original->Published)
		{
			$message = [
				'id'            => $form->FormId,
				'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $form->FormId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_PUBLISHED_' . $form->Published, 'com_rsform');
		}

		$message = [
			'id'            => $form->FormId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $form->FormId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_FORM_SAVED', 'com_rsform');
	}

	public function onRsformDirectorySave($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId = (int) $event->getArgument('formId');
		$isNew  = (int) $event->getArgument('isNew');

		if ($isNew)
		{
			$message = [
				'id'            => $formId,
				'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
				'directorylink' => 'index.php?option=com_rsform&view=directory&layout=edit&formId=' . $formId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_DIRECTORY_PUBLISHED', 'com_rsform');
		}

		$message = [
			'id'            => $formId,
			'itemlink'      => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			'directorylink' => 'index.php?option=com_rsform&view=directory&layout=edit&formId=' . $formId,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_DIRECTORY_SAVED', 'com_rsform');
	}

	public function onRsformComponentChangeStatus($event): void
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId    = (int) $event->getArgument('formId');
		$value     = (int) $event->getArgument('value');
		$ids       = (array) $event->getArgument('ids');

		foreach ($ids as $componentId)
		{
			$message = [
				'id'          => $formId,
				'name'        => $this->getComponentName($componentId),
				'itemlink'    => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			];

			$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_COMPONENT_PUBLISHED_' . $value, 'com_rsform');
		}
	}

	public function onRsformSubmissionSave($event)
	{
		if (!$this->checkLoggable())
		{
			return;
		}

		$formId    = (int) $event->getArgument('formId');
		$id        = (int) $event->getArgument('submissionId');

		$message = [
			'id'             => $formId,
			'itemlink'       => 'index.php?option=com_rsform&view=forms&layout=edit&formId=' . $formId,
			'submission'     => $id,
			'submissionlink' => 'index.php?option=com_rsform&view=submissions&layout=edit&cid=' . $id,
		];

		$this->addLog([$message], 'PLG_ACTIONLOG_RSFORM_SUBMISSION_SAVED', 'com_rsform');
	}

    /**
     * Function to check if a component is loggable or not
     *
     * @param   string  $extension  The extension that triggered the event
     *
     * @return  boolean
     *
     * @since   3.9.0
     */
    protected function checkLoggable()
    {
        return \in_array('com_rsform', $this->loggableExtensions);
    }
}
