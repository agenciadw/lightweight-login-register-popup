# CHANGELOG - Lightweight Login & Register Popup

## 🔧 **Versão 1.4.1** - Correção de Interceptação de Checkout

### Data: 30 de Janeiro de 2026

### 🎯 Destaques da Versão

- 🔧 **Correção Crítica:** Popup agora abre SEMPRE ao clicar em "Finalizar Compra"
- 🗑️ **Removido:** Opção "Controle do Botão de Checkout" (causava conflitos)
- 🚀 **Melhor Interceptação:** Usa capture phase para garantir popup antes de navegação

### 🐛 Correções

- **Popup não abria:** Ao clicar em "Finalizar Compra" no carrinho, navegava direto para checkout
- **Interceptação aprimorada:** `document.addEventListener(..., true)` (capture phase)
- **Múltiplos seletores:** `.wc-proceed-to-checkout a`, `a[href*="finalizar-compra"]`, etc.
- **stopImmediatePropagation:** Impede outros handlers de executar

### 🗑️ Removido

- **Opção "Controle do Botão de Checkout":** Removida por causar conflitos
- **CSS de ocultação:** Removido do frontend
- **Documentação:** `docs/HIDE_CHECKOUT_BUTTON.md` deletado

### 📝 Arquivos Modificados

- `lightweight-login-register-popup.php` → v1.4.1
- `includes/class-llrp-admin.php` → Removida opção `hide_checkout_button`
- `includes/class-llrp-frontend.php` → Removido CSS de ocultação
- `assets/js/llrp-script.js` → Interceptação com capture phase
- `plugin-info.json` → v1.4.1
- `readme.md` → Removida seção de controle de checkout

---

## 🛍️ **Versão 1.4.0** - Melhorias de UX para Senha Expirada

### Data: 30 de Janeiro de 2026

### 🎯 Destaques da Versão

- 🔐 **Botão "Recuperar Senha":** Substituição inteligente quando senha expirada
- 🎨 **Melhor UX:** Fluxo simplificado para recuperação de senha expirada
- 🔄 **Cache Busting:** Versão atualizada força recarga dos assets

### ✨ Novos Recursos

#### Melhorias no Sistema de Senha Expirada
- **Botão Dinâmico:** "Login com Senha" é substituído por "🔐 Recuperar Senha" (vermelho)
- **Pré-preenchimento:** E-mail automaticamente preenchido na recuperação
- **Mensagens Contextuais:** Avisos específicos para cada tipo de expiração
- **Limpeza de Estado:** Remove avisos/botões ao trocar steps
- **Evita Duplicação:** Sistema previne múltiplos elementos

### 🔧 Melhorias

#### JavaScript
- Lógica aprimorada em `showStep()` para limpeza de estado
- Gerenciamento inteligente de visibilidade de botões
- Prevenção de duplicação de elementos temporários

### 🐛 Correções

- Aviso de senha expirada agora aparece corretamente no popup
- Ordem de execução corrigida: `showStep()` → adicionar aviso
- Botões não duplicam mais ao navegar entre steps

---

## 🔒 **Versão 1.3.0** - Sistema de Expiração de Senha

### Data: Janeiro 2026

### 🎯 Destaques da Versão

- 🔐 **Expiração por Tempo:** Força troca de senha após X dias configuráveis
- ⏰ **Expiração por Inatividade:** Protege contas sem uso recente
- ⚠️ **Avisos Progressivos:** Notificações 7 dias antes da expiração
- 🚫 **Modal Bloqueador:** Impede acesso até a troca quando expirado
- 🎨 **Interface Completa:** Configuração visual no admin

### ✨ Principais Funcionalidades

#### Sistema de Expiração Configurável
- Expiração por tempo (1-365 dias, padrão: 90 dias)
- Expiração por inatividade (1-365 dias, padrão: 30 dias)
- Ativação independente de cada funcionalidade
- Avisos começam 7 dias antes da expiração

#### Verificações Automáticas
- ✅ Popup de login - Aviso ao detectar usuário
- ✅ Página Minha Conta - Banner e modal
- ✅ Checkout - Bloqueia finalização se senha expirada
- ✅ Após qualquer tipo de login - Atualiza datas

#### Modal de Troca Forçada
- Design responsivo e moderno
- Validação em tempo real
- Campos: Senha Atual, Nova Senha, Confirmar
- Feedback visual de sucesso/erro
- Bloqueia navegação até concluir

