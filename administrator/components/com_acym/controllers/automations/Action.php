<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Action
{
    public function action()
    {
        acym_session();
        acym_setVar('layout', 'action');
        $id = acym_getVar('int', 'id');
        $mailId = acym_getVar('string', 'mailid');
        $andMailEditor = acym_getVar('int', 'and');
        $stepClass = new StepClass();
        $automationClass = new AutomationClass();
        $actionClass = new ActionClass();
        $conditionClass = new ConditionClass();
        $mailClass = new MailClass();
        $tagClass = new TagClass();
        $workflowHelper = new WorkflowHelper();

        $actionObject = new \stdClass();
        $step = new \stdClass();
        $condition = new \stdClass();

        if (!empty($id)) {
            $automation = $automationClass->getOneById($id);
            $this->breadcrumb[acym_translation($automation->name)] = acym_completeLink('automation&task=edit&step=action&id='.$automation->id);
            $steps = $stepClass->getStepsByAutomationId($id);

            if (!empty($steps)) {
                $step = $steps[0];
                $conditions = $conditionClass->getConditionsByStepId($step->id);
                if (empty($conditions)) {
                    acym_setVar('stepId', $step->id);
                    acym_setVar('id', $id);
                    acym_enqueueMessage(acym_translation('ACYM_PLEASE_SET_CONDITION_OR_SAVE'), 'warning');

                    return $this->condition();
                }

                $condition = $conditions[0];
                $actions = $actionClass->getActionsByConditionId($condition->id);
                if (!empty($actions)) $actionObject = $actions[0];
            }
        } else {
            $automation = new \stdClass();
            $this->breadcrumb[acym_translation('ACYM_NEW_MASS_ACTION')] = acym_completeLink('automation&task=edit&step=action');

            if (!empty($_SESSION['massAction']['actions'])) {
                $actionObject->actions = $_SESSION['massAction']['actions'];
            }
        }

        if (!empty($actionObject->actions) && !is_array($actionObject->actions)) $actionObject->actions = json_decode($actionObject->actions, true);

        if (!empty($actionObject->actions[$andMailEditor]) && !empty($mailId) || !empty($actionObject->actions[$andMailEditor]['acy_add_queue']['mail_id'])) {
            $mail = $mailClass->getOneById(empty($mailId) ? $actionObject->actions[$andMailEditor]['acy_add_queue']['mail_id'] : $mailId);
            if (!empty($mail)) {
                $actionObject->actions[$andMailEditor]['acy_add_queue']['mail_id'] = $mail->id;
                $actionObject->actions[$andMailEditor]['acy_add_queue']['mail_name'] = empty($mail->subject) ? $mail->name : $mail->subject;
            }
        }

        if (!empty($actionObject->actions)) {
            foreach ($actionObject->actions as $and => $actions) {
                foreach ($actions as $name => $actionOption) {
                    if ('acy_add_queue' == $name && !empty($actionObject->actions[$and][$name]['mail_id'])) {
                        $mail = $mailClass->getOneById($actionObject->actions[$and][$name]['mail_id']);
                        if (!empty($mail)) {
                            $actionObject->actions[$and][$name]['mail_id'] = $mail->id;
                            $actionObject->actions[$and][$name]['mail_name'] = $mail->name;
                        } else {
                            $actionObject->actions[$and][$name]['mail_id'] = '';
                        }
                    }
                }
            }
        }

        $actionObject->actions = empty($actionObject->actions) ? '[]' : json_encode($actionObject->actions);

        $actions = [];
        acym_trigger('onAcymDeclareActions', [&$actions]);

        uasort(
            $actions,
            function ($a, $b) {
                return strcmp(strtolower($a->name), strtolower($b->name));
            }
        );

        $firstAction = new \stdClass();
        $firstAction->name = acym_translation('ACYM_CHOOSE_ACTION');
        $firstAction->option = '';
        array_unshift($actions, $firstAction);

        $actionsOption = [];

        foreach ($actions as $key => $action) {
            $actionsOption[$key] = $action->name;
        }

        $data = [
            'automation' => $automation,
            'step' => $step,
            'condition' => $condition,
            'action' => $actionObject,
            'actionsOption' => $actionsOption,
            'actions' => json_encode($actions),
            'id' => empty($id) ? '' : $id,
            'step_automation_id' => empty($step->id) ? 0 : $step->id,
            'tagClass' => $tagClass,
            'workflowHelper' => $workflowHelper,
        ];

        parent::display($data);

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    private function _saveActions($isMassAction = false)
    {
        if ($isMassAction) {
            acym_session();
        }

        $automationID = acym_getVar('int', 'id');
        $actionId = acym_getVar('int', 'actionId');
        $action = acym_getVar('array', 'acym_action');
        $actionClass = new ActionClass();
        $stepAutomationId = acym_getVar('int', 'stepAutomationId');
        $conditionId = acym_getVar('int', 'conditionId');

        if ((!empty($conditionId))) {
            $action['condition_id'] = $conditionId;
        }

        if (!empty($actionId)) {
            $action['id'] = $actionId;
        }

        if (empty($action['actions'])) {
            $action['actions'] = [];
        }

        if ($isMassAction) {
            $_SESSION['massAction']['actions'] = $action['actions'];

            return true;
        }

        $action['actions'] = json_encode($action['actions']);

        foreach ($action as $column => $value) {
            acym_secureDBColumn($column);
        }

        $action = (object)$action;

        $action->id = $actionClass->save($action);

        return [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'actionId' => $action->id,
        ];
    }

    public function saveExitActions()
    {
        $ids = $this->_saveActions();

        if (empty($ids)) {
            return;
        }

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveActions()
    {
        $ids = $this->_saveActions();

        if (empty($ids)) {
            return;
        }

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('actionId', $ids['actionId']);
        $this->filter();
    }

    public function createMail()
    {
        $id = acym_getVar('int', 'id');
        $idAdmin = acym_getVar('boolean', 'automation_admin');
        $type = MailClass::TYPE_AUTOMATION;
        if ($idAdmin) {
            $type = 'automation_admin';
        }

        $and = acym_getVar('string', 'and_action');
        $this->_saveActions(empty($id));

        $actions = acym_getVar('array', 'acym_action');
        $mailId = $actions['actions'][$and]['acy_add_queue']['mail_id'];
        $mailId = empty($mailId) ? '' : '&id='.$mailId;

        $favoriteTemplate = $this->config->get('favorite_template', 0);
        $startFrom = empty($favoriteTemplate) || !empty($mailId) ? '' : '&from='.$favoriteTemplate;

        acym_redirect(
            acym_completeLink(
                'mails&task=edit&step=editEmail&type='.$type.$mailId.'&return='.urlencode(
                    acym_completeLink('automation&task=edit&step=action&id='.$id.'&fromMailEditor=1&mailid={mailid}&and='.$and)
                ).$startFrom,
                false,
                true
            )
        );
    }
}
