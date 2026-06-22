# Documentación de la API — Gantt App Backend

> Versión: 1.0 · Base URL: `/api` · Autenticación: Sanctum (Bearer Token) · Formato: JSON

---

## 1. Convenciones Generales

### 1.1 Base URL

Todas las rutas están bajo el prefijo `/api`.

### 1.2 Autenticación

- **Esquema**: Bearer Token vía Laravel Sanctum.
- **Header requerido** (excepto en `login`): `Authorization: Bearer {token}`
- **Tokens**: se crean en `/api/auth/login` con `device_name` opcional. No tienen expiración global configurada.
- **Abilities**: todos los tokens se emiten con `['*']`. La autorización fina se hace por Policies (no por `tokenCan`).

### 1.3 Rate Limiting

| Throttle | Límite                      | Aplicación                                 |
| -------- | --------------------------- | ------------------------------------------ |
| `login`  | 5/min por `email\|ip`       | `POST /api/auth/login`                     |
| `api`    | 60/min por `user.id` o `ip` | Todas las rutas bajo `/api/*` autenticadas |

### 1.4 Formato de Respuesta

Todas las respuestas usan el trait `ApiResponse` (`app/Http/Resources/ApiResponse.php`).

**Éxito (200)**

```json
{
  "data": { ... },
  "message": "Mensaje descriptivo"
}
```

**Creación (201)**

```json
{
  "data": { ... },
  "message": "Created successfully"
}
```

**Eliminación (200)**

```json
{
  "message": "Deleted successfully"
}
```

**Listado paginado (200)**

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

**Error 404 (recurso no encontrado)**

```json
{ "message": "Resource not found." }
```

**Error 422 (validación / reglas de negocio)**

```json
{ "message": "The task is already in status Completada." }
```

**Error 401 (no autenticado)**

```json
{ "message": "Unauthenticated." }
```

**Error 403 (no autorizado)**

```json
{ "message": "This action is unauthorized." }
```

### 1.5 Parámetros de Query Comunes

| Parámetro       | Tipo | Default | Descripción                                 |
| --------------- | ---- | ------- | ------------------------------------------- |
| `per_page`      | int  | 10      | Items por página (máx 100)                  |
| `status_id`     | int  | null    | Filtrar por estado (solo `projects.index`)  |
| `include_stats` | bool | false   | Incluir estadísticas (solo `projects.show`) |

### 1.6 Fechas

- Todas las fechas se manejan como **CarbonImmutable**.
- En requests: formato `Y-m-d` (ej. `2026-06-21`).
- En respuestas: `start_date`/`end_date` → `Y-m-d`; `created_at`/`updated_at` → ISO 8601.

---

## 2. Roles y Permisos

### 2.1 Roles Globales (`RoleEnum`, tabla `roles`)

| ID  | Slug          | Nombre      | Descripción                                                  |
| --- | ------------- | ----------- | ------------------------------------------------------------ |
| 1   | `super_admin` | Super Admin | Acceso total, bypass de todas las policies                   |
| 2   | `admin`       | Admin       | Gestión de proyectos, bypass en Task/TaskAssignment policies |
| 3   | `staff`       | Staff       | Usuario operativo, sin privilegios administrativos           |

### 2.2 Roles de Proyecto (`ProjectRoleEnum`, tabla `project_roles`)

| ID  | Slug              | Nombre          |
| --- | ----------------- | --------------- |
| 1   | `project_manager` | Project Manager |
| 2   | `team_member`     | Team Member     |
| 3   | `viewer`          | Viewer          |

### 2.3 Roles de Tarea (`task_roles`)

| Slug          | Nombre      |
| ------------- | ----------- |
| `team_leader` | Team Leader |
| `developer`   | Developer   |
| `analyst`     | Analyst     |
| `designer`    | Designer    |
| `tester`      | Tester      |

### 2.4 Matriz de Permisos por Endpoint

#### Projects

| Endpoint  | Super Admin | Admin | Staff | PM  | Miembro | Viewer |
| --------- | ----------- | ----- | ----- | --- | ------- | ------ |
| `index`   | ✓           | ✓     | ✓     | ✓   | ✓       | ✓      |
| `store`   | ✓           | ✓     | ✗     | ✗   | ✗       | ✗      |
| `show`    | ✓           | ✓     | ✓\*   | ✓\* | ✓\*     | ✓\*    |
| `update`  | ✓           | ✓     | ✗     | ✓\* | ✗       | ✗      |
| `destroy` | ✓           | ✓\*\* | ✗     | ✗   | ✗       | ✗      |
| `restore` | ✓           | ✓\*\* | ✗     | ✗   | ✗       | ✗      |

\* Solo si es miembro del proyecto (creador o en `project_users`)
\*\* Solo si es el creador del proyecto (`created_by === user.id`)

#### Tasks (requieren ser miembro del proyecto)

| Endpoint      | Super Admin | Admin | PM  | Miembro/Viewer |
| ------------- | ----------- | ----- | --- | -------------- |
| `index`       | ✓           | ✓     | ✓   | ✓              |
| `store`       | ✓           | ✓     | ✓   | ✗              |
| `show`        | ✓           | ✓     | ✓   | ✓              |
| `update`      | ✓           | ✓     | ✓   | ✗              |
| `destroy`     | ✓           | ✓     | ✓   | ✗              |
| `restore`     | ✓           | ✓     | ✓   | ✗              |
| `bulk-update` | ✓           | ✓     | ✓   | ✗              |

#### Project Users

| Endpoint        | Super Admin | Admin | PM  | Miembro/Viewer |
| --------------- | ----------- | ----- | --- | -------------- |
| `index`         | ✓           | ✓     | ✓   | ✓              |
| `index-by-role` | ✓           | ✓     | ✓   | ✓              |
| `store`         | ✓           | ✓     | ✓   | ✗              |
| `destroy`       | ✓           | ✓     | ✓   | ✗              |

#### Task Assignments

| Endpoint  | Super Admin | Admin | PM  | Miembro/Viewer |
| --------- | ----------- | ----- | --- | -------------- |
| `index`   | ✓           | ✓     | ✓   | ✓              |
| `store`   | ✓           | ✓     | ✓   | ✗              |
| `update`  | ✓           | ✓     | ✓   | ✗              |
| `destroy` | ✓           | ✓     | ✓   | ✗              |

#### Dashboard

| Endpoint | Acceso                                                              |
| -------- | ------------------------------------------------------------------- |
| `stats`  | Cualquier usuario autenticado (gate `viewDashboard` siempre `true`) |

---

## 3. Autenticación — `/api/auth`

### 3.1 POST `/api/auth/login`

Autentica credenciales y emite un token Sanctum.

**Throttle**: `login` (5/min por email+ip)

**Request Body**

```json
{
  "email": "admin@example.com", // required, email
  "password": "password", // required, string
  "device_name": "Postman" // optional, string, max 255, default "api-token"
}
```

**Respuesta 200**

```json
{
  "data": {
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@example.com",
      "role": "admin"
    }
  },
  "message": "Login exitoso"
}
```

**Errores**
| Status | Causa | Mensaje |
|---|---|---|
| 422 | Email/password inválidos | `{"message":"The provided credentials are incorrect."}` (en `email`) |
| 422 | Validación de campos | Errores estándar de Laravel |
| 429 | Rate limit excedido | `Too Many Attempts.` |

