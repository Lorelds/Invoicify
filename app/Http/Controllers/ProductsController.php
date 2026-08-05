<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class ProductsController extends Controller
{
    public function history(Request $request)
    {
        $movements = \App\Models\StockMovement::with(['product', 'receipt.store', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('products.history', compact('movements'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Search logic
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter logic
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Sort logic
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'stock_high':
                $query->orderBy('stock', 'desc');
                break;
            case 'stock_low':
                $query->orderBy('stock', 'asc');
                break;
            case 'price_high':
                $query->orderBy('sell_price', 'desc');
                break;
            case 'price_low':
                $query->orderBy('sell_price', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate(10)->withQueryString();
        $categories = Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except('image');
        $data['sku'] = $this->generateSKU($data['name']);
        
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('products.index')
            ->with('success', __('Product created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $history = \App\Models\ReceiptItem::where('product_id', $product->id)
            ->join('receipts', 'receipt_items.receipt_id', '=', 'receipts.id')
            ->select('receipt_items.unit_price', 'receipts.transaction_date')
            ->orderBy('receipts.transaction_date', 'asc')
            ->get();

        $price_history_dates = [];
        $price_history_values = [];

        foreach ($history as $record) {
            $date = \Carbon\Carbon::parse($record->transaction_date)->format('d M Y');
            // Prevent duplicate dates on the same day if multiple receipts were scanned, 
            // or just plot all data points. We will plot all points.
            $price_history_dates[] = $date;
            $price_history_values[] = $record->unit_price;
        }

        // Add the current creation date/price as the first data point if history is empty or if we want the baseline
        if (empty($price_history_dates)) {
            $price_history_dates[] = $product->created_at->format('d M Y');
            $price_history_values[] = $product->buy_price;
        }

        $stock_movements = $product->stockMovements()->with('receipt.store', 'user')->orderBy('created_at', 'desc')->get();

        return view('products.show', compact('product', 'price_history_dates', 'price_history_values', 'stock_movements'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->input('remove_image') == '1') {
            if ($product->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = null;
        }

        $product->update($data);

        return redirect()->route('products.index')
            ->with('success', __('Product updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->image_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', __('Product deleted successfully.'));
    }

    // Generate SKU from product name
    // Format: 3-4 letter abbreviation (first letter of each word) + numbers from name, ALL CAPS
    // e.g. "Paku Raja 5" → "PR5", "Kawat BWG 10" → "KB10", "Asbes 180" → "ASB180"
    private function generateSKU($name)
    {
        // Split into words, remove special chars like @
        $cleanName = preg_replace('/[@#]/', '', $name);
        $words = preg_split('/[\s\-_\/]+/', $cleanName);

        $letters = '';
        $numbers = '';

        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '') continue;

            // If word is purely numeric, treat as the size/quantity part
            if (preg_match('/^\d+$/', $word)) {
                $numbers .= $word;
            } else {
                // Extract leading letters for abbreviation, and trailing numbers
                if (preg_match('/^([a-zA-Z]+)(\d*)$/', $word, $m)) {
                    $letters .= strtoupper(substr($m[1], 0, 1));
                    if ($m[2] !== '') {
                        $numbers .= $m[2];
                    }
                } else {
                    // Mixed/special word — just take first letter
                    $firstChar = substr(preg_replace('/[^a-zA-Z]/', '', $word), 0, 1);
                    if ($firstChar) {
                        $letters .= strtoupper($firstChar);
                    }
                    // Extract any digits
                    $digits = preg_replace('/[^\d]/', '', $word);
                    if ($digits) {
                        $numbers .= $digits;
                    }
                }
            }
        }

        // Ensure letters part is 2-4 chars (pad with extra chars from first word if only 1 letter)
        if (strlen($letters) < 2 && count($words) > 0) {
            $firstWord = preg_replace('/[^a-zA-Z]/', '', $words[0]);
            $letters = strtoupper(substr($firstWord, 0, 3));
        }

        // Cap letters at 4 characters max
        $letters = substr($letters, 0, 4);

        $sku = $letters . $numbers;

        // Ensure uniqueness
        $baseSku = $sku;
        $counter = 1;
        while (Product::where('sku', $sku)->exists()) {
            $sku = $baseSku . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    public function adjustStock(\Illuminate\Http\Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'stock_adjust')
                ->withInput();
        }

        if ($request->type == 'in') {
            $product->increment('stock', $request->quantity);
        } else {
            // Optional: prevent negative stock
            // if ($product->stock < $request->quantity) {
            //     return redirect()->back()->with('error', __('Not enough stock to deduct.'));
            // }
            $product->decrement('stock', $request->quantity);
        }

        $newBalance = $product->fresh()->stock;

        \App\Models\StockMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'quantity' => $request->quantity,
            'balance' => $newBalance,
            'notes' => 'Manual: ' . $request->notes,
        ]);

        return redirect()->route('products.show', $product->id)
            ->with('success', __('Stock adjusted successfully.'));
    }
}
