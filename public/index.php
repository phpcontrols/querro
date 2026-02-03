<?php
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
include_once(dirname(__DIR__).'/config/config.php');
include_once(dirname(__DIR__).'/includes/functions.php');

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
