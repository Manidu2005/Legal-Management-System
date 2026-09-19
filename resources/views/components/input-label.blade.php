@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-mist-700 dark:text-mist-400']) }}>
    {{ $value ?? $slot }}
</label>
