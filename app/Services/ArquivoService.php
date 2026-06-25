<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ArquivoService
{
    /**
     * Upload genérico de arquivos.
     *
     * * @param Model $model O modelo que possui a relação (ex: Evento, Pessoa)
     * @param  UploadedFile|null  $file  O arquivo vindo do request
     * @param  string  $relationName  O nome do método de relação no Model (ex: 'foto')
     * @param  string  $column  O nome da coluna no banco (ex: 'med_logo')
     * @param  string  $path  O caminho da pasta no storage
     * @param  string  $customName  O nome específico para o arquivo
     */
    public function upload(
        Model $model,
        ?UploadedFile $file,
        string $relationName,
        string $column,
        string $path = 'eventos',
        ?string $customName = null
    ): void {
        if (! $file) {
            return;
        }

        // 1. Carrega a relação para verificar se já existe arquivo antigo. Ex: $evento->load('foto')
        $model->load($relationName);
        $relatedModel = $model->{$relationName};

        // 2. Remove o arquivo antigo se ele existir
        if ($relatedModel && $relatedModel->{$column}) {
            if (Storage::disk('public')->exists($relatedModel->{$column})) {
                Storage::disk('public')->delete($relatedModel->{$column});
            }
        }

        // 3. Define o nome do arquivo
        // Se houver nome customizado, usa ele + extensão original. Caso contrário, gera um hash.
        $fileName = $customName
            ? $customName.'.'.$file->getClientOriginalExtension()
            : $file->hashName();

        // 4. Salva o arquivo no disco público
        $filePath = $file->storeAs($path, $fileName, 'public');

        // 5. Atualiza ou cria o registro na tabela relacionada
        $model->{$relationName}()->updateOrCreate(
            [], // A FK é gerenciada automaticamente pelo Laravel através da relação
            [$column => $filePath]
        );
    }

    /**
     * Exclui um arquivo do disco público se ele existir.
     */
    public function excluirArquivo(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
