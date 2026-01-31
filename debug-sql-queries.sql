-- =====================================================
-- SCRIPT SQL DE DEBUG PARA USUÁRIOS IMPORTADOS
-- Execute estas queries no phpMyAdmin
-- =====================================================

-- 1. VERIFICAR CONFIGURAÇÕES DO PLUGIN
-- =====================================================
SELECT 
    option_name as 'Configuração',
    CASE 
        WHEN option_value = '1' THEN '✓ ATIVO'
        WHEN option_value = '0' THEN '✗ DESATIVADO'
        ELSE option_value
    END as 'Status'
FROM wp_options 
WHERE option_name IN (
    'llrp_password_force_imported_users',
    'llrp_password_expiration_enabled',
    'llrp_password_expiration_days',
    'llrp_password_expiration_inactivity_enabled',
    'llrp_password_expiration_inactivity_days'
)
ORDER BY option_name;

-- =====================================================
-- 2. CONTAR USUÁRIOS IMPORTADOS (SEM DATA DE TROCA)
-- =====================================================
SELECT 
    COUNT(*) as 'Total de Usuários Importados',
    'Usuários SEM _llrp_last_password_change' as 'Descrição'
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
WHERE um.meta_value IS NULL;

-- =====================================================
-- 3. LISTAR USUÁRIOS IMPORTADOS (50 MAIS RECENTES)
-- =====================================================
SELECT 
    u.ID as 'ID',
    u.user_login as 'Usuário',
    u.user_email as 'E-mail',
    DATE_FORMAT(u.user_registered, '%d/%m/%Y %H:%i') as 'Registrado em',
    'SEM DATA' as 'Status Senha'
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
WHERE um.meta_value IS NULL
ORDER BY u.user_registered DESC
LIMIT 50;

-- =====================================================
-- 4. LISTAR USUÁRIOS IMPORTADOS ONTEM
-- =====================================================
SELECT 
    u.ID as 'ID',
    u.user_login as 'Usuário',
    u.user_email as 'E-mail',
    DATE_FORMAT(u.user_registered, '%d/%m/%Y %H:%i') as 'Registrado',
    um.meta_value as 'Tem Data?'
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
WHERE DATE(u.user_registered) = CURDATE() - INTERVAL 1 DAY
ORDER BY u.ID;

-- =====================================================
-- 5. VERIFICAR UM USUÁRIO ESPECÍFICO (SUBSTITUA O ID)
-- =====================================================
SELECT 
    u.ID as 'ID',
    u.user_login as 'Usuário',
    u.user_email as 'E-mail',
    DATE_FORMAT(u.user_registered, '%d/%m/%Y %H:%i') as 'Registrado',
    um_pass.meta_value as '_llrp_last_password_change',
    CASE 
        WHEN um_pass.meta_value IS NULL THEN 'NÃO (será forçado)'
        ELSE FROM_UNIXTIME(um_pass.meta_value, '%d/%m/%Y %H:%i')
    END as 'Data Última Troca',
    um_login.meta_value as '_llrp_last_login',
    CASE 
        WHEN um_login.meta_value IS NULL THEN 'Nunca'
        ELSE FROM_UNIXTIME(um_login.meta_value, '%d/%m/%Y %H:%i')
    END as 'Último Login'
FROM wp_users u
LEFT JOIN wp_usermeta um_pass ON u.ID = um_pass.user_id AND um_pass.meta_key = '_llrp_last_password_change'
LEFT JOIN wp_usermeta um_login ON u.ID = um_login.user_id AND um_login.meta_key = '_llrp_last_login'
WHERE u.ID = 123;  -- ← SUBSTITUA 123 PELO ID DO USUÁRIO DE TESTE

-- =====================================================
-- 6. COMPARAR USUÁRIOS COM E SEM DATA
-- =====================================================
SELECT 
    CASE 
        WHEN um.meta_value IS NULL THEN 'SEM DATA (Importados)'
        ELSE 'COM DATA (Normais)'
    END as 'Tipo',
    COUNT(*) as 'Quantidade'
FROM wp_users u
LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = '_llrp_last_password_change'
GROUP BY (um.meta_value IS NULL)
ORDER BY (um.meta_value IS NULL) DESC;

-- =====================================================
-- 7. SOLUÇÕES - ESCOLHA UMA
-- =====================================================

-- SOLUÇÃO 1: Remover data de TODOS os usuários importados ONTEM
-- Use se importou ontem e quer forçar todos a trocarem senha
-- =====================================================
-- DELETE FROM wp_usermeta
-- WHERE meta_key = '_llrp_last_password_change'
-- AND user_id IN (
--     SELECT ID FROM wp_users 
--     WHERE DATE(user_registered) = CURDATE() - INTERVAL 1 DAY
-- );

-- SOLUÇÃO 2: Remover data de UM usuário específico (para teste)
-- =====================================================
-- DELETE FROM wp_usermeta
-- WHERE meta_key = '_llrp_last_password_change'
-- AND user_id = 123;  -- ← SUBSTITUA 123 PELO ID DO USUÁRIO

-- SOLUÇÃO 3: Remover data de TODOS os usuários (CUIDADO!)
-- Use apenas se quiser forçar TODOS a trocarem senha
-- =====================================================
-- DELETE FROM wp_usermeta
-- WHERE meta_key = '_llrp_last_password_change';

-- SOLUÇÃO 4: Adicionar data para usuários ANTIGOS (antes de ontem)
-- Use para proteger usuários antigos de serem forçados
-- =====================================================
-- INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
-- SELECT 
--     u.ID, 
--     '_llrp_last_password_change', 
--     UNIX_TIMESTAMP()
-- FROM wp_users u
-- WHERE u.user_registered < CURDATE() - INTERVAL 1 DAY
-- AND NOT EXISTS (
--     SELECT 1 FROM wp_usermeta um 
--     WHERE um.user_id = u.ID 
--     AND um.meta_key = '_llrp_last_password_change'
-- );

-- =====================================================
-- 8. APÓS EXECUTAR A SOLUÇÃO, VERIFICAR NOVAMENTE
-- =====================================================
-- Execute a query #2 novamente para confirmar as mudanças