> **Casuística**: Si el usuario tiene 2FA habilitado (vía Fortify web), el login API no lo desafía (el flujo 2FA es solo web/Inertia). El token API se emite directamente.

---

### 3.2 POST `/api/auth/logout`

Revoca el token actual del usuario.

**Auth**: requerida

**Respuesta 200**

```json
{ "message": "Sesión cerrada correctamente" }
```

---

### 3.3 POST `/api/auth/logout-all`

Revoca **todos** los tokens del usuario autenticado (todos los dispositivos).

**Auth**: requerida

**Respuesta 200**

```json
{ "message": "Sesión cerrada en todos los dispositivos" }
```

---

### 3.4 GET `/api/auth/me`

Devuelve el usuario autenticado actual.

**Auth**: requerida

**Respuesta 200**

```json
{
  "data": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

---

## 4. Dashboard — `/api/dashboard`

### 4.1 GET `/api/dashboard/stats`

Métricas agregadas del usuario autenticado.

**Auth**: requerida · **Policy**: `viewDashboard` (siempre true)

**Respuesta 200**

```json
{
  "data": {
    "metrics": {
      "total_projects": 12,
      "active_projects": 7,
      "completed_projects": 3,
      "overall_progress": 65
    },
    "projects": [
      {
        "id": 1,
        "name": "Proyecto Alpha",
        "color": "#3B82F6",
        "status_name": "Activo",
        "status_color": "#22C55E",
        "progress": 75,
        "total_tasks": 24
      }
    ]
  }
}
```

**Lógica del scope** (`DashboardRepository.php:72-79`):

- Proyectos donde `created_by === userId` **OR** el usuario está en `project_users`.
- `overall_progress`: promedio de `tasks.progress` (round).
- `projects`: top 10 por `created_at DESC`, con promedio de progreso y conteo de tareas.

---

## 5. Projects — `/api/projects`

### 5.1 GET `/api/projects`

Lista paginada de proyectos del usuario.

**Auth**: requerida · **Policy**: `viewAny` (cualquier autenticado)

**Query Params**
| Param | Tipo | Default | Notas |
|---|---|---|---|
| `per_page` | int | 10 | Máx 100 |
| `status_id` | int | null | Filtra por `project_statuses.id` |

**Respuesta 200** (paginada)

```json
{
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "project_status_id": 1,
      "status": { "name": "Activo", "slug": "active", "color": "#22C55E" },
      "name": "Proyecto Alpha",
      "description": "Descripción...",
      "color": "#3B82F6",
      "start_date": "2026-01-15",
      "end_date": "2026-06-30",
      "created_by": 1,
      "created_at": "2026-01-15T10:00:00+00:00",
      "project_users": []
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 10, "total": 1 }
}
```

**Scope**: proyectos donde `created_by === userId` OR el usuario es miembro (`project_users`). Orden: `created_at DESC`.

---

### 5.2 POST `/api/projects`

Crea un proyecto.

**Auth**: requerida · **Policy**: `create` (solo Admin o Super Admin)

**Request Body**

```json
{
  "company_id": 1, // required, integer, exists:companies,id
  "project_status_id": 1, // optional, integer, exists:project_statuses,id (default: ACTIVE=1)
  "name": "Proyecto Beta", // required, string, max 255
  "description": "Descripción", // optional, nullable, string
  "color": "#3B82F6", // required, regex /^#[0-9A-Fa-f]{6}$/
  "start_date": "2026-02-01", // optional, nullable, date
  "end_date": "2026-07-31" // optional, nullable, date, after_or_equal:start_date
}
```

**Reglas de validación** (`ProjectRequest.php`)
| Campo | Create | Update (PATCH) |
|---|---|---|
| `company_id` | `required\|integer\|exists:companies,id` | `sometimes\|integer\|exists:companies,id` |
| `project_status_id` | `sometimes\|integer\|exists:project_statuses,id` | `sometimes\|integer\|exists:project_statuses,id` |
| `name` | `required\|string\|max:255` | `sometimes\|string\|max:255` |
| `description` | `nullable\|string` | `nullable\|string` |
| `color` | `required\|regex:/^#[0-9A-Fa-f]{6}$/` | `sometimes\|regex:/^#[0-9A-Fa-f]{6}$/` |
| `start_date` | `nullable\|date` | `nullable\|date` |
| `end_date` | `nullable\|date\|after_or_equal:start_date` | `nullable\|date\|after_or_equal:start_date` |

**Comportamiento**

- Se ejecuta en transacción DB.
- `created_by` y `updated_by` se setean automáticamente (trait `HasAuditFields`).
- Se dispara evento `ProjectCreated` → listener `LogProjectActivity`.
- Si no se envía `project_status_id`, el modelo default es `ACTIVE` (1).

**Respuesta 201**

```json
{
  "data": {
    /* ProjectResource */
  },
  "message": "Created successfully"
}
```

---

### 5.3 GET `/api/projects/{project}`

Detalle de un proyecto.

**Auth**: requerida · **Policy**: `view` (solo miembros del proyecto)

**Path Params**: `project` = ID del proyecto

**Query Params**
| Param | Tipo | Efecto |
|---|---|---|
| `include_stats` | any | Incluye bloque `stats` con `total_tasks`, `completed_tasks`, `overall_progress` |

**Respuesta 200**

```json
{
  "data": {
    "id": 1,
    "company_id": 1,
    "project_status_id": 1,
    "status": { "name": "Activo", "slug": "active", "color": "#22C55E" },
    "name": "Proyecto Alpha",
    "description": "...",
    "color": "#3B82F6",
    "start_date": "2026-01-15",
    "end_date": "2026-06-30",
    "created_by": 1,
    "created_at": "2026-01-15T10:00:00+00:00",
    "project_users": [
      {
        "id": 1,
        "project_id": 1,
        "user": { "id": 2, "name": "Admin", "email": "admin@example.com" },
        "project_role": {
          "id": 1,
          "name": "Project Manager",
          "slug": "project_manager"
        },
        "creator": { "id": 1, "name": "Super Admin" },
        "created_at": "2026-01-15T10:00:00+00:00"
      }
    ]
  },
  "stats": {
    "total_tasks": 24,
    "completed_tasks": 8,
    "overall_progress": 65
  }
}
```

**Eager loads**: `projectUsers.user`, `projectUsers.projectRole`.

**Stats** (`ProjectRepository::getStats`): cuenta solo tareas **hoja** (sin hijos) — `whereDoesntHave('children')`.

---

### 5.4 PATCH `/api/projects/{project}`

Actualiza un proyecto (update parcial).

**Auth**: requerida · **Policy**: `update` (Admin o Project Manager del proyecto)

**Request Body** (todos opcionales en PATCH, ver tabla 5.2)

```json
{
  "name": "Proyecto Alpha Renombrado",
  "project_status_id": 2,
  "start_date": "2026-02-01"
}
```

**Reglas de negocio — Máquina de estados del Project**

Si se envía `project_status_id`, se valida la transición:

