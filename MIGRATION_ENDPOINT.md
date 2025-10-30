# Endpoint de Migración de Pacientes

## Descripción
Este endpoint permite migrar pacientes específicos desde la base de datos legacy hacia la nueva estructura, junto con todos sus datos relacionados.

## URL
```
POST /api/migrate-patients
```

## Parámetros de Entrada

### Request Body (JSON)
```json
{
    "patient_ids": [1, 2, 3, 4, 5]
}
```

- **patient_ids** (array, requerido): Array de IDs de pacientes a migrar
  - Cada ID debe ser un entero positivo
  - Mínimo 1 ID requerido

## Respuesta

### Respuesta Exitosa (200)
```json
{
    "success": true,
    "migrated_patients": 5,
    "migrated_appointments": 3,
    "migrated_plans": 4,
    "migrated_balances": 2,
    "migrated_purchases": 8,
    "migrated_medical_records": 5,
    "migrated_adjustments": 12,
    "migrated_therapies": 15,
    "errors": []
}
```

### Respuesta con Errores Parciales (200)
```json
{
    "success": true,
    "migrated_patients": 3,
    "migrated_appointments": 2,
    "migrated_plans": 2,
    "migrated_balances": 1,
    "migrated_purchases": 4,
    "migrated_medical_records": 3,
    "migrated_adjustments": 8,
    "migrated_therapies": 10,
    "errors": [
        "Error migrando paciente 4: Paciente no encontrado en legacy",
        "Plan no encontrado - ID: 123"
    ]
}
```

### Respuesta de Error (500)
```json
{
    "success": false,
    "message": "Error durante la migración: Database connection failed",
    "errors": ["Database connection failed"]
}
```

## Proceso de Migración

El endpoint ejecuta la migración en el siguiente orden:

1. **Pacientes**: Migra los datos básicos del paciente y sus citas
2. **Planes Asignados**: Migra los planes asignados con transacciones y vouchers
3. **Balance**: Migra las notas de crédito
4. **Compras**: Migra las compras de servicios individuales
5. **Antecedentes**: Migra los registros médicos
6. **Historial de Ajuste**: Migra los historiales de ajustes quiroprácticos
7. **Historial Terapia Física**: Migra los historiales de terapia física

## Características

- **Transaccional**: Toda la migración se ejecuta en una transacción de base de datos
- **Rollback automático**: Si ocurre un error crítico, todos los cambios se revierten
- **Errores parciales**: Los errores en registros individuales no detienen el proceso completo
- **Validaciones**: Verifica la existencia de datos relacionados antes de migrar
- **Logging**: Registra errores detallados para debugging

## Validaciones Incluidas

- Verificación de existencia de pacientes en legacy
- Validación de planes no ignorados
- Verificación de usuarios existentes
- Validación de datos de referencia (grupos, centros, etc.)
- Prevención de duplicados

## Ejemplo de Uso

### Con cURL
```bash
curl -X POST http://localhost:8000/api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{"patient_ids": [1, 2, 3, 4, 5]}'
```

### Con JavaScript (Fetch)
```javascript
const response = await fetch('/api/migrate-patients', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        patient_ids: [1, 2, 3, 4, 5]
    })
});

const result = await response.json();
console.log(result);
```

## Notas Importantes

1. **Rendimiento**: Para grandes volúmenes de datos, considere migrar en lotes más pequeños
2. **Memoria**: El proceso mantiene algunos datos en memoria para optimización
3. **Tiempo de ejecución**: Puede tomar varios minutos dependiendo del volumen de datos
4. **Duplicados**: El endpoint evita migrar pacientes que ya existen en la nueva base de datos
5. **Dependencias**: Algunos datos requieren que otros estén presentes (ej: planes requieren pacientes)

## Códigos de Error Comunes

- **422**: Datos de entrada inválidos (IDs faltantes o formato incorrecto)
- **500**: Error interno del servidor (problemas de base de datos, etc.)

## Logging

Los errores se registran en el log de Laravel con contexto adicional:
- IDs de pacientes procesados
- Stack trace completo
- Timestamp del error

---

## PUT /api/assigned-plans/{assignedPlanId}

### Descripción
Actualiza los datos de un plan asignado a un paciente, incluyendo el número de terapias y sesiones. Este endpoint permite corregir las terapias o sesiones consumidas, creando o eliminando los servicios correspondientes para mantener un conteo preciso.

### URL
```
PUT /api/assigned-plans/{assignedPlanId}
```

### Parámetros de URL
- `assignedPlanId` (integer, requerido): ID del plan asignado a actualizar

### Request Body
```json
{
  "therapies_number": 10,        // Número total de terapias del plan (opcional)
  "consumed_therapies": 3,       // Número de terapias consumidas (opcional)
  "total_sessions": 20,          // Número total de sesiones del plan (opcional)
  "consumed_sessions": 5,        // Número de sesiones consumidas (opcional)
  "amount": 1500.00,            // Monto total del plan (opcional)
  "status": 1,                  // Estado del plan: 1=Activo, 2=Expirado, 3=Completado, 4=Desactivado (opcional)
  "date_start": "2024-01-01",   // Fecha de inicio del plan (opcional)
  "date_end": "2024-12-31"      // Fecha de fin del plan (opcional)
}
```

