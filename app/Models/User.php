<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_USER = 'user';

    const ROLE_ADMIN = 'admin';

    const ROLE_COORDENADOR = 'coord';

    const ROLE_ESPEC = 'espec';

    public function isAdmin(): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;
        return strtolower($roleValue) === self::ROLE_ADMIN;
    }

    public function isCoordenador(): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;
        return strtolower($roleValue) === self::ROLE_COORDENADOR;
    }

    public function isEspec(): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;
        return strtolower($roleValue) === self::ROLE_ESPEC;
    }

    public function hasRole(string ...$roles): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;
        return in_array(strtolower($roleValue), array_map('strtolower', $roles));
    }

    /**
     * Verifica se o usuário está trabalhando (como coord ou espec) em um evento específico.
     * Coord: precisa ter ind_coordenador = true no evento.
     * Espec: basta estar na tabela trabalhador do evento.
     */
    public function trabalhaNoEvento(int $idtEvento): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $pessoa = $this->pessoa;

        if (! $pessoa) {
            return false;
        }

        return Trabalhador::where('idt_evento', $idtEvento)
            ->where('idt_pessoa', $pessoa->idt_pessoa)
            ->when($this->isCoordenador(), fn ($q) => $q->where('ind_coordenador', true))
            ->exists();
    }

    public function pessoa()
    {
        return $this->hasOne(Pessoa::class, 'idt_usuario', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (User $user) {
            $pessoaCadastrada = Pessoa::where('eml_pessoa', $user->email)->first();

            if (! $pessoaCadastrada) {
                $user->pessoa()->create([
                    'nom_pessoa' => $user->name,
                    'eml_pessoa' => $user->email,
                    'tel_pessoa' => $user->phone,
                    'dat_nascimento' => '1900-01-01',
                ]);
            } else {
                $pessoaCadastrada->idt_usuario = $user->id;
                // Para evitar loop infinito, salvar a pessoa sem disparar eventos
                $pessoaCadastrada->saveQuietly();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function routeNotificationForTelegram()
    {
        if ($this->role === 'admin') {
            return env('TELEGRAM_CHAT_IDS');
        }

        return null;
    }
}
