<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_users_matching_name_or_username_partially()
    {
        $usernameMatch = User::factory()->create([
            'name' => 'Alice Example',
            'username' => 'alice123',
        ]);
        $nameMatch = User::factory()->create([
            'name' => 'Super Bob',
            'username' => 'bob456',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Charlie Example',
            'username' => 'charlie789',
        ]);

        // username にマッチ
        $response = $this->getJson('/api/users/search?keyword=ali');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 1,
                'users' => [[
                    'id' => $usernameMatch->id,
                    'name' => 'Alice Example',
                    'username' => 'alice123',
                ]],
            ])
            ->assertJsonMissing([
                'username' => $otherUser->username,
            ]);

        // name にマッチ
        $response = $this->getJson('/api/users/search?keyword=Super');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 1,
                'users' => [[
                    'id' => $nameMatch->id,
                    'name' => 'Super Bob',
                    'username' => 'bob456',
                ]],
            ])
            ->assertJsonMissing([
                'username' => $otherUser->username,
            ]);
    }

    /** @test */
    public function it_returns_empty_list_when_no_users_match()
    {
        User::factory()->create([
            'name' => 'Charlie Example',
            'username' => 'charlie789',
        ]);

        $response = $this->getJson('/api/users/search?keyword=zzz');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 0,
                'users' => [],
            ]);
    }

    /** @test */
    public function it_validates_keyword_query_parameter()
    {
        $response = $this->getJson('/api/users/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword']);
    }
}

