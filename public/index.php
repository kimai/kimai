<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// TODO remove once PARTIAL usage was replaced entirely
\Doctrine\Deprecations\Deprecation::ignoreDeprecations('https://github.com/doctrine/orm/issues/8471');

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
