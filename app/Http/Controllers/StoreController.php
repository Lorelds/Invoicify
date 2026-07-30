<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::query();

        // Search logic
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort logic
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $stores = $query->paginate(10)->withQueryString();
        return view('stores.index', compact('stores'));
    }

    public function create()
    {
        return view('stores.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('stores', 'name')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id ?? null);
                })
            ],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $store = Store::create($request->all());

        if ($request->wantsJson()) {
            return response()->json(['store' => $store, 'message' => __('Store created successfully.')]);
        }

        return redirect()->route('stores.index')
            ->with('success', __('Store created successfully.'));
    }

    public function show(Store $store)
    {
        return view('stores.show', compact('store'));
    }

    public function edit(Store $store)
    {
        return view('stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('stores', 'name')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id ?? null);
                })->ignore($store->id)
            ],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $store->update($request->all());

        return redirect()->route('stores.index')
            ->with('success', __('Store updated successfully.'));
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()->route('stores.index')
            ->with('success', __('Store deleted successfully.'));
    }
}
