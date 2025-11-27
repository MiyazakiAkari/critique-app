<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Other User',
            'username' => 'otheruser',
            'email' => 'other@example.com',
        ]);
    }

    /** @test */
    public function it_can_get_own_profile()
    {
        // ユーザー作成時に自動作成されたプロフィールを更新
        $this->user->profile->update([
            'bio' => 'This is my bio',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/profile/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $this->user->id,
                    'name' => 'Test User',
                    'username' => 'testuser',
                    'email' => 'test@example.com',
                ],
                'profile' => [
                    'bio' => 'This is my bio',
                    'avatar_url' => 'https://example.com/avatar.jpg',
                ],
            ]);
    }

    /** @test */
    public function it_creates_profile_if_not_exists_when_getting_own_profile()
    {
        // User作成時に自動でプロフィールが作成されるため、既に存在することを確認
        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/profile/me');

        $response->assertStatus(200);

        // プロフィールが存在することを再度確認（bioはnullで初期化される）
        $profile = $this->user->profile;
        $this->assertNotNull($profile);
        $this->assertNull($profile->bio);
    }

    /** @test */
    public function it_can_get_other_users_profile_by_username()
    {
        // 自動作成されたプロフィールを更新
        $this->otherUser->profile->update([
            'bio' => 'Other user bio',
            'avatar_url' => 'https://example.com/other-avatar.jpg',
        ]);

        $response = $this->getJson('/api/profile/otheruser');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $this->otherUser->id,
                    'name' => 'Other User',
                    'username' => 'otheruser',
                ],
                'profile' => [
                    'bio' => 'Other user bio',
                    'avatar_url' => 'https://example.com/other-avatar.jpg',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_user()
    {
        $response = $this->getJson('/api/profile/nonexistent');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_profile()
    {
        Profile::create([
            'user_id' => $this->user->id,
            'bio' => 'Old bio',
            'avatar_url' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/profile', [
                'bio' => 'Updated bio',
                'avatar_url' => 'https://example.com/new-avatar.jpg',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'プロフィールを更新しました',
                'profile' => [
                    'bio' => 'Updated bio',
                    'avatar_url' => 'https://example.com/new-avatar.jpg',
                ],
            ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
            'bio' => 'Updated bio',
            'avatar_url' => 'https://example.com/new-avatar.jpg',
        ]);
    }

    /** @test */
    public function it_creates_profile_if_not_exists_when_updating()
    {
        // User作成時に自動でプロフィールが作成されるため、既に存在することを確認
        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/profile', [
                'bio' => 'New bio',
            ]);

        $response->assertStatus(200);

        // プロフィールが更新されたことを確認
        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
            'bio' => 'New bio',
        ]);
    }

    /** @test */
    public function it_validates_bio_length()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/profile', [
                'bio' => str_repeat('a', 501), // 500文字を超える
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bio']);
    }

    /** @test */
    public function it_validates_avatar_url_format()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/profile', [
                'avatar_url' => 'not-a-valid-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar_url']);
    }

    /** @test */
    public function it_can_upload_avatar()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'avatar_url',
            ]);

        // ファイルが保存されたか確認
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . $file->hashName()));
    }

    /** @test */
    public function it_validates_avatar_file_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function it_validates_avatar_file_size()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function it_deletes_old_avatar_when_uploading_new_one()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        // 古いアバターを作成
        $oldFile = UploadedFile::fake()->image('old-avatar.jpg');
        $oldPath = $oldFile->store('avatars', 'public');
        
        Profile::create([
            'user_id' => $this->user->id,
            'bio' => 'Test bio',
            'avatar_url' => Storage::url($oldPath),
        ]);

        $this->assertTrue(Storage::disk('public')->exists($oldPath));

        // 新しいアバターをアップロード
        $newFile = UploadedFile::fake()->image('new-avatar.jpg');
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/avatar', [
                'avatar' => $newFile,
            ]);

        $response->assertStatus(200);

        // 古いファイルが削除されているか確認
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        
        // 新しいファイルが保存されているか確認
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . $newFile->hashName()));
    }

    /** @test */
    public function it_resets_profile_and_deletes_avatar_image()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        // プロフィールとアバターを作成
        $file = UploadedFile::fake()->image('avatar.jpg');
        $path = $file->store('avatars', 'public');

        Profile::create([
            'user_id' => $this->user->id,
            'bio' => 'Test bio',
            'avatar_url' => Storage::url($path),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'プロフィールをリセットしました',
            ]);

        // プロフィールがリセットされているか確認
        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
            'bio' => '',
            'avatar_url' => null,
        ]);

        // アバター画像が削除されているか確認
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    /** @test */
    public function it_requires_authentication_for_own_profile()
    {
        $response = $this->getJson('/api/profile/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_profile_update()
    {
        $response = $this->putJson('/api/profile', [
            'bio' => 'Test bio',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_avatar_upload()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_profile_reset()
    {
        $response = $this->deleteJson('/api/profile');

        $response->assertStatus(401);
    }
}
