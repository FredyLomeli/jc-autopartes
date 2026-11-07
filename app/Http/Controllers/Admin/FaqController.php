<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    /**
     * Listado de FAQs
     */
    public function index(Request $request): Response
    {
        $category = $request->query('category');

        $faqs = Faq::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => $faqs,
            'currentCategory' => $category,
        ]);
    }

    /**
     * Guarda una nueva FAQ
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['required', 'in:general,garantias,envios,pagos'],
            'is_active' => ['boolean'],
        ]);

        $order = (Faq::max('order') ?? 0) + 1;

        Faq::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'],
            'order' => $order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Pregunta frecuente agregada exitosamente.');
    }

    /**
     * Actualiza una FAQ existente
     */
    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['required', 'in:general,garantias,envios,pagos'],
            'is_active' => ['boolean'],
        ]);

        $faq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Pregunta frecuente actualizada exitosamente.');
    }

    /**
     * Reordena las FAQs hacia arriba o hacia abajo
     */
    public function reorder(Request $request, Faq $faq): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $allFaqs = Faq::query()
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $currentIndex = $allFaqs->search(fn ($f) => $f->id === $faq->id);

        if ($currentIndex === false) {
            return back();
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex >= 0 && $targetIndex < $allFaqs->count()) {
            $otherFaq = $allFaqs[$targetIndex];

            $allFaqs[$currentIndex] = $otherFaq;
            $allFaqs[$targetIndex] = $faq;

            foreach ($allFaqs->values() as $index => $item) {
                $item->order = $index + 1;
                $item->save();
            }
        }

        return back()->with('success', 'Orden de preguntas actualizado.');
    }

    /**
     * Elimina una FAQ
     */
    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', 'Pregunta frecuente eliminada exitosamente.');
    }
}
