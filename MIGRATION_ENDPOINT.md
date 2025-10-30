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
