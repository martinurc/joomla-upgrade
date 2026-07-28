<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Helpers\CronHelper;
use AcyMailing\Libraries\acymController;

class CronController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        acym_setNoTemplate();
        $this->setDefaultTask('cron');

        $this->publicFrontTasks = [
            'cron',
        ];
    }

    public function isSecureCronUrl(): bool
    {
        $cronKey = acym_getVar('string', 'cronKey', '');

        return $cronKey === $this->config->get('cron_key', '');
    }

    public function cron()
    {
        if (!empty($this->config->get('cron_security', 0)) && !$this->isSecureCronUrl()) {
            die(acym_translation('ACYM_SECURITY_KEY_CRON_MISSING'));
        }


        if (!acym_level(ACYM_ESSENTIAL)) exit;

        acym_header('Content-type:text/html; charset=utf-8');
        if (strlen(ACYM_LIVE) < 10) {
            die(acym_translationSprintf('ACYM_CRON_WRONG_DOMAIN', ACYM_LIVE));
        }


        if (!acym_isLicenseValidWeekly() && (empty($_SERVER['HTTP_REFERER']) || (strpos($_SERVER['HTTP_REFERER'], 'www.yourcrontask.com') === false && strpos(
                    $_SERVER['HTTP_REFERER'],
                    'api.acymailing.com'
                ) === false))) {
            exit;
        }


        echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title>'.acym_translation('ACYM_CRON').'</title></head><body>';
        $cronHelper = new CronHelper();
        $cronHelper->report = true;
        $cronHelper->addSkipFromString(acym_getVar('string', 'skip'));
        $emailtypes = acym_getVar('string', 'emailtypes');
        if (!empty($emailtypes)) {
            $cronHelper->emailtypes = explode(',', $emailtypes);
        }
        $cronHelper->cron();
        $cronHelper->report();
        echo '</body></html>';

        exit;
    }
}
