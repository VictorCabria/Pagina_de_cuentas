<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with(['category', 'plans'])
            ->when($request->filled('categoria'), fn ($q) => $q->where('category_id', $request->integer('categoria')))
            ->when($request->input('estado') === 'agotados', fn ($q) => $q->where('is_available', false))
            ->when($request->input('estado') === 'disponibles', fn ($q) => $q->where('is_available', true))
            ->ordered()
            ->get();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => Category::ordered()->get(),
            'currency' => Currency::from(Setting::get('default_currency')),
        ]);
    }

    public function create()
    {
        return view('admin.services.form', [
            'service' => new Service(['is_active' => true, 'is_available' => true, 'color' => '#4d8dff']),
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $service = Service::create($this->validated($request));

        return redirect()->route('admin.services.edit', $service)
            ->with('status', "Servicio \"{$service->name}\" creado. Ahora agrégale sus planes.");
    }

    public function edit(Service $service)
    {
        $service->load('plans');

        return view('admin.services.form', [
            'service' => $service,
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request, $service));

        return redirect()->route('admin.services.index')->with('status', "Servicio \"{$service->name}\" actualizado.");
    }

    /** Marca el servicio como disponible o agotado con un clic. */
    public function toggle(Service $service)
    {
        $service->update(['is_available' => ! $service->is_available]);

        return back()->with('status', $service->is_available
            ? "\"{$service->name}\" vuelve a estar disponible."
            : "\"{$service->name}\" marcado como agotado.");
    }

    public function destroy(Service $service)
    {
        if ($service->logo && ! str_starts_with($service->logo, 'images/')) {
            Storage::disk('public')->delete($service->logo);
        }
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', 'Servicio eliminado junto con sus planes.');
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'alpha_dash', 'max:110', Rule::unique('services', 'slug')->ignore($service)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        // Una característica por línea
        $data['features'] = collect(preg_split('/\r\n|\r|\n/', $data['features'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
        $data['is_available'] = $request->boolean('is_available');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] ??= 0;

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($service?->logo && ! str_starts_with($service->logo, 'images/')) {
                Storage::disk('public')->delete($service->logo);
            }
            $data['logo'] = $request->hasFile('logo') ? $request->file('logo')->store('logos', 'public') : null;
        } else {
            unset($data['logo']);
        }

        unset($data['remove_logo']);

        return $data;
    }
}
