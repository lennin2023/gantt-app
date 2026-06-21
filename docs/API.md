# API Documentation - Gantt App

> Backend: Laravel 12 + Sanctum  
> Base URL: `/api`  
> Auth: Bearer token (Sanctum)  
> Content-Type: `application/json`

---

## Tabla de Contenidos

1. [Autenticación](#1-autenticación)
2. [Dashboard](#2-dashboard)
3. [Proyectos](#3-proyectos)
4. [Usuarios de Proyecto](#4-usuarios-de-proyecto)
5. [Tareas](#5-tareas)
6. [Asignaciones de Tarea](#6-asignaciones-de-tarea)
7. [Modelos de Datos](#7-modelos-de-datos)
8. [Enums y Catálogos](#8-enums-y-catálogos)
9. [Reglas de Negocio](#9-reglas-de-negocio)
10. [Transiciones de Estado](#10-transiciones-de-estado)
11. [Eventos](#11-eventos)
12. [Errores](#12-errores)

---

## 1. Autenticación

### POST `/api/auth/login`

Login y obtención de token Sanctum.

**Request:**

```json
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "postman" // opcional, default: "api-token"
}
```

**Response 200:**

```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "super_admin"
    }
  }
}
```

**Reglas:**

- `email`: required, email
- `password`: required, string
- Rate limited: `throttle:login`
- El token incluye abilities basadas en el rol del usuario

---

### POST `/api/auth/logout`

Revoca el token actual.

**Headers:** `Authorization: Bearer {token}`

**Response 200:**

```json
{
  "success": true,
  "message": "Sesión cerrada correctamente",
  "data": null
}
```

---

### POST `/api/auth/logout-all`

Revoca TODOS los tokens del usuario (todos los dispositivos).

**Headers:** `Authorization: Bearer {token}`

**Response 200:**

```json
{
  "success": true,
  "message": "Sesión cerrada en todos los dispositivos",
  "data": null
}
```

---

### GET `/api/auth/me`

Devuelve el usuario autenticado actual con su rol.

**Headers:** `Authorization: Bearer {token}`

**Response 200:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "super_admin"
  }
}
```

---

## 2. Dashboard

### GET `/api/dashboard/stats`

Estadísticas generales del usuario autenticado.

**Headers:** `Authorization: Bearer {token}`  
**Policy:** `viewDashboard`

**Response 200:**

```json
{
  "success": true,
  "data": {
    "metrics": {
      "total_projects": 5,
      "active_projects": 3,
      "total_tasks": 42,
      "completed_tasks": 18,
      "pending_tasks": 12,
      "in_progress_tasks": 8,
      "overdue_tasks": 4
    },
    "projects": [...]
  }
}
```

---

## 3. Proyectos

### GET `/api/projects`

Lista los proyectos del usuario autenticado con paginación.

**Query params:**
| Param | Tipo | Default | Descripción |
|-------|------|---------|-------------|
| `per_page` | int | 10 | Registros por página (max: 100) |
| `status_id` | int | null | Filtrar por estado del proyecto |

**Response 200:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Proyecto Alpha",
      "description": "...",
      "color": "#3B82F6",
      "start_date": "2026-01-15",
      "end_date": "2026-06-30",
      "status": { "id": 1, "name": "Activo" },
      "company": { "id": 1, "name": "Acme Corp" },
      "created_at": "2026-01-15T10:00:00Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### POST `/api/projects`

Crea un nuevo proyecto.

**Policy:** `create`  
**Request body:**

```json
{
  "company_id": 1,
  "name": "Proyecto Alpha",
  "description": "Descripción del proyecto",
  "color": "#3B82F6",
  "start_date": "2026-01-15",
  "end_date": "2026-06-30"
}
```

**Validación:**
| Campo | Create | Update | Reglas |
|-------|--------|--------|--------|
| `company_id` | required | sometimes | integer, exists:companies,id |
| `name` | required | sometimes | string, max:255 |
| `description` | nullable | nullable | string |
| `color` | required | sometimes | string, regex: `/^#[0-9A-Fa-f]{6}$/` |
| `start_date` | nullable | nullable | date |
| `end_date` | nullable | nullable | date, after_or_equal:start_date |

**Response 201:** Objeto Project completo

---

### GET `/api/projects/{project}`

Detalle de un proyecto con usuarios asignados.

**Query params:**
| Param | Tipo | Descripción |
|-------|------|-------------|
| `include_stats` | bool | Incluir estadísticas del proyecto |

**Response 200:** Project con relations `projectUsers.user`, `projectUsers.projectRole`

---

### PATCH `/api/projects/{project}`

Actualiza un proyecto. Soporta cambios de estado con validación de transiciones.

**Policy:** `update`

**Response 200:** Objeto Project actualizado

---

### DELETE `/api/projects/{project}`

Elimina (soft-delete) un proyecto.

**Policy:** `delete`  
**Estados permitidos:** ACTIVE, ON_HOLD, CANCELLED  
**No permite eliminar:** COMPLETED, DELETED

---

### POST `/api/projects/{project}/restore`

Restaura un proyecto eliminado.

**Policy:** `restore`  
**Solo funciona si:** el proyecto está en estado DELETED  
**Resultado:** el proyecto pasa a ACTIVE

---

## 4. Usuarios de Proyecto

### GET `/api/projects/{project}/users`

Lista los usuarios asignados a un proyecto.

---

### GET `/api/projects/{project}/users/role/{projectRole}`

Filtra usuarios de un proyecto por rol específico.

---

### POST `/api/projects/{project}/users`

Asigna un usuario a un proyecto con un rol.

**Request body:**

```json
{
  "user_id": 3,
  "project_role_id": 1
}
```

**Validación:**
| Campo | Reglas |
|-------|--------|
| `user_id` | required, exists:users,id |
| `project_role_id` | required, exists:project_roles,id |

**Reglas de negocio:**

- Un usuario no puede ser asignado dos veces al mismo proyecto
- Solo admin/super_admin pueden asignar usuarios

---

### DELETE `/api/projects/{project}/users/{user}`

Remueve un usuario de un proyecto.

**Policy:** `delete` (solo admin)

---

## 5. Tareas

### GET `/api/projects/{project}/tasks`

Lista las tareas de un proyecto con paginación.

**Query params:**
| Param | Tipo | Default | Descripción |
|-------|------|---------|-------------|
| `per_page` | int | 10 | Registros por página (max: 100) |

**Response 200:** Colección de TaskResource con relations: `status`, `parent`, `children`, `assignments.projectUser.user`, `assignments.taskRole`

---

### POST `/api/projects/{project}/tasks`

Crea una tarea, contenedor o hito.

**Request body:**

```json
{
  "type": "task",
  "parent_id": 5,
  "title": "Diseñar interfaz",
  "description": "Crear mockups",
  "task_status_id": 1,
  "start_date": "2026-01-15",
  "end_date": "2026-02-15",
  "progress": 0,
  "dependency_ids": [3, 4],
  "dependency_type": "finish_to_start"
}
```

**Validación por tipo:**

| Campo            | container      | task             | milestone                  |
| ---------------- | -------------- | ---------------- | -------------------------- |
| `type`           | required       | required         | required                   |
| `title`          | required       | required         | required                   |
| `description`    | **prohibited** | nullable         | **prohibited**             |
| `task_status_id` | **prohibited** | nullable         | nullable                   |
| `start_date`     | **prohibited** | nullable         | **required**               |
| `end_date`       | **prohibited** | nullable         | **prohibited** (auto-sync) |
| `progress`       | **prohibited** | nullable (0-100) | **prohibited**             |
| `parent_id`      | nullable       | nullable         | nullable                   |
| `dependency_ids` | nullable       | nullable         | nullable                   |

**Reglas de `parent_id`:**

- No puede ser la tarea misma (self-parent)
- El padre debe pertenecer al mismo proyecto
- El padre debe ser tipo `container`

**Reglas de `dependency_ids`:**

- Todas deben pertenecer al mismo proyecto
- No puede depender de sí misma
- No puede crear ciclos (cycle detection)

---

### GET `/api/tasks/{task}`

Detalle de una tarea con todas sus relations.

---

### PATCH `/api/tasks/{task}`

Actualiza una tarea.

**Reglas especiales:**

- Si el tipo es `container`: `task_status_id` y `progress` se ignoran (calculados automáticamente)
- Si el tipo es `milestone`: `end_date` se sincroniza con `start_date`
- No se puede actualizar una tarea con estado DELETED
- El cambio de estado se valida contra la matriz de transiciones

---

### DELETE `/api/tasks/{task}`

Soft-delete de una tarea.

**Estados permitidos:** PENDING, IN_PROGRESS, ON_HOLD, CANCELLED  
**No permite eliminar:** COMPLETED, DELETED  
**Efecto cascada:** Todos los descendientes pasan a DELETED (excepto COMPLETED)

---

### POST `/api/tasks/{task}/restore`

Restaura una tarea eliminada.

**Solo funciona si:** la tarea está en estado DELETED  
**Resultado:** la tarea y todos sus descendientes DELETED pasan a PENDING

---

### PATCH `/api/tasks/bulk-update`

Actualización masiva de tareas.

**Request body:**

```json
{
  "task_ids": [1, 2, 3],
  "data": {
    "task_status_id": 2,
    "progress": 50
  }
}
```

**Reglas:**

- Todas las tareas deben pertenecer al mismo proyecto
- Las tareas en estado DELETED se saltan
- Se validan transiciones de estado individualmente

---

## 6. Asignaciones de Tarea

### GET `/api/tasks/{task}/assignments`

Lista las asignaciones de una tarea.

---

### POST `/api/tasks/{task}/assignments`

Asigna un usuario a una tarea.

**Request body:**

```json
{
  "project_user_id": 5,
  "task_role_id": 2
}
```

**Validación:**
| Campo | Reglas |
|-------|--------|
| `project_user_id` | required, exists:project_users,id |
| `task_role_id` | nullable, exists:task_roles,id |

**Reglas de negocio:**

- `project_user_id` debe pertenecer al mismo proyecto que la tarea
- Un usuario no puede ser asignado dos veces a la misma tarea

---

### PATCH `/api/tasks/{task}/assignments/{assignment}`

Actualiza el rol de una asignación.

**Request body:**

```json
{
  "task_role_id": 3
}
```

---

### DELETE `/api/tasks/{task}/assignments/{assignment}`

Remueve la asignación de un usuario de una tarea.

---

## 7. Modelos de Datos

### User

| Campo        | Tipo   | Descripción     |
| ------------ | ------ | --------------- |
| `id`         | int    | PK              |
| `name`       | string | Nombre completo |
| `email`      | string | Email único     |
| `role_id`    | int    | FK → roles      |
| `created_by` | int    | FK → users      |
| `updated_by` | int    | FK → users      |

**Relations:** `role`, `creator`, `updater`, `createdProjects`, `createdTasks`

---

### Project

| Campo               | Tipo   | Descripción           |
| ------------------- | ------ | --------------------- |
| `id`                | int    | PK                    |
| `company_id`        | int    | FK → companies        |
| `project_status_id` | int    | FK → project_statuses |
| `name`              | string | Nombre del proyecto   |
| `description`       | string | Descripción           |
| `color`             | string | Color hex (#RRGGBB)   |
| `start_date`        | date   | Fecha inicio          |
| `end_date`          | date   | Fecha fin             |
| `created_by`        | int    | FK → users            |
| `updated_by`        | int    | FK → users            |

**Relations:** `company`, `status`, `creator`, `updater`, `histories`, `projectUsers`, `tasks`, `rootTasks`

---

### Task

| Campo            | Tipo   | Descripción                  |
| ---------------- | ------ | ---------------------------- |
| `id`             | int    | PK                           |
| `project_id`     | int    | FK → projects                |
| `parent_id`      | int    | FK → tasks (nullable)        |
| `path`           | string | Materialized path jerárquico |
| `task_status_id` | int    | FK → task_statuses           |
| `type`           | string | container, task, milestone   |
| `title`          | string | Título                       |
| `description`    | string | Descripción (nullable)       |
| `start_date`     | date   | Fecha inicio                 |
| `end_date`       | date   | Fecha fin                    |
| `progress`       | int    | 0-100                        |
| `created_by`     | int    | FK → users                   |
| `updated_by`     | int    | FK → users                   |

**Relations:** `project`, `parent`, `children`, `status`, `assignments`, `creator`, `updater`, `dependencies`, `dependents`

**Tipo de task:**

- `container`: Agrupa tareas. Progress y status se calculan automáticamente.
- `task`: Tarea ejecutable con fechas, progreso y dependencias.
- `milestone`: Punto de control. Solo tiene `start_date` (= `end_date`). Sin dependencias.

---

### TaskAssignment

| Campo             | Tipo | Descripción                |
| ----------------- | ---- | -------------------------- |
| `id`              | int  | PK                         |
| `task_id`         | int  | FK → tasks                 |
| `project_user_id` | int  | FK → project_users         |
| `task_role_id`    | int  | FK → task_roles (nullable) |

---

### ProjectUser

| Campo             | Tipo | Descripción        |
| ----------------- | ---- | ------------------ |
| `id`              | int  | PK                 |
| `project_id`      | int  | FK → projects      |
| `user_id`         | int  | FK → users         |
| `project_role_id` | int  | FK → project_roles |
| `created_by`      | int  | FK → users         |

---

## 8. Enums y Catálogos

### RoleEnum

| Value | Label       | Slug        |
| ----- | ----------- | ----------- |
| 1     | Super Admin | super_admin |
| 2     | Admin       | admin       |
| 3     | Staff       | staff       |

---

### ProjectStatusEnum

| Value | Label      | Slug      |
| ----- | ---------- | --------- |
| 1     | Activo     | active    |
| 2     | Completado | completed |
| 3     | En Pausa   | on_hold   |
| 4     | Cancelado  | cancelled |
| 5     | Eliminado  | deleted   |

---

### TaskStatusEnum

| Value | Label       | Slug        |
| ----- | ----------- | ----------- |
| 1     | Pendiente   | pending     |
| 2     | En Progreso | in_progress |
| 3     | Completada  | completed   |
| 4     | En Pausa    | on_hold     |
| 5     | Cancelada   | cancelled   |
| 6     | Eliminada   | deleted     |

---

### TaskTypeEnum

| Value     | Label      |
| --------- | ---------- |
| container | Contenedor |
| task      | Tarea      |
| milestone | Hito       |

---

### TaskDependencyTypeEnum

| Value           | Descripción                                                         |
| --------------- | ------------------------------------------------------------------- |
| finish_to_start | La tarea dependiente no puede empezar hasta que termine la anterior |

---

## 9. Reglas de Negocio

### Jerarquía de Tareas

- Las tareas se organizan en árbol usando `parent_id` y `path` (materialized path)
- Solo los `container` pueden ser padres
- Un container no puede tener `task_status_id`, `progress`, `start_date` ni `end_date` (se calculan de hijos)
- `display_path` convierte `000001/000003` a `1.3` para mostrar al usuario

### Progreso de Containers

- El `progress` del container = promedio de progress de hijos activos
- Se excluyen hijos CANCELLED y DELETED
- Se excluyen milestones del cálculo
- Si no hay hijos activos: progress = 0, status = PENDING

### Status de Containers

- **CANCELLED/DELETED** si todos los hijos activos lo están
- **COMPLETED** si todos los hijos activos están COMPLETED
- **IN_PROGRESS** si algún hijo está IN_PROGRESS, ON_HOLD, o tiene mezcla
- **PENDING** si todos los hijos están PENDING

### Fechas de Proyecto

- Se calculan automáticamente: `start_date` = min(start_date de root tasks), `end_date` = max(end_date de root tasks)
- Se recalculan cuando cambian las fechas de tareas raíz
- Se ignoran tareas CANCELLED y DELETED

### Milestones

- `start_date` es requerido
- `end_date` se sincroniza automáticamente con `start_date`
- No aceptan `description`
- No aceptan dependencias
- Transiciones de estado más restrictivas

### Eliminación (Soft-Delete)

- **Project**: solo se puede eliminar desde ACTIVE, ON_HOLD o CANCELLED. No desde COMPLETED.
- **Task**: solo se puede eliminar desde PENDING, IN_PROGRESS, ON_HOLD o CANCELLED. No desde COMPLETED.
- **Cascada de tareas**: al eliminar un container, todos los descendientes (excepto COMPLETED) pasan a DELETED
- **Restaurar**: la tarea y todos sus descendientes DELETED pasan a PENDING

---

## 10. Transiciones de Estado

### Project Status Transitions

```
ACTIVE ──────→ ON_HOLD
    │              │
    ├──────→ COMPLETED
    │              │
    └──────→ CANCELLED
                 │
ON_HOLD ──→ ACTIVE
ON_HOLD ──→ CANCELLED
COMPLETED ──→ ACTIVE
CANCELLED ──→ ACTIVE
```

**Transiciones no permitidas:**

- ON_HOLD → COMPLETED (debe pasar por ACTIVE)
- COMPLETED → CANCELLED (debe pasar por ACTIVE)
- CUALQUIER → DELETED (solo DELETE endpoint)
- DELETED → CUALQUIER (solo RESTORE endpoint)

---

### Task Status Transitions (tasks y containers)

```
PENDING ──────→ IN_PROGRESS
    │              │
    ├──────→ ON_HOLD
    │              │
    └──────→ CANCELLED
                 │
IN_PROGRESS ──→ PENDING
IN_PROGRESS ──→ COMPLETED
IN_PROGRESS ──→ ON_HOLD
IN_PROGRESS ──→ CANCELLED
COMPLETED ──→ IN_PROGRESS
ON_HOLD ──→ PENDING
ON_HOLD ──→ IN_PROGRESS
ON_HOLD ──→ CANCELLED
CANCELLED ──→ PENDING
```

**Transiciones no permitidas:**

- PENDING → COMPLETED (debe pasar por IN_PROGRESS)
- COMPLETED → CANCELLED (debe pasar por IN_PROGRESS)
- CUALQUIER → DELETED (solo DELETE endpoint)

---

### Milestone Status Transitions (más restrictivo)

```
PENDING ──→ COMPLETED
PENDING ──→ CANCELLED
COMPLETED ──→ PENDING
CANCELLED ──→ PENDING
```

**Transiciones NO permitidas para milestones:**

- PENDING → IN_PROGRESS
- PENDING → ON_HOLD
- CUALQUIER → ON_HOLD
- COMPLETED → CANCELLED
- CUALQUIER → DELETED (excepto DELETE endpoint)

---

## 11. Eventos

| Evento           | Disparador                                                           | Descripción                                |
| ---------------- | -------------------------------------------------------------------- | ------------------------------------------ |
| `TaskCreated`    | `TaskService::createTask`                                            | Se emite al crear una tarea                |
| `TaskUpdated`    | `TaskService::updateTask`, `deleteTask`, `restoreTask`, `bulkUpdate` | Se emite al actualizar/eliminar/restaurar  |
| `TaskCompleted`  | `TaskService::updateTask`, `bulkUpdate`                              | Se emite cuando una tarea pasa a COMPLETED |
| `ProjectCreated` | `ProjectService::createProject`                                      | Se emite al crear un proyecto              |
| `ProjectUpdated` | `ProjectService::updateProject`, `delete`, `restore`                 | Se emite al actualizar/eliminar/restaurar  |

---

## 12. Estructura de Respuesta

Todas las respuestas siguen el formato:

```json
{
  "success": true|false,
  "message": "Mensaje descriptivo",
  "data": { ... } | null
}
```

### Errores de validación (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "email": ["The email has already been taken."]
  }
}
```

### Errores de autorización (403)

```json
{
  "message": "This action is unauthorized."
}
```

### No encontrado (404)

```json
{
  "message": "No query results for model [App\\Models\\Project]."
}
```

### Rate limited (429)

```json
{
  "message": "Too Many Attempts."
}
```
