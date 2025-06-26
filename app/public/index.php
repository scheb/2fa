<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return fn (array $context): \App\Kernel => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
