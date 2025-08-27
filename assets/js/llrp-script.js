(function ($) {
  "use strict";

  $(function () {
    var $overlay = $("#llrp-overlay");
    var $popup = $("#llrp-popup");
    var savedIdentifier = "";
    var deliveryMethod = "email";
    var userEmail = ""; // Variável para armazenar o e-mail do usuário

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
      userEmail = ""; // Limpar o e-mail salvo
      clearFeedback();
    }

    function clearFeedback() {
      $popup.find(".llrp-feedback").text("");
    }

    function showFeedback(selector, message, isSuccess) {
      var $feedback = $popup.find("." + selector);
      $feedback.text(message);
      $feedback.toggleClass("success", !!isSuccess);
      $feedback.toggleClass("error", !isSuccess);
    }

    function showStep(step) {
      if (step === "code") {
        if (deliveryMethod === "whatsapp") {
          $popup.find(".llrp-step-code h2").text("Verifique seu WhatsApp");
          $popup
            .find(".llrp-step-code p")
            .first()
            .text(
              "Enviamos um código de 6 dígitos para o seu WhatsApp. Insira-o abaixo para fazer login."
            );
        } else {
          $popup.find(".llrp-step-code h2").text("Verifique seu E-mail");
          $popup
            .find(".llrp-step-code p")
            .first()
            .text(
              "Enviamos um código de 6 dígitos para o seu e-mail. Insira-o abaixo para fazer login."
            );
        }
      } else if (step === "lost") {
        // Usar o e-mail salvo na variável global
        $("#llrp-lost-email").val(userEmail);
      }
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
        action: "llrp_check_user",
        identifier: savedIdentifier,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          if (res.data.exists) {
            $(".llrp-user-name").text(res.data.username);
            $(".llrp-user-email").text(res.data.email);
            userEmail = res.data.email; // Salvar o e-mail na variável global
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
        showFeedback(
          "llrp-feedback-register-email",
          "Por favor, insira seu e-mail."
        );
        return;
      }
      if (!password) {
        showFeedback(
          "llrp-feedback-register-email",
          "Por favor, insira uma senha."
        );
        return;
      }
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_register",
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
        action: "llrp_send_login_code",
        identifier: savedIdentifier,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          deliveryMethod = res.data.delivery_method;
          showFeedback("llrp-feedback-code", res.data.message, true);
          showStep("code");
        } else {
          showFeedback("llrp-feedback-login-options", res.data.message);
        }
      });
    }

    function handleCodeLogin() {
      var code = $("#llrp-code").val().trim();
      if (!code) {
        showFeedback("llrp-feedback-code", "Por favor, insira o código.");
        return;
      }
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_code_login",
        identifier: savedIdentifier,
        code: code,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          window.location.href = res.data.redirect;
        } else {
          showFeedback("llrp-feedback-code", res.data.message);
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
        action: "llrp_login_with_password",
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
        action: "llrp_register",
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
    $popup.on("click", "#llrp-show-password-login", function () {
      showStep("login");
    });
    $popup.on("click", "#llrp-send-code", handleSendCode);
    $popup.on("click", "#llrp-code-submit", handleCodeLogin);
    $(document).on("click", ".llrp-resend-code", function (e) {
      if ($(this).closest("#llrp-popup").length) {
        e.preventDefault();
        handleSendCode();
      }
    });
    $(document).on("click", ".llrp-back-to-options", function (e) {
      if ($(this).closest("#llrp-popup").length) {
        e.preventDefault();
        showStep("login-options");
      }
    });

    $popup.on("click", ".llrp-forgot", function (e) {
      e.preventDefault();
      showStep("lost");
    });

    $popup.on("click", "#llrp-lost-submit", handleLostStep);

    function handleLostStep() {
      var email = $("#llrp-lost-email").val().trim();
      if (!email) {
        showFeedback("llrp-feedback-lost", "Por favor, insira seu e-mail.");
        return;
      }
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_lostpassword",
        email: email,
        nonce: LLRP_Data.nonce,
      }).done(function (res) {
        if (res.success) {
          showFeedback("llrp-feedback-lost", res.data.message, true);
        } else {
          showFeedback("llrp-feedback-lost", res.data.message);
        }
      });
    }

    function applyIdentifierMask(e) {
      var value = e.target.value;
      if (/[a-zA-Z]/.test(value)) {
        return;
      }
      value = value.replace(/\D/g, "");

      var maxLength = 0;
      if (LLRP_Data.cnpj_login_enabled === "1") {
        maxLength = 14;
      } else if (LLRP_Data.cpf_login_enabled === "1") {
        maxLength = 11;
      }

      if (maxLength > 0 && value.length > maxLength) {
        value = value.slice(0, maxLength);
      }

      if (value.length > 3) {
        if (value.length <= 11) {
          // CPF
          value = value.replace(/(\d{3})(\d)/, "$1.$2");
          value = value.replace(/(\d{3})(\d)/, "$1.$2");
          value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        } else {
          // CNPJ
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
        if ($step.hasClass("llrp-step-email")) {
          handleIdentifierStep();
        } else if ($step.hasClass("llrp-step-login")) {
          handleLoginStep();
        } else if ($step.hasClass("llrp-step-register")) {
          handleRegisterStep();
        } else if ($step.hasClass("llrp-step-code")) {
          handleCodeLogin();
        } else if ($step.hasClass("llrp-step-register-email")) {
          handleRegisterCpfStep();
        }
      }
    });

    // Initialize Social Login SDKs
    console.log("LLRP: Initializing social login...");
    console.log("LLRP: Google enabled:", LLRP_Data.google_login_enabled);
    console.log("LLRP: Google client ID:", LLRP_Data.google_client_id);
    console.log(
      "LLRP: Google object available:",
      typeof google !== "undefined"
    );

    initializeSocialLogin();

    // Social Login Event Bindings for popup
    $popup.on(
      "click",
      "#llrp-google-login, #llrp-google-login-initial",
      handleGoogleLogin
    );
    $popup.on(
      "click",
      "#llrp-facebook-login, #llrp-facebook-login-initial",
      handleFacebookLogin
    );

    // Social Login Event Bindings for My Account page
    $(document).on(
      "click",
      "#llrp-google-login-account, #llrp-google-register-account",
      handleGoogleLogin
    );
    $(document).on(
      "click",
      "#llrp-facebook-login-account, #llrp-facebook-register-account",
      handleFacebookLogin
    );

    // Social Login Functions
    function initializeSocialLogin() {
      // Initialize Facebook SDK
      if (
        LLRP_Data.facebook_login_enabled === "1" &&
        LLRP_Data.facebook_app_id &&
        typeof FB !== "undefined"
      ) {
        FB.init({
          appId: LLRP_Data.facebook_app_id,
          cookie: true,
          xfbml: true,
          version: "v18.0",
        });
      }
    }

    function handleGoogleLogin(e) {
      e.preventDefault();
      console.log("LLRP: Google login button clicked");
      clearFeedback();

      // Check if Google SDK is loaded
      if (typeof google === "undefined") {
        console.error("LLRP: Google SDK not loaded");
        showFeedback(
          "llrp-feedback-email",
          "Google SDK não carregado. Tente novamente."
        );
        return;
      }

      // Check if OAuth2 is available
      if (!google.accounts || !google.accounts.oauth2) {
        console.error("LLRP: Google OAuth2 not available");
        showFeedback(
          "llrp-feedback-email",
          "Google OAuth2 não disponível. Verifique a configuração."
        );
        return;
      }

      console.log("LLRP: Starting Google OAuth2 flow");

      // Use Google OAuth popup flow
      google.accounts.oauth2
        .initTokenClient({
          client_id: LLRP_Data.google_client_id,
          scope: "email profile",
          callback: (response) => {
            console.log("LLRP: Google OAuth callback received:", response);
            if (response.access_token) {
              console.log("LLRP: Access token received, fetching user info");
              // Get user info using the access token
              fetch("https://www.googleapis.com/oauth2/v2/userinfo", {
                headers: {
                  Authorization: "Bearer " + response.access_token,
                },
              })
                .then((response) => {
                  console.log(
                    "LLRP: User info fetch response status:",
                    response.status
                  );
                  return response.json();
                })
                .then((userInfo) => {
                  console.log(
                    "LLRP: User info received from Google:",
                    userInfo
                  );

                  // Validate user info
                  if (!userInfo.email) {
                    console.error("LLRP: No email in user info");
                    showFeedback(
                      "llrp-feedback-email",
                      "Google não forneceu e-mail."
                    );
                    return;
                  }

                  // Process login with user info directly
                  processGoogleLogin(userInfo);
                })
                .catch((error) => {
                  console.error("LLRP: Error fetching user info:", error);
                  showFeedback(
                    "llrp-feedback-email",
                    "Erro ao obter informações do Google."
                  );
                });
            } else {
              console.log("LLRP: Google login cancelled or failed");
              showFeedback(
                "llrp-feedback-email",
                "Login com Google cancelado."
              );
            }
          },
          error_callback: (error) => {
            console.error("Google OAuth error:", error);
            showFeedback(
              "llrp-feedback-email",
              "Erro no login com Google. Tente novamente."
            );
          },
        })
        .requestAccessToken();
    }

    function processGoogleLogin(userInfo) {
      console.log("LLRP: Processing Google login");
      console.log("LLRP: User info:", userInfo);
      console.log("LLRP: Nonce:", LLRP_Data.nonce);
      console.log("LLRP: AJAX URL:", LLRP_Data.ajax_url);

      $.post(LLRP_Data.ajax_url, {
        action: "llrp_google_login",
        user_info: JSON.stringify(userInfo),
        nonce: LLRP_Data.nonce,
        from_account: LLRP_Data.is_account_page || "0",
      })
        .done(function (res) {
          console.log("LLRP: AJAX response:", res);
          if (res.success) {
            console.log("LLRP: Login successful, redirecting...");

            // Smart redirect based on current page
            if (LLRP_Data.is_account_page === "1") {
              // On My Account page, reload to show logged-in state
              window.location.reload();
            } else {
              // On cart page, redirect to checkout
              window.location.href = res.data.redirect;
            }
          } else {
            console.log("LLRP: Login failed with message:", res.data.message);

            // Check if it's a nonce error and regenerate
            if (res.data.message && res.data.message.includes("segurança")) {
              // Refresh the page to get a new nonce
              showAccountFeedback("Sessão expirada. Recarregando...", "error");
              setTimeout(() => window.location.reload(), 1500);
            } else {
              showAccountFeedback(res.data.message, "error");
            }
          }
        })
        .fail(function (xhr) {
          console.log("LLRP: AJAX request failed");
          console.log("LLRP: Status:", xhr.status);
          console.log("LLRP: Response:", xhr.responseText);
          console.log("LLRP: Full xhr object:", xhr);

          if (xhr.status === 403) {
            showAccountFeedback(
              "Sessão expirada. Recarregue a página.",
              "error"
            );
          } else if (xhr.status === 0) {
            showAccountFeedback(
              "Problema de conexão. Verifique sua internet.",
              "error"
            );
          } else {
            showAccountFeedback(
              "Erro de conexão (Status: " + xhr.status + "). Tente novamente.",
              "error"
            );
          }
        });
    }

    function handleFacebookLogin(e) {
      e.preventDefault();
      clearFeedback();

      if (typeof FB === "undefined") {
        showFeedback(
          "llrp-feedback-email",
          "Facebook SDK não carregado. Tente novamente."
        );
        return;
      }

      FB.login(
        function (response) {
          if (response.authResponse) {
            $.post(LLRP_Data.ajax_url, {
              action: "llrp_facebook_login",
              access_token: response.authResponse.accessToken,
              nonce: LLRP_Data.nonce,
              from_account: LLRP_Data.is_account_page || "0",
            })
              .done(function (res) {
                if (res.success) {
                  // Smart redirect based on current page
                  if (LLRP_Data.is_account_page === "1") {
                    // On My Account page, reload to show logged-in state
                    window.location.reload();
                  } else {
                    // On cart page, redirect to checkout
                    window.location.href = res.data.redirect;
                  }
                } else {
                  // Check if it's a nonce error and regenerate
                  if (
                    res.data.message &&
                    res.data.message.includes("segurança")
                  ) {
                    // Refresh the page to get a new nonce
                    showAccountFeedback(
                      "Sessão expirada. Recarregando...",
                      "error"
                    );
                    setTimeout(() => window.location.reload(), 1500);
                  } else {
                    showAccountFeedback(res.data.message, "error");
                  }
                }
              })
              .fail(function (xhr) {
                if (xhr.status === 403) {
                  showAccountFeedback(
                    "Sessão expirada. Recarregue a página.",
                    "error"
                  );
                } else {
                  showAccountFeedback(
                    "Erro de conexão. Tente novamente.",
                    "error"
                  );
                }
              });
          } else {
            showAccountFeedback("Login com Facebook cancelado.", "error");
          }
        },
        { scope: "email" }
      );
    }

    /**
     * Smart feedback function that works on both popup and My Account page
     */
    function showAccountFeedback(message, type = "error") {
      if (LLRP_Data.is_account_page === "1") {
        // We're on My Account page - show WooCommerce-style notices
        var noticeClass =
          type === "error" ? "woocommerce-error" : "woocommerce-message";
        var noticeHtml =
          '<div class="' + noticeClass + '" role="alert">' + message + "</div>";

        // Remove existing notices
        $(".woocommerce-error, .woocommerce-message").remove();

        // Add new notice at the top of forms
        if ($(".llrp-my-account-social-login").length) {
          $(".llrp-my-account-social-login").prepend(noticeHtml);
        } else if ($(".llrp-my-account-social-register").length) {
          $(".llrp-my-account-social-register").prepend(noticeHtml);
        } else {
          // Fallback: add to top of page
          $(".woocommerce").prepend(noticeHtml);
        }
      } else {
        // We're on cart page - use popup feedback
        showFeedback("llrp-feedback-email", message);
      }
    }

    // Make the checkout button visible now that the JS is ready
    $(".checkout-button").css("visibility", "visible");
  });
})(jQuery);
