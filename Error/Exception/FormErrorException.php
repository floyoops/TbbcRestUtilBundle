<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Error\Exception;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * @author Boris Guéry <guery.b@gmail.com>
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 * @author Valentin Ferriere <valentin@v-labs.fr>
 */
class FormErrorException extends InvalidArgumentException
{
    private $formErrors;

    public function __construct(
        FormInterface $form,
        private $translator = null,
        $message = 'An error has occurred while processing your request, make sure your data are valid',
        $code = 400,
        ?Exception $previous = null
    ) {
        $this->buildErrorsTree($form);

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getFormErrors(): array
    {
        return $this->formErrors;
    }

    /**
     * @param FormInterface $form
     */
    private function buildErrorsTree(FormInterface $form): void
    {
        $this->formErrors = [];

        $this->formErrors['form_errors'] = [];
        foreach ($form->getErrors() as $error) {
            /** @var $error FormError */
            $message = $error->getMessage();
            if ($this->translator) {
                /** @Ignore */
                $message = $this->translator->trans($message, $error->getMessageParameters(), 'validators');
            }
            array_push($this->formErrors['form_errors'], $message);
        }

        $this->formErrors['field_errors'] = [];
        $this->buildFormFieldErrorTree($form);
    }

    /**
     * @param FormInterface $form
     */
    private function buildFormFieldErrorTree(FormInterface $form, $name = null): void
    {

        foreach ($form->all() as $key => $child) {
            $children = count($child->all());

            if ($children > 0 && ! is_int($key)) {
                $name = $key;
            }

            /** @var $error FormError */
            foreach ($child->getErrors() as $error) {
                $message = $error->getMessage();
                if ($this->translator) {
                    /** @Ignore */
                    $message = $this->translator->trans($message, $error->getMessageParameters(), 'validators');
                }

                if ($name == null) {
                    $this->formErrors['field_errors'][$key][] = $message;
                } else {
                    $this->formErrors['field_errors'][sprintf('%s-%s-%s', $name, $form->getName(), $key)][] = $message;
                }
            }

            if ($children > 0) {
                $this->buildFormFieldErrorTree($child, $name);
            }
        }
    }
}
