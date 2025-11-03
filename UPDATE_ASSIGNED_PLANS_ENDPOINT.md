# Endpoint para Actualizar Planes Asignados desde Base de Datos Legacy

## Descripción
Este endpoint permite comparar y actualizar los planes asignados de pacientes utilizando los datos de la base de datos legacy (tabla `ajuste`). Revisa diferencias en campos importantes como `therapies_number`, `total_sessions`, `amount`, y fechas.

## Endpoint
```
POST /api/update-assigned-plans-from-legacy
```

## Parámetros de Entrada

### Opción 1: Actualizar planes específicos
```json
{
    "assigned_plan_ids": [123, 456, 789]
}
```

### Opción 2: Actualizar planes de pacientes específicos
```json
{
    "patient_ids": [1, 2, 3, 4]
}
```

### Opción 3: Actualizar planes por sucursal (branch_id)
```json
{
    "branch_ids": [1, 2, 3]
}
```

### Opción 4: Actualizar todos los planes (máximo 1000)
```json
{
    "check_all": true
}
```

## Campos Comparados

El endpoint compara los siguientes campos entre la base de datos actual y la legacy:

1. **therapies_number**: Número de terapias físicas
   - Actual: `assigned_plans.therapies_number`
   - Legacy: `ajuste.terapias_fisicas`

2. **amount**: Costo del plan
   - Actual: `assigned_plans.amount`
   - Legacy: `ajuste.costo`

3. **date_start**: Fecha de inicio del plan
   - Actual: `assigned_plans.date_start`
   - Legacy: `ajuste.fecha_ciclo_insertada`

4. **date_end**: Fecha de expiración del plan
   - Actual: `assigned_plans.date_end`
   - Legacy: `ajuste.fecha_expiracion`

## Respuesta de Éxito

```json
{
    "success": true,
    "checked_plans": 25,
    "updated_plans": 3,
    "differences_found": [
        {
            "assigned_plan_id": 123,
            "patient_id": 456,
            "patient_name": "Juan Pérez",
            "plan_name": "Plan Básico",
            "differences": {
                "therapies_number": {
                    "current": 10,
                    "legacy": 12
                },
                "amount": {
                    "current": 5000.00,
                    "legacy": 5500.00
                }
            }
        }
    ],
    "errors": []
}
```

## Respuesta de Error

```json
{
    "success": false,
    "message": "Error durante la actualización: [mensaje de error]",
    "errors": ["Detalle del error"]
}
```

## Ejemplos de Uso

### 1. Actualizar planes de un paciente específico
```bash
curl -X POST http://localhost:8000/api/update-assigned-plans-from-legacy \
  -H "Content-Type: application/json" \
  -d '{"patient_ids": [123]}'
```

### 2. Actualizar planes específicos
```bash
curl -X POST http://localhost:8000/api/update-assigned-plans-from-legacy \
  -H "Content-Type: application/json" \
  -d '{"assigned_plan_ids": [456, 789]}'
```

### 3. Actualizar planes por sucursal
```bash
curl -X POST http://localhost:8000/api/update-assigned-plans-from-legacy \
  -H "Content-Type: application/json" \
  -d '{"branch_ids": [1, 2]}'
```

### 4. Revisar todos los planes
```bash
curl -X POST http://localhost:8000/api/update-assigned-plans-from-legacy \
  -H "Content-Type: application/json" \
  -d '{"check_all": true}'
```

## Características Importantes

- **Transaccional**: Todas las actualizaciones se realizan dentro de una transacción de base de datos
- **Logging**: Se registran todas las actualizaciones y errores en los logs de Laravel
- **Límite de seguridad**: Máximo 1000 planes cuando se usa `check_all`
- **Tolerancia a errores**: Si un plan falla, continúa procesando los demás
- **Memoria optimizada**: Configurado para manejar grandes volúmenes de datos

## Validaciones

- `patient_ids`: Debe ser un array de enteros
- `assigned_plan_ids`: Debe ser un array de enteros
- `branch_ids`: Debe ser un array de enteros
- `check_all`: Debe ser un booleano
- Se debe especificar al menos uno de los cuatro parámetros

## Logs

El endpoint genera logs detallados:
- **Info**: Cuando se actualiza un plan exitosamente
- **Error**: Cuando ocurre un error procesando un plan específico
- **Error**: Cuando ocurre un error general en el proceso

Los logs incluyen:
- ID del plan asignado
- ID del paciente
- Diferencias encontradas
- Detalles del error (si aplica)
- Stack trace completo (en caso de errores)
