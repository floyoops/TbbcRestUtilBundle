<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\DependencyInjection\Compiler;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbbc\RestUtil\Error\DefaultErrorFactory;
use Tbbc\RestUtil\Error\Error;
use Tbbc\RestUtil\Error\ErrorFactoryInterface;
use Tbbc\RestUtil\Error\ErrorInterface;
use Tbbc\RestUtil\Error\ErrorResolver;
use Tbbc\RestUtil\Error\Mapping\ExceptionMap;
use Tbbc\RestUtil\Error\Mapping\ExceptionMapping;
use Tbbc\RestUtil\Error\Mapping\ExceptionMappingInterface;
use Tbbc\RestUtilBundle\DependencyInjection\TbbcRestUtilExtension;
use Tbbc\RestUtilBundle\TbbcRestUtilBundle;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class ErrorFactoryCompilerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var TbbcRestUtilBundle
     */
    private $extension;

    public function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new TbbcRestUtilExtension();

        $this->container->registerExtension($this->extension);

        $bundle = new TbbcRestUtilBundle();
        $bundle->build($this->container);
    }

    public function tearDown(): void
    {
        unset($this->container, $this->extension);
    }

    public function testErrorResolverWithCustomErrorFactoriesIsConstructedCorrectly()
    {
        $config = $this->getConfig();
        $this->extension->load($config, $this->container);

        // add custom factory definition to the container
        $customErrorFactoryDefinition = new Definition(
            '\Tbbc\RestUtilBundle\Tests\DependencyInjection\Compiler\CustomErrorFactory'
        );
        $customErrorFactoryDefinition->addTag('tbbc_rest_util.error_factory');
        $this->container->addDefinitions([$customErrorFactoryDefinition]);

        $this->container->compile();

        // Manual construction of expected ErrorResolver
        $exceptionMap = $this->getExceptionMap();
        $expectedErrorResolver = new ErrorResolver($exceptionMap);
        $expectedErrorResolver->registerFactory(new DefaultErrorFactory());
        $expectedErrorResolver->registerFactory(new CustomErrorFactory());

        $this->assertEquals($expectedErrorResolver, $this->container->get('tbbc_rest_util.error.error_resolver'));
    }

    /**
     * Returns ExceptionMap corresponding to the getConfig() result
     *
     * @return ExceptionMap
     */
    private function getExceptionMap()
    {
        $exceptionMap = new ExceptionMap();
        $exceptionMap
            ->add(new ExceptionMapping([
                'exceptionClassName' => '\RuntimeException',
                'factory' => '__DEFAULT__',
                'httpStatusCode' => 500,
                'errorCode' => 500123,
                'errorMessage' => 'Server error',
                'errorExtendedMessage' => 'Extended message',
                'errorMoreInfoUrl' => 'http://api.my.tld/doc/error/500123',
            ]))
        ;

        $exceptionMap->add(new ExceptionMapping([
                'exceptionClassName' => 'My\CustomException',
                'factory' => 'custom',
                'httpStatusCode' => 400,
                'errorCode' => 400110,
                'errorMessage' => 'Validation failed',
                'errorExtendedMessage' => 'Extended message',
                'errorMoreInfoUrl' => 'http://api.my.tld/doc/error/400110',
            ]))
        ;

        return $exceptionMap;
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return [
            "tbbc_rest_util" => [
                "error" => [
                    'use_bundled_factories' => false,
                    'exception_mapping' => [
                        'InvalidArgumentException' => [
                            'class' => '\RuntimeException',
                            'factory' => 'default',
                            'http_status_code' => 500,
                            'error_code' => 500123,
                            'error_message' => 'Server error',
                            'error_extended_message' => 'Extended message',
                            'error_more_info_url' => 'http://api.my.tld/doc/error/500123',
                        ],
                        'CustomException' => [
                            'class' => 'My\CustomException',
                            'factory' => 'custom',
                            'http_status_code' => 400,
                            'error_code' => 400110,
                            'error_message' => 'Validation failed',
                            'error_extended_message' => 'Extended message',
                            'error_more_info_url' => 'http://api.my.tld/doc/error/400110',
                        ],
                    ],
                ],
            ],
        ];
    }
}

class CustomErrorFactory implements ErrorFactoryInterface
{
    public function getIdentifier(): string
    {
        return 'custom';
    }

    public function createError(Exception $exception, ExceptionMappingInterface $mapping): ?ErrorInterface
    {
        return new Error($mapping->getHttpStatusCode(), $mapping->getErrorCode(), $mapping->getErrorMessage());
    }
}
