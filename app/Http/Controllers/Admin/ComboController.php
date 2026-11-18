<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(): View
    {
        return view('admin.combos.index', [
            'combos' => Combo::with('plans.service')->ordered()->get(),
            'currency' => Currency::from(Setting::get('default_currency')),
        ]);
    }

    public function create(): View
    {
        return view('admin.combos.form', $this->formData(new Combo([
            'is_active' => true, 'is_available' => true, 'duration_months' => 1,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $combo = Combo::create($this->validated($request));
        $combo->plans()->sync($request->input('plans', []));

        return redirect()->route('admin.combos.index')->with('status', "Combo \"{$combo->name}\" creado.");
    }

    public function edit(Combo $combo): View
    {
        return view('admin.combos.form', $this->formData($combo->load('plans')));
    }

    public function update(Request $request, Combo $combo): RedirectResponse
    {
        $combo->update($this->validated($request, $combo));
        $combo->plans()->sync($request->input('plans', []));

        return redirect()->route('admin.combos.index')->with('status', "Combo \"{$combo->name}\" actualizado.");
    }

    public function toggle(Combo $combo): RedirectResponse
    {
        $combo->update(['is_available' => ! $combo->is_available]);

        return back()->with('status', "Combo \"{$combo->name}\" ".($combo->is_available ? 'disponible.' : 'marcado como agotado.'));
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        if ($combo->image) {
            Storage::disk('public')->delete($combo->image);
        }
        $combo->delete();

        return redirect()->route('admin.combos.index')->with('status', 'Combo eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Combo $combo): array
    {
        return [
            'combo' => $combo,
            'services' => Service::with('plans')->ordered()->get()->filter(fn (Service $service) => $service->plans->isNotEmpty()),
            'selected' => old('plans', $combo->exists ? $combo->plans->pluck('id')->all() : []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Combo $combo = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'alpha_dash', 'max:110', Rule::unique('combos', 'slug')->ignore($combo)],
            'description' => ['nullable', 'string', 'max:255'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:36'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:100000'],
            'price_cop' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'plans' => ['required', 'array', 'min:2'],
            'plans.*' => ['integer', 'exists:plans,id'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'plans.required' => 'Elige al menos dos planes para el combo.',
            'plans.min' => 'Elige al menos dos planes para el combo.',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_available'] = $request->boolean('is_available');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] ??= 0;

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($combo?->image) {
                Storage::disk('public')->delete($combo->image);
            }
            $data['image'] = $request->hasFile('image') ? $request->file('image')->store('combos', 'public') : null;
        } else {
            unset($data['image']);
        }

        unset($data['plans'], $data['remove_image']);

        return $data;
    }
}
