<?php

use Illuminate\Http\Request;
use Winter\Storm\Support\Facades\Input;

class ValidationTest extends TestCase
{
    use \Winter\Storm\Database\Traits\Validation;

    public $exists;

    public $primaryKey = 'primaryKeyValue';

    public $customColumn = 'customColumnValue';

    public function getKeyName()
    {
        return 'primaryKey';
    }

    protected function getConnectionName()
    {
        return 'mysql';
    }

    protected function getTable($table = null)
    {
        return 'users';
    }

    public function testUnique()
    {
        // Fake a request so flash messages are not sent
        Input::swap(new Request());

        /**
         * The current model should be excluded when it exists, otherwise all models are evaluated
         *
         * Possible values for unique:
         *
         * - "unique"
         * - "unique:table_name
         * - "unique:?connection?.table_name"
         * - "unique:table_name,column_to_check
         * - "unique:table_name,column_to_check,ignore_value
         * - "unique:table_name,column_to_check,ignore_value,ignore_column
         * - "unique:table_name,column_to_check,ignore_value,ignore_column,extra_where_column
         * - "unique:table_name,column_to_check,ignore_value,ignore_column,extra_where_column,extra_where_value...
         *
         * Default values:
         * connection: $model->getConnectionName()
         * table_name: $model->getTable()
         * column_to_check: Attribute being validated
         * ignore_column: $model->getKeyName()
         * ignore_value: $model->{$ignore_column}
         */
        $tests = [
            // Basic usage
            [
                'rules'      => ['email' => 'unique'],
                'exists'     => ['email' => ['unique:mysql.users,email,primaryKeyValue,primaryKey']],
                'not_exists' => ['email' => ['unique:mysql.users,email,NULL,primaryKey']],
            ],
            // Custom connection & table name
            [
                'rules'      => ['email' => 'unique:connection.table'],
                'exists'     => ['email' => ['unique:connection.table,email,primaryKeyValue,primaryKey']],
                'not_exists' => ['email' => ['unique:connection.table,email,NULL,primaryKey']],
            ],
            // Custom column name
            [
                'rules'      => ['email' => 'unique:users,email_address'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,primaryKeyValue,primaryKey']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,NULL,primaryKey']],
            ],
            // Custom ignored primaryKey
            [
                'rules'      => ['email' => 'unique:users,email_address,customKeyValue'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,customKeyValue,primaryKey']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,customKeyValue,primaryKey']],
            ],
            // Custom primary search column name
            [
                'rules'      => ['email' => 'unique:users,email_address,NULL,customColumn'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,customColumnValue,customColumn']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,NULL,customColumn']],
            ],
            // Additional where clauses (no value)
            [
                'rules'      => ['email' => 'unique:users,email_address,NULL,primaryKey,extraWhereColumn'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,primaryKeyValue,primaryKey,extraWhereColumn']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,NULL,primaryKey,extraWhereColumn']],
            ],
            // Additional where clauses (with value)
            [
                'rules'      => ['email' => 'unique:users,email_address,NULL,primaryKey,extraWhereColumn,extraWhereValue'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,primaryKeyValue,primaryKey,extraWhereColumn,extraWhereValue']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,NULL,primaryKey,extraWhereColumn,extraWhereValue']],
            ],
            // Multiple additional where clauses (no value)
            [
                'rules'      => ['email' => 'unique:users,email_address,NULL,primaryKey,extraWhereColumn,extraWhereValue,secondWhereColumn'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,primaryKeyValue,primaryKey,extraWhereColumn,extraWhereValue,secondWhereColumn']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,NULL,primaryKey,extraWhereColumn,extraWhereValue,secondWhereColumn']],
            ],
            // Multiple additional where clauses (with value)
            [
                'rules'      => ['email' => 'unique:users,email_address,NULL,primaryKey,extraWhereColumn,extraWhereValue,secondWhereColumn,secondWhereValue'],
                'exists'     => ['email' => ['unique:mysql.users,email_address,primaryKeyValue,primaryKey,extraWhereColumn,extraWhereValue,secondWhereColumn,secondWhereValue']],
                'not_exists' => ['email' => ['unique:mysql.users,email_address,NULL,primaryKey,extraWhereColumn,extraWhereValue,secondWhereColumn,secondWhereValue']],
            ],
        ];

        foreach ($tests as $test) {
            $this->exists = true;
            $this->assertEquals($test['exists'], $this->processValidationRules($test['rules']));

            $this->exists = false;
            $this->assertEquals($test['exists'], $this->processValidationRules($test['rules']));
        }
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
