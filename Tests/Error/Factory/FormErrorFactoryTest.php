<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\Error\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tbbc\RestUtil\Error\Error;
use Tbbc\RestUtil\Error\ErrorResolver;
use Tbbc\RestUtil\Error\Mapping\ExceptionMap;
use Tbbc\RestUtil\Error\Mapping\ExceptionMapping;
use Tbbc\RestUtil\Error\Mapping\ExceptionMappingInterface;
use Tbbc\RestUtilBundle\Error\Exception\FormErrorException;
use Tbbc\RestUtilBundle\Error\Factory\FormErrorFactory;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class FormErrorFactoryTest extends TestCase
{
    /**
     * @var ErrorResolver
     */
    private $errorResolver;

    /**
     * @var ExceptionMappingInterface
     */
    private $formErrorExceptionMapping;

    /**
     * @var FormInterface
     */
    private $form;

    public function setUp(): void
    {
        $this->formErrorExceptionMapping = new ExceptionMapping([
            'exceptionClassName' => '\Tbbc\RestUtilBundle\Error\Exception\FormErrorException',
            'factory' => 'tbbc_rest_util_form_error',
            'httpStatusCode' => 400,
            'errorCode' => 400101,
            'errorMessage' => 'An error has occurred while processing your request, make sure your data are valid',
            'errorExtendedMessage' => 'Extended message',
            'errorMoreInfoUrl' => 'http://api.my.tld/doc/error/400101',
        ]);

        $exceptionMap = new ExceptionMap();
        $exceptionMap->add($this->formErrorExceptionMapping);
        $this->errorResolver = new ErrorResolver($exceptionMap);

        $this->form = $this->createForm();
    }

    public function tearDown(): void
    {
        unset($this->errorResolver, $this->form, $this->formErrorExceptionMapping);
    }

    public function testFormErrorFactoryCreateErrorReturnNullIfExceptionNotSupported()
    {
        $formErrorFactory = new FormErrorFactory();
        $exception = new InvalidArgumentException();

        $this->assertNull($formErrorFactory->createError($exception, $this->formErrorExceptionMapping));
    }

    public function testFormErrorFactoryCreateErrorReturnsValidErrorObject()
    {
        $formErrorFactory = new FormErrorFactory();
        $formErrorException = new FormErrorException($this->form);

        $expectedError = new Error(
            400,
            400101,
            'An error has occurred while processing your request, make sure your data are valid',
            [
                'global_errors' => ['Error!'],
                'property_errors' => [
                    'foo' => [
                        'Foo should not be blank',
                    ],
                    'bar' => [
                        'This value is not a valid Bar',
                    ],
                ],
            ],
            'http://api.my.tld/doc/error/400101'
        );

        $actualError = $formErrorFactory->createError($formErrorException, $this->formErrorExceptionMapping);

        $this->assertEquals($expectedError, $actualError);
    }

    /**
     * @return FormInterface
     */
    protected function createForm()
    {
        $eventDispatcher = new EventDispatcher();

        $mapper = $this->getMockBuilder(DataMapperInterface::class)->getMock();
        $form = $this->getBuilder('name', $eventDispatcher)
            ->setCompound(true)
            ->setDataMapper($mapper)
            ->getForm();

        $form->add($this->getBuilder('foo', $eventDispatcher)->setCompound(false)->getForm());
        $form->add($this->getBuilder('bar', $eventDispatcher)->setCompound(false)->getForm());

        $form->addError(new FormError('Error!'));
        $form->get('foo')->addError(new FormError('Foo should not be blank'));
        $form->get('bar')->addError(new FormError('This value is not a valid Bar'));

        return $form;
    }

    /**
     * @param string $name
     * @param EventDispatcherInterface|null $dispatcher
     * @param string|null $dataClass
     *
     * @return FormBuilder
     */
    protected function getBuilder(string $name = 'name', ?EventDispatcherInterface $dispatcher = null, ?string $dataClass = null)
    {
        $factory = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        return new FormBuilder($name, $dataClass, $dispatcher, $factory);
    }
}
