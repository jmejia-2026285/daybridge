<?php

namespace Tests\Feature;

use App\Livewire\Chat\Index;
use App\Livewire\Chat\Show;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('chat.index'))->assertRedirect(route('login'));
    }

    public function test_participants_can_open_a_conversation_and_outsiders_are_forbidden(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();

        $conversation = $this->createConversation($initiator, $recipient);

        $this->actingAs($outsider)
            ->get(route('chat.show', $conversation))
            ->assertForbidden();

        $this->actingAs($recipient)
            ->get(route('chat.show', $conversation))
            ->assertOk();
    }

    public function test_users_can_send_a_message_and_the_conversation_responds(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $this->actingAs($initiator);

        Livewire::test(Show::class, ['conversation' => $conversation])
            ->set('message', '¿Cómo va el informe?')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSet('message', '');

        $message = Message::query()->sole();

        $this->assertSame('¿Cómo va el informe?', $message->body);
        $this->assertTrue($message->sender->is($initiator));
        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_empty_messages_are_rejected(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $this->actingAs($initiator);

        Livewire::test(Show::class, ['conversation' => $conversation])
            ->set('message', '')
            ->call('sendMessage')
            ->assertHasErrors(['message' => 'required']);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_sending_a_reply_persists_the_quoted_message(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $first = $conversation->messages()->create([
            'sender_id' => $recipient->id,
            'body' => 'El informe va listo para el viernes.',
            'type' => 'text',
        ]);

        $this->actingAs($initiator);

        Livewire::test(Show::class, ['conversation' => $conversation])
            ->set('replyingToId', $first->id)
            ->set('message', 'perfecto, avisa cuando cargue el PDF')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $fresh = Message::query()->where('sender_id', $initiator->id)->sole();

        $this->assertTrue($fresh->replyTo()->is($first));
    }

    public function test_senders_can_delete_their_own_message_and_the_recap_is_recalculated(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $first = $conversation->messages()->create([
            'sender_id' => $initiator->id,
            'body' => 'Primer mensaje del día.',
            'type' => 'text',
            'created_at' => today()->subHours(2),
            'updated_at' => today()->subHours(2),
        ]);

        $second = $conversation->messages()->create([
            'sender_id' => $recipient->id,
            'body' => 'Otro mensaje para la historia.',
            'type' => 'text',
        ]);

        $this->actingAs($recipient);

        Livewire::test(Show::class, ['conversation' => $conversation])
            ->call('deleteMessage', $second->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('messages', ['id' => $second->id]);
        $this->assertSame($first->created_at->format('YmdHis'), $conversation->fresh()->last_message_at->format('YmdHis'));
    }

    public function test_participants_cannot_delete_someone_elses_message(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $message = $conversation->messages()->create([
            'sender_id' => $initiator->id,
            'body' => 'Este mensaje no se borra.',
            'type' => 'text',
        ]);

        $this->assertFalse($recipient->can('delete', $message));

        $this->actingAs($recipient);

        try {
            Livewire::test(Show::class, ['conversation' => $conversation])
                ->call('deleteMessage', $message->id);
        } catch (\Throwable $e) {
            // La petición simulada también puede relanzar el abort.
        }

        $this->assertDatabaseHas('messages', ['id' => $message->id]);
    }

    public function test_starting_a_direct_chat_is_idempotent(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($initiator);

        $component = Livewire::test(Index::class)
            ->call('startConversation', $recipient->id)
            ->assertHasNoErrors();

        $conversation = Conversation::query()->sole();

        $component->assertSet('selectedConversationId', $conversation->id);

        $this->assertTrue($conversation->participants->contains($initiator->id));
        $this->assertTrue($conversation->participants->contains($recipient->id));
        $this->assertSame($initiator->id, $conversation->creator_id);

        Livewire::test(Index::class)
            ->call('startConversation', $recipient->id)
            ->assertHasNoErrors()
            ->assertSet('selectedConversationId', $conversation->id);

        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_participants_can_open_a_conversation_inside_the_inbox_panel(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $this->actingAs($recipient);

        Livewire::test(Index::class)
            ->call('openConversation', $conversation->id)
            ->assertSet('selectedConversationId', $conversation->id)
            ->assertSee('Escribe un mensaje');

        $this->assertSame(0, $conversation->fresh()->unreadCountFor($recipient));
    }

    public function test_outsiders_cannot_load_a_conversation_into_the_inbox_panel(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $this->actingAs($outsider);

        Livewire::test(Index::class)
            ->call('openConversation', $conversation->id)
            ->assertDontSee('Escribe un mensaje')
            ->assertSee('Selecciona una conversación');

        $recipientPivot = $conversation->fresh()->participants()
            ->where('user_id', $recipient->id)
            ->first()
            ->pivot;

        $this->assertNull($recipientPivot->last_read_at);
    }

    public function test_chat_messages_are_read_oldest_first(): void
    {
        $initiator = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = $this->createConversation($initiator, $recipient);

        $older = $conversation->messages()->create([
            'sender_id' => $recipient->id,
            'body' => 'Primer mensaje del hilo.',
            'type' => 'text',
        ]);

        $newer = $conversation->messages()->create([
            'sender_id' => $initiator->id,
            'body' => 'Segundo mensaje del hilo.',
            'type' => 'text',
        ]);

        $this->actingAs($initiator);

        $component = Livewire::test(Show::class, ['conversation' => $conversation]);

        $paginatorIds = $component->instance()->chatMessages->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // El paginador trae las más recientes primero (la vista las invierte al pintarlas).
        $this->assertSame([$newer->id, $older->id], $paginatorIds);

        // Tras el reverse() de la vista el hilo queda cronológico: lo más nuevo al fondo.
        $displayIds = $component->instance()->chatMessages->reverse()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertSame([$older->id, $newer->id], $displayIds);
    }

    public function test_you_cannot_start_a_chat_with_yourself(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        try {
            Livewire::test(Index::class)
                ->call('startConversation', $user->id);
        } catch (\Throwable $e) {
            // La petición simulada también puede relanzar el abort 422.
        }

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_chats_cannot_be_started_with_inactive_people(): void
    {
        $user = User::factory()->create();
        $inactive = User::factory()->inactive()->create();

        $this->actingAs($user);

        try {
            Livewire::test(Index::class)
                ->call('startConversation', $inactive->id);
        } catch (\Throwable $e) {
            // La petición simulada también puede relanzar el 404 del findOrFail.
        }

        $this->assertDatabaseCount('conversations', 0);
    }

    private function createConversation(User $initiator, User $recipient): Conversation
    {
        $conversation = Conversation::query()->create([
            'creator_id' => $initiator->id,
            'is_group' => false,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->attach([
            $initiator->id => ['is_admin' => true, 'last_read_at' => now()],
            $recipient->id => ['is_admin' => false, 'last_read_at' => null],
        ]);

        return $conversation;
    }
}
