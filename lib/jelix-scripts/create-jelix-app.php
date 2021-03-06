<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2016 Laurent Jouanneau
 * @link        http://jelix.org
 * @licence     MIT
 */

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    // when Jelix is installed via a tar.gz package
    require(__DIR__.'/../vendor/autoload.php');
}
else if (file_exists(__DIR__.'/../../../../autoload.php')) {
    // when Jelix is installed via Composer
    require(__DIR__.'/../../../../autoload.php');
}
else {
    echo "Error: the vendor directory of Composer is not found\n";
    echo "   ".__FILE__."\n";
    exit(1);
}

require(__DIR__.'/includes/scripts.inc.php');

use Jelix\DevHelper\CreateAppApplication;

$application = new CreateAppApplication();
$application->run();
