# Lightweight Login & Register Popup v1.2.0

![Plugin Version](https://img.shields.io/badge/version-1.2.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.6+-green.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0+-orange.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)

Popup inteligente para WooCommerce com suporte a checkout de convidado, login social, persistência de carrinho e compatibilidade total com plugins de checkout populares.

## 🚀 Funcionalidades Principais

### ✨ Checkout de Convidado Inteligente
- **Detecção Automática**: Detecta automaticamente se o checkout de convidado está habilitado no WooCommerce
- **Comportamento Adaptativo**: Se adapta ao comportamento de checkout sem duplicação de popups
- **Opção de Pular**: Quando checkout de convidado está ativo, oferece opção "Não quero fazer cadastro" com botão "Pular para o checkout"

### 🔐 Múltiplas Opções de Login
- **E-mail**: Login tradicional com e-mail e senha
- **CPF/CNPJ**: Suporte a identificadores brasileiros
- **Login Social**: Integração com Google e Facebook
- **Código por E-mail/WhatsApp**: Sistema de código de verificação
- **Proteção Anti-Bot**: Cloudflare Turnstile e Google reCAPTCHA (v2 Checkbox, v2 Invisível, v3)

### 🛒 Persistência de Carrinho
- **Backup Automático**: Salva carrinho antes de login/registro
- **Restauração Inteligente**: Restaura carrinho após login com sistema triplo de backup
- **Compatibilidade Total**: Funciona com todos os plugins de checkout

### 🎨 Interface Moderna
- **Design Responsivo**: Funciona perfeitamente em desktop e mobile
- **Customização Total**: Cores, fontes e estilos personalizáveis
- **Integração Nativa**: Se integra perfeitamente ao design do WooCommerce

## 📋 Requisitos

- **WordPress**: 6.6 ou superior
- **WooCommerce**: 8.0 ou superior
- **PHP**: 7.4 ou superior
- **Navegadores**: Chrome, Firefox, Safari, Edge (versões recentes)

## 🔧 Instalação

1. **Upload do Plugin**:
   ```
   wp-content/plugins/lightweight-login-register-popup/
   ```

2. **Ativação**:
   - Acesse WordPress Admin → Plugins
   - Ative "Lightweight Login & Register Popup"

3. **Configuração**:
   - Vá para WooCommerce → Login Popup
   - Configure as opções desejadas

## ⚙️ Configuração

### Configurações Básicas
- **Cores**: Personalize cores do popup, botões e textos
- **Textos**: Customize todos os textos do popup
- **Fontes**: Configure tamanhos e famílias de fontes

### Login Social
- **Google**: Configure Client ID e Client Secret
- **Facebook**: Configure App ID e App Secret

### Identificadores Brasileiros
- **CPF**: Habilite login com CPF
- **CNPJ**: Habilite login com CNPJ

### WhatsApp (Opcional)
- **Integração**: Requer plugin Joinotify
- **Códigos**: Envio de códigos via WhatsApp

## 🎯 Como Funciona

### Quando Checkout de Convidado está **HABILITADO**:
1. **Carrinho**: Popup aparece com opção "Não quero fazer cadastro"
2. **Escolha**: Usuário pode fazer login OU pular para checkout
3. **Checkout**: Sem popup (evita duplicação)

### Quando Checkout de Convidado está **DESABILITADO**:
1. **Carrinho**: Popup aparece solicitando login
2. **Checkout**: Popup aparece novamente se não logado
3. **Comportamento**: Original do plugin mantido

## 🔌 Compatibilidade

### Plugins de Checkout e Anti-Spam
- ✅ **Fluid Checkout**: Compatibilidade total
- ✅ **Brazilian Market**: Integração completa
- ✅ **WooCommerce Blocks**: Suporte nativo
- ✅ **CleanTalk Anti-Spam**: Compatibilidade total com injeção automática de campos
- ✅ **Outros plugins**: Compatibilidade geral

### Temas
- ✅ **Temas Oficiais**: Storefront, Twenty Twenty-Four, etc.
- ✅ **Temas Premium**: Astra, GeneratePress, etc.
- ✅ **Temas Personalizados**: Compatibilidade geral

### Plugins WooCommerce
- ✅ **Pagamentos**: Todos os gateways
- ✅ **Frete**: Todos os métodos
- ✅ **Produtos**: Todos os tipos
- ✅ **Cupons**: Sistema completo

## 📱 Recursos Técnicos

### Performance
- **Ultra Otimizado**: Redução de 64 queries para 1 única query ao banco de dados
- **Sistema de Cache**: Cache estático em memória + transients persistentes
- **Carregamento Condicional**: Assets carregados apenas quando necessário
- **Código Otimizado**: Clean code e estrutura modular
- **Cache Friendly**: Compatível com sistemas de cache

### Segurança
- **Proteção Anti-Bot**: Cloudflare Turnstile e Google reCAPTCHA (v2/v3)
- **CleanTalk Compatible**: Integração automática com anti-spam
- **Nonces**: Verificação de segurança em todas as requisições
- **Sanitização**: Todos os dados sanitizados
- **Validação**: Validação rigorosa de entradas

### Acessibilidade
- **WCAG**: Compatível com diretrizes de acessibilidade
- **Keyboard Navigation**: Navegação por teclado
- **Screen Readers**: Suporte a leitores de tela

## 🛠️ Desenvolvimento

### Estrutura do Plugin
```
lightweight-login-register-popup/
├── assets/
│   ├── css/           # Estilos
│   ├── js/            # JavaScript
│   └── screenshot/    # Screenshots
├── docs/
│   └── changelog/     # Histórico de versões
├── includes/
│   ├── class-llrp-admin.php    # Painel administrativo
│   ├── class-llrp-ajax.php     # Handlers AJAX
│   └── class-llrp-frontend.php # Frontend
└── lightweight-login-register-popup.php
```

### Hooks e Filtros
- **Actions**: `llrp_before_popup`, `llrp_after_login`
- **Filters**: `llrp_popup_styles`, `llrp_user_data`

## 📊 Changelog

### v1.2.0 (Dezembro 2025)
- ✅ **Otimização de Queries**: Redução de 64 para 1 query ao banco de dados
- ✅ **Sistema de Cache**: Cache estático + transients para máxima performance
- ✅ **Captcha Completo**: Suporte a Cloudflare Turnstile e Google reCAPTCHA (v2/v3)
- ✅ **Admin UI/UX**: Interface administrativa completamente reformulada com abas
- ✅ **Preservação de Dados**: Campos hidden automáticos para não perder configurações
- ✅ **CleanTalk Compatible**: Compatibilidade total com anti-spam CleanTalk
- ✅ **Mensagens Inteligentes**: Erros específicos para facilitar debug
- ✅ **Botão de Teste**: Teste de configuração de captcha direto no admin

### v1.1.0 (Novembro 2025)
- ✅ Reestruturação e clean code
- ✅ Organização de pastas
- ✅ Documentação atualizada
- ✅ Performance otimizada

### v1.0.4 (Novembro 2025)
- ✅ Checkout de convidado inteligente
- ✅ Detecção automática de configurações
- ✅ Opção "Pular para o checkout"
- ✅ Correção de popup duplicado

### v1.0.3 (Outubro 2025)
- ✅ Login social (Google/Facebook)
- ✅ Login com CPF/CNPJ
- ✅ Integração WhatsApp
- ✅ Persistência de carrinho

[Ver changelog completo](docs/changelog/)

## 🐛 Suporte e Bugs

### Reportar Problemas
- **GitHub Issues**: [Criar issue](https://github.com/agenciadw/lightweight-login-register-popup/issues)
- **Email**: david@dwdigital.com.br

### Solução de Problemas
1. **Verifique Requisitos**: WordPress, WooCommerce, PHP
2. **Desative Outros Plugins**: Teste com plugins desativados
3. **Tema Padrão**: Teste com tema padrão
4. **Logs de Erro**: Verifique logs do WordPress

## 📄 Licença

Este plugin é licenciado sob GPL v2 ou posterior.

## 👨‍💻 Desenvolvedor

**David William da Costa**
- GitHub: [@agenciadw](https://github.com/agenciadw)
- Website: [DW Digital](https://dwdigital.com.br)
- Email: david@dwdigital.com.br

## 🙏 Agradecimentos

- Comunidade WordPress
- Desenvolvedores WooCommerce
- Testadores e contribuidores
- Usuários que reportam bugs e sugerem melhorias

---

**⭐ Se este plugin foi útil para você, considere dar uma estrela no GitHub!**