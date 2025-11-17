# Documentación: updateExistingPatientsData

## Descripción
Esta nueva función permite migrar datos faltantes del sistema viejo a pacientes que **ya existen** en el sistema nuevo. Es diferente a `migratePatientsByIds` porque:

- **No crea nuevos pacientes**, solo actualiza datos de pacientes existentes
- Permite **seleccionar específicamente** qué tipos de datos migrar
- Valida que los pacientes existan antes de migrar datos
- **Maneja IDs diferentes**: Usa la columna `old_id` del modelo Patient para mapear correctamente los datos del sistema legacy al nuevo

## Endpoint
```
POST /api/update-existing-patients-data
```

## ⚠️ Importante: Manejo de IDs Diferentes

**La función usa automáticamente la columna `old_id` del modelo Patient**:

- Si un paciente tiene `id=10001` en el sistema nuevo pero su `old_id=123`, la función:
  1. Busca el paciente por `id=10001` en el sistema nuevo
  2. Lee su `old_id=123`
  3. Busca datos asociados a `paciente_id=123` en el sistema legacy
  4. Migra esos datos y los asocia al `patient_id=10001` en el sistema nuevo

**No necesitas enviar los old_id manualmente**, la función los lee automáticamente de la base de datos.

## Parámetros

### Requeridos
- **patient_ids** (array): IDs de pacientes **del sistema nuevo** (no los IDs del sistema legacy)
- **data_to_migrate** (array): Tipos de datos a migrar. Opciones disponibles:
  - `'citas'` - Última cita completada del paciente
  - `'planes'` - Planes asignados
  - `'balance'` - Balance/notas de crédito
  - `'compras'` - Compras de servicios
  - `'antecedentes'` - Antecedentes médicos
  - `'historial_ajuste'` - Historial de ajustes quiroprácticos
  - `'historial_terapia'` - Historial de terapias físicas

### Opcionales
- **legacy_database** (string): Base de datos de origen ('legacy' o 'produccion'). Por defecto: 'legacy'
- **target_database** (string): Base de datos destino ('mysql' o 'produccion'). Por defecto: 'mysql'

## Ejemplos de Uso

### Ejemplo 1: Migrar solo planes y balance
Si tienes pacientes que ya están en el sistema nuevo pero les faltan sus planes y balance:

```json
{
  "patient_ids": [1, 2, 3, 4, 5],
  "data_to_migrate": ["planes", "balance"]
}
```

### Ejemplo 2: Migrar solo historial médico
Si solo necesitas migrar antecedentes médicos y historiales:

```json
{
  "patient_ids": [10, 20, 30],
  "data_to_migrate": ["antecedentes", "historial_ajuste", "historial_terapia"]
}
```

### Ejemplo 3: Migrar todo excepto paciente
Si necesitas migrar todos los datos posibles (útil cuando el paciente ya fue creado manualmente):

```json
{
  "patient_ids": [100],
  "data_to_migrate": [
    "citas",
    "planes",
    "balance",
    "compras",
    "antecedentes",
    "historial_ajuste",
    "historial_terapia"
  ]
}
```

### Ejemplo 4: Usando bases de datos específicas
Para migrar desde la BD de producción legacy a la BD de producción nueva:

```json
{
  "patient_ids": [50, 51, 52],
  "data_to_migrate": ["planes", "compras"],
  "legacy_database": "produccion",
  "target_database": "produccion"
}
```

### Ejemplo 5: Solo agregar citas faltantes
Si un paciente ya tiene todos sus datos pero le falta su última cita:

```json
{
  "patient_ids": [200],
  "data_to_migrate": ["citas"]
}
```

## Respuesta Exitosa

La respuesta incluye un campo `patient_mappings` que muestra cómo se mapearon los IDs:

```json
{
  "success": true,
  "processed_patients": 5,
  "migrated_appointments": 5,
  "migrated_plans": 12,
  "migrated_balances": 3,
  "migrated_purchases": 8,
  "migrated_medical_records": 5,
  "migrated_adjustments": 45,
  "migrated_therapies": 30,
  "errors": [],
  "patient_mappings": [
    {
      "new_id": 10001,
      "old_id": 123,
      "name": "Juan Pérez",
      "id_changed": true
    },
    {
      "new_id": 10002,
      "old_id": 124,
      "name": "María García",
      "id_changed": true
    },
    {
      "new_id": 500,
      "old_id": 500,
      "name": "Pedro López",
      "id_changed": false
    }
  ]
}
```

### Explicación de patient_mappings:
- **new_id**: ID del paciente en el sistema nuevo
- **old_id**: ID del paciente en el sistema legacy (obtenido de la columna `old_id`)
- **name**: Nombre completo del paciente (para verificación)
- **id_changed**: `true` si el ID cambió, `false` si se mantiene igual

