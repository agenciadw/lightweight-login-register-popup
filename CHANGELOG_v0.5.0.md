# Changelog v0.5.0

## 🛒 Correções Críticas do E-commerce

### ✅ 1. Persistência do Carrinho (PROBLEMA CRÍTICO RESOLVIDO)
- **Problema:** Produtos adicionados ao carrinho desapareciam após o usuário fazer login
- **Solução implementada:**
  - Backup automático do carrinho no `localStorage` antes do login
  - Restauração dos itens após autenticação bem-sucedida
  - Mesclagem inteligente do carrinho local com carrinho do usuário
  - Persistência mantida durante todo o fluxo de checkout

**Funções JavaScript adicionadas:**
- `saveCartToLocalStorage()` - Salva carrinho antes do login
- `restoreCartFromLocalStorage()` - Restaura carrinho após login
- `mergeLocalCartWithUserCart()` - Mescla carrinhos local e do usuário

### ✅ 2. Remoção Condicional do Botão X
- **Problema:** Botão X aparecia em todas as situações, inclusive no checkout
- **Solução implementada:**
  - Detecção automática da página de checkout/finalizar-compra
  - Ocultação do botão X apenas nessas páginas críticas
  - Manutenção do botão X na página do carrinho regular

**Função JavaScript adicionada:**
- `hideCloseButtonIfCheckout()` - Controla visibilidade do botão X

### ✅ 3. Auto-preenchimento Completo de Dados do Usuário
- **Problema:** Dados do usuário não eram transferidos para o formulário após login/cadastro
- **Solução implementada:**
  - **Para usuários existentes:** Carregamento completo de todos os dados salvos
  - **Para novos usuários:** Transferência automática do email usado no cadastro
  - Auto-preenchimento de todos os campos disponíveis no checkout
  - Sincronização entre dados de autenticação e formulários

**Campos auto-preenchidos:**
- Email, nome, telefone
- Endereço completo (logradouro, número, bairro, cidade, estado, CEP)
- CPF/CNPJ quando disponível
- Dados de entrega e cobrança

**Funções adicionadas:**
- `fillCheckoutFormData()` - Preenche formulário de checkout
- `get_user_checkout_data()` - Coleta dados do usuário (PHP)

## 🔧 Melhorias Técnicas

### JavaScript (llrp-script.js)
- Novo sistema de backup/restauração de carrinho
- Detecção inteligente de página de checkout
- Auto-preenchimento de formulários com dados completos
- Integração aprimorada com Fluid Checkout

### PHP (class-llrp-ajax.php)
- Resposta AJAX enriquecida com dados do usuário
- Função `get_user_checkout_data()` para coleta completa de dados
- Compatibilidade aprimorada com fragmentos de carrinho
- Melhor tratamento de dados de usuário

## 🎯 Resultados Esperados

### ✅ Critérios de Sucesso Atendidos:
1. **Carrinho mantém itens após login** em qualquer página
2. **Botão X não aparece** na página de checkout/finalizar-compra
3. **Todos os dados do usuário são automaticamente preenchidos** no formulário após login
4. **Email é preenchido automaticamente** após novo cadastro na página de checkout
5. **Fluxo de checkout funciona** sem perda de dados em nenhuma etapa

## 🧪 Testes Recomendados

1. **Teste de Persistência do Carrinho:**
   - Adicionar produto → Fazer login → Verificar se produto permanece

2. **Teste do Botão X:**
   - Acessar `/finalizar-compra` diretamente → Verificar ausência do botão X

3. **Teste de Auto-preenchimento:**
   - Fazer login na página de checkout → Verificar auto-preenchimento de TODOS os dados salvos
   - Criar conta nova na página de checkout → Verificar se email é transferido

4. **Teste de Fluxo Completo:**
   - Produto → Carrinho → Login → Checkout → Finalização

## 🔄 Compatibilidade

- ✅ WooCommerce padrão
- ✅ Fluid Checkout
- ✅ Plugins de CPF/CNPJ
- ✅ Login social (Google/Facebook)

---

**Data:** 19 de setembro de 2025  
**Autor:** David William da Costa  
**Versão:** 0.5.0
