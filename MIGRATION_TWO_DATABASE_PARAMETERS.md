# Sistema de Doble Selección de Base de Datos para Migración

## Resumen de Cambios

Se ha implementado un sistema de doble selección de base de datos que permite controlar de manera independiente:
1. **Base de datos ORIGEN** (lectura): De dónde se migran los datos
2. **Base de datos DESTINO** (escritura): Donde se guardan los datos migrados

## Nuevos Parámetros

### `legacy_database` (Base de datos de ORIGEN)
- **Propósito**: Controla de dónde se **leen** los datos legacy
- **Valores aceptados**: `legacy`, `produccion`
- **Valor por defecto**: `legacy`
- **Uso**: Solo lectura/consultas

### `target_database` (Base de datos de DESTINO)
- **Propósito**: Controla donde se **escriben/guardan** los datos migrados
- **Valores aceptados**: `mysql`, `produccion`
- **Valor por defecto**: `mysql`
- **Uso**: Escritura (inserts, updates, creates)

## Casos de Uso

### Caso 1: Desarrollo/Testing (por defecto)
```json
{
  "patient_ids": [1, 2, 3]
}
```
- Lee de: `legacy` (base de datos legacy de desarrollo)
- Escribe en: `mysql` (base de datos principal de desarrollo)

### Caso 2: Migración desde producción a desarrollo
```json
{
  "patient_ids": [1, 2, 3],
  "legacy_database": "produccion"
}
```
- Lee de: `produccion` (base de datos legacy de producción)
- Escribe en: `mysql` (base de datos principal de desarrollo)

### Caso 3: Preparación de datos en producción
```json
{
  "patient_ids": [1, 2, 3],
  "target_database": "produccion"
}
```
- Lee de: `legacy` (base de datos legacy de desarrollo)
- Escribe en: `produccion` (base de datos de producción)

### Caso 4: Migración en producción
```json
{
  "patient_ids": [1, 2, 3],
  "legacy_database": "produccion",
  "target_database": "produccion"
}
```
- Lee de: `produccion` legacy
- Escribe en: `produccion` destino

## Endpoints Actualizados

### 1. POST `/api/migrate-patients`
Migra pacientes y todos sus datos relacionados.

**Parámetros:**
- `patient_ids` (array, requerido)
- `legacy_database` (string, opcional, default: 'legacy')
- `target_database` (string, opcional, default: 'mysql')

### 2. POST `/api/update-assigned-plans-from-legacy`
Actualiza planes asignados comparando con datos legacy.

**Parámetros:**
- `patient_ids` | `assigned_plan_ids` | `branch_ids` | `check_all` (uno requerido)
- `legacy_database` (string, opcional, default: 'legacy')
- `target_database` (string, opcional, default: 'mysql')

## Métodos del Controlador Actualizados

Todos estos métodos ahora aceptan ambos parámetros de conexión:

### Métodos Principales
1. `migratePatientsByIds()` - Endpoint principal de migración
2. `updateAssignedPlansFromLegacy()` - Actualización de planes

### Métodos Privados de Migración
1. `migratePatients()` - Migra pacientes y citas
2. `migratePlanes()` - Migra planes asignados
3. `migrateBalance()` - Migra balance de pacientes
4. `migrateCompras()` - Migra compras
5. `migrateAntecedentes()` - Migra antecedentes médicos
6. `migrateHistorialAjuste()` - Migra historial de ajustes
7. `migrateHistorialTerapiaFisicas()` - Migra historial de terapias

### Métodos Auxiliares
1. `createVouchersAndServices()` - Crea vouchers y servicios
2. `processVertebrae()` - Procesa zonas de vértebras

## Implementación Técnica

### Operaciones de Lectura (Legacy Connection)
Usan `$legacyConnection`:
- `Paciente::on($legacyConnection)->whereIn(...)`
- `Ajuste::on($legacyConnection)->whereIn(...)`
- `Balance::on($legacyConnection)->where(...)`
- `Compra::on($legacyConnection)->where(...)`
- `Antecedente::on($legacyConnection)->whereIn(...)`
- `HistorialAjuste::on($legacyConnection)->whereIn(...)`
- `HistorialTerapia::on($legacyConnection)->whereIn(...)`

### Operaciones de Escritura (Target Connection)
Usan `$targetConnection`:
- `Patient::on($targetConnection)->upsert(...)`
- `Appointment::on($targetConnection)->insert(...)`
- `AssignedPlan::on($targetConnection)->create(...)`
- `CreditNote::on($targetConnection)->create(...)`
- `PatientItem::on($targetConnection)->updateOrCreate(...)`
- `MedicalRecord::on($targetConnection)->create(...)`
- `AcquiredService::on($targetConnection)->create(...)`
- `Voucher::on($targetConnection)->create(...)`

