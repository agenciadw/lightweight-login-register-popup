(function ($) {
  "use strict";

  // Validação simples de formato de e-mail
  function isValidEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  $(function () {
    var $overlay = $("#llrp-overlay"),
      $popup = $("#llrp-popup"),
      savedEmail = ""; // guarda o e-mail digitado na primeira etapa

    // --- Dados vindos do PHP via wp_localize_script ---
    var initialCount = parseInt(LLRP_Data.initial_cart_count, 10);
    var isLoggedIn = parseInt(LLRP_Data.is_logged_in, 10) === 1;

    function openPopup(e) {
      if (e) e.preventDefault();
      resetSteps();
      $overlay.removeClass("hidden");
      $popup.removeClass("hidden");
      $("body").addClass("llrp-no-scroll");
      setTimeout(function () {
        $("#llrp-email").focus();
      }, 50);
    }

    function closePopup() {
      $overlay.addClass("hidden");
      $popup.addClass("hidden");
      $("body").removeClass("llrp-no-scroll");
      resetSteps();
    }

    function resetSteps() {
      $popup.find(".llrp-step").addClass("hidden");
      $popup.find(".llrp-step-email").removeClass("hidden");
      clearFeedback();
      $popup.find("input").val("");
    }

    function clearFeedback() {
      $popup.find(".llrp-feedback").text("");
    }

    function showFeedback(selector, message) {
      $popup.find("." + selector).text(message);
    }

    function showStep(step) {
      $popup.find(".llrp-step").addClass("hidden");
      $popup.find(".llrp-step-" + step).removeClass("hidden");
      var $inp = $popup.find(".llrp-step-" + step + " input").first();
      if ($inp.length) {
        setTimeout(function () {
          $inp.focus();
        }, 50);
      }
    }

    function handleEmailStep() {
      var email = $("#llrp-email").val().trim();
      clearFeedback();
      if (!email) {
        showFeedback("llrp-feedback-email", "Por favor, insira seu e-mail.");
        return;
      }
      if (!isValidEmail(email)) {
        showFeedback(
          "llrp-feedback-email",
          "Por favor, insira um e-mail válido."
        );
        return;
      }

      savedEmail = email;

      $.post(llrp_ajax.url, {
        action: llrp_ajax.action_check,
        email: email,
        nonce: llrp_ajax.nonce,
      }).done(function (res) {
        if (res.success) {
          if (res.data.exists) {
            $(".llrp-login-header").text(
              "Obrigado por voltar, " + res.data.username + "!"
            );
            $(".llrp-avatar").attr("src", res.data.avatar);
            $(".llrp-user-name").text(res.data.username);
            $(".llrp-user-email").text(res.data.email);
            showStep("login");
          } else {
            showStep("register");
          }
        } else {
          showFeedback("llrp-feedback-email", res.data.message);
        }
      });
    }

    function handleLoginStep() {
      var password = $("#llrp-password").val();
      clearFeedback();
      if (!password) {
        showFeedback("llrp-feedback-login", "Por favor, insira sua senha.");
        return;
      }
      $.post(llrp_ajax.url, {
        action: llrp_ajax.action_login,
        email: $("#llrp-email").val().trim(),
        password: password,
        remember: $("#llrp-remember").is(":checked") ? 1 : 0,
        nonce: llrp_ajax.nonce,
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
      clearFeedback();
      if (!password) {
        showFeedback("llrp-feedback-register", "Por favor, insira uma senha.");
        return;
      }
      $.post(llrp_ajax.url, {
        action: llrp_ajax.action_register,
        email: $("#llrp-email").val().trim(),
        password: password,
        nonce: llrp_ajax.nonce,
      }).done(function (res) {
        if (res.success) {
          window.location = res.data.redirect;
        } else {
          showFeedback("llrp-feedback-register", res.data.message);
        }
      });
    }

    function handleLostStep() {
      var email = $("#llrp-lost-email").val().trim();
      clearFeedback();
      if (!email) {
        showFeedback(
          "llrp-feedback-lost",
          "Por favor, insira seu e-mail para recuperar a senha."
        );
        return;
      }
      if (!isValidEmail(email)) {
        showFeedback(
          "llrp-feedback-lost",
          "Por favor, insira um e-mail válido."
        );
        return;
      }

      $.post(llrp_ajax.url, {
        action: llrp_ajax.action_lost,
        email: email,
        nonce: llrp_ajax.nonce,
      }).done(function (res) {
        showFeedback("llrp-feedback-lost", res.data.message);
      });
    }

    // Submit on Enter
    $popup.on("keypress", "input", function (e) {
      if (e.which === 13) {
        var $step = $(this).closest(".llrp-step");
        if ($step.hasClass("llrp-step-email")) handleEmailStep();
        else if ($step.hasClass("llrp-step-login")) handleLoginStep();
        else if ($step.hasClass("llrp-step-register")) handleRegisterStep();
        else if ($step.hasClass("llrp-step-lost")) handleLostStep();
        e.preventDefault();
      }
    });

    // Bind clicks
    $(".checkout-button").on("click", openPopup);
    $popup.find(".llrp-close").on("click", closePopup);
    $("#llrp-email-submit").on("click", handleEmailStep);
    $("#llrp-password-submit").on("click", handleLoginStep);
    $("#llrp-register-submit").on("click", handleRegisterStep);
    $popup.on("click", ".llrp-forgot", function (e) {
      e.preventDefault();
      showStep("lost");
      $("#llrp-lost-email").val(savedEmail);
    });
    $popup.on("click", ".llrp-back", function (e) {
      e.preventDefault();
      resetSteps();
    });
    $popup.on("click", "#llrp-lost-submit", handleLostStep);

    // --- Nova funcionalidade: abre popup no primeiro add-to-cart (com delay e só se deslogado) ---
    //if (!isLoggedIn) {
    // Opcional: listener para quem ainda adicionar via AJAX (caso carregue o script em outras páginas)
    //$(document.body).on("added_to_cart", function (e, fragments) {
    //var newCount = initialCount + 1;

    //if (fragments && fragments["div.widget_shopping_cart_content"]) {
    //var html = fragments["div.widget_shopping_cart_content"];
    //var match = html.match(/(\d+)\s+it(?:e|ê)m/);
    //if (match) newCount = parseInt(match[1], 10);
    //}

    //if (initialCount === 0 && newCount === 1) {
    //setTimeout(openPopup, 900);
    //}
    //});

    // **Auto-open**: ao carregar o carrinho *se já houver exatamente 1 item*
    // if (initialCount === 1) {
    ////setTimeout(openPopup, 900);
    // }
    // }
  });
})(jQuery);
