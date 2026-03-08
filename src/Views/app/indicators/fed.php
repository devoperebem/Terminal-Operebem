<?php
$title = 'CME - Fed Watch Tool';
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Faz a chamada para a API Railway e captura o link retornado
$api_url = "https://apifedwatchtool-production.up.railway.app/";
$iframe_src = "";

// Usa cURL para fazer a requisição GET
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);
$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Verifica se a requisição foi bem-sucedida
if ($http_code === 200 && !empty($response)) {
    $iframe_src = trim($response);
} else {
    $iframe_src = "about:blank"; // Fallback
}

ob_start();
?>

<style>
/* Layout responsivo para o iframe */
.fedwatch-container {
    max-width: 1433px;
    margin: 0 auto;
    padding: 1rem;
}

.aspect-16-9 {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 */
}

.aspect-16-9 iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
    border-radius: 12px;
}

/* Card adaptado aos temas */
.fedwatch-card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

html.light .fedwatch-card {
    background-color: #ffffff;
    border: 1px solid rgba(0,0,0,0.125);
}

html.dark-blue .fedwatch-card {
    background-color: #001233;
    border: 1px solid #ffffff;
}

html.all-black .fedwatch-card {
    background-color: #0a0a0a;
    border: 1px solid #ffffff;
}

/* Rodapé do card */
.fedwatch-footer {
    padding: 0.75rem 1rem;
    font-size: 0.75rem;
}

html.light .fedwatch-footer {
    background-color: #f4f4fa;
    color: #333;
}

html.dark-blue .fedwatch-footer {
    background-color: #050D26;
    color: #e0e0e0;
}

html.all-black .fedwatch-footer {
    background-color: #00010a;
    color: #e0e0e0;
}

/* Inverter cores do iframe em temas escuros */
html.dark-blue .cme-iframe,
html.all-black .cme-iframe {
    filter: invert(1) hue-rotate(180deg);
}

/* Título */
.fedwatch-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.fedwatch-header img {
    height: 24px;
}

.fedwatch-header h1 {
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0;
}

html.dark-blue .fedwatch-header h1,
html.all-black .fedwatch-header h1 {
    color: #ffffff;
}

/* Modal disclaimer */
.modal-disclaimer .modal-content {
    border-radius: 12px;
}

html.dark-blue .modal-disclaimer .modal-content {
    background-color: #001233;
    color: #ffffff;
    border: 1px solid rgba(255,255,255,0.2);
}

html.all-black .modal-disclaimer .modal-content {
    background-color: #0a0a0a;
    color: #ffffff;
    border: 1px solid #222222;
}

