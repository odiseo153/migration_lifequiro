# Selección de Base de Datos para Migración

Este documento explica cómo usar los parámetros `legacy_database` y `target_database` en los endpoints de migración para seleccionar dinámicamente las bases de datos de origen (lectura) y destino (escritura).

## Conceptos Clave

- **`legacy_database`**: Base de datos de **ORIGEN** (solo lectura/consultas) - de dónde se migran los datos
- **`target_database`**: Base de datos de **DESTINO** (escritura) - donde se guardan los datos migrados

## Bases de Datos Disponibles

Según la configuración en `config/database.php`, las opciones disponibles son:

### Para legacy_database (origen):
- `legacy` (por defecto): Base de datos legacy principal
- `produccion`: Base de datos de producción legacy

### Para target_database (destino):
- `mysql` (por defecto): Base de datos MySQL principal
- `produccion`: Base de datos de producción

## Endpoints que Soportan Selección de Base de Datos

### 1. Migrar Pacientes

**Endpoint:** `POST /api/migrate-patients`

**Parámetros:**
- `patient_ids` (array, requerido): IDs de los pacientes a migrar
- `legacy_database` (string, opcional): Base de datos de **origen** (`legacy` o `produccion`)
  - Valor por defecto: `legacy`
- `target_database` (string, opcional): Base de datos de **destino** (`mysql` o `produccion`)
  - Valor por defecto: `mysql`

**Ejemplo 1: Uso con valores por defecto (leer de legacy, escribir en mysql):**

```json
{
  "patient_ids": [1, 2, 3, 4, 5]
}
```

**Ejemplo 2: Leer de producción legacy, escribir en mysql:**

```json
{
  "patient_ids": [1, 2, 3, 4, 5],
  "legacy_database": "produccion"
}
```

**Ejemplo 3: Leer de legacy, escribir en producción:**

```json
{
  "patient_ids": [1, 2, 3, 4, 5],
  "target_database": "produccion"
}
```

**Ejemplo 4: Leer de producción legacy, escribir en producción:**

```json
{
  "patient_ids": [1, 2, 3, 4, 5],
  "legacy_database": "produccion",
  "target_database": "produccion"
}
```

**Ejemplo con cURL:**

```bash
# Ejemplo 1: Por defecto - leer de legacy, escribir en mysql
curl -X POST http://localhost/api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{
    "patient_ids": [1, 2, 3, 4, 5]
  }'

# Ejemplo 2: Leer de producción legacy, escribir en mysql
curl -X POST http://localhost/api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{
    "patient_ids": [1, 2, 3, 4, 5],
    "legacy_database": "produccion"
  }'

# Ejemplo 3: Leer de legacy, escribir en producción
curl -X POST http://localhost/api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{
    "patient_ids": [1, 2, 3, 4, 5],
    "target_database": "produccion"
  }'

# Ejemplo 4: Leer de producción legacy, escribir en producción
curl -X POST http://localhost/api/migrate-patients \
  -H "Content-Type: application/json" \
  -d '{
    "patient_ids": [1, 2, 3, 4, 5],
    "legacy_database": "produccion",
    "target_database": "produccion"
  }'
```

### 2. Actualizar Planes Asignados desde Legacy

**Endpoint:** `POST /api/update-assigned-plans-from-legacy`

**Parámetros:**
- `patient_ids` (array, opcional): IDs de pacientes
- `assigned_plan_ids` (array, opcional): IDs de planes asignados
- `branch_ids` (array, opcional): IDs de sucursales
- `check_all` (boolean, opcional): Revisar todos los planes
- `legacy_database` (string, opcional): Base de datos de **origen** (`legacy` o `produccion`)
  - Valor por defecto: `legacy`
- `target_database` (string, opcional): Base de datos de **destino** (`mysql` o `produccion`)
  - Valor por defecto: `mysql`

**Ejemplo de uso:**

```json
{
  "patient_ids": [1, 2, 3],
  "legacy_database": "produccion",
  "target_database": "mysql"
}
```

**Ejemplo con cURL:**

