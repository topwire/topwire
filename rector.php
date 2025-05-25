<?php
declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Ssch\TYPO3Rector\FileProcessor\TypoScript\Rector\v9\v0\FileIncludeToImportStatementTypoScriptRector;
use Ssch\TYPO3Rector\Rector\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\Rector\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        Typo3LevelSetList::UP_TO_TYPO3_12,
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
    ]);
    // Ensure file system caching is used instead of in-memory.
    $rectorConfig->cacheClass(FileCacheStorage::class);

    // Specify a path that works locally as well as on CI job runners.
    $rectorConfig->cacheDirectory('var/rector');

    // In order to have a better analysis from phpstan we teach it here some more things
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');

    // FQN classes are not imported by default. If you don't do it manually after every Rector run, enable it by:
    $rectorConfig->importNames();

    $rectorConfig->parallel();

    // this will not import root namespace classes, like \DateTime or \Exception
    $rectorConfig->importShortClasses(\false);

    // Define your target version which you want to support
    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    $rectorConfig->paths([
        __DIR__ . '/rector.php',
        __DIR__ . '/Classes/',
    ]);

    // If you use the option --config change __DIR__ to getcwd()
    $rectorConfig->skip([
        // We skip those directories on purpose as there might be node_modules or similar
        // that include typescript which would result in false positive processing
        __DIR__ . '/**/node_modules/*',
        __DIR__ . '/**/Resources/**/build/*',
        __DIR__ . '/**/vendor/*',
        __DIR__ . '/.build',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',

        AddLiteralSeparatorToNumberRector::class,
        ChangeAndIfToEarlyReturnRector::class,
        AddMethodCallBasedStrictParamTypeRector::class,
    ]);

    /**
     * Useful rule from RectorPHP itself to transform i.e. GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')
     * to GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class) calls.
     * But be warned, sometimes it produces false positives (edge cases), so watch out
     */
    $rectorConfig->rule(StringClassNameToClassConstantRector::class);

    // Optional non-php file functionalities:
    // @see https://github.com/sabbelasichon/typo3-rector/blob/main/docs/beyond_php_file_processors.md

    // Add some general TYPO3 rules
    $rectorConfig->rule(ConvertImplicitVariablesToExplicitGlobalsRector::class);
    $rectorConfig->rule(ExtEmConfRector::class);

    // Do you want to modernize your TypoScript include statements for files and move from <INCLUDE /> to @import use the FileIncludeToImportStatementVisitor
    $rectorConfig->rule(FileIncludeToImportStatementTypoScriptRector::class);
};
