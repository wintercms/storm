<?php

class ValidationTest extends TestCase
{
    use \Winter\Storm\Database\Traits\Validation;

    public $exists;

    public $id = 20;

    public function testUnique()
    {
        /*
         * Basic usage of unique rule
         */
        $rules = ['email' => 'unique:users'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:mysql.users,email,7,the_id']
        ], $this->processValidationRules($rules));

        $this->exists = false;

        $this->assertEquals([
            'email' => ['unique:mysql.users,email,NULL,the_id']
        ], $this->processValidationRules($rules));

        /*
         * Specifying a custom column name
         */
        $rules = ['email' => 'unique:users,email_address'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:mysql.users,email_address,7,the_id']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:mysql.users,email_address,NULL,the_id']
        ], $this->processValidationRules($rules));

        /*
         * Adding additional where clauses
         */
        $rules = ['email' => 'unique:users,email_address,NULL,id,account_id,1'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:mysql.users,email_address,20,id,account_id,1']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:mysql.users,email_address,20,id,account_id,1']
        ], $this->processValidationRules($rules));
    }

    protected function getConnectionName()
    {
        return 'mysql';
    }

    protected function getTable()
    {
        return 'users';
    }

    protected function getKey()
    {
        return $this->exists ? 7 : null;
    }

    protected function getKeyName()
    {
        return 'the_id';
    }

    public function testArrayFieldNames()
    {
        $mock = $this->getMockForTrait('Winter\Storm\Database\Traits\Validation');

        $rules = [
            'field' => 'required',
            'field.two' => 'required|boolean',
            'field[three]' => 'required|date',
            'field[three][child]' => 'required',
            'field[four][][name]' => 'required',
            'field[five' => 'required|string',
            'field][six' => 'required|string',
            'field]seven' => 'required|string',
        ];
        $rules = self::callProtectedMethod($mock, 'processRuleFieldNames', [$rules]);

        $this->assertEquals([
            'field' => 'required',
            'field.two' => 'required|boolean',
            'field.three' => 'required|date',
            'field.three.child' => 'required',
            'field.four.*.name' => 'required',
            'field[five' => 'required|string',
            'field][six' => 'required|string',
            'field]seven' => 'required|string',
        ], $rules);
    }
}
