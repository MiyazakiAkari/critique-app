<?php

namespace Tests\Unit;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'avatar_url' => null,
        ]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $user = User::factory()->create();
        
        $profile = Profile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $this->assertEquals($user->id, $profile->user_id);
        $this->assertEquals('Test bio', $profile->bio);
        $this->assertEquals('https://example.com/avatar.jpg', $profile->avatar_url);
    }

    /** @test */
    public function bio_can_be_null()
    {
        $user = User::factory()->create();
        
        $profile = Profile::create([
            'user_id' => $user->id,
            'bio' => null,
            'avatar_url' => null,
        ]);

        $this->assertNull($profile->bio);
    }

    /** @test */
    public function avatar_url_can_be_null()
    {
        $user = User::factory()->create();
        
        $profile = Profile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'avatar_url' => null,
        ]);

        $this->assertNull($profile->avatar_url);
    }

    /** @test */
    public function user_has_one_profile()
    {
        $user = User::factory()->create();
        
        // ユーザー作成時に自動でプロフィールが作成されることを確認
        $this->assertInstanceOf(Profile::class, $user->profile);
        
        // 自動作成されたプロフィールを更新
        $user->profile->update([
            'bio' => 'Test bio',
            'avatar_url' => null,
        ]);

        $this->assertEquals('Test bio', $user->profile->bio);
    }

    /** @test */
    public function profile_is_deleted_when_user_is_deleted()
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'avatar_url' => null,
        ]);

        $profileId = $profile->id;

        $user->delete();

        $this->assertDatabaseMissing('profiles', [
            'id' => $profileId,
        ]);
    }
}