```bash
# Leer de producción legacy, actualizar en mysql
curl -X POST http://localhost/api/update-assigned-plans-from-legacy \
  -H "Content-Type: application/json" \
  -d '{
    "assigned_plan_ids": [100, 101, 102],
    "legacy_database": "produccion",
    "target_database": "mysql"
  }'
```

## Configuración de Bases de Datos

Las configuraciones de conexión se encuentran en `config/database.php`:

### Conexión Legacy

```php
'legacy' => [
    'driver' => 'mysql',
    'host' => env('DB_LEGACY_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_LEGACY_DATABASE', 'old_db'),
    'username' => env('DB_LEGACY_USERNAME', 'root'),
    'password' => env('DB_LEGACY_PASSWORD', ''),
    'charset' => 'latin1',
    'collation' => 'latin1_swedish_ci',
    'prefix' => '',
    'strict' => false,
],
```

### Conexión Producción

```php
'produccion' => [
    'driver' => 'mysql',
    'host' => env('DB_LEGACY_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE_PRODUCCION', 'old_db'),
    'username' => env('DB_USERNAME_PRODUCCION', 'root'),
    'password' => env('DB_PASSWORD_PRODUCCION', ''),
    'charset' => 'latin1',
    'collation' => 'latin1_swedish_ci',
    'prefix' => '',
    'strict' => false,
],
```

## Variables de Entorno

Asegúrate de configurar las siguientes variables en tu archivo `.env`:

```env
# Configuración Legacy
DB_LEGACY_HOST=127.0.0.1
DB_LEGACY_DATABASE=nombre_db_legacy
DB_LEGACY_USERNAME=usuario_legacy
DB_LEGACY_PASSWORD=password_legacy

# Configuración Producción
DB_DATABASE_PRODUCCION=nombre_db_produccion
DB_USERNAME_PRODUCCION=usuario_produccion
DB_PASSWORD_PRODUCCION=password_produccion
```

## Validación

**Parámetro `legacy_database` (origen):**
- Valores aceptados: `legacy`, `produccion`
- Si se proporciona un valor diferente, se devolverá un error de validación 422

**Parámetro `target_database` (destino):**
- Valores aceptados: `mysql`, `produccion`
- Si se proporciona un valor diferente, se devolverá un error de validación 422

## Respuesta del Endpoint

La respuesta incluye información sobre la migración realizada:

```json
{
  "success": true,
  "migrated_patients": 5,
  "migrated_appointments": 3,
  "migrated_plans": 10,
  "migrated_balances": 2,
  "migrated_purchases": 4,
  "migrated_medical_records": 5,
  "migrated_adjustments": 8,
  "migrated_therapies": 6,
  "errors": []
}
```

## Consideraciones Importantes

1. **Permisos de Base de Datos**: Asegúrate de que el usuario configurado tiene permisos de lectura en la base de datos seleccionada.

2. **Configuración de Charset**: La base de datos legacy usa `latin1` con colación `latin1_swedish_ci`. Asegúrate de que tus datos estén en el formato correcto.

3. **Transacciones**: Todas las migraciones se ejecutan dentro de transacciones para garantizar la integridad de los datos.

4. **Manejo de Errores**: Si ocurre un error durante la migración, se hace rollback automático y se devuelve información detallada del error.

5. **Límites de Ejecución**: Los endpoints están configurados con límites de memoria (2G) y tiempo de ejecución (600 segundos) aumentados para manejar grandes volúmenes de datos.

## Mejores Prácticas

1. **Probar en Desarrollo**: Siempre prueba las migraciones en un ambiente de desarrollo antes de ejecutarlas en producción.

2. **Migraciones por Lotes**: Para grandes cantidades de pacientes, considera migrar en lotes más pequeños.

3. **Verificar Conexiones**: Antes de migrar, verifica que ambas conexiones de base de datos estén funcionando correctamente.

4. **Respaldos**: Realiza respaldos de tu base de datos antes de ejecutar migraciones masivas.

5. **Monitorear Logs**: Revisa los logs de Laravel para detectar posibles problemas durante la migración.

