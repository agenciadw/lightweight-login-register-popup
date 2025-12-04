jQuery(document).ready(function ($) {
  "use strict";

  // ==========================================
  // Color Picker Initialization
  // ==========================================

  $(".llrp-color-picker").each(function () {
    var $input = $(this);
    var defaultColor = $input.data("default-color") || "#ffffff";

    $input.wpColorPicker({
      defaultColor: defaultColor,
      change: function (event, ui) {
        // Atualiza preview
        $input
          .closest(".llrp-color-input-wrapper")
          .find(".llrp-color-preview")
          .css("background-color", ui.color.toString());
      },
      clear: function () {
        // Reseta para cor padrão
        $input
          .closest(".llrp-color-input-wrapper")
          .find(".llrp-color-preview")
          .css("background-color", defaultColor);
      },
    });
  });

  // Overlay color (aceita rgba)
  $(".llrp-overlay-color").on("change", function () {
    var color = $(this).val();
    $(this)
      .closest(".llrp-color-input-wrapper")
      .find(".llrp-color-preview")
      .css("background-color", color);
  });

  // ==========================================
  // Captcha Type Switcher
  // ==========================================

  $("#llrp_captcha_type").on("change", function () {
    var captchaType = $(this).val();

    // Oculta todas as seções
    $(".llrp-captcha-section").hide();

    // Mostra seção relevante
    if (captchaType === "turnstile") {
      $("#llrp-turnstile-section").fadeIn(300);
      $("#llrp-recaptcha-v3-score").hide();
    } else if (captchaType.startsWith("recaptcha")) {
      $("#llrp-recaptcha-section").fadeIn(300);

      // Mostra campo de score apenas para v3
      if (captchaType === "recaptcha_v3") {
        $("#llrp-recaptcha-v3-score").fadeIn(300);
      } else {
        $("#llrp-recaptcha-v3-score").hide();
      }
    }
  });

  // Trigger initial state
  $("#llrp_captcha_type").trigger("change");

  // ==========================================
  // Form Validation
  // ==========================================

  $("form.llrp-admin-form").on("submit", function (e) {
    var errors = [];

    // Validação do Google Login
    var googleEnabled = $('input[name="llrp_google_login_enabled"]').is(
      ":checked"
    );
    if (googleEnabled) {
      var googleClientId = $('input[name="llrp_google_client_id"]').val();
      if (!googleClientId) {
        errors.push("Google Client ID é obrigatório quando o login está ativo");
      }
    }

    // Validação do Facebook Login
    var facebookEnabled = $('input[name="llrp_facebook_login_enabled"]').is(
      ":checked"
    );
    if (facebookEnabled) {
      var facebookAppId = $('input[name="llrp_facebook_app_id"]').val();
      if (!facebookAppId) {
        errors.push(
          "Facebook App ID é obrigatório quando o login está ativo"
        );
      }
    }

    // Validação do Turnstile
    var captchaType = $("#llrp_captcha_type").val();
    if (captchaType === "turnstile") {
      var turnstileSiteKey = $('input[name="llrp_turnstile_site_key"]').val();
      var turnstileSecretKey = $(
        'input[name="llrp_turnstile_secret_key"]'
      ).val();

      if (!turnstileSiteKey || !turnstileSecretKey) {
        errors.push(
          "Turnstile Site Key e Secret Key são obrigatórios para Cloudflare Turnstile"
        );
      }
    }

    // Validação do reCAPTCHA
    if (captchaType.startsWith("recaptcha")) {
      var recaptchaSiteKey = $('input[name="llrp_recaptcha_site_key"]').val();
      var recaptchaSecretKey = $(
        'input[name="llrp_recaptcha_secret_key"]'
      ).val();

      if (!recaptchaSiteKey || !recaptchaSecretKey) {
        errors.push(
          "reCAPTCHA Site Key e Secret Key são obrigatórios para Google reCAPTCHA"
        );
      }
    }

    // Mostra erros se houver
    if (errors.length > 0) {
      e.preventDefault();

      var errorHtml =
        '<div class="notice notice-error is-dismissible"><p><strong>Erros encontrados:</strong></p><ul>';
      errors.forEach(function (error) {
        errorHtml += "<li>" + error + "</li>";
      });
      errorHtml += "</ul></div>";

      $(".llrp-admin-wrap").prepend(errorHtml);

      // Scroll to top
      $("html, body").animate({ scrollTop: 0 }, 300);

      // Auto dismiss
      setTimeout(function () {
        $(".notice-error").fadeOut(300, function () {
          $(this).remove();
        });
      }, 8000);

      return false;
    }
  });

  // ==========================================
  // Save Success Notice
  // ==========================================

  // Verifica se foi salvo com sucesso
  if (window.location.href.indexOf("settings-updated=true") > -1) {
    // Adiciona notice de sucesso
    var successNotice =
      '<div class="notice notice-success is-dismissible" style="animation: fadeIn 0.3s ease;"><p><strong>✅ Configurações salvas com sucesso!</strong></p></div>';
    $(".llrp-admin-wrap h1").after(successNotice);

    // Auto dismiss
    setTimeout(function () {
      $(".notice-success").fadeOut(300, function () {
        $(this).remove();
      });
    }, 3000);
  }

  // ==========================================
  // Dismiss Notice Handler
  // ==========================================

  $(document).on("click", ".notice-dismiss", function () {
    $(this).parent(".notice").fadeOut(300, function () {
      $(this).remove();
    });
  });

  // ==========================================
  // Color Preview Click
  // ==========================================

  $(".llrp-color-preview").on("click", function () {
    $(this)
      .closest(".llrp-color-input-wrapper")
      .find("input")
      .wpColorPicker("open");
  });

  // ==========================================
  // Auto-save indicator
  // ==========================================

  var originalValues = {};

  // Salva valores originais
  $("form.llrp-admin-form")
    .find("input, select, textarea")
    .each(function () {
      var $field = $(this);
      var name = $field.attr("name");
      if (name) {
        if ($field.is(":checkbox")) {
          originalValues[name] = $field.is(":checked");
        } else {
          originalValues[name] = $field.val();
        }
      }
    });

  // Detecta mudanças
  $("form.llrp-admin-form")
    .find("input, select, textarea")
    .on("change", function () {
      var hasChanges = false;

      $("form.llrp-admin-form")
        .find("input, select, textarea")
        .each(function () {
          var $field = $(this);
          var name = $field.attr("name");
          if (name && originalValues[name] !== undefined) {
            var currentValue;
            if ($field.is(":checkbox")) {
              currentValue = $field.is(":checked");
            } else {
              currentValue = $field.val();
            }

            if (currentValue !== originalValues[name]) {
              hasChanges = true;
              return false;
            }
          }
        });

      // Adiciona indicador visual se houver mudanças
      if (hasChanges) {
        $(".llrp-save-bar").addClass("has-changes");
        if (!$(".llrp-unsaved-notice").length) {
          $(".llrp-save-bar .button-primary").before(
            '<span class="llrp-unsaved-notice" style="color: #d63638; margin-right: 10px;">● Alterações não salvas</span>'
          );
        }
      } else {
        $(".llrp-save-bar").removeClass("has-changes");
        $(".llrp-unsaved-notice").remove();
      }
    });

  // ==========================================
  // Prevent accidental navigation
  // ==========================================

  var formChanged = false;
  var formSubmitted = false;

  $("form.llrp-admin-form")
    .find("input, select, textarea")
    .on("change", function () {
      if (!formSubmitted) {
        formChanged = true;
      }
    });

  $(window).on("beforeunload", function (e) {
    if (formChanged && !formSubmitted) {
      // Padrão moderno para beforeunload
      e.preventDefault();
      e.returnValue = "";
      return "";
    }
  });

  $("form.llrp-admin-form").on("submit", function () {
    formChanged = false;
    formSubmitted = true;
  });
  
  // Reset flag após página carregar (significa que salvou com sucesso)
  if (window.location.href.indexOf("settings-updated=true") > -1) {
    formSubmitted = true;
    formChanged = false;
  }
  
  // ==========================================
  // Captcha Configuration Test
  // ==========================================
  
  $("#llrp-test-captcha-btn").on("click", function () {
    var $btn = $(this);
    var $result = $("#llrp-captcha-test-result");
    var captchaType = $("#llrp_captcha_type").val();
    
    // Disable button
    $btn.prop("disabled", true).text("🔄 Testando...");
    
    // Get keys based on type
    var siteKey, secretKey, score;
    if (captchaType === "turnstile") {
      siteKey = $("#llrp_turnstile_site_key").val();
      secretKey = $("#llrp_turnstile_secret_key").val();
    } else if (captchaType.indexOf("recaptcha") === 0) {
      siteKey = $("#llrp_recaptcha_site_key").val();
      secretKey = $("#llrp_recaptcha_secret_key").val();
      score = $("#llrp_recaptcha_v3_score").val();
    }
    
    // Validate fields
    var errors = [];
    if (!siteKey || siteKey.trim() === "") {
      errors.push("❌ Site Key está vazia");
    }
    if (!secretKey || secretKey.trim() === "") {
      errors.push("❌ Secret Key está vazia");
    }
    
    if (errors.length > 0) {
      $result
        .show()
        .css({ background: "#fcf3cd", border: "1px solid #dba617", color: "#7a5d00" })
        .html(
          "<strong>⚠️ Erro de Validação:</strong><br>" + errors.join("<br>")
        );
      $btn.prop("disabled", false).text("🔍 Testar Configuração");
      return;
    }
    
    // Show configuration summary
    var html =
      "<strong>✅ Configuração Válida!</strong><br><br>" +
      "<table style='width: 100%; border-collapse: collapse;'>" +
      "<tr><td style='padding: 5px; border-bottom: 1px solid #ddd;'><strong>Tipo:</strong></td><td style='padding: 5px; border-bottom: 1px solid #ddd;'>" +
      captchaType +
      "</td></tr>" +
      "<tr><td style='padding: 5px; border-bottom: 1px solid #ddd;'><strong>Site Key:</strong></td><td style='padding: 5px; border-bottom: 1px solid #ddd;'><code style='font-size: 11px;'>" +
      siteKey.substring(0, 20) +
      "...</code> (" +
      siteKey.length +
      " chars)</td></tr>" +
      "<tr><td style='padding: 5px; border-bottom: 1px solid #ddd;'><strong>Secret Key:</strong></td><td style='padding: 5px; border-bottom: 1px solid #ddd;'><code style='font-size: 11px;'>" +
      secretKey.substring(0, 20) +
      "...</code> (" +
      secretKey.length +
      " chars)</td></tr>";
    
    if (captchaType === "recaptcha_v3" && score) {
      html +=
        "<tr><td style='padding: 5px;'><strong>Score Mínimo:</strong></td><td style='padding: 5px;'>" +
        score +
        "</td></tr>";
    }
    
    html += "</table>";
    html +=
      "<br><div style='background: #f0f6fc; padding: 10px; border-radius: 4px; border-left: 4px solid #0d5cd2;'>";
    html +=
      "<p style='margin: 0;'><strong>📋 Próximos Passos:</strong></p>";
    html += "<ol style='margin: 10px 0 0 20px; padding: 0;'>";
    html +=
      "<li>Salve as alterações</li>";
    html +=
      "<li>Abra o frontend do site</li>";
    html +=
      "<li>Teste o login/registro</li>";
    html +=
      "<li>Verifique o Console (F12) e o arquivo <code>wp-content/debug.log</code></li>";
    html += "</ol>";
    html += "</div>";
    
    $result
      .show()
      .css({ background: "#edfaef", border: "1px solid #69bd83", color: "#0f5323" })
      .html(html);
    
    $btn.prop("disabled", false).text("🔍 Testar Configuração");
  });
});
