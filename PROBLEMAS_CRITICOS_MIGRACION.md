# PROBLEMAS CRÍTICOS EN MigrationController.php

## 🚨 PROBLEMA 1: migratePlanes() NO FILTRA PLAN MÁS RECIENTE

**Ubicación:** Líneas 352-455

**Problema:**
```php
$planesAsignados = Ajuste::on($legacyConnection)->whereIn('paciente_id', $patientIds)
    ->whereIn('plan_id', Plan::on($targetConnection)->whereNotIn('id', $this->ignored_plan)->pluck('id')->toArray())
    ->whereIn('estado', [1, 2, 3])
    ->get();
```

❌ **Trae TODOS los planes del paciente, no solo el más reciente**
❌ **NO usa fecha_ciclo_insertada para filtrar**
❌ **Puede migrar múltiples planes cuando solo se debería migrar el último**

**Impacto:**
- Migra planes antiguos innecesarios
- Puede crear duplicados
- Datos históricos incorrectos

---

## 🚨 PROBLEMA 2: USO DE ID DIRECTO EN create() - RIESGO DE SOBRESCRITURA

**Ubicación:** Líneas 398-418 (migratePlanes)

**Problema:**
```php
$assignedPlan = AssignedPlan::on($targetConnection)->create([
    'id' => $p->id,  // ⚠️ PELIGRO: Usa ID directo del plan legacy
    'plan_id' => $p->plan_id,
    'patient_id' => $finalPatientId,
    ...
]);
```

❌ **Si el ID del plan ya existe, generará error de clave duplicada**
❌ **Si se cambia a updateOrCreate, podría SOBRESCRIBIR datos de otro paciente**

**Mismo problema en:**
- **migrateBalance()** línea 552: `'id' => $balance->id`
- **migrateCompras()** línea 614: `'id' => $compra->id`
- **migrateAntecedentes()** línea 658: `'id' => $antecedente->id`
- **migrateHistorialAjuste()** línea 702: `'id' => $historial->service_id`
- **migrateHistorialTerapiaFisicas()** línea 857: `'id' => $historial->service_id`

---

## 🚨 PROBLEMA 3: GENERACIÓN DE IDs ENTRE 10000-11000

**Ubicación:** Líneas 1046-1091 (determineFinalPatientIdOptimized)

**Problema:**
```php
$idExtra = 1;
$newId = (int) ($idExtra . $originalId);  // Concatenación de strings
```

**Ejemplos de IDs generados:**
- originalId = 500 → idExtra = 1 → newId = 1500
- originalId = 0500 → idExtra = 1 → newId = 10500 ✅ (rango 10000-11000)
- originalId = 1000 → idExtra = 1 → newId = 11000 ✅ (rango 10000-11000)

❌ **Puede generar IDs en el rango 10000-11000 que podrían existir**
❌ **La concatenación de strings puede crear colisiones**

---

## 🚨 PROBLEMA 4: MAPEO DE IDs PUEDE FALLAR

**Ubicación:** Múltiples lugares donde se usa $idMapping

**Problema:**
Si hay un error en el mapeo de IDs ($idMapping), TODOS los datos se asignarían al paciente incorrecto:

```php
$finalPatientId = $idMapping[$p->paciente_id];  // ¿Qué pasa si no existe?
```

**Métodos afectados:**
- migratePlanes()
- migrateBalance()
- migrateCompras()
- migrateAntecedentes()
- migrateHistorialAjuste()
- migrateHistorialTerapiaFisicas()

---

## 🚨 PROBLEMA 5: FALTA VALIDACIÓN DE PACIENTE EN updateExistingPatientsData

**Ubicación:** Líneas 1792-1926

**Problema:**
```php
$patients = Patient::on($targetConnection)
    ->whereIn('id', $patientIds)
    ->get(['id', 'old_id', 'first_name', 'last_name']);

foreach ($patients as $patient) {
    $oldId = $patient->old_id ?? $patient->id;  // ⚠️ Asume que si no hay old_id, el ID no cambió
    $idMapping[$oldId] = $newId;
}
```

❌ **Si old_id es NULL, asume que el ID del paciente no cambió (puede ser incorrecto)**
❌ **No valida que el old_id exista en la base legacy**

---

## 🚨 PROBLEMA 6: TRANSACCIONES Y VOUCHERS

**Ubicación:** Líneas 420-427, 466-473

**Problema:**
```php
// Crear transacción
$assignedPlan->transactions()->create([
    'assigned_plan_id' => $assignedPlan->id,
    'patient_id' => $finalPatientId,  // ⚠️ Usa finalPatientId del mapeo
    ...
]);
```

❌ **Si $finalPatientId es incorrecto, las transacciones se crean para el paciente equivocado**
❌ **Los vouchers también se crean con el patient_id incorrecto**

---

## ✅ SOLUCIONES RECOMENDADAS

### 1. APLICAR FILTRO DE PLAN MÁS RECIENTE EN migratePlanes()

