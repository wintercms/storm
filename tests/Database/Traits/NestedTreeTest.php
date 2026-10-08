<?php

namespace Winter\Storm\Tests\Database\Traits;

use Illuminate\Support\Facades\DB;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\CategoryNested;
use Winter\Storm\Tests\DbTestCase;

class NestedTreeTest extends \Winter\Storm\Tests\DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->seedSampleTree();
    }

    public function testGetNested()
    {
        $items = CategoryNested::getNested();

        // Eager loaded
        $items->each(function ($item) {
            $this->assertTrue($item->relationLoaded('children'));
        });

        $this->assertEquals(2, $items->count());
    }

    public function testGetAllRoot()
    {
        $items = CategoryNested::getAllRoot();

        // Not eager loaded
        $items->each(function ($item) {
            $this->assertFalse($item->relationLoaded('children'));
        });

        $this->assertEquals(2, $items->count());
    }

    public function testListsNested()
    {
        $array = CategoryNested::listsNested('name', 'id');
        $this->assertEquals([
            1 => 'Category Orange',
            2 => '&nbsp;&nbsp;&nbsp;Autumn Leaves',
            3 => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;September',
            4 => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;October',
            5 => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;November',
            6 => '&nbsp;&nbsp;&nbsp;Summer Breeze',
            7 => 'Category Green',
            8 => '&nbsp;&nbsp;&nbsp;Winter Snow',
            9 => '&nbsp;&nbsp;&nbsp;Spring Trees'
        ], $array);

        CategoryNested::flushDuplicateCache();

        $array = CategoryNested::listsNested('name', 'id', '--');
        $this->assertEquals([
            1 => 'Category Orange',
            2 => '--Autumn Leaves',
            3 => '----September',
            4 => '----October',
            5 => '----November',
            6 => '--Summer Breeze',
            7 => 'Category Green',
            8 => '--Winter Snow',
            9 => '--Spring Trees'
        ], $array);

        CategoryNested::flushDuplicateCache();

        $array = CategoryNested::listsNested('description', 'name', '**');
        $this->assertEquals([
            'Category Orange' => 'A root level test category',
            'Autumn Leaves' => '**Disccusion about the season of falling leaves.',
            'September' => '****The start of the fall season.',
            'October' => '****The middle of the fall season.',
            'November' => '****The end of the fall season.',
            'Summer Breeze' => '**Disccusion about the wind at the ocean.',
            'Category Green' => 'A root level test category',
            'Winter Snow' => '**Disccusion about the frosty snow flakes.',
            'Spring Trees' => '**Disccusion about the blooming gardens.'
        ], $array);
    }

    public function testListsNestedFromCollection()
    {
        $array = CategoryNested::get()->listsNested('custom_name', 'id', '...');
        $this->assertEquals([
            1 => 'Category Orange (#1)',
            2 => '...Autumn Leaves (#2)',
            3 => '......September (#3)',
            4 => '......October (#4)',
            5 => '......November (#5)',
            6 => '...Summer Breeze (#6)',
            7 => 'Category Green (#7)',
            8 => '...Winter Snow (#8)',
            9 => '...Spring Trees (#9)'
        ], $array);
    }

    public function testToNestedArray()
    {
        $array = CategoryNested::nestedArray('name', 'id');
        $this->assertEquals([
            1 => [
                "name" => "Category Orange",
                "children" => [
                    2 => [
                        "name" => "Autumn Leaves",
                        "children" => [
                            3 => [
                                "name" => "September",
                            ],
                            4 => [
                                "name" => "October",
                            ],
                            5 => [
                                "name" => "November",
                            ],
                        ],
                    ],
                    6 => [
                        "name" => "Summer Breeze",
                    ],
                ],
            ],
            7 => [
                "name" => "Category Green",
                "children" => [
                    8 => [
                        "name" => "Winter Snow",
                    ],
                    9 => [
                        "name" => "Spring Trees",
                    ],
                ],
            ],
        ], $array);

        CategoryNested::flushDuplicateCache();

        $array = CategoryNested::nestedArray('name');
        $this->assertEquals([
            0 => [
                "name" => "Category Orange",
                "children" => [
                    0 => [
                        "name" => "Autumn Leaves",
                        "children" => [
                            0 => [
                                "name" => "September",
                            ],
                            1 => [
                                "name" => "October",
                            ],
                            2 => [
                                "name" => "November",
                            ],
                        ],
                    ],
                    1 => [
                        "name" => "Summer Breeze",
                    ],
                ],
            ],
            1 => [
                "name" => "Category Green",
                "children" => [
                    0 => [
                        "name" => "Winter Snow",
                    ],
                    1 => [
                        "name" => "Spring Trees",
                    ],
                ],
            ],
        ], $array);
    }

    public function testToNestedArrayFromCollection()
    {
        $array = CategoryNested::get()->toNestedArray('name', 'id');
        $this->assertEquals([
            1 => [
                "name" => "Category Orange",
                "children" => [
                    2 => [
                        "name" => "Autumn Leaves",
                        "children" => [
                            3 => [
                                "name" => "September",
                            ],
                            4 => [
                                "name" => "October",
                            ],
                            5 => [
                                "name" => "November",
                            ],
                        ],
                    ],
                    6 => [
                        "name" => "Summer Breeze",
                    ],
                ],
            ],
            7 => [
                "name" => "Category Green",
                "children" => [
                    8 => [
                        "name" => "Winter Snow",
                    ],
                    9 => [
                        "name" => "Spring Trees",
                    ],
                ],
            ],
        ], $array);

        CategoryNested::flushDuplicateCache();

        $array = CategoryNested::get()->toNestedArray(['name', 'description'], 'id');
        $this->assertEquals([
            1 => [
                "name" => "Category Orange",
                'description' => 'A root level test category',
                "children" => [
                    2 => [
                        "name" => "Autumn Leaves",
                        'description' => 'Disccusion about the season of falling leaves.',
                        "children" => [
                            3 => [
                                "name" => "September",
                                'description' => 'The start of the fall season.'
                            ],
                            4 => [
                                "name" => "October",
                                'description' => 'The middle of the fall season.'
                            ],
                            5 => [
                                "name" => "November",
                                'description' => 'The end of the fall season.'
                            ],
                        ],
                    ],
                    6 => [
                        "name" => "Summer Breeze",
                        'description' => 'Disccusion about the wind at the ocean.'
                    ],
                ],
            ],
            7 => [
                "name" => "Category Green",
                'description' => 'A root level test category',
                "children" => [
                    8 => [
                        "name" => "Winter Snow",
                        'description' => 'Disccusion about the frosty snow flakes.'
                    ],
                    9 => [
                        "name" => "Spring Trees",
                        'description' => 'Disccusion about the blooming gardens.'
                    ],
                ],
            ],
        ], $array);
    }

    public function testToNestedArrayWithoutKey()
    {
        $array = CategoryNested::nestedArray('name');
        $this->assertEquals([
            [
                "name" => "Category Orange",
                "children" => [
                    [
                        "name" => "Autumn Leaves",
                        "children" => [
                            [
                                "name" => "September",
                            ],
                            [
                                "name" => "October",
                            ],
                            [
                                "name" => "November",
                            ],
                        ],
                    ],
                    [
                        "name" => "Summer Breeze",
                    ],
                ],
            ],
            [
                "name" => "Category Green",
                "children" => [
                    [
                        "name" => "Winter Snow",
                    ],
                    [
                        "name" => "Spring Trees",
                    ],
                ],
            ],
        ], $array);

        CategoryNested::flushDuplicateCache();

        $array = CategoryNested::nestedArray(['name', 'description']);
        $this->assertEquals([
            [
                "name" => "Category Orange",
                'description' => 'A root level test category',
                "children" => [
                    [
                        "name" => "Autumn Leaves",
                        'description' => 'Disccusion about the season of falling leaves.',
                        "children" => [
                            [
                                "name" => "September",
                                'description' => 'The start of the fall season.'
                            ],
                            [
                                "name" => "October",
                                'description' => 'The middle of the fall season.'
                            ],
                            [
                                "name" => "November",
                                'description' => 'The end of the fall season.'
                            ],
                        ],
                    ],
                    [
                        "name" => "Summer Breeze",
                        'description' => 'Disccusion about the wind at the ocean.'
                    ],
                ],
            ],
            [
                "name" => "Category Green",
                'description' => 'A root level test category',
                "children" => [
                    [
                        "name" => "Winter Snow",
                        'description' => 'Disccusion about the frosty snow flakes.'
                    ],
                    [
                        "name" => "Spring Trees",
                        'description' => 'Disccusion about the blooming gardens.'
                    ],
                ],
            ],
        ], $array);
    }

    public function testToNestedArrayFromCollectionWithoutKey()
    {
        $array = CategoryNested::get()->toNestedArray('name');
        $this->assertEquals([
            [
                "name" => "Category Orange",
                "children" => [
                    [
                        "name" => "Autumn Leaves",
                        "children" => [
                            [
                                "name" => "September",
                            ],
                            [
                                "name" => "October",
                            ],
                            [
                                "name" => "November",
                            ],
                        ],
                    ],
                    [
                        "name" => "Summer Breeze",
                    ],
                ],
            ],
            [
                "name" => "Category Green",
                "children" => [
                    [
                        "name" => "Winter Snow",
                    ],
                    [
                        "name" => "Spring Trees",
                    ],
                ],
            ],
        ], $array);

        CategoryNested::flushDuplicateCache();

        $array = CategoryNested::get()->toNestedArray(['name', 'description']);
        $this->assertEquals([
            [
                "name" => "Category Orange",
                'description' => 'A root level test category',
                "children" => [
                    [
                        "name" => "Autumn Leaves",
                        'description' => 'Disccusion about the season of falling leaves.',
                        "children" => [
                            [
                                "name" => "September",
                                'description' => 'The start of the fall season.'
                            ],
                            [
                                "name" => "October",
                                'description' => 'The middle of the fall season.'
                            ],
                            [
                                "name" => "November",
                                'description' => 'The end of the fall season.'
                            ],
                        ],
                    ],
                    [
                        "name" => "Summer Breeze",
                        'description' => 'Disccusion about the wind at the ocean.'
                    ],
                ],
            ],
            [
                "name" => "Category Green",
                'description' => 'A root level test category',
                "children" => [
                    [
                        "name" => "Winter Snow",
                        'description' => 'Disccusion about the frosty snow flakes.'
                    ],
                    [
                        "name" => "Spring Trees",
                        'description' => 'Disccusion about the blooming gardens.'
                    ],
                ],
            ],
        ], $array);
    }

    public function testMoveSubtreeRealignsDescendantDepths()
    {
        $autumn = CategoryNested::where('name', 'Autumn Leaves')->first();
        $winter = CategoryNested::where('name', 'Winter Snow')->first();

        // Move the 'Autumn Leaves' subtree (three children) one level deeper, under 'Winter Snow'.
        $autumn->makeChildOf($winter);
        CategoryNested::flushDuplicateCache();

        $this->assertEquals(2, CategoryNested::where('name', 'Autumn Leaves')->value('nest_depth'));
        foreach (['September', 'October', 'November'] as $name) {
            $this->assertEquals(3, CategoryNested::where('name', $name)->value('nest_depth'));
        }

        // Move the subtree back to the root level (two levels shallower).
        CategoryNested::flushDuplicateCache();
        CategoryNested::where('name', 'Autumn Leaves')->first()->makeRoot();
        CategoryNested::flushDuplicateCache();

        $this->assertEquals(0, CategoryNested::where('name', 'Autumn Leaves')->value('nest_depth'));
        foreach (['September', 'October', 'November'] as $name) {
            $this->assertEquals(1, CategoryNested::where('name', $name)->value('nest_depth'));
        }
    }

    public function testParentIdChangeRealignsDescendantDepths()
    {
        $autumn = CategoryNested::where('name', 'Autumn Leaves')->first();
        $winter = CategoryNested::where('name', 'Winter Snow')->first();

        // Reassign the parent through the attribute, as a form submission would.
        $autumn->parent_id = $winter->id;
        $autumn->save();
        CategoryNested::flushDuplicateCache();

        $this->assertEquals(2, CategoryNested::where('name', 'Autumn Leaves')->value('nest_depth'));
        foreach (['September', 'October', 'November'] as $name) {
            $this->assertEquals(3, CategoryNested::where('name', $name)->value('nest_depth'));
        }
    }

    public function testSaveWithoutMoveSkipsDepthRecompute()
    {
        $september = CategoryNested::where('name', 'September')->first();

        // Plant a sentinel depth value behind the model's back.
        DB::table('database_tester_categories_nested')
            ->where('id', $september->id)
            ->update(['nest_depth' => 42]);

        // A save that does not move the node must not pay for a depth recompute.
        $september = CategoryNested::find($september->id);
        $september->name = 'September (renamed)';
        $september->save();
        CategoryNested::flushDuplicateCache();

        $this->assertEquals(42, CategoryNested::where('id', $september->id)->value('nest_depth'));

        // Moving the node realigns the depth from its ancestry again.
        $green = CategoryNested::where('name', 'Category Green')->first();
        $september->parent_id = $green->id;
        $september->save();
        CategoryNested::flushDuplicateCache();

        $this->assertEquals(1, CategoryNested::where('id', $september->id)->value('nest_depth'));
    }

    public function testMoveSubtreeQueryCountIsConstant()
    {
        $autumn = CategoryNested::where('name', 'Autumn Leaves')->first();
        $winter = CategoryNested::where('name', 'Winter Snow')->first();

        DB::enableQueryLog();
        $autumn->makeChildOf($winter);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The bulk depth realignment keeps a move at a fixed number of statements no matter how
        // many descendants the subtree has (previously one full save cycle per descendant).
        $this->assertLessThan(12, $queries);
    }

    public function seedSampleTree()
    {
        Model::unguard();

        $orange = CategoryNested::create([
            'name' => 'Category Orange',
            'description' => 'A root level test category',
        ]);

        $autumn = $orange->children()->create([
            'name' => 'Autumn Leaves',
            'description' => 'Disccusion about the season of falling leaves.'
        ]);

        $autumn->children()->create([
            'name' => 'September',
            'description' => 'The start of the fall season.'
        ]);

        $autumn->children()->create([
            'name' => 'October',
            'description' => 'The middle of the fall season.'
        ]);

        $autumn->children()->create([
            'name' => 'November',
            'description' => 'The end of the fall season.'
        ]);

        $orange->children()->create([
            'name' => 'Summer Breeze',
            'description' => 'Disccusion about the wind at the ocean.'
        ]);

        $green = CategoryNested::create([
            'name' => 'Category Green',
            'description' => 'A root level test category',
        ]);

        $green->children()->create([
            'name' => 'Winter Snow',
            'description' => 'Disccusion about the frosty snow flakes.'
        ]);

        $green->children()->create([
            'name' => 'Spring Trees',
            'description' => 'Disccusion about the blooming gardens.'
        ]);

        Model::reguard();
    }
}
