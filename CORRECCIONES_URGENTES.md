# CORRECCIONES URGENTES PARA MigrationController.php

## 🔴 CORRECCIÓN 1: Aplicar filtro de plan más reciente en migratePlanes()

**Problema:** Migra TODOS los planes del paciente en lugar de solo el más reciente.

**Reemplazar desde línea 352:**

```php
private function migratePlanes(array $patientIds, array &$results, array $idMapping, string $legacyConnection = 'legacy', string $targetConnection = 'mysql')
{
    // Obtener IDs de planes válidos
    $valid_plan_ids = Plan::on($targetConnection)->whereNotIn('id', $this->ignored_plan)->pluck('id')->toArray();

    // NUEVO: Obtener solo el plan más reciente para cada paciente usando fecha_ciclo_insertada
    $this->info("Obteniendo planes más recientes para " . count($patientIds) . " pacientes...");
    
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
            ->orderBy('id', 'desc') // Si hay múltiples con la misma fecha, tomar el más reciente por ID
            ->value('id');
        
        if ($ajuste_id) {
            $latest_plan_ids[] = $ajuste_id;
        }
    }

    Log::info("Planes más recientes encontrados: " . count($latest_plan_ids));

    // Obtener solo los planes más recientes
    $planesAsignados = Ajuste::on($legacyConnection)
        ->whereIn('id', $latest_plan_ids)
        ->get();

    $user = User::on($targetConnection)->first();
    $planStatusMatch = [
        1 => PlanStatus::Activo->value,
        2 => PlanStatus::Expirado->value,
        3 => PlanStatus::Completado->value,
        4 => PlanStatus::Desactivado->value,
    ];

    foreach ($planesAsignados as $p) {
        try {
            $planFound = Plan::on($targetConnection)->find($p->plan_id);
            if (!$planFound) {
                $results['errors'][] = "Plan no encontrado - ID: {$p->plan_id}";
                continue;
            }

            // VALIDACIÓN 1: Verificar que el paciente esté en el mapeo
            if (!isset($idMapping[$p->paciente_id])) {
                $results['errors'][] = "Paciente {$p->paciente_id} no fue migrado, saltando plan {$p->id}";
                continue;
            }

            // Usar el ID mapeado
            $finalPatientId = $idMapping[$p->paciente_id];

            // Log para auditoría
            Log::info("Migrando plan - Mapeo de ID de paciente", [
                'plan_id' => $p->id,
                'original_patient_id' => $p->paciente_id,
                'final_patient_id' => $finalPatientId,
                'id_changed' => ($p->paciente_id != $finalPatientId)
            ]);

            // VALIDACIÓN 2: Verificar que el paciente exista en la nueva base de datos
            if (!Patient::on($targetConnection)->find($finalPatientId)) {
                $results['errors'][] = "Paciente con ID final {$finalPatientId} (ID viejo: {$p->paciente_id}) no existe en la BD, saltando plan {$p->id}";
                continue;
            }

            // VALIDACIÓN 3: Verificar que el ID del plan no exista ya
            if (AssignedPlan::on($targetConnection)->where('id', $p->id)->exists()) {
                $results['errors'][] = "Plan asignado con ID {$p->id} ya existe, saltando";
                Log::warning("Intento de sobrescribir plan existente", [
                    'plan_id' => $p->id,
                    'existing_patient_id' => AssignedPlan::on($targetConnection)->find($p->id)->patient_id ?? 'unknown',
                    'new_patient_id' => $finalPatientId
                ]);
                continue;
            }

            $assignedPlan = AssignedPlan::on($targetConnection)->create([
                'id' => $p->id,
                'plan_id' => $p->plan_id,
                'patient_id' => $finalPatientId,
                'date_start' => $this->parseDate($p->fecha_ciclo_insertada),
                'date_end' => $this->parseDate($p->fecha_expiracion),
                'plan_name' => $planFound->name ?? 'Plan ' . $this->generateRandomCode(AssignedPlan::class, 8, 'plan_name'),
                'paid_type' => 1,
                'amount' => $p->costo,
                'therapies_number' => $p->terapias_fisicas,
                'total_sessions' => $p->ajustes,
                'number_installments' => $planFound->number_installments ?? 0,
                'status' => $planStatusMatch[$p->estado],
                'branch_id' => $p->centro_id,
                'user_id' => $user->id,
                'card_commission' => $p->card_fee,
                'bank_commission' => $p->bank_fee,
                'other_commission' => $p->other_fee,
                'created_at' => $this->parseDateInt($p->fecha_cre),
                'updated_at' => $this->parseDateInt($p->fecha_cre),
            ]);

            // Crear transacción
            $assignedPlan->transactions()->create([
                'assigned_plan_id' => $assignedPlan->id,
                'patient_id' => $finalPatientId,
                'amount' => $p->pagado,
                'transaction_type' => 'entrada',
                'description' => 'Plan asignado',
            ]);

            // Crear descuento si existe
            if ($p->descuento != 0) {
                DescuentAuthorization::on($targetConnection)->create([
                    'patient_id' => $finalPatientId,
                    'assigned_plan_id' => $assignedPlan->id,
                    'type' => 1,
                    'request_amount' => $p->descuento,
                    'approved_amount' => $p->descuento,
                    'status' => 2,
                    'request_by' => $user->id,
                    'authorized_by' => $user->id,
                    'authorized_at' => now(),
                    'created_at' => $this->parseDateInt($p->fecha_cre),
                    'updated_at' => $this->parseDateInt($p->fecha_cre),
                ]);
            }

            // Crear vouchers y servicios adquiridos
            $this->createVouchersAndServices($assignedPlan, $p, $finalPatientId, $targetConnection);

            $results['migrated_plans']++;

        } catch (\Exception $e) {
            $results['errors'][] = "Error migrando plan {$p->id}: " . $e->getMessage();
            Log::error("Error en migración de plan", [
                'plan_id' => $p->id,
                'patient_id' => $p->paciente_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
```

