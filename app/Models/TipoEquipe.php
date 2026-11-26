<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TipoEquipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_equipe';

    protected $primaryKey = 'idt_equipe';

    public $timestamps = true;

    protected $fillable = [
        'idt_movimento',
        'des_grupo',
        'txt_documento',
        'ind_disponivel_candidatura',
    ];

    public function movimento()
    {
        return $this->belongsTo(TipoMovimento::class, 'idt_movimento');
    }

    public function trabalhadores()
    {
        return $this->hasMany(Trabalhador::class, 'idt_equipe');
    }

    public function voluntarios()
    {
        return $this->hasMany(Voluntario::class, 'idt_equipe');
    }

    public static function catalogo(int $movimento): Collection
    {
        $carregar = fn () => self::query()->where('idt_movimento', $movimento)
            ->orderBy('des_grupo')->orderBy('idt_equipe')
            ->get(['idt_equipe', 'des_grupo', 'ind_disponivel_candidatura']);

        return DB::transactionLevel() > 0
            ? $carregar()
            : Cache::remember(self::chaveCatalogo($movimento), now()->addMinutes(5), $carregar);
    }

    protected static function booted(): void
    {
        $invalidar = static function (self $equipe): void {
            $ids = array_unique(array_filter([$equipe->idt_movimento, $equipe->getOriginal('idt_movimento')]));
            $limpar = static function () use ($ids): void {
                foreach ($ids as $id) {
                    Cache::forget(self::chaveCatalogo((int) $id));
                }
            };

            if (DB::transactionLevel() > 0) {
                DB::afterCommit($limpar);
            } else {
                $limpar();
            }
        };

        static::saved($invalidar);
        static::deleted($invalidar);
    }

    private static function chaveCatalogo(int $movimento): string
    {
        return 'equipes:catalogo:'.$movimento;
    }
}
