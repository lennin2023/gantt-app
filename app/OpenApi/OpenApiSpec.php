<?php

namespace App\OpenApi;

use OpenApi\Attributes as OAT;

#[OAT\Info(
    title: 'Gantt App API',
    version: '1.0.0',
    description: 'API REST para gestión de proyectos y tareas con diagramas Gantt',
)]
#[OAT\Contact(name: 'Gantt App Team')]
#[OAT\Server(url: '/api', description: 'API base URL')]
#[OAT\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token de autenticación Laravel Sanctum'
)]
class OpenApiSpec {}
