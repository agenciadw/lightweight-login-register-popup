# Integração de Captcha - LLRP

## Visão Geral

O plugin Lightweight Login & Register Popup agora possui suporte completo para proteção contra bots através de:

- **Cloudflare Turnstile**
- **Google reCAPTCHA v2** (Checkbox e Invisível)
- **Google reCAPTCHA v3** (Transparente)

## Configuração

### Painel Administrativo

1. Acesse **WooCommerce > Login Popup**
2. Role até a seção **Configurações de Captcha**
3. Configure as opções:

#### Opções Disponíveis

- **Tipo de Captcha**: Selecione o tipo desejado
  - Nenhum (desabilitado)
  - Cloudflare Turnstile
  - reCAPTCHA v2 (Checkbox) - exige clique do usuário
  - reCAPTCHA v2 (Invisível) - validação transparente
  - reCAPTCHA v3 - totalmente transparente com score

### Cloudflare Turnstile

**Como obter as chaves:**
1. Acesse https://dash.cloudflare.com/?to=/:account/turnstile
2. Crie um novo site
3. Copie a **Site Key** e **Secret Key**
4. Cole no painel do plugin

**Vantagens:**
- Gratuito e ilimitado
- Proteção moderna e eficiente
- Boa experiência do usuário

### Google reCAPTCHA

**Como obter as chaves:**
1. Acesse https://www.google.com/recaptcha/admin
2. Registre um novo site
3. Escolha o tipo (v2 ou v3)
4. Copie a **Site Key** e **Secret Key**
5. Cole no painel do plugin

#### reCAPTCHA v2 Checkbox
- Exige que o usuário clique em "Não sou um robô"
- Pode apresentar desafios adicionais
- Proteção robusta

#### reCAPTCHA v2 Invisível
- Validação automática em segundo plano
- Pode apresentar desafio se detectar comportamento suspeito
- Melhor experiência do usuário

#### reCAPTCHA v3
- Totalmente transparente
- Usa score de 0.0 a 1.0 para avaliar interações
- Configure o **Score Mínimo** (recomendado: 0.5)
- Score mais alto = mais rigoroso

## Funcionamento

### Frontend

O captcha é exibido nos seguintes passos do popup:

1. **Step de E-mail**: Primeira verificação ao inserir e-mail/CPF/CNPJ
2. **Step de Login**: Verificação ao fazer login com senha
3. **Step de Registro**: Verificação ao criar nova conta

### Backend

Todas as requisições AJAX são validadas no servidor:
- `ajax_check_user()` - verifica usuário
- `ajax_login_with_password()` - login com senha
- `ajax_register()` - registro de novo usuário

### Cache

As configurações de captcha são incluídas no sistema de cache do plugin, reduzindo queries ao banco de dados.

## Segurança

### Validação Dupla

1. **Cliente**: JavaScript valida e obtém token
2. **Servidor**: PHP valida o token com a API do provedor

### Proteção contra Replay

- Tokens são de uso único
- Timeout automático
- Validação de IP

### Mensagens de Erro

As mensagens são amigáveis e não expõem detalhes técnicos:
- "Por favor, complete a verificação de segurança."
- "Verificação de segurança falhou. Tente novamente."

## Casos de Uso

### E-commerce com Alto Tráfego
- **Recomendado**: Turnstile ou reCAPTCHA v3
- Proteção transparente sem atrapalhar conversão

### Site com Problemas de Spam
- **Recomendado**: reCAPTCHA v2 Checkbox
- Proteção mais rigorosa

### Site Internacional
- **Recomendado**: Turnstile
- Funciona melhor em todos os países

## Troubleshooting

### Captcha não aparece

1. Verifique se as chaves estão corretas
2. Confirme que o domínio está autorizado na configuração do provedor
3. Verifique o console do navegador para erros JavaScript

### Validação sempre falha

1. Confirme que a **Secret Key** está correta
2. Verifique se o servidor consegue acessar a API do provedor
3. Para reCAPTCHA v3, ajuste o score mínimo

### Conflito com outros plugins

O LLRP é compatível com:
- WooCommerce
- Fluid Checkout
- Brazilian Market
- Outros captchas em formulários diferentes

## API JavaScript

### Funções Disponíveis

```javascript
// Inicializa captcha em um container
initCaptcha(containerId)

// Obtém token do captcha (retorna Promise)
getCaptchaToken(step)

// Reseta o captcha para nova tentativa
resetCaptcha(step)
```

### Exemplo de Uso

```javascript
getCaptchaToken('email').then(function(token) {
  // Enviar token para o servidor
  $.post(ajaxurl, {
    action: 'minha_acao',
    captcha_token: token
  });
}).catch(function(error) {
  alert(error.message);
});
```

## Suporte

Para questões sobre:
- **Cloudflare Turnstile**: https://developers.cloudflare.com/turnstile/
- **Google reCAPTCHA**: https://developers.google.com/recaptcha/

## Changelog

### v1.1.2
- ✅ Adicionado suporte completo para Cloudflare Turnstile
- ✅ Adicionado suporte para Google reCAPTCHA v2 (Checkbox e Invisível)
- ✅ Adicionado suporte para Google reCAPTCHA v3
- ✅ Validação backend em todos os endpoints AJAX
- ✅ Interface de configuração no painel admin
- ✅ Integração automática com sistema de cache
- ✅ Reset automático em caso de erro

## Licença

Este recurso está incluído no plugin sob licença GPL v2 ou posterior.