| Estado actual   | Estados permitidos                              |
| --------------- | ----------------------------------------------- |
| `ACTIVE` (1)    | `ON_HOLD` (3), `COMPLETED` (2), `CANCELLED` (4) |
| `ON_HOLD` (3)   | `ACTIVE` (1), `CANCELLED` (4)                   |
| `COMPLETED` (2) | `ACTIVE` (1)                                    |
| `CANCELLED` (4) | `ACTIVE` (1)                                    |
| `DELETED` (5)   | _(ninguno — no se puede actualizar)_            |

**Excepciones de negocio**
| Excepción | HTTP | Causa |
|---|---|---|
| `ProjectDeletedCannotBeUpdatedException` | 422 | El proyecto está DELETED |
| `ProjectAlreadyInStatusException` | 422 | El estado enviado == estado actual |
| `ProjectInvalidStatusTransitionException` | 422 | Transición no permitida (mensaje: `Cannot transition project from Activo to Completado.`) |

**Comportamiento**

- Transacción DB.
- Se dispara `ProjectUpdated` → `LogProjectActivity`.
- `ProjectObserver` registra entrada en `project_histories` si cambió `project_status_id`.

**Respuesta 200**: `ProjectResource` actualizado.

---

### 5.5 DELETE `/api/projects/{project}`

Eliminación lógica (soft delete vía status).

**Auth**: requerida · **Policy**: `delete` (solo el creador del proyecto)

**Reglas de negocio**

- Solo se puede eliminar si el estado actual está en `[ACTIVE, ON_HOLD, CANCELLED]`.
- No se puede eliminar si está `COMPLETED` o `DELETED`.

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `ProjectAlreadyInStatusException` | 422 | Ya está DELETED |
| `ProjectInvalidStatusTransitionException` | 422 | Estado actual es COMPLETED |

**Comportamiento**

- Cambia `project_status_id` a `DELETED` (5).
- Dispara `ProjectUpdated` → `LogProjectActivity`.
- Registra en `project_histories`.

**Respuesta 200**

```json
{ "message": "Project deleted successfully" }
```

---

### 5.6 POST `/api/projects/{project}/restore`

Restaura un proyecto eliminado.

**Auth**: requerida · **Policy**: `restore` (solo el creador)

**Reglas de negocio**

- Solo se puede restaurar si el estado actual es `DELETED`.
- El estado restaurado es `ACTIVE` (1).

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `ProjectNotDeletedException` | 422 | El proyecto no está DELETED |

**Respuesta 200**

```json
{ "message": "Project restored successfully" }
```

---

### 5.7 Auto-completado de Proyecto (regla automática)

Cuando una tarea pasa a `COMPLETED`, el listener `RefreshProjectStatus` evalúa:

- Si `total_tasks > 0` AND `total_tasks === completed_tasks` → el proyecto pasa a `COMPLETED`.
- En caso contrario → pasa a `ACTIVE` (si no estaba en un estado protegido: `ON_HOLD`, `CANCELLED`, `DELETED`).

> **Nota**: Esta regla **no** dispara `ProjectUpdated` (usa `$project->save()` directo en `ProjectService::refreshStatus`).

---

## 6. Project Users — `/api/projects/{project}/users`

### 6.1 GET `/api/projects/{project}/users`

Lista los usuarios asignados al proyecto.

**Auth**: requerida · **Policy**: `viewAny` (miembros del proyecto)

**Respuesta 200** (colección, sin paginación)

```json
{
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "user": { "id": 2, "name": "Admin", "email": "admin@example.com" },
      "project_role": {
        "id": 1,
        "name": "Project Manager",
        "slug": "project_manager"
      },
      "creator": { "id": 1, "name": "Super Admin" },
      "created_at": "2026-01-15T10:00:00+00:00"
    }
  ]
}
```

---

### 6.2 GET `/api/projects/{project}/users/role/{projectRole}`

Lista usuarios filtrando por rol de proyecto.

**Auth**: requerida · **Policy**: `viewAny`

**Path Params**
| Param | Descripción |
|---|---|
| `project` | ID del proyecto |
| `projectRole` | ID del `project_roles` (1=PM, 2=TeamMember, 3=Viewer) |

**Respuesta 200**: misma estructura que 6.1.

---

### 6.3 POST `/api/projects/{project}/users`

Asigna un usuario al proyecto con un rol.

**Auth**: requerida · **Policy**: `create` (Admin o PM)

**Request Body**

```json
{
  "user_id": 3, // required, exists:users,id
  "project_role_id": 2 // required, exists:project_roles,id
}
```

**Reglas de negocio**

- No se puede asignar el mismo usuario dos veces (unique `project_id + user_id`).

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `ProjectUserAlreadyAssignedException` | 422 | El usuario ya está asignado |

**Comportamiento**

- Transacción DB.
- `created_by` se setea automáticamente (trait `HasCreatedBy`).
- Dispara `ProjectUserAssigned` → `LogProjectUserActivity`.

**Respuesta 201**: `ProjectUserResource`.

---

### 6.4 DELETE `/api/projects/{project}/users/{user}`

Remueve un usuario del proyecto.

**Auth**: requerida · **Policy**: `delete` (Admin o PM)

**Path Params**: `project` ID, `user` ID

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `ProjectUserNotFoundException` | 404 | El usuario no está asignado al proyecto |

**Comportamiento**

- Transacción DB.
- Dispara `ProjectUserRemoved` → `LogProjectUserActivity`.

**Respuesta 200**

```json
{ "message": "User removed from project" }
```

---

## 7. Tasks — `/api/tasks` y `/api/projects/{project}/tasks`

### 7.1 Tipos de Task (`TaskTypeEnum`)

| Valor       | Nombre     | Descripción                                                                            |
| ----------- | ---------- | -------------------------------------------------------------------------------------- |
| `container` | Contenedor | Agrupa tareas hijas. **No tiene** status/progress/fechas manuales (se calculan)        |
| `task`      | Tarea      | Tarea normal con status, progress, fechas, descripción, asignaciones                   |
| `milestone` | Hito       | Punto en el tiempo. `start_date === end_date`. Sin descripción. Transiciones limitadas |

### 7.2 Estados de Task (`TaskStatusEnum`)

| ID  | Slug          | Nombre      |
| --- | ------------- | ----------- |
| 1   | `pending`     | Pendiente   |
| 2   | `in_progress` | En Progreso |
| 3   | `completed`   | Completada  |
| 4   | `on_hold`     | En Pausa    |
| 5   | `cancelled`   | Cancelada   |
| 6   | `deleted`     | Eliminada   |

### 7.3 Tipos de Dependencia (`TaskDependencyTypeEnum`)

| Valor              | Nombre                    |
| ------------------ | ------------------------- |
| `finish_to_start`  | Finish to Start (default) |
| `start_to_start`   | Start to Start            |
| `finish_to_finish` | Finish to Finish          |
| `start_to_finish`  | Start to Finish           |

---

### 7.4 GET `/api/projects/{project}/tasks`

Lista paginada de tareas de un proyecto.

**Auth**: requerida · **Policy**: `viewAny` (miembros del proyecto)

**Query Params**: `per_page` (default 10, máx 100)

**Orden**: `path` ASC (orden jerárquico materializado)

**Eager loads**: `status`, `assignments.projectUser.user`, `assignments.taskRole`, `dependencies`, `creator`, `project`