---

## 🔴 CORRECCIÓN 2: Validar IDs en migrateBalance()

**Línea 527 - Agregar validaciones:**

```php
private function migrateBalance(array $patientIds, array &$results, array $idMapping, string $legacyConnection = 'legacy', string $targetConnection = 'mysql')
{
    $balances = Balance::on($legacyConnection)->where('monto', '>', 0)
        ->where('estado', 1)
        ->whereIn('paciente_id', $patientIds)
        ->whereNotIn('id', CreditNote::on($targetConnection)->pluck('id')->toArray())
        ->get();

    foreach ($balances as $balance) {
        try {
            // VALIDACIÓN 1: Verificar que el paciente esté en el mapeo
            if (!isset($idMapping[$balance->paciente_id])) {
                $results['errors'][] = "Paciente {$balance->paciente_id} no fue migrado, saltando balance {$balance->id}";
                continue;
            }

            $finalPatientId = $idMapping[$balance->paciente_id];

            // VALIDACIÓN 2: Verificar que el paciente exista en la base de datos objetivo
            if (!Patient::on($targetConnection)->find($finalPatientId)) {
                $results['errors'][] = "Paciente con ID final {$finalPatientId} (ID viejo: {$balance->paciente_id}) no existe en la BD, saltando balance {$balance->id}";
                continue;
            }

            // VALIDACIÓN 3: Verificar que el ID del balance no exista ya
            if (CreditNote::on($targetConnection)->where('id', $balance->id)->exists()) {
                $results['errors'][] = "Balance con ID {$balance->id} ya existe, saltando";
                Log::warning("Intento de sobrescribir balance existente", [
                    'balance_id' => $balance->id,
                    'existing_patient_id' => CreditNote::on($targetConnection)->find($balance->id)->patient_id ?? 'unknown',
                    'new_patient_id' => $finalPatientId
                ]);
                continue;
            }

            CreditNote::on($targetConnection)->create([
                'id' => $balance->id,
                'patient_id' => $finalPatientId,
                'amount' => $balance->monto,
                'payment_method_id' => PaymentMethodType::NOTA_CREDITO->value,
                'note' => "Balance de paciente migrado",
            ]);

            $results['migrated_balances']++;

        } catch (\Exception $e) {
            $results['errors'][] = "Error migrando balance {$balance->id}: " . $e->getMessage();
        }
    }
}
```

---

## 🔴 CORRECCIÓN 3: Validar IDs en migrateCompras()

**Línea 568 - Agregar validaciones:**

