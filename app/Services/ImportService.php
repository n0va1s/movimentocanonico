<?php

namespace App\Services;

use App\Enums\CorTroca;
use App\Enums\Genero;
use App\Enums\TamanhoCamiseta;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ImportService
{
    /**
     * Importa participantes a partir de um arquivo CSV.
     * Processa em lotes de 50 registros.
     */
    public function importarParticipantes(Evento $evento, string $filePath): array
    {
        $logPath = storage_path('logs/import_participantes.log');
        $startTime = now()->toDateTimeString();
        $this->writeLog($logPath, "=== INÍCIO DA IMPORTAÇÃO DE PARTICIPANTES - EVENTO: {$evento->des_evento} (ID: {$evento->idt_evento}) em {$startTime} ===");

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->writeLog($logPath, "Erro: Não foi possível abrir o arquivo.");
            return ['success' => false, 'message' => 'Não foi possível abrir o arquivo.'];
        }

        // Detecta delimitador (normalmente ',' ou ';')
        $headerLine = fgets($file);
        $delimiter = $this->detectDelimiter($headerLine);
        rewind($file);

        $headers = fgetcsv($file, 0, $delimiter);
        if (!$headers) {
            $this->writeLog($logPath, "Erro: Planilha vazia ou cabeçalhos não encontrados.");
            fclose($file);
            return ['success' => false, 'message' => 'Planilha vazia ou cabeçalhos não encontrados.'];
        }

        $headerMap = $this->mapHeaders($headers);
        $this->writeLog($logPath, "Cabeçalhos mapeados: " . json_encode($headerMap));

        // Valida se os campos obrigatórios estão presentes
        if (!isset($headerMap['nome']) || !isset($headerMap['email']) || !isset($headerMap['data_nascimento'])) {
            $msg = "Erro: Cabeçalhos obrigatórios ausentes. Certifique-se de que a planilha possui 'Nome', 'Email' e 'Data Nascimento'.";
            $this->writeLog($logPath, $msg);
            fclose($file);
            return ['success' => false, 'message' => $msg];
        }

        $stats = [
            'total_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'linked' => 0,
            'errors' => 0,
            'batches' => 0,
        ];

        $batch = [];
        $lineNumber = 1;

        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            $lineNumber++;
            // Ignora linhas vazias
            if (empty(array_filter($row))) {
                continue;
            }

            $stats['total_rows']++;
            $batch[] = [
                'line' => $lineNumber,
                'data' => $row
            ];

            // Quando atinge o lote de 50
            if (count($batch) === 50) {
                $stats['batches']++;
                $this->processarLoteParticipantes($evento, $batch, $headerMap, $stats, $logPath);
                $batch = [];
            }
        }

        // Processa o último lote residual
        if (count($batch) > 0) {
            $stats['batches']++;
            $this->processarLoteParticipantes($evento, $batch, $headerMap, $stats, $logPath);
        }

        fclose($file);

        $endTime = now()->toDateTimeString();
        $summary = "=== FIM DA IMPORTAÇÃO DE PARTICIPANTES ===\n" .
            "Total de linhas lidas: {$stats['total_rows']}\n" .
            "Criados (Pessoa): {$stats['created']}\n" .
            "Atualizados (Pessoa): {$stats['updated']}\n" .
            "Vinculados ao Evento: {$stats['linked']}\n" .
            "Erros: {$stats['errors']}\n" .
            "Lotes processados: {$stats['batches']}\n" .
            "Finalizado em: {$endTime}\n" .
            "==========================================";
        $this->writeLog($logPath, $summary);

        return [
            'success' => true,
            'stats' => $stats,
            'log_path' => $logPath
        ];
    }

    /**
     * Importa trabalhadores a partir de um arquivo CSV.
     * Processa em lotes de 50 registros.
     */
    public function importarTrabalhadores(Evento $evento, string $filePath): array
    {
        $logPath = storage_path('logs/import_trabalhadores.log');
        $startTime = now()->toDateTimeString();
        $this->writeLog($logPath, "=== INÍCIO DA IMPORTAÇÃO DE TRABALHADORES - EVENTO: {$evento->des_evento} (ID: {$evento->idt_evento}) em {$startTime} ===");

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->writeLog($logPath, "Erro: Não foi possível abrir o arquivo.");
            return ['success' => false, 'message' => 'Não foi possível abrir o arquivo.'];
        }

        // Detecta delimitador (normalmente ',' ou ';')
        $headerLine = fgets($file);
        $delimiter = $this->detectDelimiter($headerLine);
        rewind($file);

        $headers = fgetcsv($file, 0, $delimiter);
        if (!$headers) {
            $this->writeLog($logPath, "Erro: Planilha vazia ou cabeçalhos não encontrados.");
            fclose($file);
            return ['success' => false, 'message' => 'Planilha vazia ou cabeçalhos não encontrados.'];
        }

        $headerMap = $this->mapHeaders($headers);
        $this->writeLog($logPath, "Cabeçalhos mapeados: " . json_encode($headerMap));

        // Valida se os campos obrigatórios estão presentes
        if (!isset($headerMap['nome']) || !isset($headerMap['email']) || !isset($headerMap['data_nascimento']) || !isset($headerMap['equipe'])) {
            $msg = "Erro: Cabeçalhos obrigatórios ausentes. Certifique-se de que a planilha possui 'Nome', 'Email', 'Data Nascimento' e 'Equipe'.";
            $this->writeLog($logPath, $msg);
            fclose($file);
            return ['success' => false, 'message' => $msg];
        }

        $stats = [
            'total_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'linked' => 0,
            'errors' => 0,
            'batches' => 0,
        ];

        $batch = [];
        $lineNumber = 1;

        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            $lineNumber++;
            // Ignora linhas vazias
            if (empty(array_filter($row))) {
                continue;
            }

            $stats['total_rows']++;
            $batch[] = [
                'line' => $lineNumber,
                'data' => $row
            ];

            // Quando atinge o lote de 50
            if (count($batch) === 50) {
                $stats['batches']++;
                $this->processarLoteTrabalhadores($evento, $batch, $headerMap, $stats, $logPath);
                $batch = [];
            }
        }

        // Processa o último lote residual
        if (count($batch) > 0) {
            $stats['batches']++;
            $this->processarLoteTrabalhadores($evento, $batch, $headerMap, $stats, $logPath);
        }

        fclose($file);

        $endTime = now()->toDateTimeString();
        $summary = "=== FIM DA IMPORTAÇÃO DE TRABALHADORES ===\n" .
            "Total de linhas lidas: {$stats['total_rows']}\n" .
            "Criados (Pessoa): {$stats['created']}\n" .
            "Atualizados (Pessoa): {$stats['updated']}\n" .
            "Vinculados ao Evento: {$stats['linked']}\n" .
            "Erros: {$stats['errors']}\n" .
            "Lotes processados: {$stats['batches']}\n" .
            "Finalizado em: {$endTime}\n" .
            "==========================================";
        $this->writeLog($logPath, $summary);

        return [
            'success' => true,
            'stats' => $stats,
            'log_path' => $logPath
        ];
    }

    /**
     * Processa um lote de 50 participantes em transação.
     */
    private function processarLoteParticipantes(Evento $evento, array $batch, array $headerMap, array &$stats, string $logPath): void
    {
        $this->writeLog($logPath, "Processando lote de participantes - Tamanho: " . count($batch));

        DB::transaction(function () use ($evento, $batch, $headerMap, &$stats, $logPath) {
            foreach ($batch as $item) {
                $line = $item['line'];
                $data = $item['data'];

                try {
                    $rowValues = $this->extractRowData($data, $headerMap);

                    // Validações básicas da linha
                    if (empty($rowValues['nome']) || empty($rowValues['email']) || empty($rowValues['data_nascimento'])) {
                        $this->writeLog($logPath, "Linha {$line}: Ignorada devido a campos obrigatórios em branco (Nome/Email/Data Nascimento).");
                        $stats['errors']++;
                        continue;
                    }

                    $cpfClean = $rowValues['cpf'] ? preg_replace('/\D/', '', $rowValues['cpf']) : null;
                    $emailClean = trim(strtolower($rowValues['email']));
                    $dataNascimento = $this->parseBirthDate($rowValues['data_nascimento']);

                    if (!$dataNascimento) {
                        $this->writeLog($logPath, "Linha {$line}: Data de nascimento inválida ('{$rowValues['data_nascimento']}').");
                        $stats['errors']++;
                        continue;
                    }

                    // Busca se a pessoa já está cadastrada
                    $pessoa = null;
                    if ($cpfClean) {
                        $pessoa = Pessoa::where('num_cpf_pessoa', $cpfClean)->first();
                    }
                    if (!$pessoa) {
                        $pessoa = Pessoa::where('eml_pessoa', $emailClean)->first();
                    }

                    $dadosPessoa = [
                        'nom_pessoa' => trim($rowValues['nome']),
                        'nom_apelido' => $rowValues['apelido'] ? trim($rowValues['apelido']) : null,
                        'tel_pessoa' => $rowValues['telefone'] ? preg_replace('/\D/', '', $rowValues['telefone']) : null,
                        'dat_nascimento' => $dataNascimento,
                        'des_endereco' => $rowValues['endereco'] ? trim($rowValues['endereco']) : null,
                        'eml_pessoa' => $emailClean,
                        'tip_genero' => $this->parseGenero($rowValues['genero']),
                        'tam_camiseta' => $this->parseTamanhoCamiseta($rowValues['tamanho_camiseta']),
                    ];

                    if ($cpfClean) {
                        $dadosPessoa['num_cpf_pessoa'] = $cpfClean;
                    }

                    if ($pessoa) {
                        // Atualiza a pessoa
                        $pessoa->update(array_filter($dadosPessoa, fn($v) => !is_null($v)));
                        $stats['updated']++;
                        $this->writeLog($logPath, "Linha {$line}: Pessoa existente atualizada (ID: {$pessoa->idt_pessoa}, Nome: {$pessoa->nom_pessoa}).");
                    } else {
                        // Cadastra nova pessoa
                        $pessoa = Pessoa::create($dadosPessoa);
                        $stats['created']++;
                        $this->writeLog($logPath, "Linha {$line}: Nova Pessoa cadastrada (ID: {$pessoa->idt_pessoa}, Nome: {$pessoa->nom_pessoa}).");
                    }

                    // Garante que há um usuário vinculado
                    $this->garantirUsuarioParaPessoa($pessoa);

                    // Cria ou atualiza o vínculo de Participante no evento
                    $corTroca = $rowValues['cor_troca'] ? $this->parseCorTroca($rowValues['cor_troca']) : null;
                    $taxaPagou = $this->parseBoolean($rowValues['taxa_pagou']);
                    $presente = $this->parseBoolean($rowValues['presente']);

                    $participante = Participante::updateOrCreate(
                        [
                            'idt_pessoa' => $pessoa->idt_pessoa,
                            'idt_evento' => $evento->idt_evento,
                        ],
                        [
                            'tip_cor_troca' => $corTroca,
                            'ind_taxa_pagou' => $taxaPagou,
                            'ind_presente' => $presente,
                        ]
                    );

                    $stats['linked']++;
                    $this->writeLog($logPath, "Linha {$line}: Participante vinculado ao evento com sucesso (ID Participante: {$participante->idt_participante}).");

                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $this->writeLog($logPath, "Linha {$line}: Erro no processamento - " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }
        });
    }

    /**
     * Processa um lote de 50 trabalhadores em transação.
     */
    private function processarLoteTrabalhadores(Evento $evento, array $batch, array $headerMap, array &$stats, string $logPath): void
    {
        $this->writeLog($logPath, "Processando lote de trabalhadores - Tamanho: " . count($batch));

        DB::transaction(function () use ($evento, $batch, $headerMap, &$stats, $logPath) {
            foreach ($batch as $item) {
                $line = $item['line'];
                $data = $item['data'];

                try {
                    $rowValues = $this->extractRowData($data, $headerMap);

                    // Validações básicas da linha
                    if (empty($rowValues['nome']) || empty($rowValues['email']) || empty($rowValues['data_nascimento']) || empty($rowValues['equipe'])) {
                        $this->writeLog($logPath, "Linha {$line}: Ignorada devido a campos obrigatórios em branco (Nome/Email/Data Nascimento/Equipe).");
                        $stats['errors']++;
                        continue;
                    }

                    $cpfClean = $rowValues['cpf'] ? preg_replace('/\D/', '', $rowValues['cpf']) : null;
                    $emailClean = trim(strtolower($rowValues['email']));
                    $dataNascimento = $this->parseBirthDate($rowValues['data_nascimento']);

                    if (!$dataNascimento) {
                        $this->writeLog($logPath, "Linha {$line}: Data de nascimento inválida ('{$rowValues['data_nascimento']}').");
                        $stats['errors']++;
                        continue;
                    }

                    // Encontra a equipe adequada do movimento
                    $nomeEquipe = trim($rowValues['equipe']);
                    $equipe = null;
                    if (is_numeric($nomeEquipe)) {
                        $equipe = TipoEquipe::where('idt_equipe', (int)$nomeEquipe)
                            ->where('idt_movimento', $evento->idt_movimento)
                            ->first();
                    } else {
                        $equipe = TipoEquipe::where('idt_movimento', $evento->idt_movimento)
                            ->where('des_grupo', 'like', "%{$nomeEquipe}%")
                            ->first();
                    }

                    if (!$equipe) {
                        $this->writeLog($logPath, "Linha {$line}: Equipe '{$nomeEquipe}' não encontrada para o movimento deste evento.");
                        $stats['errors']++;
                        continue;
                    }

                    // Busca se a pessoa já está cadastrada
                    $pessoa = null;
                    if ($cpfClean) {
                        $pessoa = Pessoa::where('num_cpf_pessoa', $cpfClean)->first();
                    }
                    if (!$pessoa) {
                        $pessoa = Pessoa::where('eml_pessoa', $emailClean)->first();
                    }

                    $dadosPessoa = [
                        'nom_pessoa' => trim($rowValues['nome']),
                        'nom_apelido' => $rowValues['apelido'] ? trim($rowValues['apelido']) : null,
                        'tel_pessoa' => $rowValues['telefone'] ? preg_replace('/\D/', '', $rowValues['telefone']) : null,
                        'dat_nascimento' => $dataNascimento,
                        'des_endereco' => $rowValues['endereco'] ? trim($rowValues['endereco']) : null,
                        'eml_pessoa' => $emailClean,
                        'tip_genero' => $this->parseGenero($rowValues['genero']),
                        'tam_camiseta' => $this->parseTamanhoCamiseta($rowValues['tamanho_camiseta']),
                    ];

                    if ($cpfClean) {
                        $dadosPessoa['num_cpf_pessoa'] = $cpfClean;
                    }

                    if ($pessoa) {
                        // Atualiza a pessoa
                        $pessoa->update(array_filter($dadosPessoa, fn($v) => !is_null($v)));
                        $stats['updated']++;
                        $this->writeLog($logPath, "Linha {$line}: Pessoa existente atualizada (ID: {$pessoa->idt_pessoa}, Nome: {$pessoa->nom_pessoa}).");
                    } else {
                        // Cadastra nova pessoa
                        $pessoa = Pessoa::create($dadosPessoa);
                        $stats['created']++;
                        $this->writeLog($logPath, "Linha {$line}: Nova Pessoa cadastrada (ID: {$pessoa->idt_pessoa}, Nome: {$pessoa->nom_pessoa}).");
                    }

                    // Garante que há um usuário vinculado
                    $this->garantirUsuarioParaPessoa($pessoa);

                    // Cria ou atualiza o vínculo de Trabalhador no evento
                    $trabalhador = Trabalhador::updateOrCreate(
                        [
                            'idt_pessoa' => $pessoa->idt_pessoa,
                            'idt_evento' => $evento->idt_evento,
                            'idt_equipe' => $equipe->idt_equipe,
                        ],
                        [
                            'ind_coordenador' => $this->parseBoolean($rowValues['coordenador']),
                            'ind_primeira_vez' => $this->parseBoolean($rowValues['primeira_vez']),
                            'ind_recomendado' => $this->parseBoolean($rowValues['recomendado']),
                            'ind_lideranca' => $this->parseBoolean($rowValues['lideranca']),
                            'ind_destaque' => $this->parseBoolean($rowValues['destaque']),
                            'ind_avaliacao' => $this->parseBoolean($rowValues['avaliacao']),
                            'ind_camiseta_pediu' => $this->parseBoolean($rowValues['camiseta_pediu']),
                            'ind_camiseta_pagou' => $this->parseBoolean($rowValues['camiseta_pagou']),
                            'ind_taxa_pagou' => $this->parseBoolean($rowValues['taxa_pagou']),
                            'ind_presente' => $this->parseBoolean($rowValues['presente']),
                        ]
                    );

                    $stats['linked']++;
                    $this->writeLog($logPath, "Linha {$line}: Trabalhador vinculado à equipe '{$equipe->des_grupo}' com sucesso (ID Trabalhador: {$trabalhador->idt_trabalhador}).");

                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $this->writeLog($logPath, "Linha {$line}: Erro no processamento - " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }
        });
    }

    /**
     * Garante de forma segura que a Pessoa possua um User vinculado.
     */
    private function garantirUsuarioParaPessoa(Pessoa $pessoa): void
    {
        if ($pessoa->idt_usuario) {
            return;
        }

        // Verifica se já existe um User com o mesmo email
        $user = User::where('email', trim(strtolower($pessoa->eml_pessoa)))->first();

        if (!$user) {
            $senha = $pessoa->dat_nascimento ? $pessoa->dat_nascimento->format('Ymd') : '12345678';
            $user = User::create([
                'name' => $pessoa->nom_pessoa,
                'email' => $pessoa->eml_pessoa,
                'password' => Hash::make($senha),
                'role' => User::ROLE_USER,
            ]);
        }

        $pessoa->idt_usuario = $user->id;
        $pessoa->saveQuietly();
    }

    /**
     * Mapeia os cabeçalhos às colunas internas com tolerância.
     */
    private function mapHeaders(array $headers): array
    {
        $headerMap = [];
        foreach ($headers as $index => $rawHeader) {
            $normalized = $this->normalizeHeader($rawHeader);

            if (in_array($normalized, ['cpf', 'num_cpf_pessoa', 'num_cpf'])) {
                $headerMap['cpf'] = $index;
            } elseif (in_array($normalized, ['nome', 'nom_pessoa', 'fullname', 'nome_completo'])) {
                $headerMap['nome'] = $index;
            } elseif (in_array($normalized, ['apelido', 'nom_apelido', 'nickname'])) {
                $headerMap['apelido'] = $index;
            } elseif (in_array($normalized, ['telefone', 'tel_pessoa', 'tel', 'phone', 'celular'])) {
                $headerMap['telefone'] = $index;
            } elseif (in_array($normalized, ['email', 'eml_pessoa', 'mail'])) {
                $headerMap['email'] = $index;
            } elseif (in_array($normalized, ['data_nascimento', 'nascimento', 'dat_nascimento', 'birthdate', 'data_nasc'])) {
                $headerMap['data_nascimento'] = $index;
            } elseif (in_array($normalized, ['genero', 'sexo', 'tip_genero', 'gender'])) {
                $headerMap['genero'] = $index;
            } elseif (in_array($normalized, ['tamanho_camiseta', 'camiseta', 'tam_camiseta', 'tshirt'])) {
                $headerMap['tamanho_camiseta'] = $index;
            } elseif (in_array($normalized, ['endereco', 'des_endereco', 'address', 'localizacao'])) {
                $headerMap['endereco'] = $index;
            } elseif (in_array($normalized, ['cor_troca', 'tip_cor_troca', 'troca', 'cor'])) {
                $headerMap['cor_troca'] = $index;
            } elseif (in_array($normalized, ['taxa_pagou', 'ind_taxa_pagou', 'taxa', 'pagou_taxa'])) {
                $headerMap['taxa_pagou'] = $index;
            } elseif (in_array($normalized, ['presente', 'ind_presente', 'presenca'])) {
                $headerMap['presente'] = $index;
            } elseif (in_array($normalized, ['equipe', 'des_grupo', 'idt_equipe', 'grupo', 'team'])) {
                $headerMap['equipe'] = $index;
            } elseif (in_array($normalized, ['coordenador', 'ind_coordenador', 'coord'])) {
                $headerMap['coordenador'] = $index;
            } elseif (in_array($normalized, ['primeira_vez', 'ind_primeira_vez'])) {
                $headerMap['primeira_vez'] = $index;
            } elseif (in_array($normalized, ['recomendado', 'ind_recomendado'])) {
                $headerMap['recomendado'] = $index;
            } elseif (in_array($normalized, ['lideranca', 'ind_lideranca'])) {
                $headerMap['lideranca'] = $index;
            } elseif (in_array($normalized, ['destaque', 'ind_destaque'])) {
                $headerMap['destaque'] = $index;
            } elseif (in_array($normalized, ['avaliacao', 'ind_avaliacao'])) {
                $headerMap['avaliacao'] = $index;
            } elseif (in_array($normalized, ['camiseta_pediu', 'ind_camiseta_pediu'])) {
                $headerMap['camiseta_pediu'] = $index;
            } elseif (in_array($normalized, ['camiseta_pagou', 'ind_camiseta_pagou'])) {
                $headerMap['camiseta_pagou'] = $index;
            }
        }
        return $headerMap;
    }

    /**
     * Normaliza a string de cabeçalho para comparação flexível.
     */
    private function normalizeHeader(string $header): string
    {
        $header = trim(strtolower($header));
        // Remove acentos
        $header = preg_replace('/[áàâãä]/u', 'a', $header);
        $header = preg_replace('/[éèêë]/u', 'e', $header);
        $header = preg_replace('/[íìîï]/u', 'i', $header);
        $header = preg_replace('/[óòôõö]/u', 'o', $header);
        $header = preg_replace('/[úùûü]/u', 'u', $header);
        $header = preg_replace('/[ç]/u', 'c', $header);
        // Transforma caracteres especiais e espaços em underscores
        $header = preg_replace('/[^a-z0-9_]/', '_', $header);
        $header = preg_replace('/_+/', '_', $header);
        return trim($header, '_');
    }

    /**
     * Extrai dados de uma linha baseada no mapeamento de cabeçalho.
     */
    private function extractRowData(array $row, array $headerMap): array
    {
        $extracted = [];
        $keys = [
            'cpf', 'nome', 'apelido', 'telefone', 'email', 'data_nascimento', 'genero', 'tamanho_camiseta', 'endereco',
            'cor_troca', 'taxa_pagou', 'presente', 'equipe', 'coordenador', 'primeira_vez', 'recomendado', 'lideranca',
            'destaque', 'avaliacao', 'camiseta_pediu', 'camiseta_pagou'
        ];

        foreach ($keys as $key) {
            $index = $headerMap[$key] ?? null;
            // Se existir na planilha e o índice não exceder o tamanho da linha
            if ($index !== null && isset($row[$index])) {
                $extracted[$key] = trim($row[$index]);
            } else {
                $extracted[$key] = null;
            }
        }

        return $extracted;
    }

    /**
     * Detecta o delimitador CSV baseado no cabeçalho.
     */
    private function detectDelimiter(string $headerLine): string
    {
        $semicolons = substr_count($headerLine, ';');
        $commas = substr_count($headerLine, ',');
        return $semicolons > $commas ? ';' : ',';
    }

    /**
     * Analisa e formata data de nascimento.
     */
    private function parseBirthDate($value): ?string
    {
        if (!$value) return null;
        $value = trim($value);

        // DD/MM/AAAA
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        // AAAA-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Converte o gênero para o tipo adequado.
     */
    private function parseGenero($value): ?Genero
    {
        if (!$value) return null;
        $val = strtoupper(trim($value));

        if (in_array($val, ['M', 'MASCULINO'])) {
            return Genero::MASCULINO;
        }
        if (in_array($val, ['F', 'FEMININO'])) {
            return Genero::FEMININO;
        }

        return null;
    }

    /**
     * Converte o tamanho da camiseta para o enum correto.
     */
    private function parseTamanhoCamiseta($value): ?TamanhoCamiseta
    {
        if (!$value) return null;
        return TamanhoCamiseta::tryFrom(strtoupper(trim($value)));
    }

    /**
     * Converte a cor da troca para o enum correto.
     */
    private function parseCorTroca($value): ?string
    {
        if (!$value) return null;
        $val = strtolower(trim($value));
        $cor = CorTroca::tryFrom($val);
        return $cor ? $cor->value : null;
    }

    /**
     * Converte texto ou número em boolean de forma robusta.
     */
    private function parseBoolean($value): bool
    {
        if (!$value) return false;
        $val = trim(strtolower($value));
        return in_array($val, ['1', 'yes', 'y', 'sim', 's', 'true', 'confirmado', 'pago', 'presente']);
    }

    /**
     * Escreve linha no arquivo de log customizado.
     */
    private function writeLog(string $path, string $message): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $timestamp = now()->toDateTimeString();
        file_put_contents($path, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
}
