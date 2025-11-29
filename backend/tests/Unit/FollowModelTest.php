<?php

namespace Tests\Unit;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function follow_belongs_to_follower_user()
    {
        $follower = User::factory()->create();
        $following = User::factory()->create();

        $follow = Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        $this->assertInstanceOf(User::class, $follow->follower);
        $this->assertEquals($follower->id, $follow->follower->id);
    }

    /** @test */
    public function follow_belongs_to_following_user()
    {
        $follower = User::factory()->create();
        $following = User::factory()->create();

        $follow = Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        $this->assertInstanceOf(User::class, $follow->following);
        $this->assertEquals($following->id, $follow->following->id);
    }

    /** @test */
    public function user_has_many_followings()
    {
        $user = User::factory()->create();
        $following1 = User::factory()->create();
        $following2 = User::factory()->create();

        $user->followings()->attach([$following1->id, $following2->id]);

        $this->assertCount(2, $user->followings);
        $this->assertTrue($user->followings->contains($following1));
        $this->assertTrue($user->followings->contains($following2));
    }

    /** @test */
    public function user_has_many_followers()
    {
        $user = User::factory()->create();
        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();

        $follower1->followings()->attach($user->id);
        $follower2->followings()->attach($user->id);

        $this->assertCount(2, $user->followers);
        $this->assertTrue($user->followers->contains($follower1));
        $this->assertTrue($user->followers->contains($follower2));
    }

    /** @test */
    public function is_following_returns_true_when_following()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->followings()->attach($otherUser->id);

        $this->assertTrue($user->isFollowing($otherUser));
    }

    /** @test */
    public function is_following_returns_false_when_not_following()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($user->isFollowing($otherUser));
    }

    /** @test */
    public function follow_is_deleted_when_follower_is_deleted()
    {
        $follower = User::factory()->create();
        $following = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        $follower->delete();

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);
    }

    /** @test */
    public function follow_is_deleted_when_following_is_deleted()
    {
        $follower = User::factory()->create();
        $following = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        $following->delete();

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);
    }
}
