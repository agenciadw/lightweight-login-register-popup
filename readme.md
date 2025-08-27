=== Lightweight Login & Register Popup ===
Contributors: agenciadw
Tags: woocommerce, login, register, popup
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Popup personalizável e integração com login social para WooCommerce - funciona no carrinho e página "Minha Conta"

== Descrição ==

Este plugin adiciona um popup de login e registro na página do carrinho do WooCommerce e integra botões de login social (Google/Facebook) nos formulários da página "Minha Conta", permitindo que os clientes façam login, se registrem ou recuperem sua senha sem sair da página.

**Funcionalidades principais:**

- Popup de login/registro no carrinho
- Login social (Google/Facebook) na página "Minha Conta"
- Redirecionamento inteligente baseado no contexto
- Interface totalmente personalizável
- Suporte a CPF/CNPJ para login
- Integração nativa com WooCommerce

== Instalação ==

Faça o upload dos arquivos do plugin para o diretório /wp-content/plugins/lightweight-login-register-popup ou instale o plugin diretamente pela tela de plugins do WordPress.
Ative o plugin através da tela 'Plugins' no WordPress.
Use a tela WooCommerce > Popup de Login para configurar o plugin.

== Registro de Alterações ==

= 0.4.2 - Hoje =

- **MELHORIA WHATSAPP**: Códigos de login agora são enviados em **duas mensagens separadas** para facilitar a cópia
- **PRIMEIRA MENSAGEM**: "🔐 Código de Login - Segue seu código para efetuar login em [Nome da Loja], seu código é válido por 5 minutos"
- **SEGUNDA MENSAGEM**: Apenas o código (ex: "123456") para facilitar a seleção e cópia
- **MELHORIA UX**: Delay de 0.5 segundo entre mensagens para garantir ordem correta no WhatsApp
- **COMPATIBILIDADE**: Funciona com todas as modalidades (botão copiar, botão interativo, mensagem simples)

= 0.4.1 =

- **MELHORIA**: Padronização visual dos botões sociais - Botão do Facebook agora usa borda azul e texto azul (ao invés de preenchimento), mantendo consistência com o botão do Google
- **MELHORIA**: Estilos específicos para página "Minha Conta" garantem consistência visual em todos os contextos

= 0.4.0 =

- **NOVA FUNCIONALIDADE**: Extensão para página "Minha Conta" - Login social agora disponível nos formulários de login e registro da página "Minha Conta"
- **MELHORIA**: Redirecionamento inteligente - Login na página "Minha Conta" redireciona para dashboard, login no carrinho redireciona para checkout
- **MELHORIA**: Sistema de feedback adaptativo - Mensagens de erro/sucesso adaptam-se ao contexto (popup vs página)
- **MELHORIA**: Estilos CSS específicos para integração com formulários do WooCommerce
- **CORREÇÃO**: Corrigido problema de nonce em logins sociais usando função wp_create_user ao invés de wc_create_new_customer
- **CORREÇÃO**: Redirecionamento baseado no contexto de origem da requisição

= 0.3.0 =

- **NOVA FUNCIONALIDADE**: Login com Google - Permite login usando conta Google
- **NOVA FUNCIONALIDADE**: Login com Facebook - Permite login usando conta Facebook
- **NOVA FUNCIONALIDADE**: Configurações de login social no painel administrativo
- **NOVA FUNCIONALIDADE**: Integração completa com Google Sign-In API e Facebook SDK
- **NOVA FUNCIONALIDADE**: Criação automática de usuários via login social
- **NOVA FUNCIONALIDADE**: Botões de login social com design moderno e responsivo
- **MELHORIA**: Interface mais intuitiva com separadores visuais entre opções de login
- **MELHORIA**: Feedback visual aprimorado para erros e sucessos
- **MELHORIA**: Estilos CSS otimizados para melhor experiência do usuário
- **MELHORIA**: Suporte completo para dispositivos móveis nos botões sociais

= 0.2.1 - 17/08/2025 =

- **NOVA FUNCIONALIDADE**: Suporte a botões interativos do WhatsApp (botão "Copiar código")
- **NOVA FUNCIONALIDADE**: Mensagem do WhatsApp aprimorada com formatação especial
- **NOVA FUNCIONALIDADE**: Opção no painel administrativo para ativar/desativar botões interativos
- **MELHORIA**: Integração com API de botões do WhatsApp Business
- **MELHORIA**: Fallback automático para mensagem normal se botões não estiverem disponíveis
- **MELHORIA**: Mensagem mais clara e organizada com emojis e formatação
- **MELHORIA**: Informação sobre validade do código (5 minutos) na mensagem

= 0.2.0 - 17/08/2025 =

- Funcionalidade: Adicionada funcionalidade "Login com Código" via e-mail.
- Funcionalidade: Adicionada integração com WhatsApp para envio de códigos de login usando o plugin Joinotify.
- Funcionalidade: Adicionado login com CPF e CNPJ.
- Funcionalidade: Adicionadas opções no painel administrativo para ativar/desativar login com CPF e CNPJ.
- Funcionalidade: Adicionada validação no backend para números de CPF e CNPJ.
- Melhoria: Aumentada a segurança com sanitização de todas as entradas e adição de callbacks de sanitização explícitos para configurações.
- Melhoria: Adicionado verificador de força de senha ao formulário de registro.
- Melhoria: O fluxo de "Esqueceu a Senha" agora preenche automaticamente o e-mail do usuário.
- Correção: Corrigido um bug que causava falha no botão "Enviar Código" com erro 400.
- Correção: Corrigido um bug que tornava o botão de fechar invisível.
- Correção: Corrigido um bug que impedia a tecla "Enter" de enviar formulários.
- Correção: Corrigido um bug que fazia o botão "Enviar Código" não usar as cores corretas do painel administrativo.
- Correção: Corrigido um bug que causava duplicação do e-mail do usuário na etapa "Esqueceu a Senha".

= 0.1.0 =

Lançamento inicial.