**Respuesta 200** (paginada): colección de `TaskResource`.

---

### 7.5 POST `/api/projects/{project}/tasks`

Crea una tarea dentro de un proyecto.

**Auth**: requerida · **Policy**: `create` (Project Manager)

**Request Body**

```json
{
  "type": "task", // required, enum: container|task|milestone
  "parent_id": 5, // optional, nullable, exists:tasks,id
  //   - debe ser del mismo proyecto
  //   - debe ser de tipo CONTAINER
  //   - no puede ser self
  "task_status_id": 1, // optional, enum TaskStatusEnum (default: PENDING=1)
  //   - PROHIBIDO si type=container
  "title": "Implementar login", // required, string, max 255
  "description": "...", // optional, nullable, string
  //   - PROHIBIDO si type=milestone o type=container
  "start_date": "2026-02-01", // optional para task | required para milestone | PROHIBIDO para container
  "end_date": "2026-02-15", // optional, after_or_equal:start_date
  //   - PROHIBIDO para milestone (se sincroniza con start_date)
  //   - PROHIBIDO para container
  "progress": 0, // optional, int 0-100
  //   - PROHIBIDO para container
  "dependency_ids": [10, 11], // optional, array of task IDs
  //   - deben ser del mismo proyecto
  //   - no puede incluirse a sí misma
  "dependency_type": "finish_to_start" // optional, enum TaskDependencyTypeEnum
}
```

**Reglas de validación dinámica según `type`** (`TaskRequest.php:38-115`)

| Campo            | `container`    | `task`                  | `milestone`                   |
| ---------------- | -------------- | ----------------------- | ----------------------------- |
| `task_status_id` | **prohibited** | nullable enum           | nullable enum                 |
| `description`    | **prohibited** | nullable string         | **prohibited**                |
| `start_date`     | **prohibited** | nullable date           | **required** date             |
| `end_date`       | **prohibited** | nullable date (≥ start) | **prohibited** (auto = start) |
| `progress`       | **prohibited** | nullable int 0-100      | nullable int 0-100            |

**Validaciones custom de `parent_id`** (closure)

- No puede ser self (`task.id === parent_id`).
- Debe pertenecer al mismo proyecto.
- Debe ser de tipo `CONTAINER`.

**Validaciones custom de `dependency_ids.*`** (closure)

- Deben pertenecer al mismo proyecto.
- No puede incluirse a sí misma.

**Reglas de negocio al crear**

1. Si `type=milestone` y se envía `start_date` sin `end_date` → `end_date = start_date`.
2. Se persiste con `path='0000'` provisional (`TaskObserver::creating`).
3. `TaskObserver::created` calcula el path real vía `TaskPathService::applyPathOnCreate`:
   - Segmento = `count(siblings) + 1`, padded a 4 dígitos.
   - `path` = `parentPath/segment` o solo `segment` si es root.
   - Ej: primer hijo del root → `0001`; segundo → `0002`; hijo del `0001` → `0001/0001`.
4. Si tiene `parent_id` → `TaskProgressService::recalculateAncestors` (recalcula containers ancestros).
5. Si es root → `ProjectService::refreshDates` (recalcula fechas del proyecto).
6. Si hay `dependency_ids` → se valida que no creen ciclo (`wouldCreateCycle` con CTE recursiva SQL) y se sincronizan con `syncDependencies`.
7. Se dispara `TaskCreated` → `LogTaskActivity`.

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `CycleDetectionException` | 422 | La dependencia crearía un ciclo |

**Respuesta 201**: `TaskResource` con eager loads.

---

### 7.6 GET `/api/tasks/{task}`

Detalle de una tarea.

**Auth**: requerida · **Policy**: `view` (miembro del proyecto de la tarea)

**Eager loads**: `status`, `dependencies`, `dependents`, `assignments.projectUser.user`, `assignments.taskRole`, `parent`, `children.status`, `project`

**Respuesta 200**

```json
{
  "data": {
    "id": 5,
    "project_id": 1,
    "parent_id": null,
    "path": "0001",
    "display_path": "1",
    "type": "task",
    "task_status_id": 2,
    "status": {
      "id": 2,
      "name": "En Progreso",
      "slug": "in_progress",
      "color": "#3B82F6"
    },
    "title": "Implementar login",
    "description": "...",
    "start_date": "2026-02-01",
    "end_date": "2026-02-15",
    "progress": 50,
    "dependencies": [{ "id": 10, "type": "finish_to_start" }],
    "assignments": [
      {
        "id": 1,
        "user": { "id": 3, "name": "Staff" },
        "task_role": { "id": 2, "name": "Developer", "slug": "developer" }
      }
    ],
    "created_at": "2026-01-15T10:00:00+00:00",
    "updated_at": "2026-02-01T08:00:00+00:00",
    "creator": { "id": 1, "name": "Super Admin" },
    "updater": { "id": 1, "name": "Super Admin" }
  }
}
```

> **`display_path`**: conversión legible de `path`. Ej: `0001/0002/0003` → `1.2.3`.

> **Campos condicionales en `TaskResource`**:
>
> - `description`, `progress`, `assignments`: solo si `type === TASK`.
> - `progress`, `children`: solo si `type === CONTAINER`.

---

### 7.7 PATCH `/api/tasks/{task}`

Actualiza una tarea (update parcial).

**Auth**: requerida · **Policy**: `update` (Project Manager)

**Request Body** (todos opcionales según reglas de `type`)

**Reglas de negocio — Actualización**

1. **Si la tarea está DELETED** → `TaskDeletedCannotBeUpdatedException` (422).
2. **Si `type=CONTAINER`** y se envía `task_status_id` o `progress` → se **ignoran** (unset) porque los containers se calculan automáticamente.
3. **Si `type=MILESTONE`** y se envía `start_date` → `end_date` se sincroniza con `start_date`.
4. **Si cambia `parent_id`** (`wasChanged('parent_id')`):
   - `TaskPathService::handleParentChange` recalcula el path del task movido.
   - Renumeración de siblings del padre antiguo.
   - Update de paths de descendientes (`path LIKE oldPath/%` → reemplazo).
   - Recálculo de progreso/status del padre antiguo y nuevo.
   - Refresh de fechas del proyecto si corresponde.
5. **Si cambió `task_status_id`** → se valida la transición (máquina de estados, ver 7.10).
6. **Si `dependency_ids` fue enviado** (distinto de `UNDEFINED_ARRAY`):
   - Se calculan nuevos IDs = `array_diff(dependencyIds, existingIds)`.
   - Se valida que los nuevos no creen ciclo.
   - `syncDependencies` reemplaza todas las dependencias (sync).
7. **Si pasa a `COMPLETED`** y el estado anterior no era `COMPLETED`:
   - Se dispara `TaskCompleted` → `LogTaskActivity` + `RefreshProjectStatus`.
   - `RefreshProjectStatus` puede auto-completar el proyecto si todas las tareas hoja están completadas.
8. Siempre se dispara `TaskUpdated` → `LogTaskActivity`.
9. `TaskObserver::updated` recalcula ancestors si cambiaron `task_status_id`, `progress`, `start_date`, `end_date`.

**Respuesta 200**: `TaskResource` actualizado.

---

### 7.8 DELETE `/api/tasks/{task}`

