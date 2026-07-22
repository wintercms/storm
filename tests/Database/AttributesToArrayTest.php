<?php

namespace Tests\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\DbTestCase;

/**
 * Tests for Winter\Storm\Database\Model::attributesToArray().
 *
 * Storm overrides this method purely so that it can fire the `model.beforeGetAttribute` and
 * `model.getAttribute` events and decode `$jsonable` columns; everything else is delegated to
 * Laravel. These tests pin both halves of that contract: the Winter specific behaviour that must
 * survive any future re-sync with Laravel, and the cast handling that Storm's previous hand-rolled
 * copy of Laravel's cast loops silently got wrong.
 */
class AttributesToArrayTest extends DbTestCase
{
    //
    // Winter specific behaviour: $jsonable
    //

    public function testJsonableAttributesAreDecoded()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['jsondata' => '{"foo":"bar"}']);

        $this->assertSame(['foo' => 'bar'], $model->attributesToArray()['jsondata']);
    }

    public function testJsonableAttributesWithInvalidJsonAreLeftAsStrings()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['jsondata' => 'not json at all']);

        $this->assertSame('not json at all', $model->attributesToArray()['jsondata']);
    }

    public function testJsonableAttributesAreNotDoubleDecoded()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['jsondata' => ['foo' => 'bar']]);

        $this->assertSame(['foo' => 'bar'], $model->attributesToArray()['jsondata']);
    }

    public function testJsonableAttributesAreSkippedWhenAMutatorExists()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['mutatedjson' => '{"foo":"bar"}']);

        $this->assertSame('MUTATED', $model->attributesToArray()['mutatedjson']);
    }

    //
    // Winter specific behaviour: attribute events
    //

    public function testBeforeGetAttributeEventCanOverrideValues()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['name' => 'original', 'other' => 'untouched']);

        $model->bindEvent('model.beforeGetAttribute', function ($key) {
            return $key === 'name' ? 'overridden' : null;
        });

        $attributes = $model->attributesToArray();

        $this->assertSame('overridden', $attributes['name']);
        $this->assertSame('untouched', $attributes['other']);
    }

    public function testGetAttributeEventCanOverrideValues()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['name' => 'original', 'other' => 'untouched']);

        $model->bindEvent('model.getAttribute', function ($key, $value) {
            return $key === 'name' ? 'overridden' : null;
        });

        $attributes = $model->attributesToArray();

        $this->assertSame('overridden', $attributes['name']);
        $this->assertSame('untouched', $attributes['other']);
    }

    /**
     * The `model.getAttribute` event must fire *after* dates, mutators, casts, appends and jsonable
     * decoding have all been applied, so that listeners observe the final serialized value rather
     * than the raw attribute. Reordering the pipeline would silently change what plugins receive.
     */
    public function testGetAttributeEventReceivesFullyProcessedValues()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes([
            'dt' => '2025-01-01 00:00:00',
            'jsondata' => '{"foo":"bar"}',
            'mutated' => 'raw',
        ]);

        $seen = [];

        $model->bindEvent('model.getAttribute', function ($key, $value) use (&$seen) {
            $seen[$key] = $value;
        });

        $model->attributesToArray();

        $this->assertSame('2025-01-01T00:00:00.000000Z', $seen['dt']);
        $this->assertSame(['foo' => 'bar'], $seen['jsondata']);
        $this->assertSame('MUT:raw', $seen['mutated']);
        $this->assertSame('APPENDED', $seen['appended']);
    }

    //
    // Cast handling
    //

    /**
     * Pure (non-backed) enums have no default JSON representation, so leaving the enum instance in
     * the array caused `toJson()` to throw outright.
     */
    public function testPureEnumCastsAreSerialized()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['pureenum' => 'Live']);

        $this->assertSame('Live', $model->attributesToArray()['pureenum']);

        // Would previously throw: "Non-backed enums have no default serialization".
        $this->assertSame('Live', json_decode($model->toJson(), true)['pureenum']);
    }

    public function testBackedEnumCastsAreConvertedToScalars()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['backedenum' => 'live']);

        $this->assertSame('live', $model->attributesToArray()['backedenum']);
    }

    /**
     * Casts implementing SerializesCastableAttributes must have their serialize() method honoured
     * rather than the raw value object being left in the array.
     */
    public function testSerializableCastsUseTheirSerializeMethod()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['money' => '1250']);

        $this->assertInstanceOf(TestMoneyValue::class, $model->money);
        $this->assertSame('SERIALIZED:1250', $model->attributesToArray()['money']);
    }

    /**
     * Arrayable casts must be flattened, otherwise attributesToArray() returns a nested object and
     * breaks its own contract of returning a plain array.
     */
    public function testArrayableCastsAreConvertedToArrays()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes([
            'collection' => '{"foo":"bar"}',
            'arrayobject' => '{"baz":"qux"}',
        ]);

        $attributes = $model->attributesToArray();

        $this->assertIsArray($attributes['collection']);
        $this->assertSame(['foo' => 'bar'], $attributes['collection']);
        $this->assertIsArray($attributes['arrayobject']);
        $this->assertSame(['baz' => 'qux'], $attributes['arrayobject']);
    }

    /**
     * The implicit primary key cast comes from getCasts(), not the raw $casts property. Iterating
     * the latter meant $model->id and $model->toArray()['id'] disagreed on any driver that returns
     * column values as strings.
     */
    public function testPrimaryKeyCastMatchesTheAttributeAccessor()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['id' => '5']);

        $this->assertSame(5, $model->id);
        $this->assertSame(5, $model->attributesToArray()['id']);
    }

    public function testNonIncrementingModelsDoNotCastTheirPrimaryKey()
    {
        $model = new TestModelNonIncrementing();
        $model->setRawAttributes(['id' => '5']);

        $this->assertSame('5', $model->attributesToArray()['id']);
    }

    //
    // Boundaries
    //

    public function testNullCastValuesRemainNull()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes([
            'dt' => null,
            'backedenum' => null,
            'collection' => null,
            'money' => null,
        ]);

        $attributes = $model->attributesToArray();

        $this->assertNull($attributes['dt']);
        $this->assertNull($attributes['backedenum']);
        $this->assertNull($attributes['collection']);
        $this->assertNull($attributes['money']);
    }

    public function testMutatorsTakePrecedenceOverCasts()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes(['mutated' => 'raw']);

        $this->assertSame('MUT:raw', $model->attributesToArray()['mutated']);
    }

    public function testAppendedAttributesAreIncluded()
    {
        $model = new TestModelAttributes();
        $model->setRawAttributes([]);

        $this->assertSame('APPENDED', $model->attributesToArray()['appended']);
    }
}

