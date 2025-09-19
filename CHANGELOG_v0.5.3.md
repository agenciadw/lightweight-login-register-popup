# CHANGELOG v0.5.3

## 🎯 CHECKOUT DIRETO MELHORADO - MODO SEGURO

### Data: 19 de setembro de 2025

---

## ✅ CORREÇÕES CRÍTICAS IMPLEMENTADAS

### 🛒 **CARRINHO PRESERVADO - FUNCIONAMENTO CORRETO MANTIDO**

- **Sistema de backup mantido** mas em modo mais seguro
- **Apenas restaura carrinho vazio** - nunca sobrescreve carrinho existente
- **Verificação de contagem** antes de qualquer ação de restauração
- **Logs melhorados** para debug sem interferir no funcionamento

**Código crítico melhorado:**

```javascript
// SAFE MODE: Only restore if current cart is empty
var currentCartCount = $(".cart-contents-count").text() || "0";
if (currentCartCount === "0" || parseInt(currentCartCount) === 0) {
  console.log("🛒 SAFE: Current cart is empty, attempting restoration");
  var restored = restoreCartAfterLogin();
} else {
  console.log("🛒 SAFE: Current cart has items, not touching it");
}
```

### 🔄 **AUTO-PREENCHIMENTO CHECKOUT DIRETO MELHORADO**

#### **Detecção Inteligente de Login Direto:**

- **Hooks seguros** para `wp_login` e `user_register`
- **Exclusão de AJAX** - só ativa em login direto do WooCommerce
- **Sistema de sessão temporária** (30 segundos) para identificar contexto
- **Auto-limpeza** de dados de sessão para evitar conflitos

#### **Auto-preenchimento Duplo:**

1. **Imediato** - via session após login/registro direto
2. **Fallback** - via AJAX para usuários já logados com formulário vazio

#### **Compatibilidade Brazilian Market:**

- **Campos específicos** incluídos no auto-preenchimento
- **Triggers de atualização** para plugins de terceiros
- **Sincronização de email** account_email ↔ billing_email

---

## 🛡️ **MODO SEGURO IMPLEMENTADO**

### **Proteções Adicionadas:**

- **Não interfere** com carrinho existente
- **Não quebra** funcionalidades que já funcionavam
- **Detecção precisa** de contexto (direto vs popup)
- **Exclusão de AJAX** para evitar conflitos com nosso popup

### **Logs de Debug Melhorados:**

- `🔑 LLRP: Direct checkout login detected`
- `📝 LLRP: Direct checkout registration detected`
- `🔄 LLRP: Preparing autofill for direct_login`
- `🛒 SAFE: Current cart has items, not touching it`

---

## 📋 **CENÁRIOS TESTADOS E FUNCIONANDO**

### ✅ **Checkout Direto - Auto-preenchimento:**

1. Usuário acessa `/checkout` diretamente
2. Faz login com conta existente
3. **Resultado:** Dados preenchidos automaticamente

### ✅ **Carrinho Preservado:**

1. Usuário adiciona itens ao carrinho
2. Faz login via popup na página do carrinho
3. **Resultado:** Itens permanecem no carrinho

### ✅ **Registro Direto:**

1. Usuário acessa `/checkout` diretamente
2. Cria nova conta
3. **Resultado:** Email preenchido automaticamente

---

## 🔧 **ARQUIVOS MODIFICADOS**

### **Frontend PHP** (`includes/class-llrp-frontend.php`)

- Hooks seguros para detecção de login direto
- Sistema de sessão temporária
- Autofill inteligente apenas quando necessário

### **JavaScript** (`assets/js/llrp-script.js`)

- Função `mergeLocalCartWithUserCart()` em modo seguro
- Verificação de carrinho existente antes de restaurar

### **Plugin Principal** (`lightweight-login-register-popup.php`)

- Versão atualizada para `0.5.3`

---

## 🎯 **RESULTADO FINAL**

### **✅ O QUE FUNCIONA PERFEITAMENTE:**

- Carrinho nunca é perdido
- Auto-preenchimento no checkout direto
- Login via popup funcionando normalmente
- Compatibilidade com Brazilian Market plugin
- Sistema de backup seguro do carrinho

### **✅ O QUE FOI MELHORADO:**

- Detecção mais precisa de login direto
- Proteção contra sobrescrita de carrinho existente
- Logs mais informativos para debug
- Compatibilidade aprimorada com plugins de terceiros

**RESOLVE:** Problema de auto-preenchimento no checkout direto mantendo todas as funcionalidades existentes funcionando perfeitamente.
