=== Lightweight Login & Register Popup ===
Contributors: agenciadw, davidwilliamdacosta
Tags: woocommerce, login, register, popup, carrinho, checkout, social-login, google, facebook, cpf, cnpj, brazilian-market, fluid-checkout, hpos
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

🚀 Solução completa de autenticação para WooCommerce com persistência de carrinho, auto-preenchimento inteligente e compatibilidade universal.

== Descrição ==

**Lightweight Login & Register Popup** é uma solução avançada e completa para autenticação no WooCommerce que revoluciona a experiência do usuário durante o processo de compra.

🎯 **PROBLEMA RESOLVIDO:**
Elimina a frustração de perder itens do carrinho ou ter que preencher dados manualmente após login/cadastro, oferecendo uma experiência fluida e sem interrupções.

🚀 **FUNCIONALIDADES PRINCIPAIS:**

**🔑 Sistema de Autenticação Avançado**
* **Login múltiplo:** E-mail, telefone, CPF ou CNPJ
* **Cadastro otimizado** com validação em tempo real
* **Login social:** Google OAuth2 e Facebook SDK integrados
* **Códigos por WhatsApp:** Integração com Joinotify para códigos via WhatsApp
* **Recuperação de senha** integrada ao WordPress nativo

**🛒 Persistência Inteligente de Carrinho**
* **Backup automático** antes de qualquer login (localStorage + sessionStorage + DOM)
* **Restauração imediata** após autenticação bem-sucedida
* **Zero perda de itens** em qualquer cenário de login
* **Mesclagem inteligente** entre carrinho local e do usuário

**📝 Auto-preenchimento Inteligente**
* **Preenchimento automático completo** de todos os dados do usuário
* **Sincronização bidireccional** account_email ↔ billing_email
* **Compatibilidade total** com Brazilian Market on WooCommerce
* **Múltiplos pontos de detecção:** popup, login direto, usuário já logado

**🔄 Sistema de Redirecionamento Inteligente**
* **Análise de contexto** baseada em HTTP_REFERER
* **Login do carrinho** → redireciona para checkout
* **Login direto no checkout** → permanece no checkout (preserva estado)
* **Proteção contra limpeza** do estado do checkout

**🎨 Interface Otimizada**
* **Popup responsivo** com design moderno
* **Botão de fechamento condicional** (oculto em páginas críticas)
* **Feedback visual em tempo real**
* **Temas personalizáveis** via painel administrativo

== Compatibilidade ==

**✅ WooCommerce Moderno:**
* HPOS (High-Performance Order Storage) ✅
* Interactivity API powered Mini Cart ✅
* Cart & Checkout Blocks ✅
* WooCommerce tradicional ✅

**✅ Plugins Especializados:**
* Fluid Checkout ✅
* Brazilian Market on WooCommerce ✅
* Extra Checkout Fields for Brazil ✅
* Joinotify (WhatsApp) ✅

**✅ Campos Brasileiros:**
* CPF/CNPJ, Número, Bairro, Celular
* Data de nascimento, Sexo, IE, RG
* Endereço completo brasileiro

== Instalação ==

1. Faça upload dos arquivos para `/wp-content/plugins/lightweight-login-register-popup/`
2. Ative o plugin em **Plugins > Plugins instalados**
3. Configure em **WooCommerce > Login Popup**
4. Personalize cores, textos e comportamentos
5. Configure login social (Google/Facebook) se desejar

== Configuração ==

**🔧 Configurações Básicas:**
* Ative/desative login com CPF/CNPJ
* Configure cores e tipografia do popup
* Personalize textos e mensagens
* Ajuste comportamento de redirecionamento

**🌐 Login Social:**
* Configure Google Client ID para login com Google
* Configure Facebook App ID/Secret para login com Facebook
* Botões automáticos nos formulários de login/registro

**📱 WhatsApp (Joinotify):**
* Configure número remetente para códigos via WhatsApp
* Ative botões interativos para melhor UX
* Mensagens personalizáveis com código de verificação

== Casos de Uso ==

**🛒 Cenário 1: Compra no Carrinho**
1. Cliente adiciona produtos ao carrinho
2. Clica em "Finalizar Compra" 
3. Faz login via popup elegante
4. ✅ **Resultado:** Redirecionado para checkout com carrinho preservado e dados preenchidos

