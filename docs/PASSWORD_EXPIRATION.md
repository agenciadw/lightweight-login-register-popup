# Sistema de Expiração de Senha

## Visão Geral

O sistema de expiração de senha é uma funcionalidade de segurança que força os usuários a trocar suas senhas periodicamente ou após períodos de inatividade. Isso aumenta significativamente a segurança das contas de usuário no site.

## Funcionalidades

### 1. Expiração por Tempo
- Define um prazo em dias para que a senha expire
- Padrão: 90 dias
- Configurável entre 1 e 365 dias
- Avisos começam 7 dias antes da expiração

### 2. Expiração por Inatividade
- Força troca de senha quando o usuário não faz login há muito tempo
- Padrão: 30 dias sem login
- Configurável entre 1 e 365 dias
- Ideal para contas que ficam muito tempo sem uso

### 3. Avisos Progressivos
- **7 dias antes**: Aviso amarelo informando quantos dias faltam
- **Na expiração**: Modal bloqueando o acesso até a troca da senha
- **Dismissível**: Avisos podem ser dispensados temporariamente

### 4. Pontos de Verificação
O sistema verifica a expiração de senha em:
- Tela de login (popup)
- Página Minha Conta
- Checkout (antes de finalizar compra)
- Após qualquer tipo de login (senha, código, social)

## Configuração

### Acessar as Configurações

1. Acesse o WordPress Admin
2. Vá em **WooCommerce** → **Login Popup**
3. Clique na aba **Avançado**
4. Role até a seção **Expiração de Senha**

### Opções Disponíveis

#### Habilitar Expiração de Senha
- **Descrição**: Ativa o sistema de expiração por tempo
- **Padrão**: Desabilitado
- **Quando habilitar**: Quando você quer forçar trocas periódicas de senha

#### Prazo para Troca de Senha (dias)
- **Descrição**: Número de dias até a senha expirar
- **Padrão**: 90 dias
- **Recomendado**: 60-90 dias para alta segurança, 180 dias para segurança média
- **Mínimo**: 1 dia
- **Máximo**: 365 dias

#### Forçar Troca por Inatividade
- **Descrição**: Ativa a expiração por inatividade
- **Padrão**: Desabilitado
- **Quando habilitar**: Quando você quer proteger contas inativas

#### Dias de Inatividade para Forçar Troca
- **Descrição**: Dias sem login que forçam troca de senha
- **Padrão**: 30 dias
- **Recomendado**: 30 dias para alta segurança, 60-90 dias para segurança média
- **Mínimo**: 1 dia
- **Máximo**: 365 dias

## Comportamento do Sistema

### Para Usuários Existentes
Quando você ativa o sistema pela primeira vez:
1. A data atual é registrada como "última troca de senha" para todos os usuários
2. A contagem começa a partir dessa data
3. Usuários não serão forçados a trocar imediatamente

### Para Novos Usuários
- A data de registro é automaticamente definida como "última troca de senha"
- A contagem de dias começa do registro

### Processo de Troca Forçada

Quando a senha expira:

1. **Modal Bloqueador**: Um modal é exibido impedindo o acesso
2. **Campos Necessários**:
   - Senha Atual
   - Nova Senha (mínimo 8 caracteres)
   - Confirmar Nova Senha
3. **Validações**:
   - Senha atual deve estar correta
   - Nova senha deve ter pelo menos 8 caracteres
   - Confirmação deve coincidir
4. **Após Troca**:
   - Data de última troca é atualizada
   - Avisos são removidos
   - Usuário pode continuar normalmente

### Avisos na Tela de Login

No popup de login:
- ⚠️ Aviso amarelo se senha está próxima de expirar
- ⚠️ Aviso destacado se senha já expirou
- Usuário pode fazer login, mas será forçado a trocar depois

### Avisos na Minha Conta

Na página Minha Conta:
- 🔔 Banner azul informativo (7 dias antes)
- 🚨 Modal bloqueador (após expiração)
- Botão direto para editar conta

### Avisos no Checkout

No checkout:
- 🔔 Banner informativo (7 dias antes)  
- 🚨 Modal bloqueador (após expiração)
- Impede finalização até a troca

## Dados Armazenados

O sistema armazena as seguintes informações no user_meta:

