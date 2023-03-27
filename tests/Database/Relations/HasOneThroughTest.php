<?php

namespace Winter\Storm\Tests\Database\Relations;

use DbTestCase;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Models\Author;
use Winter\Storm\Tests\Database\Fixtures\Models\Phone;
use Winter\Storm\Tests\Database\Fixtures\Models\User;
use Winter\Storm\Tests\Database\Fixtures\Traits\CreatesModelTables;

class HasOneThroughTest extends DbTestCase
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
        $phone = Phone::create(['number' => '08 1234 5678']);
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@email.tld']);
        Model::reguard();

        // Set data
        $author->phone = $phone;
        $author->user = $user;
        $author->save();

        $user = User::with([
            'phone'
        ])->find($user->id);

        $this->assertEquals($phone->id, $user->phone->id);
    }
}