Eliminación lógica con **cascada a descendientes**.

**Auth**: requerida · **Policy**: `delete` (Project Manager)

**Reglas de negocio**

1. Solo se puede eliminar si el estado está en `[PENDING, IN_PROGRESS, ON_HOLD, CANCELLED]`.
2. No se puede eliminar si está `COMPLETED` o `DELETED`.

**Cascada de descendientes** (`TaskService.php:175-191`)

- Busca todos los descendientes vía `path LIKE '{task.path}/%'`.
- Los descendientes que **no** estén en `[DELETED, COMPLETED]` → pasan a `DELETED` (con `saveQuietly`, sin disparar eventos).
- La task principal pasa a `DELETED` con `save()` (dispara observer).
- Se dispara `TaskUpdated`.

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `TaskAlreadyInStatusException` | 422 | Ya está DELETED |
| `TaskInvalidStatusTransitionException` | 422 | Estado actual es COMPLETED |

**Respuesta 200**

```json
{ "message": "Task deleted successfully" }
```

---

### 7.9 POST `/api/tasks/{task}/restore`

Restaura una tarea eliminada.

**Auth**: requerida · **Policy**: `restore` (Project Manager)

**Reglas de negocio**

1. Solo se puede restaurar si el estado actual es `DELETED`.
2. Estado restaurado: `PENDING` (1).
3. **Cascada de restauración**: descendientes (`path LIKE '{task.path}/%'`) que estén en `DELETED` → pasan a `PENDING` (con `saveQuietly`).
4. Se dispara `TaskUpdated`.

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `TaskNotDeletedException` | 422 | La tarea no está DELETED |

**Respuesta 200**

```json
{ "message": "Task restored successfully" }
```

---

### 7.10 Máquina de Estados de Task

#### Transiciones para `TASK` y `CONTAINER` (aunque container no se cambia manualmente)

| Estado actual     | Estados permitidos                                             |
| ----------------- | -------------------------------------------------------------- |
| `PENDING` (1)     | `IN_PROGRESS` (2), `ON_HOLD` (4), `CANCELLED` (5)              |
| `IN_PROGRESS` (2) | `PENDING` (1), `COMPLETED` (3), `ON_HOLD` (4), `CANCELLED` (5) |
| `COMPLETED` (3)   | `IN_PROGRESS` (2)                                              |
| `ON_HOLD` (4)     | `PENDING` (1), `IN_PROGRESS` (2), `CANCELLED` (5)              |
| `CANCELLED` (5)   | `PENDING` (1)                                                  |
| `DELETED` (6)     | _(ninguno — no se puede actualizar)_                           |

#### Transiciones para `MILESTONE` (subconjunto limitado)

| Estado actual   | Estados permitidos               |
| --------------- | -------------------------------- |
| `PENDING` (1)   | `COMPLETED` (3), `CANCELLED` (5) |
| `COMPLETED` (3) | `PENDING` (1)                    |
| `CANCELLED` (5) | `PENDING` (1)                    |

**Excepciones de transición**
| Excepción | HTTP | Causa |
|---|---|---|
| `TaskAlreadyInStatusException` | 422 | Estado enviado == estado actual |
| `TaskInvalidStatusTransitionException` | 422 | Transición no permitida |

---

### 7.11 PATCH `/api/tasks/bulk-update`

Actualización masiva de tareas.

**Auth**: requerida · **Policy**: `update` sobre el proyecto de las tareas (PM)

**Request Body**

```json
{
  "task_ids": [5, 6, 7], // required, array, min 1
  "task_ids.*": "integer|exists:tasks,id",
  "data": {
    // required, array, min 1
    "task_status_id": 3, // optional, enum TaskStatusEnum
    "title": "Nuevo título", // optional, string, max 255
    "description": "...", // optional, nullable, string
    "start_date": "2026-02-01", // optional, nullable, date
    "end_date": "2026-02-15", // optional, nullable, date, after_or_equal:data.start_date
    "progress": 80 // optional, nullable, int 0-100
  }
}
```

**Reglas de negocio**

1. `task_ids` no puede ser vacío → `BulkOperationException::noTaskIdsProvided` (422).
2. Todas las tareas deben existir → `BulkOperationException::tasksNotFound` (422) si ninguna existe.
3. **Todas las tareas deben pertenecer al mismo proyecto** → `BulkOperationException::tasksMustBelongToSameProject` (422).
4. Se autoriza al usuario contra el proyecto de las tareas (`authorize('update', [Task::class, $project])`).
5. Se ejecuta en transacción DB.
6. Se itera sobre cada tarea:
   - Si la tarea está `DELETED` → se **omite** (skip).
   - Se llama a `TaskRepository::update` (que dispara `TaskObserver::updated` para recálculos).
   - Se dispara `TaskUpdated` por cada una.
   - Si pasó a `COMPLETED` y el estado anterior no lo era → se dispara `TaskCompleted` (que puede auto-completar el proyecto).

**Respuesta 200**

```json
{
  "data": {
    "tasks": [
      /* TaskResource[] */
    ]
  },
  "message": "Tasks updated successfully"
}
```

> **Nota**: Solo se devuelven las tareas que **no** estaban DELETED y que sí se actualizaron.

---

## 8. Task Assignments — `/api/tasks/{task}/assignments`

### 8.1 GET `/api/tasks/{task}/assignments`

Lista las asignaciones de una tarea.

**Auth**: requerida · **Policy**: `viewAny` (miembros del proyecto)

**Eager loads**: `projectUser.user`, `taskRole`

**Respuesta 200** (colección, sin paginación)

```json
{
  "data": [
    {
      "id": 1,
      "task_id": 5,
      "project_user": {
        "id": 3,
        "user": { "id": 2, "name": "Admin", "email": "admin@example.com" }
      },
      "task_role": { "id": 2, "name": "Developer", "slug": "developer" },
      "created_at": "2026-02-01T10:00:00+00:00",
      "updated_at": "2026-02-01T10:00:00+00:00"
    }
  ]
}
```

> **Nota**: Solo las tareas de `type=TASK` pueden tener asignaciones (los containers y milestones no las muestran en su Resource).

---

### 8.2 POST `/api/tasks/{task}/assignments`

Asigna un `project_user` a una tarea con un `task_role`.

**Auth**: requerida · **Policy**: `create` (Project Manager)

**Request Body**

```json
{
  "project_user_id": 3, // required, exists:project_users,id
  "task_role_id": 2 // optional, nullable, exists:task_roles,id
}
```

**Reglas de negocio**

- No se puede asignar el mismo `project_user` dos veces a la misma task (unique `task_id + project_user_id`).

**Excepciones**
| Excepción | HTTP | Causa |
|---|---|---|
| `TaskAssignmentAlreadyExistsException` | 422 | Ya existe la asignación |

**Respuesta 201**: `TaskAssignmentResource`.

---

### 8.3 PATCH `/api/tasks/{task}/assignments/{assignment}`

Actualiza el rol de una asignación.

**Auth**: requerida · **Policy**: `update` (Project Manager)

**Request Body**

```json
{
  "project_user_id": 3, // optional (sometimes), exists:project_users,id
  "task_role_id": 3 // optional, nullable, exists:task_roles,id
}
```

