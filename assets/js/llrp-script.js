(function ($) {
  "use strict";

  function isValidEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  $(function () {
    var $overlay = $("#llrp-overlay");
    var $popup = $("#llrp-popup");
    var savedEmail = "";

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

    function handleEmailStep() {
      clearFeedback();
      savedEmail = $("#llrp-email").val().trim();
      if (!isValidEmail(savedEmail)) {
        showFeedback("llrp-feedback-email", "Por favor, insira um e-mail válido.");
        return;
      }

      $.post(LLRP_Data.ajax_url, {
        action: 'llrp_check_email',
        email: savedEmail,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          if (res.data.exists) {
            $(".llrp-user-name").text(res.data.username);
            $(".llrp-user-email").text(savedEmail);
            $(".llrp-avatar").attr("src", res.data.avatar);
            showStep("login-options");
          } else {
            showStep("register");
          }
        } else {
          showFeedback("llrp-feedback-email", res.data.message);
        }
      });
    }

    function handleSendCode() {
        clearFeedback();
        $.post(LLRP_Data.ajax_url, {
            action: 'llrp_send_login_code',
            email: savedEmail,
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
            email: savedEmail,
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
        action: 'llrp_login',
        email: savedEmail,
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
        email: savedEmail,
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
    $popup.on("click", "#llrp-email-submit", handleEmailStep);
    $popup.on("click", "#llrp-password-submit", handleLoginStep);
    $popup.on("click", "#llrp-register-submit", handleRegisterStep);
    $popup.on("click", "#llrp-show-password-login", function() { showStep('login'); });
    $popup.on("click", "#llrp-send-code", handleSendCode);
    $popup.on("click", "#llrp-code-submit", handleCodeLogin);
    $popup.on("click", ".llrp-resend-code", function(e) {
        e.preventDefault();
        handleSendCode();
    });

    $popup.on("keypress", "input", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        var $step = $(this).closest(".llrp-step");
        if ($step.hasClass("llrp-step-email")) handleEmailStep();
        else if ($step.hasClass("llrp-step-login")) handleLoginStep();
        else if ($step.hasClass("llrp-step-register")) handleRegisterStep();
        else if ($step.hasClass("llrp-step-code")) handleCodeLogin();
      }
    });
  });
})(jQuery);