#### Avisos Inteligentes
- 🟡 Aviso amarelo 7 dias antes
- 🔴 Modal bloqueador na expiração
- ✖️ Botão para dispensar avisos temporariamente
- 📝 Mensagens contextuais por tipo de expiração

### 🔧 Arquivos Novos

- `includes/class-llrp-password-expiration.php` - Gerencia expiração
- `docs/PASSWORD_EXPIRATION.md` - Documentação completa
- `docs/changelog/CHANGELOG_v1.3.0.md` - Detalhes da versão

### 📊 User Meta Adicionados

```php
_llrp_last_password_change        // Data da última troca de senha
_llrp_last_login                  // Data do último login
_llrp_password_warning_dismissed  // Data quando aviso foi dispensado
```

### 🎯 Casos de Uso Recomendados

- **E-commerce B2C:** 90 dias / 60 dias inatividade
- **E-commerce B2B:** 60 dias / 30 dias inatividade
- **Marketplace:** 180 dias / 90 dias inatividade
- **Dados Sensíveis:** 30 dias / 15 dias inatividade

[📖 Changelog Detalhado v1.3.0](./CHANGELOG_v1.3.0.md)  
[📚 Documentação Completa](../PASSWORD_EXPIRATION.md)

---

## 🚀 **Versão 1.2.0** - Otimização e Segurança

### Data: Dezembro de 2025

### 🎯 Destaques da Versão

- ⚡ **Performance Crítica:** Redução de 64 para 1 query ao banco de dados
- 🛡️ **Proteção Anti-Bot:** Suporte completo a Cloudflare Turnstile e Google reCAPTCHA
- 🎨 **Admin Reformulado:** Interface em abas com UX moderna
- 🔒 **Preservação de Dados:** Sistema de campos hidden automáticos
- 🤝 **CleanTalk Compatible:** Integração automática com anti-spam

### ✨ Principais Funcionalidades

#### Sistema de Cache Avançado
- Cache estático em memória (runtime)
- Cache persistente com transients (1 hora)
- Auto-limpeza ao atualizar opções
- **Resultado:** De 64 queries para 1 única query

#### Proteção Anti-Bot
- **Cloudflare Turnstile:** Gratuito, rápido e moderno
- **reCAPTCHA v2 Checkbox:** Desafio manual
- **reCAPTCHA v2 Invisível:** Transparente
- **reCAPTCHA v3:** Score-based (0.0 - 1.0)
- Renderização dinâmica por step
- Validação backend robusta

#### Interface Admin Redesenhada
- Sistema de abas intuitivo
- Cards visuais modernos
- Color pickers integrados
- Validação em tempo real
- Botão "Testar Configuração"

#### Compatibilidade CleanTalk
- Detecção automática de campos
- Injeção em todas as requisições AJAX
- Zero configuração necessária

### 🐛 Correções
- ✅ Caixa de diálogo "Sair do site?" após salvar
- ✅ Perda de dados ao alternar entre abas
- ✅ Erro reCAPTCHA "Already Rendered"
- ✅ reCAPTCHA v2 Checkbox não validando
- ✅ Token invisível não capturado

### 📦 Arquivos Modificados
- `includes/class-llrp-frontend.php` - Sistema de cache
- `includes/class-llrp-ajax.php` - Validação de captcha
- `includes/class-llrp-admin.php` - Interface redesenhada
- `assets/js/llrp-script.js` - Captcha e CleanTalk
- `assets/js/llrp-admin.js` - Admin interativo
- `assets/css/llrp-admin.css` - Novos estilos

[📖 Changelog Detalhado v1.2.0](./CHANGELOG_v1.2.0.md)

---

## 🎉 **Versão 1.0.0** - Lançamento Oficial

### Data: Setembro de 2025

---

## 🚀 **FUNCIONALIDADES PRINCIPAIS**

### 🔑 **Sistema de Autenticação Avançado**

- **Login via email/telefone/CPF** com verificação inteligente de identidade
- **Cadastro otimizado** com validação em tempo real
- **Recuperação de senha** integrada ao sistema nativo do WordPress
- **Login social** com Google OAuth2 e Facebook SDK
- **Autenticação por código** via email e WhatsApp (Joinotify)
- **Sistema de nonce** robusto para segurança

