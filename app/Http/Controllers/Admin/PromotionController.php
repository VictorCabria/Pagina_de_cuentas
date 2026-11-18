<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function index()
    {
        return view('admin.promotions.index', [
            'promotions' => Promotion::with(['category', 'service', 'plan.service'])
                ->orderByDesc('is_active')
                ->latest()
                ->get(),
        ]);
    }

    public function create()
    {
        return view('admin.promotions.form', $this->formData(new Promotion(['is_active' => true, 'percent' => 10])));
    }

    public function store(Request $request)
    {
        $promotion = Promotion::create($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('status', "Promoción \"{$promotion->name}\" creada.");
    }

    public function edit(Promotion $promotion)
    {
        return view('admin.promotions.form', $this->formData($promotion));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $promotion->update($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('status', "Promoción \"{$promotion->name}\" actualizada.");
    }

    public function toggle(Promotion $promotion)
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return back()->with('status', "Promoción \"{$promotion->name}\" ".($promotion->is_active ? 'activada.' : 'pausada.'));
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('status', 'Promoción eliminada.');
    }

    private function formData(Promotion $promotion): array
    {
        return [
            'promotion' => $promotion,
            'scope' => match (true) {
                $promotion->plan_id !== null => 'plan',
                $promotion->service_id !== null => 'service',
                $promotion->category_id !== null => 'category',
                default => 'all',
            },
            'categories' => Category::ordered()->get(),
            'services' => Service::ordered()->get(),
            'plans' => Plan::with('service')->get()->sortBy(fn ($p) => $p->service->name.$p->duration_months),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'percent' => ['required', 'integer', 'min:1', 'max:100'],
            'scope' => ['required', Rule::in(['all', 'category', 'service', 'plan'])],
            'category_id' => ['nullable', 'required_if:scope,category', 'exists:categories,id'],
            'service_id' => ['nullable', 'required_if:scope,service', 'exists:services,id'],
            'plan_id' => ['nullable', 'required_if:scope,plan', 'exists:plans,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ], [
            'category_id.required_if' => 'Elige la categoría.',
            'service_id.required_if' => 'Elige el servicio.',
            'plan_id.required_if' => 'Elige el plan.',
            'ends_at.after' => 'La fecha de fin debe ser posterior al inicio.',
        ]);

        // Solo se guarda el destino del alcance elegido
        foreach (['category', 'service', 'plan'] as $target) {
            if ($data['scope'] !== $target) {
                $data[$target.'_id'] = null;
            }
        }
        unset($data['scope']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
