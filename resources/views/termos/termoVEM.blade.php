<x-layouts.public :title="'Documentos Legais - VEM'">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        .doc-article-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1e3a8a;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .doc-p {
            margin-bottom: 0.75rem;
            line-height: 1.6;
            text-align: justify;
        }

        .doc-subitem {
            padding-left: 1rem;
            margin-bottom: 0.6rem;
            line-height: 1.6;
        }

        .doc-ul {
            list-style: none;
            padding-left: 1.2rem;
            margin-bottom: 0.75rem;
        }

        .doc-ul li {
            position: relative;
            padding-left: 1.2rem;
            margin-bottom: 0.4rem;
            line-height: 1.6;
        }

        .doc-ul li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #3b82f6;
            font-weight: bold;
        }

        .doc-warning {
            background-color: #fefce8;
            border-left: 4px solid #eab308;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #854d0e;
        }
    </style>

    <section class="px-4 py-6 w-full max-w-4xl mx-auto" x-data="{ tab: 'uso', gerando: false }" aria-labelledby="page-title">

        {{-- Cabeçalho e Ações --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <flux:heading size="xl" id="page-title" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Documentos Legais - VEM</flux:heading>

            <div class="flex items-center gap-3">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md transition-colors border border-gray-300 dark:border-zinc-600">
                    Voltar
                </a>

                <button @click="gerarPDF($el)" :disabled="gerando"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-md transition-colors shadow-sm">
                    <span x-text="gerando ? 'Gerando PDF...' : 'Baixar PDF Único'"></span>
                </button>
            </div>
        </div>

        {{-- Navegação por Abas --}}
        <div class="border-b border-gray-200 dark:border-zinc-700 mb-6">
            <nav class="flex space-x-8">
                <button @click="tab = 'uso'"
                    :class="tab === 'uso' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all">Participação</button>
                <button @click="tab = 'privacidade'"
                    :class="tab === 'privacidade' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all">Privacidade</button>
                <button @click="tab = 'consentimento'"
                    :class="tab === 'consentimento' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all">Consentimento</button>
            </nav>
        </div>

        {{-- Conteúdo --}}
        <div
            class="bg-white dark:bg-zinc-900 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-zinc-800 text-gray-700 dark:text-gray-300 text-sm">

            {{-- ABA 1: TERMOS DE PARTICIPAÇÃO --}}
            <div x-show="tab === 'uso'">
                <h2 class="text-xl font-bold mb-1 text-blue-600 uppercase">Termos e Condições de Uso</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6 font-bold uppercase">PARTE I — TERMOS E
                    CONDIÇÕES DE PARTICIPAÇÃO</p>

                <p class="doc-article-title">1. IDENTIFICAÇÃO DO CONTROLADOR DE DADOS</p>
                <p class="doc-p">A Paróquia Nossa Senhora do Lago, pessoa jurídica de direito privado, inscrita no CNPJ
                    sob o nº 00.108.217/0052-60, com sede em Brasília – DF, é a controladora dos dados pessoais
                    coletados por meio desta plataforma, nos termos da Lei nº 13.709/2018 (Lei Geral de Proteção de
                    Dados Pessoais – LGPD).</p>
                <p class="doc-p">O Movimento VEM é um programa de encontro de jovens vinculado à Paróquia Nossa Senhora
                    do Lago, voltado a participantes com idade entre 12 e 15 anos, contando também com a colaboração de
                    jovens trabalhadores voluntários que já vivenciaram o encontro como participantes.</p>

                <p class="doc-article-title">2. ATENÇÃO ESPECIAL: PARTICIPAÇÃO DE MENORES DE IDADE</p>
                <div class="doc-warning">
                    ⚠️ Todos os participantes do VEM na condição de jovem (12 a 15 anos) são menores de idade. Por isso,
                    este termo deve ser lido, compreendido e aceito pelo pai, mãe ou responsável legal, que assume
                    integralmente a responsabilidade pelas declarações aqui prestadas.
                </div>
                <p class="doc-p">O tratamento de dados de crianças e adolescentes observa rigorosamente:</p>
                <ul class="doc-ul">
                    <li>Lei nº 8.069/1990 – Estatuto da Criança e do Adolescente (ECA);</li>
                    <li>Lei nº 14.811/2024 – ECA Digital, que estabelece proteções específicas para crianças e
                        adolescentes no ambiente digital;</li>
                    <li>Art. 14 da Lei nº 13.709/2018 (LGPD) – que determina que o tratamento deve ser realizado no
                        melhor interesse do menor;</li>
                    <li>Marco Civil da Internet (Lei nº 12.965/2014) – proteção à privacidade digital.</li>
                </ul>

                <p class="doc-article-title">3. VERACIDADE DAS INFORMAÇÕES</p>
                <p class="doc-p">O responsável legal pelo participante garante que todas as informações prestadas no
                    formulário de cadastro são verdadeiras, completas e atualizadas. A prestação de informações falsas
                    poderá comprometer a segurança do menor, implicar responsabilidade civil/penal e acarretar
                    cancelamento da inscrição.</p>

                <p class="doc-article-title">4. ELEGIBILIDADE E PERFIS DE PARTICIPAÇÃO</p>
                <p class="doc-subitem"><strong>4.1 JOVEM PARTICIPANTE:</strong> Pessoa entre 12 e 15 anos completos que
                    viverá a experiência do VEM pela primeira vez. Requer autorização expressa do responsável legal e
                    vínculo paroquial.</p>
                <p class="doc-subitem"><strong>4.2 JOVEM TRABALHADOR:</strong> Jovem que já vivenciou o VEM e integra a
                    equipe voluntária. Deve manter sigilo absoluto sobre as dinâmicas e agir como referência de conduta
                    cristã.</p>

                <p class="doc-article-title">5. REGRAS DE CONDUTA E CONVIVÊNCIA</p>
                <p class="doc-subitem"><strong>5.1</strong> Respeito irrestrito à dignidade de todos, vedando-se
                    qualquer forma de discriminação, assédio ou bullying (Lei nº 13.185/2015).</p>
                <p class="doc-subitem"><strong>5.2</strong> Cumprimento integral de horários e rotinas.</p>
                <p class="doc-subitem"><strong>5.3</strong> Cuidado com o patrimônio paroquial.</p>
                <p class="doc-subitem"><strong>5.4</strong> Proibição absoluta de bebidas alcoólicas, substâncias
                    psicoativas e tabaco.</p>

                <p class="doc-article-title">6. PROTEÇÃO INTEGRAL DO ADOLESCENTE</p>
                <p class="doc-p">A coordenação adotará medidas necessárias para garantir a integridade física,
                    emocional e moral dos jovens. Situações de risco serão comunicadas aos responsáveis e, se
                    necessário, ao Conselho Tutelar (Art. 13 e 245 do ECA).</p>

                <p class="doc-article-title">7. USO DE DISPOSITIVOS ELETRÔNICOS</p>
                <p class="doc-p">O uso de celulares será restrito durante o encontro para favorecer a integração e
                    proteção. É vedada a gravação ou fotografia de atividades internas sem autorização.</p>

                <p class="doc-article-title">8. CONFIRMAÇÃO DE VAGA E CANCELAMENTO</p>
                <p class="doc-p">O cadastro não garante vaga. A confirmação oficial será comunicada após verificação de
                    critérios. Em caso de desistência, comunicar com antecedência para redistribuição da vaga.</p>
            </div>

            {{-- ABA 2: POLÍTICA DE PRIVACIDADE --}}
            <div x-show="tab === 'privacidade'">
                <h2 class="text-xl font-bold mb-1 text-blue-600 uppercase">Política de Privacidade</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6 font-bold uppercase">PARTE II — POLÍTICA DE
                    PRIVACIDADE E PROTEÇÃO DE DADOS</p>

                <p class="doc-article-title">11. BASE LEGAL PARA O TRATAMENTO DE DADOS</p>
                <p class="doc-p">Fundamenta-se no Art. 7º (I, II) e Art. 14 da LGPD (consentimento parental em
                    destaque) e na Lei nº 14.811/2024 (ECA Digital).</p>

                <p class="doc-article-title">12. DADOS COLETADOS</p>
                <p class="doc-subitem"><strong>12.1 Menor:</strong> Nome, nascimento, RG e fotografia.</p>
                <p class="doc-subitem"><strong>12.2 Responsável:</strong> Nome, parentesco, CPF, telefone e e-mail.</p>
                <p class="doc-subitem"><strong>12.4 Dados Sensíveis de Saúde:</strong> Alergias, restrições alimentares,
                    tipo sanguíneo, condições psicológicas/neurológicas e plano de saúde (Tratados sob o Art. 11 da
                    LGPD).</p>

                <p class="doc-article-title">13. FINALIDADES DO TRATAMENTO</p>
                <p class="doc-p">Os dados serão usados exclusivamente para organização logística, garantia de segurança
                    médica/alimentar, contatos de emergência e cumprimento de obrigações legais.</p>

                <p class="doc-article-title">14. COMPARTILHAMENTO DE DADOS</p>
                <p class="doc-p">Acesso restrito à Coordenação e Secretaria. Dados de saúde compartilhados apenas com
                    equipes de Cozinha e Primeiros Socorros. Não há venda ou cessão de dados a terceiros externos.</p>

                <p class="doc-article-title">15. USO DE IMAGEM E VOZ</p>
                <p class="doc-p">Autorizado para divulgação nos canais oficiais da Paróquia (Art. 17 do ECA).
                    Responsáveis com restrições devem comunicar formalmente antes do encontro.</p>

                <p class="doc-article-title">16. ARMAZENAMENTO E RETENÇÃO</p>
                <p class="doc-p">Dados armazenados em ambiente seguro. Após o encerramento do vínculo, serão
                    anonimizados ou eliminados, salvo conservação obrigatória por lei.</p>
            </div>

            {{-- ABA 3: CONSENTIMENTO --}}
            <div x-show="tab === 'consentimento'">
                <h2 class="text-xl font-bold mb-1 text-blue-600 uppercase">Termo de Consentimento e Autorização</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6 font-bold uppercase">PARTE III — TERMO DE
                    CONSENTIMENTO E AUTORIZAÇÃO</p>

                <p class="doc-article-title">17. QUALIFICAÇÃO DO RESPONSÁVEL LEGAL</p>
                <p class="doc-p">O declarante afirma ser o responsável legal pelo menor, possuindo plena capacidade
                    civil e respondendo pela veracidade das informações.</p>

                <p class="doc-article-title">18. AUTORIZAÇÃO DE PARTICIPAÇÃO</p>
                <p class="doc-p">Autorizo o menor sob minha responsabilidade a participar do Encontro do VEM, ciente
                    das regras e do regime de restrição eletrônica.</p>

                <p class="doc-article-title">19. CONSENTIMENTO LGPD + ECA DIGITAL</p>
                <p class="doc-p">Autorizo a coleta e tratamento de dados pessoais e sensíveis de saúde para segurança
                    médica e organização, conforme Art. 11 e 14 da LGPD e Lei nº 14.811/2024.</p>

                <p class="doc-article-title">20. AUTORIZAÇÃO DE USO DE IMAGEM E VOZ</p>
                <p class="doc-p">Autorizo o uso gratuito de imagem e voz captadas no encontro para divulgação nos
                    canais oficiais da Paróquia Nossa Senhora do Lago.</p>

                <p class="doc-article-title">21. AUTORIZAÇÃO PARA ATENDIMENTO MÉDICO</p>
                <p class="doc-p">Em caso de emergência, autorizo primeiros socorros, acionamento de SAMU/Bombeiros e
                    encaminhamento hospitalar, bem como o compartilhamento de dados de saúde com os médicos atendentes.
                </p>

                <p class="doc-article-title">22. VALIDADE DA ASSINATURA ELETRÔNICA</p>
                <p class="doc-p">A confirmação eletrônica possui validade jurídica equivalente à manuscrita (Lei nº
                    14.063/2020). O registro de IP, data e hora será armazenado como evidência do consentimento.</p>

                <div class="mt-8 pt-6 border-t border-gray-200 text-center text-xs text-gray-500">
                    <p>Paróquia Nossa Senhora do Lago — CNPJ 00.108.217/0052-60 — Brasília – DF</p>
                    <p>Data e hora de aceite registradas automaticamente pelo sistema.</p>
                </div>
            </div>
        </div>
    </section>

    <script>
        function gerarPDF(elementoBotao) {
            const alpineData = Alpine.$data(elementoBotao);
            alpineData.gerando = true;

            try {
                const {
                    jsPDF
                } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');

                const mL = 20,
                    mR = 20;
                const pageW = pdf.internal.pageSize.getWidth();
                const pageH = pdf.internal.pageSize.getHeight();
                const usableW = pageW - mL - mR;
                let y = 20;

                function checkY(needed) {
                    if (y + (needed || 10) > pageH - 18) {
                        pdf.addPage();
                        y = 20;
                    }
                }

                function addText(text, size, rgb, bold, after) {
                    pdf.setFontSize(size);
                    pdf.setTextColor(rgb[0], rgb[1], rgb[2]);
                    pdf.setFont('helvetica', bold ? 'bold' : 'normal');
                    var lines = pdf.splitTextToSize(text, usableW);
                    var lh = size * 0.42;
                    for (var i = 0; i < lines.length; i++) {
                        checkY(lh + 2);
                        pdf.text(lines[i], mL, y);
                        y += lh + 1.5;
                    }
                    y += (after || 0);
                }

                function p(text) {
                    addText(text, 10, [50, 50, 50], false, 3);
                }

                function sectionTitle(num, title) {
                    checkY(18);
                    pdf.setFillColor(29, 78, 216);
                    pdf.rect(mL, y - 6, 2, 9, 'F');
                    pdf.setFontSize(13);
                    pdf.setFont('helvetica', 'bold');
                    pdf.setTextColor(29, 78, 216);
                    pdf.text(num + '. ' + title, mL + 5, y);
                    y += 11;
                }

                function articleTitle(text) {
                    checkY(12);
                    y += 2;
                    addText(text, 10, [20, 20, 20], true, 2);
                }

                function subItem(text) {
                    checkY(10);
                    addText(text, 10, [50, 50, 50], false, 1);
                }

                function bullet(text) {
                    checkY(10);
                    var lines = pdf.splitTextToSize(text, usableW - 6);
                    var lh = 10 * 0.42;
                    pdf.setFontSize(10);
                    pdf.setFont('helvetica', 'normal');
                    pdf.setTextColor(50, 50, 50);
                    for (var i = 0; i < lines.length; i++) {
                        checkY(lh + 2);
                        pdf.text((i === 0 ? '\u2022   ' : '     ') + lines[i], mL + 3, y);
                        y += lh + 1.5;
                    }
                    y += 1;
                }

                function hr(rgb) {
                    var c = rgb || [210, 210, 210];
                    checkY(8);
                    pdf.setDrawColor(c[0], c[1], c[2]);
                    pdf.setLineWidth(0.3);
                    pdf.line(mL, y, pageW - mR, y);
                    y += 7;
                }

                // ── Cabeçalho ────────────────────────────────────────────────
                pdf.setFillColor(29, 78, 216);
                pdf.rect(0, 0, pageW, 16, 'F');
                pdf.setFontSize(12);
                pdf.setFont('helvetica', 'bold');
                pdf.setTextColor(255, 255, 255);
                pdf.text('MOVIMENTO VEM', pageW / 2, 7, {
                    align: 'center'
                });
                pdf.setFontSize(9);
                pdf.setFont('helvetica', 'normal');
                pdf.text('Documentos Legais e Consentimento Unificado', pageW / 2, 13, {
                    align: 'center'
                });
                y = 26;
                hr([29, 78, 216]);

                // ════════════════════════════════════════════════════════════
                // PARTE I — TERMOS DE USO (CONTEÚDO DOCX VEM)
                // ════════════════════════════════════════════════════════════
                sectionTitle(1, 'Termos e Condições de Uso');

                articleTitle('1. IDENTIFICAÇÃO DO CONTROLADOR DE DADOS');
                p(
                'A Paróquia Nossa Senhora do Lago, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 00.108.217/0052-60, com sede em Brasília – DF, é a controladora dos dados pessoais coletados por meio desta plataforma, nos termos da Lei nº 13.709/2018 (Lei Geral de Proteção de Dados Pessoais – LGPD).');
                p(
                'O Movimento VEM é um programa de encontro de jovens vinculado à Paróquia Nossa Senhora do Lago, voltado a participantes com idade entre 12 e 15 anos, contando também com a colaboração de jovens trabalhadores voluntários que já vivenciaram o encontro como participantes.');

                articleTitle('2. ATENÇÃO ESPECIAL: PARTICIPAÇÃO DE MENORES DE IDADE');
                p(
                'Todos os participantes do VEM na condição de jovem (12 a 15 anos) são menores de idade. Por isso, este termo deve ser lido, compreendido e aceito pelo pai, mãe ou responsável legal, que assume integralmente a responsabilidade pelas declarações aqui prestadas.');
                p(
                'O tratamento de dados de crianças e adolescentes observa rigorosamente a Lei nº 8.069/1990 (ECA), a Lei nº 14.811/2024 (ECA Digital) e o Art. 14 da LGPD.');

                articleTitle('3. VERACIDADE DAS INFORMAÇÕES');
                p(
                'O responsável legal pelo participante garante que todas as informações prestadas no formulário de cadastro (dados de identificação, contato, saúde e documentos) são verdadeiras, completas e atualizadas.');
                p(
                'A prestação de informações falsas ou omissão de dados relevantes poderá comprometer a segurança do menor e implicar responsabilidade civil e/ou penal do declarante.');

                articleTitle('4. ELEGIBILIDADE E PERFIS DE PARTICIPAÇÃO');
                p('O Movimento VEM admite dois perfis de participação:');
                subItem(
                    '4.1   Jovem Participante: Pessoa com idade entre 12 e 15 anos completos que participará do encontro pela primeira vez. Requer autorização expressa do responsável legal e vínculo paroquial conforme critérios da coordenação.');
                subItem(
                    '4.2   Jovem Trabalhador: Jovem que já vivenciou o VEM como participante e retorna para integrar a equipe de trabalho voluntário. Deve manter sigilo absoluto sobre as dinâmicas e agir como referência de conduta cristã.');

                articleTitle('5. REGRAS DE CONDUTA E CONVIVÊNCIA');
                p('A inscrição no VEM implica o conhecimento e a aceitação das seguintes normas:');
                bullet(
                    'Respeito irrestrito à dignidade de todos os presentes, vedando-se qualquer forma de discriminação, assédio ou bullying (Lei nº 13.185/2015);');
                bullet('Cumprimento integral dos horários, rotinas e atividades propostas;');
                bullet(
                    'Cuidado e zelo com o patrimônio da Paróquia. Danos causados por negligência serão de responsabilidade do responsável legal;');
                bullet('Proibição absoluta do consumo de bebidas alcoólicas, substâncias psicoativas e tabaco.');

                articleTitle('6. PROTEÇÃO INTEGRAL DO ADOLESCENTE');
                p(
                'A equipe de coordenação e os tios dirigentes adotarão todas as medidas necessárias para garantir a integridade física, emocional e moral dos jovens durante o encontro. Situações de risco serão comunicadas imediatamente aos responsáveis e, se necessário, às autoridades competentes.');

                articleTitle('7. USO DE DISPOSITIVOS ELETRÔNICOS');
                p(
                'O uso de celulares e dispositivos eletrônicos será restrito durante as atividades do encontro para favorecer a integração e proteção. Em caso de necessidade urgente, a coordenação disponibilizará canal para contato com os familiares.');

                articleTitle('8. CONFIRMAÇÃO DE VAGA E CANCELAMENTO');
                p(
                'O preenchimento do cadastro não garante automaticamente a vaga. A confirmação oficial será comunicada após verificação de critérios. Em caso de desistência, o responsável deve comunicar a coordenação com antecedência.');

                y += 4;
                hr();

                // ════════════════════════════════════════════════════════════
                // PARTE II — POLÍTICA DE PRIVACIDADE
                // ════════════════════════════════════════════════════════════
                sectionTitle(2, 'Política de Privacidade (LGPD)');

                articleTitle('1. BASE LEGAL');
                p(
                'O tratamento fundamenta-se no Art. 7º (I, II) e Art. 14 da LGPD (consentimento parental em destaque) e na Lei nº 14.811/2024 (ECA Digital).');

                articleTitle('2. DADOS COLETADOS');
                subItem('2.1   Dados do Menor: Nome completo, data de nascimento, documento de identidade e fotografia.');
                subItem('2.2   Dados do Responsável: Nome, CPF, telefone e e-mail.');
                subItem(
                    '2.3   Dados Sensíveis de Saúde: Alergias, restrições alimentares, tipo sanguíneo, condições médicas e plano de saúde.');

                articleTitle('3. FINALIDADES');
                p(
                'Os dados serão utilizados exclusivamente para logística do encontro, segurança alimentar e médica, comunicações de emergência e cumprimento de obrigações legais.');

                articleTitle('4. COMPARTILHAMENTO');
                p(
                'Acesso restrito à Coordenação e Secretaria. Dados de saúde compartilhados apenas com as equipes de Cozinha e Saúde. Não há venda ou cessão de dados para fins comerciais.');

                y += 4;
                hr();

                // ════════════════════════════════════════════════════════════
                // PARTE III — CONSENTIMENTO
                // ════════════════════════════════════════════════════════════
                sectionTitle(3, 'Termo de Consentimento e Autorização');

                articleTitle('1. DECLARAÇÃO DE RESPONSABILIDADE');
                p(
                'O declarante afirma ser o responsável legal pelo menor, possuindo plena capacidade civil e respondendo pela veracidade das informações.');

                articleTitle('2. AUTORIZAÇÃO DE PARTICIPAÇÃO');
                p(
                'Autorizo o menor sob minha responsabilidade a participar do Encontro do VEM, ciente das regras de conduta e do regime de restrição eletrônica.');

                articleTitle('3. CONSENTIMENTO LGPD');
                p(
                'Autorizo a Paróquia e o VEM a coletar e tratar os dados pessoais e sensíveis de saúde para fins de segurança e organização, conforme Art. 11 e 14 da LGPD.');

                articleTitle('4. USO DE IMAGEM E VOZ');
                p(
                'Autorizo o uso gratuito da imagem e voz do menor captadas durante o encontro para fins de divulgação nos canais oficiais da Paróquia.');

                articleTitle('5. ATENDIMENTO MÉDICO');
                p(
                'Em caso de emergência, autorizo primeiros socorros, acionamento de serviços de emergência (SAMU/Bombeiros) e encaminhamento hospitalar, bem como o compartilhamento de dados de saúde com os médicos atendentes.');

                articleTitle('6. VALIDADE JURÍDICA');
                p(
                'A confirmação eletrônica desta inscrição possui validade jurídica equivalente à assinatura manuscrita (Lei nº 14.063/2020). O sistema registra automaticamente IP, data e hora do aceite.');

                y += 10;
                addText('Paróquia Nossa Senhora do Lago — CNPJ 00.108.217/0052-60 — Brasília – DF', 9, [100, 100, 100],
                    false, 1);
                addText('Data e hora de aceite registradas automaticamente pelo sistema.', 9, [100, 100, 100], false, 0);

                // ── Assinaturas ──────────────────────────────────────────────
                checkY(30);
                y += 15;
                pdf.setDrawColor(30, 30, 30);
                pdf.setLineWidth(0.4);
                var halfW = (usableW / 2) - 8;
                pdf.line(mL, y, mL + halfW, y);
                pdf.line(mL + usableW / 2 + 8, y, pageW - mR, y);
                pdf.setFontSize(8.5);
                pdf.text('Assinatura do Responsável', mL + halfW / 2, y + 5, {
                    align: 'center'
                });
                pdf.text('Data', mL + usableW / 2 + 8 + halfW / 2, y + 5, {
                    align: 'center'
                });

                // ── Rodapé ───────────────────────────────────────────────────
                var totalPages = pdf.internal.getNumberOfPages();
                for (var pg = 1; pg <= totalPages; pg++) {
                    pdf.setPage(pg);
                    pdf.setFontSize(7.5);
                    pdf.setTextColor(160, 160, 160);
                    pdf.text('Movimento VEM • Página ' + pg + ' de ' + totalPages, pageW / 2, pageH - 8, {
                        align: 'center'
                    });
                }

                pdf.save('documentos-legais-vem.pdf');

            } catch (e) {
                console.error(e);
                alert('Erro ao criar o PDF.');
            } finally {
                alpineData.gerando = false;
            }
        }
    </script>
</x-layouts.public>
