<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cardápio - Pizzaria São Paulo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/pages/cardapio.css" rel="stylesheet">
    <style>
    /* Estilos corrigidos para os tamanhos */
    .size-options {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)) !important;
        gap: 1.5rem !important;
        padding: 1.5rem 1rem !important;
        max-width: 600px !important;
        margin: 0 auto !important;
    }
    .size-option {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        background: white !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 12px !important;
        padding: 1.5rem 1rem !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        min-height: 120px !important;
    }
    .size-option:hover {
        border-color: #dc2626 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    .size-option.selected {
        border-color: #dc2626 !important;
        background: #fef2f2 !important;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15) !important;
    }
    .size-icon {
        font-size: 2rem !important;
        margin-bottom: 0.75rem !important;
    }
    .size-label {
        font-weight: 600 !important;
        font-size: 1.1rem !important;
        color: #111827 !important;
        margin-bottom: 0.25rem !important;
    }
    .size-price {
        color: #6b7280 !important;
        font-size: 0.9rem !important;
    }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="form-header">
            <img src="../assets/img/logo.webp" alt="Pizzaria São Paulo" class="logo" width="120" height="120">
            <h1 class="form-title">Cardápio</h1>
            <p class="form-subtitle">Monte seu pedido</p>
        </header>

        <main class="cardapio-main">
            <section id="message-container"></section>
            
            <div class="stepper">
                <div class="stepper-step active" data-step="tamanho">1. Tamanho</div>
                <div class="stepper-step" data-step="sabores">2. Sabores</div>
                <div class="stepper-step" data-step="adicionais">3. Adicionais</div>
                <div class="stepper-step" data-step="endereco">4. Endereço</div>
                <div class="stepper-step" data-step="bebidas">5. Bebidas</div>
                <div class="stepper-step" data-step="finalizacao">6. Revisão</div>
            </div>

            <div class="step-content">
                <section id="step-tamanho" class="step-panel active">
                    <h2 class="step-title">Escolha o tamanho</h2>
                    <div class="size-options"></div>
                    <div class="form-actions">
                        <button id="btn-next-sabores" class="btn btn-primary" disabled>Continuar</button>
                    </div>
                </section>

                <section id="step-sabores" class="step-panel">
                    <h2 class="step-title">Escolha os sabores</h2>
                    <p class="step-hint" id="sabores-hint"></p>
                    <div class="tabs">
                        <button class="tab-btn active" data-category="tradicionais">Tradicionais</button>
                        <button class="tab-btn" data-category="premium">Premium</button>
                        <button class="tab-btn" data-category="doces">Doces</button>
                    </div>
                    <div id="pizza-list" class="pizza-grid"></div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                        <button id="btn-next-addons" class="btn btn-primary" disabled>Continuar</button>
                    </div>
                </section>

                <section id="step-adicionais" class="step-panel">
                    <h2 class="step-title">Adicionais</h2>
                    <div class="addons-grid"></div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                        <button id="btn-next-endereco" class="btn btn-primary">Continuar</button>
                    </div>
                </section>

                <section id="step-endereco" class="step-panel">
                    <h2 class="step-title">Endereço de entrega</h2>
                    <p class="step-hint">Atendemos apenas Guarapari/ES. Informe seu endereço para entrega.</p>
                    <div class="option-tabs">
                        <button class="option-tab active" id="tab-addr-list">Endereço cadastrado</button>
                        <button class="option-tab" id="tab-addr-new">Cadastrar novo</button>
                    </div>
                    <div class="endereco-content">
                        <div class="enderecos-list"></div>
                        <div class="endereco-form hidden">
                            <div class="form-grid">
                                <input type="text" id="cep" class="form-control" placeholder="CEP" />
                                <input type="text" id="logradouro" class="form-control" placeholder="Logradouro" />
                                <input type="text" id="numero" class="form-control" placeholder="Número" />
                                <input type="text" id="complemento" class="form-control" placeholder="Complemento (opcional)" />
                                <input type="text" id="bairro" class="form-control" placeholder="Bairro" />
                                <div id="bairro-suggestions" class="autocomplete"></div>
                                <div id="taxa-info" class="taxa-info"></div>
                            </div>
                            <div class="form-actions">
                                <button class="btn btn-secondary btn-back">Voltar</button>
                                <button id="btn-salvar-endereco" class="btn btn-success">Salvar Endereço</button>
                                <button id="btn-next-bebidas" class="btn btn-primary">Continuar</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="step-bebidas" class="step-panel">
                    <h2 class="step-title">Bebidas</h2>
                    <div class="bebidas-grid"></div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                        <button id="btn-next-finalizacao" class="btn btn-primary">Continuar</button>
                    </div>
                </section>

                <section id="step-finalizacao" class="step-panel">
                    <h2 class="step-title">Revisão do pedido</h2>
                    <div class="finalizacao-content">
                        <p>Confira os itens e escolha a forma de pagamento.</p>
                        <button class="btn btn-success btn-full" id="btn-finalizar">Finalizar Pedido</button>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                    </div>
                </section>
            </div>
        </main>

        
    </div>

    <aside id="cart-panel" class="cart-panel">
        <div class="cart-header">
            <h3>Seu pedido</h3>
            <button class="cart-close" onclick="closeCart()">&times;</button>
        </div>
        <div class="cart-content"></div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <strong>R$ <span id="cart-total">0.00</span></strong>
            </div>
            <button class="btn btn-primary btn-full" onclick="finalizarPedido()">Finalizar</button>
        </div>
    </aside>

    <script>
    window.__CARDAPIO_ENDPOINT__ = '../config/cardapio_data.php';
    </script>
    <script src="../assets/js/pages/cardapio.js"></script>
</body>
</html>