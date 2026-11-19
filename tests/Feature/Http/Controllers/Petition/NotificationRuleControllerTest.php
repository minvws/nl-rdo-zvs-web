<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\NotificationRuleAnchor;
use App\Enums\NotificationRuleTiming;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\NotificationRule;
use App\Models\NotificationRuleDelivery;
use App\Models\PetitionType;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Feature\FeatureTestCase;
use Webmozart\Assert\Assert;

use function __;
use function array_merge;
use function html_entity_decode;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_quote;
use function route;
use function sprintf;

use const PHP_URL_QUERY;

class NotificationRuleControllerTest extends FeatureTestCase
{
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
        $this->department = Department::factory()->create();
        PetitionType::factory()->recycle($this->department)->create(['type' => PetitionVariant::WOO_VERZOEK]);
    }

    #[Test]
    public function theIndexListsTheRulesOfTheDepartment(): void
    {
        NotificationRule::factory()->recycle($this->department)->create([
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
            'anchor' => NotificationRuleAnchor::EVENT_DATE,
            'offset_days' => 14,
        ]);
        NotificationRule::factory()->create(['event_type' => PetitionEventType::PRIMARY_DECISION]);

        $this->beUser($this->admin())
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department])
            ->assertOk()
            ->assertViewIs('notification-rules.index')
            ->assertSeeInOrder([
                PetitionEventType::ACTUAL_DISCLOSURE->label(PetitionVariant::WOO_VERZOEK),
                'Vindt plaats',
                '14 dagen',
                'Na',
                __('general.yes'),
            ])
            ->assertDontSee(PetitionEventType::PRIMARY_DECISION->label());
    }

    #[Test]
    public function theIndexShowsDirectForANotificationOnTheDay(): void
    {
        NotificationRule::factory()->recycle($this->department)->inactive()->create([
            'offset_days' => 0,
        ]);

        $this->beUser($this->admin())
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department])
            ->assertOk()
            ->assertSeeInOrder(['Direct', 'Bij', __('general.no')]);
    }

    #[Test]
    public function theIndexWithoutRules(): void
    {
        $this->beUser($this->admin())
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department])
            ->assertOk()
            ->assertSee(__('notification_rule.no_records'));
    }

    #[Test]
    public function theIndexHasADeleteConfirmation(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->create();

        $response = $this->beUser($this->admin())
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department])
            ->assertOk();

        $pattern = sprintf('/href="(%s\?[^"]+)"/', preg_quote(route(RouteName::CONFIRM), '/'));
        $this->assertSame(1, preg_match($pattern, (string) $response->getContent(), $matches));
        parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $confirmation);

        $this->assertIsString($confirmation['confirm_url']);
        $this->assertSame(
            route(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_DELETE,
                ['department' => $this->department, 'notificationRule' => $rule->id],
            ),
            Crypt::decryptString($confirmation['confirm_url']),
        );
        $this->assertSame('DELETE', $confirmation['method']);
    }

    /**
     * @return array<string, array{RouteName, string}>
     */
    public static function pageProvider(): array
    {
        return [
            'index' => [RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, 'GET'],
            'create' => [RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, 'GET'],
            'store' => [RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, 'POST'],
            'preview' => [RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_PREVIEW, 'POST'],
        ];
    }

    #[Test]
    #[DataProvider('pageProvider')]
    public function onlyAdministratorsManageRules(RouteName $route, string $method): void
    {
        $user = User::factory()
            ->withDepartmentRoles($this->department, DepartmentRole::WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $this->department)
            ->call($method, route($route, ['department' => $this->department]))
            ->assertForbidden();
    }

    #[Test]
    public function theCreateFormOffersTheEventTypesOfTheDepartment(): void
    {
        $this->beUser($this->admin())
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $this->department])
            ->assertOk()
            ->assertViewIs('notification-rules.create')
            ->assertSee('value="' . PetitionEventType::ACTUAL_DISCLOSURE->value . '"', false)
            ->assertDontSee('value="' . PetitionEventType::PRIMARY_DECISION->value . '"', false)
            ->assertDontSee('value="' . PetitionEventType::MEETING_SCHEDULED->value . '"', false)
            ->assertDontSee('value="' . NotificationRuleAnchor::EVENT_END_DATE->value . '"', false);
    }

    #[Test]
    public function theCreateFormOffersTheEndDateForAnEventTypeWithAnEnd(): void
    {
        $this->beUser($this->admin())
            ->get(route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, [
                'department' => $this->department,
                'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->value,
            ]))
            ->assertOk()
            ->assertSee('value="' . NotificationRuleAnchor::EVENT_END_DATE->value . '"', false);
    }

    #[Test]
    public function aRuleIsStoredForTheDepartmentFromToday(): void
    {
        $this->beUser($this->admin())
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $this->department], $this->validData())
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department])
            ->assertSessionHas('message.success', __('general.saved'));

        $rule = NotificationRule::query()->sole();
        $this->assertEquals($this->department->id, $rule->department_id);
        $this->assertSame(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $rule->event_type);
        $this->assertSame(NotificationRuleAnchor::EVENT_END_DATE, $rule->anchor);
        $this->assertSame(7, $rule->offset_days);
        $this->assertTrue($rule->active);
        $this->assertSame('2026-10-20', $rule->active_from->toDateString());
    }

    #[Test]
    public function aRuleOnTheDayHasNoDays(): void
    {
        $this->beUser($this->admin())
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $this->department], $this->validData([
                'timing' => NotificationRuleTiming::ON->value,
                'days' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, NotificationRule::query()->sole()->offset_days);
    }

    #[Test]
    public function aRuleBeforeTheDateCountsBack(): void
    {
        $this->beUser($this->admin())
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $this->department], $this->validData([
                'timing' => NotificationRuleTiming::BEFORE->value,
                'days' => '3',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(-3, NotificationRule::query()->sole()->offset_days);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidDataProvider(): array
    {
        return [
            'missing event type' => [['event_type' => ''], 'event_type'],
            'event type of no petition type of the department' => [['event_type' => PetitionEventType::PRIMARY_DECISION->value], 'event_type'],
            'event type not available to the department' => [['event_type' => PetitionEventType::MEETING_SCHEDULED->value], 'event_type'],
            'unknown anchor' => [['anchor' => 'tomorrow'], 'anchor'],
            'end date of an event type without end' => [['event_type' => PetitionEventType::ACTUAL_DISCLOSURE->value], 'anchor'],
            'unknown timing' => [['timing' => 'sideways'], 'timing'],
            'missing days' => [['days' => ''], 'days'],
            'negative days' => [['days' => -1], 'days'],
            'too many days' => [['days' => 366], 'days'],
            'missing active' => [['active' => ''], 'active'],
        ];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[Test]
    #[DataProvider('invalidDataProvider')]
    public function invalidRulesAreRejected(array $override, string $expectedErrorField): void
    {
        $this->beUser($this->admin())
            ->postByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE,
                ['department' => $this->department],
                $this->validData($override),
            )
            ->assertSessionHasErrors($expectedErrorField);

        $this->assertDatabaseCount('notification_rules', 0);
    }

    #[Test]
    public function theEditFormShowsTheRule(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->create(['offset_days' => -21]);

        $this->beUser($this->admin())
            ->getByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $rule->id],
            )
            ->assertOk()
            ->assertViewIs('notification-rules.edit')
            ->assertSee('value="21"', false);
    }

    #[Test]
    public function theEditFormChecksTheRadioButtonsOfTheRule(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->create([
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
            'anchor' => NotificationRuleAnchor::EVENT_DATE,
            'offset_days' => 7,
        ]);

        $content = $this->beUser($this->admin())
            ->getByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $rule->id],
            )
            ->assertOk()
            ->getContent();
        Assert::string($content);

        $checked = new Crawler($content)
            ->filter('input[type="radio"]:checked')
            ->each(static fn(Crawler $radio): string => (string) $radio->attr('id'));

        $this->assertSame(['anchor-event_date', 'timing-after'], $checked);
    }

    #[Test]
    public function aRuleOfAnotherDepartmentIsNotFound(): void
    {
        $rule = NotificationRule::factory()->create();
        $admin = $this->admin();

        $this->beUser($admin)
            ->getByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $rule->id],
            )
            ->assertNotFound();
        $this->postByRoute(
            RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
            ['department' => $this->department, 'notificationRule' => $rule->id],
            $this->validData(),
        )
            ->assertNotFound();
        $this->deleteByRoute(
            RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_DELETE,
            ['department' => $this->department, 'notificationRule' => $rule->id],
        )
            ->assertNotFound();
        $this->assertModelExists($rule);
    }

    #[Test]
    public function anUnknownRuleIsNotFound(): void
    {
        $this->beUser($this->admin())
            ->getByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $this->faker->uuid()],
            )
            ->assertNotFound();
    }

    #[Test]
    public function changingHowARuleCountsMakesItStartToday(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->create([
            'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            'anchor' => NotificationRuleAnchor::EVENT_END_DATE,
            'offset_days' => 7,
            'active_from' => CalendarDate::create('2026-10-01'),
        ]);

        $this->beUser($this->admin())
            ->postByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $rule->id],
                $this->validData(['days' => 10]),
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department]);

        $rule->refresh();
        $this->assertSame(10, $rule->offset_days);
        $this->assertSame('2026-10-20', $rule->active_from->toDateString());
    }

    #[Test]
    public function savingAnUnchangedRuleKeepsItsStart(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->create([
            'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            'anchor' => NotificationRuleAnchor::EVENT_END_DATE,
            'offset_days' => 7,
            'active_from' => CalendarDate::create('2026-10-01'),
        ]);

        $this->beUser($this->admin())
            ->postByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $rule->id],
                $this->validData(['active' => '0']),
            )
            ->assertSessionHasNoErrors();

        $rule->refresh();
        $this->assertFalse($rule->active);
        $this->assertSame('2026-10-01', $rule->active_from->toDateString());
    }

    #[Test]
    public function switchingARuleBackOnMakesItStartToday(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->inactive()->create([
            'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            'anchor' => NotificationRuleAnchor::EVENT_END_DATE,
            'offset_days' => 7,
            'active_from' => CalendarDate::create('2026-10-01'),
        ]);

        $this->beUser($this->admin())
            ->postByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT,
                ['department' => $this->department, 'notificationRule' => $rule->id],
                $this->validData(),
            )
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-20', $rule->refresh()->active_from->toDateString());
    }

    #[Test]
    public function deletingARuleRemovesItsDeliveries(): void
    {
        $rule = NotificationRule::factory()->recycle($this->department)->create();
        $delivery = NotificationRuleDelivery::factory()->recycle($rule)->create();

        $this->beUser($this->admin())
            ->deleteByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_DELETE,
                ['department' => $this->department, 'notificationRule' => $rule->id],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $this->department])
            ->assertSessionHas('message.success', __('general.deleted'));

        $this->assertModelMissing($rule);
        $this->assertModelMissing($delivery);
    }

    #[Test]
    public function thePreviewShowsTheMail(): void
    {
        $this->beUser($this->admin())
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_PREVIEW, ['department' => $this->department], $this->validData())
            ->assertOk()
            ->assertViewIs('notification-rules.preview')
            ->assertSeeInOrder([
                'Beste behandelaar,',
                'Er is een notificatie aangemaakt op zaak [zaaknummer].',
                'Betreft: ' . PetitionEventType::LETTER_OF_SUSPENSION_SENT->label(PetitionVariant::WOO_VERZOEK),
                'Actie: Is 7 dagen geleden afgelopen',
                'Het zaakvolgsysteem',
            ]);
    }

    #[Test]
    public function thePreviewOfANotificationOnTheDayShowsADatePlaceholder(): void
    {
        $this->beUser($this->admin())
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_PREVIEW, ['department' => $this->department], $this->validData([
                'timing' => NotificationRuleTiming::ON->value,
                'days' => '5',
            ]))
            ->assertOk()
            ->assertSee('Actie: Loopt af op [datum]');
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function incompletePreviewProvider(): array
    {
        return [
            'no event type' => [['event_type' => '']],
            'event type of no petition type of the department' => [['event_type' => PetitionEventType::PRIMARY_DECISION->value]],
            'no anchor' => [['anchor' => '']],
            'no timing' => [['timing' => '']],
        ];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[Test]
    #[DataProvider('incompletePreviewProvider')]
    public function thePreviewAsksToCompleteTheForm(array $override): void
    {
        $this->beUser($this->admin())
            ->postByRoute(
                RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_PREVIEW,
                ['department' => $this->department],
                $this->validData($override),
            )
            ->assertOk()
            ->assertSee(__('notification_rule.preview_incomplete'));
    }

    private function admin(): User
    {
        return User::factory()->asAdministrator()->create();
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function validData(array $override = []): array
    {
        return array_merge([
            'event_type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->value,
            'anchor' => NotificationRuleAnchor::EVENT_END_DATE->value,
            'timing' => NotificationRuleTiming::AFTER->value,
            'days' => '7',
            'active' => '1',
        ], $override);
    }
}
