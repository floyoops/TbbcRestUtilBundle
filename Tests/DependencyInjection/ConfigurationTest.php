<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Tbbc\RestUtilBundle\DependencyInjection\Configuration;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class ConfigurationTest extends TestCase
{
    public function testExceptionErrorMappingProcessing()
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [
            [
                'error' => [
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
                        'FormException' => [
                            'class' => 'My\FormException',
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
        ]);

        $expected = [
            'InvalidArgumentException' => [
                'class' => '\RuntimeException',
                'factory' => 'default',
                'http_status_code' => 500,
                'error_code' => 500123,
                'error_message' => 'Server error',
                'error_extended_message' => 'Extended message',
                'error_more_info_url' => 'http://api.my.tld/doc/error/500123',
            ],
            'FormException' => [
                'class' => 'My\FormException',
                'factory' => 'custom',
                'http_status_code' => 400,
                'error_code' => 400110,
                'error_message' => 'Validation failed',
                'error_extended_message' => 'Extended message',
                'error_more_info_url' => 'http://api.my.tld/doc/error/400110',
            ],
        ];

        $this->assertEquals($expected, $config['error']['exception_mapping']);
    }
}
