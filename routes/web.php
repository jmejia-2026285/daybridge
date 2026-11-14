<?php

use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\MoodleSsoController;
use App\Http\Controllers\ProfileAvatarController;
use App\Models\Conversation;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('offline', 'offline')->name('offline');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Volt::route('/', 'pages.home')->name('home');
    Volt::route('cumpleanos', 'pages.birthdays')->name('birthdays');
    Volt::route('comunicados', 'pages.feed.index')->name('feed.index');
    Volt::route('comunicados/{post}', 'pages.feed.show')->name('feed.show');
    Volt::route('muro', 'pages.wall.index')->name('wall.index');
    Volt::route('directorio', 'pages.directory.index')->name('directory.index');
    Volt::route('directorio/{person}', 'pages.directory.show')->name('directory.show');
    Volt::route('tareas', 'pages.tasks.index')->name('tasks.index');
    Volt::route('encuestas', 'pages.surveys.index')->name('surveys.index');
    Volt::route('encuestas/nueva', 'pages.surveys.create')->name('surveys.create');
    Volt::route('encuestas/{survey}', 'pages.surveys.show')->name('surveys.show');
    Volt::route('documentos', 'pages.documents.index')->name('documents.index');
    Route::get('documentos/{document}/descargar', DocumentDownloadController::class)->name('documents.download');
    Volt::route('capacitacion', 'pages.learning.index')->name('learning.index');
    Route::get('capacitacion/moodle', MoodleSsoController::class)->name('learning.sso');
    Volt::route('formularios', 'pages.forms.index')->name('forms.index');
    Volt::route('formularios/nuevo', 'pages.forms.create')->name('forms.create');
    Volt::route('formularios/{form}', 'pages.forms.show')->name('forms.show');
    Volt::route('admin/personas', 'pages.admin.people')->name('admin.people');
    Volt::route('admin/comunicados', 'pages.admin.content')->name('admin.content');
    Volt::route('admin/apariencia', 'pages.admin.appearance')->name('admin.appearance');

    Route::post('admin/fondo/update', [BackgroundController::class, 'update'])->name('admin.background.update');
    Route::post('admin/fondo/destroy', [BackgroundController::class, 'destroy'])->name('admin.background.destroy');

    Route::post('profile/avatar/update', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
    Route::post('profile/avatar/destroy', [ProfileAvatarController::class, 'destroy'])->name('profile.avatar.destroy');

    Volt::route('it-desk', 'pages.it-desk.dashboard')->name('it-desk.dashboard');
    Volt::route('it-desk/mi-equipo', 'pages.it-desk.my-equipment')->name('it-desk.my-equipment');
    Volt::route('it-desk/agenda', 'pages.it-desk.agenda')->name('it-desk.agenda');
    Volt::route('it-desk/equipos', 'pages.it-desk.equipment.index')->name('it-desk.equipment.index');
    Volt::route('it-desk/equipos/nuevo', 'pages.it-desk.equipment.create')->name('it-desk.equipment.create');
    Volt::route('it-desk/equipos/{equipment}/imprimir/{ticket?}', 'pages.it-desk.equipment.print')->name('it-desk.equipment.print');
    Volt::route('it-desk/equipos/{equipment}', 'pages.it-desk.equipment.show')->name('it-desk.equipment.show');
    Volt::route('it-desk/equipos/{equipment}/editar', 'pages.it-desk.equipment.edit')->name('it-desk.equipment.edit');

    Route::get('mensajes', function () {
        return view('chat.index', ['title' => 'Mensajes · Daybridge']);
    })->name('chat.index');
    Route::get('mensajes/nueva', function () {
        return view('chat.create', ['title' => 'Nueva conversación · Daybridge']);
    })->name('chat.create');
    Route::get('mensajes/{conversation}', function (Conversation $conversation) {
        abort_unless($conversation->participants()->where('user_id', auth()->id())->exists(), 403);

        return view('chat.show', ['conversation' => $conversation, 'title' => 'Chat · Daybridge']);
    })->name('chat.show');

    Volt::route('notificaciones', 'notifications.index')->name('notifications.index');
});

Route::view('profile', 'profile')
    ->middleware(['auth', 'active'])
    ->name('profile');

require __DIR__.'/auth.php';
