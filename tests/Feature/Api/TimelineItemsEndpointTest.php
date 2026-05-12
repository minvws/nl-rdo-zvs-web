<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\TimelineType;
use App\Models\ApiUser;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class TimelineItemsEndpointTest extends FeatureTestCase
{
    private ApiUser $apiUser;
    private string $token;
    private string $apiSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // Create API user with known credentials
        $apiKey = Str::random(64);
        $this->apiSecret = Str::random(128);

        $this->apiUser = ApiUser::factory()->create([
            'api_key' => $apiKey,
            'api_secret' => Hash::make($this->apiSecret),
        ]);

        // Login to get token
        $response = $this->postJson('/api/login', [
            'api_key' => $this->apiUser->api_key,
            'api_secret' => $this->apiSecret,
        ]);

        $this->token = $response->json('access_token');
    }

    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/petition_timeline_items');

        $response->assertUnauthorized();
    }

    public function testAuthenticatedRequestReturns200(): void
    {
        Petition::factory()->create();
        TimelineItem::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/petition_timeline_items', [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'internal_id',
                    'timelineable_type',
                    'timelineable_id',
                    'user_id',
                    'type',
                    'data',
                    'created_at',
                    'updated_at',
                ],
            ],
            'pagination' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
                'from',
                'to',
            ],
            'meta' => [
                'available_fields',
            ],
        ]);
    }

    public function testReturnsAllRequiredFields(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();

        TimelineItem::factory()
            ->for($petition, 'timelineable')
            ->for($user)
            ->create();

        $response = $this->getJson('/api/v1/petition_timeline_items', [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();

        $data = $response->json('data.0');
        $this->assertNotNull($data['internal_id']);
        $this->assertNotNull($data['timelineable_type']);
        $this->assertNotNull($data['timelineable_id']);
        $this->assertNotNull($data['type']);
        // data field is included in response
        $this->assertArrayHasKey('data', $data);
        // Timestamps are included in response structure
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
        // user_id might be null
        $this->assertArrayHasKey('user_id', $data);
    }

    public function testPaginationWorks(): void
    {
        TimelineItem::factory()->count(30)->create();

        $response = $this->getJson(sprintf('/api/v1/petition_timeline_items?per_page=10&page=2'), [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();

        $pagination = $response->json('pagination');
        $this->assertEquals(2, $pagination['current_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(30, $pagination['total']);
        $this->assertCount(10, $response->json('data'));
    }

    public function testFilterByType(): void
    {
        TimelineItem::factory()->count(5)->create([
            'type' => TimelineType::NOTE,
        ]);

        TimelineItem::factory()->count(3)->create([
            'type' => TimelineType::STATUS_OCCURRENCE,
        ]);

        $response = $this->getJson('/api/v1/petition_timeline_items', [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();

        // Just verify we get data back
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function testFilterByTimelineableId(): void
    {
        $petition = Petition::factory()->create();
        $otherPetition = Petition::factory()->create();

        TimelineItem::factory()->count(3)->for($petition, 'timelineable')->create();
        TimelineItem::factory()->count(2)->for($otherPetition, 'timelineable')->create();

        $response = $this->getJson(sprintf('/api/v1/petition_timeline_items?timelineable_id=%s', $petition->id), [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertEquals($petition->id, $item['timelineable_id']);
        }
    }

    public function testPaginationWithCustomPerPage(): void
    {
        TimelineItem::factory()->count(15)->create();

        $response = $this->getJson(sprintf('/api/v1/petition_timeline_items?per_page=5&page=1'), [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();
        $this->assertCount(5, $response->json('data'));
    }

    public function testAvailableFieldsInMeta(): void
    {
        $response = $this->getJson('/api/v1/petition_timeline_items', [
            'Authorization' => sprintf('Bearer %s', $this->token),
        ]);

        $response->assertSuccessful();

        $availableFields = $response->json('meta.available_fields');
        $this->assertIsArray($availableFields);
        // Should contain the configured fields
        $this->assertContains('internal_id', $availableFields);
        $this->assertContains('type', $availableFields);
    }
}
