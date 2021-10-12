<?php

declare (strict_types=1);
namespace NotionCommotion\AttributeValidatorCommand;

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();
print_r(get_class_methods($application);

// ... register commands

$application->run();
