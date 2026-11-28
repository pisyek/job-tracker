@props(['status'])

@php
    $color = match ($status) {
        \App\Enums\ApplicationStatus::Wishlist => 'zinc',
        \App\Enums\ApplicationStatus::Applied => 'blue',
        \App\Enums\ApplicationStatus::Interviewing => 'amber',
        \App\Enums\ApplicationStatus::Offer => 'green',
        \App\Enums\ApplicationStatus::Rejected => 'red',
    };
@endphp

<flux:badge :color="$color" size="sm">{{ $status->label() }}</flux:badge>
