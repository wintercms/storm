<?php

use Winter\Storm\Html\HtmlBuilder;
use Winter\Storm\Html\FormBuilder;
use Winter\Storm\Router\UrlGenerator;

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Winter\Storm\Tests\Assertions\AssertHtml;

/**
 * @testdox The FormBuilder utility
 * @covers \Winter\Storm\Html\FormBuilder
 */
class FormBuilderTest extends TestCase
{
    use AssertHtml;

    /**
     * FormBuilder instance.
     */
    protected FormBuilder $formBuilder;

    public function setUp() : void
    {
        parent::setUp();

        $htmlBuilder = new HtmlBuilder;
        $generator = new UrlGenerator(
            new RouteCollection,
            Request::create('https://www.example.com/path/?query=arg#fragment')
        );
        $this->formBuilder = new FormBuilder($htmlBuilder, $generator);
    }

    /**
     * @testdox can generate a form open tag.
     */
    public function testFormOpen()
    {
        $result = $this->formBuilder->open();

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementDoesntHaveAttribute('enctype', $result);
    }

    /**
     * @testdox can generate a form open tag with method "GET".
     */
    public function testFormOpenMethodGet()
    {
        $result = $this->formBuilder->open([
            'method' => 'GET'
        ]);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'GET', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementDoesntHaveAttribute('enctype', $result);
    }

    /**
     * @testdox can generate a form open tag and accept file uploads.
     */
    public function testFormOpenFiles()
    {
        $result = $this->formBuilder->open([
            'files' => true,
        ]);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('enctype', 'multipart/form-data', $result);
    }

    /**
     * @testdox can generate a form open tag and have custom attributes.
     */
    public function testFormOpenCustomAttributes()
    {
        $result = $this->formBuilder->open([
            'data-my-attribute' => 'my-value',
            'class' => 'boss-form',
        ]);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementDoesntHaveAttribute('enctype', $result);
        $this->assertElementAttributeEquals('data-my-attribute', 'my-value', $result);
        $this->assertElementAttributeEquals('class', 'boss-form', $result);
    }

    /**
     * @testdox can generate a form open tag with a data attribute AJAX request.
     */
    public function testFormAjax()
    {
        $result = $this->formBuilder->ajax('onSave');

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('data-request', 'onSave', $result);
    }

    /**
     * @testdox can generate a form open tag with a data attribute AJAX request to a different target.
     */
    public function testFormAjaxTarget()
    {
        $result = $this->formBuilder->ajax(['myComponent', 'onSave']);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('data-request', 'myComponent::onSave', $result);
    }

    /**
     * @testdox can generate a form open tag with a data attribute AJAX request and accept files.
     */
    public function testFormAjaxFiles()
    {
        $result = $this->formBuilder->ajax('onSave', [
            'files' => true,
        ]);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('data-request', 'onSave', $result);
        $this->assertElementAttributeEquals('data-request-files', '1', $result);
        $this->assertElementAttributeEquals('enctype', 'multipart/form-data', $result);
    }

    /**
     * @testdox can generate a form close tag.
     */
    public function testClose()
    {
        $result = $this->formBuilder->close();

        $this->assertEquals('</form>', $result);
    }

    /**
     * @testdox can create a text input. The text input will not have an ID.
     */
    public function testFormInputText()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value');

        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox can create a text input with a corresponding label. The text input will have an ID.
     */
    public function testFormInputTextWithLabel()
    {
        $result = $this->formBuilder->label(name: 'my-input', value: 'my input label');
        $result = $this->formBuilder->input(type: 'text', name: 'my-input', value: 'my value');

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', 'my-input', $result);
        $this->assertElementAttributeEquals('name', 'my-input', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox accepts an empty ID and sets the ID attribute to empty.
     */
    public function testFormInputTextIdEmpty()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => '']);

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', '', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox ignores an ID that is "null".
     */
    public function testFormInputTextNull()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => null]);

        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox ignores an ID that is boolean "false".
     */
    public function testFormInputTextFalse()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => false]);

        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox accepts an ID that is an integer of zero.
     */
    public function testFormInputTextZero()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => 0]);

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', '0', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox can create a text input of type "email".
     */
    public function testFormInputEmail()
    {
        $result = $this->formBuilder->input(type: 'email', name: 'my-input', value: 'my value');

        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-input', $result);
        $this->assertElementAttributeEquals('type', 'email', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);

        $result = $this->formBuilder->label(name: 'my-input', value: 'my input label');
        $result = $this->formBuilder->email(name: 'my-input', value: 'my value');

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', 'my-input', $result);
        $this->assertElementAttributeEquals('name', 'my-input', $result);
        $this->assertElementAttributeEquals('type', 'email', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    /**
     * @testdox can create a submit button.
     * @see https://github.com/wintercms/winter/issues/864
     */
    public function testSubmit()
    {
        $result = $this->formBuilder->submit(value: 'Apply');

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('type', 'submit', $result);
        $this->assertElementAttributeEquals('value', 'Apply', $result);
    }

    /**
     * @testdox can create a submit button with additional classes.
     * @see https://github.com/wintercms/winter/issues/864
     */
    public function testSubmitWithClasses()
    {
        $result = $this->formBuilder->submit(value: 'Apply', options: ['class' => 'btn btn-primary']);

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('type', 'submit', $result);
        $this->assertElementAttributeEquals('class', 'btn btn-primary', $result);
        $this->assertElementAttributeEquals('value', 'Apply', $result);
    }

    /**
     * @testdox can create a standard button.
     * @see https://github.com/wintercms/winter/issues/864
     */
    public function testButton()
    {
        $result = $this->formBuilder->button(value: 'Apply');

        $this->assertElementIs('button', $result);
        $this->assertElementAttributeEquals('type', 'button', $result);
        $this->assertElementContainsText('Apply', $result);
    }

    /**
     * @testdox can create a standard button that submits the form.
     * @see https://github.com/wintercms/winter/issues/864
     */
    public function testButtonSubmitType()
    {
        $result = $this->formBuilder->button(value: 'Apply', options: [
            'type' => 'submit',
        ]);

        $this->assertElementIs('button', $result);
        $this->assertElementAttributeEquals('type', 'submit', $result);
        $this->assertElementContainsText('Apply', $result);
    }
}
