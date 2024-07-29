<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Collection;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\Country;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\DbTestCase;

class HasManyThroughTest extends DbTestCase
{
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

    public function testGetLaravelRelation()
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

        $author1->messages = new Collection([$post1, $post2]);
        $author2->messages = new Collection([$post3, $post4]);

        $author1->save();
        $author2->save();

        $country = Country::with([
            'messages'
        ])->find($country->id);

        $this->assertEquals([
            $post1->id,
            $post2->id,
            $post3->id,
            $post4->id
        ], $country->messages->pluck('id')->toArray());
    }

    public function testCount()
    {
        Model::unguard();
        $country1 = Country::create(['name' => 'Australia']);
        $country2 = Country::create(['name' => 'Canada']);
        $author1 = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $author2 = Author::create(['name' => 'Louie', 'email' => 'louie@email.tld']);
        $post1 = Post::create(['title' => "First post", 'description' => "Yay!!"]);
        $post2 = Post::create(['title' => "Second post", 'description' => "Woohoo!!"]);
        $post3 = Post::create(['title' => "Third post", 'description' => "Yipiee!!"]);
        Model::reguard();

        // Set data
        $author1->country = $country1;
        $author2->country = $country2;

        $author1->posts = new Collection([$post1, $post2]);
        $author2->posts = new Collection([$post3]);

        $author1->save();
        $author2->save();

        $country1 = Country::find($country1->id);
        $country2 = Country::find($country2->id);

        $this->assertEquals(2, $country1->posts_count->first()->count);
        $this->assertEquals(1, $country2->posts_count()->first()->count);
    }

    public function testCountLaravelRelation()
    {
        Model::unguard();
        $country1 = Country::create(['name' => 'Australia']);
        $country2 = Country::create(['name' => 'Canada']);
        $author1 = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $author2 = Author::create(['name' => 'Louie', 'email' => 'louie@email.tld']);
        $post1 = Post::create(['title' => "First post", 'description' => "Yay!!"]);
        $post2 = Post::create(['title' => "Second post", 'description' => "Woohoo!!"]);
        $post3 = Post::create(['title' => "Third post", 'description' => "Yipiee!!"]);
        Model::reguard();

        // Set data
        $author1->country = $country1;
        $author2->country = $country2;

        $author1->messages = new Collection([$post1, $post2]);
        $author2->messages = new Collection([$post3]);

        $author1->save();
        $author2->save();

        $country1 = Country::find($country1->id);
        $country2 = Country::find($country2->id);

        $this->assertEquals(2, $country1->messagesCount->first()->count);
        $this->assertEquals(1, $country2->messagesCount()->first()->count);
    }
}
