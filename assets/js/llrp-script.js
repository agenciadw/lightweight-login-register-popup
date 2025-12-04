(function ($) {
  "use strict";

  $(function () {
    // Check if we have the data we need
    if (typeof LLRP_Data === "undefined") {
      return;
    }
    
    // ==========================================
    // CAPTCHA INTEGRATION
    // ==========================================
    
    var captchaWidgets = {
      email: null,
      login: null,
      register: null
    };
    
    var captchaRendered = {
      email: false,
      login: false,
      register: false
    };
    
    /**
     * Destroi widget de captcha existente
     */
    function destroyCaptcha(widgetName) {
      var captchaType = LLRP_Data.captcha_type || 'none';
      var widgetId = captchaWidgets[widgetName];
      
      if (widgetId === null || widgetId === undefined) {
        return;
      }
      
      try {
        if (captchaType === 'turnstile' && typeof turnstile !== 'undefined') {
          turnstile.remove(widgetId);
        } else if (captchaType.startsWith('recaptcha') && typeof grecaptcha !== 'undefined') {
          // reCAPTCHA não tem método de destruição, apenas reset
          // Vamos limpar o container manualmente
          var containerId = 'llrp-captcha-' + widgetName;
          $('#' + containerId).empty();
        }
      } catch (e) {
        safeLog('Erro ao destruir captcha:', e);
      }
      
      captchaWidgets[widgetName] = null;
      captchaRendered[widgetName] = false;
    }
    
    /**
     * Inicializa captcha no container especificado
     */
    function initCaptcha(containerId) {
      var captchaType = LLRP_Data.captcha_type || 'none';
      var siteKey = LLRP_Data.captcha_site_key || '';
      
      if (captchaType === 'none' || !siteKey) {
        return;
      }
      
      var $container = $('#' + containerId);
      if (!$container.length || !$container.is(':visible')) {
        return;
      }
      
      var widgetName = containerId.replace('llrp-captcha-', '');
      
      // Se já foi renderizado neste step, apenas faz reset
      if (captchaRendered[widgetName] && captchaWidgets[widgetName] !== null) {
        resetCaptcha(widgetName);
        return;
      }
      
      // Destrói widget existente antes de criar novo
      if (captchaWidgets[widgetName] !== null) {
        destroyCaptcha(widgetName);
      }
      
      // Limpa o container
      $container.empty();
      
      try {
        if (captchaType === 'turnstile') {
          // Cloudflare Turnstile
          if (typeof turnstile !== 'undefined') {
            captchaWidgets[widgetName] = turnstile.render($container[0], {
              sitekey: siteKey,
              theme: 'light',
              callback: function(token) {
                safeLog('✅ Turnstile validated');
              }
            });
            captchaRendered[widgetName] = true;
          }
        } else if (captchaType === 'recaptcha_v2_checkbox') {
          // reCAPTCHA v2 Checkbox
          if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
            grecaptcha.ready(function() {
              try {
                captchaWidgets[widgetName] = grecaptcha.render($container[0], {
                  sitekey: siteKey,
                  theme: 'light',
                  callback: function(response) {
                    safeLog('✅ reCAPTCHA v2 checkbox validated');
                  },
                  'expired-callback': function() {
                    resetCaptcha(widgetName);
                  },
                  'error-callback': function() {
                    safeLog('❌ reCAPTCHA v2 checkbox error');
                  }
                });
                captchaRendered[widgetName] = true;
              } catch (e) {
                console.error('Erro ao renderizar reCAPTCHA v2 checkbox:', e);
                captchaRendered[widgetName] = false;
              }
            });
          }
        } else if (captchaType === 'recaptcha_v2_invisible') {
          // reCAPTCHA v2 Invisível
          if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
            grecaptcha.ready(function() {
              captchaWidgets[widgetName] = grecaptcha.render($container[0], {
                sitekey: siteKey,
                size: 'invisible',
                callback: function(token) {
                  safeLog('✅ reCAPTCHA v2 invisible validated');
                }
              });
              captchaRendered[widgetName] = true;
            });
          }
        }
        // reCAPTCHA v3 não precisa de renderização explícita
      } catch (e) {
        console.error('Erro ao inicializar captcha:', e);
        captchaRendered[widgetName] = false;
      }
    }
    
    /**
     * Obtém token do captcha para o step especificado
     */
    function getCaptchaToken(step) {
      return new Promise(function(resolve, reject) {
        var captchaType = LLRP_Data.captcha_type || 'none';
        var siteKey = LLRP_Data.captcha_site_key || '';
        
        if (captchaType === 'none') {
          resolve('');
          return;
        }
        
        try {
          if (captchaType === 'turnstile') {
            var widgetId = captchaWidgets[step];
            if (typeof turnstile !== 'undefined' && widgetId !== null) {
              var token = turnstile.getResponse(widgetId);
              if (token) {
                resolve(token);
              } else {
                reject(new Error('Por favor, complete a verificação de segurança.'));
              }
            } else {
              reject(new Error('Captcha não inicializado.'));
            }
          } else if (captchaType === 'recaptcha_v2_checkbox') {
            // Espera o widget estar pronto
            var checkWidget = function() {
              var widgetId = captchaWidgets[step];
              
              if (typeof grecaptcha === 'undefined') {
                reject(new Error('reCAPTCHA não carregado. Recarregue a página.'));
                return;
              }
              
              if (widgetId === null || widgetId === undefined) {
                // Aguarda até 5 segundos para o widget ser inicializado
                var attempts = 0;
                var checkInterval = setInterval(function() {
                  widgetId = captchaWidgets[step];
                  attempts++;
                  
                  if (widgetId !== null && widgetId !== undefined) {
                    clearInterval(checkInterval);
                    tryGetToken(widgetId);
                  } else if (attempts >= 50) { // 50 * 100ms = 5 segundos
                    clearInterval(checkInterval);
                    reject(new Error('Captcha não inicializado. Recarregue a página.'));
                  }
                }, 100);
                return;
              }
              
              tryGetToken(widgetId);
            };
            
            var tryGetToken = function(widgetId) {
              try {
                var token = grecaptcha.getResponse(widgetId);
                
                if (token && token.length > 0) {
                  resolve(token);
                } else {
                  reject(new Error('Por favor, marque a caixa "Não sou um robô".'));
                }
              } catch (e) {
                reject(new Error('Erro ao validar captcha. Tente novamente.'));
              }
            };
            
            checkWidget();
          } else if (captchaType === 'recaptcha_v2_invisible') {
            var widgetId = captchaWidgets[step];
            if (typeof grecaptcha !== 'undefined' && widgetId !== null) {
              grecaptcha.execute(widgetId);
              // O token será obtido no callback
              var checkToken = setInterval(function() {
                var token = grecaptcha.getResponse(widgetId);
                if (token) {
                  clearInterval(checkToken);
                  resolve(token);
                }
              }, 100);
              
              // Timeout após 30 segundos
              setTimeout(function() {
                clearInterval(checkToken);
                reject(new Error('Timeout na verificação de segurança.'));
              }, 30000);
            } else {
              reject(new Error('Captcha não inicializado.'));
            }
          } else if (captchaType === 'recaptcha_v3') {
            if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
              grecaptcha.execute(siteKey, {action: 'login'}).then(function(token) {
                resolve(token);
              }).catch(function(error) {
                reject(error);
              });
            } else {
              reject(new Error('reCAPTCHA v3 não disponível.'));
            }
          }
        } catch (e) {
          reject(e);
        }
      });
    }
    
    /**
     * Reseta o captcha para permitir nova tentativa
     */
    function resetCaptcha(step) {
      var captchaType = LLRP_Data.captcha_type || 'none';
      var widgetId = captchaWidgets[step];
      
      if (widgetId === null || widgetId === undefined) {
        return;
      }
      
      try {
        if (captchaType === 'turnstile' && typeof turnstile !== 'undefined') {
          turnstile.reset(widgetId);
        } else if (captchaType.startsWith('recaptcha') && typeof grecaptcha !== 'undefined') {
          // Verifica se o widget ainda existe no DOM
          var containerId = 'llrp-captcha-' + step;
          var $container = $('#' + containerId);
          
          if ($container.length && $container.children().length > 0) {
            grecaptcha.reset(widgetId);
          } else {
            // Se o container foi limpo, marca para re-renderizar
            captchaRendered[step] = false;
            captchaWidgets[step] = null;
          }
        }
      } catch (e) {
        safeLog('Erro ao resetar captcha:', e);
        // Se falhou, marca para re-renderizar
        captchaRendered[step] = false;
        captchaWidgets[step] = null;
      }
    }
    
    /**
     * Limpa todos os captchas ao fechar o popup
     */
    function cleanupAllCaptchas() {
      destroyCaptcha('email');
      destroyCaptcha('login');
      destroyCaptcha('register');
    }
    
    // ==========================================
    // FIM CAPTCHA INTEGRATION
    // ==========================================
    
    // ==========================================
    // CLEANTALK ANTI-SPAM COMPATIBILITY
    // ==========================================
    
    /**
     * Coleta campos do CleanTalk para incluir nas requisições AJAX
     * Isso garante compatibilidade com o plugin Anti-Spam by CleanTalk
     */
    function getCleanTalkFields() {
      var ctFields = {};
      
      // Lista de campos que o CleanTalk pode adicionar
      var ctFieldNames = [
        'ct_checkjs',
        'ct_bot_detector_event_token',
        'apbct_visible_fields',
        'apbct_visible_fields_count',
        'ct_timezone',
        'ct_ps_timestamp',
        'ct_fkp_timestamp',
        'ct_pointer_data',
        'ct_has_scrolled'
      ];
      
      // Procura por campos ocultos do CleanTalk no DOM
      $('input[type="hidden"]').each(function() {
        var name = $(this).attr('name');
        if (name && (name.indexOf('ct_') === 0 || name.indexOf('apbct_') === 0)) {
          ctFields[name] = $(this).val();
        }
      });
      
      // Tenta coletar campos específicos por nome
      ctFieldNames.forEach(function(fieldName) {
        var $field = $('input[name="' + fieldName + '"]');
        if ($field.length) {
          ctFields[fieldName] = $field.val();
        }
      });
      
      // Adiciona ct_checkjs se existir a função global
      if (typeof ctSetCookie === 'function') {
        try {
          ctSetCookie('ct_checkjs', '1');
          ctFields.ct_checkjs = '1';
        } catch (e) {
          // Ignora erro
        }
      }
      
      return ctFields;
    }
    
    /**
     * Adiciona campos do CleanTalk a um objeto de dados
     */
    function addCleanTalkFields(data) {
      var ctFields = getCleanTalkFields();
      
      // Merge dos campos do CleanTalk com os dados existentes
      if (Object.keys(ctFields).length > 0) {
        $.extend(data, ctFields);
      }
      
      return data;
    }
    
    // ==========================================
    // FIM CLEANTALK COMPATIBILITY
    // ==========================================

    // Check for popup elements (may not exist on My Account page)
    var $overlay = $("#llrp-overlay");
    var $popup = $("#llrp-popup");
    var hasPopup = $overlay.length > 0 && $popup.length > 0;

    // If popup exists, ensure elements are unique
    if (hasPopup) {
      if ($overlay.length > 1) {
        $overlay = $overlay.first();
      }

      if ($popup.length > 1) {
        $popup = $popup.first();
      }
    } else {
      // No popup (My Account page) - create dummy elements to avoid errors
      $overlay = $('<div id="llrp-overlay-dummy"></div>');
      $popup = $('<div id="llrp-popup-dummy"></div>');
    }

    var savedIdentifier = "";
    var deliveryMethod = "email";
    var userEmail = ""; // Variável para armazenar o e-mail do usuário

    // Debug mode - only log in development
    var debugMode =
      typeof LLRP_Data !== "undefined" && LLRP_Data.debug_mode === "1";

    function safeLog(message, data) {
      // Debug logging disabled for production
      return;
    }

    /**
     * Soft refresh for Interactivity API compatibility
     * Replaces window.location.reload() to prevent conflicts
     */
    function softRefresh() {
      safeLog("🔄 LLRP: Using soft refresh (Interactivity API compatible)");

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

      safeLog("🔄 LLRP: Soft refresh completed");
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
        safeLog("🛒 PRIMARY BACKUP saved to localStorage");

        // Backup 2: sessionStorage (failsafe)
        sessionStorage.setItem(
          "llrp_cart_backup_failsafe",
          JSON.stringify(primaryData)
        );
        safeLog("🛒 FAILSAFE BACKUP saved to sessionStorage");

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
        safeLog("🛒 ADDITIONAL DOM BACKUP saved");

        safeLog("🛒 Cart backup completed successfully");
        return true;
      } catch (error) {
        safeLog("🚨 CRITICAL ERROR: Failed to save cart before login:", error);
        return false;
      }
    }

    function restoreCartAfterLogin() {
      safeLog("🛒 CRITICAL: Restoring cart after login - STARTED");

      try {
        // Try primary backup first
        var savedCart = localStorage.getItem("llrp_cart_backup");
        var backupSource = "localStorage";

        // Fallback to sessionStorage if primary fails
        if (!savedCart) {
          savedCart = sessionStorage.getItem("llrp_cart_backup_failsafe");
          backupSource = "sessionStorage";
          safeLog("🛒 Primary backup not found, using failsafe backup");
        }

        if (savedCart) {
          var cartData = JSON.parse(savedCart);
          safeLog("🛒 RESTORING cart from " + backupSource + ":", cartData);

          // Check if cart data is not too old (24 hours)
          if (
            cartData.timestamp &&
            Date.now() - cartData.timestamp < 24 * 60 * 60 * 1000
          ) {
            if (cartData.method === "wc_fragments" && cartData.fragments) {
              // Restore using WooCommerce fragments
              updateCartFragments(cartData.fragments);
              safeLog("🛒 Cart restored using WC fragments method");
            } else {
              // Use soft refresh to restore cart state (Interactivity API compatible)
              safeLog("🛒 Using soft refresh to restore cart state");
              setTimeout(function () {
                softRefresh();
              }, 1000);
            }

            // Clear backups after successful restoration
            localStorage.removeItem("llrp_cart_backup");
            sessionStorage.removeItem("llrp_cart_backup_failsafe");
            localStorage.removeItem("llrp_cart_dom_backup");

            safeLog("🛒 CRITICAL: Cart restoration completed successfully");
            return true;
          } else {
            safeLog("🛒 Cart data expired, clearing old backups");
            localStorage.removeItem("llrp_cart_backup");
            sessionStorage.removeItem("llrp_cart_backup_failsafe");
            localStorage.removeItem("llrp_cart_dom_backup");
          }
        } else {
          safeLog("🛒 No cart backup found to restore");
        }

        return false;
      } catch (error) {
        safeLog(
          "🚨 CRITICAL ERROR: Failed to restore cart after login:",
          error
        );
        return false;
      }
    }

    function mergeLocalCartWithUserCart() {
      safeLog("🛒 SAFE: Checking if cart merge is needed");

      // SAFE MODE: Only restore if there's a legitimate backup and current cart is empty
      var currentCartCount = $(".cart-contents-count").text() || "0";
      if (currentCartCount === "0" || parseInt(currentCartCount) === 0) {
        safeLog("🛒 SAFE: Current cart is empty, attempting restoration");
        var restored = restoreCartAfterLogin();
        if (!restored) {
          safeLog("🛒 SAFE: No local cart backup found - this is normal");
        }
      } else {
        safeLog(
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
            safeLog("LLRP: Nonce refreshed successfully");
            if (callback) callback(true);
          } else {
            safeLog("LLRP: Failed to refresh nonce");
            if (callback) callback(false);
          }
        })
        .fail(function () {
          safeLog("LLRP: AJAX failed to refresh nonce");
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
              safeLog("LLRP: Nonce error detected, trying to refresh...");

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
      try {
        if (e) e.preventDefault();

        safeLog("🔓 LLRP: Opening popup, checking login status");

        // Verificar se os elementos existem
        if ($overlay.length === 0 || $popup.length === 0) {
          return;
        }

        // CRITICAL: Save cart with dual backup before ANY login action
        safeLog("🚨 CRITICAL: About to open popup - saving cart FIRST");
        var cartSaved = saveCartBeforeLogin();
        if (!cartSaved) {
          safeLog("🚨 CRITICAL WARNING: Cart backup failed!");
        }

        // Verificação dinâmica do status de login via AJAX
        $.post(LLRP_Data.ajax_url, {
          action: "llrp_check_login_status",
          nonce: LLRP_Data.nonce,
        })
          .done(function (res) {
            if (res.success && res.data.is_logged_in) {
              // Usuário está logado, redirecionar para checkout
              safeLog("🔓 LLRP: User is logged in, redirecting to checkout");
              window.location.href = res.data.checkout_url;
            } else {
              // Usuário não está logado, mostrar popup
              safeLog("🔓 LLRP: User not logged in, showing popup");
              resetSteps();
              $overlay.removeClass("hidden");
              $popup.removeClass("hidden");

              // Hide close button if on checkout page
              hideCloseButtonIfCheckout();
            }
          })
          .fail(function (xhr, status, error) {
            // Em caso de erro, assumir que não está logado e mostrar popup
            resetSteps();
            $overlay.removeClass("hidden");
            $popup.removeClass("hidden");

            // Hide close button if on checkout page
            hideCloseButtonIfCheckout();
          });
      } catch (error) {
        // Fallback: mostrar popup mesmo com erro
        if ($overlay.length > 0 && $popup.length > 0) {
          resetSteps();
          $overlay.removeClass("hidden");
          $popup.removeClass("hidden");
          hideCloseButtonIfCheckout();
        }
      }
    }

    function closePopup() {
      if (hasPopup && $overlay.length > 0 && $popup.length > 0) {
        $overlay.addClass("hidden");
        $popup.addClass("hidden");
        // Limpa todos os captchas ao fechar
        cleanupAllCaptchas();
      }
    }

    function hidePopup() {
      closePopup();
    }

    function resetSteps() {
      if (hasPopup && $popup.length > 0) {
        $popup.find(".llrp-step").addClass("hidden");
        $popup.find(".llrp-step-email").removeClass("hidden");
        $popup.find("input").val("");
      }
      userEmail = ""; // Limpar o e-mail salvo
      clearFeedback();
      
      // Inicializa captcha no step inicial
      setTimeout(function() {
        initCaptcha('llrp-captcha-email');
      }, 200);
    }

    function clearFeedback() {
      if (hasPopup && $popup.length > 0) {
        $popup.find(".llrp-feedback").text("");
      }
    }

    function hideCloseButtonIfCheckout() {
      // Check if we're on checkout or finalizar-compra page
      var isCheckoutPage =
        window.location.href.includes("checkout") ||
        window.location.href.includes("finalizar-compra") ||
        LLRP_Data.is_checkout_page === "1";

      if (isCheckoutPage) {
        safeLog("LLRP: Hiding close button on checkout page");
        $popup.find(".llrp-close").hide();
      } else {
        safeLog("LLRP: Showing close button on non-checkout page");
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
      
      // Inicializa captcha para o step atual
      var captchaMap = {
        'email': 'llrp-captcha-email',
        'login': 'llrp-captcha-login',
        'register': 'llrp-captcha-register'
      };
      
      if (captchaMap[step]) {
        // Aguarda um pouco para garantir que o DOM foi atualizado
        setTimeout(function() {
          initCaptcha(captchaMap[step]);
        }, 100);
      }
    }

    function handleIdentifierStep() {
      clearFeedback();
      savedIdentifier = $("#llrp-identifier").val().trim();
      if (!savedIdentifier) {
        showFeedback("llrp-feedback-email", "Por favor, preencha este campo.");
        return;
      }

      // Obter token do captcha antes de enviar
      getCaptchaToken('email').then(function(captchaToken) {
        // Prepara dados e adiciona campos do CleanTalk
        var postData = {
          action: "llrp_check_user",
          identifier: savedIdentifier,
          nonce: LLRP_Data.nonce,
          captcha_token: captchaToken
        };
        postData = addCleanTalkFields(postData);
        
        $.post(LLRP_Data.ajax_url, postData).done(function (res) {
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
          var errorMsg = "Erro ao verificar usuário.";
          if (res.data && res.data.message) {
            errorMsg = res.data.message;
          }
          showFeedback("llrp-feedback-email", errorMsg);
          resetCaptcha('email');
        }
      }).fail(function() {
        showFeedback("llrp-feedback-email", "Erro de conexão. Tente novamente.");
        resetCaptcha('email');
      });
      }).catch(function(error) {
        var errorMsg = "Erro ao validar captcha.";
        if (error && error.message) {
          errorMsg = error.message;
        }
        showFeedback("llrp-feedback-email", errorMsg);
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
      var postData = {
        action: "llrp_register",
        identifier: savedIdentifier,
        email: email,
        password: password
        // Remove nonce dependency for registration
      };
      postData = addCleanTalkFields(postData);
      
      $.post(LLRP_Data.ajax_url, postData)
        .done(function (res) {
          if (res.success) {
            safeLog(
              "🛒 CRITICAL: Registration (with email) successful - restoring cart IMMEDIATELY"
            );

            // CRITICAL: Immediate cart restoration
            mergeLocalCartWithUserCart();

            // Update cart fragments if provided
            if (res.data.cart_fragments) {
              updateCartFragments(res.data.cart_fragments);
            }

            // Auto-fill user data in checkout form if available
            if (res.data.user_data) {
              // Immediate autofill
              fillCheckoutFormData(res.data.user_data);

              // Additional autofill with delay to ensure DOM updates
              setTimeout(function () {
                fillCheckoutFormData(res.data.user_data);
              }, 300);
            } else if (email) {
              // Fallback: use email if user_data not available
              var userData = {
                email: email,
                account_email: email,
                billing_email: email,
              };
              fillCheckoutFormData(userData);
              setTimeout(function () {
                syncEmailFields(email);
              }, 300);
            }

            // Check if Fluid Checkout is active and handle accordingly
            if (isFluidCheckoutActive()) {
              // For Fluid Checkout, use soft refresh instead of hard reload
              safeLog(
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
          safeLog("LLRP: Registration AJAX failed:", xhr);
          showFeedback(
            "llrp-feedback-register-email",
            "Erro de conexão. Tente novamente."
          );
        });
    }

    function handleSendCode() {
      clearFeedback();
      var postData = {
        action: "llrp_send_login_code",
        identifier: savedIdentifier,
        nonce: LLRP_Data.nonce
      };
      postData = addCleanTalkFields(postData);
      
      $.post(LLRP_Data.ajax_url, postData).done(function (res) {
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
      var postData = {
        action: "llrp_code_login",
        identifier: savedIdentifier,
        code: code,
        nonce: LLRP_Data.nonce
      };
      postData = addCleanTalkFields(postData);
      
      $.post(LLRP_Data.ajax_url, postData).done(function (res) {
        if (res.success) {
          safeLog(
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
          safeLog("LLRP: Checking Fluid Checkout status after code login...");
          if (isFluidCheckoutActive()) {
            safeLog("LLRP: Fluid Checkout detected, using soft refresh...");
            // For Fluid Checkout, use soft refresh instead of hard reload
            setTimeout(function () {
              softRefresh();
            }, 500);
          } else {
            safeLog("LLRP: Standard WooCommerce, redirecting normally...");
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
      
      // Obter token do captcha antes de enviar
      getCaptchaToken('login').then(function(captchaToken) {
        // Prepara dados e adiciona campos do CleanTalk
        var postData = {
          action: "llrp_login_with_password",
          identifier: savedIdentifier,
          password: password,
          nonce: LLRP_Data.nonce,
          captcha_token: captchaToken
        };
        postData = addCleanTalkFields(postData);
        
        $.post(LLRP_Data.ajax_url, postData).done(function (res) {
        if (res.success) {
          safeLog(
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
            // Immediate autofill
            fillCheckoutFormData(res.data.user_data);

            // Additional autofill with delay to ensure DOM updates
            setTimeout(function () {
              fillCheckoutFormData(res.data.user_data);
            }, 300);
          }

          // SAFE REDIRECT: Check if we need to redirect or stay on current page
          if (res.data.redirect && res.data.redirect !== window.location.href) {
            // Only redirect if it's a different URL
            if (
              isFluidCheckoutActive() &&
              window.location.href.includes("checkout")
            ) {
              // For Fluid Checkout on checkout page, use soft refresh to preserve checkout state
              safeLog(
                "🔄 FLUID CHECKOUT: Using soft refresh to maintain state (Interactivity API compatible)"
              );
              setTimeout(function () {
                softRefresh();
              }, 500);
            } else {
              // For other cases, redirect normally
              safeLog("🔄 REDIRECTING to:", res.data.redirect);
              window.location = res.data.redirect;
            }
          } else {
            // No redirect needed, just close popup and reload fragments
            safeLog("🔄 NO REDIRECT: Staying on current page");
            hidePopup();
            if (res.data.cart_fragments) {
              updateCartFragments(res.data.cart_fragments);
            }
          }
        } else {
          showFeedback("llrp-feedback-login", res.data.message);
          resetCaptcha('login');
        }
      }).fail(function() {
        showFeedback("llrp-feedback-login", "Erro de conexão. Tente novamente.");
        resetCaptcha('login');
      });
      }).catch(function(error) {
        var errorMsg = "Erro ao validar captcha.";
        if (error && error.message) {
          errorMsg = error.message;
        }
        showFeedback("llrp-feedback-login", errorMsg);
      });
    }

    function handleRegisterStep() {
      var password = $("#llrp-register-password").val();
      if (!password) {
        showFeedback("llrp-feedback-register", "Por favor, insira uma senha.");
        return;
      }

      // Obter token do captcha antes de enviar
      getCaptchaToken('register').then(function(captchaToken) {
        // Use direct AJAX for registration (no nonce dependency)
        var postData = {
          action: "llrp_register",
          identifier: savedIdentifier,
          password: password,
          captcha_token: captchaToken
          // Remove nonce dependency for registration
        };
        postData = addCleanTalkFields(postData);
        
        $.post(LLRP_Data.ajax_url, postData)
          .done(function (res) {
          if (res.success) {
            safeLog(
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
              // Immediate autofill
              fillCheckoutFormData(res.data.user_data);

              // Additional autofill with delay to ensure DOM updates
              setTimeout(function () {
                fillCheckoutFormData(res.data.user_data);
              }, 300);
            }

            // Check if Fluid Checkout is active and handle accordingly
            if (isFluidCheckoutActive()) {
              // For Fluid Checkout, use soft refresh for Interactivity API compatibility
              softRefresh();
            } else {
              // For standard WooCommerce, redirect normally
              window.location = res.data.redirect;
            }
          } else {
            showFeedback("llrp-feedback-register", res.data.message);
            resetCaptcha('register');
          }
        })
        .fail(function (xhr) {
          safeLog("LLRP: Registration AJAX failed:", xhr);
          showFeedback(
            "llrp-feedback-register",
            "Erro de conexão. Tente novamente."
          );
          resetCaptcha('register');
        });
      }).catch(function(error) {
        var errorMsg = "Erro ao validar captcha.";
        if (error && error.message) {
          errorMsg = error.message;
        }
        showFeedback("llrp-feedback-register", errorMsg);
      });
    }

    function handleSkipToCheckout() {
      safeLog("🚀 LLRP: User chose to skip login and go to checkout");
      
      // Close the popup
      closePopup();
      
      // Redirect to checkout page
      if (LLRP_Data.is_cart_page === "1") {
        // If we're on cart page, go to checkout
        // Try to find checkout URL from existing links
        var checkoutUrl = $('a[href*="checkout"], a[href*="finalizar-compra"]').first().attr('href');
        if (checkoutUrl) {
          window.location.href = checkoutUrl;
        } else {
          // Fallback URLs
          window.location.href = "/checkout/";
        }
      } else {
        // If we're already on checkout page, just close popup (user is already there)
        safeLog("🚀 LLRP: Already on checkout page, popup closed");
      }
    }

    // Event Binding - Interceptação mais robusta do botão de checkout
    function interceptCheckoutButton(e) {
      try {
        safeLog("🔗 LLRP: Checkout button clicked, intercepting...");

        // Verificar se devemos interceptar este elemento
        var $target = $(e.target);

        // Não interceptar se for um botão de submit em formulário de checkout
        if ($target.closest('form[name="checkout"]').length > 0) {
          return true; // Permitir comportamento normal
        }

        // Não interceptar elementos de outros plugins de checkout
        if (
          $target.closest(".mp-checkout, .fc-checkout, .stripe-checkout")
            .length > 0
        ) {
          return true; // Permitir comportamento normal
        }

        // SEMPRE prevenir o comportamento padrão primeiro
        e.preventDefault();
        e.stopPropagation();

        // Abrir o popup que fará a verificação dinâmica
        openPopup(e);

        // Retornar false para garantir que o evento não continue
        return false;
      } catch (error) {
        // Em caso de erro, permitir comportamento padrão
        return true;
      }
    }

    // Usar event delegation para garantir que funcione com elementos dinâmicos
    // Mas verificar se não é um botão de submit de formulário para evitar conflitos
    $(document).on("click.llrp", ".checkout-button", function (e) {
      // Evitar interceptar botões de formulário que podem estar processando checkout
      if (
        $(e.target).closest("form").length &&
        $(e.target).attr("type") === "submit"
      ) {
        return; // Deixar o comportamento padrão para submits de formulário
      }
      return interceptCheckoutButton(e);
    });

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
          // Verificar se não é um elemento de um plugin de checkout diferente
          if ($(e.target).closest(".mp-custom-checkout").length) {
            return; // Não interferir com outros plugins de checkout
          }
          return interceptCheckoutButton(e);
        }
      }
    );
    // More popup event listeners (only if popup exists)
    if (hasPopup) {
      $popup.on("click", ".llrp-close", closePopup);
      $popup.on("click", ".llrp-back", resetSteps);
      $popup.on("click", "#llrp-email-submit", handleIdentifierStep);
      $popup.on("click", "#llrp-password-submit", handleLoginStep);
      $popup.on("click", "#llrp-register-submit", handleRegisterStep);
      $popup.on("click", "#llrp-skip-to-checkout", handleSkipToCheckout);
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
    }

    function handleLostStep() {
      var email = $("#llrp-lost-email").val().trim();
      if (!email) {
        showFeedback("llrp-feedback-lost", "Por favor, insira seu e-mail.");
        return;
      }
      var postData = {
        action: "llrp_lostpassword",
        email: email,
        nonce: LLRP_Data.nonce
      };
      postData = addCleanTalkFields(postData);
      
      $.post(LLRP_Data.ajax_url, postData).done(function (res) {
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

    // Popup event listeners (only if popup exists)
    if (hasPopup) {
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

      // Social Login Event Bindings for popup
      $popup.on(
        "click",
        "#llrp-google-login, #llrp-google-login-initial",
        function(e) {
          safeLog("LLRP: Google login button clicked in popup");
          handleGoogleLogin(e);
        }
      );
      $popup.on(
        "click",
        "#llrp-facebook-login, #llrp-facebook-login-initial",
        handleFacebookLogin
      );
    }

    // Initialize Social Login SDKs
    if (LLRP_Data.google_login_enabled === "1" && LLRP_Data.google_client_id) {
      var googleCheckAttempts = 0;
      var googleCheckInterval = setInterval(function() {
        googleCheckAttempts++;
        
        if (typeof google !== "undefined" && google.accounts && google.accounts.oauth2) {
          clearInterval(googleCheckInterval);
          initializeSocialLogin();
        } else if (googleCheckAttempts > 50) {
          clearInterval(googleCheckInterval);
          initializeSocialLogin();
        }
      }, 100);
    } else {
      initializeSocialLogin();
    }

    // Social Login Event Bindings for My Account page
    
    $(document).on(
      "click",
      "#llrp-google-login-account, #llrp-google-register-account",
      function(e) {
        // If Google SDK is not loaded yet, wait for it
        if (typeof google === "undefined") {
          e.preventDefault();
          
          var waitAttempts = 0;
          var waitInterval = setInterval(function() {
            waitAttempts++;
            
            if (typeof google !== "undefined" && google.accounts && google.accounts.oauth2) {
              clearInterval(waitInterval);
              handleMyAccountGoogleLogin(e);
            } else if (waitAttempts > 30) {
              clearInterval(waitInterval);
              alert("O SDK do Google não carregou. Por favor, recarregue a página e tente novamente.");
            }
          }, 200);
          
          return;
        }
        
        handleMyAccountGoogleLogin(e);
      }
    );
    
    $(document).on(
      "click",
      "#llrp-facebook-login-account, #llrp-facebook-register-account",
      function(e) {
        handleMyAccountFacebookLogin(e);
      }
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

    // Handle Google login specifically for My Account page
    function handleMyAccountGoogleLogin(e) {
      e.preventDefault();
      
      if (LLRP_Data.google_login_enabled !== "1") {
        alert("Login com Google não está habilitado nas configurações.");
        return;
      }

      if (!LLRP_Data.google_client_id) {
        alert("Google Client ID não configurado.");
        return;
      }

      if (typeof google === "undefined") {
        alert("Google SDK não carregado. Tente novamente.");
        return;
      }

      if (!google.accounts || !google.accounts.oauth2) {
        alert("Google OAuth2 não disponível. Verifique a configuração.");
        return;
      }

      google.accounts.oauth2
        .initTokenClient({
          client_id: LLRP_Data.google_client_id,
          scope: "email profile",
          callback: (response) => {
            if (response.access_token) {
              fetch("https://www.googleapis.com/oauth2/v2/userinfo", {
                headers: {
                  Authorization: "Bearer " + response.access_token,
                },
              })
                .then((response) => {
                  return response.json();
                })
                .then((userInfo) => {
                  if (!userInfo.email) {
                    alert("Google não forneceu e-mail.");
                    return;
                  }

                  processGoogleLogin(userInfo);
                })
                .catch((error) => {
                  alert("Erro ao obter informações do Google.");
                });
            } else {
              alert("Login com Google cancelado.");
            }
          },
          error_callback: (error) => {
            alert("Erro no login com Google. Tente novamente.");
          },
        })
        .requestAccessToken();
    }

    // Handle Facebook login specifically for My Account page
    function handleMyAccountFacebookLogin(e) {
      e.preventDefault();
      
      if (LLRP_Data.facebook_login_enabled !== "1") {
        alert("Login com Facebook não está habilitado nas configurações.");
        return;
      }

      if (!LLRP_Data.facebook_app_id) {
        alert("Facebook App ID não configurado.");
        return;
      }

      if (typeof FB === "undefined") {
        alert("Facebook SDK não carregado. Tente novamente.");
        return;
      }

      FB.login(function(response) {
        if (response.authResponse) {
          $.post(LLRP_Data.ajax_url, {
            action: "llrp_facebook_login",
            access_token: response.authResponse.accessToken,
            nonce: LLRP_Data.nonce,
            from_account: LLRP_Data.is_account_page || "0",
          })
          .done(function (res) {
            if (res.success) {
              setTimeout(function() {
                window.location.reload();
              }, 500);
            } else {
              alert(res.data.message || "Erro ao fazer login com Facebook.");
            }
          })
          .fail(function (xhr) {
            alert("Erro de conexão. Tente novamente.");
          });
        } else {
          alert("Login com Facebook cancelado.");
        }
      }, {scope: 'email'});
    }

    function handleGoogleLogin(e) {
      e.preventDefault();
      clearFeedback();

      // Debug: Check if Google login is enabled
      if (LLRP_Data.google_login_enabled !== "1") {
        safeLog("LLRP: Google login not enabled in settings");
        showFeedback(
          "llrp-feedback-email",
          "Login com Google não está habilitado nas configurações."
        );
        return;
      }

      // Debug: Check if Google Client ID is available
      if (!LLRP_Data.google_client_id) {
        safeLog("LLRP: Google Client ID not configured");
        showFeedback(
          "llrp-feedback-email",
          "Google Client ID não configurado."
        );
        return;
      }

      safeLog("LLRP: Google login attempt - Client ID: " + LLRP_Data.google_client_id);

      // Check if Google SDK is loaded
      if (typeof google === "undefined") {
        safeLog("LLRP: Google SDK not loaded");
        showFeedback(
          "llrp-feedback-email",
          "Google SDK não carregado. Tente novamente."
        );
        return;
      }

      // Check if OAuth2 is available
      if (!google.accounts || !google.accounts.oauth2) {
        safeLog("LLRP: Google OAuth2 not available");
        showFeedback(
          "llrp-feedback-email",
          "Google OAuth2 não disponível. Verifique a configuração."
        );
        return;
      }

      safeLog("LLRP: Starting Google OAuth2 flow");
      safeLog("LLRP: Google OAuth2 available: " + (google.accounts && google.accounts.oauth2 ? "YES" : "NO"));

      // Use Google OAuth popup flow
      google.accounts.oauth2
        .initTokenClient({
          client_id: LLRP_Data.google_client_id,
          scope: "email profile",
          callback: (response) => {
            safeLog("LLRP: Google OAuth callback received");
            // Google OAuth callback received (details removed for security)
            if (response.access_token) {
              // Access token received, fetching user info
              // Get user info using the access token
              fetch("https://www.googleapis.com/oauth2/v2/userinfo", {
                headers: {
                  Authorization: "Bearer " + response.access_token,
                },
              })
                .then((response) => {
                  // User info fetch response (status removed for security)
                  return response.json();
                })
                .then((userInfo) => {
                  // User info received from Google (data removed for security)

                  // Validate user info
                  if (!userInfo.email) {
                    safeLog("LLRP: No email in user info");
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
                  safeLog("LLRP: Error fetching user info:", error);
                  showFeedback(
                    "llrp-feedback-email",
                    "Erro ao obter informações do Google."
                  );
                });
            } else {
              safeLog("LLRP: Google login cancelled or failed");
              showFeedback(
                "llrp-feedback-email",
                "Login com Google cancelado."
              );
            }
          },
          error_callback: (error) => {
            safeLog("Google OAuth error:", error);
            showFeedback(
              "llrp-feedback-email",
              "Erro no login com Google. Tente novamente."
            );
          },
        })
        .requestAccessToken();
    }

    function processGoogleLogin(userInfo) {
      // Processing Google login (sensitive data removed from production logs)

      $.post(LLRP_Data.ajax_url, {
        action: "llrp_google_login",
        user_info: JSON.stringify(userInfo),
        nonce: LLRP_Data.nonce,
        from_account: LLRP_Data.is_account_page || "0",
      })
        .done(function (res) {
          safeLog("LLRP: AJAX response:", res);
          if (res.success) {
            safeLog("LLRP: Login successful, redirecting...");

            safeLog(
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
              // On My Account page, reload the page to show logged-in state
              safeLog("🔄 MY ACCOUNT: Reloading page to show logged-in state");
              setTimeout(function() {
                window.location.reload();
              }, 500);
            } else if (isFluidCheckoutActive()) {
              // For Fluid Checkout, use soft refresh for Interactivity API compatibility
              safeLog(
                "🔄 FLUID CHECKOUT: Using soft refresh for Interactivity API compatibility"
              );
              softRefresh();
            } else {
              // On cart page, redirect to checkout
              window.location.href = res.data.redirect;
            }
          } else {
            safeLog("LLRP: Login failed with message:", res.data.message);

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
          safeLog("LLRP: AJAX request failed");
          safeLog("LLRP: Status:", xhr.status);
          safeLog("LLRP: Response:", xhr.responseText);
          safeLog("LLRP: Full xhr object:", xhr);

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
                  safeLog(
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
                    // On My Account page, reload the page to show logged-in state
                    safeLog("🔄 MY ACCOUNT: Reloading page to show logged-in state");
                    setTimeout(function() {
                      window.location.reload();
                    }, 500);
                  } else if (isFluidCheckoutActive()) {
                    // For Fluid Checkout, use soft refresh for Interactivity API compatibility
                    safeLog(
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

    // Plugin initialization completed
    safeLog("LLRP: Plugin initialized with data:", LLRP_Data);

    // Auto-show popup if user accesses checkout page directly without being logged in
    // Mas não mostrar se checkout de convidado estiver habilitado
    if (LLRP_Data.is_checkout_page === "1" && LLRP_Data.is_logged_in !== "1" && LLRP_Data.guest_checkout_enabled !== "1") {
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

      safeLog("LLRP: Fluid Checkout detection:", {
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

      safeLog("LLRP: Updating cart fragments:", fragments);

      // Check if Interactivity API or WooCommerce Blocks are active
      var isInteractivityActive =
        (typeof wp !== "undefined" && wp.interactivity) ||
        $(".wp-block-woocommerce-mini-cart").length > 0 ||
        $(".wc-block-mini-cart").length > 0 ||
        $(".wc-block-cart").length > 0;

      if (isInteractivityActive) {
        safeLog("LLRP: Using Interactivity API/Blocks approach");

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
        safeLog("LLRP: Using traditional fragment update");

        // Update each fragment (traditional method)
        $.each(fragments, function (selector, content) {
          if (selector && content !== undefined) {
            var $element = $(selector);
            if ($element.length) {
              $element.html(content);
              safeLog("LLRP: Updated fragment:", selector);
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

      safeLog("LLRP: Cart fragments update completed");
    }


    /**
     * Auto-fill checkout form with user data + CRITICAL email sync
     */
    /**
     * Sync email fields to ensure consistency across all email inputs
     * @param {string} email - Email address to sync
     */
    function syncEmailFields(email) {
      if (!email) return;

      var emailSelectors = [
        'input[name="email"]',
        'input[id="email"]',
        'input[name="billing_email"]',
        'input[id="billing_email"]',
        'input[name="account_email"]',
        'input[id="account_email"]',
        'input[type="email"]',
      ];

      emailSelectors.forEach(function (selector) {
        var $field = $(selector);
        if ($field.length > 0) {
          $field.val(email);
          $field.trigger("change").trigger("input").trigger("keyup");
        }
      });
    }

    /**
     * Auto-fill checkout form with user data
     * @param {Object} userData - User data object
     */
    function fillCheckoutFormData(userData) {
      if (!userData || typeof userData !== "object") {
        return;
      }

      var userEmail = userData.email || userData.billing_email || userData.account_email || "";
      
      if (userEmail) {
        syncEmailFields(userEmail);
      }

      var fieldMappings = getFieldMappings(userData, userEmail);
      fillFormFields(fieldMappings);
      triggerCheckoutEvents(userEmail);
    }

    /**
     * Get field mappings for checkout form
     * @param {Object} userData - User data
     * @param {string} userEmail - User email
     * @returns {Object} Field mappings
     */
    function getFieldMappings(userData, userEmail) {
      return {
        // Email fields
        account_email: userEmail,
        billing_email: userEmail,
        email: userEmail,

        // Billing fields
        billing_first_name: userData.first_name || userData.billing_first_name || "",
        billing_last_name: userData.last_name || userData.billing_last_name || "",
        billing_phone: userData.phone || userData.billing_phone || "",
        billing_address_1: userData.address || userData.billing_address_1 || "",
        billing_address_2: userData.address_2 || userData.billing_address_2 || "",
        billing_city: userData.city || userData.billing_city || "",
        billing_state: userData.state || userData.billing_state || "",
        billing_postcode: userData.postcode || userData.billing_postcode || userData.cep || "",
        billing_country: userData.country || userData.billing_country || "BR",
        billing_cpf: userData.cpf || userData.billing_cpf || "",
        billing_cnpj: userData.cnpj || userData.billing_cnpj || "",

        // Brazilian Market plugin compatibility
        billing_number: userData.number || userData.billing_number || "",
        billing_neighborhood: userData.neighborhood || userData.billing_neighborhood || "",
        billing_cellphone: userData.cellphone || userData.billing_cellphone || "",
        billing_birthdate: userData.birthdate || userData.billing_birthdate || "",
        billing_sex: userData.sex || userData.billing_sex || "",
        billing_company_cnpj: userData.company_cnpj || userData.billing_company_cnpj || "",
        billing_ie: userData.ie || userData.billing_ie || "",
        billing_rg: userData.rg || userData.billing_rg || "",

        // Shipping fields (copy from billing)
        shipping_first_name: userData.first_name || userData.shipping_first_name || userData.billing_first_name || "",
        shipping_last_name: userData.last_name || userData.shipping_last_name || userData.billing_last_name || "",
        shipping_address_1: userData.address || userData.shipping_address_1 || userData.billing_address_1 || "",
        shipping_address_2: userData.address_2 || userData.shipping_address_2 || userData.billing_address_2 || "",
        shipping_city: userData.city || userData.shipping_city || userData.billing_city || "",
        shipping_state: userData.state || userData.shipping_state || userData.billing_state || "",
        shipping_postcode: userData.postcode || userData.shipping_postcode || userData.billing_postcode || userData.cep || "",
        shipping_country: userData.country || userData.shipping_country || userData.billing_country || "BR",
      };
    }

    /**
     * Fill form fields with mapped data
     * @param {Object} fieldMappings - Field mappings object
     */
    function fillFormFields(fieldMappings) {
      Object.keys(fieldMappings).forEach(function (fieldName) {
        var value = fieldMappings[fieldName];
        if (value) {
          var selectors = [
            "#" + fieldName,
            'input[name="' + fieldName + '"]',
            'select[name="' + fieldName + '"]',
            'textarea[name="' + fieldName + '"]',
          ];

          selectors.forEach(function (selector) {
            var $field = $(selector);
            if ($field.length && !$field.val()) {
              $field.val(value);
              $field.trigger("change").trigger("input").trigger("keyup").trigger("blur");
            }
          });
        }
      });
    }

    /**
     * Trigger checkout events and email sync
     * @param {string} userEmail - User email
     */
    function triggerCheckoutEvents(userEmail) {
      setTimeout(function () {
        if (userEmail) {
          syncEmailFields(userEmail);
        }
      }, 100);

      setTimeout(function () {
        $("form.checkout").trigger("update_checkout");
        $(document.body).trigger("updated_checkout");
        $(document.body).trigger("checkout_updated");
      }, 100);
    }

    // CRITICAL: Auto-restore cart when page loads if user is logged in
    if (LLRP_Data.is_logged_in === "1" && !LLRP_Data.is_account_page) {
      safeLog(
        "🛒 CRITICAL: User is logged in, attempting auto-restore cart on page load"
      );
      setTimeout(function () {
        var restored = restoreCartAfterLogin();
        if (!restored) {
          safeLog("🛒 No cart backup found to restore on page load");
        }
      }, 500);
    }

    // CRITICAL: Additional failsafe - restore cart on any navigation to checkout
    if (
      window.location.href.includes("checkout") ||
      window.location.href.includes("finalizar-compra")
    ) {
      safeLog("🛒 CRITICAL: On checkout page, checking for cart backup");
      setTimeout(function () {
        restoreCartAfterLogin();
      }, 1000);
    }

    // CRITICAL: Backup cart before page unload
    $(window).on("beforeunload", function () {
      if (!LLRP_Data.is_logged_in || LLRP_Data.is_logged_in === "0") {
        safeLog("🛒 CRITICAL: Page unloading, saving cart as backup");
        saveCartBeforeLogin();
      }
    });
  });
})(jQuery);
