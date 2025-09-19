# CHANGELOG v0.5.6

## 🎯 CONFLITO DE AUTO-PREENCHIMENTO RESOLVIDO

### Data: 19 de setembro de 2025

---

## ✅ PROBLEMA CRÍTICO RESOLVIDO

### 🔄 **CONFLITO ENTRE POPUP E AUTO-PREENCHIMENTO FORÇADO**

**O que estava acontecendo:**
```
Login via popup → dados preenchidos ✅
↓ 
Sistema de auto-preenchimento forçado executa → sobrescreve/remove dados ❌
```

**Analisando o log reportado:**
```
🔑 LLRP: Ignoring login from our popup ✅ (popup funcionou)
🔄 LLRP CRITICAL: Forcing autofill for logged-in user on checkout ❌ (conflito!)
```

**Causa identificada:**
- O sistema de auto-preenchimento forçado (`force_checkout_autofill_if_logged_in`) estava sendo executado **sempre** que um usuário logado acessava o checkout
- Isso incluía situações onde o usuário acabou de fazer login via popup e os dados já estavam preenchidos
- O auto-preenchimento forçado estava **conflitando** e removendo os dados já preenchidos

---

## 🛡️ **SOLUÇÃO IMPLEMENTADA**

### **1. Sistema de Marcação de Login Popup**
```php
// Quando login vem do popup, marcar na sessão
$_SESSION['llrp_popup_login_timestamp'] = time();

// No auto-preenchimento forçado, verificar se houve login recente do popup
if ( isset( $_SESSION['llrp_popup_login_timestamp'] ) && 
     ( time() - $_SESSION['llrp_popup_login_timestamp'] ) < 10 ) {
    error_log('🔄 LLRP: Skipping force autofill - recent popup login detected');
    return; // NÃO força auto-preenchimento
}
```

### **2. Exclusão de Requisições AJAX**
```php
// Não força auto-preenchimento durante AJAX (popup está ativo)
if ( wp_doing_ajax() ) {
    error_log('🔄 LLRP: Skipping force autofill - AJAX request (likely our popup)');
    return;
}
```

### **3. Verificação Mais Rigorosa de Formulário Vazio**
```javascript
// Só executa fallback se formulário estiver REALMENTE vazio
var isFormReallyEmpty = emailField.length && !emailField.val() && 
                       $('#billing_first_name').length && !$('#billing_first_name').val();

if (isFormReallyEmpty) {
    // Só então solicita autofill
}
```

### **4. Delay Maior para Fallback**
```javascript
// Aumentou delay de 2s para 3s para evitar conflito
setTimeout(function() {
    // Fallback autofill
}, 3000); // Tempo suficiente para popup completar
```

---

## 🔍 **LOGS MELHORADOS PARA DEBUG**

### **Antes (Conflito):**
```
🔑 LLRP: Ignoring login from our popup
🔄 LLRP CRITICAL: Forcing autofill for logged-in user on checkout ← CONFLITO
```

### **Depois (Sem Conflito):**
```
🔑 LLRP: Ignoring login from our popup
🔄 LLRP: Skipping force autofill - recent popup login detected ← PROTEÇÃO
```

**Para login direto (não popup):**
```
🔄 LLRP CRITICAL: Forcing autofill for logged-in user on checkout (DIRECT ACCESS)
```

---

## 📋 **CENÁRIOS CORRIGIDOS**

### ✅ **Cenário 1: Login via popup no checkout**
1. Usuário faz login via nosso popup
2. Dados são preenchidos pelo popup ✅
3. Sistema detecta login recente do popup
4. **Auto-preenchimento forçado é IGNORADO** ✅
5. **Resultado:** Dados permanecem preenchidos

### ✅ **Cenário 2: Acesso direto com usuário já logado**
1. Usuário já logado acessa `/checkout` diretamente
2. Não há login recente do popup
3. Formulário está vazio
4. **Auto-preenchimento forçado é EXECUTADO** ✅
5. **Resultado:** Dados são preenchidos

### ✅ **Cenário 3: Login direto WooCommerce**
1. Usuário usa login nativo do WooCommerce no checkout
2. Sistema detecta via hooks
3. **Auto-preenchimento específico é EXECUTADO** ✅
4. **Resultado:** Dados são preenchidos

---

## 🔧 **ARQUIVOS MODIFICADOS**

### **Backend PHP** (`includes/class-llrp-ajax.php`)
- Adicionada marcação `$_SESSION['llrp_popup_login_timestamp']` em todos os métodos de login popup
- Proteção contra conflito no auto-preenchimento forçado

### **Frontend PHP** (`includes/class-llrp-frontend.php`)
- Função `force_checkout_autofill_if_logged_in()` melhorada com verificações de conflito
- Handler de checkout com verificação mais rigorosa de formulário vazio
- Delay aumentado para 3 segundos para evitar conflitos

### **Plugin Principal** (`lightweight-login-register-popup.php`)
- Versão atualizada para `0.5.6`

---

## 🎯 **RESULTADO FINAL**

### **✅ CONFLITO COMPLETAMENTE RESOLVIDO:**
- ✅ **Login via popup** → dados preenchidos e **MANTIDOS**
- ✅ **Login direto WooCommerce** → dados preenchidos
- ✅ **Usuário já logado** → auto-preenchimento quando necessário
- ✅ **Sem interferência** entre diferentes sistemas de preenchimento

### **🔍 Logs para Verificação:**
- `🔄 LLRP: Skipping force autofill - recent popup login detected` = Proteção ativa
- `🔄 LLRP CRITICAL: Forcing autofill for logged-in user on checkout (DIRECT ACCESS)` = Apenas para acesso direto

**RESOLVE:** O conflito crítico onde auto-preenchimento forçado removia dados já preenchidos pelo popup.
