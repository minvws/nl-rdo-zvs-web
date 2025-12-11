<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Collections\PetitionCustomDateCollection;
use App\Enums\Ability;
use App\Enums\CustomDateLabel;
use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Petition;
use App\Models\PetitionCustomDate;
use App\Rules\CalendarDateRule;
use App\ValueObjects\CalendarDate;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Override;
use TypeError;
use ValueError;
use Webmozart\Assert\Assert;

use function assert;
use function is_array;
use function route;

class PetitionCustomDatesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $petition = $this->route('petition');
        Assert::isInstanceOf($petition, Petition::class);

        return Gate::allows(Ability::UPDATE, $petition);
    }

    /**
     * @return array<string, array<int, CalendarDateRule|string>>.
     */
    public function rules(): array
    {
        return [
            'custom_dates' => [
                'required',
                'array',
            ],
            'custom_dates.*.date_label' => [
                'string',
                'max:255',
            ],
            'custom_dates.*.date' => [
                'nullable',
                new CalendarDateRule(),
            ],
        ];
    }

    /**
     * @throws TypeError
     * @throws ValueError
     */
    public function getCustomDatesCollection(): PetitionCustomDateCollection
    {
        $customDates = new PetitionCustomDateCollection();
        $data = $this->input('custom_dates', []);
        assert(is_array($data));
        foreach ($data as $customDateLabel) {
            if ($customDateLabel['date'] === null) {
                continue;
            }
            $customDates->push(
                new PetitionCustomDate([
                    'date' => CalendarDate::createFromFormat(CalendarDate::DEFAULT_STRING_FORMAT, $customDateLabel['date']),
                    'date_label' => CustomDateLabel::from($customDateLabel['date_label']),
                ]),
            );
        }

        return $customDates;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $parameters = [
            'department' => $route->parameter('department'),
            'petition' => $route->parameter('petition'),
        ];

        if ($this->request->has('hx-target')) {
            $parameters['hx-target'] = $this->request->get('hx-target');
        }

        return route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT, $parameters);
    }
}
