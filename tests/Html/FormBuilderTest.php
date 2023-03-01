<?php

use Winter\Storm\Html\HtmlBuilder;
use Winter\Storm\Html\FormBuilder;
use Winter\Storm\Router\UrlGenerator;

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;


class FormBuilderTest extends TestCase
{
    public function setUp() : void
    {
        $htmlBuilder = new HtmlBuilder;
        $generator = new UrlGenerator(
            new RouteCollection,
            Request::create('https://www.example.com/path/?query=arg#fragment')
        );
        $this->formBuilder = new FormBuilder($htmlBuilder, $generator);
    }

    public function testInputIdMissing()
    {
        $result = $this->formBuilder->input(type:"text", name:"my-name", value:"my value");
        $this->assertEquals('<input name="my-name" type="text" value="my value">', $result);
    }

    public function testInputIdEmpty()
    {
        $result = $this->formBuilder->input(type:"text", name:"my-name", value:"my value", options:["id"=>""]);
        $this->assertEquals('<input id="" name="my-name" type="text" value="my value">', $result);
    }

    public function testInputIdNull()
    {
        $result = $this->formBuilder->input(type:"text", name:"my-name", value:"my value", options:["id"=>null]);
        $this->assertEquals('<input name="my-name" type="text" value="my value">', $result);
    }

    public function testInputIdFalse()
    {
        $result = $this->formBuilder->input(type:"text", name:"my-name", value:"my value", options:["id"=>false]);
        $this->assertEquals('<input id="" name="my-name" type="text" value="my value">', $result);
    }

    public function testInputIdZero()
    {
        $result = $this->formBuilder->input(type:"text", name:"my-name", value:"my value", options:["id"=>0]);
        $this->assertEquals('<input id="0" name="my-name" type="text" value="my value">', $result);
    }

    public function testInputIdMissingWithAssociatedLabel()
    {
        $result = $this->formBuilder->label(name:"my-input", value:"my input label");
        $result = $this->formBuilder->input(type:"text", name:"my-input", value:"my value");
        $this->assertEquals('<input name="my-input" type="text" value="my value" id="my-input">', $result);
    }
}