### 🛒 **Persistência Inteligente de Carrinho**

- **Backup automático** do carrinho antes de qualquer login
- **Sistema triplo de backup:** localStorage (primário), sessionStorage (failsafe), DOM backup (adicional)
- **Restauração imediata** após login/cadastro bem-sucedido
- **Mesclagem inteligente** entre carrinho local e carrinho do usuário
- **Proteção contra perda** em qualquer cenário de autenticação
- **Logs detalhados** para rastreamento de estado do carrinho

### 📝 **Auto-preenchimento Inteligente**

- **Preenchimento automático** de todos os dados do usuário no checkout
- **Mapeamento completo** de campos: email, nome, telefone, endereço, CPF/CNPJ
- **Sincronização bidireccional** entre `account_email` e `billing_email`
- **Sistema múltiplo de detecção:**
  - Login via popup → auto-preenchimento imediato
  - Login direto WooCommerce → detecção via hooks
  - Usuário já logado → auto-preenchimento forçado
  - Fallback AJAX → verificação de formulário vazio
- **Compatibilidade total** com Brazilian Market on WooCommerce
- **Triggers automáticos** para plugins de terceiros

### 🔄 **Sistema de Redirecionamento Inteligente**

- **Análise de contexto** baseada em HTTP_REFERER
- **Lógica específica por origem:**
  - Login do carrinho → redireciona para checkout
  - Login direto no checkout → permanece no checkout
  - Outros casos → checkout padrão
- **Proteção contra limpeza** do estado do checkout
- **Compatibilidade com Fluid Checkout** com reload inteligente

### 🎨 **Interface de Usuário Otimizada**

- **Popup responsivo** com design moderno
- **Botão de fechamento condicional** (oculto em páginas críticas como checkout)
- **Feedback visual** em tempo real para ações do usuário
- **Animações suaves** e transições elegantes
- **Temas personalizáveis** via CSS

---

## 🛡️ **COMPATIBILIDADE E INTEGRAÇÃO**

### 🔌 **Plugins Suportados**

- ✅ **WooCommerce** (8.0+) - Compatibilidade total
- ✅ **Fluid Checkout** - Integração nativa com preservação de estado
- ✅ **Brazilian Market on WooCommerce** - Suporte completo a campos brasileiros
- ✅ **Joinotify** - Integração para códigos via WhatsApp

### 🌐 **Serviços Externos**

- ✅ **Google OAuth2** - Login social seguro
- ✅ **Facebook SDK** - Autenticação via Facebook
- ✅ **WhatsApp API** (via Joinotify) - Códigos de verificação

### 🗂️ **Campos Brasileiros Suportados**

- `billing_cpf` / `billing_cnpj` - Documentos brasileiros
- `billing_number` - Número do endereço
- `billing_neighborhood` - Bairro
- `billing_cellphone` - Celular
- `billing_birthdate` - Data de nascimento
- `billing_sex` - Sexo
- `billing_company_cnpj` - CNPJ da empresa
- `billing_ie` - Inscrição estadual
- `billing_rg` - RG

---

## 🔧 **ARQUITETURA TÉCNICA**

### 📁 **Estrutura de Arquivos**

```
lightweight-login-register-popup/
├── lightweight-login-register-popup.php    # Plugin principal
├── CHANGELOG.md                             # Histórico de versões
├── readme.md                               # Documentação
├── assets/
│   ├── css/llrp-style.css                 # Estilos do popup
│   └── js/
│       ├── llrp-script.js                 # JavaScript principal
│       └── llrp-admin.js                  # Scripts do admin
└── includes/
    ├── class-llrp-frontend.php            # Lógica do frontend
    ├── class-llrp-ajax.php                # Handlers AJAX
    └── class-llrp-admin.php               # Painel administrativo
```

### 🔒 **Segurança Implementada**

- **Verificação de nonce** em todas as requisições AJAX
- **Sanitização** de todos os dados de entrada
- **Rate limiting** básico para prevenção de spam
- **Validação de tokens** para login social
- **Sessões seguras** para contexto de autenticação

### ⚡ **Otimizações de Performance**

- **Carregamento condicional** de assets (apenas quando necessário)
- **Cache inteligente** de dados do usuário
- **Requisições AJAX otimizadas** com fallbacks
- **Lazy loading** de SDKs sociais
- **Minificação** de código JavaScript e CSS

