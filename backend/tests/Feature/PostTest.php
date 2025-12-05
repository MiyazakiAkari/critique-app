<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

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
    public function it_can_create_a_post()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'This is a test post',
                'image' => $image,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '投稿しました',
                'post' => [
                    'content' => 'This is a test post',
                    'user' => [
                        'id' => $this->user->id,
                        'name' => 'Test User',
                        'username' => 'testuser',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'content' => 'This is a test post',
        ]);
    }

    /** @test */
    public function it_validates_post_content()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function it_validates_post_content_max_length()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => str_repeat('a', 501),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function it_can_get_recommended_posts()
    {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'User post',
        ]);

        Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'content' => 'Other user post',
        ]);

        $response = $this->getJson('/api/posts/recommended');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts' => [
                    '*' => [
                        'id',
                        'content',
                        'created_at',
                        'user' => ['id', 'name', 'username'],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('posts'));
    }

    /** @test */
    public function it_can_get_timeline_posts()
    {
        // userがotherUserをフォロー
        $this->user->followings()->attach($this->otherUser->id);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'My post',
        ]);

        Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'content' => 'Following user post',
        ]);

        // フォローしていない第3のユーザー
        $thirdUser = User::factory()->create();
        Post::factory()->create([
            'user_id' => $thirdUser->id,
            'content' => 'Not following user post',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/posts/timeline');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(2, $posts);

        // タイムラインには自分とフォロー中のユーザーの投稿のみ
        $contents = collect($posts)->pluck('content')->toArray();
        $this->assertContains('My post', $contents);
        $this->assertContains('Following user post', $contents);
        $this->assertNotContains('Not following user post', $contents);
    }

    /** @test */
    public function it_can_get_single_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Test post',
        ]);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'post' => [
                    'id' => $post->id,
                    'content' => 'Test post',
                    'user' => [
                        'id' => $this->user->id,
                        'name' => 'Test User',
                        'username' => 'testuser',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_delete_own_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Test post',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => '投稿を削除しました',
            ]);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    /** @test */
    public function it_cannot_delete_other_users_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'content' => 'Other user post',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => '削除権限がありません',
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
    }

    /** @test */
    public function it_can_get_user_posts()
    {
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        Post::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->getJson('/api/users/testuser/posts');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('posts'));
    }

    /** @test */
    public function it_requires_authentication_for_creating_post()
    {
        $response = $this->postJson('/api/posts', [
            'content' => 'Test post',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_timeline()
    {
        $response = $this->getJson('/api/posts/timeline');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_deleting_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_image_when_creating_post()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post without image',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_file_type()
    {
        Storage::fake('public');

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with invalid file',
                'image' => $invalidFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_file_size()
    {
        Storage::fake('public');

        // 10MBを超える画像
        $largeImage = UploadedFile::fake()->create('large.jpg', 11000, 'image/jpeg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with large image',
                'image' => $largeImage,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_returns_image_url_in_timeline()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->create('timeline-test.jpg', 100, 'image/jpeg');

        // 画像付き投稿を作成
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Timeline post with image',
                'image' => $image,
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/posts/timeline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts' => [
                    '*' => [
                        'id',
                        'content',
                        'image_path',
                        'image_url',
                        'created_at',
                        'user',
                    ],
                ],
            ]);

        $posts = $response->json('posts');
        $this->assertNotEmpty($posts);
        $this->assertNotNull($posts[0]['image_url']);
        $this->assertStringContainsString('storage/posts/', $posts[0]['image_url']);
    }

    /** @test */
    public function it_returns_image_url_in_recommended_posts()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->create('recommended-test.jpg', 100, 'image/jpeg');

        // 画像付き投稿を作成
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Recommended post with image',
                'image' => $image,
            ]);

        $response = $this->getJson('/api/posts/recommended');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts' => [
                    '*' => [
                        'id',
                        'content',
                        'image_path',
                        'image_url',
                        'created_at',
                        'user',
                    ],
                ],
            ]);

        $posts = $response->json('posts');
        $this->assertNotEmpty($posts);
        $this->assertNotNull($posts[0]['image_url']);
    }

    /** @test */
    public function it_returns_image_url_in_single_post()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->create('single-post-test.jpg', 100, 'image/jpeg');

        // 画像付き投稿を作成
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Single post with image',
                'image' => $image,
            ]);

        $postId = $response->json('post.id');

        $response = $this->getJson("/api/posts/{$postId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'post' => [
                    'id',
                    'content',
                    'image_path',
                    'image_url',
                    'created_at',
                    'user',
                ],
            ]);

        $post = $response->json('post');
        $this->assertNotNull($post['image_url']);
        $this->assertStringContainsString('storage/posts/', $post['image_url']);
    }

    /** @test */
    public function it_accepts_various_image_formats()
    {
        Storage::fake('public');

        $formats = ['jpg', 'jpeg', 'png', 'gif'];
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        foreach ($formats as $format) {
            $image = UploadedFile::fake()->create("test.{$format}", 100, $mimeTypes[$format]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/posts', [
                    'content' => "Post with {$format} image",
                    'image' => $image,
                ]);

            $response->assertStatus(201);

            $post = Post::where('content', "Post with {$format} image")->first();
            $this->assertNotNull($post);
            $this->assertNotNull($post->image_path);
            $this->assertTrue(Storage::disk('public')->exists($post->image_path));
        }
    }
}

