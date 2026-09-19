@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-mist-600 dark:text-mist-400']) }}>
        {{ $status }}
    </div>
@endif