**Respuesta 200**: `TaskAssignmentResource` actualizada.

---

### 8.4 DELETE `/api/tasks/{task}/assignments/{assignment}`

Elimina una asignación.

**Auth**: requerida · **Policy**: `delete` (Project Manager)

**Respuesta 200**

```json
{ "message": "Assignment removed successfully" }
```

---

## 9. Reglas de Negocio Transversales

### 9.1 Path Materializado de Tasks

Cada task tiene un campo `path` (varchar 255, indexado) que representa su posición jerárquica.

**Formato**: `segment/segment/segment...` donde cada segmento tiene 4 dígitos.

**Ejemplos**:
| Task | parent_id | path | display_path |
|---|---|---|---|
| Root 1 | null | `0001` | `1` |
| Root 2 | null | `0002` | `2` |
| Hijo 1 de Root 1 | 1 | `0001/0001` | `1.1` |
| Hijo 2 de Root 1 | 1 | `0001/0002` | `1.2` |
| Nieto 1 de Hijo 2 | (1.2) | `0001/0002/0001` | `1.2.1` |

**Cálculo del segmento**: `count(siblings con mismo parent_id) + 1`, padded a 4 dígitos.

**Comportamiento al mover (`parent_id` change)**:

1. Se calcula nuevo path = `newParentPath + nextSegment`.
2. Se actualizan todos los descendientes (`path LIKE oldPath/%` → `str_replace(oldPath + '/', newPath + '/')`).
3. Se renumeran los siblings del padre antiguo (para rellenar el hueco).
4. Se recalcula progreso/status del padre antiguo y nuevo.
5. Se refrescan fechas del proyecto si corresponde.

---

### 9.2 Cálculo Automático de Containers (`TaskProgressService`)

Los containers (`type=CONTAINER`) **no** permiten edición manual de:

- `task_status_id`
- `progress`
- `start_date`
- `end_date`

Estos campos se calculan automáticamente desde los **hijos activos** (excluyendo `CANCELLED`, `DELETED` y `MILESTONE`):

| Campo            | Cálculo                         |
| ---------------- | ------------------------------- |
| `progress`       | `round(avg(children.progress))` |
| `task_status_id` | Ver tabla abajo                 |
| `start_date`     | `min(children.start_date)`      |
| `end_date`       | `max(children.end_date)`        |

**Cálculo de status del container**:
| Condición de hijos | Status resultante |
|---|---|
| Todos los hijos están `CANCELLED` o `DELETED` | `CANCELLED` |
| Todos los hijos activos están `COMPLETED` | `COMPLETED` |
| Algún hijo activo está `IN_PROGRESS` o `ON_HOLD` | `IN_PROGRESS` |
| Algún hijo activo está `COMPLETED` (pero no todos) | `IN_PROGRESS` |
| Ninguno de los anteriores | `PENDING` |

**Propagación**: El recálculo sube recursivamente por los ancestros (`recalculateAncestors`). Si el último procesado es root → `ProjectService::refreshDates`.

**Sin hijos**: Si un container se queda sin hijos activos → `progress=0`, `status=PENDING`, `start_date=null`, `end_date=null`.

> **Optimización**: Usa `saveQuietly()` para evitar redisparar el `TaskObserver::updated` y prevenir loops infinitos.

---

### 9.3 Refresh de Fechas del Proyecto (`ProjectService::refreshDates`)

Calcula `start_date` y `end_date` del proyecto en base a las **tareas root activas** (sin parent, excluyendo `CANCELLED` y `DELETED`):

- `start_date` = `min(rootTasks.start_date)`
- `end_date` = `max(rootTasks.end_date)`
- Si no hay root tasks → `null`, `null`.

Se usa `saveQuietly()` (no dispara eventos ni observer).

**Disparadores**:

- `TaskObserver::created` (si es root).
- `TaskObserver::updated` (si es root y cambiaron fechas, o si cambió `parent_id`).
- `TaskProgressService::recalculateAncestors` (si el último procesado es root).

---

### 9.4 Detección de Ciclos en Dependencias

Al agregar una dependencia (`dependency_ids`), se valida que no se cree un ciclo.

**Mecanismo**: CTE recursiva SQL (`TaskRepository::wouldCreateCycle`).

```sql
WITH RECURSIVE dependency_chain AS (
    SELECT depends_on_task_id AS task_id
    FROM task_dependencies
    WHERE task_id = :start_id  -- el nuevo dependency target

    UNION ALL

    SELECT td.depends_on_task_id
    FROM task_dependencies td
    INNER JOIN dependency_chain dc ON td.task_id = dc.task_id
)
SELECT 1 as found FROM dependency_chain WHERE task_id = :target_id LIMIT 1
-- target_id = la task que está agregando la dependencia
```

**Lógica**: Si desde el nuevo dependency target se puede llegar (siguiendo la cadena) a la task que lo agrega → hay ciclo → `CycleDetectionException` (422).

> **Compatibilidad**: Requiere MySQL 8.0+, PostgreSQL o SQLite 3.8+ (soporte CTE recursiva).

---

### 9.5 Auditoría Automática

Todas las entidades con trait `HasAuditFields` o `HasCreatedBy` setean automáticamente:

| Campo        | Trait                             | Momento                                |
| ------------ | --------------------------------- | -------------------------------------- |
| `created_by` | `HasAuditFields` / `HasCreatedBy` | `creating` event (si no viene seteado) |
| `updated_by` | `HasAuditFields`                  | `creating` y `updating` events         |
| `created_at` | `HasCreatedBy`                    | `creating` (si no usa timestamps)      |

**Models con auditoría**: User, Role, Company, Project, ProjectStatus, ProjectHistory, ProjectRole, ProjectUser, Task, TaskStatus, TaskRole.

---

### 9.6 Eventos y Listeners

| Evento                | Listener                 | Acción                                    |
| --------------------- | ------------------------ | ----------------------------------------- |
| `ProjectCreated`      | `LogProjectActivity`     | `Log::info("Project created", ...)`       |
| `ProjectUpdated`      | `LogProjectActivity`     | `Log::info("Project updated", ...)`       |
| `TaskCreated`         | `LogTaskActivity`        | `Log::info("Task created", ...)`          |
| `TaskUpdated`         | `LogTaskActivity`        | `Log::info("Task updated", ...)`          |
| `TaskCompleted`       | `LogTaskActivity`        | `Log::info("Task completed", ...)`        |
| `TaskCompleted`       | `RefreshProjectStatus`   | Auto-completa proyecto si corresponde     |
| `ProjectUserAssigned` | `LogProjectUserActivity` | `Log::info("Project user assigned", ...)` |
| `ProjectUserRemoved`  | `LogProjectUserActivity` | `Log::info("Project user removed", ...)`  |

### 9.7 Historial de Project (`project_histories`)

`ProjectObserver` registra un entry en `project_histories` cuando:

- Se crea un project (`created`).
- Se actualiza y cambió `project_status_id` (`updated`).

Campos: `project_id`, `project_status_id`, `payload` (null actualmente), `created_by`, `created_at`.

---

## 10. Catálogo de Errores

### 10.1 Errores de Autenticación

