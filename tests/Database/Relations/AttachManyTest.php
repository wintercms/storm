<?php

namespace Winter\Storm\Tests\Database\Relations;

use DbTestCase;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Attach\File as FileModel;
use Winter\Storm\Tests\Database\Fixtures\Models\User;
use Winter\Storm\Tests\Database\Fixtures\Traits\CreatesModelTables;

class AttachManyTest extends DbTestCase
{
    use CreatesModelTables;

    protected string $assetPath;

    public function setUp() : void
    {
        parent::setUp();

        $this->createModelTables();
        $this->assetPath = realpath(__DIR__ . '/../../fixtures/assets/');
    }

    public function testDeleteFlagDestroyRelationship()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertEmpty($user->photos);
        $user->photos()->create(['data' => $this->assetPath . '/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->photos);

        $photo = $user->photos->first();
        $photoId = $photo->id;

        $user->photos()->remove($photo);
        $this->assertNull(FileModel::find($photoId));
    }

    public function testDeleteFlagDeleteModel()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertEmpty($user->photos);
        $user->photos()->create(['data' => $this->assetPath . '/avatar.png']);
        $user->reloadRelations();
        $this->assertNotEmpty($user->photos);

        $photo = $user->photos->first();
        $this->assertNotNull($photo);
        $photoId = $photo->id;

        $user->delete();
        $this->assertNull(FileModel::find($photoId));
    }
}
