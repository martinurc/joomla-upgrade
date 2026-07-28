<?php

if (class_exists('WpOrg\Requests\Autoload') === false) {
	require_once dirname(__DIR__) . '/src/Autoload.php';
}

WpOrg\Requests\Autoload::register();
