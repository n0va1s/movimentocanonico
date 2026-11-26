<x-layouts.auth title="Defina sua nova senha">
    <x-auth-header title="Defina sua nova senha" description="Troque a senha temporária para acessar sua conta." :level="1" />
    <form method="POST" action="{{ route('primeira-senha.update') }}" class="space-y-4">
        @csrf
        <flux:input name="current_password" label="Senha temporária" type="password" autocomplete="current-password" required />
        <flux:input name="password" label="Nova senha" type="password" autocomplete="new-password" required />
        <flux:input name="password_confirmation" label="Confirmar nova senha" type="password" autocomplete="new-password" required />
        <flux:button type="submit" variant="primary" class="w-full">Salvar senha</flux:button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <flux:button type="submit" variant="ghost">Sair</flux:button>
    </form>
</x-layouts.auth>