### `_llrp_last_password_change`
- **Tipo**: timestamp (Unix timestamp)
- **Descrição**: Data da última vez que o usuário trocou a senha
- **Atualizado em**:
  - Criação de conta (registro)
  - Troca de senha (manual ou forçada)
  - Reset de senha (recuperação)

### `_llrp_last_login`
- **Tipo**: timestamp (Unix timestamp)
- **Descrição**: Data do último login do usuário
- **Atualizado em**: Qualquer tipo de login (senha, código, social)

### `_llrp_password_warning_dismissed`
- **Tipo**: timestamp (Unix timestamp)
- **Descrição**: Quando o usuário dispensou o último aviso
- **Removido em**: Troca de senha

## Segurança

### Proteção de Dados
- Senhas nunca são armazenadas em texto plano
- Usa funções nativas do WordPress (`wp_set_password`, `wp_check_password`)
- Nonces protegem todas as requisições AJAX

### Validações
- Senha mínima de 8 caracteres
- Verificação de senha atual antes de trocar
- Confirmação de senha obrigatória

### Compatibilidade
- ✅ Login com senha
- ✅ Login com código (e-mail/WhatsApp)
- ✅ Login social (Google/Facebook)
- ✅ Registro normal
- ✅ Registro com CPF/CNPJ
- ✅ Reset de senha padrão do WooCommerce

## Casos de Uso

### Caso 1: E-commerce B2C
**Configuração Recomendada:**
- Expiração por tempo: 90 dias
- Expiração por inatividade: 60 dias

**Motivo:** Equilíbrio entre segurança e experiência do usuário

### Caso 2: E-commerce B2B
**Configuração Recomendada:**
- Expiração por tempo: 60 dias
- Expiração por inatividade: 30 dias

**Motivo:** Maior segurança para contas empresariais

### Caso 3: Marketplace com Muitos Usuários
**Configuração Recomendada:**
- Expiração por tempo: 180 dias
- Expiração por inatividade: 90 dias

**Motivo:** Menos interrupções para base grande de usuários

### Caso 4: Site com Dados Sensíveis
**Configuração Recomendada:**
- Expiração por tempo: 30 dias
- Expiração por inatividade: 15 dias

**Motivo:** Máxima segurança para dados críticos

## Troubleshooting

### Usuários reclamam de muitas trocas
**Solução**: Aumente o prazo de expiração para 120-180 dias

### Muitas contas inativas sendo bloqueadas
**Solução**: Aumente o prazo de inatividade ou desabilite essa opção

### Usuários não veem os avisos
**Solução**: 
1. Verifique se o tema não está ocultando notificações do WooCommerce
2. Limpe o cache do site
3. Teste em navegação anônima

### Modal não aparece após expiração
**Solução**:
1. Verifique se há conflitos de JavaScript no console (F12)
2. Desabilite temporariamente outros plugins
3. Teste com tema padrão (Storefront)

## Desenvolvimento

### Hooks Disponíveis

```php
// Após verificação de status de senha
do_action( 'llrp_password_status_checked', $user_id, $status );

// Após troca de senha bem-sucedida
do_action( 'llrp_password_changed', $user_id, $old_timestamp, $new_timestamp );

// Antes de exibir modal de senha expirada
do_action( 'llrp_before_password_expiration_modal', $user_id, $status );
```

### Funções Úteis

```php
// Verificar status da senha de um usuário
$status = Llrp_Password_Expiration::check_password_status( $user_id );

// Retorna array com:
// - expired (bool): Se a senha expirou
// - warning (bool): Se há aviso
// - days_until_expiration (int|null): Dias até expirar
// - reason (string|null): 'time' ou 'inactivity'
```

## Changelog

### Versão 1.3.0 (Planejada)
- ✅ Implementado sistema de expiração por tempo
- ✅ Implementado sistema de expiração por inatividade
- ✅ Avisos progressivos (7 dias antes)
- ✅ Modal bloqueador na expiração
- ✅ Integração com todos os tipos de login
- ✅ Verificação em login, checkout e minha conta
- ✅ Interface de administração completa
- ✅ Documentação completa

## Suporte

Para dúvidas ou problemas:
1. Consulte esta documentação
2. Verifique os logs em `wp-content/debug.log` (se WP_DEBUG estiver ativo)
3. Entre em contato com o desenvolvedor

---

**Desenvolvido por**: David William da Costa  
**Versão**: 1.3.0  
**Última Atualização**: Janeiro 2026
