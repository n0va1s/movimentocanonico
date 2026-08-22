{{--
    View de impressão para Restrições de Saúde do evento.
    Recebe: $evento, $restricoes (array agrupado por pessoa), $filtroTipo (label), $filtroTipoCadastro (label)
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Restrições de Saúde — {{ $evento->des_evento }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            background: #fff;
            padding: 1cm 1.5cm;
        }

        /* ── Barra de ações (oculta na impressão) ── */
        .no-print {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.75rem 1rem;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            border-radius: 0.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-primary   { background: #2563eb; color: #fff; }
        .btn-secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .btn:hover { opacity: 0.88; }

        /* ── Cabeçalho ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111;
            padding-bottom: 0.6rem;
            margin-bottom: 1rem;
        }
        .header h1 { font-size: 14pt; font-weight: 700; }
        .header .meta { font-size: 9pt; color: #555; margin-top: 0.15rem; }

        /* ── Resumo ── */
        .summary {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            font-size: 9pt;
            color: #333;
        }
        .summary .item { display: flex; gap: 0.35rem; align-items: center; }
        .summary .label { font-weight: 700; }

        /* ── Tabela ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        thead th {
            background: #f4f4f5;
            border: 1px solid #d4d4d8;
            padding: 6px 8px;
            text-align: left;
            font-weight: 700;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        tbody td {
            border: 1px solid #d4d4d8;
            padding: 5px 8px;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #fafafa; }

        /* ── Badge de tipo ── */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-ALE { background: #fecaca; color: #991b1b; }
        .badge-INT { background: #fed7aa; color: #9a3412; }
        .badge-MED { background: #bfdbfe; color: #1e40af; }
        .badge-CUT { background: #fef08a; color: #854d0e; }
        .badge-PNE { background: #e9d5ff; color: #6b21a8; }
        .badge-VEG { background: #bbf7d0; color: #166534; }
        .badge-RES { background: #bae6fd; color: #0369a1; }
        .badge-default { background: #e5e7eb; color: #374151; }

        /* ── Restrição item ── */
        .restricao-item {
            margin-bottom: 3px;
        }
        .restricao-item:last-child { margin-bottom: 0; }
        .restricao-desc {
            font-size: 9pt;
            color: #333;
        }

        /* ── Rodapé ── */
        .footer {
            margin-top: 1.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #d4d4d8;
            font-size: 8pt;
            color: #888;
            display: flex;
            justify-content: space-between;
        }

        /* ── Seção de grupo ── */
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            margin: 1rem 0 0.5rem;
            padding: 4px 8px;
            background: #e5e7eb;
            border-left: 4px solid #374151;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0.5cm 1cm; }
            thead {
                background-color: #f4f4f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tbody tr:nth-child(even) {
                background: #fafafa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge, .badge-ALE, .badge-INT, .badge-MED, .badge-CUT, .badge-PNE, .badge-VEG, .badge-RES, .badge-default {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .section-title {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

{{-- Barra de ações --}}
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">
        🖨️ Imprimir / Salvar PDF
    </button>
    <a class="btn btn-secondary" href="{{ route('eventos.gerenciamento', $evento->idt_evento) }}">
        ← Voltar ao Gerenciamento
    </a>
</div>

{{-- Cabeçalho --}}
<div class="header">
    <div>
        <h1>Restrições de Saúde</h1>
        <div class="meta">{{ $evento->des_evento }} — Nº {{ $evento->num_evento }}</div>
        @if($evento->dat_inicio)
            <div class="meta">{{ $evento->dat_inicio->format('d/m/Y') }} a {{ $evento->dat_termino?->format('d/m/Y') ?? '---' }}</div>
        @endif
    </div>
    <div style="text-align: right;">
        <div class="meta">Paróquia Nossa Senhora do Lago</div>
        <div class="meta">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

{{-- Resumo --}}
<div class="summary">
    <div class="item">
        <span class="label">Total de pessoas:</span>
        <span>{{ count($restricoes) }}</span>
    </div>
    @if($filtroTipo)
        <div class="item">
            <span class="label">Filtro (Tipo de Restrição):</span>
            <span>{{ $filtroTipo }}</span>
        </div>
    @endif
    @if($filtroTipoCadastro)
        <div class="item">
            <span class="label">Filtro (Cadastro):</span>
            <span>{{ $filtroTipoCadastro }}</span>
        </div>
    @endif
</div>

@if (count($restricoes) === 0)
    <p style="text-align: center; padding: 2rem; color: #888; font-size: 12pt;">
        Nenhuma restrição encontrada com os filtros selecionados.
    </p>
@else
    {{-- Agrupa por tipo de cadastro --}}
    @php
        $porTipoCadastro = collect($restricoes)->groupBy('tipo_cadastro');
    @endphp

    @foreach ($porTipoCadastro as $tipoCad => $pessoas)
        <div class="section-title">{{ $tipoCad }}s ({{ count($pessoas) }})</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 26%;">Nome</th>
                    @if ($tipoCad === 'Participante')
                        <th style="width: 12%;">Grupo</th>
                    @else
                        <th style="width: 12%;">Equipe</th>
                    @endif
                    <th>Restrições</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pessoas as $idx => $r)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="font-weight: 600;">{{ $r->nome }}</td>
                        @if ($tipoCad === 'Participante')
                            <td>{{ $r->troca }}</td>
                        @else
                            <td>{{ $r->equipe }}</td>
                        @endif
                        <td>
                            @foreach ($r->itens as $item)
                                <div class="restricao-item">
                                    <span class="badge badge-{{ $item->tip_restricao_raw }}">{{ $item->tipo }}</span>
                                    <span class="restricao-desc">{{ $item->descricao }}</span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif

{{-- Rodapé --}}
<div class="footer">
    <span>Movimento Canônico — Sistema de Gestão</span>
    <span>Documento gerado automaticamente</span>
</div>

{{-- Auto-print quando ?print=1 --}}
@if(request('print'))
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif

</body>
</html>
