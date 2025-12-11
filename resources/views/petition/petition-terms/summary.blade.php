<div
    id="terms-summary"
    class="petition-edit">
    <div class="petition-property__block">
        <header class="petition-property__header">
            <h2>{{ __('term.overview') }}</h2>
        </header>
        <dl class="description-list">
            <div class="description-list__item">
                <dt>{{ $current_terms->count() === 1 ? __('term.current_term') : __('term.current_terms') }}</dt>
                @forelse ($current_terms as $current_term)
                    <dd>{{ __('term.term_type.' . $current_term->type->value) }}</dd>
                @empty
                    <dd>
                        <span class="visually-hidden">{{ __('term.no_records') }}</span>
                        <span aria-hidden="true">{{ '-' }}</span>
                    </dd>
                @endforelse
            </div>
            <div class="description-list__item">
                <dt>{{ __('term.total_days_of_suspensions') }}</dt>
                <dd>{{ $total_days_of_suspensions }}</dd>
            </div>

            @if ($sum_of_penalties_per_date > 0 || $penaltyToDate > 0)
                <div class="description-list__item mt-3">
                    <dt>{{ __('term.sum_of_penalties_per_date') }}</dt>
                    <dd>{{ Number::currency($sum_of_penalties_per_date, 'EUR', 'nl', 0) }}</dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('term.penalty_to_date') }}</dt>
                    <dd>{{ Number::currency($penaltyToDate, 'EUR', 'nl', 0) }}</dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('term.total_penalty') }}</dt>
                    <dd>{{ Number::currency($totalPenalty, 'EUR', 'nl', 0) }}</dd>
                </div>
            @endif
        </dl>
    </div>
</div>
