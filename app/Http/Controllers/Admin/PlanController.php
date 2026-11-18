<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Service;
use App\Support\Pricing;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function create(Service $service, Pricing $pricing)
    {
        return view('admin.plans.form', [
            'service' => $service,
            'plan' => new Plan(['duration_months' => 1, 'is_available' => true]),
            'rate' => $pricing->rate(),
        ]);
    }

    public function store(Request $request, Service $service)
    {
        $plan = $service->plans()->create($this->validated($request));

        return redirect()->route('admin.services.edit', $service)->with('status', "Plan \"{$plan->name}\" agregado.");
    }

    public function edit(Plan $plan, Pricing $pricing)
    {
        return view('admin.plans.form', [
            'service' => $plan->service,
            'plan' => $plan,
            'rate' => $pricing->rate(),
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request));

        return redirect()->route('admin.services.edit', $plan->service)->with('status', "Plan \"{$plan->name}\" actualizado.");
    }

    public function toggle(Plan $plan)
    {
        $plan->update(['is_available' => ! $plan->is_available]);

        return back()->with('status', "Plan \"{$plan->name}\" ".($plan->is_available ? 'disponible.' : 'agotado.'));
    }

    public function destroy(Plan $plan)
    {
        $service = $plan->service;
        $plan->delete();

        return redirect()->route('admin.services.edit', $service)->with('status', 'Plan eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:99999'],
            'price_cop' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $data['sort_order'] ??= 0;

        return $data;
    }
}
