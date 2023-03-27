<?php

namespace Winter\Storm\Tests\Database\Relations;

use DbTestCase;
use Winter\Storm\Database\Collection;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Models\Author;
use Winter\Storm\Tests\Database\Fixtures\Models\Country;
use Winter\Storm\Tests\Database\Fixtures\Models\Post;
use Winter\Storm\Tests\Database\Fixtures\Traits\CreatesModelTables;

class HasManyThroughTest extends DbTestCase
{
    use CreatesModelTables;

    public function setUp() : void
    {
        parent::setUp();

        $this->createModelTables();
    }

    public function testGet()
    {
        Model::unguard();
        $country = Country::create(['name' => 'Australia']);
        $author1 = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $author2 = Author::create(['name' => 'Louie', 'email' => 'louie@email.tld']);
        $post1 = Post::create(['title' => "First post", 'description' => "Yay!!"]);
        $post2 = Post::create(['title' => "Second post", 'description' => "Woohoo!!"]);
        $post3 = Post::create(['title' => "Third post", 'description' => "Yipiee!!"]);
        $post4 = Post::make(['title' => "Fourth post", 'description' => "Hooray!!"]);
        Model::reguard();

        // Set data
        $author1->country = $country;
        $author2->country = $country;

        $author1->posts = new Collection([$post1, $post2]);
        $author2->posts = new Collection([$post3, $post4]);

        $author1->save();
        $author2->save();

        $country = Country::with([
            'posts'
        ])->find($country->id);

        $this->assertEquals([
            $post1->id,
            $post2->id,
            $post3->id,
            $post4->id
        ], $country->posts->pluck('id')->toArray());
    }
}
