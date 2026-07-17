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

    public function getRole(): string
    {
        $role = $this->role;
        $roleValue = $role instanceof \BackedEnum ? $role->value : (string) $role;

        return strtolower($roleValue);
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === self::ROLE_ADMIN;
    }

    public function isCoordenador(): bool
    {
        return $this->getRole() === self::ROLE_COORDENADOR;
    }

    public function isDirig(): bool
    {
        return $this->getRole() === self::ROLE_DIRIG;
    }

    public function isVisitacao(): bool
    {
        return !$this->isAdmin() && $this->autorizaVisit();
    }

    public function isSales(): bool
    {
        return $this->autorizaSales();
    }

    public function hasRole(string ...$roles): bool
    {
        $roleValue = $this->getRole();
        $mappedRoles = array_map('strtolower', $roles);

        if (in_array($roleValue, $mappedRoles)) {
            return true;
        }

        if (in_array('visit', $mappedRoles) && $this->autorizaVisit()) {
            return true;
        }

        if (in_array('sales', $mappedRoles) && $this->autorizaSales()) {
            return true;
        }

        if (in_array('coord_equipe', $mappedRoles) && $this->autorizaCoord()) {
            return true;
        }

        return false;
    }

    public function autorizaCoord(): bool
    {
        $pessoa = $this->pessoa;
        if (! $pessoa) {
            return false;
        }

        return Trabalhador::where('idt_pessoa', $pessoa->idt_pessoa)
            ->where('ind_coordenador', true)
            ->whereHas('evento', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->exists();
    }

    public function ehCoordenadorDeEquipe(): bool
    {
        return $this->autorizaCoord();
    }

    public function autorizaVisit(): bool
    {
        if ($this->isAdmin() || $this->isDirig()) {
            return true;
        }

        return Trabalhador::where('idt_pessoa', $this->pessoa?->idt_pessoa)
            ->whereHas('equipe', function ($q) {
                $q->whereRaw('LOWER(des_grupo) LIKE ?', ['%visita%']);
            })
            ->exists();
    }

    public function autorizaSales(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return Trabalhador::where('idt_pessoa', $this->pessoa?->idt_pessoa)
            ->whereHas('equipe', function ($q) {
                $q->where(function ($query) {
                    $query->whereRaw('LOWER(des_grupo) LIKE ?', ['%vendinha%'])
                        ->orWhereRaw('LOWER(des_grupo) LIKE ?', ['%mini-mercado%'])
                        ->orWhereRaw('LOWER(des_grupo) LIKE ?', ['%minimercado%']);
                });
            })
            ->exists();
    }

    /**
     * Verifica se o usuário está trabalhando em um evento específico.
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
