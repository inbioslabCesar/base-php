# Actualización del Campo Sexo para Veterinarias

## Cambios Realizados

### 📝 Formulario de Cliente (`form_cliente.php`)
Se actualizó el campo sexo para incluir opciones tanto para humanos como para animales:

#### Opciones Disponibles:
- **👤 Humanos:**
  - Masculino
  - Femenino
  
- **🐾 Animales:**
  - Macho
  - Hembra
  
- **Otro** (para casos especiales)

### 🗄️ Base de Datos
Se debe ejecutar el script SQL para actualizar la estructura:

```sql
ALTER TABLE clientes 
MODIFY COLUMN sexo ENUM('masculino', 'femenino', 'macho', 'hembra', 'otro') 
COLLATE utf8mb4_unicode_ci;
```

### 📋 Instrucciones de Implementación

1. **Ejecutar el script SQL:**
   - Abrir phpMyAdmin o tu cliente MySQL
   - Seleccionar la base de datos del laboratorio
   - Ejecutar el contenido del archivo `sql/actualizar_sexo_clientes.sql`

2. **Verificar los cambios:**
   - Los formularios ya están actualizados
   - El campo ahora muestra opciones agrupadas por tipo
   - Se incluye texto de ayuda para el usuario

### 🎯 Funcionalidades
- **Separación visual:** Las opciones están agrupadas con `optgroup`
- **Iconos descriptivos:** 👤 para humanos, 🐾 para animales
- **Texto de ayuda:** Guía al usuario sobre qué opción elegir
- **Compatibilidad:** Mantiene todos los valores existentes

### 🔄 Retrocompatibilidad
- Los registros existentes no se ven afectados
- Las opciones anteriores siguen funcionando
- Solo se agregan nuevas opciones

## Uso Recomendado

### Para Pacientes Humanos:
- Usar "Masculino" o "Femenino"

### Para Pacientes Animales (Veterinarias):
- Usar "Macho" o "Hembra"

### Para Casos Especiales:
- Usar "Otro"

## Notas Técnicas
- El campo sigue siendo opcional (puede estar vacío)
- Se mantiene la validación existente
- Compatible con todos los navegadores modernos