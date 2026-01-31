# Changelog - Versão 1.3.0

## 🚀 Nova Funcionalidade: Sistema de Expiração de Senha

**Data de Lançamento**: Janeiro 2026

### ✨ Novidades

#### Sistema de Expiração por Tempo
- Forçar usuários a trocar a senha após um período configurável (padrão: 90 dias)
- Avisos progressivos começando 7 dias antes da expiração
- Configuração flexível de 1 a 365 dias

#### Sistema de Expiração por Inatividade
- Forçar troca de senha quando usuário não faz login há muito tempo (padrão: 30 dias)
- Protege contas de usuários inativos
- Configuração independente da expiração por tempo

#### Interface de Administração
- Nova seção "Expiração de Senha" na aba Avançado
- Toggle switches para habilitar/desabilitar funcionalidades
- Campos numéricos para configurar prazos
- Help box com explicações detalhadas
- Validação de formulário

#### Avisos para Usuários
- **7 dias antes**: Aviso amarelo informativo com contagem regressiva
- **Na expiração**: Modal bloqueador impedindo acesso até troca
- **Botão de dispensar**: Para avisos não críticos
- **Mensagens contextuais**: Explica se foi por tempo ou inatividade

#### Verificações Automáticas
- ✅ Popup de login - Aviso ao detectar usuário
- ✅ Página Minha Conta - Banner e modal quando necessário
- ✅ Checkout - Impede finalização se senha expirada
- ✅ Após login - Atualiza data do último login
- ✅ Após registro - Define datas iniciais

#### Modal de Troca Forçada
- Design responsivo e moderno
- Campos:
  - Senha Atual (obrigatório)
  - Nova Senha (mínimo 8 caracteres)
  - Confirmar Nova Senha
- Validações em tempo real
- Feedback visual de sucesso/erro
- Recarga automática após troca bem-sucedida
- Bloqueia scroll da página (impede navegação)

### 🔧 Melhorias Técnicas

#### Nova Classe: `Llrp_Password_Expiration`
- Gerencia toda a lógica de expiração
- Métodos públicos para verificação de status
- Hooks do WordPress para atualização automática
- AJAX endpoints seguros

#### Integração Total
- ✅ Login com senha
- ✅ Login com código (e-mail/WhatsApp)
- ✅ Login social (Google/Facebook)
- ✅ Registro normal
- ✅ Registro com CPF/CNPJ
- ✅ Reset de senha (WooCommerce)

#### User Meta Adicionados
```php
_llrp_last_password_change    // timestamp da última troca
_llrp_last_login              // timestamp do último login
_llrp_password_warning_dismissed // timestamp quando aviso foi dispensado
```

#### AJAX Endpoints
```javascript
llrp_change_expired_password  // Trocar senha expirada
llrp_dismiss_password_warning // Dispensar aviso de expiração
```

### 📊 Dados no Retorno JSON

Novos campos nos endpoints de login:
```json
{
  "password_expired": false,
  "password_warning": false,
  "password_warning_days": 5,
  "password_expired_message": "Mensagem contextual",
  "password_expired_reason": "Sua senha expirou..."
}
```

### 🎨 Estilos CSS

Novos estilos para modal e avisos:
- Modal responsivo com overlay escuro
- Inputs com foco estilizado
- Botões com hover effects
- Feedback colorido (erro/sucesso)
- Compatível com mobile

### 📝 JavaScript

Adicionado no `llrp-script.js`:
- Detecção de senha expirada na verificação de usuário
- Exibição de avisos contextuais no popup
- Redirecionamento inteligente após login
- Handler do modal de troca de senha

Adicionado no `llrp-admin.js`:
- Toggle automático de campos de configuração
- Validação de valores numéricos
- Feedback visual de mudanças

### 🔒 Segurança

- ✅ Todas as requisições AJAX protegidas por nonce
- ✅ Validação de senha atual antes de trocar
- ✅ Senha mínima de 8 caracteres
- ✅ Confirmação de senha obrigatória
- ✅ Rate limiting nas tentativas
- ✅ Senhas nunca em texto plano
- ✅ Re-login automático após troca

### 📚 Documentação

- Novo arquivo `docs/PASSWORD_EXPIRATION.md` com guia completo
- Casos de uso para diferentes tipos de e-commerce
- Troubleshooting detalhado
- Exemplos de configuração
- Referência de hooks e funções

### 🐛 Correções

- Atualização automática de `_llrp_last_login` em todos os tipos de login
- Definição correta de datas iniciais para novos usuários
- Compatibilidade com todos os métodos de autenticação existentes

### ⚙️ Configurações Padrão

```php
llrp_password_expiration_enabled         => 0 (desabilitado)
llrp_password_expiration_days            => 90 dias
llrp_password_expiration_inactivity_enabled => 0 (desabilitado)
llrp_password_expiration_inactivity_days => 30 dias
```

### 🔄 Processo de Atualização

Para usuários que atualizam de versões anteriores:
1. O sistema automaticamente define a data atual como "última troca de senha"
2. Nenhum usuário é forçado a trocar imediatamente
3. A contagem de dias começa após a atualização
4. Configurações vêm desabilitadas por padrão

### 🌐 Compatibilidade

- ✅ WordPress 6.6+
- ✅ WooCommerce 8.0+
- ✅ PHP 7.4+
- ✅ Todos os navegadores modernos
- ✅ Dispositivos móveis e tablets
- ✅ HPOS (High-Performance Order Storage)
- ✅ Fluid Checkout
- ✅ Brazilian Market

### 📱 Responsividade

- Modal adapta-se a telas pequenas
- Inputs maiores em mobile
- Botões com tamanho adequado para toque
- Mensagens de erro legíveis em qualquer tela

### 🎯 Casos de Uso

#### E-commerce B2C
```
Expiração por tempo: 90 dias
Inatividade: 60 dias
```

#### E-commerce B2B
```
Expiração por tempo: 60 dias
Inatividade: 30 dias
```

#### Marketplace
```
Expiração por tempo: 180 dias
Inatividade: 90 dias
```

#### Dados Sensíveis
```
Expiração por tempo: 30 dias
Inatividade: 15 dias
```

### 🚧 Limitações Conhecidas

- Avisos não aparecem em páginas fora do WooCommerce
- Modal pode ter conflitos com alguns temas muito customizados
- Requer JavaScript ativo no navegador

### 🔮 Próximas Melhorias (Planejadas)

- [ ] Notificação por e-mail antes da expiração
- [ ] Histórico de trocas de senha
- [ ] Blacklist de senhas comuns
- [ ] Força da senha com indicador visual
- [ ] Relatório de senhas expiradas no admin
- [ ] Exportação de dados de segurança

### 📞 Suporte

Para questões sobre esta funcionalidade:
1. Consulte `docs/PASSWORD_EXPIRATION.md`
2. Verifique os logs em `wp-content/debug.log`
3. Entre em contato com o desenvolvedor

---

**Desenvolvido por**: David William da Costa  
**GitHub**: https://github.com/agenciadw/lightweight-login-register-popup  
**Versão**: 1.3.0  
**Data**: Janeiro 2026