| Status | Causa                    | Mensaje                        |
| ------ | ------------------------ | ------------------------------ |
| 401    | No autenticado           | `Unauthenticated.`             |
| 403    | No autorizado por policy | `This action is unauthorized.` |
| 429    | Rate limit excedido      | `Too Many Attempts.`           |

### 10.2 Errores de Recurso No Encontrado

| Status | Causa                           | Mensaje                                     |
| ------ | ------------------------------- | ------------------------------------------- |
| 404    | Model not found / route binding | `Resource not found.`                       |
| 404    | `ProjectUserNotFoundException`  | `The user is not assigned to this project.` |

### 10.3 Errores de Reglas de Negocio (422)

| Excepción                                              | Mensaje (es)                                            |
| ------------------------------------------------------ | ------------------------------------------------------- |
| `ProjectAlreadyInStatusException`                      | `El proyecto ya se encuentra en estado {status}.`       |
| `ProjectDeletedCannotBeUpdatedException`               | `Los proyectos eliminados no pueden ser actualizados.`  |
| `ProjectNotDeletedException`                           | `Solo los proyectos eliminados pueden ser restaurados.` |
| `ProjectInvalidStatusTransitionException`              | `No se puede cambiar el proyecto de {from} a {to}.`     |
| `ProjectUserAlreadyAssignedException`                  | `El usuario ya está asignado a este proyecto.`          |
| `TaskAlreadyInStatusException`                         | `La tarea ya se encuentra en estado {status}.`          |
| `TaskDeletedCannotBeUpdatedException`                  | `Las tareas eliminadas no pueden ser actualizadas.`     |
| `TaskNotDeletedException`                              | `Solo las tareas eliminadas pueden ser restauradas.`    |
| `TaskInvalidStatusTransitionException`                 | `No se puede cambiar la tarea de {from} a {to}.`        |
| `TaskAssignmentAlreadyExistsException`                 | `El usuario ya está asignado a esta tarea.`             |
| `CycleDetectionException`                              | `Agregar esta dependencia crearía un ciclo.`            |
| `BulkOperationException::tasksNotFound`                | `No se encontraron tareas con los IDs proporcionados.`  |
| `BulkOperationException::tasksMustBelongToSameProject` | `Todas las tareas deben pertenecer al mismo proyecto.`  |
| `BulkOperationException::noTaskIdsProvided`            | `No se proporcionaron IDs de tareas.`                   |

### 10.4 Errores de Validación (422)

Errores estándar de Laravel Form Requests. Formato:

```json
{
  "message": "The name field is required. (and 1 more error)",
  "errors": {
    "name": ["The name field is required."],
    "color": ["The color format is invalid."]
  }
}
```

### 10.5 Validaciones Custom de Tasks

| Clave de traducción                            | Causa                                      |
| ---------------------------------------------- | ------------------------------------------ |
| `validation.task.self_parent`                  | Una tarea no puede ser su propio padre     |
| `validation.task.parent_different_project`     | El padre debe pertenecer al mismo proyecto |
| `validation.task.parent_must_be_container`     | El padre debe ser de tipo CONTAINER        |
| `validation.task.dependency_different_project` | La dependencia debe ser del mismo proyecto |
| `validation.task.self_dependency`              | Una tarea no puede depender de sí misma    |

---

## 11. Esquema de Base de Datos (Resumen)

### 11.1 Tablas Principales

| Tabla                    | Descripción                                                                             |
| ------------------------ | --------------------------------------------------------------------------------------- |
| `users`                  | Usuarios con `role_id` (FK a `roles`), 2FA fields                                       |
| `roles`                  | Roles globales (Super Admin, Admin, Staff)                                              |
| `companies`              | Empresas (`name`, `is_active`)                                                          |
| `projects`               | Proyectos (`company_id`, `project_status_id`, `color`, fechas, audit)                   |
| `project_statuses`       | Estados de proyecto (5 valores sembrados)                                               |
| `project_histories`      | Auditoría de cambios de status de proyecto                                              |
| `project_roles`          | Roles de proyecto (PM, Team Member, Viewer)                                             |
| `project_users`          | Pivote proyecto↔usuario con `project_role_id` (unique project_id+user_id)               |
| `tasks`                  | Tareas jerárquicas (`parent_id` self-FK, `path` materializado, `type` enum, `progress`) |
| `task_statuses`          | Estados de tarea (6 valores sembrados)                                                  |
| `task_dependencies`      | Pivote task↔task con `type` (finish_to_start, etc.) — sin ID ni timestamps              |
| `task_roles`             | Roles de tarea (Team Leader, Developer, etc.)                                           |
| `task_assignments`       | Pivote task↔project_user con `task_role_id` (unique task_id+project_user_id)            |
| `personal_access_tokens` | Tokens Sanctum (morphs tokenable)                                                       |

### 11.2 Índices Importantes

| Tabla                    | Índice                                                                                       |
| ------------------------ | -------------------------------------------------------------------------------------------- |
| `tasks`                  | `path` (index), `[project_id, task_status_id]` (composite), `[project_id, type]` (composite) |
| `project_histories`      | `created_at` (index)                                                                         |
| `personal_access_tokens` | `expires_at` (index)                                                                         |
| `project_users`          | unique `[project_id, user_id]`                                                               |
| `task_dependencies`      | unique `[task_id, depends_on_task_id]`                                                       |
| `task_assignments`       | unique `[task_id, project_user_id]`                                                          |

### 11.3 Restricciones de FK

- `projects.company_id` → `companies.id` (restrict on delete)
- `tasks.parent_id` → `tasks.id` (null on delete)
- `task_assignments.task_id` → `tasks.id` (restrict)
- `task_assignments.project_user_id` → `project_users.id` (restrict)
- `task_assignments.task_role_id` → `task_roles.id` (null on delete)
- `task_dependencies.task_id` → `tasks.id` (cascade)
- `task_dependencies.depends_on_task_id` → `tasks.id` (cascade)

---

## 12. Flujo de Datos Completo — Ejemplo

### Crear estructura jerárquica completa

**1. Crear proyecto**

```
POST /api/projects
{ "company_id": 1, "name": "Sistema Gantt", "color": "#3B82F6" }
→ 201: Project { id: 1, status: ACTIVE }
```

**2. Crear container raíz**

```
POST /api/projects/1/tasks
{ "type": "container", "title": "Fase 1: Diseño" }
→ 201: Task { id: 10, path: "0001", type: container, progress: 0, status: PENDING }
```

**3. Crear tarea hija del container**

```
POST /api/projects/1/tasks
{ "type": "task", "parent_id": 10, "title": " wireframes", "start_date": "2026-02-01", "end_date": "2026-02-15", "progress": 0 }
→ 201: Task { id: 11, path: "0001/0001", type: task, status: PENDING, progress: 0 }
```

> Después de crear la task 11, el container 10 se recalcula automáticamente:
>
> - `progress` = avg(hijos) = 0
> - `status` = PENDING (hijos no completados)
> - `start_date` = 2026-02-01, `end_date` = 2026-02-15

**4. Crear milestone hijo del container**

```
POST /api/projects/1/tasks
{ "type": "milestone", "parent_id": 10, "title": "Aprobación diseño", "start_date": "2026-02-20" }
→ 201: Task { id: 12, path: "0001/0002", type: milestone, start_date: 2026-02-20, end_date: 2026-02-20 }
```

