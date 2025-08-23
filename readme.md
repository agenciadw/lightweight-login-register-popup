=== Lightweight Login & Register Popup ===
Contributors: agenciadw
Tags: woocommerce, login, register, popup
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Popup personalizável na página do carrinho para permitir login, cadastro e recuperação de senha sem recarregar a página

== Descrição ==

Este plugin adiciona um popup de login e registro na página do carrinho do WooCommerce, permitindo que os clientes façam login, se registrem ou recuperem sua senha sem sair da página.

== Instalação ==

Faça o upload dos arquivos do plugin para o diretório /wp-content/plugins/lightweight-login-register-popup ou instale o plugin diretamente pela tela de plugins do WordPress.
Ative o plugin através da tela 'Plugins' no WordPress.
Use a tela WooCommerce > Popup de Login para configurar o plugin.

== Registro de Alterações ==

= 0.2.1 - 17/08/2025 =

- **NOVA FUNCIONALIDADE**: Adicionado botão "Copiar Código" quando o código de login é enviado via WhatsApp
- **NOVA FUNCIONALIDADE**: Cópia automática do código para a área de transferência do usuário
- **NOVA FUNCIONALIDADE**: Feedback visual quando o código é copiado com sucesso
- **NOVA FUNCIONALIDADE**: Suporte a navegadores modernos (API Clipboard) e antigos (document.execCommand)
- **MELHORIA**: Adicionadas opções de personalização para cores do botão de copiar no painel administrativo
- **MELHORIA**: Interface mais intuitiva para usuários que recebem códigos via WhatsApp

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
