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

    public function testDeleteFlagDestroyRelationshipLaravelRelation()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertEmpty($user->images);
        $user->images()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->images);

        $photo = $user->images->first();
        $photoId = $photo->id;

        $user->images()->remove($photo);
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

    public function testDeleteFlagDeleteModelLaravelRelation()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertEmpty($user->images);
        $user->images()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->images);

        $photo = $user->images->first();
        $this->assertNotNull($photo);
        $photoId = $photo->id;

        $user->delete();
        $this->assertNull(File::find($photoId));
    }

    public function testCreateFiresRelationEvents()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $beforeAddCalls = [];
        $afterAddCalls = [];
        $user->bindEvent('model.relation.beforeAdd', function ($relationName, $relatedModel) use (&$beforeAddCalls) {
            $beforeAddCalls[] = [$relationName, $relatedModel];
        });
        $user->bindEvent('model.relation.afterAdd', function ($relationName, $relatedModel) use (&$afterAddCalls) {
            $afterAddCalls[] = [$relationName, $relatedModel];
        });

        $photo = $user->photos()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);

        $this->assertCount(1, $beforeAddCalls);
        $this->assertSame('photos', $beforeAddCalls[0][0]);
        $this->assertTrue($photo->is($beforeAddCalls[0][1]));

        $this->assertCount(1, $afterAddCalls);
        $this->assertSame('photos', $afterAddCalls[0][0]);
        $this->assertTrue($photo->is($afterAddCalls[0][1]));
    }

    public function testCreateDoesNotReplaceExistingAttachments()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $first = $user->photos()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $second = $user->photos()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);

        $this->assertNotNull(File::find($first->id));
        $this->assertNotNull(File::find($second->id));
        $user->reloadRelations();
        $this->assertCount(2, $user->photos);
    }
}
