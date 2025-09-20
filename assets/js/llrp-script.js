(function ($) {
  "use strict";

  $(function () {
    var $overlay = $("#llrp-overlay");
    var $popup = $("#llrp-popup");
    var savedIdentifier = "";
    var deliveryMethod = "email";
    var userEmail = ""; // Variável para armazenar o e-mail do usuário
    
    // Debug mode - only log in development
    var debugMode = typeof LLRP_Data !== 'undefined' && LLRP_Data.debug_mode === '1';
    
    function safeLog(message, data) {
      if (debugMode) {
        if (data) {
          console.log(message, data);
        } else {
          console.log(message);
        }
      }
    }

    /**
     * Soft refresh for Interactivity API compatibility
     * Replaces window.location.reload() to prevent conflicts
     */
    function softRefresh() {
      console.log("🔄 LLRP: Using soft refresh (Interactivity API compatible)");

      // Close popup
      closePopup();

      // Update cart fragments
      if (typeof wc_cart_fragments_params !== "undefined") {
        $(document.body).trigger("wc_fragment_refresh");
        $(document.body).trigger("woocommerce_fragments_refreshed");
      }

      // Trigger checkout updates if on checkout page
      if (
        window.location.href.includes("checkout") ||
        window.location.href.includes("finalizar-compra")
      ) {
        $("body").trigger("update_checkout");
        $(document.body).trigger("updated_checkout");
      }

      // Update account page if needed
      if (LLRP_Data.is_account_page === "1") {
        $(".woocommerce-MyAccount-content").trigger("refresh");
      }

      console.log("🔄 LLRP: Soft refresh completed");
    }

    /**
     * Enhanced cart persistence with triple backup system
     * Saves cart state before login to prevent loss
     */
    function saveCartBeforeLogin() {
      safeLog("🛒 Saving cart before login");

      try {
        // Method 1: WooCommerce fragments (primary)
        var cartData = null;
        if (
          typeof wc_add_to_cart_params !== "undefined" &&
          window.wc_cart_fragments_params
        ) {
          cartData = {
            method: "wc_fragments",
            fragments: window.wc_cart_fragments_params.cart_fragments || {},
            cart_hash: window.wc_cart_fragments_params.cart_hash || "",
            cart_total: $(".cart-contents .amount").text() || "",
            cart_count: $(".cart-contents .count").text() || "0",
            timestamp: Date.now(),
          };
        }

        // Method 2: DOM cart contents (fallback)
        var domCartData = {
          method: "dom_content",
          cart_items: [],
          cart_total:
            $(".cart-contents .amount").text() ||
            $(".woocommerce-Price-amount").text() ||
            "",
          cart_count:
            $(".cart-contents .count").text() ||
            $(".cart-contents-count").text() ||
            "0",
          timestamp: Date.now(),
        };

        // Extract cart items from DOM
        $(".woocommerce-cart-form .cart_item, .cart-item").each(function () {
          var item = {
            product_name: $(this)
              .find(".product-name, .product-title")
              .text()
              .trim(),
            quantity: $(this).find('.qty, input[name*="cart"]').val() || "1",
            price: $(this).find(".amount, .price").text().trim(),
          };
          if (item.product_name) {
            domCartData.cart_items.push(item);
          }
        });

        // CRITICAL: Dual backup system
        var primaryData = cartData || domCartData;

        // Backup 1: localStorage (primary)
        localStorage.setItem("llrp_cart_backup", JSON.stringify(primaryData));
        console.log("🛒 PRIMARY BACKUP saved to localStorage:", primaryData);

        // Backup 2: sessionStorage (failsafe)
        sessionStorage.setItem(
          "llrp_cart_backup_failsafe",
          JSON.stringify(primaryData)
        );
        console.log("🛒 FAILSAFE BACKUP saved to sessionStorage:", primaryData);

        // Backup 3: Additional DOM backup
        var additionalBackup = {
          method: "additional_dom",
          cart_widget_html: $(".widget_shopping_cart_content").html() || "",
          cart_dropdown_html: $(".cart-dropdown, .cart-contents").html() || "",
          mini_cart_html: $(".mini-cart, .woocommerce-mini-cart").html() || "",
          timestamp: Date.now(),
        };

        localStorage.setItem(
          "llrp_cart_dom_backup",
          JSON.stringify(additionalBackup)
        );
        console.log("🛒 ADDITIONAL DOM BACKUP saved:", additionalBackup);

        console.log("🛒 Cart backup completed successfully");
        return true;
      } catch (error) {
        console.error(
          "🚨 CRITICAL ERROR: Failed to save cart before login:",
          error
        );
        return false;
      }
    }

    function restoreCartAfterLogin() {
      console.log("🛒 CRITICAL: Restoring cart after login - STARTED");

      try {
        // Try primary backup first
        var savedCart = localStorage.getItem("llrp_cart_backup");
        var backupSource = "localStorage";

        // Fallback to sessionStorage if primary fails
        if (!savedCart) {
          savedCart = sessionStorage.getItem("llrp_cart_backup_failsafe");
          backupSource = "sessionStorage";
          console.log("🛒 Primary backup not found, using failsafe backup");
        }

        if (savedCart) {
          var cartData = JSON.parse(savedCart);
          console.log("🛒 RESTORING cart from " + backupSource + ":", cartData);

          // Check if cart data is not too old (24 hours)
          if (
            cartData.timestamp &&
            Date.now() - cartData.timestamp < 24 * 60 * 60 * 1000
          ) {
            if (cartData.method === "wc_fragments" && cartData.fragments) {
              // Restore using WooCommerce fragments
              updateCartFragments(cartData.fragments);
              console.log("🛒 Cart restored using WC fragments method");
            } else {
              // Use soft refresh to restore cart state (Interactivity API compatible)
              console.log("🛒 Using soft refresh to restore cart state");
              setTimeout(function () {
                softRefresh();
              }, 1000);
            }

            // Clear backups after successful restoration
            localStorage.removeItem("llrp_cart_backup");
            sessionStorage.removeItem("llrp_cart_backup_failsafe");
            localStorage.removeItem("llrp_cart_dom_backup");

            console.log("🛒 CRITICAL: Cart restoration completed successfully");
            return true;
          } else {
            console.log("🛒 Cart data expired, clearing old backups");
            localStorage.removeItem("llrp_cart_backup");
            sessionStorage.removeItem("llrp_cart_backup_failsafe");
            localStorage.removeItem("llrp_cart_dom_backup");
          }
        } else {
          console.log("🛒 No cart backup found to restore");
        }

        return false;
      } catch (error) {
        console.error(
          "🚨 CRITICAL ERROR: Failed to restore cart after login:",
          error
        );
        return false;
      }
    }

    function mergeLocalCartWithUserCart() {
      console.log("🛒 SAFE: Checking if cart merge is needed");

      // SAFE MODE: Only restore if there's a legitimate backup and current cart is empty
      var currentCartCount = $(".cart-contents-count").text() || "0";
      if (currentCartCount === "0" || parseInt(currentCartCount) === 0) {
        console.log("🛒 SAFE: Current cart is empty, attempting restoration");
        var restored = restoreCartAfterLogin();
        if (!restored) {
          console.log("🛒 SAFE: No local cart backup found - this is normal");
        }
      } else {
        console.log(
          "🛒 SAFE: Current cart has items (" +
            currentCartCount +
            "), not touching it"
        );
      }
    }

    // Function to refresh nonce when needed
    function refreshNonce(callback) {
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_refresh_nonce",
      })
        .done(function (res) {
          if (res.success && res.data.nonce) {
            LLRP_Data.nonce = res.data.nonce;
            console.log("LLRP: Nonce refreshed successfully");
            if (callback) callback(true);
          } else {
            console.log("LLRP: Failed to refresh nonce");
            if (callback) callback(false);
          }
        })
        .fail(function () {
          console.log("LLRP: AJAX failed to refresh nonce");
          if (callback) callback(false);
        });
    }

    // Enhanced AJAX function that handles nonce errors automatically
    function llrpAjax(data, successCallback, errorCallback) {
      $.post(LLRP_Data.ajax_url, data)
        .done(function (res) {
          if (res.success) {
            successCallback(res);
          } else {
            // Check if it's a nonce error
            if (
              res.data &&
              res.data.message &&
              (res.data.message.includes("segurança") ||
                res.data.message.includes("Nonce verification failed"))
            ) {
              console.log("LLRP: Nonce error detected, trying to refresh...");

              refreshNonce(function (refreshed) {
                if (refreshed) {
                  // Update the nonce in the data and retry
                  data.nonce = LLRP_Data.nonce;

                  $.post(LLRP_Data.ajax_url, data)
                    .done(function (retryRes) {
                      if (retryRes.success) {
                        successCallback(retryRes);
                      } else {
                        errorCallback(retryRes);
                      }
                    })
                    .fail(function () {
                      errorCallback({
                        data: {
                          message: "Erro de conexão após renovar nonce.",
                        },
                      });
                    });
                } else {
                  errorCallback({
                    data: {
                      message:
                        "Não foi possível renovar a sessão. Recarregue a página.",
                    },
                  });
                }
              });
            } else {
              errorCallback(res);
            }
          }
        })
        .fail(function (xhr) {
          errorCallback({ data: { message: "Erro de conexão." } });
        });
    }

    function openPopup(e) {
      if (e) e.preventDefault();

      // CRITICAL: Save cart with dual backup before ANY login action
      console.log("🚨 CRITICAL: About to open popup - saving cart FIRST");
      var cartSaved = saveCartBeforeLogin();
      if (!cartSaved) {
        console.error("🚨 CRITICAL WARNING: Cart backup failed!");
      }

      // Verificação dinâmica do status de login via AJAX
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_check_login_status",
        nonce: LLRP_Data.nonce,
      })
        .done(function (res) {
          if (res.success && res.data.is_logged_in) {
            // Usuário está logado, redirecionar para checkout
            console.log("User is logged in, redirecting to checkout");
            window.location.href = res.data.checkout_url;
          } else {
            // Usuário não está logado, mostrar popup
            console.log("User not logged in, showing popup");
            resetSteps();
            $overlay.removeClass("hidden");
            $popup.removeClass("hidden");

            // Hide close button if on checkout page
            hideCloseButtonIfCheckout();
          }
        })
        .fail(function () {
          // Em caso de erro, assumir que não está logado e mostrar popup
          console.log("AJAX failed, showing popup as fallback");
          resetSteps();
          $overlay.removeClass("hidden");
          $popup.removeClass("hidden");

          // Hide close button if on checkout page
          hideCloseButtonIfCheckout();
        });
    }

    function closePopup() {
      $overlay.addClass("hidden");
      $popup.addClass("hidden");
    }

    function hidePopup() {
      closePopup();
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

    function hideCloseButtonIfCheckout() {
      // Check if we're on checkout or finalizar-compra page
      var isCheckoutPage =
        window.location.href.includes("checkout") ||
        window.location.href.includes("finalizar-compra") ||
        LLRP_Data.is_checkout_page === "1";

      if (isCheckoutPage) {
        console.log("LLRP: Hiding close button on checkout page");
        $popup.find(".llrp-close").hide();
      } else {
        console.log("LLRP: Showing close button on non-checkout page");
        $popup.find(".llrp-close").show();
      }
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

      // Use direct AJAX for registration (no nonce dependency)
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_register",
        identifier: savedIdentifier,
        email: email,
        password: password,
        // Remove nonce dependency for registration
      })
        .done(function (res) {
          if (res.success) {
            console.log(
              "🛒 CRITICAL: Registration (with email) successful - restoring cart IMMEDIATELY"
            );

            // CRITICAL: Immediate cart restoration
            mergeLocalCartWithUserCart();

            // Update cart fragments if provided
            if (res.data.cart_fragments) {
              updateCartFragments(res.data.cart_fragments);
            }

            // CRITICAL: Auto-fill with email sync for BOTH fields
            if (email) {
              var userData = {
                email: email,
                account_email: email,
                billing_email: email,
              };
              fillCheckoutFormData(userData);
              syncEmailFields(email);
            }

            // Check if Fluid Checkout is active and handle accordingly
            if (isFluidCheckoutActive()) {
              // For Fluid Checkout, use soft refresh instead of hard reload
              console.log(
                "🔄 FLUID CHECKOUT: Using soft refresh for Interactivity API compatibility"
              );
              softRefresh();
            } else {
              // For standard WooCommerce, redirect normally
              window.location = res.data.redirect;
            }
          } else {
            showFeedback("llrp-feedback-register-email", res.data.message);
          }
        })
        .fail(function (xhr) {
          console.log("LLRP: Registration AJAX failed:", xhr);
          showFeedback(
            "llrp-feedback-register-email",
            "Erro de conexão. Tente novamente."
          );
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
          console.log(
            "🛒 CRITICAL: Code login successful - restoring cart IMMEDIATELY"
          );

          // CRITICAL: Immediate cart restoration
          mergeLocalCartWithUserCart();

          // Update cart fragments if provided
          if (res.data.cart_fragments) {
            updateCartFragments(res.data.cart_fragments);
          }

          // Auto-fill user data in checkout form if available
          if (res.data.user_data) {
            fillCheckoutFormData(res.data.user_data);
          }

          // Check if Fluid Checkout is active and handle accordingly
          console.log(
            "LLRP: Checking Fluid Checkout status after code login..."
          );
          if (isFluidCheckoutActive()) {
            console.log("LLRP: Fluid Checkout detected, using soft refresh...");
            // For Fluid Checkout, use soft refresh instead of hard reload
            setTimeout(function () {
              softRefresh();
            }, 500);
          } else {
            console.log("LLRP: Standard WooCommerce, redirecting normally...");
            // For standard WooCommerce, redirect normally
            window.location.href = res.data.redirect;
          }
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
          console.log(
            "🛒 CRITICAL: Password login successful - restoring cart IMMEDIATELY"
          );

          // CRITICAL: Immediate cart restoration
          mergeLocalCartWithUserCart();

          // Update cart fragments if provided
          if (res.data.cart_fragments) {
            updateCartFragments(res.data.cart_fragments);
          }

          // Auto-fill user data in checkout form if available
          if (res.data.user_data) {
            fillCheckoutFormData(res.data.user_data);
          }

          // SAFE REDIRECT: Check if we need to redirect or stay on current page
          if (res.data.redirect && res.data.redirect !== window.location.href) {
            // Only redirect if it's a different URL
            if (
              isFluidCheckoutActive() &&
              window.location.href.includes("checkout")
            ) {
              // For Fluid Checkout on checkout page, use soft refresh to preserve checkout state
              console.log(
                "🔄 FLUID CHECKOUT: Using soft refresh to maintain state (Interactivity API compatible)"
              );
              setTimeout(function () {
                softRefresh();
              }, 500);
            } else {
              // For other cases, redirect normally
              console.log("🔄 REDIRECTING to:", res.data.redirect);
              window.location = res.data.redirect;
            }
          } else {
            // No redirect needed, just close popup and reload fragments
            console.log("🔄 NO REDIRECT: Staying on current page");
            hidePopup();
            if (res.data.cart_fragments) {
              updateCartFragments(res.data.cart_fragments);
            }
          }
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

      // Use direct AJAX for registration (no nonce dependency)
      $.post(LLRP_Data.ajax_url, {
        action: "llrp_register",
        identifier: savedIdentifier,
        password: password,
        // Remove nonce dependency for registration
      })
        .done(function (res) {
          if (res.success) {
            console.log(
              "🛒 CRITICAL: Registration successful - restoring cart IMMEDIATELY"
            );

            // CRITICAL: Immediate cart restoration
            mergeLocalCartWithUserCart();

            // Update cart fragments if provided
            if (res.data.cart_fragments) {
              updateCartFragments(res.data.cart_fragments);
            }

            // Auto-fill user data in checkout form if available
            if (res.data.user_data) {
              fillCheckoutFormData(res.data.user_data);
            }

            // Check if Fluid Checkout is active and handle accordingly
            if (isFluidCheckoutActive()) {
              // For Fluid Checkout, use soft refresh for Interactivity API compatibility
              console.log(
                "🔄 FLUID CHECKOUT: Using soft refresh for Interactivity API compatibility"
              );
              softRefresh();
            } else {
              // For standard WooCommerce, redirect normally
              window.location = res.data.redirect;
            }
          } else {
            showFeedback("llrp-feedback-register", res.data.message);
          }
        })
        .fail(function (xhr) {
          console.log("LLRP: Registration AJAX failed:", xhr);
          showFeedback(
            "llrp-feedback-register",
            "Erro de conexão. Tente novamente."
          );
        });
    }

    // Event Binding - Interceptação mais robusta do botão de checkout
    function interceptCheckoutButton(e) {
      console.log("Checkout button clicked, intercepting...");

      // SEMPRE prevenir o comportamento padrão primeiro
      e.preventDefault();
      e.stopPropagation();

      // Abrir o popup que fará a verificação dinâmica
      openPopup(e);

      // Retornar false para garantir que o evento não continue
      return false;
    }

    // Usar event delegation para garantir que funcione com elementos dinâmicos
    $(document).on("click.llrp", ".checkout-button", interceptCheckoutButton);

    // Também interceptar outros seletores comuns de botões de checkout
    $(document).on(
      "click.llrp",
      'a[href*="checkout"], a[href*="finalizar-compra"]',
      function (e) {
        // Só interceptar se não estivermos na página de checkout
        if (
          !window.location.href.includes("checkout") &&
          !window.location.href.includes("finalizar-compra")
        ) {
          console.log("Checkout link clicked, intercepting...");
          return interceptCheckoutButton(e);
        }
      }
    );
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
      safeLog("LLRP: Processing user login");
      // Sensitive data removed from production logs

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

            console.log(
              "🛒 CRITICAL: Google login successful - restoring cart IMMEDIATELY"
            );

            // CRITICAL: Immediate cart restoration
            mergeLocalCartWithUserCart();

            // Update cart fragments if provided
            if (res.data.cart_fragments) {
              updateCartFragments(res.data.cart_fragments);
            }

            // Auto-fill user data in checkout form if available + email sync
            if (res.data.user_data) {
              fillCheckoutFormData(res.data.user_data);
              if (res.data.user_data.email) {
                syncEmailFields(res.data.user_data.email);
              }
            }

            // Smart redirect based on current page and Fluid Checkout
            if (LLRP_Data.is_account_page === "1") {
              // On My Account page, use soft refresh to show logged-in state
              console.log(
                "🔄 MY ACCOUNT: Using soft refresh for Interactivity API compatibility"
              );
              softRefresh();
            } else if (isFluidCheckoutActive()) {
              // For Fluid Checkout, use soft refresh for Interactivity API compatibility
              console.log(
                "🔄 FLUID CHECKOUT: Using soft refresh for Interactivity API compatibility"
              );
              softRefresh();
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
                  console.log(
                    "🛒 CRITICAL: Facebook login successful - restoring cart IMMEDIATELY"
                  );

                  // CRITICAL: Immediate cart restoration
                  mergeLocalCartWithUserCart();

                  // Update cart fragments if provided
                  if (res.data.cart_fragments) {
                    updateCartFragments(res.data.cart_fragments);
                  }

                  // Auto-fill user data in checkout form if available + email sync
                  if (res.data.user_data) {
                    fillCheckoutFormData(res.data.user_data);
                    if (res.data.user_data.email) {
                      syncEmailFields(res.data.user_data.email);
                    }
                  }

                  // Smart redirect based on current page and Fluid Checkout
                  if (LLRP_Data.is_account_page === "1") {
                    // On My Account page, use soft refresh to show logged-in state
                    console.log(
                      "🔄 MY ACCOUNT: Using soft refresh for Interactivity API compatibility"
                    );
                    softRefresh();
                  } else if (isFluidCheckoutActive()) {
                    // For Fluid Checkout, use soft refresh for Interactivity API compatibility
                    console.log(
                      "🔄 FLUID CHECKOUT: Using soft refresh for Interactivity API compatibility"
                    );
                    softRefresh();
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

    // Auto-show popup if user accesses checkout page directly without being logged in
    if (LLRP_Data.is_checkout_page === "1" && LLRP_Data.is_logged_in !== "1") {
      console.log(
        "User accessed checkout page directly without being logged in, showing popup automatically"
      );

      // Small delay to ensure page is fully loaded
      setTimeout(function () {
        openPopup();
      }, 500);
    }

    /**
     * Check if Fluid Checkout is active
     */
    function isFluidCheckoutActive() {
      // Check for Fluid Checkout indicators
      var isActive =
        typeof window.fluidCheckout !== "undefined" ||
        $(".fluid-checkout").length > 0 ||
        $(".fc-checkout").length > 0 ||
        $("body").hasClass("fluid-checkout") ||
        $("body").hasClass("fc-checkout") ||
        $("body").hasClass("fluid-checkout-checkout") ||
        $(".fc-step").length > 0 ||
        $(".fc-checkout-step").length > 0 ||
        $(".checkout-step").length > 0 ||
        $(".woocommerce-checkout").hasClass("fluid-checkout") ||
        $(".woocommerce-checkout").hasClass("fc-checkout") ||
        window.location.href.indexOf("fluid-checkout") !== -1 ||
        window.location.href.indexOf("finalizar-compra") !== -1 ||
        window.location.href.indexOf("checkout") !== -1 ||
        $(".checkout-step").length > 0 ||
        $("form.checkout").hasClass("fluid-checkout") ||
        $("form.checkout").hasClass("fc-checkout");

      console.log("LLRP: Fluid Checkout detection:", {
        window_fluidCheckout: typeof window.fluidCheckout !== "undefined",
        fluid_checkout_elements: $(".fluid-checkout").length,
        fc_checkout_elements: $(".fc-checkout").length,
        body_fluid_checkout: $("body").hasClass("fluid-checkout"),
        body_fc_checkout: $("body").hasClass("fc-checkout"),
        fc_step_elements: $(".fc-step").length,
        checkout_step_elements: $(".checkout-step").length,
        finalizar_compra_url:
          window.location.href.indexOf("finalizar-compra") !== -1,
        is_active: isActive,
      });

      return isActive;
    }

    /**
     * Update cart fragments with Interactivity API compatibility
     */
    function updateCartFragments(fragments) {
      if (!fragments || typeof fragments !== "object") {
        return;
      }

      console.log("LLRP: Updating cart fragments:", fragments);

      // Check if Interactivity API or WooCommerce Blocks are active
      var isInteractivityActive =
        (typeof wp !== "undefined" && wp.interactivity) ||
        $(".wp-block-woocommerce-mini-cart").length > 0 ||
        $(".wc-block-mini-cart").length > 0 ||
        $(".wc-block-cart").length > 0;

      if (isInteractivityActive) {
        console.log("LLRP: Using Interactivity API/Blocks approach");

        // Multiple triggers for better compatibility
        $(document.body).trigger("wc_fragment_refresh");
        $(document.body).trigger("woocommerce_fragments_refreshed");

        // Specific for mini cart blocks
        if ($(".wp-block-woocommerce-mini-cart").length) {
          $(".wp-block-woocommerce-mini-cart").trigger("refresh");
          // Also try direct DOM update for blocks
          var event = new CustomEvent("wc-blocks_cart_fragments_update");
          document.dispatchEvent(event);
        }

        // Trigger update for cart count and buttons
        if ($(".wc-block-mini-cart__button").length) {
          $(".wc-block-mini-cart__button").trigger("update");
        }

        // Force refresh via native WooCommerce if available
        if (
          typeof wc_cart_fragments_params !== "undefined" &&
          typeof wc_cart_fragments_params.ajax_url !== "undefined"
        ) {
          $(document.body).trigger("wc_fragment_refresh");
        }
      } else {
        console.log("LLRP: Using traditional fragment update");

        // Update each fragment (traditional method)
        $.each(fragments, function (selector, content) {
          if (selector && content !== undefined) {
            var $element = $(selector);
            if ($element.length) {
              $element.html(content);
              console.log("LLRP: Updated fragment:", selector);
            }
          }
        });

        // Trigger WooCommerce cart fragments update event
        $(document.body).trigger("wc_fragments_refreshed");
      }

      // Always trigger Fluid Checkout specific events if available
      if (isFluidCheckoutActive()) {
        $(document.body).trigger("fluidcheckout_fragments_updated");
        $(document.body).trigger("fc_fragments_updated");
      }

      console.log("LLRP: Cart fragments update completed");
    }

    /**
     * CRITICAL: Sync email fields to ensure account_email and billing_email are always identical
     */
    function syncEmailFields(email) {
      if (!email) return;

      console.log(
        "📧 CRITICAL: Syncing email fields - account_email ↔ billing_email:",
        email
      );

      // All possible email field selectors
      var emailSelectors = [
        "#account_email",
        "#billing_email",
        'input[name="account_email"]',
        'input[name="billing_email"]',
        'input[name="email"]',
        "#email",
        ".email-field",
      ];

      emailSelectors.forEach(function (selector) {
        var $field = $(selector);
        if ($field.length) {
          $field.val(email).trigger("change");
          console.log("📧 Email synced to field:", selector, "=", email);
        }
      });

      // Setup real-time synchronization listeners
      emailSelectors.forEach(function (selector) {
        $(document).off("input.email-sync change.email-sync", selector);
        $(document).on(
          "input.email-sync change.email-sync",
          selector,
          function () {
            var newEmail = $(this).val();
            if (newEmail && newEmail !== email) {
              console.log(
                "📧 Real-time sync triggered by:",
                selector,
                "→",
                newEmail
              );
              syncEmailFields(newEmail);
            }
          }
        );
      });
    }

    /**
     * Auto-fill checkout form with user data + CRITICAL email sync
     */
    function fillCheckoutFormData(userData) {
      if (!userData || typeof userData !== "object") {
        return;
      }

      console.log(
        "📝 CRITICAL: Auto-filling checkout form with user data:",
        userData
      );

      // CRITICAL: Email must go to BOTH account_email AND billing_email
      var userEmail =
        userData.email ||
        userData.billing_email ||
        userData.account_email ||
        "";
      if (userEmail) {
        syncEmailFields(userEmail);
      }

      // Common field mappings
      var fieldMappings = {
        // CRITICAL: Both email fields must have same value
        account_email: userEmail,
        billing_email: userEmail,
        email: userEmail,

        // Billing fields
        billing_first_name:
          userData.first_name || userData.billing_first_name || "",
        billing_last_name:
          userData.last_name || userData.billing_last_name || "",
        billing_phone: userData.phone || userData.billing_phone || "",
        billing_address_1: userData.address || userData.billing_address_1 || "",
        billing_address_2:
          userData.address_2 || userData.billing_address_2 || "",
        billing_city: userData.city || userData.billing_city || "",
        billing_state: userData.state || userData.billing_state || "",
        billing_postcode:
          userData.postcode || userData.billing_postcode || userData.cep || "",
        billing_country: userData.country || userData.billing_country || "BR",
        billing_cpf: userData.cpf || userData.billing_cpf || "",
        billing_cnpj: userData.cnpj || userData.billing_cnpj || "",

        // Brazilian Market plugin compatibility
        billing_number: userData.number || userData.billing_number || "",
        billing_neighborhood:
          userData.neighborhood || userData.billing_neighborhood || "",
        billing_cellphone:
          userData.cellphone || userData.billing_cellphone || "",
        billing_birthdate:
          userData.birthdate || userData.billing_birthdate || "",
        billing_sex: userData.sex || userData.billing_sex || "",
        billing_company_cnpj:
          userData.company_cnpj || userData.billing_company_cnpj || "",
        billing_ie: userData.ie || userData.billing_ie || "",
        billing_rg: userData.rg || userData.billing_rg || "",

        // Shipping fields (copy from billing)
        shipping_first_name:
          userData.first_name ||
          userData.shipping_first_name ||
          userData.billing_first_name ||
          "",
        shipping_last_name:
          userData.last_name ||
          userData.shipping_last_name ||
          userData.billing_last_name ||
          "",
        shipping_address_1:
          userData.address ||
          userData.shipping_address_1 ||
          userData.billing_address_1 ||
          "",
        shipping_address_2:
          userData.address_2 ||
          userData.shipping_address_2 ||
          userData.billing_address_2 ||
          "",
        shipping_city:
          userData.city ||
          userData.shipping_city ||
          userData.billing_city ||
          "",
        shipping_state:
          userData.state ||
          userData.shipping_state ||
          userData.billing_state ||
          "",
        shipping_postcode:
          userData.postcode ||
          userData.shipping_postcode ||
          userData.billing_postcode ||
          userData.cep ||
          "",
        shipping_country:
          userData.country ||
          userData.shipping_country ||
          userData.billing_country ||
          "BR",
      };

      // Fill form fields
      Object.keys(fieldMappings).forEach(function (fieldName) {
        var value = fieldMappings[fieldName];
        if (value) {
          // Try different field selectors
          var selectors = [
            "#" + fieldName,
            'input[name="' + fieldName + '"]',
            'select[name="' + fieldName + '"]',
            'textarea[name="' + fieldName + '"]',
          ];

          selectors.forEach(function (selector) {
            var $field = $(selector);
            if ($field.length && !$field.val()) {
              $field.val(value).trigger("change");
              console.log("📝 Filled field", fieldName, "with value:", value);
            }
          });
        }
      });

      // Trigger events to ensure other plugins/themes update
      setTimeout(function () {
        $("form.checkout").trigger("update_checkout");
        $(document.body).trigger("updated_checkout");
        $(document.body).trigger("checkout_updated");
      }, 100);
    }

    // CRITICAL: Auto-restore cart when page loads if user is logged in
    if (LLRP_Data.is_logged_in === "1" && !LLRP_Data.is_account_page) {
      console.log(
        "🛒 CRITICAL: User is logged in, attempting auto-restore cart on page load"
      );
      setTimeout(function () {
        var restored = restoreCartAfterLogin();
        if (!restored) {
          console.log("🛒 No cart backup found to restore on page load");
        }
      }, 500);
    }

    // CRITICAL: Additional failsafe - restore cart on any navigation to checkout
    if (
      window.location.href.includes("checkout") ||
      window.location.href.includes("finalizar-compra")
    ) {
      console.log("🛒 CRITICAL: On checkout page, checking for cart backup");
      setTimeout(function () {
        restoreCartAfterLogin();
      }, 1000);
    }

    // CRITICAL: Backup cart before page unload
    $(window).on("beforeunload", function () {
      if (!LLRP_Data.is_logged_in || LLRP_Data.is_logged_in === "0") {
        console.log("🛒 CRITICAL: Page unloading, saving cart as backup");
        saveCartBeforeLogin();
      }
    });
  });
})(jQuery);
