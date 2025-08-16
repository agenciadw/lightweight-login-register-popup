(function ($) {
  "use strict";

  $(function () {
    var $overlay = $("#llrp-overlay");
    var $popup = $("#llrp-popup");
    var savedIdentifier = "";

    function openPopup(e) {
      if (e) e.preventDefault();
      resetSteps();
      $overlay.removeClass("hidden");
      $popup.removeClass("hidden");
    }

    function closePopup() {
      $overlay.addClass("hidden");
      $popup.addClass("hidden");
    }

    function resetSteps() {
      $popup.find(".llrp-step").addClass("hidden");
      $popup.find(".llrp-step-email").removeClass("hidden");
      $popup.find("input").val("");
      clearFeedback();
    }

    function clearFeedback() {
      $popup.find(".llrp-feedback").text("");
    }

    function showFeedback(selector, message, isSuccess) {
      var $feedback = $popup.find("." + selector);
      $feedback.text(message);
      $feedback.toggleClass('success', !!isSuccess);
      $feedback.toggleClass('error', !isSuccess);
    }

    function showStep(step) {
      $popup.find(".llrp-step").addClass("hidden");
      $popup.find(".llrp-step-" + step).removeClass("hidden");
    }

    function handleIdentifierStep() {
      clearFeedback();
      savedIdentifier = $("#llrp-identifier").val().trim();
      if (!savedIdentifier) {
        showFeedback("llrp-feedback-email", "Por favor, preencha este campo.");
        return;
      }

      $.post(LLRP_Data.ajax_url, {
        action: 'llrp_check_user',
        identifier: savedIdentifier,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          if (res.data.exists) {
            $(".llrp-user-name").text(res.data.username);
            $(".llrp-user-email").text(res.data.email);
            $(".llrp-avatar").attr("src", res.data.avatar);
            showStep("login-options");
          } else {
            if (res.data.needs_email) {
              showStep("register-email");
            } else {
              showStep("register");
            }
          }
        } else {
          showFeedback("llrp-feedback-email", res.data.message);
        }
      });
    }

    function handleRegisterCpfStep() {
      var email = $("#llrp-register-email").val().trim();
      var password = $("#llrp-register-password-cpf").val();
      if (!email) {
        showFeedback("llrp-feedback-register-email", "Por favor, insira seu e-mail.");
        return;
      }
      if (!password) {
        showFeedback("llrp-feedback-register-email", "Por favor, insira uma senha.");
        return;
      }
      $.post(LLRP_Data.ajax_url, {
        action: 'llrp_register',
        identifier: savedIdentifier,
        email: email,
        password: password,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          window.location = res.data.redirect;
        } else {
          showFeedback("llrp-feedback-register-email", res.data.message);
        }
      });
    }

    function handleSendCode() {
        clearFeedback();
        $.post(LLRP_Data.ajax_url, {
            action: 'llrp_send_login_code',
            identifier: savedIdentifier,
            nonce: LLRP_Data.nonce,
        }).done(function(res) {
            if(res.success) {
                showFeedback('llrp-feedback-code', res.data.message, true);
                showStep('code');
            } else {
                showFeedback('llrp-feedback-login-options', res.data.message);
            }
        });
    }

    function handleCodeLogin() {
        var code = $('#llrp-code').val().trim();
        if (!code) {
            showFeedback('llrp-feedback-code', 'Por favor, insira o código.');
            return;
        }
        $.post(LLRP_Data.ajax_url, {
            action: 'llrp_code_login',
            identifier: savedIdentifier,
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

    function handleLoginStep() {
      var password = $("#llrp-password").val();
      if (!password) {
        showFeedback("llrp-feedback-login", "Por favor, insira sua senha.");
        return;
      }
      $.post(LLRP_Data.ajax_url, {
        action: 'llrp_login_with_password',
        identifier: savedIdentifier,
        password: password,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          window.location = res.data.redirect;
        } else {
          showFeedback("llrp-feedback-login", res.data.message);
        }
      });
    }

    function handleRegisterStep() {
      var password = $("#llrp-register-password").val();
      if (!password) {
        showFeedback("llrp-feedback-register", "Por favor, insira uma senha.");
        return;
      }
      $.post(LLRP_Data.ajax_url, {
        action: 'llrp_register',
        identifier: savedIdentifier,
        password: password,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          window.location = res.data.redirect;
        } else {
          showFeedback("llrp-feedback-register", res.data.message);
        }
      });
    }

    // Event Binding
    $(".checkout-button").on("click", openPopup);
    $popup.on("click", ".llrp-close", closePopup);
    $popup.on("click", ".llrp-back", resetSteps);
    $popup.on("click", "#llrp-email-submit", handleIdentifierStep);
    $popup.on("click", "#llrp-password-submit", handleLoginStep);
    $popup.on("click", "#llrp-register-submit", handleRegisterStep);
    $popup.on("click", "#llrp-register-cpf-submit", handleRegisterCpfStep);
    $popup.on("click", "#llrp-show-password-login", function() { showStep('login'); });
    $popup.on("click", "#llrp-send-code", handleSendCode);
    $popup.on("click", "#llrp-code-submit", handleCodeLogin);
    $(document).on("click", ".llrp-resend-code", function(e) {
        if ($(this).closest('#llrp-popup').length) {
            e.preventDefault();
            handleSendCode();
        }
    });
    $(document).on("click", ".llrp-back-to-options", function(e) {
        if ($(this).closest('#llrp-popup').length) {
            e.preventDefault();
            showStep('login-options');
        }
    });

    function applyIdentifierMask(e) {
        var value = e.target.value;
        if (/[a-zA-Z]/.test(value)) {
            return;
        }
        value = value.replace(/\D/g, "");

        var maxLength = 0;
        if (LLRP_Data.cnpj_login_enabled === '1') {
            maxLength = 14;
        } else if (LLRP_Data.cpf_login_enabled === '1') {
            maxLength = 11;
        }

        if (maxLength > 0 && value.length > maxLength) {
            value = value.slice(0, maxLength);
        }

        if (value.length > 3) {
            if (value.length <= 11) { // CPF
                value = value.replace(/(\d{3})(\d)/, "$1.$2");
                value = value.replace(/(\d{3})(\d)/, "$1.$2");
                value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            } else { // CNPJ
                value = value.replace(/^(\d{2})(\d)/, "$1.$2");
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                value = value.replace(/\.(\d{3})(\d)/, ".$1/$2");
                value = value.replace(/(\d{4})(\d)/, "$1-$2");
            }
        }
        e.target.value = value;
    }

    $popup.on("input", "#llrp-identifier", applyIdentifierMask);

    $popup.on("keypress", "input", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        var $step = $(this).closest(".llrp-step");
        if ($step.hasClass("llrp-step-email")) handleIdentifierStep();
        else if ($step.hasClass("llrp-step-login")) handleLoginStep();
        else if ($step.hasClass("llrp-step-register")) handleRegisterStep();
        else if ($step.hasClass("llrp-step-code")) handleCodeLogin();
      }
    });

    // Make the checkout button visible now that the JS is ready
    $('.checkout-button').css('visibility', 'visible');
  });
})(jQuery);
