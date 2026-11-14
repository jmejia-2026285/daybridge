<?php

return [
    'home' => [
        'label' => 'Inicio',
        'short' => 'Inicio',
        'icon' => 'home',
        'route' => 'home',
        'primary' => true,
        'mobile' => true,
        'enabled' => true,
    ],

    'birthdays' => [
        'label' => 'Cumpleaños',
        'short' => 'Cumpleaños',
        'icon' => 'cake',
        'route' => 'birthdays',
        'primary' => true,
        'mobile' => false,
        'enabled' => true,
    ],

    'feed' => [
        'label' => 'Comunicados',
        'short' => 'Com.',
        'icon' => 'megaphone',
        'route' => 'feed.index',
        'primary' => true,
        'mobile' => true,
        'enabled' => true,
    ],

    'directory' => [
        'label' => 'Directorio',
        'short' => 'Directorio',
        'icon' => 'users',
        'route' => 'directory.index',
        'primary' => true,
        'mobile' => true,
        'enabled' => true,
    ],

    'tasks' => [
        'label' => 'Tareas',
        'short' => 'Tareas',
        'icon' => 'task',
        'route' => 'tasks.index',
        'primary' => true,
        'mobile' => false,
        'enabled' => true,
    ],

    'it_desk' => [
        'label' => 'IT Desk',
        'short' => 'IT Desk',
        'icon' => 'monitor',
        'route' => 'it-desk.dashboard',
        'primary' => true,
        'mobile' => false,
        'enabled' => true,
        'it_desk_only' => true,
    ],

    'my_equipment' => [
        'label' => 'Mi equipo',
        'short' => 'Mi equipo',
        'icon' => 'monitor',
        'route' => 'it-desk.my-equipment',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
    ],

    'wall' => [
        'label' => 'Muro',
        'short' => 'Muro',
        'icon' => 'chat',
        'route' => 'wall.index',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
    ],

    'surveys' => [
        'label' => 'Encuestas',
        'short' => 'Encuestas',
        'icon' => 'poll',
        'route' => 'surveys.index',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
    ],

    'documents' => [
        'label' => 'Documentos',
        'short' => 'Documentos',
        'icon' => 'folder',
        'route' => 'documents.index',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
    ],

    'learning' => [
        'label' => 'Capacitación',
        'short' => 'Cursos',
        'icon' => 'book',
        'route' => 'learning.index',
        'primary' => false,
        'mobile' => false,
        'enabled' => (bool) env('MOODLE_ENABLED', false),
    ],

    'forms' => [
        'label' => 'Formularios',
        'short' => 'Formularios',
        'icon' => 'clipboard',
        'route' => 'forms.index',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
    ],

    'chat' => [
        'label' => 'Mensajes',
        'short' => 'Mensajes',
        'icon' => 'chat',
        'route' => 'chat.index',
        'primary' => true,
        'mobile' => true,
        'enabled' => true,
    ],

    'notifications' => [
        'label' => 'Notificaciones',
        'short' => 'Avisos',
        'icon' => 'bell',
        'route' => 'notifications.index',
        'primary' => true,
        'mobile' => true,
        'enabled' => true,
    ],

    'admin' => [
        'label' => 'Administración',
        'short' => 'Admin',
        'icon' => 'settings',
        'route' => 'admin.people',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
        'admin_only' => true,
    ],

    'appearance' => [
        'label' => 'Apariencia',
        'short' => 'Apariencia',
        'icon' => 'sun',
        'route' => 'admin.appearance',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
        'admin_only' => true,
    ],

    'profile' => [
        'label' => 'Perfil',
        'short' => 'Perfil',
        'icon' => 'user',
        'route' => 'profile',
        'primary' => false,
        'mobile' => false,
        'enabled' => true,
    ],
];
