<?php

namespace Winter\Storm\Tests\Database\Traits;

use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\UserWithAuthor;
use Winter\Storm\Tests\Database\Fixtures\SoftDeleteAuthor;
use Winter\Storm\Tests\Database\Fixtures\UserWithSoftAuthor;
use Winter\Storm\Tests\Database\Fixtures\UserWithAuthorAndSoftDelete;
use Winter\Storm\Tests\Database\Fixtures\UserWithSoftAuthorAndSoftDelete;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\DbTestCase;

class SoftDeleteTest extends DbTestCase
{
    public function testDeleteOptionOnHardModel()
    {
        Model::unguard();
        $user = UserWithAuthor::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author = Author::create(['name' => 'Louie', 'email' => 'louie@example.com', 'user_id' => $user->id]);
        Model::reguard();

        $authorId = $author->id;
        $user->delete(); // Hard
        $this->assertNull(Author::find($authorId));
    }

    public function testSoftDeleteOptionOnHardModel()
    {
        Model::unguard();
        $user = UserWithSoftAuthor::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author = Author::create(['name' => 'Louie', 'email' => 'louie@example.com', 'user_id' => $user->id]);
        Model::reguard();

        $authorId = $author->id;
        $user->delete(); // Hard
        $this->assertNotNull(Author::find($authorId)); // Do nothing
    }

    public function testSoftDeleteOptionOnSoftModel()
    {
        Model::unguard();
        $user = UserWithSoftAuthorAndSoftDelete::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author = SoftDeleteAuthor::create(['name' => 'Louie', 'email' => 'louie@example.com', 'user_id' => $user->id]);
        Model::reguard();

        $authorId = $author->id;
        $user->delete(); // Soft
        $this->assertNull(SoftDeleteAuthor::find($authorId));
        $this->assertNotNull(SoftDeleteAuthor::withTrashed()->find($authorId));
    }

    public function testDeleteOptionOnSoftModel()
    {
        Model::unguard();
        $user = UserWithAuthorAndSoftDelete::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author = Author::create(['name' => 'Louie', 'email' => 'louie@example.com', 'user_id' => $user->id]);
        Model::reguard();

        $authorId = $author->id;
        $user->delete(); // Soft
        $this->assertNotNull(Author::find($authorId)); // Do nothing

        $userId = $user->id;
        $user = UserWithAuthorAndSoftDelete::withTrashed()->find($userId);
        $user->restore();

        $user->forceDelete(); // Hard
        $this->assertNull(Author::find($authorId));
    }

    public function testRestoreSoftDeleteRelation()
    {
        Model::unguard();
        $user = UserWithSoftAuthorAndSoftDelete::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author = SoftDeleteAuthor::create(['name' => 'Louie', 'email' => 'louie@example.com', 'user_id' => $user->id]);
        Model::reguard();

        $authorId = $author->id;
        $user->delete(); // Soft
        $this->assertNull(SoftDeleteAuthor::find($authorId));
        $this->assertNotNull(SoftDeleteAuthor::withTrashed()->find($authorId));

        $userId = $user->id;
        $user = UserWithSoftAuthorAndSoftDelete::withTrashed()->find($userId);
        $user->restore();

        $this->assertNotNull(SoftDeleteAuthor::find($authorId));
    }
}
