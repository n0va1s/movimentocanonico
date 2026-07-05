<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        <flux:toast />

        @if (session()->has('success'))
            <div x-data x-init="$dispatch('toast-show', { slots: { text: '{{ session('success') }}' }, dataset: { variant: 'success' } })"></div>
        @endif

        @if (session()->has('error'))
            <div x-data x-init="$dispatch('toast-show', { slots: { text: '{{ session('error') }}' }, dataset: { variant: 'danger' } })"></div>
        @endif

        @if ($errors->any())
            <div x-data x-init="$dispatch('toast-show', { slots: { text: 'Por favor, verifique os erros no formulário.' }, dataset: { variant: 'danger' } })"></div>
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
