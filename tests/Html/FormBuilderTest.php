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

    public function testFormInputBooleanAttribute()
    {
        $result = $this->formBuilder->input(type: 'text', name: 'my-name', value: 'my value', options: ['required']);

        $this->assertElementIs('input', $result);
        $this->assertElementAttributeEquals('name', 'my-name', $result);
        $this->assertElementAttributeEquals('type', 'text', $result);
        $this->assertElementHasAttribute('required', $result);
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

    /**
     * @testdox can create a select element with an empty option without emptyOption appearing as an attribute.
     */
    public function testSelectWithEmptyOption()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: ['1' => 'Option 1', '2' => 'Option 2'],
            selected: null,
            options: ['emptyOption' => 'Please select', 'class' => 'form-control']
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);
        $this->assertElementAttributeEquals('class', 'form-control', $result);
        $this->assertElementDoesntHaveAttribute('emptyOption', $result);
        $this->assertStringContainsString('<option value="">Please select</option>', $result);
        $this->assertStringContainsString('<option value="1">Option 1</option>', $result);
        $this->assertStringContainsString('<option value="2">Option 2</option>', $result);
    }

    /**
     * @testdox can create a select element with icon data attributes.
     */
    public function testSelectWithIcon()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                '1' => 'Regular Option',
                '2' => ['Option With Icon', 'icon-refresh'],
            ],
            selected: null,
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);
        $this->assertStringContainsString('<option value="1">Regular Option</option>', $result);
        $this->assertStringContainsString('<option value="2" data-icon="icon-refresh">Option With Icon</option>', $result);
    }

    /**
     * @testdox can create a select element with image data attributes.
     */
    public function testSelectWithImage()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                '1' => 'Regular Option',
                '2' => ['Option With Image', 'myImage.jpeg'],
            ],
            selected: null,
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);
        $this->assertStringContainsString('<option value="1">Regular Option</option>', $result);
        $this->assertStringContainsString('<option value="2" data-image="myImage.jpeg">Option With Image</option>', $result);
    }

    /**
     * @testdox can create a select element with image data attributes.
     */
    public function testSelectWithSelectedImage()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                '1' => 'Regular Option',
                '2' => ['Option With Image', 'myImage.jpeg'],
            ],
            selected: '2',
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);
        $this->assertStringContainsString('<option value="1">Regular Option</option>', $result);
        $this->assertStringContainsString('<option value="2" selected="selected" data-image="myImage.jpeg">Option With Image</option>', $result);
    }

    /**
     * @testdox can create a select element with optgroups.
     */
    public function testSelectWithOptgroups()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                'Group 1' => [
                    'g1-opt1' => 'Group 1 Option 1',
                    'g1-opt2' => 'Group 1 Option 2',
                ],
                'Group 2' => [
                    'g2-opt1' => 'Group 2 Option 1',
                    'g2-opt2' => 'Group 2 Option 2',
                ],
            ],
            selected: null,
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);
        $this->assertStringContainsString('<optgroup label="Group 1">', $result);
        $this->assertStringContainsString('<optgroup label="Group 2">', $result);
        $this->assertStringContainsString('<option value="g1-opt1">Group 1 Option 1</option>', $result);
        $this->assertStringContainsString('<option value="g1-opt2">Group 1 Option 2</option>', $result);
        $this->assertStringContainsString('<option value="g2-opt1">Group 2 Option 1</option>', $result);
        $this->assertStringContainsString('<option value="g2-opt2">Group 2 Option 2</option>', $result);
        $this->assertStringContainsString('</optgroup>', $result);
    }

    /**
     * @testdox can create a select element with optgroups containing icons and images.
     */
    public function testSelectWithOptgroupsAndIconsImages()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                'option1' => 'Regular option',
                'option2' => ['Option With Image', 'myImage.jpeg'],
                'Group1' => [
                    'group1-opt1' => 'OptGroup Option1 regular option',
                    'group1-opt2' => ['OptGroup Option2 with icon', 'icon-refresh'],
                    'group1-opt3' => ['OptGroup Option3 with image', 'otherImage.png'],
                ],
                'Group2' => [
                    'group2-opt1' => 'OptGroup2 Option1',
                    'group2-opt2' => 'OptGroup2 Option2',
                ],
            ],
            selected: null,
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);

        // Regular options
        $this->assertStringContainsString('<option value="option1">Regular option</option>', $result);
        $this->assertStringContainsString('<option value="option2" data-image="myImage.jpeg">Option With Image</option>', $result);

        // Optgroups
        $this->assertStringContainsString('<optgroup label="Group1">', $result);
        $this->assertStringContainsString('<optgroup label="Group2">', $result);

        // Options inside optgroups
        $this->assertStringContainsString('<option value="group1-opt1">OptGroup Option1 regular option</option>', $result);
        $this->assertStringContainsString('<option value="group1-opt2" data-icon="icon-refresh">OptGroup Option2 with icon</option>', $result);
        $this->assertStringContainsString('<option value="group1-opt3" data-image="otherImage.png">OptGroup Option3 with image</option>', $result);
        $this->assertStringContainsString('<option value="group2-opt1">OptGroup2 Option1</option>', $result);
        $this->assertStringContainsString('<option value="group2-opt2">OptGroup2 Option2</option>', $result);
    }

    /**
     * @testdox can create a select element with backward compatibility for simple string options.
     */
    public function testSelectBackwardCompatibility()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                '1' => 'Option 1',
                '2' => 'Option 2',
                '3' => 'Option 3',
            ],
            selected: '2',
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);
        $this->assertStringContainsString('<option value="1">Option 1</option>', $result);
        $this->assertStringContainsString('<option value="2" selected="selected">Option 2</option>', $result);
        $this->assertStringContainsString('<option value="3">Option 3</option>', $result);
        $this->assertStringNotContainsString('data-icon', $result);
        $this->assertStringNotContainsString('data-image', $result);
    }

    /**
     * @testdox can create a select element with backward compatibility for optgroup integer keys
     */
    public function testSelectBackwardCompatibilityOptgroupIdItemsKeys()
    {
        // this simulates grouped options base on a model with ids as keys
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                'Group1' => [
                    1 => 'Option 1',
                    2 => 'Option 2',
                ],
                'Group2' => [
                    3 => 'Option 3',
                    4 => 'Option 4',
                ],
            ],
            selected: 2,
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);

        // Optgroups
        $this->assertStringContainsString('<optgroup label="Group1">', $result);
        $this->assertStringContainsString('<optgroup label="Group2">', $result);

        // Options inside optgroups
        $this->assertStringContainsString('<option value="1">Option 1</option>', $result);
        $this->assertStringContainsString('<option value="2" selected="selected">Option 2</option>', $result);
        $this->assertStringContainsString('<option value="3">Option 3</option>', $result);
        $this->assertStringContainsString('<option value="4">Option 4</option>', $result);
        $this->assertStringNotContainsString('data-icon', $result);
        $this->assertStringNotContainsString('data-image', $result);
    }

    /**
     * @testdox show case where backward compatibility is broken (expected)
     */
    public function testSelectBackwardCompatibilityBrokenOptGroup()
    {
        // optgroup syntax with two items with integer keys starting at zero are seen as a regular option with an icon
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                'Group1' => [
                    0 => 'Option 1',
                    1 => 'Option 2',
                ],
            ],
            options: []
        );

        $this->assertElementIs('select', $result);
        $this->assertElementAttributeEquals('name', 'my-select', $result);

        // Options inside optgroups
        $this->assertStringContainsString('<option value="Group1" data-icon="Option 2">Option 1</option>', $result);
        $this->assertStringContainsString('data-icon', $result);
    }

    /**
     * @testdox properly escapes HTML in option labels and values.
     */
    public function testSelectHtmlEscaping()
    {
        $result = $this->formBuilder->select(
            name: 'my-select',
            list: [
                '<script>' => 'Normal Label',
                'safe-value' => ['<b>Bold Label</b>', 'icon-test'],
            ],
            selected: null,
            options: []
        );

        $this->assertElementIs('select', $result);

        $this->assertStringContainsString('value="&lt;script&gt;"', $result);
        $this->assertStringContainsString('&lt;b&gt;Bold Label&lt;/b&gt;', $result);

        // Ensure dangerous tags are not rendered as raw HTML
        $this->assertStringNotContainsString('value="<script>"', $result);
        $this->assertStringNotContainsString('<b>Bold Label</b>', $result);
    }
}
