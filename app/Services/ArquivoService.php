<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ArquivoService
{
    /**
     * Upload genérico de arquivos para relações.
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

        $model->load($relationName);
        $relatedModel = $model->{$relationName};

        if ($relatedModel && $relatedModel->{$column}) {
            $this->excluirArquivo($relatedModel->{$column});
        }

        $fileName = $customName
            ? $customName.'.'.$file->getClientOriginalExtension()
            : $file->hashName();

        $filePath = $file->storeAs($path, $fileName, 'public');

        $model->{$relationName}()->updateOrCreate(
            [], 
            [$column => $filePath]
        );
    }

    /**
     * Upload direto de arquivo na própria model (sem relação)
     */
    public function uploadDirectly(
        Model $model,
        ?UploadedFile $file,
        string $column,
        string $path = 'uploads',
        ?string $customName = null
    ): void {
        if (! $file) {
            return;
        }

        if ($model->{$column}) {
            $this->excluirArquivo($model->{$column});
        }

        $fileName = $customName
            ? $customName.'.'.$file->getClientOriginalExtension()
            : $file->hashName();

        $filePath = $file->storeAs($path, $fileName, 'public');

        $model->update([$column => $filePath]);
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
