-- =========================================================================
-- QUERIES DE VERIFICACIÓN PARA DETECTAR PROBLEMAS EN LA MIGRACIÓN
-- Ejecutar estas queries en la base de datos TARGET (mysql/produccion)
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. VERIFICAR PACIENTES CON IDs CAMBIADOS (old_id diferente de id)
-- -------------------------------------------------------------------------
-- Esto es normal, pero ayuda a identificar qué pacientes tienen mapeo de ID
SELECT 
    id as nuevo_id,
    old_id as id_legacy,
    CONCAT(first_name, ' ', last_name) as nombre,
    branch_id,
    created_at
FROM patients
WHERE old_id IS NOT NULL AND id != old_id
ORDER BY created_at DESC;

-- -------------------------------------------------------------------------
-- 2. VERIFICAR PACIENTES CON IDs EN RANGO 10000-11000 (POTENCIALMENTE PROBLEMÁTICO)
-- -------------------------------------------------------------------------
SELECT 
    id,
    old_id,
    CONCAT(first_name, ' ', last_name) as nombre,
    branch_id,
    created_at,
    CASE 
        WHEN old_id IS NULL THEN 'Sin old_id'
        WHEN id != old_id THEN 'ID cambiado'
        ELSE 'ID original'
    END as status
FROM patients
WHERE id BETWEEN 10000 AND 11000
ORDER BY id;

-- -------------------------------------------------------------------------
-- 3. VERIFICAR PLANES ASIGNADOS HUÉRFANOS (patient_id no existe)
-- -------------------------------------------------------------------------
-- ⚠️ CRÍTICO: Planes que apuntan a pacientes inexistentes
SELECT 
    ap.id as plan_id,
    ap.patient_id as patient_id_inexistente,
    ap.plan_name,
    ap.amount,
    ap.status,
    ap.created_at
FROM assigned_plans ap
LEFT JOIN patients p ON ap.patient_id = p.id
WHERE p.id IS NULL;

-- -------------------------------------------------------------------------
-- 4. VERIFICAR PACIENTES CON MÚLTIPLES PLANES (DEBERÍA SER SOLO 1)
-- -------------------------------------------------------------------------
-- ⚠️ Si hay más de 1 plan por paciente, no se aplicó el filtro de "más reciente"
SELECT 
    ap.patient_id,
    p.first_name,
    p.last_name,
    COUNT(*) as cantidad_planes,
    GROUP_CONCAT(ap.id ORDER BY ap.date_start DESC) as plan_ids,
    GROUP_CONCAT(ap.date_start ORDER BY ap.date_start DESC) as fechas_inicio
FROM assigned_plans ap
JOIN patients p ON ap.patient_id = p.id
GROUP BY ap.patient_id, p.first_name, p.last_name
HAVING cantidad_planes > 1
ORDER BY cantidad_planes DESC;

-- -------------------------------------------------------------------------
-- 5. VERIFICAR TRANSACCIONES CON patient_id INCORRECTO
-- -------------------------------------------------------------------------
-- ⚠️ CRÍTICO: Transacciones donde el patient_id no coincide con el plan
SELECT 
    t.id as transaction_id,
    t.patient_id as transaction_patient_id,
    ap.patient_id as plan_patient_id,
    t.amount,
    ap.plan_name,
    p1.first_name as paciente_transaccion,
    p2.first_name as paciente_plan,
    t.created_at
FROM transactions t
JOIN assigned_plans ap ON t.assigned_plan_id = ap.id
LEFT JOIN patients p1 ON t.patient_id = p1.id
LEFT JOIN patients p2 ON ap.patient_id = p2.id
WHERE t.patient_id != ap.patient_id;

-- -------------------------------------------------------------------------
-- 6. VERIFICAR VOUCHERS CON PROBLEMAS
-- -------------------------------------------------------------------------
-- Vouchers que apuntan a planes de otros pacientes
SELECT 
    v.id as voucher_id,
    v.assigned_plan_id,
    ap.patient_id as plan_patient_id,
    p.first_name,
    p.last_name,
    v.price,
    v.status,
    v.created_at
FROM vouchers v
JOIN assigned_plans ap ON v.assigned_plan_id = ap.id
LEFT JOIN patients p ON ap.patient_id = p.id
WHERE p.id IS NULL;

