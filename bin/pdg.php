<?php
/**
 * This file is part of prolic/fpp.
 * (c) 2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Mgonzalezbaile;

use Mgonzalezbaile\Pdg\Parser\Constructor;
use Mgonzalezbaile\Pdg\Parser\Definition;
use Mgonzalezbaile\Pdg\Parser\ParseError;
use Mgonzalezbaile\Pdg\Parser\Parser;

if (!isset($argv[1])) {
    echo 'Missing input directory or file argument';
    exit(1);
}

if (!isset($argv[2])) {
    echo 'Missing generators namespace';
    exit(1);
}

$path = $argv[1];
$generatorsPath = $argv[2];

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        $autoloader = require_once $autoloadFile;
        break;
    }
}

$prefixesPsr4 = $autoloader->getPrefixesPsr4();
$prefixesPsr0 = $autoloader->getPrefixes();

$parser = new Parser();

$locatePsrPath = function (Definition $definition, ?Constructor $constructor) use (
    $parser,
    $prefixesPsr4,
    $prefixesPsr0
): string {
    return $parser->locatePsrPath($prefixesPsr4, $prefixesPsr0, $definition, $constructor);
};

try {
    $parsedClasses = [];
    foreach ($parser->scan($path) as $file) {
        $parsedClasses[] = $parser->parse($file);
    }
} catch (ParseError $e) {
    echo 'Parse Error: ' . $e->getMessage();
    exit(1);
}

try {
    $parser->dump($parsedClasses, $locatePsrPath, $generatorsPath);
    //TODO: Call php cs fixer on files created
    //TODO: Call php stan on files created and check output
} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage();
    exit(1);
}

echo "Successfully generated and written to disk\n";
exit(0);
