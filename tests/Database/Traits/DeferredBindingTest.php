<?php

namespace Winter\Storm\Tests\Database\Traits;

use Winter\Storm\Database\Models\DeferredBinding;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\DbTestCase;

class DeferredBindingTest extends DbTestCase
{
    public function testNegatedBinding()
    {
        $sessionKey = uniqid('session_key', true);
        DeferredBinding::truncate();

        Model::unguard();
        $author = Author::make(['name' => 'Stevie']);
        $post = Post::create(['title' => "First post"]);
        $post2 = Post::create(['title' => "Second post"]);
        Model::reguard();

        $author->posts()->add($post, $sessionKey);
        $this->assertEquals(1, DeferredBinding::count());

        // Skip repeat bindings
        $author->posts()->add($post, $sessionKey);
        $this->assertEquals(1, DeferredBinding::count());

        // Remove add-delete pairs
        $author->posts()->remove($post, $sessionKey);
        $this->assertEquals(0, DeferredBinding::count());

        // Multi ball
        $sessionKey = uniqid('session_key', true);
        $author->posts()->add($post, $sessionKey);
        $author->posts()->add($post, $sessionKey);
        $author->posts()->add($post, $sessionKey);
        $author->posts()->add($post, $sessionKey);
        $author->posts()->add($post2, $sessionKey);
        $author->posts()->add($post2, $sessionKey);
        $author->posts()->add($post2, $sessionKey);
        $author->posts()->add($post2, $sessionKey);
        $author->posts()->add($post2, $sessionKey);
        $this->assertEquals(2, DeferredBinding::count());

        // Clean up add-delete pairs
        $author->posts()->remove($post, $sessionKey);
        $author->posts()->remove($post2, $sessionKey);
        $this->assertEquals(0, DeferredBinding::count());

        // Double negative
        $author->posts()->remove($post, $sessionKey);
        $author->posts()->remove($post2, $sessionKey);
        $this->assertEquals(2, DeferredBinding::count());

        // Skip repeat bindings
        $author->posts()->remove($post, $sessionKey);
        $author->posts()->remove($post2, $sessionKey);
        $this->assertEquals(2, DeferredBinding::count());

        // Clean up add-delete pairs again
        $author->posts()->add($post, $sessionKey);
        $author->posts()->add($post2, $sessionKey);
        $this->assertEquals(0, DeferredBinding::count());
    }

    public function testCancelBinding()
    {
        $sessionKey = uniqid('session_key', true);
        DeferredBinding::truncate();

        Model::unguard();
        $author = Author::make(['name' => 'Stevie']);
        $post = Post::create(['title' => "First post"]);
        Model::reguard();

        $author->posts()->add($post, $sessionKey);
        $this->assertEquals(1, DeferredBinding::count());

        $author->cancelDeferred($sessionKey);
        $this->assertEquals(0, DeferredBinding::count());
    }

    public function testCommitBinding()
    {
        $sessionKey = uniqid('session_key', true);
        DeferredBinding::truncate();

        Model::unguard();
        $author = Author::make(['name' => 'Stevie']);
        $post = Post::create(['title' => "First post"]);
        Model::reguard();

        $author->posts()->add($post, $sessionKey);
        $this->assertEquals(1, DeferredBinding::count());

        $author->commitDeferred($sessionKey);
        $this->assertEquals(0, DeferredBinding::count());
    }
}