```php
private function migratePlanes(array $patientIds, array &$results, array $idMapping, string $legacyConnection = 'legacy', string $targetConnection = 'mysql')
{
    // Obtener IDs de planes válidos
    $valid_plan_ids = Plan::on($targetConnection)->whereNotIn('id', $this->ignored_plan)->pluck('id')->toArray();

    // NUEVO: Obtener solo el plan más reciente para cada paciente
    $latest_dates = Ajuste::on($legacyConnection)
        ->selectRaw('paciente_id, MAX(CAST(fecha_ciclo_insertada AS DATETIME)) as max_fecha')
        ->whereIn('paciente_id', $patientIds)
        ->whereIn('plan_id', $valid_plan_ids)
        ->whereNotNull('fecha_ciclo_insertada')
        ->where('fecha_ciclo_insertada', '!=', '')
        ->whereIn('estado', [1, 2, 3])
        ->groupBy('paciente_id')
        ->get()
        ->keyBy('paciente_id')
        ->map(fn($item) => $item->max_fecha);

    // Obtener los IDs de los ajustes más recientes
    $latest_plan_ids = [];
    foreach ($latest_dates as $paciente_id => $max_fecha) {
        $ajuste_id = Ajuste::on($legacyConnection)
            ->where('paciente_id', $paciente_id)
            ->whereRaw('CAST(fecha_ciclo_insertada AS DATETIME) = ?', [$max_fecha])
            ->whereIn('plan_id', $valid_plan_ids)
            ->whereIn('estado', [1, 2, 3])
            ->orderBy('id', 'desc')
            ->value('id');
        
        if ($ajuste_id) {
            $latest_plan_ids[] = $ajuste_id;
        }
    }

    // Ahora obtener solo esos planes
    $planesAsignados = Ajuste::on($legacyConnection)
        ->whereIn('id', $latest_plan_ids)
        ->get();
    
    // ... resto del código
}
```

### 2. VERIFICAR QUE EL ID NO EXISTA ANTES DE create()

```php
// Verificar que el ID del plan no exista
if (AssignedPlan::on($targetConnection)->where('id', $p->id)->exists()) {
    $results['errors'][] = "Plan con ID {$p->id} ya existe, saltando";
    continue;
}

$assignedPlan = AssignedPlan::on($targetConnection)->create([...]);
```

### 3. VALIDAR $idMapping ANTES DE USAR

```php
// Verificar que el paciente esté en el mapeo
if (!isset($idMapping[$p->paciente_id])) {
    $results['errors'][] = "Paciente {$p->paciente_id} no fue migrado, saltando plan {$p->id}";
    continue;
}

$finalPatientId = $idMapping[$p->paciente_id];

// VALIDAR que el paciente existe en la BD target
if (!Patient::on($targetConnection)->find($finalPatientId)) {
    $results['errors'][] = "Paciente con ID final {$finalPatientId} no existe en la BD";
    continue;
}
```

### 4. MEJORAR GENERACIÓN DE IDs

```php
private function determineFinalPatientIdOptimized($legacyPatient, &$existingPatientIds, $existingPatientsByName)
{
    // ... código existente ...
    
    // MEJORAR: Usar un rango de IDs específico y seguro
    $idExtra = 1;
    do {
        // Usar suma en lugar de concatenación
        $newId = ($idExtra * 1000000) + $originalId;  // Ej: 1000500, 2000500
        $idExtra++;
    } while (in_array($newId, $existingPatientIds) && $idExtra < 100);
    
    // ... resto del código ...
}
```

### 5. AGREGAR LOGS DE AUDITORÍA

```php
Log::info("Migrando plan - Mapeo de IDs", [
    'plan_id' => $p->id,
    'original_patient_id' => $p->paciente_id,
    'final_patient_id' => $finalPatientId,
    'plan_already_exists' => AssignedPlan::on($targetConnection)->where('id', $p->id)->exists(),
    'patient_exists' => Patient::on($targetConnection)->where('id', $finalPatientId)->exists()
]);
```

---

## 📋 CHECKLIST DE VALIDACIÓN ANTES DE MIGRAR

- [ ] Verificar que el paciente legacy existe
- [ ] Verificar que el paciente target existe
- [ ] Validar que $idMapping[$legacy_patient_id] existe
- [ ] Verificar que el ID del registro a crear no existe (plan, balance, etc.)
- [ ] Confirmar que se está migrando solo el plan más reciente
- [ ] Validar que las transacciones usan el patient_id correcto
- [ ] Verificar que los vouchers se crean para el paciente correcto
- [ ] Confirmar que el rango de IDs generados no colisiona con existentes

---

## 🔍 MÉTODOS A REVISAR CON PRIORIDAD

1. **migratePlanes()** - Líneas 352-455 ⚠️⚠️⚠️
2. **determineFinalPatientIdOptimized()** - Líneas 1046-1091 ⚠️⚠️
3. **migrateBalance()** - Líneas 527-566 ⚠️
4. **migrateCompras()** - Líneas 568-630 ⚠️
5. **migrateAntecedentes()** - Líneas 632-671 ⚠️
6. **migrateHistorialAjuste()** - Líneas 673-740 ⚠️
7. **migrateHistorialTerapiaFisicas()** - Líneas 795-910 ⚠️
8. **updateExistingPatientsData()** - Líneas 1792-1926 ⚠️

