<?php

namespace App\Models;

use App\Enums\EstadoCivil;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Mail\BoasVindasMail;
use App\Services\CpfService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Pessoa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pessoa';

    protected $primaryKey = 'idt_pessoa';

    public $timestamps = true;

    protected $fillable = [
        'idt_usuario',
        'idt_parceiro',
        'num_cpf_pessoa',
        'nom_pessoa',
        'nom_apelido',
        'tel_pessoa',
        'dat_nascimento',
        'des_endereco',
        'eml_pessoa',
        'tam_camiseta',
        'tip_genero',
        'tip_estado_civil',
        'tip_habilidade',
        'nom_profissao',
        'ind_restricao',
    ];

    protected $casts = [
        'dat_nascimento' => 'date:Y-m-d',
        'ind_restricao' => 'boolean',
        'tip_estado_civil' => EstadoCivil::class,
        'tip_habilidade' => HabilidadePrincipal::class,
        'tip_genero' => Genero::class,
        'tam_camiseta' => TamanhoCamiseta::class,
    ];

    protected static function booted()
    {
        parent::boot();

        static::created(function (Pessoa $pessoa) {
            if ($pessoa->idt_usuario) {
                return;
            }

            if (! $pessoa->eml_pessoa || ! $pessoa->dat_nascimento) {
                return;
            }

            $senha = $pessoa->dat_nascimento->format('Ymd');

            $user = User::create([
                'name' => $pessoa->nom_pessoa,
                'email' => $pessoa->eml_pessoa,
                'password' => Hash::make($senha),
                'role' => User::ROLE_USER,
            ]);

            $pessoa->idt_usuario = $user->id;
            // Para evitar loop infinito, salvar a pessoa sem disparar eventos
            $pessoa->saveQuietly();
            // Envia e-mail de boas-vindas com a senha gerada
            Mail::to($user->email)->send(new BoasVindasMail($user, $senha));
        });
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idt_usuario', 'id');
    }

    public function foto()
    {
        return $this->hasOne(PessoaFoto::class, 'idt_pessoa', 'idt_pessoa');
    }

    public function restricoes()
    {
        return $this->belongsToMany(TipoRestricao::class, 'pessoa_saude', 'idt_pessoa', 'idt_restricao')
            ->withPivot('txt_complemento')
            ->withTimestamps();
    }

    public function participantes()
    {
        return $this->hasMany(Participante::class, 'idt_pessoa');
    }

    public function trabalhadores()
    {
        return $this->hasMany(Trabalhador::class, 'idt_pessoa');
    }

    public function voluntarios()
    {
        return $this->hasMany(Voluntario::class, 'idt_pessoa');
    }

    public function parceiro()
    {
        return $this->belongsTo(Pessoa::class, 'idt_parceiro', 'idt_pessoa');
    }

    public function pontos()
    {
        return $this->hasMany(Gamificacao::class, 'idt_pessoa', 'idt_pessoa');
    }

    public function setParceiro(Pessoa $umaSoCarne)
    {
        if ($umaSoCarne && $this->idt_pessoa === $umaSoCarne->idt_pessoa) {
            throw new \InvalidArgumentException('Uma pessoa não pode ser parceira de si mesma.');
        }

        $this->idt_parceiro = $umaSoCarne ? $umaSoCarne->idt_pessoa : null;
        $this->save();

        if ($umaSoCarne) {
            $umaSoCarne->idt_parceiro = $this->idt_pessoa;
            $umaSoCarne->save();
        }
    }

    public function removeParceiro()
    {
        if ($this->parceiro) {
            $umaSoCarne = $this->parceiro;
            $umaSoCarne->idt_parceiro = null;
            $umaSoCarne->save();
        }
        $this->idt_parceiro = null;
        $this->save();
    }

    public function getDataNascimentoFormatada()
    {
        return $this->dat_nascimento
            ? $this->dat_nascimento->format('Y-m-d')
            : null;
    }

    public function scopeSearchByName($query, $search)
    {
        // Verifica se estamos usando MySQL/MariaDB (produção) e não estamos em testes
        if (!app()->runningUnitTests() && (config('database.default') === 'mysql' || config('database.default') === 'mariadb')) {
            // Usa o Full-Text Search OTIMIZADO (Exige que você adicione o FTS manualmente no MySQL)
            return $query->whereFullText(['nom_pessoa', 'nom_apelido'], $search);
        }

        // Caso contrário (ambiente SQLite de desenvolvimento)
        // Usamos a sintaxe mais lenta, mas compatível (LIKE "%...%")
        return $query->where(function ($q) use ($search) {
            $q->where('nom_pessoa', 'like', "%{$search}%")
                ->orWhere('nom_apelido', 'like', "%{$search}%");
        });
    }

    protected function numCpfPessoa(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CpfService::format($value),
            set: fn (?string $value) => CpfService::clean($value),
        );
    }
}