> El milestone **no** contribuye al cálculo del container (se excluye `type=MILESTONE`).
> Pero el container 10 sigue con progress=0 (solo la task 11 contribuye).

**5. Actualizar progreso de la tarea**

```
PATCH /api/tasks/11
{ "progress": 50, "task_status_id": 2 }
→ 200: Task { id: 11, progress: 50, status: IN_PROGRESS }
```

> Cascada automática:
>
> - Container 10: `progress` = 50, `status` = IN_PROGRESS
> - Project 1: `start_date` = 2026-02-01, `end_date` = 2026-02-15

**6. Completar la tarea**

```
PATCH /api/tasks/11
{ "task_status_id": 3, "progress": 100 }
→ 200: Task { id: 11, progress: 100, status: COMPLETED }
```

> Cascada:
>
> - Container 10: `progress` = 100, `status` = COMPLETED
> - Evento `TaskCompleted` → `RefreshProjectStatus`: si todas las tareas hoja están COMPLETED → project pasa a COMPLETED.

**7. Eliminar el container (cascada)**

```
DELETE /api/tasks/10
→ 200: "Task deleted successfully"
```

> Cascada:
>
> - Task 11 (descendiente vía `0001/0001`): si no está COMPLETED/DELETED → pasa a DELETED. Como está COMPLETED → **se preserva**.
> - Task 12 (descendiente vía `0001/0002`): pasa a DELETED.
> - Task 10: pasa a DELETED.

**8. Restaurar el container**

```
POST /api/tasks/10/restore
→ 200: "Task restored successfully"
```

> Cascada:
>
> - Task 10: vuelve a PENDING.
> - Task 12 (que estaba DELETED): vuelve a PENDING.
> - Task 11 (que estaba COMPLETED): **no se ve afectada** (solo se restauran las DELETED).

---

## 13. Notas de Implementación

### 13.1 DTOs con patrón `UNDEFINED`

Los DTOs usan constantes `UNDEFINED = '__UNDEFINED__'` y `UNDEFINED_ARRAY` para distinguir:

- **Campo no enviado** en el request → `UNDEFINED` → no se incluye en `toArray()` → no se actualiza.
- **Campo enviado como `null`** → `null` → se incluye en `toArray()` → se actualiza a `null`.
- **Campo enviado con valor** → el valor → se actualiza.

Esto permite updates parciales PATCH sin sobrescribir campos no tocados.

### 13.2 Transacciones DB

Todas las operaciones de escritura en Services usan `DB::transaction()`:

- `ProjectService::createProject`, `updateProject`, `delete`, `restore`
- `TaskService::createTask`, `updateTask`, `deleteTask`, `restoreTask`, `bulkUpdate`
- `ProjectUserService::assignUser`, `removeUser`
- `TaskAssignmentService::assign`, `updateRole`, `unassign`

### 13.3 Soft Deletes

No se usa el trait `SoftDeletes` de Eloquent. La eliminación es lógica vía cambio de `status` a `DELETED`:

- Projects: `project_status_id = 5`
- Tasks: `task_status_id = 6`

### 13.4 Rate Limiting

- `login`: 5 intentos/minuto por `email|ip`.
- `api`: 60 requests/minuto por usuario autenticado o IP.
- Los rate limits se configuran en `AppServiceProvider::configureRateLimiting` y `FortifyServiceProvider::configureRateLimiting`.

### 13.5 Idioma

- Default: `en` (config/app.php).
- Lang files disponibles: `en`, `es`.
- Las excepciones usan `__()` con claves `exceptions.{entity}.{key}`.
- Los labels de enums están en español (`ProjectStatusEnum::label()` devuelve "Activo", "Completado", etc.).

### 13.6 CORS y Stateful

- Middleware `EnsureFrontendRequestsAreStateful` aplicado al grupo API (SPA-first).
- CSRF excluido en `api/*`.
- Cookies no encriptadas: `appearance`, `sidebar_state` (frontend).

---

## 14. Endpoints Resumen (Quick Reference)

| #   | Método | Ruta                                               | Auth | Policy          | Descripción               |
| --- | ------ | -------------------------------------------------- | ---- | --------------- | ------------------------- |
| 1   | POST   | `/api/auth/login`                                  | —    | —               | Login + emisión de token  |
| 2   | POST   | `/api/auth/logout`                                 | ✓    | —               | Revoca token actual       |
| 3   | POST   | `/api/auth/logout-all`                             | ✓    | —               | Revoca todos los tokens   |
| 4   | GET    | `/api/auth/me`                                     | ✓    | —               | Usuario actual            |
| 5   | GET    | `/api/dashboard/stats`                             | ✓    | `viewDashboard` | Métricas agregadas        |
| 6   | GET    | `/api/projects`                                    | ✓    | `viewAny`       | Listado paginado          |
| 7   | POST   | `/api/projects`                                    | ✓    | `create`        | Crear proyecto            |
| 8   | GET    | `/api/projects/{project}`                          | ✓    | `view`          | Detalle (+stats opcional) |
| 9   | PATCH  | `/api/projects/{project}`                          | ✓    | `update`        | Actualizar                |
| 10  | DELETE | `/api/projects/{project}`                          | ✓    | `delete`        | Eliminar (lógico)         |
| 11  | POST   | `/api/projects/{project}/restore`                  | ✓    | `restore`       | Restaurar                 |
| 12  | GET    | `/api/projects/{project}/users`                    | ✓    | `viewAny`       | Usuarios del proyecto     |
| 13  | GET    | `/api/projects/{project}/users/role/{projectRole}` | ✓    | `viewAny`       | Usuarios por rol          |
| 14  | POST   | `/api/projects/{project}/users`                    | ✓    | `create`        | Asignar usuario           |
| 15  | DELETE | `/api/projects/{project}/users/{user}`             | ✓    | `delete`        | Remover usuario           |
| 16  | GET    | `/api/projects/{project}/tasks`                    | ✓    | `viewAny`       | Tasks del proyecto        |
| 17  | POST   | `/api/projects/{project}/tasks`                    | ✓    | `create`        | Crear task                |
| 18  | PATCH  | `/api/tasks/bulk-update`                           | ✓    | `update`        | Update masivo             |
| 19  | GET    | `/api/tasks/{task}`                                | ✓    | `view`          | Detalle de task           |
| 20  | PATCH  | `/api/tasks/{task}`                                | ✓    | `update`        | Actualizar task           |
| 21  | DELETE | `/api/tasks/{task}`                                | ✓    | `delete`        | Eliminar (cascada)        |
| 22  | POST   | `/api/tasks/{task}/restore`                        | ✓    | `restore`       | Restaurar (cascada)       |
| 23  | GET    | `/api/tasks/{task}/assignments`                    | ✓    | `viewAny`       | Asignaciones              |
| 24  | POST   | `/api/tasks/{task}/assignments`                    | ✓    | `create`        | Asignar                   |
| 25  | PATCH  | `/api/tasks/{task}/assignments/{assignment}`       | ✓    | `update`        | Cambiar rol               |
| 26  | DELETE | `/api/tasks/{task}/assignments/{assignment}`       | ✓    | `delete`        | Desasignar                |

---

_Documento generado desde el código fuente del backend Laravel 13. Última revisión: junio 2026._
