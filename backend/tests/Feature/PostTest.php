<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'This is a test post',
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
}
