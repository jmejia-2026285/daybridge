<?php

namespace Tests\Feature;

use App\Models\BirthdayWish;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BirthdayWishButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_a_birthday_wish(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $sender = User::factory()->create();
        $recipient = User::factory()->create(['birth_date' => '1995-11-04']);

        $this->actingAs($sender);

        Volt::test('birthday-wish-button', ['recipient' => $recipient])
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('birthday_wishes', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'year' => 2026,
        ]);
    }

    public function test_user_cannot_send_more_than_one_wish_per_year(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $sender = User::factory()->create();
        $recipient = User::factory()->create(['birth_date' => '1995-11-04']);

        BirthdayWish::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'year' => 2026,
        ]);

        $this->actingAs($sender);

        Volt::test('birthday-wish-button', ['recipient' => $recipient])
            ->call('send')
            ->assertSee('Felicitado');

        $this->assertDatabaseCount('birthday_wishes', 1);
    }

    public function test_user_cannot_wish_themselves(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $user = User::factory()->create(['birth_date' => '1995-11-04']);

        $this->actingAs($user);

        Volt::test('birthday-wish-button', ['recipient' => $user])
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('birthday_wishes', 0);
    }

    public function test_sending_a_wish_notifies_the_recipient_once(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $sender = User::factory()->create();
        $recipient = User::factory()->create(['birth_date' => '1995-11-04']);

        $this->actingAs($sender);

        $component = Volt::test('birthday-wish-button', ['recipient' => $recipient]);
        $component->call('send');

        $this->assertDatabaseHas('notifications', [
            'type' => 'birthday_wish',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'actor_id' => $sender->id,
        ]);

        $component->call('send');

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('birthday_wishes', 1);
    }
}
