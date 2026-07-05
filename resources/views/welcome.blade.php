<x-layouts.public :title="'Movimento Canônico'">
    <div>
    </div>
    <!-- Seção: Sistema de Gestão -->
    <section
        class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-7xl mx-auto my-16 px-4">
        <div class="md:col-span-1 space-y-4">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-400">
                Movimente a sua Paróquia
            </h2>
            <p class="max-w-2xl mx-auto md:mx-0 text-gray-600 dark:text-gray-300">
                Bem-vindo! <br /><br />
                Estamos aqui para facilitar a organização de encontros paroquiais como: VEM, ECC e Segue-Me.
                <br><br>
                "Não tenhais medo!", frase várias vezes repetida pelo Papa João Paulo II e
                Francisco para encorajar os jovens de todo o mundo para que não tenham medo de buscar a Cristo.
            </p>
        </div>
        <div class="md:col-span-2">
            <div class="relative w-full max-w-2xl mx-auto overflow-hidden rounded-2xl" id="carousel">
                <div id="carouselSlides" class="flex transition-transform duration-500">
                    <img src="https://i.imgur.com/yXiQHE9.jpeg"
                        class="w-full h-64 md:h-100 object-cover flex-shrink-0 carousel-img " alt="Imagem 1">
                    <img src="https://i.imgur.com/FdbulA4.jpeg"
                        class="w-full h-64 md:h-100 object-cover flex-shrink-0 carousel-img " alt="Imagem 2">
                    <img src="https://i.imgur.com/piduEFx.jpeg"
                        class="w-full h-64 md:h-100 object-cover flex-shrink-0 carousel-img " alt="Imagem 3">
                </div>

                <!-- Dots navigation -->
                <div class="absolute bottom-4 left-0 right-0 flex justify-center space-x-2">
                    <button onclick="showSlide(0)"
                        class="w-3 h-3 rounded-full transition-all duration-300 cursor-pointer hover:bg-blue-600"
                        id="dot-0"></button>
                    <button onclick="showSlide(1)"
                        class="w-3 h-3 rounded-full transition-all duration-300 cursor-pointer hover:bg-blue-600"
                        id="dot-1"></button>
                    <button onclick="showSlide(2)"
                        class="w-3 h-3 rounded-full transition-all duration-300 cursor-pointer hover:bg-blue-600"
                        id="dot-2"></button>
                </div>

                <button onclick="prevSlide()"
                    class="absolute top-1/2 left-0 transform -translate-y-1/2 bg-opacity-50 text-white p-3 text-2xl z-10 hover: transition cursor-pointer"
                    onmouseover="this.querySelector('svg').style.transform='scale(1.4)'"
                    onmouseout="this.querySelector('svg').style.transform='scale(1)'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button onclick="nextSlide()"
                    class="absolute top-1/2 right-0 transform -translate-y-1/2 bg-opacity-50 text-white p-3 text-2xl z-10 hover:bg-opacity-70 transition cursor-pointer"
                    onmouseover="this.querySelector('svg').style.transform='scale(1.4)'"
                    onmouseout="this.querySelector('svg').style.transform='scale(1)'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <script>
                let index = 0;
                const slides = document.getElementById('carouselSlides');
                const totalSlides = slides.querySelectorAll('.carousel-img').length;
                const dots = Array.from({
                    length: totalSlides
                }, (_, i) => document.getElementById(`dot-${i}`));

                function updateDots() {
                    dots.forEach((dot, i) => {
                        if (i === index) {
                            dot.classList.add('bg-blue-600');
                            dot.classList.remove('bg-gray-300');
                        } else {
                            dot.classList.add('bg-gray-300');
                            dot.classList.remove('bg-blue-600');
                        }
                    });
                }

                function showSlide(i) {
                    index = (i + totalSlides) % totalSlides;
                    slides.style.transform = `translateX(-${index * 100}%)`;
                    updateDots();
                }

                function nextSlide() {
                    showSlide(index + 1);
                }

                function prevSlide() {
                    showSlide(index - 1);
                }

                setInterval(() => {
                    nextSlide();
                }, 7000);

                showSlide(index);
            </script>


    </section>

    <!-- Seção: Movimentos -->
    <section class="text-center space-y-8 mt-16">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">
            Nossos Movimentos
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-7xl mx-auto my-16 px-4">

            <!-- Card VEM -->
            <div class="border border-gray-300 dark:border-gray-700 rounded-xl p-6 flex flex-col justify-between shadow-sm dark:bg-gray-800">
                <div>
                    <img src="https://i.imgur.com/JmjVysp.png"
                        class="w-full h-64 md:h-30 object-cover flex-shrink-0 rounded-2xl" alt="Imagem 1">
                    <h3 class="text-xl font-bold text-blue-600 dark:text-blue-400">VEM</h3>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Encontro de Adolescentes com Cristo</p>
                </div>
            
                <!-- NOVO Badge Premium de Esgotado -->
                <div class="mt-4 relative">
                    <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 dark:from-red-700 dark:to-red-800 text-white font-bold rounded-md shadow-lg border-2 border-red-500 dark:border-red-600 animate-pulse-subtle">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex flex-col leading-tight">
                            <span class="text-sm uppercase tracking-wider">✓ Esgotado</span>
                            <span class="text-xs font-normal opacity-90">Vamos selecionar as fichas</span>
                        </div>
                    </div>
                    <!-- Badge "Esgotado" flutuante opcional -->
                    <span class="absolute -top-2 -right-2 bg-yellow-400 text-yellow-900 text-xs font-black px-2 py-1 rounded-full shadow-md rotate-12">
                        Até o ano que vem!
                    </span>
                </div>
            </div>

            <!-- Card Segue-Me -->
            <div
                class="border border-gray-300 dark:border-gray-700 rounded-xl p-6 flex flex-col justify-between shadow-sm dark:bg-gray-800">
                <div>
                    <img src="https://i.imgur.com/U3FfnPu.png"
                        class="w-full h-64 md:h-30 object-contain flex-shrink-0 rounded-2xl" alt="Imagem 1">
                    <h3 class="text-xl font-bold text-orange-600 dark:text-orange-400">Segue-Me</h3>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Encontro de Jovens com Cristo</p>
                </div>
                <a href="{{ route('home.ficha.sgm') }}"
                    class="mt-4 inline-block bg-orange-600 hover:bg-orange-700 text-white font-semibold px-4 py-2 rounded-md transition">
                    Ficha do Segue-Me
                </a>
            </div>

            <!-- Card ECC -->
            <div
                class="border border-gray-300 dark:border-gray-700 rounded-xl p-6 flex flex-col justify-between shadow-sm dark:bg-gray-800">
                <div>
                    <img src="https://i.imgur.com/aaifcaH.png"
                        class="w-full h-64 md:h-30 object-contain flex-shrink-0 rounded-2xl" alt="Imagem 1">
                    <h3 class="text-xl font-bold text-green-600 dark:text-green-400">ECC</h3>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">Encontro de Casais com Cristo</p>
                </div>
                <a
                    class="mt-4 inline-block bg-gray-600 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md transition">
                    Ficha do ECC (Em breve)
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-7xl mx-auto my-16 px-4">
        <!-- FAQ VEM -->
        <section class="max-w-full bg-blue-50 dark:bg-blue-900 rounded-xl p-6 shadow">
            <h4 class="font-bold text-blue-600 mb-8 dark:text-blue-400">Perguntas Frequentes - VEM</h4>

            <div class="space-y-4">
                <!-- Quem pode participar -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Quem pode participar do VEM?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        Jovens entre 12 e 15 anos que desejam iniciar sua caminhada cristã.
                    </p>
                </details>

                <!-- Quantos encontros -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Quantos encontros tem no ano?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        O VEM realiza 1 encontro por ano.
                    </p>
                </details>

                <!-- Pós encontro -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Como funciona o pós encontro?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        Após o VEM, realizamos encontros temáticos e momentos de espiritualização para continuar o
                        crescimento na fé dos jovens.
                    </p>
                </details>

                <!-- Taxa -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Tem que pagar?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        Há uma taxa para ajudar nos custos com alimentação e materiais. Caso não consiga pagar, informe
                        durante a confirmação — seu filho(a) não deixará de participar por esse motivo.
                    </p>
                </details>

                <!-- Precisa ser católico -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Precisa ser católico?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        O VEM é baseado nos valores e princípios católicos. Jovens de outras religiões são bem-vindos,
                        desde que estejam abertos a participar e ouvir.
                    </p>
                </details>

                <!-- Primeira comunhão -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Precisa ter feito primeira comunhão ou crisma?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        Não é necessário ter feito a primeira comunhão ou crisma.
                    </p>
                </details>

                <!-- Pré-VEM -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Se eu não puder ir no Pré-VEM, ainda posso participar do encontro?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        Infelizmente não. O Pré-VEM é um momento essencial para formação e integração dos grupos. Caso
                        não possa participar, avise a equipe responsável pela sua confirmação.
                    </p>
                </details>

                <!-- Como peço reembolso -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Como peço reembolso?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        - até 10 dias antes do encontro: reembolso de 100% <br />
                        - entre 9 e 5 dias antes do encontro: reembolso de 50% <br />
                        - entre 4 e 1 dia antes do encontro: reembolso de 25% <br />
                        - forma: pix para a mesma conta que pagou a taxa (enviar comprovante)
                    </p>
                </details>

                <!-- Criterios de selecao -->
                <details class="group border border-blue-200 rounded-lg p-4 bg-white dark:bg-blue-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-blue-700 font-medium dark:text-blue-400">
                        Quais os critérios de seleção
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-blue-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">
                        1) Pais trabalhando no encontro <br />
                        2) Preferência pelos mais velhos <br />
                        3) Paroquianos do Lago Norte
                    </p>
                </details>
            </div>
        </section>

        <!-- FAQ Segue-Me -->
        <section class="max-w-4xl mx-auto bg-orange-50 dark:bg-orange-950 rounded-xl p-6 shadow-lg">
            <div class="text-center mb-8">
                <h5 class=" font-extrabold text-orange-600 dark:text-orange-400">Dúvidas Frequentes - Segue-Me 2026
                </h5>
            </div>

            <div class="space-y-4">
                <!-- O que é -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        O que é o SEGUE-ME?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-3 text-gray-700 dark:text-gray-200 leading-relaxed">
                        O SEGUE-ME é um Encontro de Jovens com Cristo, um movimento nacional que proporciona momentos de
                        espiritualidade, convivência e transformação pessoal.
                    </p>
                </details>

                <!-- Quem pode participar -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        Quem pode participar? (Idade e Localização)
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 text-gray-700 dark:text-gray-200 space-y-2">
                        <p><strong>Faixa etária:</strong> Jovens de 16 a 23 anos.</p>
                        <p><strong>Localidade:</strong> Priorizamos jovens do <strong>Lago Norte e Varjão</strong>.
                            Inscritos de outros bairros próximos são avaliados caso a caso.</p>
                        <p><strong>Não católicos:</strong> Podem participar! O encontro é aberto a todos que desejam
                            viver uma experiência com Cristo.</p>
                        <p><strong>Participar sozinho:</strong> Com certeza! Muitos jovens vêm sem conhecer ninguém e
                            fazem grandes amizades durante o fim de semana.</p>
                    </div>
                </details>

                <!-- Inscrição e Pagamento -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        Como faço minha inscrição e qual o valor?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 text-gray-700 dark:text-gray-200 space-y-2">
                        <p><strong>Prazo final:</strong> 30/06/2026.</p>
                        <p><strong>Taxa:</strong> O valor é destinado aos custos de alimentação e materiais. Caso tenha
                            dificuldade financeira, fale conosco — ninguém fica de fora por esse motivo.</p>
                        <p><strong>Confirmação:</strong> Os selecionados serão contatados via telefone pela equipe
                            organizadora.</p>
                    </div>
                </details>

                <!-- Documentos -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        Quais documentos são necessários?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <ul class="mt-3 list-disc ml-5 text-gray-700 dark:text-gray-200 space-y-1">
                        <li>Documento de identidade do participante;</li>
                        <li>Foto 3x4 (pode ser impressa em papel comum/ofício);</li>
                        <li>Ficha de inscrição com autorização dos pais (obrigatório para menores);</li>
                        <li>Comprovante de pagamento (salvo casos excepcionais).</li>
                    </ul>
                </details>

                <!-- Local e Horários -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        Local, Data e Horários
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 text-gray-700 dark:text-gray-200">
                        <p><strong>Local:</strong> Paróquia Nossa Senhora do Lago</p>
                        <p><strong>Data:</strong> 28, 29 e 30 de agosto de 2026</p>
                        <p class="mt-2 font-semibold">Horários de chegada:</p>
                        <ul class="list-none space-y-1">
                            <li>🗓️ Sexta: 19h10 às 22h00</li>
                            <li>🗓️ Sábado: 07h30 às 22h00</li>
                            <li>🗓️ Domingo: 07h30 às 20h00</li>
                        </ul>
                    </div>
                </details>

                <!-- Alimentação e Roupas -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        O que levar e Alimentação
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 text-gray-700 dark:text-gray-200 space-y-2">
                        <p><strong>Alimentação:</strong> Inclusa (Café, almoço, jantar e lanches). Existe campo na ficha
                            para informar restrições alimentares.</p>
                        <p><strong>Roupas:</strong> Confortáveis. Priorizar calças ou saias abaixo dos joelhos. Meninas
                            devem evitar calça legging.</p>
                        <p><strong>Outros:</strong> Remédios pessoais e caderno para anotações (opcional).</p>
                    </div>
                </details>

                <!-- Regras e Segurança -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        Visitas, Segurança e Emergências
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 text-gray-700 dark:text-gray-200 space-y-2">
                        <p><strong>Visitas:</strong> Não é permitida a visita de pais ou amigos durante o encontro,
                            salvo situações excepcionais.</p>
                        <p><strong>Responsáveis:</strong> Sim, haverá adultos responsáveis e casais acompanhando os
                            jovens durante todo o período do encontro.</p>
                        <p><strong>Emergências:</strong> Em caso de necessidade, a família poderá entrar em contato com
                            a organização através dos números fornecidos no ato da confirmação da inscrição.</p>
                    </div>
                </details>

                <!-- Servir e Organização -->
                <details
                    class="group border border-orange-200 rounded-lg p-4 bg-white dark:bg-orange-900 transition-all hover:shadow-md">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-orange-700 font-bold dark:text-orange-300">
                        Organização e como Servir
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-orange-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <div class="mt-3 text-gray-700 dark:text-gray-200 space-y-2">
                        <p><strong>Posso servir em vez de participar?</strong> Para servir no Segue-Me, é necessário já
                            ter feito o encontro (sido encontrista) anteriormente.</p>
                        <p><strong>Quem organiza?</strong> A Equipe Dirigente e o Comando, com apoio dos diretores
                            espirituais Padre André e Padre Lucas.</p>
                    </div>
                </details>
            </div>
        </section>

        <!-- FAQ ECC\ -->
        <section class="max-w-full bg-green-50 dark:bg-green-900 rounded-xl p-6 shadow">
            <h4 class="font-bold text-green-600 mb-8 dark:text-green-200">Perguntas Frequentes - ECC</h4>
            <div class="space-y-4">
                <details class="group border border-green-200 rounded-lg p-4 bg-white dark:bg-green-800">
                    <summary
                        class="flex justify-between items-center cursor-pointer text-green-700 font-medium dark:text-green-600">
                        Quem pode participar do ECC?
                        <svg class="w-5 h-5 transform transition-transform group-open:rotate-180 text-green-600"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </summary>
                    <p class="mt-2 text-gray-700 dark:text-gray-100">Casais de todas as idades</p>
                </details>
            </div>
        </section>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-8 max-w-7xl mx-auto my-8 px-4">
        <!-- Seção 60% -->
        <div class="md:col-span-3">
            <section class="max-w-3xl mx-auto mt-8 px-4">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">Próximos Eventos</h2>
                {{-- Lista de eventos --}}
                <ul class="space-y-4">
                    @forelse ($proximoseventos as $evento)
                        @php
                            $sigla = $evento->movimento?->des_sigla;
                            $badgeClasses = match ($sigla) {
                                'VEM' => 'bg-blue-100 text-blue-700',
                                'Segue-Me' => 'bg-orange-100 text-orange-700',
                                'ECC' => 'bg-green-100 text-green-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <li
                            class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                            <x-heroicon-o-calendar class="w-6 h-6 text-blue-500 mt-1" />
                            <div class="flex-1 flex flex-col w-full">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                                        {{ $evento->des_evento }}
                                    </h3>

                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badgeClasses }}">
                                        {{ $sigla }}
                                    </span>
                                </div>

                                {{-- Informações adicionais --}}
                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($evento->dat_inicio)->translatedFormat('d/m/Y') }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="text-gray-600 dark:text-gray-300">Nenhum evento encontrado.</li>
                    @endforelse
                </ul>
            </section>
        </div>
        <!-- Seção 40% -->
        <div class="md:col-span-2">
            <section class="max-w-3xl mx-auto mt-8 px-4">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">Entre em Contato</h2>

                <form action="{{ route('home.contato') }}" method="POST"
                    class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    @csrf

                    <!-- Nome -->
                    <div>
                        <label for="nom_contato" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nome <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <input type="text" name="nom_contato" id="nom_contato" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>


                    <!-- Email -->
                    <div>
                        <label for="eml_contato"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" name="eml_contato" id="eml_contato"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Telefone -->
                    <div>
                        <label for="tel_contato"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone <span
                                class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <input type="tel" name="tel_contato" id="tel_contato" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Movimento -->
                    <div>
                        <label for="idt_movimento"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Movimento <span
                                class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <select name="idt_movimento" id="idt_movimento" required
                            class="w-full rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Selecione um movimento</option>
                            @foreach ($movimentos as $movimento)
                                <option value="{{ $movimento->idt_movimento }}">{{ $movimento->des_sigla }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mensagem -->
                    <div>
                        <label for="txt_mensagem"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mensagem <span
                                class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <textarea name="txt_mensagem" id="txt_mensagem" rows="4" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <!-- Botão -->
                    <div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition">
                            Enviar Mensagem
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
    </main>

    <!-- Rodapé -->
    <footer class="bg-gray-100 dark:bg-gray-800 text-sm text-gray-600 dark:text-gray-300 py-6">
        <div class="flex flex-col sm:flex-row justify-center items-center gap-6">

            <a href="https://www.instagram.com/nossasenhoradolago" target="_blank"
                class="flex items-center gap-2 hover:underline">
                <!-- Instagram Icon -->
                <svg class="w-6 h-6 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        d="M7.75 2A5.75 5.75 0 0 0 2 7.75v8.5A5.75 5.75 0 0 0 7.75 22h8.5A5.75 5.75 0 0 0 22 16.25v-8.5A5.75 5.75 0 0 0 16.25 2h-8.5ZM4.5 7.75a3.25 3.25 0 0 1 3.25-3.25h8.5a3.25 3.25 0 0 1 3.25 3.25v8.5a3.25 3.25 0 0 1-3.25 3.25h-8.5a3.25 3.25 0 0 1-3.25-3.25v-8.5Zm7.5 1.25a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 1.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM17.5 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" />
                </svg>
                Instagram da Paróquia
            </a>

            <a href="https://www.instagram.com/vempnsl" target="_blank"
                class="flex items-center gap-2 hover:underline">
                <svg class="w-6 h-6 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        d="M7.75 2A5.75 5.75 0 0 0 2 7.75v8.5A5.75 5.75 0 0 0 7.75 22h8.5A5.75 5.75 0 0 0 22 16.25v-8.5A5.75 5.75 0 0 0 16.25 2h-8.5ZM4.5 7.75a3.25 3.25 0 0 1 3.25-3.25h8.5a3.25 3.25 0 0 1 3.25 3.25v8.5a3.25 3.25 0 0 1-3.25 3.25h-8.5a3.25 3.25 0 0 1-3.25-3.25v-8.5Zm7.5 1.25a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 1.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM17.5 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" />
                </svg>
                Fale com o VEM
            </a>

            <a href="https://www.instagram.com/seguemepnsl" target="_blank"
                class="flex items-center gap-2 hover:underline">
                <svg class="w-6 h-6 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        d="M7.75 2A5.75 5.75 0 0 0 2 7.75v8.5A5.75 5.75 0 0 0 7.75 22h8.5A5.75 5.75 0 0 0 22 16.25v-8.5A5.75 5.75 0 0 0 16.25 2h-8.5ZM4.5 7.75a3.25 3.25 0 0 1 3.25-3.25h8.5a3.25 3.25 0 0 1 3.25 3.25v8.5a3.25 3.25 0 0 1-3.25 3.25h-8.5a3.25 3.25 0 0 1-3.25-3.25v-8.5Zm7.5 1.25a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 1.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM17.5 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" />
                </svg>
                Fale com o Segue-Me
            </a>

            <a href="https://www.facebook.com/lourdes.ecc/" target="_blank"
                class="flex items-center gap-2 hover:underline">
                <svg class="w-6 h-6 text-blue-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        d="M22 12a10 10 0 1 0-11.5 9.9v-7h-2v-2.9h2v-2.2c0-2 1.2-3.1 3-3.1.9 0 1.8.1 1.8.1v2h-1c-1 0-1.3.6-1.3 1.2v1.9h2.2l-.4 2.9h-1.8v7A10 10 0 0 0 22 12Z" />
                </svg>
                Fale com o ECC
            </a>
        </div>
    </footer>
</x-layouts.public>
