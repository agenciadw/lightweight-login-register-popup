<?php
/**
 * Script de Debug para Usuários Importados
 * 
 * Execute este arquivo diretamente no navegador: https://seusite.com/wp-content/plugins/lightweight-login-register-popup/debug-imported-users.php
 * 
 * IMPORTANTE: Remova este arquivo após o debug por questões de segurança!
 */

// Tentar carregar WordPress de diferentes localizações
$wp_load_paths = [
    '../../../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../wp-load.php',
    '../../wp-load.php',
    dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('<h1>Erro: Não foi possível carregar o WordPress</h1><p>Por favor, ajuste o caminho manualmente no arquivo debug-imported-users.php</p>');
}

// Verificar se usuário é admin
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
    exit;
}

if (!current_user_can('manage_options')) {
    die('<h1>Acesso Negado</h1><p>Você precisa ser administrador para acessar esta página.</p>');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug - Usuários Importados</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            margin: 20px;
            background: #f0f0f1;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .status-box {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .status-success {
            background: #e8f5e8;
            border-left: 4px solid #2e7d32;
            color: #1e4620;
        }
        .status-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .status-error {
            background: #ffebee;
            border-left: 4px solid #d32f2f;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f6f7f7;
            font-weight: 600;
            color: #1d2327;
        }
        tr:hover {
            background: #f6f7f7;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-yes {
            background: #d4edda;
            color: #155724;
        }
        .badge-no {
            background: #f8d7da;
            color: #721c24;
        }
        code {
            background: #f6f7f7;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: Monaco, Consolas, monospace;
            font-size: 13px;
        }
        .sql-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: Monaco, Consolas, monospace;
            font-size: 13px;
            margin: 15px 0;
        }
        .button {
            background: #2271b1;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .button:hover {
            background: #135e96;
        }
        .button-secondary {
            background: #6c757d;
        }
        .button-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug - Sistema de Usuários Importados</h1>
        
        <?php
        // 1. Verificar se a opção está ativada
        $force_imported = get_option('llrp_password_force_imported_users');
        $expiration_enabled = get_option('llrp_password_expiration_enabled');
        $inactivity_enabled = get_option('llrp_password_expiration_inactivity_enabled');
        
        echo '<h2>1️⃣ Configurações do Plugin</h2>';
        ?>
        
        <table>
            <tr>
                <th>Opção</th>
                <th>Status</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td><strong>Forçar Troca para Usuários Importados</strong></td>
                <td>
                    <?php if ($force_imported): ?>
                        <span class="badge badge-yes">✓ ATIVO</span>
                    <?php else: ?>
                        <span class="badge badge-no">✗ DESATIVADO</span>
                    <?php endif; ?>
                </td>
                <td><code>llrp_password_force_imported_users = <?php echo $force_imported ? '1' : '0'; ?></code></td>
            </tr>
            <tr>
                <td><strong>Expiração por Tempo</strong></td>
                <td>
                    <?php if ($expiration_enabled): ?>
                        <span class="badge badge-yes">✓ ATIVO</span>
                    <?php else: ?>
                        <span class="badge badge-no">✗ DESATIVADO</span>
                    <?php endif; ?>
                </td>
                <td><code>llrp_password_expiration_enabled = <?php echo $expiration_enabled ? '1' : '0'; ?></code></td>
            </tr>
            <tr>
                <td><strong>Expiração por Inatividade</strong></td>
                <td>
                    <?php if ($inactivity_enabled): ?>
                        <span class="badge badge-yes">✓ ATIVO</span>
                    <?php else: ?>
                        <span class="badge badge-no">✗ DESATIVADO</span>
                    <?php endif; ?>
                </td>
                <td><code>llrp_password_expiration_inactivity_enabled = <?php echo $inactivity_enabled ? '1' : '0'; ?></code></td>
            </tr>
        </table>
        
        <?php if (!$force_imported): ?>
            <div class="status-error">
                <strong>⚠️ PROBLEMA IDENTIFICADO:</strong> A opção "Forçar Troca para Usuários Importados" está DESATIVADA.<br>
                <strong>Solução:</strong> Vá em <strong>WooCommerce → Login Popup → Avançado → Expiração de Senha</strong> e ative o toggle.
            </div>
        <?php endif; ?>
        
        <?php
        // 2. Buscar usuários SEM a meta _llrp_last_password_change
        global $wpdb;
        
        $imported_users = $wpdb->get_results("
            SELECT u.ID, u.user_login, u.user_email, u.user_registered
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
            WHERE um.meta_value IS NULL
            ORDER BY u.user_registered DESC
            LIMIT 50
        ");
        
        $total_imported = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
            WHERE um.meta_value IS NULL
        ");
        
        echo '<h2>2️⃣ Usuários Identificados como "Importados"</h2>';
        ?>
        
        <div class="status-box <?php echo $total_imported > 0 ? 'status-success' : 'status-warning'; ?>">
            <strong>Total de usuários sem data de troca de senha:</strong> <?php echo $total_imported; ?> usuários<br>
            <?php if ($total_imported > 0): ?>
                <em>Estes usuários serão forçados a trocar a senha no próximo login (se a opção estiver ativa).</em>
            <?php else: ?>
                <em>Todos os usuários já possuem data de troca de senha registrada.</em>
            <?php endif; ?>
        </div>
        
        <?php if (count($imported_users) > 0): ?>
            <p>Mostrando os 50 usuários mais recentes:</p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Registrado em</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imported_users as $user): ?>
                        <tr>
                            <td><strong><?php echo $user->ID; ?></strong></td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user->user_registered)); ?></td>
                            <td><span class="badge badge-no">SEM DATA</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?php
        // 3. Buscar usuários COM a meta _llrp_last_password_change (exemplo)
        $users_with_date = $wpdb->get_results("
            SELECT u.ID, u.user_login, u.user_email, um.meta_value as last_change
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
            ORDER BY um.meta_value DESC
            LIMIT 10
        ");
        
        echo '<h2>3️⃣ Usuários com Data de Troca (Exemplo dos 10 mais recentes)</h2>';
        ?>
        
        <?php if (count($users_with_date) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Última Troca</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_with_date as $user): ?>
                        <tr>
                            <td><strong><?php echo $user->ID; ?></strong></td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo date('d/m/Y H:i', $user->last_change); ?></td>
                            <td><span class="badge badge-yes">COM DATA</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="status-warning">
                Nenhum usuário possui data de troca de senha registrada.
            </div>
        <?php endif; ?>
        
        <h2>4️⃣ Testar Usuário Específico</h2>
        
        <?php
        if (isset($_GET['test_user_id'])) {
            $test_user_id = intval($_GET['test_user_id']);
            $test_user = get_userdata($test_user_id);
            
            if ($test_user) {
                $last_change = get_user_meta($test_user_id, '_llrp_last_password_change', true);
                $last_login = get_user_meta($test_user_id, '_llrp_last_login', true);
                
                // Simular verificação
                if (defined('LLRP_PLUGIN_DIR')) {
                    $class_file = LLRP_PLUGIN_DIR . 'includes/class-llrp-password-expiration.php';
                } else {
                    $class_file = dirname(__FILE__) . '/includes/class-llrp-password-expiration.php';
                }
                
                if (file_exists($class_file)) {
                    require_once($class_file);
                }
                
                if (class_exists('Llrp_Password_Expiration')) {
                    $status = Llrp_Password_Expiration::check_password_status($test_user_id);
                } else {
                    $status = ['expired' => false, 'warning' => false, 'reason' => 'Classe não encontrada'];
                }
                
                echo '<div class="status-box status-success">';
                echo '<h3>Resultado do Teste para: ' . esc_html($test_user->user_login) . ' (ID: ' . $test_user_id . ')</h3>';
                echo '<table>';
                echo '<tr><th>Campo</th><th>Valor</th></tr>';
                echo '<tr><td><strong>E-mail</strong></td><td>' . esc_html($test_user->user_email) . '</td></tr>';
                echo '<tr><td><strong>_llrp_last_password_change</strong></td><td>' . ($last_change ? date('d/m/Y H:i', $last_change) : '<span class="badge badge-no">NÃO DEFINIDO</span>') . '</td></tr>';
                echo '<tr><td><strong>_llrp_last_login</strong></td><td>' . ($last_login ? date('d/m/Y H:i', $last_login) : '<span class="badge badge-no">NÃO DEFINIDO</span>') . '</td></tr>';
                echo '<tr><td><strong>Senha Expirada?</strong></td><td>' . ($status['expired'] ? '<span class="badge badge-no">SIM</span>' : '<span class="badge badge-yes">NÃO</span>') . '</td></tr>';
                echo '<tr><td><strong>Motivo</strong></td><td>' . ($status['reason'] ?: 'N/A') . '</td></tr>';
                echo '<tr><td><strong>Aviso?</strong></td><td>' . ($status['warning'] ? '<span class="badge badge-no">SIM</span>' : '<span class="badge badge-yes">NÃO</span>') . '</td></tr>';
                echo '</table>';
                echo '</div>';
                
                if ($status['expired'] && $status['reason'] === 'imported') {
                    echo '<div class="status-success">';
                    echo '<strong>✓ CORRETO:</strong> Este usuário SERÁ FORÇADO a trocar a senha no próximo login!';
                    echo '</div>';
                } elseif (!$last_change && $force_imported) {
                    echo '<div class="status-error">';
                    echo '<strong>✗ ERRO:</strong> Este usuário não tem data de troca mas não está sendo detectado como expirado!<br>';
                    echo '<strong>Possível causa:</strong> A opção pode ter sido ativada após o usuário já ter feito login uma vez.';
                    echo '</div>';
                } else {
                    echo '<div class="status-warning">';
                    echo '<strong>ℹ️ INFO:</strong> Este usuário já possui data de troca de senha, portanto não será forçado a trocar.';
                    echo '</div>';
                }
            }
        }
        ?>
        
        <form method="GET" style="margin: 20px 0;">
            <label><strong>ID do Usuário para Testar:</strong></label><br>
            <input type="number" name="test_user_id" placeholder="Digite o ID do usuário" style="padding: 8px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="button">Testar Usuário</button>
        </form>
        
        <h2>5️⃣ Soluções</h2>
        
        <h3>Se os usuários importados já têm a data de troca:</h3>
        <p>Execute este SQL no phpMyAdmin para <strong>REMOVER</strong> a data de troca de senha de todos os usuários importados ontem:</p>
        
        <div class="sql-box">
DELETE FROM <?php echo $wpdb->usermeta; ?>
WHERE meta_key = '_llrp_last_password_change'
AND user_id IN (
    SELECT ID FROM <?php echo $wpdb->users; ?>
    WHERE DATE(user_registered) = '<?php echo date('Y-m-d', strtotime('-1 day')); ?>'
);
        </div>
        
        <h3>Ou para remover de TODOS os usuários (cuidado!):</h3>
        <div class="sql-box">
DELETE FROM <?php echo $wpdb->usermeta; ?>
WHERE meta_key = '_llrp_last_password_change';
        </div>
        
        <h3>Para adicionar a data APENAS para usuários antigos (antes de ontem):</h3>
        <div class="sql-box">
INSERT INTO <?php echo $wpdb->usermeta; ?> (user_id, meta_key, meta_value)
SELECT ID, '_llrp_last_password_change', UNIX_TIMESTAMP()
FROM <?php echo $wpdb->users; ?>
WHERE user_registered < '<?php echo date('Y-m-d', strtotime('-1 day')); ?>'
AND ID NOT IN (
    SELECT user_id FROM <?php echo $wpdb->usermeta; ?> WHERE meta_key = '_llrp_last_password_change'
);
        </div>
        
        <p style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <strong>⚠️ IMPORTANTE:</strong> Depois de executar o SQL, faça logout e login novamente com um usuário importado para testar.
        </p>
        
        <p style="margin-top: 30px; text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=llrp-settings&tab=advanced'); ?>" class="button">Ir para Configurações do Plugin</a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="button button-secondary">Fazer Logout</a>
        </p>
        
        <hr style="margin: 40px 0; border: none; border-top: 1px solid #ddd;">
        
        <p style="text-align: center; color: #666; font-size: 12px;">
            <strong>⚠️ SEGURANÇA:</strong> Remova este arquivo após o debug!<br>
            <code>rm <?php echo __FILE__; ?></code>
        </p>
    </div>
</body>
</html>