```php
private function migrateCompras(array $patientIds, array &$results, array $idMapping, string $legacyConnection = 'legacy', string $targetConnection = 'mysql')
{
    $comprasTipo = [
        1 => ItemType::CONSULTA->value,
        2 => ItemType::RADIOGRAFIA->value,
        3 => ItemType::COMPARACION->value,
        4 => ItemType::REPORTE->value,
        5 => ItemType::AJUSTE->value,
        6 => ItemType::AJUSTE->value,
        7 => ItemType::TERAPIA_FISICA->value,
        8 => ItemType::TRACCION->value,
        9 => ItemType::TERAPIA_FISICA->value,
    ];

    $compras = Compra::on($legacyConnection)->where('estado', 1)
        ->where('tipo_servicio', '!=', 0)
        ->whereIn('paciente_id', $patientIds)
        ->get();

    foreach ($compras as $compra) {
        try {
            // VALIDACIÓN 1: Verificar que el paciente esté en el mapeo
            if (!isset($idMapping[$compra->paciente_id])) {
                $results['errors'][] = "Paciente {$compra->paciente_id} no fue migrado, saltando compra {$compra->id}";
                continue;
            }

            $finalPatientId = $idMapping[$compra->paciente_id];

            // VALIDACIÓN 2: Verificar que el paciente exista en la base de datos objetivo
            if (!Patient::on($targetConnection)->find($finalPatientId)) {
                $results['errors'][] = "Paciente con ID final {$finalPatientId} (ID viejo: {$compra->paciente_id}) no existe en la BD, saltando compra {$compra->id}";
                continue;
            }

            // VALIDACIÓN 3: Verificar que el ID de la compra no exista ya
            if (PatientItem::on($targetConnection)->where('id', $compra->id)->exists()) {
                $existingItem = PatientItem::on($targetConnection)->find($compra->id);
                if ($existingItem->patient_id != $finalPatientId) {
                    $results['errors'][] = "Compra con ID {$compra->id} ya existe para otro paciente (ID: {$existingItem->patient_id}), saltando";
                    Log::warning("Intento de sobrescribir compra de otro paciente", [
                        'compra_id' => $compra->id,
                        'existing_patient_id' => $existingItem->patient_id,
                        'new_patient_id' => $finalPatientId
                    ]);
                    continue;
                }
            }

            $item = Item::on($targetConnection)->where('type_of_item_id', $comprasTipo[$compra->tipo_servicio])->first();
            if (!$item) {
                $item = Item::on($targetConnection)->factory()->create([
                    'type_of_item_id' => $comprasTipo[$compra->tipo_servicio],
                ]);
            }

            PatientItem::on($targetConnection)->updateOrCreate(
                ['id' => $compra->id],
                [
                    'id' => $compra->id,
                    'patient_id' => $finalPatientId,
                    'item_id' => $item->id,
                    'description' => $compra->servicio,
                    'quantity' => 1,
                    'total' => $compra->costo,
                    'created_at' => $compra->fecha_comprado,
                ]
            );

            $results['migrated_purchases']++;

        } catch (\Exception $e) {
            $results['errors'][] = "Error migrando compra {$compra->id}: " . $e->getMessage();
        }
    }
}
```

---

## 🔴 CORRECCIÓN 4: Mejorar generación de IDs en determineFinalPatientIdOptimized()

**Línea 1046 - Reemplazar método completo:**

```php
private function determineFinalPatientIdOptimized($legacyPatient, &$existingPatientIds, $existingPatientsByName)
{
    $originalId = (int) $legacyPatient->id;
    $legacyFullName = strtolower(trim(($legacyPatient->nombre ?? '') . ' ' . ($legacyPatient->apellido ?? '')));

    // Verificar si ya existe un paciente con el mismo nombre completo
    if (isset($existingPatientsByName[$legacyFullName])) {
        return null; // Ya existe un paciente con el mismo nombre
    }

    // Verificar si el ID original existe
    if (!in_array($originalId, $existingPatientIds)) {
        // Agregar el ID al array para futuras verificaciones
        $existingPatientIds[] = $originalId;
        return $originalId; // El ID no existe, usar el original
    }

    // El ID existe, generar nuevo ID con multiplicador (NO concatenación)
    // MEJORA: Usar suma matemática en lugar de concatenación de strings
    // Esto evita generar IDs en rangos específicos como 10000-11000
    
    $multiplier = 1;
    $maxAttempts = 100;
    $attempts = 0;
    
    // Estrategia: Sumar un offset grande al ID original
    // Ej: originalId = 500 → newId = 500 + 900000 = 900500
    $baseOffset = 900000; // IDs seguros en el rango 900000+
    
    do {
        $newId = $originalId + ($baseOffset * $multiplier);
        $multiplier++;
        $attempts++;
        
        if ($attempts >= $maxAttempts) {
            // Si llegamos al límite, generar un ID aleatorio muy alto
            do {
                $newId = rand(950000000, 999999999);
            } while (in_array($newId, $existingPatientIds));
            break;
        }
    } while (in_array($newId, $existingPatientIds));

    // Log del cambio de ID para auditoría
    Log::info("ID de paciente generado", [
        'original_id' => $originalId,
        'new_id' => $newId,
        'patient_name' => "{$legacyPatient->nombre} {$legacyPatient->apellido}",
        'reason' => 'ID original ya existe en la base de datos',
        'method' => $attempts >= $maxAttempts ? 'random' : 'offset'
    ]);

    // Agregar el nuevo ID al array para futuras verificaciones
    $existingPatientIds[] = $newId;

    return $newId;
}
```

