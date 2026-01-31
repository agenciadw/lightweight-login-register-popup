# Forçar Troca de Senha para Usuários Importados

## Visão Geral

Esta funcionalidade permite forçar todos os usuários que foram importados de outras plataformas a trocar suas senhas no primeiro login. Isso aumenta significativamente a segurança, especialmente quando você migrou dados de outro sistema.

## Como Funciona

O sistema identifica usuários importados através da **ausência** do campo `_llrp_last_password_change` no user_meta. Quando esse campo não existe e a opção está habilitada, o usuário é forçado a criar uma nova senha ao fazer login.

## Passo a Passo

### 1. Habilitar a Funcionalidade

1. Acesse o WordPress Admin
2. Vá em **WooCommerce** → **Login Popup**
3. Clique na aba **Avançado**
4. Role até a seção **Expiração de Senha**
5. Ative o toggle **"Forçar Troca para Usuários Importados"**
6. Clique em **Salvar Configurações**

### 2. O Que Acontece

Quando a funcionalidade está ativa:

- **Usuários importados** (sem `_llrp_last_password_change`): São forçados a trocar a senha
- **Usuários novos** (criados após ativar o plugin): Não são afetados
- **Usuários antigos que já trocaram**: Não são afetados

### 3. Experiência do Usuário

Quando um usuário importado tenta fazer login:

#### No Popup de Login
1. Usuário digita e-mail/telefone/CPF
2. Sistema detecta que é usuário importado
3. Exibe aviso: **"Por segurança, você precisa criar uma nova senha antes de continuar."**
4. Usuário digita a senha atual
5. Após login bem-sucedido, é redirecionado para página de troca de senha

#### Na Minha Conta / Checkout
1. Usuário faz login
2. Modal aparece impedindo acesso
3. Campos obrigatórios:
   - Senha Atual
   - Nova Senha (mínimo 8 caracteres)
   - Confirmar Nova Senha
4. Após trocar, acesso é liberado

### 4. Mensagens Exibidas

**Tela de Verificação de Usuário:**
> Por segurança, você precisa criar uma nova senha antes de continuar.

**Após Login (popup):**
> Por segurança, você precisa criar uma nova senha. Você será redirecionado.

**Modal Bloqueador:**
> Para garantir a segurança da sua conta, você precisa criar uma nova senha antes de continuar usando o site.

## Cenários de Uso

### Cenário 1: Migração Completa de Plataforma

**Situação:** Você migrou 10.000 clientes do Magento para WooCommerce

**Ação:**
1. Importe todos os usuários
2. Ative "Forçar Troca para Usuários Importados"
3. Todos os usuários serão forçados a trocar no primeiro login

**Resultado:** Segurança máxima após migração

---

### Cenário 2: Importação Parcial

**Situação:** Você tem usuários antigos no WooCommerce + novos usuários importados

**Ação:**
1. Importe os novos usuários
2. Ative "Forçar Troca para Usuários Importados"
3. Apenas os importados (sem meta) serão afetados

**Resultado:** Usuários antigos não são incomodados

---

### Cenário 3: Vazamento de Dados em Outra Plataforma

**Situação:** Sua plataforma anterior teve um vazamento de senhas

**Ação:**
1. Importe os usuários
2. Ative "Forçar Troca para Usuários Importados"
3. Envie e-mail explicando o motivo

**Resultado:** Todas as contas protegidas com novas senhas

---

### Cenário 4: Conformidade com LGPD/GDPR

**Situação:** Você precisa garantir que senhas antigas sejam atualizadas

**Ação:**
1. Ative a funcionalidade
2. Documente na sua política de privacidade
3. Usuários trocam senhas gradualmente

**Resultado:** Conformidade regulatória

## Como Remover Usuários da Lista de "Importados"

Se você importou usuários mas alguns já trocaram a senha manualmente ou não devem ser forçados, você pode adicionar o campo manualmente:

### Opção 1: Via PHP (WP-CLI ou functions.php)

```php
// Marcar um usuário específico como tendo senha válida
$user_id = 123; // ID do usuário
update_user_meta( $user_id, '_llrp_last_password_change', time() );
```

### Opção 2: Via SQL (phpMyAdmin)

```sql
-- Marcar UM usuário como tendo senha válida
INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
VALUES (123, '_llrp_last_password_change', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE meta_value = UNIX_TIMESTAMP();

-- Marcar TODOS os usuários como tendo senha válida (CUIDADO!)
INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
SELECT ID, '_llrp_last_password_change', UNIX_TIMESTAMP()
FROM wp_users
ON DUPLICATE KEY UPDATE meta_value = UNIX_TIMESTAMP();
```

### Opção 3: Via Plugin (Mass User Meta Update)

1. Instale um plugin de atualização em massa de user_meta
2. Defina:
   - Meta Key: `_llrp_last_password_change`
   - Meta Value: Timestamp atual
3. Execute para os usuários desejados

## Identificar Usuários Importados

Para saber quantos usuários serão afetados:

### Via SQL (phpMyAdmin)

```sql
-- Contar usuários SEM data de última troca
SELECT COUNT(*) as usuarios_importados
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
WHERE um.meta_value IS NULL;

-- Listar usuários SEM data de última troca
SELECT u.ID, u.user_login, u.user_email
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
WHERE um.meta_value IS NULL;
```

### Via PHP (WP-CLI ou Code Snippet)

```php
// Contar usuários importados
$all_users = get_users();
$imported_count = 0;

foreach ( $all_users as $user ) {
    $last_change = get_user_meta( $user->ID, '_llrp_last_password_change', true );
    if ( ! $last_change ) {
        $imported_count++;
    }
}

echo "Usuários importados: {$imported_count}";
```

## Comunicação com os Usuários

