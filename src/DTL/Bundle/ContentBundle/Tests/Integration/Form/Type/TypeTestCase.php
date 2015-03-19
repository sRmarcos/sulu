<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Tests\Integration\Form\Type;

use DTL\Bundle\ContentBundle\Tests\Integration\BaseTestCase;
use Symfony\Component\Form\FormInterface;
use DTL\Component\Content\FrontView\FrontView;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use Symfony\Component\OptionsResolver\OptionsResolver;
use DTL\Component\Content\Form\Exception\InvalidFormException;

/**
 * Abstract test class for all content types
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class TypeTestCase extends BaseTestCase
{
    /**
     * Provide the alias for the property which will be used
     * to retrieve the PropertyTypeInterface instance from the
     * registry.
     *
     * @return string
     */
    abstract protected function getPropertyAlias();

    /**
     * Provide data for testFormSubmit
     */
    public function provideFormSubmit()
    {
        return array(
            array(
                array(),
                'hello',
                'hello',
            ),
        );
    }

    /**
     * Test form submission in mapping
     *
     * @param array $options Options for the form
     * @param mixed $data The data to submit to the form
     * @param mixed $expectedData The data that the form should map to the data
     *
     * @dataProvider provideFormSubmit
     */
    public function testFormSubmit($options, $data, $expectedData)
    {
        $data = $this->prepareFormSubmitData($data);
        $content = new \ArrayObject();
        $form = $this->createForm($options);
        $form->setData($content);

        $form->submit(array(
            'test_type' => $data,
        ));

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->assertTrue($form->isValid());
        $this->assertFormSubmitData($expectedData, $content['test_type']);
    }

    protected function prepareFormSubmitData($data)
    {
        return $data;
    }

    protected function assertFormSubmitData($expectedData, $content)
    {
        $this->assertEquals($expectedData, $content);
    }

    /**
     * Create form with a single field "test_type" using this
     * tests content type.
     *
     * @param array $options Options for the content form
     *
     * @return FormInterface
     */
    private function createForm($options)
    {
        $form = $this->getContainer()->get('form.factory')->createBuilder()
            ->add('test_type', $this->getProperty(), $options)
            ->getForm();

        return $form;
    }

    protected function getProperty()
    {
        return $this->getContainer()->get('dtl_content.property.registry')->getProperty($this->getPropertyAlias());
    }
}
