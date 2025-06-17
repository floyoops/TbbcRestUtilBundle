<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/',
    ]);

    $rectorConfig->sets([
        SetList::PHP_82,
        SetList::PHP_83,
        SetList::PHP_84,
    ]);

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ReadOnlyPropertyRector::class,
        AddReturnTypeDeclarationRector::class,
        AddPropertyTypeDeclarationRector::class,
    ]);


    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Symfony\Component\Translation\TranslatorInterface' => 'Symfony\Contracts\Translation\TranslatorInterface',
        'Symfony\Component\EventDispatcher\Event' => 'Symfony\Contracts\EventDispatcher\Event',
        'Symfony\Component\EventDispatcher\EventDispatcherInterface' => 'Symfony\Contracts\EventDispatcher\EventDispatcherInterface',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/var',
    ]);

    $rectorConfig->phpVersion(80400); // PHP 8.4

    $rectorConfig->memoryLimit('2G');

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};