**🔄 Cenário 2: Checkout Direto**
1. Cliente acessa `/checkout` diretamente
2. Faz login via sistema nativo WooCommerce
3. ✅ **Resultado:** Dados preenchidos automaticamente sem perda de estado

**👤 Cenário 3: Usuário Já Logado**
1. Cliente logado acessa checkout
2. Formulário aparece inicialmente vazio
3. ✅ **Resultado:** Auto-preenchimento automático detecta e preenche todos os dados

**📱 Cenário 4: Login Social**
1. Cliente usa Google ou Facebook
2. ✅ **Resultado:** Dados sociais importados automaticamente

== Perguntas Frequentes ==

= O plugin funciona com o Fluid Checkout? =
Sim! Compatibilidade total com Fluid Checkout, incluindo preservação de estado e auto-preenchimento.

= Funciona com campos brasileiros? =
Sim! Suporte completo ao Brazilian Market on WooCommerce e todos os campos brasileiros (CPF, CNPJ, bairro, etc.).

= O carrinho é perdido após login? =
Não! Sistema triplo de backup garante que o carrinho nunca seja perdido em nenhum cenário.

= Funciona com a Interactivity API do WooCommerce? =
Sim! Compatibilidade total com HPOS, Interactivity API e Mini Cart moderno.

= Posso personalizar as cores e textos? =
Sim! Painel administrativo completo permite personalizar cores, fontes, textos e comportamentos.

= Funciona com login via WhatsApp? =
Sim! Integração com Joinotify para envio de códigos via WhatsApp com botões interativos.

== Screenshots ==

1. Popup de login elegante na página do carrinho
2. Opções de login: senha, código por email/WhatsApp, social
3. Interface de cadastro otimizada
4. Painel administrativo completo
5. Compatibilidade com checkout direto

== Changelog ==

= 1.0.0 - 19/09/2025 =

🎉 **LANÇAMENTO OFICIAL - VERSÃO ESTÁVEL**

**🚀 FUNCIONALIDADES PRINCIPAIS CONSOLIDADAS:**
* ✅ Sistema de autenticação avançado (email/telefone/CPF/social)
* ✅ Persistência inteligente de carrinho (sistema triplo de backup)  
* ✅ Auto-preenchimento inteligente (múltiplos pontos de detecção)
* ✅ Redirecionamento inteligente (baseado em contexto)
* ✅ Compatibilidade universal (Fluid Checkout + Brazilian Market + HPOS)

**🛡️ PROBLEMAS CRÍTICOS RESOLVIDOS:**
* ✅ Perda de carrinho após login (RESOLVIDO)
* ✅ Auto-preenchimento checkout direto (RESOLVIDO)  
* ✅ Conflitos entre sistemas de preenchimento (RESOLVIDO)
* ✅ Redirecionamento incorreto (RESOLVIDO)
* ✅ Sincronização de emails account_email ↔ billing_email (RESOLVIDO)

**🔧 COMPATIBILIDADE UNIVERSAL:**
* ✅ HPOS (High-Performance Order Storage)
* ✅ Interactivity API powered Mini Cart
* ✅ Cart & Checkout Blocks
* ✅ WooCommerce tradicional
* ✅ Fluid Checkout
* ✅ Brazilian Market on WooCommerce

**📋 CENÁRIOS TESTADOS E FUNCIONANDO:**
* ✅ Login carrinho → checkout (carrinho preservado)
* ✅ Login direto checkout → dados preenchidos
* ✅ Usuário já logado → auto-preenchimento
* ✅ Registro nova conta → email preenchido
* ✅ Login social → dados importados

**🎯 MARCO HISTÓRICO:**
Plugin robusto, testado e pronto para produção com arquitetura escalável e código limpo.

== Upgrade Notice ==

= 1.0.0 =
🎉 Versão estável com compatibilidade universal! Backup recomendado antes da atualização. Todos os problemas críticos foram resolvidos.

== Suporte ==

**📞 Contato:** david@dwdigital.com.br
**🔗 GitHub:** https://github.com/agenciadw/lightweight-login-register-popup
**📚 Documentação:** Incluída no painel administrativo

== Desenvolvedor ==

Desenvolvido por **David William da Costa** - Especialista em WooCommerce e soluções e-commerce.

🏷️ **Tags:** woocommerce, login, register, popup, carrinho, checkout, social-login, google, facebook, cpf, cnpj, brazilian-market, fluid-checkout, hpos, interactivity-api