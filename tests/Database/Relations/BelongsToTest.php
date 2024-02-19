<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\DbTestCase;

class BelongsToTest extends DbTestCase
{
    public function testSetRelationValue()
    {
        Model::unguard();
        $post = Post::create(['title' => 'First post', 'description' => 'Yay!!']);
        $author1 = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author2 = Author::create(['name' => 'Louie', 'email' => 'louie@example.com']);
        $author3 = Author::make(['name' => 'Charlie', 'email' => 'charlie@example.com']);
        Model::reguard();

        // Set by Model object
        $post->author = $author1;
        $this->assertEquals($author1->id, $post->author_id);
        $this->assertEquals('Stevie', $post->author->name);

        // Set by primary key
        $post->author = $author2->id;
        $this->assertEquals($author2->id, $post->author_id);
        $this->assertEquals('Louie', $post->author->name);

        // Nullify
        $post->author = null;
        $this->assertNull($post->author_id);
        $this->assertNull($post->author);

        // Deferred in memory
        $post->author = $author3;
        $this->assertEquals('Charlie', $post->author->name);
        $this->assertNull($post->author_id);
        $author3->save();
        $this->assertEquals($author3->id, $post->author_id);
    }

    /**
     * Tests a belongsTo relation being specified with the Laravel format - ie. a public method that returns a relation
     * instance.
     */
    public function testSetRelationValueLaravelRelation()
    {
        Model::unguard();
        $post = Post::create(['title' => 'First post', 'description' => 'Yay!!']);
        $author1 = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author2 = Author::create(['name' => 'Louie', 'email' => 'louie@example.com']);
        $author3 = Author::make(['name' => 'Charlie', 'email' => 'charlie@example.com']);
        Model::reguard();

        // Set by Model object
        $post->writer = $author1;
        $this->assertEquals($author1->id, $post->author_id);
        $this->assertEquals('Stevie', $post->writer->name);

        // Set by primary key
        $post->writer = $author2->id;
        $this->assertEquals($author2->id, $post->author_id);
        $this->assertEquals('Louie', $post->writer->name);

        // Nullify
        $post->writer = null;
        $this->assertNull($post->author_id);
        $this->assertNull($post->writer);

        // Deferred in memory
        $post->writer = $author3;
        $this->assertEquals('Charlie', $post->writer->name);
        $this->assertNull($post->author_id);
        $author3->save();
        $this->assertEquals($author3->id, $post->author_id);
    }

    public function testGetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $post = Post::make(['title' => "First post", 'author_id' => $author->id]);
        Model::reguard();

        $this->assertEquals($author->id, $post->getRelationValue('author'));
    }

    public function testGetRelationValueLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $post = Post::make(['title' => "First post", 'author_id' => $author->id]);
        Model::reguard();

        $this->assertEquals($author->id, $post->getRelationValue('writer'));
    }

    public function testDeferredBinding()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $post = Post::make(['title' => "First post"]);
        $author = Author::create(['name' => 'Stevie']);
        Model::reguard();

        // Deferred add
        $post->author()->add($author, $sessionKey);
        $this->assertNull($post->author_id);
        $this->assertNull($post->author);

        $this->assertEquals(0, $post->author()->count());
        $this->assertEquals(1, $post->author()->withDeferred($sessionKey)->count());

        // Commit deferred
        $post->save(null, $sessionKey);
        $this->assertEquals(1, $post->author()->count());
        $this->assertEquals($author->id, $post->author_id);
        $this->assertEquals('Stevie', $post->author->name);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $post->author()->remove($author, $sessionKey);
        $this->assertEquals(1, $post->author()->count());
        $this->assertEquals(0, $post->author()->withDeferred($sessionKey)->count());
        $this->assertEquals($author->id, $post->author_id);
        $this->assertEquals('Stevie', $post->author->name);

        // Commit deferred
        $post->save(null, $sessionKey);
        $this->assertEquals(0, $post->author()->count());
        $this->assertNull($post->author_id);
        $this->assertNull($post->author);
    }

    public function testDeferredBindingLaravelRelation()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $post = Post::make(['title' => "First post"]);
        $author = Author::create(['name' => 'Stevie']);
        Model::reguard();

        // Deferred add
        $post->writer()->add($author, $sessionKey);
        $this->assertNull($post->author_id);
        $this->assertNull($post->writer);

        $this->assertEquals(0, $post->writer()->count());
        $this->assertEquals(1, $post->writer()->withDeferred($sessionKey)->count());

        // Commit deferred
        $post->save(null, $sessionKey);
        $this->assertEquals(1, $post->writer()->count());
        $this->assertEquals($author->id, $post->author_id);
        $this->assertEquals('Stevie', $post->writer->name);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $post->writer()->remove($author, $sessionKey);
        $this->assertEquals(1, $post->writer()->count());
        $this->assertEquals(0, $post->writer()->withDeferred($sessionKey)->count());
        $this->assertEquals($author->id, $post->author_id);
        $this->assertEquals('Stevie', $post->writer->name);

        // Commit deferred
        $post->save(null, $sessionKey);
        $this->assertEquals(0, $post->writer()->count());
        $this->assertNull($post->author_id);
        $this->assertNull($post->writer);
    }
}
