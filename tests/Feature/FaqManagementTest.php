<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_faqs_list(): void
    {
        $user = User::factory()->create();

        Faq::create([
            'question' => '¿Tienen garantía las refacciones?',
            'answer' => 'Sí, garantía escrita de 30 días.',
            'category' => 'garantias',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.faqs.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_faq_and_auto_assign_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => '¿Hacen entregas en Tepatitlán?',
            'answer' => 'Sí, entrega inmediata en sucursal.',
            'category' => 'envios',
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('faqs', [
            'question' => '¿Hacen entregas en Tepatitlán?',
            'order' => 1,
        ]);
    }

    public function test_can_reorder_faqs(): void
    {
        $user = User::factory()->create();

        $faq1 = Faq::create([
            'question' => 'Pregunta 1',
            'answer' => 'Respuesta 1',
            'category' => 'general',
            'order' => 1,
            'is_active' => true,
        ]);

        $faq2 = Faq::create([
            'question' => 'Pregunta 2',
            'answer' => 'Respuesta 2',
            'category' => 'general',
            'order' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('admin.faqs.reorder', $faq2->id), [
            'direction' => 'up',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(1, $faq2->fresh()->order);
        $this->assertEquals(2, $faq1->fresh()->order);
    }
}