---

## 🔴 CORRECCIÓN 5: Agregar método de validación de integridad

**Agregar al final de la clase (antes del último }):**

```php
/**
 * Validar integridad de la migración antes de commit
 * Retorna true si todo está correcto, false si hay problemas
 */
private function validateMigrationIntegrity(array $idMapping, array $patientIds, string $legacyConnection, string $targetConnection)
{
    $issues = [];
    
    // 1. Validar que todos los pacientes en $idMapping existen en target
    foreach ($idMapping as $oldId => $newId) {
        if (!Patient::on($targetConnection)->find($newId)) {
            $issues[] = "Patient mapping error: Legacy ID {$oldId} → New ID {$newId} (patient doesn't exist in target)";
        }
    }
    
    // 2. Validar que no hay planes duplicados para un mismo paciente
    $plansPerPatient = AssignedPlan::on($targetConnection)
        ->whereIn('patient_id', array_values($idMapping))
        ->select('patient_id', DB::raw('COUNT(*) as plan_count'))
        ->groupBy('patient_id')
        ->having('plan_count', '>', 1)
        ->get();
    
    foreach ($plansPerPatient as $patientPlans) {
        $issues[] = "Patient {$patientPlans->patient_id} has {$patientPlans->plan_count} plans (should have only 1)";
    }
    
    // 3. Validar que todas las transacciones tienen patient_id correcto
    $invalidTransactions = DB::connection($targetConnection)
        ->table('transactions')
        ->whereIn('assigned_plan_id', function($query) use ($idMapping, $targetConnection) {
            $query->select('id')
                ->from('assigned_plans')
                ->whereIn('patient_id', array_values($idMapping));
        })
        ->whereNotIn('patient_id', array_values($idMapping))
        ->count();
    
    if ($invalidTransactions > 0) {
        $issues[] = "Found {$invalidTransactions} transactions with incorrect patient_id";
    }
    
    // 4. Validar que no hay conflictos de ID
    $conflictingPlans = AssignedPlan::on($targetConnection)
        ->whereIn('id', function($query) use ($patientIds, $legacyConnection) {
            $query->select('id')
                ->from((new Ajuste())->getTable())
                ->setConnection($legacyConnection)
                ->whereIn('paciente_id', $patientIds);
        })
        ->get();
    
    foreach ($conflictingPlans as $plan) {
        $legacyPlan = Ajuste::on($legacyConnection)->find($plan->id);
        if ($legacyPlan && $legacyPlan->paciente_id != $plan->patient_id) {
            $issues[] = "Plan ID {$plan->id} conflict: Legacy patient {$legacyPlan->paciente_id} vs Target patient {$plan->patient_id}";
        }
    }
    
    if (!empty($issues)) {
        Log::error("Migration integrity validation failed", ['issues' => $issues]);
        foreach ($issues as $issue) {
            echo "\n❌ " . $issue;
        }
        return false;
    }
    
    Log::info("Migration integrity validation passed");
    echo "\n✅ Migration integrity validation passed";
    return true;
}
```

---

## 📝 CÓMO APLICAR ESTAS CORRECCIONES

1. **Hacer backup de MigrationController.php**
2. **Aplicar las correcciones en orden**
3. **Ejecutar pruebas en ambiente de desarrollo**
4. **Validar con pacientes de prueba antes de producción**

## ⚠️ ADVERTENCIA IMPORTANTE

**NO EJECUTAR MIGRACIONES EN PRODUCCIÓN HASTA:**
- [ ] Aplicar todas las correcciones
- [ ] Probar en ambiente de desarrollo
- [ ] Verificar logs de auditoría
- [ ] Validar integridad de datos
- [ ] Hacer backup completo de base de datos