### Campos del Request

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| therapies_number | integer | No | Número total de terapias físicas disponibles en el plan |
| consumed_therapies | integer | No | Número de terapias que han sido consumidas. El sistema ajustará los servicios para coincidir con este número |
| total_sessions | integer | No | Número total de sesiones de ajuste disponibles en el plan |
| consumed_sessions | integer | No | Número de sesiones que han sido consumidas. El sistema ajustará los servicios para coincidir con este número |
| amount | numeric | No | Monto total del plan asignado |
| status | integer | No | Estado del plan (1=Activo, 2=Expirado, 3=Completado, 4=Desactivado) |
| date_start | date | No | Fecha de inicio del plan |
| date_end | date | No | Fecha de finalización del plan (debe ser igual o posterior a date_start) |

### Lógica de Ajuste de Servicios

#### Para Sesiones (Ajustes)
- Si `consumed_sessions` es **mayor** que las sesiones actualmente consumidas:
  - Se crean nuevos registros de `AcquiredService` con tipo AJUSTE
  - Cada servicio se marca como COMPLETADA
  - Se asigna el precio calculado por ítem

- Si `consumed_sessions` es **menor** que las sesiones actualmente consumidas:
  - Se eliminan los servicios más recientes hasta alcanzar el número objetivo
  - Los servicios se eliminan en orden descendente por fecha de creación

#### Para Terapias Físicas
- Si `consumed_therapies` es **mayor** que las terapias actualmente consumidas:
  - Se crean nuevos registros de `AcquiredService` con tipo TERAPIA_FISICA
  - Cada servicio se marca como COMPLETADA
  - Se asigna el precio calculado por ítem

- Si `consumed_therapies` es **menor** que las terapias actualmente consumidas:
  - Se eliminan los servicios más recientes hasta alcanzar el número objetivo
  - Los servicios se eliminan en orden descendente por fecha de creación

#### Actualización de Vouchers
- Cuando se modifica el `amount`, `consumed_sessions` o `consumed_therapies`:
  - Se eliminan todos los vouchers existentes del plan
  - Se crean nuevos vouchers basados en el consumo total
  - Cada voucher representa un ítem consumido con su precio correspondiente

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Plan asignado actualizado exitosamente",
  "data": {
    "assigned_plan_id": 123,
    "patient_id": 456,
    "plan_id": 789,
    "therapies_number": 10,
    "total_sessions": 20,
    "consumed_therapies": 3,
    "consumed_sessions": 5,
    "remaining_therapies": 7,
    "remaining_sessions": 15,
    "amount": 1500.00,
    "status": 1,
    "date_start": "2024-01-01",
    "date_end": "2024-12-31",
    "item_price": 50.00
  }
}
```

### Response Error (404 Not Found)
```json
{
  "message": "No query results for model [App\\Models\\AssignedPlan] 999"
}
```

### Response Error (422 Validation Error)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "consumed_sessions": [
      "The consumed sessions field must be at least 0."
    ],
    "date_end": [
      "The date end field must be a date after or equal to date start."
    ]
  }
}
```

### Response Error (500 Server Error)
```json
{
  "success": false,
  "message": "Error al actualizar el plan asignado: [mensaje de error]"
}
```

### Ejemplo de Uso

#### Corregir sesiones consumidas
```bash
curl -X PUT http://tu-dominio.com/api/assigned-plans/123 \
  -H "Content-Type: application/json" \
  -d '{
    "consumed_sessions": 8,
    "consumed_therapies": 2
  }'
```

#### Actualizar información completa del plan
```bash
curl -X PUT http://tu-dominio.com/api/assigned-plans/123 \
  -H "Content-Type: application/json" \
  -d '{
    "therapies_number": 15,
    "consumed_therapies": 5,
    "consumed_sessions": 10,
    "amount": 2000.00,
    "status": 1,
    "date_start": "2024-01-15",
    "date_end": "2024-06-15"
  }'
```

### Notas Importantes

1. **Transaccional**: Todas las operaciones se ejecutan dentro de una transacción de base de datos. Si algo falla, todos los cambios se revierten.

2. **Cálculo de Precio**: El precio por ítem se recalcula automáticamente como: `amount / (total_sessions + therapies_number)`

3. **Estado Automático**: Después de la actualización, el sistema verifica si el plan está completado y actualiza el estado automáticamente si es necesario.

4. **Integridad de Datos**: El endpoint mantiene la consistencia entre:
   - Los servicios adquiridos (AcquiredService)
   - Los vouchers del plan
   - El estado del plan asignado

5. **Eliminación de Servicios**: Cuando se reducen los servicios consumidos, se eliminan los más recientes primero (LIFO - Last In, First Out).
