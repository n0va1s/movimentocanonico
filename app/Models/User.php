<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Services\PhoneService;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_USER = 'user';

    const ROLE_ADMIN = 'admin';

    const ROLE_COORDENADOR = 'coord';

    const ROLE_DIRIG = 'dirig';

    const ROLE_SALES = 'sales';

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

    public function isDirig(): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;

        return strtolower($roleValue) === self::ROLE_DIRIG;
    }

    public function isVisitacao(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        return $this->podeAcessarMinhasFichas();
    }

    public function isSales(): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;

        return strtolower($roleValue) === self::ROLE_SALES;
    }

    public function hasRole(string ...$roles): bool
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;
        $roleValue = strtolower($roleValue);

        $mappedRoles = array_map('strtolower', $roles);

        if (in_array($roleValue, $mappedRoles)) {
            return true;
        }

        if (in_array('visit', $mappedRoles) && $this->podeAcessarMinhasFichas()) {
            return true;
        }

        return false;
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

        if ($this->isDirig()) {
            $evento = \App\Models\Evento::find($idtEvento);
            if (! $evento || is_null($this->idt_movimento) || (int) $evento->idt_movimento !== (int) $this->idt_movimento) {
                return false;
            }
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

    public function podeAcessarMinhasFichas(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return Trabalhador::where('idt_pessoa', $this->pessoa?->idt_pessoa)
            ->whereHas('equipe', function ($q) {
                $q->where('des_grupo', 'like', '%Visitação%');
            })
            ->exists();
    }

    public function pessoa()
    {
        return $this->hasOne(Pessoa::class, 'idt_usuario', 'id');
    }

    public function movimento()
    {
        return $this->belongsTo(TipoMovimento::class, 'idt_movimento', 'idt_movimento');
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
        'idt_movimento',
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

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }
}
