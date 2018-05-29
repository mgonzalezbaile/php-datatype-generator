<?php
/**
 * This file is part of prolic/fpp.
 * (c) 2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgonzalezbaile\Pdg\Parser;

use FilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class Parser
{
    public function locatePsrPath(array $prefixesPsr4, array $prefixesPsr0, Definition $definition, ?Constructor $constructor): string
    {
        if ($constructor && ! in_array(
                $constructor->name(),
                ['Bool', 'Bool[]', 'Float', 'Float[]', 'Int', 'Int[]', 'String', 'String[]'],
                true
            )) {
            $class = $constructor->name();
        } else {
            $class = $definition->namespace() . '\\' . $definition->name();
        }

        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR);

        foreach ($prefixesPsr4 as $prefix => $dirs) {
            if (0 === strpos($class, $prefix)) {
                $dir = $dirs[0];

                return $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, strlen($prefix)) . '.php';
            }
        }

        // PSR-0 lookup
        $pos = strrpos($class, '\\');
        $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
            . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);

        foreach ($prefixesPsr0 as $prefix => $dirs) {
            if (0 === strpos($class, $prefix)) {
                $dir = $dirs[0];

                return $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0 . '.php';
            }
        }

        throw new \RuntimeException('Could not find psr-autoloading path for ' . $class);
    }

    public function dump(array $collection, callable $locatePsrPath, string $generatorsPath): void
    {
        foreach ($collection as $item) {
            foreach ($item->definitions() as $definition) {
                $file = $locatePsrPath($definition, $definition->constructor());
                $dir  = dirname($file);

                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                file_put_contents($file, $definition->generateFrom($generatorsPath));
            }
        }
    }

    public function scan(string $directoryOrFile): array
    {
        if (!is_readable($directoryOrFile)) {
            throw new RuntimeException("'$directoryOrFile' is not readable");
        }

        if (is_file($directoryOrFile)) {
            return [$directoryOrFile];
        }

        $iterator = new class(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directoryOrFile))) extends FilterIterator {
            public function __construct($directoryOrFile)
            {
                parent::__construct($directoryOrFile);
            }

            public function accept()
            {
                $file = $this->getInnerIterator()->current();

                if (!$file->isFile()) {
                    return false;
                }

                if (!$file->isReadable()) {
                    return false;
                }

                return $file->getExtension() === 'datatypes';
            }
        };

        $files = [];

        foreach ($iterator as $file) {
            /* @var SplFileInfo $file */
            $files[] = $file->getPathname();
        }

        if (empty($files)) {
            throw new RuntimeException("No .datatypes files found in '$directoryOrFile'");
        }

        return $files;
    }

    public function parse(string $filename): DefinitionCollection
    {
        if (! defined('T_OTHER')) {
            define('T_OTHER', 100000);
        }

        if (!is_file($filename)) {
            throw new \RuntimeException("'$filename' is not a file");
        }

        if (!is_readable($filename)) {
            throw new \RuntimeException("'$filename' is not readable");
        }

        $namespaceFound = false;
        $contents       = file_get_contents($filename);
        $tokens         = token_get_all("<?php\n\n$contents");

        $collection = new DefinitionCollection();

        $tokenCount = count($tokens);
        $position   = 0;
        $line       = 1;
        $namespace  = '';

        $nextToken = function () use ($tokens, &$position, &$tokenCount, &$line, $filename): array {
            nextToken:

            if ($position === $tokenCount - 1) {
                throw ParseError::unexpectedEndOfFile($filename);
            }

            $token = $tokens[++$position];

            if (!is_array($token)) {
                $token = [
                    T_OTHER,
                    $token,
                    $line,
                ];
            } else {
                $token[2] = $token[2] - 1;
                $line     = $token[2];
            }

            if ($token[0] === T_COMMENT) {
                if ($position === $tokenCount - 1) {
                    ++$position;

                    return $token;
                }
                goto nextToken;
            }

            return $token;
        };

        $skipWhitespace = function (array $token) use ($tokens, &$position, $nextToken): array {
            while ($token[0] === T_WHITESPACE) {
                $token = $nextToken();
            }

            return $token;
        };

        $requireWhitespace = function (array $token) use ($filename): void {
            if ($token[0] !== T_WHITESPACE) {
                throw ParseError::unexpectedTokenFound(' ', $token, $filename);
            }
        };

        $requireString = function (array $token) use ($filename): void {
            if ($token[0] !== T_STRING) {
                throw ParseError::unexpectedTokenFound('T_STRING', $token, $filename);
            }
        };

        $requireVariable = function (array $token) use ($filename): void {
            if ($token[0] !== T_VARIABLE) {
                throw ParseError::unexpectedTokenFound('T_VARIABLE', $token, $filename);
            }
        };

        $isEndOfFile = function () use (&$position, &$tokenCount): bool {
            return $position === ($tokenCount - 1);
        };

        $nextToken();

        if ($isEndOfFile()) {
            return $collection;
        }

        $token = $nextToken();

        while ($position < $tokenCount) {
            switch ($token[0]) {
                case T_NAMESPACE:
                    if ($namespaceFound) {
                        throw ParseError::nestedNamespacesDetected($token[2], $filename);
                    }

                    $token = $nextToken();
                    $requireWhitespace($token);
                    $token = $nextToken();
                    $requireString($token);
                    $namespace = $token[1];
                    $token     = $nextToken();

                    while ($token[0] === T_NS_SEPARATOR) {
                        $token = $nextToken();
                        $requireString($token);
                        $namespace .= '\\' . $token[1];
                        $token     = $nextToken();
                    }

                    $token = $skipWhitespace($token);

                    if ($token[1] === '{') {
                        $namespaceFound = true;
                        break;
                    }

                    if ($token[1] !== ';') {
                        throw ParseError::unexpectedTokenFound(';', $token, $filename);
                    }
                    break;
                case T_STRING:
                    if ($token[1] !== 'data') {
                        throw ParseError::unknownDefinition($token, $filename);
                    }

                    // parse name (incl. message name for prooph messages)
                    $token = $nextToken();
                    $requireWhitespace($token);
                    $token = $nextToken();
                    $requireString($token);
                    $name        = $token[1];
                    $token       = $nextToken();
                    $token       = $skipWhitespace($token);
                    $messageName = null;

                    if ($token[1] !== '=') {
                        throw ParseError::unexpectedTokenFound('=', $token, $filename);
                    }

                    // parse constructors
                    $constructors = [];
                    $derivings    = [];
                    $conditions   = [];
                    parseConstructor:

                    $constructorName = '';
                    $arguments       = [];
                    $token           = $nextToken();
                    $token           = $skipWhitespace($token);

                    if ($token[0] === T_NS_SEPARATOR) {
                        $constructorName = '\\';
                        $token           = $nextToken();
                    }

                    $requireString($token);
                    $constructorName .= $token[1];
                    $token           = $nextToken();

                    while ($token[0] === T_NS_SEPARATOR) {
                        $constructorName .= $token[1];
                        $token           = $nextToken();
                        $requireString($token);
                        $constructorName .= $token[1];
                        $token           = $nextToken();
                    }

                    if (in_array($constructorName, ['Bool', 'Float', 'Int', 'String'], true)
                        && $token[1] === '['
                    ) {
                        $token = $nextToken();

                        if ($token[1] !== ']') {
                            throw ParseError::unexpectedTokenFound(']', $token, $filename);
                        }

                        $token           = $nextToken();
                        $constructorName .= '[]';
                    }

                    if ($namespace
                        && substr($constructorName, 0, 1) !== '\\'
                        && !in_array(
                            $constructorName,
                            ['Bool', 'Bool[]', 'Float', 'Float[]', 'Int', 'Int[]', 'String', 'String[]'],
                            true
                        )
                    ) {
                        $constructorName = $namespace . '\\' . $constructorName;
                    } elseif (substr($constructorName, 0, 1) === '\\') {
                        $constructorName = substr($constructorName, 1);
                    }

                    $token = $skipWhitespace($token);

                    if ($token[1] === '{') {
                        $arguments = [];
                        parseArguments:

                        while ($token[1] !== '}') {
                            $token    = $nextToken();
                            $type     = null;
                            $nullable = false;

                            $token = $skipWhitespace($token);

                            if ($token[1] === '?') {
                                $nullable = true;
                                $token    = $nextToken();
                                $token    = $skipWhitespace($token);
                                if ($token[0] !== T_STRING && $token[0] !== T_NS_SEPARATOR) {
                                    throw ParseError::unexpectedTokenFound(
                                        'T_STRING or T_NS_SEPARATOR',
                                        $token,
                                        $filename
                                    );
                                }
                            }

                            if ($token[0] === T_NS_SEPARATOR) {
                                $type  = '\\';
                                $token = $nextToken();
                            }

                            if ($token[0] === T_STRING) {
                                $type .= $token[1];

                                if (!in_array($type, ['string', 'int', 'bool', 'float'], true)) {
                                    $requireString($token);
                                }

                                $token = $nextToken();

                                $isList = false;

                                if ($token[1] === '[') {
                                    $token = $nextToken();

                                    if ($token[1] !== ']') {
                                        throw ParseError::unexpectedTokenFound(']', $token, $filename);
                                    }
                                    $token = $nextToken();
                                    $requireWhitespace($token);
                                    $isList = true;
                                }

                                while ($token[0] !== T_WHITESPACE) {
                                    if ($token[0] !== T_NS_SEPARATOR) {
                                        throw ParseError::unexpectedTokenFound(
                                            'T_WHITESPACE or T_NS_SEPARATOR',
                                            $token,
                                            $filename
                                        );
                                    }

                                    $type  .= '\\';
                                    $token = $nextToken();
                                    $requireString($token);
                                    $type  .= $token[1];
                                    $token = $nextToken();

                                    if ($token[1] === '[') {
                                        $token = $nextToken();

                                        if ($token[1] !== ']') {
                                            throw ParseError::unexpectedTokenFound(']', $token, $filename);
                                        }

                                        $token = $nextToken();
                                        $requireWhitespace($token);
                                        $isList = true;
                                    }
                                }

                                if (substr($type, 0, 1) === '\\') {
                                    $type = substr($type, 1);
                                } elseif (substr($type, 0, 1) !== '\\'
                                    && !in_array($type, ['string', 'int', 'bool', 'float'], true)
                                ) {
                                    $type = $namespace . '\\' . $type;
                                }

                                $token = $nextToken();
                                $requireVariable($token);
                                $argumentName = substr($token[1], 1);
                                $token        = $nextToken();
                                $token        = $skipWhitespace($token);

                                if (in_array($token[1], [',', '}'], true)) {
                                    $arguments[] = new Argument($argumentName, $type, $nullable, $isList);
                                    goto parseArguments;
                                }
                                throw ParseError::unexpectedTokenFound(', or }', $token, $filename);
                            } elseif ($token[0] === T_VARIABLE) {
                                $arguments[] = new Argument(substr($token[1], 1));
                                $token       = $nextToken();
                                $token       = $skipWhitespace($token);

                                if (in_array($token[1], [',', '}'], true)) {
                                    goto parseArguments;
                                }
                                throw ParseError::unexpectedTokenFound(', or }', $token, $filename);
                            }
                        }

                        $token = $nextToken();
                    }

                    if ('|' === $token[1]) {
                        $constructors[] = new Constructor($constructorName, $arguments);
                        goto parseConstructor;
                    }

                    if (';' === $token[1]) {
                        $constructors[] = new Constructor($constructorName, $arguments);
                        goto buildDefinition;
                    }

                    $token          = $skipWhitespace($token);
                    $constructors[] = new Constructor($constructorName, $arguments);

                    if ('|' === $token[1]) {
                        goto parseConstructor;
                    }

                    if (';' === $token[1]) {
                        goto buildDefinition;
                    }

                    if ('deriving' === $token[1]) {
                        $token = $nextToken();
                        $token = $skipWhitespace($token);

                        if ($token[1] !== '(') {
                            throw ParseError::unexpectedTokenFound('(', $token, $filename);
                        }

                        $token = $nextToken();

                        while ($token[1] !== ')') {
                            $token = $skipWhitespace($token);
                            $requireString($token);

                            $derivingName = $token[1];

                            $derivings[] = $token[1];
                            $token       = $nextToken();
                            $token       = $skipWhitespace($token);

                            if (in_array($derivingName, ['AggregateChanged', 'Command', 'DomainEvent', 'Query'], true)
                                && ':' === $token[1]
                            ) {
                                $token = $nextToken();
                                $token = $skipWhitespace($token);

                                if (T_CONSTANT_ENCAPSED_STRING !== $token[0]) {
                                    throw ParseError::unexpectedTokenFound(
                                        'T_CONSTANT_ENCAPSED_STRING',
                                        $token,
                                        $filename
                                    );
                                }

                                $messageName = substr($token[1], 1, -1);

                                $token = $nextToken();
                                $token = $skipWhitespace($token);
                            }

                            if ($token[1] === ',') {
                                $token = $nextToken();
                            }
                        }

                        $token = $nextToken();
                        $token = $skipWhitespace($token);

                        if (';' === $token[1]) {
                            goto buildDefinition;
                        }
                    }

                    if ('with' === $token[1]) {
                        $enumDerivingFound = false;

                        foreach ($derivings as $key => $deriving) {
                            if ($deriving->equals(new Deriving\Enum())) {
                                $enumDerivingFound = true;
                                break;
                            }
                        }

                        if (!$enumDerivingFound) {
                            throw ParseError::unexpectedTokenFound('\'where\' or \';\'', $token, $filename);
                        }

                        $valueMapping = [];
                        $token        = $nextToken();
                        $token        = $skipWhitespace($token);

                        if ($token[1] !== '(') {
                            throw ParseError::unexpectedTokenFound('(', $token, $filename);
                        }

                        $token = $nextToken();

                        while ($token[1] !== ')') {
                            $token = $skipWhitespace($token);
                            $requireString($token);
                            $enumConstructor = $token[1];
                            $token           = $nextToken();
                            $token           = $skipWhitespace($token);

                            if ($token[1] !== ':') {
                                throw ParseError::unexpectedTokenFound(':', $token, $filename);
                            }

                            $token = $nextToken();
                            $token = $skipWhitespace($token);

                            $bracesOpened = 0;
                            $code         = '';

                            while (true) {
                                if ($token[1] === '[') {
                                    ++$bracesOpened;
                                }

                                if ($token[1] === ']') {
                                    --$bracesOpened;
                                }

                                $code  .= $token[1];
                                $token = $nextToken();
                                $token = $skipWhitespace($token);

                                if (in_array($code, ['+', '-'], true)) {
                                    $code  .= $token[1];
                                    $token = $nextToken();
                                    $token = $skipWhitespace($token);
                                }

                                if (0 === $bracesOpened) {
                                    break;
                                }
                            }

                            if (!in_array($token[1], [',', ')'], true)) {
                                throw ParseError::unexpectedTokenFound(',', $token, $filename);
                            }

                            if ($token[1] !== ')') {
                                $token = $nextToken();
                                $token = $skipWhitespace($token);
                            }

                            if (in_array(substr($code, 0, 1), ['\'', '"'], true)) {
                                $code = substr($code, 1, -1);
                            } else {
                                eval('$code = ' . $code . ';');
                            }

                            $valueMapping[$enumConstructor] = $code;
                        }

                        $token = $nextToken();
                        $token = $skipWhitespace($token);
                        unset($derivings[$key]);
                        $derivings[] = new Deriving\Enum($valueMapping);
                    }

                    if ('where' === $token[1]) {
                        $conditionConstructor = '_';
                        $token                = $nextToken();
                        $token                = $skipWhitespace($token);

                        if (T_STRING === $token[0]) {
                            parseConditionsForConstructor:
                            $conditionConstructor = $token[1];

                            if ($conditionConstructor !== '_'
                                && substr($conditionConstructor, 0, 1) !== '\\'
                            ) {
                                $conditionConstructor = $namespace . '\\' . $conditionConstructor;
                            }

                            $token = $nextToken();
                            $token = $skipWhitespace($token);

                            if (':' !== $token[1]) {
                                throw ParseError::unexpectedTokenFound(':', $token, $filename);
                            }

                            $token = $nextToken();
                            $token = $skipWhitespace($token);
                        }

                        if ('|' !== $token[1]) {
                            throw ParseError::unexpectedTokenFound('|', $token, $filename);
                        }

                        $token = $nextToken();
                        $token = $skipWhitespace($token);

                        parseCondition:
                        $bracesOpened = 0;
                        $code         = '';

                        while (true) {
                            if (in_array($token[1], ['(', '['], true)) {
                                ++$bracesOpened;
                            }

                            if (in_array($token[1], [')', ']'], true)) {
                                --$bracesOpened;
                            }

                            if (0 === $bracesOpened && T_DOUBLE_ARROW === $token[0]) {
                                break;
                            }

                            $code  .= $token[1];
                            $token = $nextToken();
                        }

                        $token = $nextToken();
                        $token = $skipWhitespace($token);

                        if (T_CONSTANT_ENCAPSED_STRING !== $token[0]) {
                            throw ParseError::unexpectedTokenFound('T_CONSTANT_ENCAPSED_STRING', $token, $filename);
                        }

                        $errorMessage = $token[1];

                        $conditions[] = new Condition($conditionConstructor, trim($code), substr($errorMessage, 1, -1));

                        $token = $nextToken();
                        $token = $skipWhitespace($token);

                        if ('|' === $token[1]) {
                            $token = $nextToken();
                            $token = $skipWhitespace($token);
                            goto parseCondition;
                        }

                        if (T_STRING === $token[0]) {
                            goto parseConditionsForConstructor;
                        }
                    }

                    buildDefinition:
                    $collection->addDefinition(
                        new Definition($namespace, $name, $constructors, $derivings, $conditions, $messageName)
                    );
                    break;
                case T_WHITESPACE:
                    break;
                case T_OTHER:
                    if ($token[1] === '}' && $namespaceFound) {
                        $namespaceFound = false;
                        $namespace      = '';
                        break;
                    }
                    throw ParseError::unexpectedTokenFound('T_STRING', $token, $filename);
                    break;
                default:
                    throw ParseError::unexpectedTokenFound('T_STRING', $token, $filename);
            }

            if ($position < $tokenCount - 1) {
                $token = $nextToken();
            } else {
                ++$position;
            }
        }

        return $collection;
    }
}
