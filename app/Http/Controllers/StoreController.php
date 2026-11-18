<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Models\Category;
use App\Models\Combo;
use App\Models\Service;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()->ordered()
            ->with(['services' => fn ($query) => $query->active()->ordered()->with('plans')])
            ->get()
            ->filter(fn (Category $category) => $category->services->isNotEmpty());

        return view('store.index', [
            'currency' => Currency::current(),
            'categories' => $categories,
            'services' => $categories->flatMap->services,
            'licenses' => $categories->firstWhere('slug', 'licencias')?->services ?? collect(),
            'combos' => Combo::active()->ordered()->with('plans.service')->get(),
        ]);
    }

    public function show(Service $service): View
    {
        abort_unless($service->is_active, 404);

        $service->load(['plans', 'category']);

        return view('store.show', [
            'currency' => Currency::current(),
            'service' => $service,
            'related' => Service::active()->ordered()->with(['plans', 'category'])
                ->where('category_id', $service->category_id)
                ->whereKeyNot($service->id)
                ->limit(4)
                ->get(),
        ]);
    }
}