-- -------------------------------------------------------------------------
-- 7. VERIFICAR SERVICIOS ADQUIRIDOS CON patient_id INCORRECTO
-- -------------------------------------------------------------------------
-- ⚠️ CRÍTICO: Servicios donde el patient_id no coincide con el del plan
SELECT 
    acs.id as service_id,
    acs.patient_id as service_patient_id,
    ap.patient_id as plan_patient_id,
    acs.price,
    acs.status,
    p1.first_name as paciente_servicio,
    p2.first_name as paciente_plan
FROM acquired_services acs
LEFT JOIN assigned_plans ap ON acs.assigned_plan_id = ap.id
LEFT JOIN patients p1 ON acs.patient_id = p1.id
LEFT JOIN patients p2 ON ap.patient_id = p2.id
WHERE acs.assigned_plan_id IS NOT NULL 
  AND acs.patient_id != ap.patient_id;

-- -------------------------------------------------------------------------
-- 8. VERIFICAR DESCUENTOS CON PROBLEMAS
-- -------------------------------------------------------------------------
SELECT 
    da.id as descuento_id,
    da.patient_id as descuento_patient_id,
    ap.patient_id as plan_patient_id,
    da.approved_amount,
    da.status,
    p1.first_name as paciente_descuento,
    p2.first_name as paciente_plan
FROM descuent_authorizations da
JOIN assigned_plans ap ON da.assigned_plan_id = ap.id
LEFT JOIN patients p1 ON da.patient_id = p1.id
LEFT JOIN patients p2 ON ap.patient_id = p2.id
WHERE da.patient_id != ap.patient_id;

-- -------------------------------------------------------------------------
-- 9. VERIFICAR BALANCES (CREDIT NOTES) HUÉRFANOS
-- -------------------------------------------------------------------------
SELECT 
    cn.id as credit_note_id,
    cn.patient_id,
    cn.amount,
    cn.note,
    p.id as patient_exists,
    cn.created_at
FROM credit_notes cn
LEFT JOIN patients p ON cn.patient_id = p.id
WHERE p.id IS NULL;

-- -------------------------------------------------------------------------
-- 10. VERIFICAR COMPRAS (PATIENT ITEMS) HUÉRFANAS
-- -------------------------------------------------------------------------
SELECT 
    pi.id as item_id,
    pi.patient_id,
    pi.description,
    pi.total,
    p.id as patient_exists,
    pi.created_at
FROM patient_items pi
LEFT JOIN patients p ON pi.patient_id = p.id
WHERE p.id IS NULL
  AND pi.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY); -- Últimos 30 días

-- -------------------------------------------------------------------------
-- 11. VERIFICAR HISTORIALES MÉDICOS HUÉRFANOS
-- -------------------------------------------------------------------------
SELECT 
    mr.id as medical_record_id,
    mr.patient_id,
    mr.consultation_reason,
    p.id as patient_exists,
    mr.created_at
FROM medical_records mr
LEFT JOIN patients p ON mr.patient_id = p.id
WHERE p.id IS NULL;

-- -------------------------------------------------------------------------
-- 12. DETECTAR POSIBLES CONFLICTOS DE ID EN PLANES
-- -------------------------------------------------------------------------
-- Compara IDs de planes en target vs legacy para detectar posibles sobrescrituras
-- NOTA: Requiere acceso a la base de datos legacy
SELECT 
    ap.id as plan_id_target,
    ap.patient_id as patient_id_target,
    CONCAT(p.first_name, ' ', p.last_name) as nombre_target,
    ap.amount,
    ap.date_start,
    ap.created_at
FROM assigned_plans ap
JOIN patients p ON ap.patient_id = p.id
WHERE ap.id IN (
    -- Aquí deberías poner los IDs de planes que están siendo migrados
    -- Ejemplo: SELECT id FROM legacy.planes_asignados WHERE paciente_id IN (100, 200, 300)
    SELECT id FROM assigned_plans WHERE patient_id IN (
        SELECT id FROM patients WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    )
)
ORDER BY ap.created_at DESC;

-- -------------------------------------------------------------------------
-- 13. RESUMEN GENERAL DE INTEGRIDAD
-- -------------------------------------------------------------------------
SELECT 
    'Total Pacientes' as tipo,
    COUNT(*) as cantidad
FROM patients
UNION ALL
SELECT 
    'Pacientes con ID cambiado',
    COUNT(*)
