<?php

use Winter\Storm\Database\Model;

class PurgeableTest extends TestCase
{
    public function testDirectImplementation()
    {
        $model = new TestModelDirect();
        $this->assertEquals(['Winter.Storm.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }

    public function testDirectImplementationWithoutProperty()
    {
        $model = new TestModelDirectWithoutProperty();
        $this->assertEquals(['Winter.Storm.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }

    public function testDynamicImplementation()
    {
        TestModelDynamic::extend(function ($model) {
            $model->implement[] = 'Winter.Storm.Database.Behaviors.Purgeable';
            $model->addDynamicProperty('purgeable', []);
        });
        $model = new TestModelDynamic();
        $this->assertEquals(['Winter.Storm.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }

    public function testDynamicImplementationWithoutProperty()
    {
        TestModelDynamicWithoutProperty::extend(function ($model) {
            $model->implement[] = 'Winter.Storm.Database.Behaviors.Purgeable';
        });
        $model = new TestModelDynamicWithoutProperty();
        $this->assertEquals(['Winter.Storm.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }
}

/*
 * Class with implementation in the class itself
 */
class TestModelDirect extends Model
{
    public $implement = [
        'Winter.Storm.Database.Behaviors.Purgeable'
    ];

    public $purgeable = [];
}

/*
 * Class with implementation in the class itself but without property
 */
class TestModelDirectWithoutProperty extends Model
{
    public $implement = [
        'Winter.Storm.Database.Behaviors.Purgeable'
    ];
}


/*
 * Class with no implementation that can be extended
 */
class TestModelDynamic extends Model
{

}

class TestModelDynamicWithoutProperty extends Model
{

}
