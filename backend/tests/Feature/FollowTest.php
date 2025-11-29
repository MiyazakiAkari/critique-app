<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
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
    public function it_can_follow_a_user()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/users/otheruser/follow');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'フォローしました',
                'is_following' => true,
            ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->otherUser->id,
        ]);
    }

    /** @test */
    public function it_cannot_follow_self()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/users/testuser/follow');

        $response->assertStatus(400)
            ->assertJson([
                'message' => '自分自身をフォローすることはできません',
            ]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_cannot_follow_twice()
    {
        // 最初のフォロー
        $this->user->followings()->attach($this->otherUser->id);

        // 2回目のフォロー試行
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/users/otheruser/follow');

        $response->assertStatus(400)
            ->assertJson([
                'message' => '既にフォローしています',
            ]);
    }

    /** @test */
    public function it_can_unfollow_a_user()
    {
        // 先にフォロー
        $this->user->followings()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/users/otheruser/follow');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'フォローを解除しました',
                'is_following' => false,
            ]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->otherUser->id,
        ]);
    }

    /** @test */
    public function it_cannot_unfollow_if_not_following()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/users/otheruser/follow');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'フォローしていません',
            ]);
    }

    /** @test */
    public function it_can_get_followers_list()
    {
        // otherUserをuserがフォロー
        $this->user->followings()->attach($this->otherUser->id);

        $response = $this->getJson('/api/users/otheruser/followers');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 1,
                'followers' => [
                    [
                        'id' => $this->user->id,
                        'name' => 'Test User',
                        'username' => 'testuser',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_followings_list()
    {
        // userがotherUserをフォロー
        $this->user->followings()->attach($this->otherUser->id);

        $response = $this->getJson('/api/users/testuser/followings');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 1,
                'followings' => [
                    [
                        'id' => $this->otherUser->id,
                        'name' => 'Other User',
                        'username' => 'otheruser',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_follow_status()
    {
        // userがotherUserをフォロー
        $this->user->followings()->attach($this->otherUser->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/users/otheruser/follow-status');

        $response->assertStatus(200)
            ->assertJson([
                'is_following' => true,
                'followers_count' => 1,
                'followings_count' => 0,
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_follow()
    {
        $response = $this->postJson('/api/users/otheruser/follow');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_unfollow()
    {
        $response = $this->deleteJson('/api/users/otheruser/follow');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_user()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/users/nonexistentuser/follow');

        $response->assertStatus(404);
    }
}
