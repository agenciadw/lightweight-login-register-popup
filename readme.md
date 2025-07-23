=== Lightweight Login & Register Popup ===

Contribuidores: David William da Costa  
Tags: login, cadastro, popup, WooCommerce  
Requer WordPress: 6.6 ou superior  
Testado até: WordPress 6.6  
Requer PHP: 7.4  
Versão estável: 0.1.1
Licença: GPLv2 ou superior  
Link da licença: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Este plugin exibe um popup personalizável na página do carrinho para permitir login, cadastro e recuperação de senha sem recarregar a página. Foi desenvolvido para carregar apenas os recursos necessários, mantendo a leveza e performance.

== Features ==

- Modal na página do carrinho: Aparece automaticamente para visitantes não autenticados.
- Suporte AJAX: Verificação de e-mail, login, cadastro e redefinição de senha sem recarga.
- Textos personalizáveis: Configure cabeçalhos, descrições, marcadores de formulário e rótulos de botões para todas as etapas.
- Opções de cores e estilo: Defina cores de fundo, sobreposição, cabeçalho, texto, links e botões pelo seletor de cores do WordPress.
- Largura do popup ajustável: Informe a largura desejada em pixels.
- Segurança: Proteção CSRF com nonces e sanitização de entradas.
- Logs de erro: Registra falhas nas requisições AJAX para facilitar a depuração.
- Carregamento seletivo: Scripts e estilos são incluídos apenas na página do carrinho.

== Installation ==

1. Faça upload da pasta `lightweight-login-register-popup` para `/wp-content/plugins/`.
2. Ative o plugin em Plugins > Plugins instalados.
3. Acesse WooCommerce > Login Popup para ajustar as configurações.
4. Personalize textos, cores e largura do popup conforme necessário.
5. Salve as alterações.

== Usage ==

1. Verifique se o WooCommerce está instalado e ativo.
2. Como usuário visitante, abra a página do carrinho para ver o popup.
3. Insira o e-mail para prosseguir com login, cadastro ou recuperação de senha.
4. Ajuste o fluxo e a aparência em WooCommerce > Login Popup.

== Changelog ==

= 0.1.1 =

- Inclusão de Screenshots de configuração e checkout
- Alguns ajustes no CSS
- Correção de bugs

= 0.1.0 =

- Lançamento inicial com suporte a login, cadastro e redefinição de senha via popup.
- Fluxo AJAX para melhor experiência do usuário.
- Página de configurações no admin para personalização de textos e estilos.

== Frequently Asked Questions ==

Como altero a largura do popup?  
No menu WooCommerce > Login Popup, atualize o campo Largura do Popup.

Posso modificar os rótulos e marcadores dos formulários?  
Sim, todas as etapas têm campos de texto configuráveis nas opções do plugin.

Este plugin carrega scripts em outras páginas?  
Não, todos os recursos são incluídos somente na página do carrinho para visitantes.

Onde obtenho suporte?  
Abra uma issue no repositório GitHub ou entre em contato com o autor.

== Screenshots ==

1. **Página de configurações exibindo opções de texto e cores.**  
   ![Configurações](/assets/screenshot/screenshot-05.jpeg)

2. **Popup de login na página do carrinho "CADASTRO / LOGIN"**  
   ![Checkout](/assets/screenshot/screenshot-01.jpeg)

3. **Popup de login na página do carrinho "CASO TENHA CONTA"**  
   ![Checkout](/assets/screenshot/screenshot-02.jpeg)

4. **Popup de login na página do carrinho "RECUPERAÇÃO DE SENHA"**  
   ![Checkout](/assets/screenshot/screenshot-03.jpeg)

5. **Popup de login na página do carrinho "VERIFICA SE TEM CONTA, CASO CONTRÁRIO CRIAR UMA NOVA CONTA"**  
   ![Checkout](/assets/screenshot/screenshot-04.jpeg)
