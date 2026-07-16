<x-layouts.public :title="'Documentos Legais - Segue-me'">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        .doc-article-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1e3a8a;
            margin-top: 1.5rem;
            margin-bottom: 0.4rem;
        }
        .doc-p {
            margin-bottom: 0.6rem;
            line-height: 1.7;
        }
        .doc-subitem {
            padding-left: 1.2rem;
            margin-bottom: 0.5rem;
            line-height: 1.7;
        }
        .doc-subitem strong {
            font-weight: 600;
        }
        .doc-ul {
            list-style: none;
            padding-left: 1.2rem;
            margin-bottom: 0.6rem;
        }
        .doc-ul li {
            position: relative;
            padding-left: 1rem;
            margin-bottom: 0.3rem;
            line-height: 1.7;
        }
        .doc-ul li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #3b82f6;
        }
        .doc-footer-note {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.8rem;
            color: #6b7280;
        }
    </style>

    <section class="px-4 py-6 w-full max-w-4xl mx-auto" x-data="{ tab: 'uso', gerando: false }" aria-labelledby="page-title">

        {{-- Cabeçalho e Ações --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
            <flux:heading size="xl" id="page-title" class="text-indigo-900 dark:text-indigo-100 font-bold tracking-tight mb-1">Documentos Legais</flux:heading>

            <div class="flex items-center gap-3">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md transition-colors border border-gray-300 dark:border-zinc-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar
                </a>

                <button @click="gerarPDF($el)" :disabled="gerando"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-md transition-colors shadow-sm">
                    <svg x-show="!gerando" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <svg x-show="gerando" class="animate-spin h-4 w-4 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span x-text="gerando ? 'Gerando...' : 'Baixar PDF Único'"></span>
                </button>
            </div>
        </div>

        {{-- Navegação por Abas --}}
        <div class="border-b border-gray-200 dark:border-zinc-700 mb-6">
            <nav class="flex space-x-8">
                <button @click="tab = 'uso'"
                    :class="tab === 'uso' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    Termos de Uso
                </button>
                <button @click="tab = 'privacidade'"
                    :class="tab === 'privacidade' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    Privacidade
                </button>
                <button @click="tab = 'consentimento'"
                    :class="tab === 'consentimento' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    Consentimento
                </button>
            </nav>
        </div>

        {{-- Conteúdo das abas --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-zinc-800 text-gray-700 dark:text-gray-300 text-sm">

            {{-- ABA 1: TERMOS DE USO --}}
            <div x-show="tab === 'uso'">
                <h2 class="text-xl font-bold mb-1 text-blue-600">1. Termos e Condições de Uso</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">Parte I — Termos e Condições de Participação</p>

                <p class="doc-article-title">1. Identificação do Controlador de Dados</p>
                <p class="doc-p">A Paróquia Nossa Senhora do Lago, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 00.108.217/0052-60, com sede em Brasília – DF, é a controladora dos dados pessoais coletados por meio desta plataforma, nos termos da Lei nº 13.709/2018 (Lei Geral de Proteção de Dados Pessoais – LGPD).</p>
                <p class="doc-p">O Movimento Segue-Me é um programa de encontro de jovens vinculado à Paróquia Nossa Senhora do Lago, voltado a participantes com idade entre 16 e 23 anos.</p>

                <p class="doc-article-title">2. Veracidade das Informações</p>
                <p class="doc-p">O participante — ou seu responsável legal, quando aplicável — garante que todas as informações prestadas no formulário de cadastro (dados de identificação, contato, saúde e documentos) são verdadeiras, completas e atualizadas.</p>
                <p class="doc-p">A prestação de informações falsas ou omissão de dados relevantes poderá:</p>
                <ul class="doc-ul">
                    <li>Comprometer a segurança do participante e dos demais envolvidos no encontro;</li>
                    <li>Implicar responsabilidade civil e/ou penal do declarante, nos termos da legislação vigente;</li>
                    <li>Acarretar o cancelamento da inscrição, sem direito a reembolso de eventuais taxas pagas.</li>
                </ul>

                <p class="doc-article-title">3. Elegibilidade e Perfis de Participação</p>
                <p class="doc-p">O Movimento Segue-Me admite dois perfis de participação:</p>
                <p class="doc-subitem"><strong>3.1 Participante (Jovem):</strong> Pessoa com idade entre 16 e 23 anos que participará do encontro pela primeira vez ou retornará como participante.</p>
                <p class="doc-subitem"><strong>3.2 Trabalhador (Jovem que já fez o encontro):</strong> Jovem que já vivenciou o Segue-Me como participante e retorna para integrar a equipe de trabalho do encontro. Para este perfil, aplicam-se adicionalmente as responsabilidades inerentes ao papel de colaborador voluntário, incluindo sigilo sobre dinâmicas e comprometimento com a coordenação.</p>
                <p class="doc-p">Em ambos os perfis, o participante ou responsável legal deve indicar claramente a modalidade no momento do cadastro.</p>

                <p class="doc-article-title">4. Regras de Conduta e Convivência</p>
                <p class="doc-p">A inscrição no Segue-Me implica o conhecimento e a aceitação das seguintes normas:</p>
                <p class="doc-subitem"><strong>4.1</strong> Respeito irrestrito à dignidade de todos os presentes — jovens, tios, equipes de apoio e coordenação — vedando-se qualquer forma de discriminação, assédio, bullying ou violência, seja física, verbal, psicológica ou virtual, conforme dispõe a Lei nº 13.185/2015 (Programa de Combate à Intimidação Sistemática).</p>
                <p class="doc-subitem"><strong>4.2</strong> Cumprimento integral dos horários, rotinas e atividades propostas pela coordenação durante o encontro.</p>
                <p class="doc-subitem"><strong>4.3</strong> Cuidado e zelo com o patrimônio da Paróquia Nossa Senhora do Lago e/ou do local onde o encontro for realizado. Danos causados por negligência ou dolo serão de responsabilidade do participante ou de seu responsável legal.</p>
                <p class="doc-subitem"><strong>4.4</strong> Proibição do consumo de bebidas alcoólicas, substâncias psicoativas e tabaco nas dependências do encontro, em qualquer modalidade, para todos os participantes, independentemente da idade.</p>
                <p class="doc-subitem"><strong>4.5</strong> Vedação a qualquer comportamento que comprometa a integridade física, moral ou espiritual dos demais participantes ou que seja contrário aos valores cristãos que fundamentam o movimento.</p>

                <p class="doc-article-title">5. Uso de Dispositivos Eletrônicos</p>
                <p class="doc-subitem"><strong>5.1</strong> O participante está ciente de que o uso de celulares e demais dispositivos eletrônicos poderá ser restrito ou suspenso durante as atividades do encontro, a critério da coordenação, com o objetivo de favorecer a integração, o foco e a profundidade das dinâmicas propostas.</p>
                <p class="doc-subitem"><strong>5.2</strong> Em caso de necessidade urgente de comunicação, a equipe de coordenação disponibilizará canal específico para contato com familiares.</p>
                <p class="doc-subitem"><strong>5.3</strong> É vedada a gravação, fotografia ou transmissão de qualquer atividade interna do encontro sem autorização prévia e expressa da coordenação, sob pena de cancelamento da participação.</p>

                <p class="doc-article-title">6. Confirmação de Vaga e Critérios de Inscrição</p>
                <p class="doc-subitem"><strong>6.1</strong> O preenchimento do formulário de cadastro não garante automaticamente a reserva de vaga. A confirmação oficial será comunicada pela coordenação pelos canais de contato informados, após verificação do cumprimento dos critérios estabelecidos (faixa etária, vínculo paroquial, pagamento de taxa se houver, entre outros).</p>
                <p class="doc-subitem"><strong>6.2</strong> A Paróquia Nossa Senhora do Lago reserva-se o direito de recusar inscrições que não atendam aos critérios do movimento ou que apresentem inconsistências nas informações fornecidas.</p>

                <p class="doc-article-title">7. Desistência e Cancelamento</p>
                <p class="doc-subitem"><strong>7.1</strong> Em caso de desistência, o participante ou seu responsável compromete-se a comunicar a coordenação com antecedência mínima de 15 dias, para que a vaga possa ser redistribuída a candidatos em lista de espera.</p>
                <p class="doc-subitem"><strong>7.2</strong> Eventuais critérios para reembolso de taxas de inscrição, se aplicável, serão definidos e comunicados no regulamento específico de cada edição do encontro.</p>

                <p class="doc-article-title">8. Alterações nos Termos</p>
                <p class="doc-p">{{ __('messages.terms.formSGM.updates') }}</p>
            </div>

            {{-- ABA 2: POLÍTICA DE PRIVACIDADE --}}
            <div x-show="tab === 'privacidade'">
                <h2 class="text-xl font-bold mb-1 text-blue-600">2. Política de Privacidade e Proteção de Dados</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">Em conformidade com a Lei nº 13.709/2018 (LGPD)</p>

                <p class="doc-article-title">1. Base Legal para o Tratamento de Dados</p>
                <p class="doc-p">O tratamento dos dados pessoais coletados por esta plataforma fundamenta-se nas seguintes bases legais previstas na LGPD (Lei nº 13.709/2018):</p>
                <ul class="doc-ul">
                    <li><strong>Art. 7º, inciso I</strong> – Consentimento livre, informado e inequívoco do titular ou de seu responsável legal;</li>
                    <li><strong>Art. 7º, inciso II</strong> – Cumprimento de obrigação legal (ex.: registros exigidos por normas de segurança de eventos);</li>
                    <li><strong>Art. 7º, inciso VI</strong> – Exercício regular de direitos em processo administrativo (organização interna do movimento);</li>
                    <li><strong>Art. 11, inciso II, alínea "a"</strong> – Para dados sensíveis de saúde: consentimento específico e destacado do titular ou responsável.</li>
                </ul>

                <p class="doc-article-title">2. Dados Coletados</p>
                <p class="doc-p">Para a organização do encontro, coletamos exclusivamente as informações necessárias, organizadas nas seguintes categorias:</p>
                <p class="doc-subitem"><strong>2.1 Dados de Identificação:</strong> Nome completo, data de nascimento, número do documento de identidade (RG ou equivalente) e fotografia.</p>
                <p class="doc-subitem"><strong>2.2 Dados de Contato:</strong> Telefone celular e e-mail do participante; nome, telefone e e-mail do pai, mãe ou responsável legal (obrigatório para participantes menores de 18 anos).</p>
                <p class="doc-subitem"><strong>2.3 Dados Sensíveis de Saúde</strong> (tratados com proteção reforçada — Art. 11 da LGPD): Alergias alimentares e medicamentosas, restrições alimentares, tipo sanguíneo, condições médicas relevantes, nome e contato de médico de referência, plano de saúde e uso contínuo de medicamentos.</p>
                <p class="doc-subitem"><strong>2.4 Dados de Vínculo Religioso:</strong> Paróquia de origem e, se aplicável, sacramento de Crisma, para fins de elegibilidade.</p>

                <p class="doc-article-title">3. Finalidades do Tratamento</p>
                <p class="doc-p">Os dados coletados serão utilizados exclusivamente para as seguintes finalidades:</p>
                <ul class="doc-ul">
                    <li>Organização logística do encontro (formação de círculos/grupos, alocação de quartos, montagem de equipes);</li>
                    <li>Garantia da segurança alimentar e médica dos participantes durante o fim de semana;</li>
                    <li>Comunicação de informações relevantes sobre o encontro, pré e pós-evento;</li>
                    <li>Avisos sobre próximas edições e eventos do movimento e da Paróquia Nossa Senhora do Lago, para participantes que manifestarem interesse;</li>
                    <li>Cumprimento de obrigações legais decorrentes da realização do evento.</li>
                </ul>
                <p class="doc-p">Os dados não serão utilizados para fins comerciais, marketing de terceiros ou qualquer finalidade incompatível com as aqui descritas.</p>

                <p class="doc-article-title">4. Compartilhamento de Dados</p>
                <p class="doc-subitem"><strong>4.1</strong> O acesso aos dados é restrito aos membros da Equipe Dirigente/Coordenação Geral e à Secretaria do encontro, que necessitam das informações para o desempenho de suas funções.</p>
                <p class="doc-subitem"><strong>4.2</strong> Os dados sensíveis de saúde (item 2.3) serão compartilhados apenas com as equipes de Cozinha e de Saúde/Primeiros Socorros do encontro, na medida estritamente necessária.</p>
                <p class="doc-subitem"><strong>4.3</strong> A Paróquia Nossa Senhora do Lago não vende, não aluga e não cede dados pessoais a terceiros externos à estrutura paroquial.</p>
                <p class="doc-subitem"><strong>4.4</strong> Em situações de emergência médica, os dados de saúde poderão ser compartilhados com profissionais de saúde ou serviços de emergência, com base no legítimo interesse e na proteção da vida do participante (Art. 7º, inciso X, e Art. 11, inciso II, alínea "e", da LGPD).</p>

                <p class="doc-article-title">5. Uso de Imagem e Voz</p>
                <p class="doc-subitem"><strong>5.1</strong> Fotos e vídeos produzidos durante o encontro poderão ser utilizados para o acervo histórico do movimento e para divulgação nos canais de comunicação oficiais da Paróquia Nossa Senhora do Lago (Instagram, Facebook, site institucional e materiais impressos).</p>
                <p class="doc-subitem"><strong>5.2</strong> A utilização da imagem e voz é autorizada exclusivamente para as finalidades acima descritas, sendo expressamente vedada qualquer utilização que atente contra a honra, a intimidade ou a moral do participante.</p>
                <p class="doc-subitem"><strong>5.3</strong> Participantes que possuam restrição ao uso de imagem deverão informar a coordenação antes do início do encontro, mediante comunicação formal. A restrição será registrada e respeitada pela equipe.</p>

                <p class="doc-article-title">6. Armazenamento, Segurança e Retenção</p>
                <p class="doc-subitem"><strong>6.1</strong> Os dados serão armazenados em ambiente seguro, com acesso controlado por credenciais, adotando-se medidas técnicas e administrativas adequadas à proteção contra acesso não autorizado, destruição, perda ou alteração indevida, conforme Art. 46 da LGPD.</p>
                <p class="doc-subitem"><strong>6.2</strong> Os dados serão retidos pelo período necessário ao cumprimento das finalidades descritas neste documento ou pelo prazo mínimo exigido por obrigação legal.</p>
                <p class="doc-subitem"><strong>6.3</strong> {{ __('messages.terms.formSGM.data_retention') }}</p>
            </div>

            {{-- ABA 3: TERMO DE CONSENTIMENTO --}}
            <div x-show="tab === 'consentimento'">
                <h2 class="text-xl font-bold mb-1 text-blue-600">3. Termo de Consentimento e Autorização</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">Paróquia Nossa Senhora do Lago — CNPJ 00.108.217/0052-60 — Brasília – DF</p>

                <p class="doc-article-title">1. Declaração de Identidade e Responsabilidade</p>
                <p class="doc-p">Declaro, sob as penas da lei, que as informações prestadas nos campos de identificação deste cadastro são verdadeiras e de minha inteira responsabilidade, servindo como base para o presente termo de consentimento.</p>

                <p class="doc-article-title">2. Consentimento para Tratamento de Dados Pessoais (LGPD)</p>
                <p class="doc-p">Autorizo a Paróquia Nossa Senhora do Lago e o Movimento Segue-Me a coletar, armazenar e tratar os dados pessoais e sensíveis constantes nesta ficha de inscrição, para as finalidades descritas na Parte II deste documento, em especial:</p>
                <ul class="doc-ul">
                    <li>Organização logística, formação de equipes e gestão do encontro;</li>
                    <li>Comunicação sobre o evento e atividades futuras do movimento;</li>
                    <li>Garantia da segurança alimentar, médica e física durante o encontro.</li>
                </ul>
                <p class="doc-p">Consentimento específico para dados sensíveis de saúde: Declaro ter sido informado(a) de forma clara sobre o tratamento dos meus dados de saúde, nos termos do Art. 11, inciso II, alínea "a", da LGPD, e autorizo expressamente seu uso pelas equipes responsáveis.</p>

                <p class="doc-article-title">3. Autorização de Uso de Imagem e Voz</p>
                <p class="doc-p">Autorizo, de forma gratuita, o uso da minha imagem e voz captadas durante o Encontro do Segue-Me para fins de divulgação nos canais oficiais da Paróquia Nossa Senhora do Lago e materiais do movimento.</p>
                <p class="doc-p">Declaro estar ciente de que a imagem não será utilizada de forma que atente contra minha honra, dignidade ou moral, e que possuo o direito de revogar esta autorização mediante comunicação prévia à coordenação, sem retroatividade quanto ao material já publicado.</p>

                <p class="doc-article-title">4. Declaração de Saúde e Autorização para Atendimento Médico</p>
                <p class="doc-p">Declaro que as informações sobre condições de saúde, alergias e restrições médicas constantes nesta ficha são verdadeiras e completas.</p>
                <p class="doc-p">Em caso de emergência médica durante o encontro, autorizo a equipe responsável a:</p>
                <ul class="doc-ul">
                    <li>Prestar os primeiros socorros necessários;</li>
                    <li>Acionar o serviço de emergência (SAMU, Corpo de Bombeiros ou equivalente);</li>
                    <li>Encaminhar para atendimento médico hospitalar, se necessário;</li>
                    <li>Compartilhar as informações de saúde aqui constantes com os profissionais de saúde que prestarem atendimento.</li>
                </ul>

                <p class="doc-article-title">5. Declaração Final e Validade da Assinatura Eletrônica</p>
                <p class="doc-p">Ao confirmar minha inscrição nesta plataforma, declaro que:</p>
                <ul class="doc-ul">
                    <li>Li e compreendi integralmente os Termos e Condições, a Política de Privacidade e o presente Termo de Consentimento;</li>
                    <li>Concordo livre e voluntariamente com todas as disposições aqui contidas;</li>
                    <li>Estou ciente das normas de conduta do encontro e comprometo-me a respeitá-las;</li>
                    <li>No caso de participante menor de 18 anos: o responsável legal declara ter lido, compreendido e autorizado a participação e o tratamento dos dados do menor sob sua responsabilidade, nos termos do Art. 14 da LGPD e do Estatuto da Criança e do Adolescente (Lei nº 8.069/1990).</li>
                </ul>
                <p class="doc-p">A confirmação eletrônica desta inscrição possui validade jurídica equivalente à assinatura manuscrita, nos termos da Lei nº 14.063/2020 (Assinaturas Eletrônicas) e do Art. 107 do Código Civil Brasileiro.</p>

                <div class="doc-footer-note">
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
                if (!window.jspdf) {
                    alert('Biblioteca jsPDF não carregou. Recarregue a página.');
                    alpineData.gerando = false;
                    return;
                }

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');

                const mL = 20, mR = 20;
                const pageW   = pdf.internal.pageSize.getWidth();
                const pageH   = pdf.internal.pageSize.getHeight();
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

                function p(text) { addText(text, 10, [50, 50, 50], false, 3); }

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
                        pdf.text((i === 0 ? '\u2022  ' : '    ') + lines[i], mL + 3, y);
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
                pdf.text('MOVIMENTO SEGUE-ME', pageW / 2, 7, { align: 'center' });
                pdf.setFontSize(9);
                pdf.setFont('helvetica', 'normal');
                pdf.text('Documentos Legais e Consentimento', pageW / 2, 13, { align: 'center' });
                y = 26;
                hr([29, 78, 216]);

                // ════════════════════════════════════════════════════════════
                // PARTE I — TERMOS DE USO
                // ════════════════════════════════════════════════════════════
                sectionTitle(1, 'Termos e Condições de Uso');

                articleTitle('1. IDENTIFICAÇÃO DO CONTROLADOR DE DADOS');
                p('A Paróquia Nossa Senhora do Lago, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 00.108.217/0052-60, com sede em Brasília – DF, é a controladora dos dados pessoais coletados por meio desta plataforma, nos termos da Lei nº 13.709/2018 (Lei Geral de Proteção de Dados Pessoais – LGPD).');
                p('O Movimento Segue-Me é um programa de encontro de jovens vinculado à Paróquia Nossa Senhora do Lago, voltado a participantes com idade entre 16 e 23 anos.');

                articleTitle('2. VERACIDADE DAS INFORMAÇÕES');
                p('O participante — ou seu responsável legal, quando aplicável — garante que todas as informações prestadas no formulário de cadastro (dados de identificação, contato, saúde e documentos) são verdadeiras, completas e atualizadas.');
                p('A prestação de informações falsas ou omissão de dados relevantes poderá:');
                bullet('Comprometer a segurança do participante e dos demais envolvidos no encontro;');
                bullet('Implicar responsabilidade civil e/ou penal do declarante, nos termos da legislação vigente;');
                bullet('Acarretar o cancelamento da inscrição, sem direito a reembolso de eventuais taxas pagas.');

                articleTitle('3. ELEGIBILIDADE E PERFIS DE PARTICIPAÇÃO');
                p('O Movimento Segue-Me admite dois perfis de participação:');
                subItem('3.1  Participante (Jovem): Pessoa com idade entre 16 e 23 anos que participará do encontro pela primeira vez ou retornará como participante.');
                subItem('3.2  Trabalhador (Jovem que já fez o encontro): Jovem que já vivenciou o Segue-Me como participante e retorna para integrar a equipe de trabalho do encontro. Para este perfil, aplicam-se adicionalmente as responsabilidades inerentes ao papel de colaborador voluntário, incluindo sigilo sobre dinâmicas e comprometimento com a coordenação.');
                p('Em ambos os perfis, o participante ou responsável legal deve indicar claramente a modalidade no momento do cadastro.');

                articleTitle('4. REGRAS DE CONDUTA E CONVIVÊNCIA');
                p('A inscrição no Segue-Me implica o conhecimento e a aceitação das seguintes normas:');
                subItem('4.1  Respeito irrestrito à dignidade de todos os presentes — jovens, tios, equipes de apoio e coordenação — vedando-se qualquer forma de discriminação, assédio, bullying ou violência, seja física, verbal, psicológica ou virtual, conforme dispõe a Lei nº 13.185/2015.');
                subItem('4.2  Cumprimento integral dos horários, rotinas e atividades propostas pela coordenação durante o encontro.');
                subItem('4.3  Cuidado e zelo com o patrimônio da Paróquia Nossa Senhora do Lago e/ou do local onde o encontro for realizado. Danos causados por negligência ou dolo serão de responsabilidade do participante ou de seu responsável legal.');
                subItem('4.4  Proibição do consumo de bebidas alcoólicas, substâncias psicoativas e tabaco nas dependências do encontro, em qualquer modalidade, para todos os participantes, independentemente da idade.');
                subItem('4.5  Vedação a qualquer comportamento que comprometa a integridade física, moral ou espiritual dos demais participantes ou que seja contrário aos valores cristãos que fundamentam o movimento.');

                articleTitle('5. USO DE DISPOSITIVOS ELETRÔNICOS');
                subItem('5.1  O participante está ciente de que o uso de celulares e demais dispositivos eletrônicos poderá ser restrito ou suspenso durante as atividades do encontro, a critério da coordenação, com o objetivo de favorecer a integração, o foco e a profundidade das dinâmicas propostas.');
                subItem('5.2  Em caso de necessidade urgente de comunicação, a equipe de coordenação disponibilizará canal específico para contato com familiares.');
                subItem('5.3  É vedada a gravação, fotografia ou transmissão de qualquer atividade interna do encontro sem autorização prévia e expressa da coordenação, sob pena de cancelamento da participação.');

                articleTitle('6. CONFIRMAÇÃO DE VAGA E CRITÉRIOS DE INSCRIÇÃO');
                subItem('6.1  O preenchimento do formulário de cadastro não garante automaticamente a reserva de vaga. A confirmação oficial será comunicada pela coordenação pelos canais de contato informados, após verificação do cumprimento dos critérios estabelecidos.');
                subItem('6.2  A Paróquia Nossa Senhora do Lago reserva-se o direito de recusar inscrições que não atendam aos critérios do movimento ou que apresentem inconsistências nas informações fornecidas.');

                articleTitle('7. DESISTÊNCIA E CANCELAMENTO');
                subItem('7.1  Em caso de desistência, o participante ou seu responsável compromete-se a comunicar a coordenação com antecedência mínima de 15 dias, para que a vaga possa ser redistribuída a candidatos em lista de espera.');
                subItem('7.2  Eventuais critérios para reembolso de taxas de inscrição, se aplicável, serão definidos e comunicados no regulamento específico de cada edição do encontro.');

                articleTitle('8. ALTERAÇÕES NOS TERMOS');
                p('{{ __('messages.terms.formSGM.updates') }}');

                y += 4;
                hr();

                // ════════════════════════════════════════════════════════════
                // PARTE II — POLÍTICA DE PRIVACIDADE
                // ════════════════════════════════════════════════════════════
                sectionTitle(2, 'Política de Privacidade e Proteção de Dados (LGPD)');

                articleTitle('1. BASE LEGAL PARA O TRATAMENTO DE DADOS');
                p('O tratamento dos dados pessoais coletados por esta plataforma fundamenta-se nas seguintes bases legais previstas na LGPD (Lei nº 13.709/2018):');
                bullet('Art. 7º, inciso I – Consentimento livre, informado e inequívoco do titular ou de seu responsável legal;');
                bullet('Art. 7º, inciso II – Cumprimento de obrigação legal (ex.: registros exigidos por normas de segurança de eventos);');
                bullet('Art. 7º, inciso VI – Exercício regular de direitos em processo administrativo (organização interna do movimento);');
                bullet('Art. 11, inciso II, alínea "a" – Para dados sensíveis de saúde: consentimento específico e destacado do titular ou responsável.');

                articleTitle('2. DADOS COLETADOS');
                p('Para a organização do encontro, coletamos exclusivamente as informações necessárias, organizadas nas seguintes categorias:');
                subItem('2.1  Dados de Identificação: Nome completo, data de nascimento, número do documento de identidade (RG ou equivalente) e fotografia.');
                subItem('2.2  Dados de Contato: Telefone celular e e-mail do participante; nome, telefone e e-mail do pai, mãe ou responsável legal (obrigatório para participantes menores de 18 anos).');
                subItem('2.3  Dados Sensíveis de Saúde (Art. 11 da LGPD): Alergias alimentares e medicamentosas, restrições alimentares, tipo sanguíneo, condições médicas relevantes, nome e contato de médico de referência, plano de saúde e uso contínuo de medicamentos.');
                subItem('2.4  Dados de Vínculo Religioso: Paróquia de origem e, se aplicável, sacramento de Crisma, para fins de elegibilidade.');

                articleTitle('3. FINALIDADES DO TRATAMENTO');
                p('Os dados coletados serão utilizados exclusivamente para as seguintes finalidades:');
                bullet('Organização logística do encontro (formação de círculos/grupos, alocação de quartos, montagem de equipes);');
                bullet('Garantia da segurança alimentar e médica dos participantes durante o fim de semana;');
                bullet('Comunicação de informações relevantes sobre o encontro, pré e pós-evento;');
                bullet('Avisos sobre próximas edições e eventos do movimento e da Paróquia Nossa Senhora do Lago, para participantes que manifestarem interesse;');
                bullet('Cumprimento de obrigações legais decorrentes da realização do evento.');
                p('Os dados não serão utilizados para fins comerciais, marketing de terceiros ou qualquer finalidade incompatível com as aqui descritas.');

                articleTitle('4. COMPARTILHAMENTO DE DADOS');
                subItem('4.1  O acesso aos dados é restrito aos membros da Equipe Dirigente/Coordenação Geral e à Secretaria do encontro, que necessitam das informações para o desempenho de suas funções.');
                subItem('4.2  Os dados sensíveis de saúde (item 2.3) serão compartilhados apenas com as equipes de Cozinha e de Saúde/Primeiros Socorros do encontro, na medida estritamente necessária.');
                subItem('4.3  A Paróquia Nossa Senhora do Lago não vende, não aluga e não cede dados pessoais a terceiros externos à estrutura paroquial.');
                subItem('4.4  Em situações de emergência médica, os dados de saúde poderão ser compartilhados com profissionais de saúde ou serviços de emergência, com base no legítimo interesse e na proteção da vida do participante (Art. 7º, inciso X, e Art. 11, inciso II, alínea "e", da LGPD).');

                articleTitle('5. USO DE IMAGEM E VOZ');
                subItem('5.1  Fotos e vídeos produzidos durante o encontro poderão ser utilizados para o acervo histórico do movimento e para divulgação nos canais de comunicação oficiais da Paróquia Nossa Senhora do Lago (Instagram, Facebook, site institucional e materiais impressos).');
                subItem('5.2  A utilização da imagem e voz é autorizada exclusivamente para as finalidades acima descritas, sendo expressamente vedada qualquer utilização que atente contra a honra, a intimidade ou a moral do participante.');
                subItem('5.3  Participantes que possuam restrição ao uso de imagem deverão informar a coordenação antes do início do encontro, mediante comunicação formal. A restrição será registrada e respeitada pela equipe.');

                articleTitle('6. ARMAZENAMENTO, SEGURANÇA E RETENÇÃO');
                subItem('6.1  Os dados serão armazenados em ambiente seguro, com acesso controlado por credenciais, adotando-se medidas técnicas e administrativas adequadas à proteção contra acesso não autorizado, destruição, perda ou alteração indevida, conforme Art. 46 da LGPD.');
                subItem('6.2  Os dados serão retidos pelo período necessário ao cumprimento das finalidades descritas neste documento ou pelo prazo mínimo exigido por obrigação legal.');
                subItem('6.3  {{ __('messages.terms.formSGM.data_retention') }}');

                y += 4;
                hr();

                // ════════════════════════════════════════════════════════════
                // PARTE III — TERMO DE CONSENTIMENTO
                // ════════════════════════════════════════════════════════════
                sectionTitle(3, 'Termo de Consentimento e Autorização');

                articleTitle('1. DECLARAÇÃO DE IDENTIDADE E RESPONSABILIDADE');
                p('Declaro, sob as penas da lei, que as informações prestadas nos campos de identificação deste cadastro são verdadeiras e de minha inteira responsabilidade, servindo como base para o presente termo de consentimento.');

                articleTitle('2. CONSENTIMENTO PARA TRATAMENTO DE DADOS PESSOAIS (LGPD)');
                p('Autorizo a Paróquia Nossa Senhora do Lago e o Movimento Segue-Me a coletar, armazenar e tratar os dados pessoais e sensíveis constantes nesta ficha de inscrição, para as finalidades descritas na Parte II deste documento, em especial:');
                bullet('Organização logística, formação de equipes e gestão do encontro;');
                bullet('Comunicação sobre o evento e atividades futuras do movimento;');
                bullet('Garantia da segurança alimentar, médica e física durante o encontro.');
                p('Consentimento específico para dados sensíveis de saúde: Declaro ter sido informado(a) de forma clara sobre o tratamento dos meus dados de saúde, nos termos do Art. 11, inciso II, alínea "a", da LGPD, e autorizo expressamente seu uso pelas equipes responsáveis.');

                articleTitle('3. AUTORIZAÇÃO DE USO DE IMAGEM E VOZ');
                p('Autorizo, de forma gratuita, o uso da minha imagem e voz captadas durante o Encontro do Segue-Me para fins de divulgação nos canais oficiais da Paróquia Nossa Senhora do Lago e materiais do movimento.');
                p('Declaro estar ciente de que a imagem não será utilizada de forma que atente contra minha honra, dignidade ou moral, e que possuo o direito de revogar esta autorização mediante comunicação prévia à coordenação, sem retroatividade quanto ao material já publicado.');

                articleTitle('4. DECLARAÇÃO DE SAÚDE E AUTORIZAÇÃO PARA ATENDIMENTO MÉDICO');
                p('Declaro que as informações sobre condições de saúde, alergias e restrições médicas constantes nesta ficha são verdadeiras e completas.');
                p('Em caso de emergência médica durante o encontro, autorizo a equipe responsável a:');
                bullet('Prestar os primeiros socorros necessários;');
                bullet('Acionar o serviço de emergência (SAMU, Corpo de Bombeiros ou equivalente);');
                bullet('Encaminhar para atendimento médico hospitalar, se necessário;');
                bullet('Compartilhar as informações de saúde aqui constantes com os profissionais de saúde que prestarem atendimento.');

                articleTitle('5. DECLARAÇÃO FINAL E VALIDADE DA ASSINATURA ELETRÔNICA');
                p('Ao confirmar minha inscrição nesta plataforma, declaro que:');
                bullet('Li e compreendi integralmente os Termos e Condições, a Política de Privacidade e o presente Termo de Consentimento;');
                bullet('Concordo livre e voluntariamente com todas as disposições aqui contidas;');
                bullet('Estou ciente das normas de conduta do encontro e comprometo-me a respeitá-las;');
                bullet('No caso de participante menor de 18 anos: o responsável legal declara ter lido, compreendido e autorizado a participação e o tratamento dos dados do menor sob sua responsabilidade, nos termos do Art. 14 da LGPD e do Estatuto da Criança e do Adolescente (Lei nº 8.069/1990).');
                p('A confirmação eletrônica desta inscrição possui validade jurídica equivalente à assinatura manuscrita, nos termos da Lei nº 14.063/2020 (Assinaturas Eletrônicas) e do Art. 107 do Código Civil Brasileiro.');

                y += 4;
                addText('Paróquia Nossa Senhora do Lago — CNPJ 00.108.217/0052-60 — Brasília – DF', 9, [100, 100, 100], false, 1);
                addText('Data e hora de aceite registradas automaticamente pelo sistema.', 9, [100, 100, 100], false, 0);

                // ── Assinatura ───────────────────────────────────────────────
                checkY(30);
                y += 14;
                pdf.setDrawColor(30, 30, 30);
                pdf.setLineWidth(0.4);

                var halfW = (usableW / 2) - 8;
                var col1x = mL;
                var col2x = mL + usableW / 2 + 8;

                pdf.line(col1x, y, col1x + halfW, y);
                pdf.line(col2x, y, col2x + halfW, y);

                pdf.setFontSize(8.5);
                pdf.setFont('helvetica', 'normal');
                pdf.setTextColor(90, 90, 90);
                pdf.text('Assinatura', col1x + halfW / 2, y + 5, { align: 'center' });
                pdf.text('Data', col2x + halfW / 2, y + 5, { align: 'center' });

                // ── Rodapé paginado ──────────────────────────────────────────
                var totalPages = pdf.internal.getNumberOfPages();
                for (var pg = 1; pg <= totalPages; pg++) {
                    pdf.setPage(pg);
                    pdf.setFontSize(7.5);
                    pdf.setTextColor(160, 160, 160);
                    pdf.text(
                        'Movimento Segue-me  \u2022  P\u00e1gina ' + pg + ' de ' + totalPages,
                        pageW / 2, pageH - 8, { align: 'center' }
                    );
                }

                pdf.save('documentos-segueme.pdf');

            } catch (e) {
                console.error('[PDF] erro =>', e);
                alert('Falha ao criar o PDF: ' + e.message);
            } finally {
                alpineData.gerando = false;
            }
        }
    </script>
</x-layouts.public>
