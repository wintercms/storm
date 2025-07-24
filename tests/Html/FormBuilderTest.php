<?php

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use PHPUnit\Framework\Attributes\TestDox;
use Winter\Storm\Html\FormBuilder;
use Winter\Storm\Html\HtmlBuilder;
use Winter\Storm\Router\UrlGenerator;
use Winter\Storm\Tests\Assertions\AssertHtml;
use Winter\Storm\Tests\TestCase;

/**
 * @covers \Winter\Storm\Html\FormBuilder
 */
#[TestDox('The FormBuilder utility')]
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

    public function test_it_can_generate_a_form_open_tag()
    {
        $result = $this->formBuilder->open();

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementDoesntHaveAttribute('enctype', $result);
    }

    public function test_it_can_generate_a_form_open_tag_with_method_GET()
    {
        $result = $this->formBuilder->open([
            'method' => 'GET'
        ]);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'GET', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementDoesntHaveAttribute('enctype', $result);
    }

    public function test_it_can_generate_a_form_open_tag_and_accept_file_uploads()
    {
        $result = $this->formBuilder->open([
            'files' => true,
        ]);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('enctype', 'multipart/form-data', $result);
    }

    public function test_it_can_generate_a_form_open_tag_and_have_custom_attributes()
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

    public function test_it_can_generate_a_form_open_tag_with_a_data_attribute_AJAX_request()
    {
        $result = $this->formBuilder->ajax('onSave');

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('data-request', 'onSave', $result);
    }

    public function test_it_can_generate_a_form_open_tag_with_a_data_attribute_ajax_request_to_a_different_target()
    {
        $result = $this->formBuilder->ajax(['myComponent', 'onSave']);

        $this->assertElementIs('form', $result);
        $this->assertElementAttributeEquals('method', 'POST', $result);
        $this->assertElementAttributeEquals('action', 'https://www.example.com/path', $result);
        $this->assertElementAttributeEquals('data-request', 'myComponent::onSave', $result);
    }

    public function test_it_can_generate_a_form_open_tag_with_a_data_attribute_ajax_request_and_accept_files()
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

    public function test_it_can_generate_a_form_close_tag()
    {
        $result = $this->formBuilder->close();
        $this->assertEquals('</form>', $result);
    }

    public function test_it_can_create_a_text_input()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value');
        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    public function test_it_can_create_a_text_input_with_a_corresponding_label()
    {
        $result = $this->formBuilder->label(name: 'my-input', value: 'my input label');
        $result = $this->formBuilder->input(type: 'text', name: 'my-input', value: 'my value');

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', 'my-input', $result);
        $this->assertElementAttributeEquals('name', 'my-input', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    public function test_it_accepts_an_empty_id_and_sets_the_id_attribute_to_empty()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => '']);
        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', '', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    public function test_it_ignores_an_id_that_is_null()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => null]);
        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    public function test_it_ignores_an_id_that_is_boolean_false()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => false]);
        $this->assertElementIs('input', $result);
        $this->assertElementDoesntHaveAttribute('id', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    public function test_it_accepts_an_id_that_is_an_integer_of_zero()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['id' => 0]);
        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('id', '0', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementAttributeEquals('value', 'my value', $result);
    }

    public function test_it_can_create_a_required_input()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['required']);

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementHasAttribute('required', $result);
    }

    public function test_it_can_create_a_text_input_of_type_email()
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

    /** @see https://github.com/wintercms/winter/issues/864 */
    public function test_it_can_create_a_submit_button()
    {
        $result = $this->formBuilder->submit(value: 'Apply');
        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('type', 'submit', $result);
        $this->assertElementAttributeEquals('value', 'Apply', $result);
    }

    /** @see https://github.com/wintercms/winter/issues/864 */
    public function test_it_can_create_a_submit_button_with_additional_classes()
    {
        $result = $this->formBuilder->submit(value: 'Apply', options: ['class' => 'btn btn-primary']);
        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('type', 'submit', $result);
        $this->assertElementAttributeEquals('class', 'btn btn-primary', $result);
        $this->assertElementAttributeEquals('value', 'Apply', $result);
    }

    /** @see https://github.com/wintercms/winter/issues/864 */
    public function test_it_can_create_a_standard_button()
    {
        $result = $this->formBuilder->button(value: 'Apply');
        $this->assertElementIs('button', $result);
        $this->assertElementAttributeEquals('type', 'button', $result);
        $this->assertElementContainsText('Apply', $result);
    }

    /** @see https://github.com/wintercms/winter/issues/864 */
    public function test_it_can_create_a_standard_button_that_submits_the_form()
    {
        $result = $this->formBuilder->button(value: 'Apply', options: ['type' => 'submit']);
        $this->assertElementIs('button', $result);
        $this->assertElementAttributeEquals('type', 'submit', $result);
        $this->assertElementContainsText('Apply', $result);
    }
}
