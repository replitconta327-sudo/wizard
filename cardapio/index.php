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
    .size-options, .flavor-mode-options {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)) !important;
        gap: 1.5rem !important;
        padding: 1.5rem 1rem !important;
        max-width: 600px !important;
        margin: 0 auto !important;
    }
    .size-option, .flavor-mode-option {
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
        min-height: 100px !important;
    }
    .size-option:hover, .flavor-mode-option:hover {
        border-color: #dc2626 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    .size-option.selected, .flavor-mode-option.selected {
        border-color: #dc2626 !important;
        background: #fef2f2 !important;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15) !important;
    }
    .size-icon { font-size: 2rem !important; margin-bottom: 0.75rem !important; }
    .size-label { font-weight: 600 !important; font-size: 1.1rem !important; color: #111827 !important; margin-bottom: 0.25rem !important; }
    .size-price { color: #6b7280 !important; font-size: 0.9rem !important; }
    .cart-items { margin-top: 1.5rem; }
    .cart-item-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cart-item-info { flex: 1; }
    .cart-item-name { font-weight: 600; color: #111827; }
    .cart-item-flavors { font-size: 0.9rem; color: #6b7280; margin-top: 0.25rem; }
    .cart-item-price { font-weight: 600; color: #dc2626; margin-right: 1rem; }
    .cart-item-actions { display: flex; gap: 0.5rem; align-items: center; }
    .btn-edit-pizza { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
    .btn-remove-pizza { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
    .qty-controls { display: flex; gap: 0.5rem; align-items: center; }
    .qty-btn { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
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
                <div class="stepper-step" data-step="modo">2. Modo</div>
                <div class="stepper-step" data-step="sabores">3. Sabores</div>
                <div class="stepper-step" data-step="carrinho">4. Carrinho</div>
                <div class="stepper-step" data-step="adicionais">5. Adicionais</div>
                <div class="stepper-step" data-step="bebidas">6. Bebidas</div>
                <div class="stepper-step" data-step="endereco">7. Endereço</div>
                <div class="stepper-step" data-step="finalizacao">8. Revisão</div>
            </div>

            <div class="step-content">
                <!-- PASSO 1: TAMANHO -->
                <section id="step-tamanho" class="step-panel active">
                    <h2 class="step-title">Escolha o tamanho</h2>
                    <div class="size-options"></div>
                    <div class="form-actions">
                        <button id="btn-next-modo" class="btn btn-primary" disabled>Continuar</button>
                    </div>
                </section>

                <!-- PASSO 2: MODO (1/2/3 sabores) -->
                <section id="step-modo" class="step-panel">
                    <h2 class="step-title">Como você quer sua pizza?</h2>
                    <p class="step-hint">Escolha se quer 1 sabor, meio a meio (2 sabores) ou 3 sabores</p>
                    <div class="flavor-mode-options"></div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                        <button id="btn-next-sabores" class="btn btn-primary" disabled>Continuar</button>
                    </div>
                </section>

                <!-- PASSO 3: SABORES -->
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
                        <button id="btn-add-to-cart" class="btn btn-primary" disabled>Adicionar ao Carrinho</button>
                    </div>
                </section>

                <!-- PASSO 4: CARRINHO -->
                <section id="step-carrinho" class="step-panel">
                    <h2 class="step-title">Seu carrinho</h2>
                    <div class="cart-items"></div>
                    <div style="margin-top: 2rem; text-align: center;">
                        <p style="color: #6b7280; margin-bottom: 1.5rem;">Deseja pedir mais uma pizza?</p>
                        <button id="btn-add-more-pizza" class="btn btn-primary" style="margin-right: 1rem;">Adicionar mais pizza</button>
                        <button id="btn-skip-more-pizza" class="btn btn-secondary">Não, continuar</button>
                    </div>
                </section>

                <!-- PASSO 6: ADICIONAIS -->
                <section id="step-adicionais" class="step-panel">
                    <h2 class="step-title">Adicionais</h2>
                    <div class="addons-grid"></div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                        <button id="btn-next-bebidas" class="btn btn-primary">Continuar</button>
                    </div>
                </section>

                <!-- PASSO 7: BEBIDAS -->
                <section id="step-bebidas" class="step-panel">
                    <h2 class="step-title">Bebidas</h2>
                    <div class="bebidas-grid"></div>
                    <div class="form-actions">
                        <button class="btn btn-secondary btn-back">Voltar</button>
                        <button id="btn-next-endereco" class="btn btn-primary">Continuar</button>
                    </div>
                </section>

                <!-- PASSO 8: ENDEREÇO -->
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
                                <input type="text" id="cep" class="form-control" placeholder="CEP" oninput="window.cardapioApp?.formatarCEP(this)" />
                                <input type="text" id="logradouro" class="form-control" placeholder="Logradouro" />
                                <input type="text" id="numero" class="form-control" placeholder="Número" />
                                <input type="text" id="complemento" class="form-control" placeholder="Complemento (opcional)" />
                                <input type="text" id="bairro" class="form-control" placeholder="Bairro" oninput="window.cardapioApp?.buscarTaxaBairro(this.value)" />
                                <div id="bairro-suggestions" class="autocomplete"></div>
                                <div id="taxa-info" class="taxa-info"></div>
                            </div>
                            <div class="form-actions">
                                <button class="btn btn-secondary btn-back">Voltar</button>
                                <button id="btn-salvar-endereco" class="btn btn-success">Salvar Endereço</button>
                                <button id="btn-skip-endereco" class="btn btn-primary">Usar este endereço</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- PASSO 9: REVISÃO -->
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

    <script>
    window.__CARDAPIO_ENDPOINT__ = '../config/cardapio_data.php';
    </script>
    <script src="../assets/js/pages/cardapio.js"></script>
</body>
</html>