## Errores Comunes

### Error 1: Paciente no existe en sistema nuevo
```json
{
  "success": false,
  "message": "Ninguno de los pacientes especificados existe en el sistema nuevo",
  "errors": [
    "Pacientes no encontrados en sistema nuevo: 999, 888"
  ]
}
```
**Solución**: Verifica que los IDs de pacientes existan en el sistema nuevo. Si no existen, usa `migratePatientsByIds` en su lugar.

### Error 2: Data type inválido
```json
{
  "success": false,
  "message": "The data_to_migrate.0 must be one of: planes,balance,compras,antecedentes,historial_ajuste,historial_terapia,citas",
  "errors": []
}
```
**Solución**: Verifica que estés usando solo los tipos de datos permitidos.

## Diferencias con migratePatientsByIds

| Característica | migratePatientsByIds | updateExistingPatientsData |
|----------------|----------------------|----------------------------|
| Crea pacientes nuevos | ✅ Sí | ❌ No |
| Requiere que paciente exista | ❌ No | ✅ Sí |
| Selección de datos | ❌ Migra todo | ✅ Selección flexible |
| Uso típico | Migración inicial | Correcciones/Actualizaciones |

## Casos de Uso Reales

### Caso 1: Paciente creado manualmente
Un paciente fue creado manualmente en el sistema nuevo pero ahora necesitas traer toda su información del sistema viejo:

```json
{
  "patient_ids": [12345],
  "data_to_migrate": ["planes", "balance", "compras", "antecedentes", "historial_ajuste", "historial_terapia"]
}
```

### Caso 2: Error en migración anterior
Durante una migración anterior, los planes no se migraron correctamente para ciertos pacientes:

```json
{
  "patient_ids": [100, 101, 102, 103, 104],
  "data_to_migrate": ["planes"]
}
```

### Caso 3: Actualizar solo información médica
Necesitas actualizar solo la información médica sin tocar datos financieros:

```json
{
  "patient_ids": [500, 501, 502],
  "data_to_migrate": ["antecedentes", "historial_ajuste", "historial_terapia"]
}
```

## Cómo funciona el Mapeo de IDs - Ejemplo Detallado

### Escenario:
Tienes un paciente en el sistema nuevo con estos datos:
- **ID en sistema nuevo**: `10001`
- **old_id**: `123`
- **Nombre**: Juan Pérez

### Proceso paso a paso:

1. **Envías la request**:
```json
{
  "patient_ids": [10001],
  "data_to_migrate": ["planes"]
}
```

2. **La función hace lo siguiente**:
   - Busca el paciente con `id=10001` en el sistema nuevo
   - Lee su columna `old_id=123`
   - Busca planes en el sistema legacy donde `paciente_id=123`
   - Migra esos planes y los asocia al `patient_id=10001` en el sistema nuevo

3. **Resultado**:
   - Los planes que estaban asociados al paciente `123` en legacy
   - Ahora están asociados al paciente `10001` en el sistema nuevo
   - ✅ El mapeo fue: `legacy[123]` → `nuevo[10001]`

### ¿Y si old_id es null?
Si un paciente no tiene `old_id` (es null), la función asume que su ID no cambió:
- Paciente con `id=500` y `old_id=null`
- La función busca datos de `paciente_id=500` en legacy
- Los asocia al `patient_id=500` en el sistema nuevo

## Notas Importantes

1. **No duplica datos**: Si un registro ya existe (por ejemplo, un plan con el mismo ID), la función maneja la actualización correctamente
2. **Transaccional**: Toda la operación se realiza en una transacción, si hay un error crítico, se hace rollback
3. **ID Mapping Inteligente**: La función usa `old_id` para mapear correctamente incluso cuando los IDs cambiaron
4. **Validaciones**: Valida que los pacientes existan antes de intentar migrar datos
5. **Errores parciales**: Si algunos pacientes no se encuentran, continúa con los que sí existen
6. **Transparencia**: La respuesta incluye `patient_mappings` para que veas exactamente cómo se mapearon los IDs

## Testing con Postman/Insomnia

```bash
POST http://localhost:8000/api/update-existing-patients-data
Content-Type: application/json

{
  "patient_ids": [1, 2, 3],
  "data_to_migrate": ["planes", "balance"]
}
```

## Monitoreo

Los logs de errores se guardan automáticamente en Laravel. Para ver los logs:

```bash
tail -f storage/logs/laravel.log
```

Busca entradas que comiencen con:
- `Error actualizando datos de pacientes existentes:`
- `Error preparando cita para paciente:`
- Cualquier error de las funciones de migración individuales