html.dark-blue .modal-disclaimer .modal-header { border-bottom-color: rgba(255,255,255,0.1); }
html.dark-blue .modal-disclaimer .modal-footer { border-top-color: rgba(255,255,255,0.1); }
html.all-black .modal-disclaimer .modal-header { border-bottom-color: #222222; }
html.all-black .modal-disclaimer .modal-footer { border-top-color: #222222; }

@media (max-width: 768px) {
    .fedwatch-header h1 {
        font-size: 1.25rem;
    }
    .fedwatch-header img {
        height: 20px;
    }
}
</style>

<!-- Modal Disclaimer -->
<div class="modal fade modal-disclaimer" id="disclaimerModal" tabindex="-1" aria-labelledby="disclaimerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disclaimerModalLabel">Disclaimer CME</h5>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    Os dados e os resultados oriundos desta ferramenta não constituem orientação de investimento e não são uma recomendação pessoal do CME Group. Nada contido neste documento constitui a solicitação da compra ou venda de futuros ou opções.
                </p>
                <p class="mb-3">
                    Todas as atividades de investimento realizadas usando esta ferramenta serão de risco exclusivo do investidor. O CME Group isenta-se expressamente de toda responsabilidade pelo uso ou interpretação (seja pelo visitante ou por terceiros) das informações contidas neste documento.
                </p>
                <p class="mb-3">
                    As decisões tomadas com base nessas informações são de responsabilidade exclusiva do investidor. Qualquer visitante desta página concorda em isentar o CME Group e suas afiliadas e licenciadores de quaisquer reivindicações por danos decorrentes de quaisquer decisões que o visitante tome com base nessas informações.
                </p>
                <p class="mb-0">
                    Utilize de acordo com os 
                    <a href="https://group.operebem.com.br/terminal/terms-of-use" target="_blank" rel="noopener" class="fw-bold">Termos de Uso</a>
                    e <a href="https://group.operebem.com.br/terminal/risk-advice" target="_blank" rel="noopener" class="fw-bold">Aviso de Risco</a>.
                </p>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3 small">
                    <a href="https://www.cmegroup.com/pt/markets/interest-rates/cme-fedwatch-tool.html" target="_blank" rel="noopener" class="text-muted">CME • FedWatch</a>
                </div>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Concordo</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4">
    <div class="fedwatch-container">
        <!-- Logo e título -->
        <div class="fedwatch-header">
            <img src="https://companieslogo.com/img/orig/CME-ce2f32ad.png?t=1720244491" alt="CME Group Logo" />
            <h1>CME - Fed Watch Tool</h1>
        </div>
        
        <!-- Card com iframe -->
        <div class="fedwatch-card">
            <div class="aspect-16-9">
                <iframe
                    class="cme-iframe"
                    src="<?= htmlspecialchars($iframe_src, ENT_QUOTES, 'UTF-8') ?>"
                    scrolling="auto"
                    id="cmeIframe"
                    aria-label="Integrated Fed Watch Tool"
                    loading="lazy"
                ></iframe>
            </div>
            <!-- Rodapé -->
            <div class="fedwatch-footer">
                Analise as probabilidades de mudanças na taxa do Fed e na política monetária dos EUA, conforme dados implícitos nos preços de futuros do Fed Funds dos últimos 30 dias. Utilize de acordo com os <a href="https://group.operebem.com.br/terminal/terms-of-use" target="_blank" rel="noopener" class="fw-bold">Termos de Uso</a>.
            </div>
        </div>
    </div>
</div>

<!-- FAQ (estética alinhada à home deslogada) -->
<section class="py-5" id="fed-faq">
    <div class="container">
        <div class="w-100 text-center mb-5 pb-3">
            <h3 class="title_login mb-0">Perguntas Frequentes</h3>
        </div>
        <div class="accordion" id="fedFaqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="fedFaqH1">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fedFaqC1" aria-expanded="true" aria-controls="fedFaqC1">
                        O que é o CME FedWatch Tool?
                    </button>
                </h2>
                <div id="fedFaqC1" class="accordion-collapse collapse show" aria-labelledby="fedFaqH1" data-bs-parent="#fedFaqAccordion">
                    <div class="accordion-body text-secondary">
                        É uma ferramenta da CME que estima probabilidades de decisões do FOMC a partir dos preços de futuros de Fed Funds.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="fedFaqH2">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fedFaqC2" aria-expanded="false" aria-controls="fedFaqC2">
                        De onde vêm os dados?
                    </button>
                </h2>
                <div id="fedFaqC2" class="accordion-collapse collapse" aria-labelledby="fedFaqH2" data-bs-parent="#fedFaqAccordion">
                    <div class="accordion-body text-secondary">
                        Os dados do CME FedWatch Tool não vêm de analistas ou pesquisas de opinião, mas sim do mercado financeiro em tempo real, as probabilidades são derivadas dos contratos futuros de Fed Funds negociados na CME.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="fedFaqH3">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fedFaqC3" aria-expanded="false" aria-controls="fedFaqC3">
                        Como interpretar as probabilidades?
                    </button>
                </h2>
                <div id="fedFaqC3" class="accordion-collapse collapse" aria-labelledby="fedFaqH3" data-bs-parent="#fedFaqAccordion">
                    <div class="accordion-body">
                        <div class="p-4 border rounded-4 shadow-sm" style="background-color: var(--card-bg); border-color: var(--border-color) !important; color: var(--text-primary);">
                            <div class="mb-3">
                                <h6 class="text-uppercase fw-bold mb-2" style="color: var(--secondary-color);">
                                    <i class="fas fa-percentage me-2"></i>O Que a Probabilidade Significa
                                </h6>
                                <p class="mb-0" style="color: var(--text-primary);">
                                    A porcentagem é a <strong>Probabilidade Implícita</strong> de um resultado (corte, alta ou manutenção da taxa) na data da reunião do FOMC.
                                </p>
                            </div>

                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                    <div style="color: var(--text-primary);">
                                        <strong>90% de Chance de Manutenção:</strong>
                                        <span class="d-block">O mercado financeiro já precificou que a taxa ficará inalterada. Se o FED realmente mantiver a taxa, o impacto costuma ser neutro, pois o resultado já estava "na conta".</span>
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="fas fa-bolt text-warning me-2 mt-1"></i>
                                    <div style="color: var(--text-primary);">
                                        <strong>55% de Chance de Corte:</strong>
                                        <span class="d-block">O mercado está dividido, com uma ligeira inclinação para o corte. É um cenário de alta incerteza, e os preços dos ativos reagem com força a qualquer dado econômico novo (inflação, emprego, etc.).</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Exibe o modal de disclaimer ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    const disclaimerModal = new bootstrap.Modal(document.getElementById('disclaimerModal'));
    disclaimerModal.show();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
?>