enum TestPureStatus
{
    case Draft;
    case Live;
}

enum TestBackedStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
}

class TestMoneyValue
{
    public function __construct(public int $cents)
    {
    }
}

class TestMoneyCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return is_null($value) ? null : new TestMoneyValue((int) $value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => $value instanceof TestMoneyValue ? $value->cents : $value];
    }

    public function serialize($model, string $key, $value, array $attributes)
    {
        return 'SERIALIZED:' . $value->cents;
    }
}

class TestModelAttributes extends Model
{
    public $table = 'test_model';

    public $timestamps = false;

    protected $guarded = [];

    protected $jsonable = ['jsondata', 'mutatedjson'];

    protected $appends = ['appended'];

    protected $casts = [
        'dt' => 'datetime',
        'pureenum' => TestPureStatus::class,
        'backedenum' => TestBackedStatus::class,
        'money' => TestMoneyCast::class,
        'collection' => AsCollection::class,
        'arrayobject' => AsArrayObject::class,
        'mutated' => 'integer',
    ];

    public function getMutatedAttribute($value)
    {
        return 'MUT:' . $value;
    }

    public function getMutatedjsonAttribute($value)
    {
        return 'MUTATED';
    }

    public function getAppendedAttribute()
    {
        return 'APPENDED';
    }
}

class TestModelNonIncrementing extends Model
{
    public $table = 'test_model';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];
}
