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
     * 画像付きの添削を作成できる
     */
    public function test_authenticated_user_can_create_critique_with_image(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $image = \Illuminate\Http\Testing\File::image('test.png');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques", [
                'content' => 'Critique with image',
                'image' => $image,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('content', 'Critique with image')
            ->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseHas('critiques', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Critique with image',
        ]);

        $critique = Critique::where('content', 'Critique with image')->first();
        $this->assertNotNull($critique->image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($critique->image_path);
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

    /**
     * 認証済みユーザーは添削にいいねできる
     */
    public function test_authenticated_user_can_like_critique(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critiqueAuthor = User::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(201)
            ->assertJsonPath('is_liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('critique_likes', [
            'critique_id' => $critique->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * ユーザーは自分の添削にいいねできない
     */
    public function test_user_cannot_like_own_critique(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(403)
            ->assertJsonPath('message', '自分の添削にはいいねできません');

        $this->assertDatabaseMissing('critique_likes', [
            'critique_id' => $critique->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * ユーザーは同じ添削に2回いいねできない
     */
    public function test_user_cannot_like_critique_twice(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critiqueAuthor = User::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        // 1回目のいいね
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        // 2回目のいいね
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(409)
            ->assertJsonPath('message', '既にいいねしています');
    }

    /**
     * 認証済みユーザーはいいねを解除できる
     */
    public function test_authenticated_user_can_unlike_critique(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critiqueAuthor = User::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        // まずいいねする
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        // いいね解除
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(200)
            ->assertJsonPath('is_liked', false)
            ->assertJsonPath('likes_count', 0);

        $this->assertDatabaseMissing('critique_likes', [
            'critique_id' => $critique->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * いいねしていない添削のいいねを解除しようとすると404
     */
    public function test_cannot_unlike_critique_not_liked(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critique = Critique::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'いいねしていません');
    }

    /**
     * 未認証ユーザーは添削にいいねできない
     */
    public function test_unauthenticated_user_cannot_like_critique(): void
    {
        $post = Post::factory()->create();
        $critique = Critique::factory()->create(['post_id' => $post->id]);

        $response = $this->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(401);
    }

    /**
     * 未認証ユーザーはいいねを解除できない
     */
    public function test_unauthenticated_user_cannot_unlike_critique(): void
    {
        $post = Post::factory()->create();
        $critique = Critique::factory()->create(['post_id' => $post->id]);

        $response = $this->deleteJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $response->assertStatus(401);
    }

    /**
     * 添削一覧取得時にいいね数といいね状態が含まれる
     */
    public function test_critiques_include_likes_count_and_is_liked(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $critiqueAuthor = User::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        // いいねする
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        // 添削一覧を取得（認証済み）
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/posts/{$post->id}/critiques");

        $response->assertStatus(200)
            ->assertJsonPath('0.likes_count', 1)
            ->assertJsonPath('0.is_liked', true);
    }

    /**
     * 未認証時の添削一覧ではis_likedはfalse
     */
    public function test_critiques_show_is_liked_false_for_unauthenticated(): void
    {
        $critiqueAuthor = User::factory()->create();
        $post = Post::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        // 他のユーザーがいいね
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        // 未認証で添削一覧を取得（明示的にゲストとして実行）
        $this->app->get('auth')->forgetGuards();
        $response = $this->getJson("/api/posts/{$post->id}/critiques");

        $response->assertStatus(200)
            ->assertJsonPath('0.likes_count', 1)
            ->assertJsonPath('0.is_liked', false);
    }

    /**
     * 添削が削除されるといいねも削除される（カスケード）
     */
    public function test_likes_are_deleted_when_critique_is_deleted(): void
    {
        $user = User::factory()->create();
        $critiqueAuthor = User::factory()->create();
        $post = Post::factory()->create();
        $critique = Critique::factory()->create([
            'post_id' => $post->id,
            'user_id' => $critiqueAuthor->id,
        ]);

        // いいねする
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/critiques/{$critique->id}/like");

        $this->assertDatabaseHas('critique_likes', [
            'critique_id' => $critique->id,
        ]);

        // 添削を削除
        $critique->delete();

        $this->assertDatabaseMissing('critique_likes', [
            'critique_id' => $critique->id,
        ]);
    }
}
