<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Attach\File;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\User;
use Winter\Storm\Tests\DbTestCase;

class AttachManyTest extends DbTestCase
{
    public function testDeleteFlagDestroyRelationship()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertEmpty($user->photos);
        $user->photos()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->photos);

        $photo = $user->photos->first();
        $photoId = $photo->id;

        $user->photos()->remove($photo);
        $this->assertNull(File::find($photoId));
    }

    public function testDeleteFlagDeleteModel()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertEmpty($user->photos);
        $user->photos()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->photos);

        $photo = $user->photos->first();
        $this->assertNotNull($photo);
        $photoId = $photo->id;

        $user->delete();
        $this->assertNull(File::find($photoId));
    }
}
