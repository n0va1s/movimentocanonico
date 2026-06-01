{{--
    View de impressão unificada para fichas VEM, ECC e SGM.
    Recebe: $ficha (com relações carregadas), $tipo ('VEM'|'ECC'|'SGM'), $rotaEdit
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ficha {{ $tipo }} — {{ $ficha->nom_candidato }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #111;
            background: #fff;
            padding: 1.5cm 2cm;
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
        .btn-danger    { background: #dc2626; color: #fff; }
        .btn:hover { opacity: 0.88; }

        /* ── Cabeçalho da ficha ── */
        .ficha-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111;
            padding-bottom: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .ficha-header h1 { font-size: 16pt; font-weight: 700; }
        .ficha-header .meta { font-size: 9pt; color: #555; margin-top: 0.25rem; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-green  { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-yellow { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }

        /* ── Foto ── */
        .foto-box {
            width: 3cm;
            height: 3.5cm;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            flex-shrink: 0;
        }
        .foto-box img { width: 100%; height: 100%; object-fit: cover; }
        .foto-box .sem-foto { font-size: 8pt; color: #9ca3af; text-align: center; padding: 0.5rem; }

        /* ── Seções ── */
        .section {
            margin-bottom: 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .section-title {
            background: #f3f4f6;
            padding: 0.4rem 0.75rem;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        .section-body { padding: 0.75rem; }

        /* ── Grid de campos ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem 1rem; }
        .field { margin-bottom: 0.35rem; }
        .field label { font-size: 7.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 0.1rem; }
        .field span  { font-size: 10pt; color: #111; display: block; border-bottom: 1px dotted #d1d5db; padding-bottom: 0.1rem; min-height: 1.2em; }

        /* ── Restrições ── */
        .restricao-tag {
            display: inline-block;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 0.25rem;
            padding: 0.15rem 0.5rem;
            font-size: 8pt;
            font-weight: 600;
            margin: 0.15rem 0.15rem 0 0;
        }

        /* ── Rodapé ── */
        .ficha-footer {
            margin-top: 2rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
            font-size: 8pt;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
        }

        /* ── Impressão ── */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0.5cm 1cm; }
            @page { size: A4; margin: 1cm; }
            .section { break-inside: avoid; }
        }
    </style>
</head>
<body>

{{-- ── Barra de ações (não imprime) ── --}}
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">
        🖨️ Imprimir
    </button>
    <a href="{{ $rotaEdit }}" class="btn btn-secondary">
        ✏️ Editar Ficha
    </a>
    <button class="btn btn-secondary" onclick="window.close()">
        ✕ Fechar
    </button>
    @if($ficha->ind_aprovado)
        <span class="badge badge-green">✓ Aprovada</span>
    @else
        <span class="badge badge-yellow">⏳ Pendente</span>
    @endif
</div>

{{-- ── Cabeçalho ── --}}
<div class="ficha-header">
    <div>
        <h1>Ficha de Inscrição — {{ $tipo }}</h1>
        <p class="meta">
            Evento: <strong>{{ $ficha->evento->des_evento ?? '—' }}</strong>
            &nbsp;|&nbsp;
            Nº {{ $ficha->evento->num_evento ?? '—' }}
            &nbsp;|&nbsp;
            Ficha #{{ $ficha->idt_ficha }}
        </p>
        <p class="meta" style="margin-top:0.25rem;">
            Preenchida em: {{ $ficha->created_at?->format('d/m/Y H:i') ?? '—' }}
        </p>
    </div>
    <div class="foto-box">
        @if($ficha->foto?->med_foto)
            <img src="{{ asset('storage/' . $ficha->foto->med_foto) }}" alt="Foto do candidato" />
        @else
            <span class="sem-foto">Sem foto</span>
        @endif
    </div>
</div>

{{-- ── Dados do Candidato ── --}}
<div class="section">
    <div class="section-title">Dados do Candidato</div>
    <div class="section-body">
        <div class="grid-2">
            <div class="field"><label>Nome Completo</label><span>{{ $ficha->nom_candidato }}</span></div>
            <div class="field"><label>Apelido</label><span>{{ $ficha->nom_apelido ?: '—' }}</span></div>
            <div class="field"><label>CPF</label><span>{{ $ficha->num_cpf_candidato ?: '—' }}</span></div>
            <div class="field"><label>Gênero</label><span>{{ $ficha->tip_genero?->label() ?? '—' }}</span></div>
            <div class="field"><label>Data de Nascimento</label><span>{{ $ficha->dat_nascimento?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="field"><label>Telefone / WhatsApp</label><span>{{ $ficha->tel_candidato ?: '—' }}</span></div>
            <div class="field"><label>E-mail</label><span>{{ $ficha->eml_candidato ?: '—' }}</span></div>
            <div class="field"><label>Endereço</label><span>{{ $ficha->des_endereco ?: '—' }}</span></div>
            <div class="field"><label>Tamanho Camiseta</label><span>{{ $ficha->tam_camiseta?->value ?? '—' }}</span></div>
            <div class="field"><label>Como Soube</label><span>{{ $ficha->tip_como_soube?->label() ?? '—' }}</span></div>
            @if($tipo === 'ECC')
                <div class="field"><label>Profissão</label><span>{{ $ficha->nom_profissao ?: '—' }}</span></div>
            @endif
        </div>
    </div>
</div>

{{-- ── VEM: Responsáveis ── --}}
@if($tipo === 'VEM' && $ficha->fichaVem)
    @php $vem = $ficha->fichaVem; @endphp
    <div class="section">
        <div class="section-title">Responsáveis</div>
        <div class="section-body">
            <div class="grid-2">
                <div class="field"><label>Nome da Mãe</label><span>{{ $vem->nom_mae ?: '—' }}</span></div>
                <div class="field"><label>Tel. Mãe</label><span>{{ $vem->tel_mae ?: '—' }}</span></div>
                <div class="field"><label>Nome do Pai</label><span>{{ $vem->nom_pai ?: '—' }}</span></div>
                <div class="field"><label>Tel. Pai</label><span>{{ $vem->tel_pai ?: '—' }}</span></div>
                <div class="field"><label>Responsável</label><span>{{ $vem->nom_responsavel ?: '—' }}</span></div>
                <div class="field"><label>Tel. Responsável</label><span>{{ $vem->tel_responsavel ?: '—' }}</span></div>
                <div class="field"><label>Mora com quem</label><span>{{ $vem->des_mora_quem ?: '—' }}</span></div>
                <div class="field"><label>Onde Estuda</label><span>{{ $vem->des_onde_estuda ?: '—' }}</span></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Dados Religiosos</div>
        <div class="section-body">
            <div class="grid-3">
                <div class="field"><label>Católico</label><span>{{ $ficha->ind_catolico ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>Batizado</label><span>{{ $vem->ind_batizado ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>1ª Comunhão</label><span>{{ $vem->ind_primeira_comunhao ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>Crismado</label><span>{{ $vem->ind_crismado ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>Paróquia</label><span>{{ $vem->nom_paroquia ?: '—' }}</span></div>
            </div>
        </div>
    </div>
@endif

{{-- ── ECC: Cônjuge ── --}}
@if($tipo === 'ECC' && $ficha->fichaEcc)
    @php $ecc = $ficha->fichaEcc; @endphp
    <div class="section">
        <div class="section-title">Cônjuge</div>
        <div class="section-body">
            <div class="grid-2">
                <div class="field"><label>Nome</label><span>{{ $ecc->nom_conjuge ?: '—' }}</span></div>
                <div class="field"><label>Apelido</label><span>{{ $ecc->nom_apelido_conjuge ?: '—' }}</span></div>
                <div class="field"><label>CPF</label><span>{{ $ecc->num_cpf_conjuge ?: '—' }}</span></div>
                <div class="field"><label>Gênero</label><span>{{ $ecc->tip_genero_conjuge ?: '—' }}</span></div>
                <div class="field"><label>Nascimento</label><span>{{ $ecc->dat_nascimento_conjuge ? \Carbon\Carbon::parse($ecc->dat_nascimento_conjuge)->format('d/m/Y') : '—' }}</span></div>
                <div class="field"><label>Telefone</label><span>{{ $ecc->tel_conjuge ?: '—' }}</span></div>
                <div class="field"><label>E-mail</label><span>{{ $ecc->eml_conjuge ?: '—' }}</span></div>
                <div class="field"><label>Profissão</label><span>{{ $ecc->nom_profissao_conjuge ?: '—' }}</span></div>
                <div class="field"><label>Estado Civil</label><span>{{ $ecc->tip_estado_civil ?: '—' }}</span></div>
                <div class="field"><label>Data Casamento</label><span>{{ $ecc->dat_casamento ? \Carbon\Carbon::parse($ecc->dat_casamento)->format('d/m/Y') : '—' }}</span></div>
                <div class="field"><label>Paróquia</label><span>{{ $ecc->nom_paroquia ?: '—' }}</span></div>
                <div class="field"><label>Qtd. Filhos</label><span>{{ $ecc->qtd_filhos ?? '0' }}</span></div>
            </div>
        </div>
    </div>

    @if($ecc->filhos && $ecc->filhos->count() > 0)
        <div class="section">
            <div class="section-title">Filhos</div>
            <div class="section-body">
                @foreach($ecc->filhos as $filho)
                    <div class="grid-3" style="margin-bottom:0.5rem; padding-bottom:0.5rem; border-bottom:1px dotted #e5e7eb;">
                        <div class="field"><label>Nome</label><span>{{ $filho->nom_filho }}</span></div>
                        <div class="field"><label>Nascimento</label><span>{{ $filho->dat_nascimento_filho ? \Carbon\Carbon::parse($filho->dat_nascimento_filho)->format('d/m/Y') : '—' }}</span></div>
                        <div class="field"><label>CPF</label><span>{{ $filho->num_cpf_filho ?: '—' }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif

{{-- ── SGM: Dados complementares ── --}}
@if($tipo === 'SGM' && $ficha->fichaSGM)
    @php $sgm = $ficha->fichaSGM; @endphp
    <div class="section">
        <div class="section-title">Filiação</div>
        <div class="section-body">
            <div class="grid-2">
                <div class="field"><label>Nome da Mãe</label><span>{{ $sgm->nom_mae ?: '—' }}</span></div>
                <div class="field"><label>Tel. Mãe</label><span>{{ $sgm->tel_mae ?: '—' }}</span></div>
                <div class="field"><label>Nome do Pai</label><span>{{ $sgm->nom_pai ?: '—' }}</span></div>
                <div class="field"><label>Tel. Pai</label><span>{{ $sgm->tel_pai ?: '—' }}</span></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Escolaridade e Religião</div>
        <div class="section-body">
            <div class="grid-2">
                <div class="field"><label>Escolaridade</label><span>{{ $sgm->tip_escolaridade?->label() ?? '—' }}</span></div>
                <div class="field"><label>Situação</label><span>{{ $sgm->tip_escolaridade_situacao?->label() ?? '—' }}</span></div>
                <div class="field"><label>Curso</label><span>{{ $sgm->des_curso ?: '—' }}</span></div>
                <div class="field"><label>Instituição</label><span>{{ $sgm->nom_instituicao ?: '—' }}</span></div>
                <div class="field"><label>Religião</label><span>{{ $sgm->tip_religiao?->label() ?? '—' }}</span></div>
                <div class="field"><label>Paróquia</label><span>{{ $sgm->nom_paroquia ?: '—' }}</span></div>
                <div class="field"><label>Batismo</label><span>{{ $sgm->ind_batismo ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>Eucaristia</label><span>{{ $sgm->ind_eucaristia ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>Crisma</label><span>{{ $sgm->ind_crisma ? 'Sim' : 'Não' }}</span></div>
                <div class="field"><label>Quem Convidou</label><span>{{ $sgm->nom_convidou ?: '—' }}</span></div>
            </div>
        </div>
    </div>
@endif

{{-- ── Restrições de Saúde ── --}}
@if($ficha->fichaSaude && $ficha->fichaSaude->count() > 0)
    <div class="section">
        <div class="section-title">Restrições de Saúde</div>
        <div class="section-body">
            @foreach($ficha->fichaSaude as $saude)
                <span class="restricao-tag">{{ $saude->restricao?->des_restricao ?? $saude->idt_restricao }}</span>
                @if($saude->txt_complemento)
                    <span style="font-size:8pt; color:#555;"> — {{ $saude->txt_complemento }}</span>
                @endif
            @endforeach
        </div>
    </div>
@endif

{{-- ── Observações ── --}}
@if($ficha->txt_observacao)
    <div class="section">
        <div class="section-title">Observações</div>
        <div class="section-body">
            <p style="font-size:10pt;">{{ $ficha->txt_observacao }}</p>
        </div>
    </div>
@endif

{{-- ── Rodapé ── --}}
<div class="ficha-footer">
    <span>Ficha #{{ $ficha->idt_ficha }} — {{ $tipo }}</span>
    <span>Impresso em {{ now()->format('d/m/Y H:i') }}</span>
</div>

{{-- Auto-print quando ?print=1 ── --}}
@if(request('print'))
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif

</body>
</html>