---

## 📋 **CASOS DE USO SUPORTADOS**

### ✅ **Cenário 1: Login no Carrinho**

1. Usuário adiciona produtos ao carrinho
2. Clica em "Finalizar Compra"
3. Faz login via popup
4. **Resultado:** Redirecionado para checkout com carrinho preservado e dados preenchidos

### ✅ **Cenário 2: Checkout Direto com Login**

1. Usuário acessa `/checkout` diretamente
2. Faz login via sistema nativo do WooCommerce
3. **Resultado:** Dados preenchidos automaticamente sem reload

### ✅ **Cenário 3: Usuário Já Logado**

1. Usuário logado acessa checkout
2. Formulário aparece vazio inicialmente
3. **Resultado:** Auto-preenchimento forçado detecta e preenche dados

### ✅ **Cenário 4: Registro de Nova Conta**

1. Usuário cria conta via popup ou sistema nativo
2. **Resultado:** Email e dados disponíveis preenchidos automaticamente

### ✅ **Cenário 5: Login Social**

1. Usuário usa Google ou Facebook
2. **Resultado:** Dados sociais importados e formulário preenchido

---

## 🐛 **PROBLEMAS RESOLVIDOS**

### 🛒 **Carrinho**

- ✅ **Perda de itens** após login em qualquer cenário
- ✅ **Conflitos** com Fluid Checkout
- ✅ **Estado inconsistente** entre sessões
- ✅ **Fragmentos de carrinho** não atualizados

### 📝 **Auto-preenchimento**

- ✅ **Campos vazios** após login direto no checkout
- ✅ **Conflitos** entre sistemas de preenchimento
- ✅ **Sincronização de email** entre account_email ↔ billing_email
- ✅ **Dados não carregados** em acessos subsequentes

### 🔄 **Redirecionamento**

- ✅ **Login do carrinho** voltando para carrinho (ao invés de checkout)
- ✅ **Checkout sendo limpo** após login direto
- ✅ **Loops de redirecionamento** em alguns cenários

### 🎨 **Interface**

- ✅ **Botão X aparecendo** em páginas críticas (checkout)
- ✅ **Feedback visual** inadequado para ações
- ✅ **Responsividade** em dispositivos móveis

---

## 🔍 **LOGS E DEBUG**

### 📊 **Sistema de Logs Implementado**

- `🛒 CRITICAL` - Operações críticas do carrinho
- `🔑 LLRP` - Detecção de login/registro
- `🔄 LLRP` - Auto-preenchimento e redirecionamento
- `📧 LLRP CRITICAL` - Sincronização de emails

### 🧪 **Exemplos de Logs**

```
🛒 CRITICAL: Cart backup completed successfully with 3 methods
🔑 LLRP: Direct WooCommerce checkout login detected for user: 52
🔄 LLRP: Smart redirect - User came from cart, redirecting to checkout
📧 LLRP CRITICAL: Email sync for user 52 - account_email = billing_email = user@example.com
🔄 LLRP: Skipping force autofill - recent popup login detected
```

---

## 🚀 **PRÓXIMAS MELHORIAS**

### 🎯 **Roadmap v1.1**

- [ ] **Suporte a múltiplos idiomas** (i18n completo)
- [ ] **API REST** para integração com outros plugins
- [ ] **Webhook system** para notificações externas
- [ ] **Analytics dashboard** no admin
- [ ] **A/B testing** para otimização de conversão

### 🎨 **UX/UI v1.2**

- [ ] **Temas pré-definidos** para diferentes estilos
- [ ] **Customizador visual** no admin
- [ ] **Animações avançadas** com CSS3
- [ ] **Dark mode** automático

---

## 📞 **SUPORTE E DOCUMENTAÇÃO**

### 🔗 **Links Úteis**

- **GitHub:** https://github.com/agenciadw/lightweight-login-register-popup
- **Documentação:** [Em desenvolvimento]
- **Suporte:** david@dwdigital.com.br

### 🏷️ **Tags**

`woocommerce` `login` `register` `popup` `carrinho` `checkout` `social-login` `google` `facebook` `brazilian-market` `fluid-checkout`

---

**🎉 Parabéns! Você está usando a versão 1.0.0 - uma solução completa e robusta para autenticação no WooCommerce!**


