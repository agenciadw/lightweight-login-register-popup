(function ($) {
  "use strict";

  $(function () {
    // --- DOM Elements ---
    var $overlay = $("#llrp-overlay");
    var $popup = $("#llrp-popup");

    // --- State Variables ---
    var state = {
      email: "",
      phone: "",
      userExists: false,
      hasPhone: false,
      avatar: "",
      username: "",
    };

    // --- Utility Functions ---
    function isValidEmail(email) {
      var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    }

    function showStep(step) {
      $popup.find(".llrp-step").addClass("hidden");
      $popup.find(".llrp-step-" + step).removeClass("hidden");
      var $inp = $popup.find(".llrp-step-" + step + " input:visible").first();
      if ($inp.length) {
        setTimeout(function () { $inp.focus(); }, 50);
      }
    }

    function clearFeedback() {
      $popup.find(".llrp-feedback").text("").removeClass("success error");
    }

    function showFeedback(selector, message, isSuccess) {
      var $feedback = $popup.find("." + selector);
      $feedback.text(message);
      if (isSuccess) {
        $feedback.removeClass("error").addClass("success");
      } else {
        $feedback.removeClass("success").addClass("error");
      }
    }

    function resetState() {
        state = { email: "", phone: "", userExists: false, hasPhone: false, avatar: "", username: "" };
        $popup.find("input").val("");
        $('#llrp-options-container').empty();
        clearFeedback();
    }

    function openPopup(e) {
      if (e) e.preventDefault();
      resetState();
      showStep('initial');
      $overlay.removeClass("hidden");
      $popup.removeClass("hidden");
      $("body").addClass("llrp-no-scroll");
    }

    function closePopup() {
      $overlay.addClass("hidden");
      $popup.addClass("hidden");
      $("body").removeClass("llrp-no-scroll");
    }

    // --- UI Building Functions ---
    function displayOptions() {
        var optionsHtml = '';
        if(state.userExists) {
            $('.llrp-user-info').removeClass('hidden');
            $('.llrp-avatar').attr('src', state.avatar);
            $('.llrp-user-name').text(state.username);
            $('.llrp-user-email').text(state.email);

            optionsHtml += '<p>Como você gostaria de fazer login?</p>';
            optionsHtml += '<button class="llrp-button" data-action="show-password">Login com Senha</button>';
            optionsHtml += '<p>Ou</p>';

        } else {
            $('.llrp-user-info').addClass('hidden');
            optionsHtml += '<p>Parece que você é novo por aqui! Escolha como quer continuar:</p>';
            optionsHtml += '<button class="llrp-button" data-action="show-password-register">Cadastrar com Senha</button>';
            optionsHtml += '<p>Ou</p>';
        }

        // Code options
        optionsHtml += '<div>';
        optionsHtml += '<label><input type="radio" name="code_method" value="email" checked> E-mail</label>';
        if (state.hasPhone) {
            optionsHtml += '<label style="margin-left: 15px;"><input type="radio" name="code_method" value="whatsapp"> WhatsApp</label>';
        }
        optionsHtml += '</div>';
        optionsHtml += '<button class="llrp-button" data-action="send-code">Receber código de acesso</button>';

        $('#llrp-options-container').html(optionsHtml);
        showStep('options');
    }


    // --- AJAX Handlers ---
    function handleInitialStep() {
      clearFeedback();
      state.email = $("#llrp-email").val().trim();
      state.phone = $("#llrp-phone").val().trim();

      if (!isValidEmail(state.email)) {
        showFeedback("llrp-feedback-initial", "Por favor, insira um e-mail válido.");
        return;
      }

      $.post(llrp_ajax.url, {
        action: 'llrp_check_user',
        email: state.email,
        phone: state.phone,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          state.userExists = res.data.exists;
          state.hasPhone = res.data.has_phone;
          if(res.data.exists) {
              state.avatar = res.data.avatar;
              state.username = res.data.username;
          }
          displayOptions();
        } else {
          showFeedback("llrp-feedback-initial", res.data.message);
        }
      });
    }

    function handleSendCode() {
        clearFeedback();
        var method = $('input[name="code_method"]:checked').val();
        $.post(llrp_ajax.url, {
            action: 'llrp_send_code',
            email: state.email,
            phone: state.phone,
            method: method,
            nonce: LLRP_Data.nonce,
        }).done(function(res) {
            if(res.success) {
                $('#llrp-code-instructions').text(res.data.message);
                showStep('code');
            } else {
                showFeedback('llrp-feedback-options', res.data.message);
            }
        });
    }

    function handleCodeVerification() {
        clearFeedback();
        var code = $('#llrp-code').val().trim();
        if (!code) {
            showFeedback('llrp-feedback-code', 'Por favor, insira o código.');
            return;
        }
        $.post(llrp_ajax.url, {
            action: 'llrp_verify_code_and_login',
            email: state.email,
            phone: state.phone,
            code: code,
            nonce: LLRP_Data.nonce,
        }).done(function(res) {
            if(res.success) {
                window.location.href = res.data.redirect;
            } else {
                showFeedback('llrp-feedback-code', res.data.message);
            }
        });
    }

    function handlePasswordLogin() {
        clearFeedback();
        var password = $('#llrp-password').val();
        if (!password) {
            showFeedback('llrp-feedback-password', 'Por favor, insira sua senha.');
            return;
        }
        $.post(llrp_ajax.url, {
            action: 'llrp_login_with_password',
            email: state.email,
            password: password,
            nonce: LLRP_Data.nonce,
        }).done(function(res) {
            if(res.success) {
                window.location.href = res.data.redirect;
            } else {
                showFeedback('llrp-feedback-password', res.data.message);
            }
        });
    }

    function handlePasswordRegister() {
        clearFeedback();
        var password = $('#llrp-password').val();
        if (!password) {
            showFeedback('llrp-feedback-password', 'Por favor, crie uma senha.');
            return;
        }
        $.post(llrp_ajax.url, {
            action: 'llrp_register_with_password',
            email: state.email,
            phone: state.phone,
            password: password,
            nonce: LLRP_Data.nonce,
        }).done(function(res) {
            if(res.success) {
                window.location.href = res.data.redirect;
            } else {
                showFeedback('llrp-feedback-password', res.data.message);
            }
        });
    }

    // --- Event Binding ---
    $('.checkout-button').on('click', openPopup);
    $popup.on('click', '.llrp-close', closePopup);
    $popup.on('click', '#llrp-initial-submit', handleInitialStep);

    // Delegated events for dynamic content
    $('#llrp-options-container').on('click', 'button', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        if (action === 'show-password') {
            $('#llrp-password-submit').data('action', 'login');
            showStep('password');
        } else if (action === 'show-password-register') {
            $('#llrp-password-submit').data('action', 'register');
            showStep('password');
        } else if (action === 'send-code') {
            handleSendCode();
        }
    });

    $('#llrp-password-submit').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        if (action === 'login') {
            handlePasswordLogin();
        } else if (action === 'register') {
            handlePasswordRegister();
        }
    });

    $('#llrp-code-submit').on('click', function(e) {
        e.preventDefault();
        handleCodeVerification();
    });

    $popup.on('click', '.llrp-back', function(e) {
        e.preventDefault();
        resetState();
        showStep('initial');
    });

    $popup.on('click', '.llrp-back-to-options', function(e) {
        e.preventDefault();
        showStep('options');
    });

    // Submit on Enter
    $popup.on("keypress", "input", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        var $step = $(this).closest(".llrp-step");
        if ($step.hasClass("llrp-step-initial")) handleInitialStep();
        else if ($step.hasClass("llrp-step-password")) $('#llrp-password-submit').trigger('click');
        else if ($step.hasClass("llrp-step-code")) handleCodeVerification();
      }
    });

  });
})(jQuery);
