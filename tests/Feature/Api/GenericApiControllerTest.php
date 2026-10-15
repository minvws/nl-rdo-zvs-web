<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ContactRole;
use App\Enums\TagColor;
use App\Models\ApiUser;
use App\Models\Contact;
use App\Models\CustomCost;
use App\Models\CustomPetitionProperty;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionCustomDate;
use App\Models\PetitionDeliverable;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionExternalUrl;
use App\Models\PetitionQuerysnapshot;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\ProcessingStep;
use App\Models\PublicHoliday;
use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function array_diff_key;
use function array_map;
use function sprintf;

class GenericApiControllerTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $apiUser = ApiUser::factory()->create();
        Sanctum::actingAs($apiUser);
    }

    public function testIndexEndpointReturnsUsers(): void
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
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

    public function testFieldSelectionWorks(): void
    {
        User::factory()->create();

        $response = $this->getJson('/api/v1/users?fields=id,name');

        $response->assertStatus(200);

        $userData = $response->json('data.0');
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('name', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayNotHasKey('password', $userData);
    }

    public function testPaginationWorks(): void
    {
        User::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/users?per_page=5');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonPath('pagination.total', 20);
    }

    public function testInvalidModelReturns404(): void
    {
        $response = $this->getJson('/api/v1/invalid-model');

        $response->assertStatus(404)
            ->assertJsonPath('error', 'Table not found');
    }

    public function testFilteringByCreatedAfter(): void
    {
        User::factory()->create(['created_at' => '2023-01-01']);
        $newUser = User::factory()->create(['created_at' => '2024-01-01']);

        $response = $this->getJson('/api/v1/users?created_at_after=2023-06-01');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($newUser->id, $data[0]['id']);
    }

    public function testFilteringByCreatedAtAfter(): void
    {
        User::factory()->create(['created_at' => '2023-01-01']);
        $newUser = User::factory()->create(['created_at' => '2024-01-01']);

        $response = $this->getJson('/api/v1/users?created_at_after=2023-06-01');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($newUser->id, $data[0]['id']);
    }

    public function testFilteringByCreatedAtBefore(): void
    {
        $oldUser = User::factory()->create(['created_at' => '2023-01-01']);
        User::factory()->create(['created_at' => '2024-01-01']);

        $response = $this->getJson('/api/v1/users?created_at_before=2023-06-01');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($oldUser->id, $data[0]['id']);
    }

    public function testPetitionPetitionEndpointExists(): void
    {
        // This test just verifies the endpoint is configured and accessible
        // It may return empty data, which is fine for testing the configuration
        $response = $this->getJson('/api/v1/petition_petition');

        // Should not return 404 (table not found), meaning it's properly configured
        $this->assertNotEquals(404, $response->status());

        // If it returns 200, verify the structure
        if ($response->status() !== 200) {
            return;
        }

        $response->assertJsonStructure([
            'data',
            'pagination',
            'meta',
        ]);
    }

    public function testFilteringPetitionAssignmentsByPetitionId(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();
        PetitionAssignment::create([
            'petition_id' => $petition->id,
            'user_id' => $user->id,
            'assignment_role' => 1,
        ]);

        // Create another assignment for a different petition
        $otherPetition = Petition::factory()->create();
        $otherUser = User::factory()->create();
        PetitionAssignment::create([
            'petition_id' => $otherPetition->id,
            'user_id' => $otherUser->id,
            'assignment_role' => 1,
        ]);

        $response = $this->getJson(sprintf("/api/v1/petition_assignments?petition_id=%s", $petition->id));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($petition->id, $data[0]['petition_id']);
        $this->assertEquals($user->id, $data[0]['user_id']);
    }

    public function testFilteringPetitionAssignmentsByUserId(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();
        PetitionAssignment::create([
            'petition_id' => $petition->id,
            'user_id' => $user->id,
            'assignment_role' => 1,
        ]);

        // Create another assignment for the same user but different petition
        $otherPetition = Petition::factory()->create();
        PetitionAssignment::create([
            'petition_id' => $otherPetition->id,
            'user_id' => $user->id,
            'assignment_role' => 2,
        ]);

        $response = $this->getJson(sprintf("/api/v1/petition_assignments?user_id=%s", $user->id));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        // Verify all results have the correct user_id
        foreach ($data as $assignment) {
            $this->assertEquals($user->id, $assignment['user_id']);
        }
    }

    public function testContactPetitionEndpointReturnsFirstPhoneContactDate(): void
    {
        $petition = Petition::factory()->create();
        $contact = Contact::factory()->recycle($petition->department)->create();

        $petition->contacts()->attach($contact, [
            'role' => ContactRole::APPLICANT->value,
            'date_first_phone_contact' => '2026-04-09',
        ]);

        $response = $this->getJson('/api/v1/contact_petition')->assertOk();

        $this->assertSame('2026-04-09', $response->json('data.0.date_first_phone_contact'));
    }

    public function testPetitionsEndpointReturnsTheIgsForfeited(): void
    {
        Petition::factory()->create(['igs_forfeited' => 1500]);

        $response = $this->getJson('/api/v1/petitions?fields=id,igs_forfeited')->assertOk();

        $this->assertSame(1500, $response->json('data.0.igs_forfeited'));
    }

    public function testTagsEndpointReturnsActiveAndInactiveTags(): void
    {
        $tag = Tag::factory()->create(['color' => TagColor::ORANGE, 'description' => 'Komt steeds terug']);
        Tag::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/tags')->assertOk();

        $response->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'department_id', 'name', 'color', 'description', 'active', 'created_at', 'updated_at'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertContains(
            [
                'id' => $tag->id->toString(),
                'department_id' => (string) $tag->department_id,
                'name' => $tag->name,
                'color' => TagColor::ORANGE->value,
                'description' => 'Komt steeds terug',
                'active' => true,
            ],
            array_map(static fn(array $row): array => array_diff_key($row, ['created_at' => 0, 'updated_at' => 0]), $data),
        );
    }

    public function testFilteringPetitionTagByTagId(): void
    {
        $petition = Petition::factory()->create();
        $tag = Tag::factory()->recycle($petition->department)->create();
        $otherTag = Tag::factory()->recycle($petition->department)->create();
        $petition->tags()->attach([$tag->id->toString(), $otherTag->id->toString()]);

        $response = $this->getJson(sprintf('/api/v1/petition_tag?tag_id=%s', $tag->id))->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.petition_id', $petition->id->toString())
            ->assertJsonPath('data.0.tag_id', $tag->id->toString());
    }

    public function testFilteringPetitionTagByPetitionId(): void
    {
        $petition = Petition::factory()->create();
        $otherPetition = Petition::factory()->recycle($petition->department)->create();
        $tag = Tag::factory()->recycle($petition->department)->create();
        $petition->tags()->attach($tag);
        $otherPetition->tags()->attach($tag);

        $response = $this->getJson(sprintf('/api/v1/petition_tag?petition_id=%s', $petition->id))->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.petition_id', $petition->id->toString());
    }

    #[DataProvider('genericApiEndpoints')]
    public function testPetitionEndpointsAccessible(string $endpoint, ?string $model = null): void
    {
        if ($model) {
            $model::factory()->create();
        }

        $response = $this->getJson($endpoint)->assertOk();
        $response->assertJsonStructure([
            'data',
            'pagination',
            'meta',
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function genericApiEndpoints(): array
    {
        return [
            ['/api/v1/petition_deliverables', PetitionDeliverable::class],
            ['/api/v1/petition_draft_terms', PetitionDraftTerm::class],
            ['/api/v1/petition_statuses', PetitionStatus::class],
            ['/api/v1/petitions', Petition::class],
            ['/api/v1/petition_types', PetitionType::class],
            ['/api/v1/petition_custom_properties', CustomPetitionProperty::class],
            ['/api/v1/petition_custom_costs', CustomCost::class],
            ['/api/v1/petition_custom_dates', PetitionCustomDate::class],
            ['/api/v1/petition_assignments', PetitionAssignment::class],
            ['/api/v1/decision_petition'],
            ['/api/v1/public_holidays', PublicHoliday::class],
            ['/api/v1/processing_steps', ProcessingStep::class],
            ['/api/v1/policy_departments', PolicyDepartment::class],
            ['/api/v1/custom_petition_properties_definitions'],
            ['/api/v1/petition_external_urls', PetitionExternalUrl::class],
            ['/api/v1/petition_policy_department'],
            ['/api/v1/petition_querysnapshots', PetitionQuerysnapshot::class],
            ['/api/v1/decisions', Decision::class],
        ];
    }
}
