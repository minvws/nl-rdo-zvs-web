@foreach ($custom_dates as $custom_date)
    <div class="description-list__item">
        <dt>
            {{ __('custom_dates.' . $custom_date->date_label->value) }}
        </dt>
        <dd>
            @if ($custom_date->date)
                {{ DisplayDate::date($custom_date->date) }}
            @else
                {{ '-' }}
            @endif
        </dd>
    </div>
@endforeach
