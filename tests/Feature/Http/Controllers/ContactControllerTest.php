<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\ContactCriteria;
use App\Enums\ContactType;
use App\Enums\RouteName;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function __;
use function route;
use function str_contains;

class ContactControllerTest extends FeatureTestCase
{
    public function testCreate(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_CREATE, ['department' => $department])
            ->assertOk()
            ->assertViewIs('contacts.create');
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, ['department' => $department, 'contact' => $contact])
            ->assertOk()
            ->assertViewIs('contacts.edit');
    }

    public function testEditNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, ['department' => $department, 'contact' => $this->faker->uuid()])
            ->assertNotFound();
    }

    public function testIndex(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertViewIs('contacts.index');
    }

    public function testIndexWithPagination(): void
    {
        $department = Department::factory()->create();

        $pageLength = 2;
        ConfigHelper::set('app.pagination.contact_items_per_page', $pageLength);
        Contact::factory()
            ->recycle($department)
            ->count($pageLength + 1)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $department, 'page' => 2])
            ->assertOk()
            ->assertViewIs('contacts.index');
    }

    public function testShow(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $department, 'contact' => $contact])
            ->assertOk()
            ->assertViewIs('contacts.show');
    }

    public function testIndexMustHaveValidUuidInRoute(): void
    {
        $department = Department::factory()->create();
        $notUuid = $this->faker()->word();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $department, 'contact' => $notUuid])
            ->assertStatus(404);
    }

    public function testShowNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $department, 'contact' => $this->faker->uuid()])
            ->assertNotFound();
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    #[DataProvider('crossDepartmentAccessProvider')]
    public function testCrossDepartmentAccessReturnsNotFound(string $method, RouteName $routeName, array $additionalData = []): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $contact = Contact::factory()
            ->recycle($otherDepartment)
            ->create();

        $permission = str_contains($routeName->value, 'SHOW') ? Permission::CONTACT_READ : Permission::CONTACT_WRITE;
        $authUser = User::factory()->withPermissions($permission)->fullyVerified()->create();

        if ($method === 'GET') {
            $this->beUser($authUser)
                ->getByRoute($routeName, ['department' => $department, 'contact' => $contact])
                ->assertNotFound();
        } else {
            $this->beUser($authUser)
                ->postByRoute($routeName, ['department' => $department, 'contact' => $contact], $additionalData)
                ->assertNotFound();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function crossDepartmentAccessProvider(): array
    {
        return [
            'show contact from different department' => [
                'GET',
                RouteName::DEPARTMENTS_CONTACTS_SHOW,
            ],
            'edit contact from different department' => [
                'GET',
                RouteName::DEPARTMENTS_CONTACTS_EDIT,
            ],
            'update contact from different department' => [
                'POST',
                RouteName::DEPARTMENTS_CONTACTS_EDIT,
                ['last_name' => 'TestName', 'type' => ContactType::REPRESENTATIVE->value],
            ],
            'archive contact from different department' => [
                'POST',
                RouteName::DEPARTMENTS_CONTACTS_ARCHIVE_STORE,
            ],
        ];
    }

    public function testStore(): void
    {
        $department = Department::factory()->create();
        $lastName = $this->faker->lastName();
        $type = $this->faker->randomElement(ContactType::cases());

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::CONTACT_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_CREATE, ['department' => $department], [
                'last_name' => $lastName,
                'type' => $type->value,
            ]);

        $contact = Contact::where('last_name', $lastName)->where('type', $type)->firstOrFail();

        $response->assertRedirect(route(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
            'department' => $department,
            'contact' => $contact->id,
        ]));

        $this->assertDatabaseHas(Contact::class, [
            'last_name' => $lastName,
            'type' => $type,
        ]);
    }

    public function testStoreWithNotes(): void
    {
        $department = Department::factory()->create();
        $lastName = $this->faker->lastName();
        $type = $this->faker->randomElement(ContactType::cases());
        $notes = $this->faker->sentence();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::CONTACT_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_CREATE, ['department' => $department], [
                'last_name' => $lastName,
                'type' => $type->value,
                'notes' => $notes,
            ]);

        $contact = Contact::where('last_name', $lastName)->where('type', $type)->firstOrFail();

        $response->assertRedirectToRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
            'department' => $department,
            'contact' => $contact->id,
        ]);

        $this->assertDatabaseHas(Contact::class, [
            'last_name' => $lastName,
            'type' => $type,
            'notes' => $notes,
        ]);
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()
            ->recycle($department)
            ->create();

        $lastName = $this->faker->lastName();
        $type = $this->faker->randomElement(ContactType::cases());

        $authUser = User::factory()->withPermissions(Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, [
                'department' => $department,
                'contact' => $contact,
            ], [
                'last_name' => $lastName,
                'type' => $type->value,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
                'department' => $department,
                'contact' => $contact,
            ]);

        $this->assertDatabaseHas(Contact::class, [
            'last_name' => $lastName,
            'type' => $type,
        ]);
    }

    public function testUpdateWithNotes(): void
    {
        $department = Department::factory()->create();
        $contact = Contact::factory()->recycle($department)->create();
        $lastName = $this->faker->lastName();
        $type = $this->faker->randomElement(ContactType::cases());
        $notes = $this->faker->sentence();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_EDIT, [
                'department' => $department,
                'contact' => $contact,
            ], [
                'last_name' => $lastName,
                'type' => $type->value,
                'notes' => $notes,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
                'department' => $department,
                'contact' => $contact,
            ]);

        $this->assertDatabaseHas(Contact::class, [
            'last_name' => $lastName,
            'type' => $type,
            'notes' => $notes,
        ]);
    }

    public function testIndexSeeOnlyDepartmentContacts(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        Contact::factory()
            ->recycle($departmentA)
            ->create(['last_name' => 'Contact in Department A']);

        Contact::factory()
            ->recycle($departmentB)
            ->create(['last_name' => 'Contact in Department B']);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($departmentA, Permission::CONTACT_READ, Permission::CONTACT_MANAGE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $departmentA)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $departmentA])
            ->assertOk()
            ->assertSee('Contact in Department A')
            ->assertDontSee('Contact in Department B');
    }

    public function testFilterRedirectsToIndex(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::CONTACT_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX_FILTER, [
                'department' => $department,
                ContactCriteria::SEARCH->value => $this->faker->word(),
            ])
            ->assertRedirect(route(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $department]));
    }

    public function testIndexWithSearchFilterShowsOnlyDepartmentContacts(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $searchTerm = Str::random(8); // Unique search term

        // Create contacts with matching search term in different departments
        $contact1 = Contact::factory()->recycle($department1)->create([
            'last_name' => 'Doe' . $searchTerm . 'Dept1',
        ]);
        $contact2 = Contact::factory()->recycle($department2)->create([
            'last_name' => 'Smith' . $searchTerm . 'Dept2',
        ]);

        $contact3 = Contact::factory()->recycle($department1)->create([
            'last_name' => 'WilsonOther',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department1,
            Permission::CONTACT_READ,
            Permission::CONTACT_MANAGE,
        )->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, [
                'department' => $department1->slug,
                'filter' => [
                    ContactCriteria::SEARCH->value => $searchTerm,
                ],
            ]);

        $response->assertOk()
            ->assertSee($contact1->last_name)
            ->assertDontSee($contact2->last_name)
            ->assertDontSee($contact3->last_name);
    }

    public function testIndexWithSearchFilterRespectsDepartmentIsolationDept1(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $searchTerm = 'CommonSearchTerm' . Str::random(5);

         Contact::factory()->recycle($department1)->create([
             'last_name' => $searchTerm . ' Department1',
         ]);
        Contact::factory()->recycle($department2)->create([
            'last_name' => $searchTerm . ' Department2',
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department1, Permission::CONTACT_READ, Permission::CONTACT_MANAGE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, [
                'department' => $department1->slug,
                'filter' => [
                    ContactCriteria::SEARCH->value => $searchTerm,
                ],
            ]);

        $response->assertOk()
            ->assertSee('Department1')
            ->assertDontSee('Department2');
    }

    public function testIndexWithSearchFilterRespectsDepartmentIsolationDept2(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $searchTerm = 'CommonSearchTerm' . Str::random(5);

         Contact::factory()->recycle($department1)->create([
             'last_name' => $searchTerm . ' Department1',
         ]);
         Contact::factory()->recycle($department2)->create([
             'last_name' => $searchTerm . ' Department2',
         ]);

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department2,
            Permission::CONTACT_READ,
            Permission::CONTACT_MANAGE,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department2)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, [
                'department' => $department2->slug,
                'filter' => [
                    ContactCriteria::SEARCH->value => $searchTerm,
                ],
            ])
            ->assertOk()
            ->assertSee('Department2')
            ->assertDontSee('Department1');
    }

    public function testIndexSeeNothingWHenNoAdmin(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        Contact::factory()
            ->recycle($departmentA)
            ->create(['last_name' => 'Contact in Department A']);

        Contact::factory()
            ->recycle($departmentB)
            ->create(['last_name' => 'Contact in Department B']);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($departmentA, Permission::CONTACT_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $departmentA)
            ->getByRoute(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $departmentA])
            ->assertOk()
            ->assertDontSee('Contact in Department B')
            ->assertSee(__('contact.no_permission_to_view_contacts'));
    }
}