FROM patients
WHERE old_id IS NOT NULL AND id != old_id
UNION ALL
SELECT 
    'Pacientes en rango 10000-11000',
    COUNT(*)
FROM patients
WHERE id BETWEEN 10000 AND 11000
UNION ALL
SELECT 
    'Planes asignados',
    COUNT(*)
FROM assigned_plans
UNION ALL
SELECT 
    'Planes huérfanos (sin paciente)',
    COUNT(*)
FROM assigned_plans ap
LEFT JOIN patients p ON ap.patient_id = p.id
WHERE p.id IS NULL
UNION ALL
SELECT 
    'Pacientes con múltiples planes',
    COUNT(DISTINCT patient_id)
FROM (
    SELECT patient_id, COUNT(*) as cnt
    FROM assigned_plans
    GROUP BY patient_id
    HAVING cnt > 1
) as multi_plans
UNION ALL
SELECT 
    'Transacciones con patient_id incorrecto',
    COUNT(*)
FROM transactions t
JOIN assigned_plans ap ON t.assigned_plan_id = ap.id
WHERE t.patient_id != ap.patient_id
UNION ALL
SELECT 
    'Servicios con patient_id incorrecto',
    COUNT(*)
FROM acquired_services acs
JOIN assigned_plans ap ON acs.assigned_plan_id = ap.id
WHERE acs.patient_id != ap.patient_id
UNION ALL
SELECT 
    'Credit notes huérfanas',
    COUNT(*)
FROM credit_notes cn
LEFT JOIN patients p ON cn.patient_id = p.id
WHERE p.id IS NULL;

-- -------------------------------------------------------------------------
-- 14. VERIFICAR PLANES DUPLICADOS POR FECHA (sin filtro de más reciente)
-- -------------------------------------------------------------------------
-- Si hay múltiples planes con fechas diferentes, no se aplicó el filtro
SELECT 
    ap.patient_id,
    CONCAT(p.first_name, ' ', p.last_name) as nombre_paciente,
    COUNT(*) as total_planes,
    MIN(ap.date_start) as fecha_plan_mas_antiguo,
    MAX(ap.date_start) as fecha_plan_mas_reciente,
    DATEDIFF(MAX(ap.date_start), MIN(ap.date_start)) as dias_diferencia,
    GROUP_CONCAT(ap.id ORDER BY ap.date_start DESC) as plan_ids
FROM assigned_plans ap
JOIN patients p ON ap.patient_id = p.id
GROUP BY ap.patient_id, p.first_name, p.last_name
HAVING total_planes > 1
ORDER BY total_planes DESC, dias_diferencia DESC;

-- -------------------------------------------------------------------------
-- 15. VERIFICAR INTEGRIDAD DE MAPEO DE IDs (old_id vs id)
-- -------------------------------------------------------------------------
-- Pacientes donde old_id podría estar mal mapeado
SELECT 
    p.id,
    p.old_id,
    CONCAT(p.first_name, ' ', p.last_name) as nombre,
    p.branch_id,
    COUNT(ap.id) as cantidad_planes,
    p.created_at
FROM patients p
LEFT JOIN assigned_plans ap ON ap.patient_id = p.id
WHERE p.old_id IS NOT NULL
  AND p.id != p.old_id
GROUP BY p.id, p.old_id, p.first_name, p.last_name, p.branch_id, p.created_at
ORDER BY cantidad_planes DESC, p.created_at DESC;

-- =========================================================================
-- INSTRUCCIONES DE USO:
-- 1. Ejecutar estas queries una por una
-- 2. Si alguna query devuelve resultados, revisar los datos
-- 3. Queries que NO deberían devolver resultados (indican problemas):
--    - Query 3: Planes huérfanos
--    - Query 5: Transacciones con patient_id incorrecto
--    - Query 7: Servicios con patient_id incorrecto
--    - Query 8: Descuentos con problemas
--    - Query 9: Balances huérfanos
--    - Query 10: Compras huérfanas
--    - Query 11: Historiales médicos huérfanos
-- 4. Queries que pueden devolver resultados pero deben revisarse:
--    - Query 2: IDs en rango 10000-11000
--    - Query 4: Pacientes con múltiples planes
--    - Query 14: Planes sin filtro de más reciente
-- =========================================================================

