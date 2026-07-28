<?php

namespace AcyMailing\Views;

use AcyMailing\Libraries\acymView;

class QueueViewQueue extends acymView
{
    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'campaigns' => 'ACYM_MAILS',
        ];

        if (acym_level(ACYM_ESSENTIAL)) {
            $this->steps['scheduled'] = 'ACYM_SCHEDULED';
        }

        $this->steps['detailed'] = 'ACYM_QUEUE_DETAILED';
    }
}