### E-mail Recomendado

**Assunto:** Atualização de Segurança - Nova Senha Necessária

**Corpo:**
```
Olá [Nome],

Por medida de segurança, atualizamos nosso sistema e precisamos que você crie uma nova senha.

No seu próximo login, você será solicitado a:
1. Entrar com sua senha atual
2. Criar uma nova senha (mínimo 8 caracteres)
3. Confirmar a nova senha

Esta é uma medida de proteção para garantir a segurança da sua conta.

Se você não lembra sua senha atual, use a opção "Esqueci minha senha" na tela de login.

Obrigado pela compreensão!

Equipe [Sua Empresa]
```

### Banner no Site (Opcional)

```html
<div style="background: #fff3cd; padding: 15px; text-align: center; border-bottom: 1px solid #ffc107;">
    <strong>⚠️ Importante:</strong> Atualizamos nosso sistema de segurança. 
    Você precisará criar uma nova senha no próximo login.
</div>
```

## Desativar a Funcionalidade

Para desativar depois que todos os usuários trocaram:

1. Acesse **WooCommerce** → **Login Popup** → **Avançado**
2. Desative o toggle **"Forçar Troca para Usuários Importados"**
3. Salve as configurações

**Nota:** Isso não afeta usuários que já trocaram a senha.

## Combinação com Outras Funcionalidades

Esta funcionalidade funciona em conjunto com:

### Expiração por Tempo
- ✅ Usuários importados trocam primeiro
- ✅ Depois, seguem o ciclo normal de expiração

### Expiração por Inatividade
- ✅ Usuários importados trocam primeiro
- ✅ Se ficarem inativos depois, trocam novamente

### Exemplo de Configuração Ideal

```
✅ Forçar Troca para Usuários Importados: ATIVO
✅ Expiração por Tempo: 90 dias
✅ Expiração por Inatividade: 60 dias
```

**Resultado:**
1. Usuário importado faz login → Troca senha imediatamente
2. Após 90 dias → Sistema pede nova troca
3. Se ficar 60 dias inativo → Sistema pede nova troca

## Troubleshooting

### Problema: Todos os usuários estão sendo forçados a trocar

**Causa:** Nenhum usuário tem `_llrp_last_password_change`

**Solução:** Execute o SQL de massa para adicionar o campo aos usuários antigos:

```sql
INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
SELECT ID, '_llrp_last_password_change', UNIX_TIMESTAMP()
FROM wp_users
WHERE user_registered < '2026-01-01'  -- Data antes da migração
ON DUPLICATE KEY UPDATE meta_value = UNIX_TIMESTAMP();
```

---

### Problema: Usuários não conseguem trocar a senha

**Causa:** Senha atual incorreta ou nova senha muito fraca

**Solução:** Use "Esqueci minha senha" para resetar

---

### Problema: Usuário troca senha mas continua sendo solicitado

**Causa:** Meta não foi salva corretamente

**Solução:** Limpe o cache e tente novamente, ou adicione manualmente via SQL

---

### Problema: Como saber se funcionou?

**Verificação:** Após o usuário trocar, confira no banco:

```sql
SELECT user_id, meta_value, FROM_UNIXTIME(meta_value) as data_troca
FROM wp_usermeta
WHERE meta_key = '_llrp_last_password_change'
AND user_id = 123;  -- ID do usuário
```

## Segurança e Boas Práticas

### ✅ Faça

- ✅ Avise os usuários por e-mail antes de ativar
- ✅ Ofereça suporte extra durante a transição
- ✅ Teste com poucos usuários primeiro
- ✅ Mantenha a funcionalidade ativa por 30-60 dias
- ✅ Monitore logins após ativar

### ❌ Não Faça

- ❌ Não ative sem avisar os usuários
- ❌ Não force troca imediata de todos ao mesmo tempo
- ❌ Não desative antes que todos tenham trocado
- ❌ Não use senhas temporárias muito simples na importação
- ❌ Não deixe ativo permanentemente sem necessidade

## Relatórios

### Quantos usuários trocaram?

```sql
SELECT COUNT(*) as usuarios_que_trocaram
FROM wp_usermeta
WHERE meta_key = '_llrp_last_password_change'
AND meta_value > UNIX_TIMESTAMP('2026-01-30');  -- Data de ativação
```

### Quem ainda não trocou?

```sql
SELECT u.ID, u.user_login, u.user_email, um_login.meta_value as ultimo_login
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
LEFT JOIN wp_usermeta um_login ON u.ID = um_login.user_id AND um_login.meta_key = '_llrp_last_login'
WHERE um.meta_value IS NULL
ORDER BY um_login.meta_value DESC;
```

## FAQ

**P: E se o usuário esquecer a senha atual?**
R: Use a função "Esqueci minha senha" do WooCommerce. Após resetar, o campo `_llrp_last_password_change` será automaticamente definido.

**P: Administradores também são forçados?**
R: Sim, a menos que tenham o campo `_llrp_last_password_change`. Para excluir admins, adicione o campo manualmente.

**P: Funciona com login social?**
R: Sim, mas usuários de login social geralmente não têm senha. Eles não serão afetados.

**P: Quanto tempo a funcionalidade deve ficar ativa?**
R: Recomendamos 30-60 dias após a importação, para dar tempo de todos os usuários fazerem login.

**P: Posso forçar apenas alguns usuários?**
R: Sim, remova o campo `_llrp_last_password_change` apenas dos usuários que deseja forçar.

**P: Como exportar lista de usuários que não trocaram?**
R: Use a query SQL de relatório e exporte para CSV pelo phpMyAdmin.

---

**Desenvolvido por**: David William da Costa  
**Versão**: 1.3.0  
**Última Atualização**: Janeiro 2026
