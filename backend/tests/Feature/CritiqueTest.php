<?php

namespace Tests\Feature;

use App\Models\Critique;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CritiqueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿への添削一覧が取得できる
     */
    public function test_can_get_critiques_for_post(): void
    {
        $post = Post::factory()->create();
        $user1 = User::factory()->create(['username' => 'user1', 'name' => 'User One']);
        $user2 = User::factory()->create(['username' => 'user2', 'name' => 'User Two']);

        // 時系列順にテストするため、明示的にタイムスタンプを設定
        $critique1 = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user1->id,
            'content' => 'First critique',
            'created_at' => now()->subHours(2),
        ]);
        $critique2 = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user2->id,
            'content' => 'Second critique',
            'created_at' => now()->subHours(1),
        ]);

        $response = $this->getJson("/api/posts/{$post->id}/critiques");

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $critique1->id)
            ->assertJsonPath('0.content', 'First critique')
            ->assertJsonPath('0.user.id', $user1->id)
            ->assertJsonPath('0.user.username', 'user1')
            ->assertJsonPath('0.user.name', 'User One')
            ->assertJsonPath('1.id', $critique2->id)
            ->assertJsonPath('1.content', 'Second critique')
            ->assertJsonPath('1.user.id', $user2->id);
    }

    /**
     * 他の投稿の添削は含まれない
     */
    public function test_critiques_are_filtered_by_post(): void
    {
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        Critique::factory()->count(3)->create(['post_id' => $post1->id]);
        Critique::factory()->count(2)->create(['post_id' => $post2->id]);

        $response = $this->getJson("/api/posts/{$post1->id}/critiques");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * 認証済みユーザーは添削を作成できる
     */
    public function test_authenticated_user_can_create_critique(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques", [
                'content' => 'This is my critique',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('content', 'This is my critique')
            ->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseHas('critiques', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'This is my critique',
        ]);
    }

    /**
     * 未認証ユーザーは添削を作成できない
     */
    public function test_unauthenticated_user_cannot_create_critique(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/critiques", [
            'content' => 'This is my critique',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 添削のcontentは必須
     */
    public function test_critique_content_is_required(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques", [
                'content' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * 添削のcontentは1000文字まで
     */
    public function test_critique_content_has_max_length(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques", [
                'content' => str_repeat('a', 1001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /**
     * ユーザーは自分の添削を削除できる
     */
    public function test_user_can_delete_own_critique(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('critiques', [
            'id' => $critique->id,
        ]);
    }

    /**
     * ユーザーは他人の添削を削除できない
     */
    public function test_user_cannot_delete_others_critique(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('critiques', [
            'id' => $critique->id,
        ]);
    }

    /**
     * 投稿の作成者は他人の添削を削除できない
     */
    public function test_post_owner_cannot_delete_others_critique(): void
    {
        $postOwner = User::factory()->create();
        $critiqueAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postOwner->id]);
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        $response = $this->actingAs($postOwner, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('critiques', [
            'id' => $critique->id,
        ]);
    }

    /**
     * 未認証ユーザーは添削を削除できない
     */
    public function test_unauthenticated_user_cannot_delete_critique(): void
    {
        $post = Post::factory()->create();
        $critique = Critique::factory()->create(['post_id' => $post->id]);

        $response = $this->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}");

        $response->assertStatus(401);
    }

    /**
     * 投稿が削除されると、その投稿の添削も削除される（カスケード）
     */
    public function test_critiques_are_deleted_when_post_is_deleted(): void
    {
        $post = Post::factory()->create();
        $critique1 = Critique::factory()->create(['post_id' => $post->id]);
        $critique2 = Critique::factory()->create(['post_id' => $post->id]);

        $post->delete();

        $this->assertDatabaseMissing('critiques', ['id' => $critique1->id]);
        $this->assertDatabaseMissing('critiques', ['id' => $critique2->id]);
    }
}