### Transacciones
Las transacciones ahora usan la conexión destino:
```php
DB::connection($targetConnection)->beginTransaction();
// ... operaciones ...
DB::connection($targetConnection)->commit();
// o
DB::connection($targetConnection)->rollBack();
```

## Configuración de Variables de Entorno

```env
# Base de datos legacy (origen)
DB_LEGACY_HOST=127.0.0.1
DB_LEGACY_DATABASE=nombre_db_legacy
DB_LEGACY_USERNAME=usuario_legacy
DB_LEGACY_PASSWORD=password_legacy

# Base de datos MySQL principal (destino)
DB_HOST=127.0.0.1
DB_DATABASE=nombre_db_principal
DB_USERNAME=usuario_principal
DB_PASSWORD=password_principal

# Base de datos de producción (puede ser origen o destino)
DB_DATABASE_PRODUCCION=nombre_db_produccion
DB_USERNAME_PRODUCCION=usuario_produccion
DB_PASSWORD_PRODUCCION=password_produccion
```

## Validación y Seguridad

### Validación de Parámetros
- `legacy_database`: Solo acepta 'legacy' o 'produccion'
- `target_database`: Solo acepta 'mysql' o 'produccion'
- Cualquier otro valor retorna error HTTP 422

### Seguridad de Transacciones
- Cada migración usa su propia transacción
- Rollback automático en caso de error
- Logging detallado de errores incluyendo qué conexiones se usaron

### Logging Mejorado
Los errores ahora incluyen información de ambas conexiones:
```php
Log::error('Error en migración de pacientes: ' . $e->getMessage(), [
    'patient_ids' => $patientIds,
    'legacy_database' => $legacyConnection,  // Nueva información
    'target_database' => $targetConnection,  // Nueva información
    'trace' => $e->getTraceAsString()
]);
```

## Ventajas del Sistema

1. **Flexibilidad**: Puedes leer de una base de datos y escribir en otra
2. **Testing Seguro**: Prueba con datos de producción sin afectar producción
3. **Migración Controlada**: Control fino sobre origen y destino de datos
4. **Reversibilidad**: Fácil rollback cambiando parámetros
5. **Auditoría**: Logs claros de qué conexiones se usaron

## Mejores Prácticas

### En Desarrollo
```json
{
  "patient_ids": [1, 2, 3],
  "legacy_database": "produccion",  // Leer datos reales
  "target_database": "mysql"        // Escribir en desarrollo
}
```

### En Staging
```json
{
  "patient_ids": [1, 2, 3],
  "legacy_database": "produccion",
  "target_database": "produccion"   // Simular producción completa
}
```

### En Producción
```json
{
  "patient_ids": [1, 2, 3],
  "legacy_database": "produccion",
  "target_database": "produccion"
}
```

## Archivos Modificados

1. **app/Http/Controllers/MigrationController.php**
   - Añadido parámetro `target_database` en métodos públicos
   - Actualizado todos los métodos privados para usar ambas conexiones
   - Mejorado manejo de transacciones con conexiones específicas

2. **routes/api.php**
   - Actualizados comentarios de documentación

3. **MIGRATION_DATABASE_SELECTION.md**
   - Actualizada documentación completa

4. **MIGRATION_EXAMPLES.http**
   - Actualizados ejemplos con ambos parámetros

5. **MIGRATION_TWO_DATABASE_PARAMETERS.md** (nuevo)
   - Documentación técnica detallada

## Testing

### Validación de Parámetros
```bash
# Test 1: Parámetro legacy_database inválido (debe retornar 422)
curl -X POST /api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{"patient_ids": [1], "legacy_database": "invalid"}'

# Test 2: Parámetro target_database inválido (debe retornar 422)
curl -X POST /api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{"patient_ids": [1], "target_database": "invalid"}'
```

### Pruebas Funcionales
```bash
# Test 3: Lectura de producción, escritura en desarrollo
curl -X POST /api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{"patient_ids": [1,2,3], "legacy_database": "produccion"}'

# Test 4: Escritura directa en producción
curl -X POST /api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{"patient_ids": [1,2,3], "target_database": "produccion"}'
```

## Notas Importantes

1. **Permisos**: Asegúrate de que los usuarios de BD tengan permisos apropiados
2. **Backups**: Siempre haz backup antes de migrar a producción
3. **Testing**: Prueba primero con `target_database: "mysql"` 
4. **Monitoreo**: Revisa logs para detectar problemas
5. **Performance**: Las migraciones grandes pueden tardar varios minutos

