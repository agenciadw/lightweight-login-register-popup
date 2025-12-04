# Compatibilidade com Outros Plugins - LLRP

## Visão Geral

O plugin Lightweight Login & Register Popup foi desenvolvido para ser compatível com os principais plugins do ecossistema WordPress/WooCommerce.

## Plugins Suportados

### ✅ Anti-Spam by CleanTalk

**Status:** Totalmente compatível desde v1.2.0

**O que faz:**
O CleanTalk é um plugin anti-spam que adiciona campos de verificação ocultos em todos os formulários do site para detectar bots.

**Como funciona a integração:**

O LLRP detecta automaticamente os campos do CleanTalk e os inclui em todas as requisições AJAX:

```javascript
// Campos detectados automaticamente:
- ct_checkjs
- ct_bot_detector_event_token
- apbct_visible_fields
- apbct_visible_fields_count
- ct_timezone
- ct_ps_timestamp
- ct_fkp_timestamp
- ct_pointer_data
- ct_has_scrolled
```

**Configuração necessária:**
Nenhuma! A compatibilidade é automática.

**Solução de problemas:**

Se você vir a mensagem:
```
*** Forbidden. You sent forms too often. Please wait a few minutes. Anti-Spam by CleanTalk. ***
```

**Soluções:**

1. **Aguarde alguns minutos** - O CleanTalk pode ter bloqueado temporariamente por detectar múltiplas tentativas
2. **Limpe o cache do navegador** (Ctrl+Shift+Delete)
3. **Verifique o console** - Você deve ver:
   ```
   🛡️ CleanTalk: Adicionando X campos de anti-spam
   ```
4. **Whitelist no CleanTalk:**
   - Acesse o painel do CleanTalk
   - Vá em Settings → Advanced
   - Adicione seu IP ou e-mail à whitelist

---

### ✅ WooCommerce

**Status:** Totalmente compatível

**Recursos integrados:**
- Cart fragments para persistência do carrinho
- Checkout autofill após login
- Compatibilidade com HPOS (High-Performance Order Storage)
- Suporte a checkout de convidado

---

### ✅ Fluid Checkout

**Status:** Totalmente compatível

**Recursos integrados:**
- Soft refresh para compatibilidade com Interactivity API
- Fragmentos customizados
- Auto-preenchimento inteligente

---

### ✅ Brazilian Market

**Status:** Totalmente compatível

**Recursos integrados:**
- Login com CPF/CNPJ
- Campos brasileiros (bairro, número, celular, etc.)
- Auto-preenchimento de endereço

---

### ✅ Cloudflare Turnstile

**Status:** Totalmente compatível desde v1.1.2

**Integração:**
- Renderização automática
- Validação server-side
- Suporte a múltiplos widgets

---

### ✅ Google reCAPTCHA (v2 e v3)

**Status:** Totalmente compatível desde v1.1.2

**Modos suportados:**
- reCAPTCHA v2 Checkbox
- reCAPTCHA v2 Invisível
- reCAPTCHA v3

---

## Plugins Testados

| Plugin | Versão Testada | Status | Notas |
|--------|---------------|--------|-------|
| CleanTalk Anti-Spam | 6.x | ✅ Compatível | Desde v1.2.0 |
| WooCommerce | 8.0+ | ✅ Compatível | Requerido |
| Fluid Checkout | 3.x | ✅ Compatível | Suporte especial |
| Brazilian Market | 3.9+ | ✅ Compatível | Login CPF/CNPJ |
| WPML | 4.x | ✅ Compatível | Multi-idioma |
| Polylang | 3.x | ✅ Compatível | Multi-idioma |

## Conflitos Conhecidos

### ⚠️ Outros plugins de Login Popup

Se você tiver outro plugin de popup de login instalado, pode haver conflitos. Recomendamos desativar outros plugins similares.

### ⚠️ Cache Agressivo

Alguns plugins de cache podem impedir o carregamento correto dos scripts. Configure exceções para:
- `/wp-admin/admin-ajax.php`
- Scripts do LLRP (`llrp-script.js`)

## Reportar Problemas de Compatibilidade

Se você encontrar problemas com algum plugin específico:

1. **Verifique a versão** - Certifique-se de estar usando a versão mais recente do LLRP
2. **Desative outros plugins** - Teste com apenas LLRP + WooCommerce + o plugin problemático
3. **Verifique o console** - Procure por erros JavaScript (F12)
4. **Reporte o problema** - Inclua:
   - Nome e versão do plugin conflitante
   - Mensagem de erro (se houver)
   - Screenshots do console
   - Passos para reproduzir

## Desenvolvendo Integrações

Se você é desenvolvedor e quer integrar seu plugin com o LLRP:

### Hook de Login Bem-sucedido

```javascript
jQuery(document).on('llrp_login_success', function(event, userData) {
  console.log('Usuário logado via LLRP:', userData);
  // Seu código aqui
});
```

### Hook de Registro Bem-sucedido

```javascript
jQuery(document).on('llrp_register_success', function(event, userData) {
  console.log('Usuário registrado via LLRP:', userData);
  // Seu código aqui
});
```

### Adicionar Campos às Requisições

```javascript
// Adicione seus campos às requisições AJAX do LLRP
jQuery(document).on('llrp_before_ajax', function(event, data) {
  data.meu_campo_customizado = 'valor';
});
```

## Changelog de Compatibilidade

### v1.2.0
- ✅ Adicionado suporte completo ao CleanTalk Anti-Spam
- ✅ Detecção automática de campos anti-spam
- ✅ Logs de debug para troubleshooting

### v1.1.5
- ✅ Melhorias no reCAPTCHA v2 checkbox
- ✅ Sistema de espera para widgets assíncronos

### v1.1.2
- ✅ Adicionado suporte a Turnstile e reCAPTCHA
- ✅ Validação backend de captchas

### v1.1.0
- ✅ Compatibilidade com Fluid Checkout
- ✅ Suporte a Brazilian Market
- ✅ Login com CPF/CNPJ

## Suporte

Para questões sobre compatibilidade, abra uma issue no GitHub ou entre em contato com o suporte.

