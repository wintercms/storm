<?php

namespace Winter\Storm\Tests\Database\Relations;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Winter\Storm\Database\Attach\File;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\SoftDeleteUser;
use Winter\Storm\Tests\Database\Fixtures\User;
use Winter\Storm\Tests\DbTestCase;

class AttachOneTest extends DbTestCase
{
    public function testSetRelationValue()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $user2 = User::create(['name' => 'Joe', 'email' => 'joe@example.com']);
        Model::reguard();

        // Set by string
        $user->avatar = dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png';

        // @todo $user->avatar currently sits as a string, not good for validation
        // this should really assert as an UploadedFile instead.

        // Commit the file and it should snap to a File model
        $user->save();

        $this->assertNotNull($user->avatar);
        $this->assertEquals('avatar.png', $user->avatar->file_name);

        // Set by Uploaded file
        $sample = $user->avatar;
        $upload = new UploadedFile(
            dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png',
            $sample->file_name,
            $sample->content_type,
            null,
            true
        );

        $user2->avatar = $upload;

        // The file is prepped but not yet commited, this is for validation
        $this->assertNotNull($user2->avatar);
        $this->assertEquals($upload, $user2->avatar);

        // Commit the file and it should snap to a File model
        $user2->save();

        $this->assertNotNull($user2->avatar);
        $this->assertEquals('avatar.png', $user2->avatar->file_name);
    }

    public function testSetRelationValueLaravelRelation()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $user2 = User::create(['name' => 'Joe', 'email' => 'joe@example.com']);
        Model::reguard();

        // Set by string
        $user->displayPicture = dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png';

        // @todo $user->displayPicture currently sits as a string, not good for validation
        // this should really assert as an UploadedFile instead.

        // Commit the file and it should snap to a File model
        $user->save();

        $this->assertNotNull($user->displayPicture);
        $this->assertEquals('avatar.png', $user->displayPicture->file_name);

        // Set by Uploaded file
        $sample = $user->displayPicture;
        $upload = new UploadedFile(
            dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png',
            $sample->file_name,
            $sample->content_type,
            null,
            true
        );

        $user2->displayPicture = $upload;

        // The file is prepped but not yet commited, this is for validation
        $this->assertNotNull($user2->displayPicture);
        $this->assertEquals($upload, $user2->displayPicture);

        // Commit the file and it should snap to a File model
        $user2->save();

        $this->assertNotNull($user2->displayPicture);
        $this->assertEquals('avatar.png', $user2->displayPicture->file_name);
    }

    public function testDeleteFlagDestroyRelationship()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertNull($user->avatar);
        $user->avatar()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotNull($user->avatar);

        $avatar = $user->avatar;
        $avatarId = $avatar->id;

        $user->avatar()->remove($avatar);
        $this->assertNull(File::find($avatarId));
    }

    public function testDeleteFlagDestroyRelationshipLaravelRelation()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertNull($user->displayPicture);
        $user->displayPicture()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotNull($user->displayPicture);

        $avatar = $user->displayPicture;
        $avatarId = $avatar->id;

        $user->displayPicture()->remove($avatar);
        $this->assertNull(File::find($avatarId));
    }

    public function testDeleteFlagDeleteModel()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertNull($user->avatar);
        $user->avatar()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotNull($user->avatar);

        $avatarId = $user->avatar->id;
        $user->delete();
        $this->assertNull(File::find($avatarId));
    }

    public function testDeleteFlagDeleteModelLaravelRelation()
    {
        Model::unguard();
        $user = User::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $this->assertNull($user->displayPicture);
        $user->displayPicture()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $user->reloadRelations();
        $this->assertNotNull($user->displayPicture);

        $avatarId = $user->displayPicture->id;
        $user->delete();
        $this->assertNull(File::find($avatarId));
    }

    public function testDeleteFlagSoftDeleteModel()
    {
        Model::unguard();
        $user = SoftDeleteUser::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        Model::reguard();

        $user->avatar()->create(['data' => dirname(dirname(__DIR__)) . '/fixtures/attach/avatar.png']);
        $this->assertNotNull($user->avatar);

        $avatarId = $user->avatar->id;
        $user->delete();
        $this->assertNotNull(File::find($avatarId));
    }
}
