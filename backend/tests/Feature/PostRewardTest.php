<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PostRewardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_post_without_reward()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post without reward',
                'image' => $image,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => '投稿しました',
                'post' => [
                    'content' => 'Post without reward',
                    'reward_amount' => 0,
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'content' => 'Post without reward',
            'reward_amount' => 0,
            'stripe_payment_intent_id' => null,
        ]);
    }

    /** @test */
    public function it_can_create_post_with_reward_amount_only()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        // payment_method_id なしで reward_amount のみ設定
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with reward amount only',
                'image' => $image,
                'reward_amount' => 500,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'post' => [
                    'content' => 'Post with reward amount only',
                    'reward_amount' => 500,
                ],
            ]);

        // payment_method_id がないので stripe_payment_intent_id は null
        $this->assertDatabaseHas('posts', [
            'content' => 'Post with reward amount only',
            'reward_amount' => 500,
            'stripe_payment_intent_id' => null,
        ]);
    }

    /** @test */
    public function it_validates_minimum_reward_amount()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        // 100円未満は拒否される
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with low reward',
                'image' => $image,
                'reward_amount' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reward_amount']);
    }

    /** @test */
    public function it_validates_maximum_reward_amount()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        // 10000円超は拒否される
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with high reward',
                'image' => $image,
                'reward_amount' => 15000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reward_amount']);
    }

    /** @test */
    public function it_allows_zero_reward_amount()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with zero reward',
                'image' => $image,
                'reward_amount' => 0,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'post' => [
                    'reward_amount' => 0,
                ],
            ]);
    }

    /** @test */
    public function it_allows_null_reward_amount()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Post with null reward',
                'image' => $image,
                'reward_amount' => null,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_allows_valid_reward_amounts()
    {
        Storage::fake('public');
        
        $validAmounts = [100, 500, 1000, 5000, 10000];

        foreach ($validAmounts as $amount) {
            $image = UploadedFile::fake()->create("test_{$amount}.jpg", 100, 'image/jpeg');

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/posts', [
                    'content' => "Post with {$amount} yen reward",
                    'image' => $image,
                    'reward_amount' => $amount,
                ]);

            $response->assertStatus(201)
                ->assertJson([
                    'post' => [
                        'reward_amount' => $amount,
                    ],
                ]);
        }
    }

    /** @test */
    public function posts_with_reward_are_returned_in_timeline()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Rewarded post',
            'reward_amount' => 1000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/posts/recommended');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'reward_amount' => 1000,
            ]);
    }

    /** @test */
    public function posts_with_reward_are_returned_in_recommended()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Rewarded post in recommended',
            'reward_amount' => 500,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/posts/recommended');

        $response->assertStatus(200);
        
        $posts = $response->json('posts');
        $this->assertNotEmpty($posts);
        
        $foundPost = collect($posts)->firstWhere('id', $post->id);
        $this->assertNotNull($foundPost);
        $this->assertEquals(500, $foundPost['reward_amount']);
    }

    /** @test */
    public function it_requires_authentication_to_create_rewarded_post()
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/api/posts', [
            'content' => 'Unauthorized post',
            'image' => $image,
            'reward_amount' => 500,
        ]);

        $response->assertStatus(401);
    }
}
