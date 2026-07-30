<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Receipt;
use App\Models\Store;
use App\Models\Product;
use App\Models\ReceiptItem;
use App\Models\Debt;
use Illuminate\Support\Facades\Http;

use Intervention\Image\Facades\Image as InterventionImage;

class ReceiptController extends Controller
{
    // Show list of all receipts (History)
    public function index(Request $request)
    {
        $query = Receipt::with('store');

        // Search logic
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('store', function($qStore) use ($search) {
                      $qStore->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        // Sort logic
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'highest_amount':
                $query->orderBy('total_amount', 'desc');
                break;
            case 'lowest_amount':
                $query->orderBy('total_amount', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $receipts = $query->paginate(10)->withQueryString();
        
        return view('admin.receipts.index', compact('receipts'));
    }

    // Show upload form
    public function create()
    {
        $stores = Store::all();
        return view('admin.receipts.upload', compact('stores'));
    }

    // Show receipt details
    public function show($id)
    {
        $receipt = Receipt::with(['store', 'items', 'validatedBy', 'debt'])->findOrFail($id);
        return view('admin.receipts.show', compact('receipt'));
    }

    // Handle file upload and OCR processing
    public function upload(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'store_id' => 'required|exists:stores,id',
            'type' => 'required|in:pembelian,penjualan',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Store the uploaded image
        $imagePath = $request->file('receipt_image')->store('receipts', 'public');

        // Get full path for Tesseract processing
        $fullImagePath = Storage::disk('public')->path($imagePath);

        // Use Gemini API instead of Tesseract
        $apiKey = config('services.gemini.key');
        
        $base64Image = base64_encode(file_get_contents($fullImagePath));
        $mimeType = mime_content_type($fullImagePath);

        $prompt = "Extract the receipt details into JSON. This is an Indonesian hardware store (toko bangunan) receipt with handwritten items. Common items include 'Pasir', 'Besi Polos' or 'Polos', 'Besi Ulir', 'Semen', 'Paku', 'Bendrat', 'Pipa', etc. Be very careful with messy handwriting (e.g. read 'Polos' instead of 'Plor' or 'Pdor'). We need the following fields exactly: 'receipt_number' (string, e.g. from 'No. Nota'), 'store_name' (string), 
        'transaction_date' (YYYY-MM-DD format), 'total_amount' (number), and an array of 'items' containing 'name' (string),
        'quantity' (raw quantity from the leftmost column, number), 'measure' (extract the multiplier like 6 for 6m or 25 for 25kg, default to 1, number), 
        'unit_price' (number), 'subtotal' (number). If a field is not found, leave it null or 0. Respond with ONLY the JSON object, nothing else.";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ]
        ]);

        $rawText = "AI Extracted Data";
        $parsedData = [
            'receipt_number' => null,
            'store_name' => null,
            'transaction_date' => null,
            'total_amount' => 0,
            'items' => []
        ];
        $apiError = null;

        if ($response->successful()) {
            $responseData = $response->json();
            $content = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            
            // Clean up any markdown code blocks if gemini returns them despite response_mime_type
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*/', '', $content);
            
            $aiData = json_decode($content, true);
            if (is_array($aiData)) {
                $parsedData = array_merge($parsedData, $aiData);
                $rawText = json_encode($parsedData, JSON_PRETTY_PRINT);
                
                // Keep ai items in session so validate page can use them
                session()->put('ai_receipt_items_' . $imagePath, $parsedData['items'] ?? []);
            }
        } else {
            $errorBody = $response->json();
            $apiError = $errorBody['error']['message'] ?? 'The AI model is currently unavailable or busy. Please enter items manually.';
            \Log::error('Gemini API Error: ' . $response->body());
        }

        // Calculate total amount from items to ensure consistency with validation page
        $calculatedTotal = 0;
        if (!empty($parsedData['items']) && is_array($parsedData['items'])) {
            foreach ($parsedData['items'] as $item) {
                $qty = isset($item['quantity']) ? (float)$item['quantity'] : 1;
                $measure = isset($item['measure']) && $item['measure'] > 0 ? (float)$item['measure'] : 1;
                $price = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                $calculatedTotal += ($qty * $measure * $price);
            }
        }
        $totalAmount = $calculatedTotal > 0 ? $calculatedTotal : ($parsedData['total_amount'] ?? 0.00);

        // Create receipt record
        $receipt = Receipt::create([
            'store_id' => $request->store_id,
            'image_path' => $imagePath,
            'raw_text' => $rawText,
            'receipt_number' => $parsedData['receipt_number'] ?? null,
            'store_name' => $parsedData['store_name'] ?? null,
            'transaction_date' => $parsedData['transaction_date'] ?? null,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'type' => $request->type,
            'payment_status' => 'hutang', // Default to hutang, will be confirmed in validation
        ]);

        if ($apiError) {
            return redirect()->route('admin.receipts.validate', $receipt->id)
                ->withErrors(['ai_error' => 'AI Extraction Failed: ' . $apiError]);
        }

        // Redirect to validation page
        return redirect()->route('admin.receipts.validate', $receipt->id)
            ->with('success', __('Receipt uploaded successfully. Please validate the extracted data.'));
    }

    // Show validation form
    public function validate($id)
    {
        $receipt = Receipt::with('store')->findOrFail($id);
        $stores = Store::all();
        $categories = Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');
        
        // Use session items if available, or parse raw JSON
        $parsedData = json_decode($receipt->raw_text, true) ?? [];
        if (session()->has('ai_receipt_items_' . $receipt->image_path)) {
            $parsedData['items'] = session('ai_receipt_items_' . $receipt->image_path);
        }

        // If already validated, redirect to list or show message
        if ($receipt->status === 'validated') {
            return redirect()->route('admin.receipts.index')
                ->with('info', __('This receipt has already been validated.'));
        }

        return view('admin.receipts.validate', compact('receipt', 'stores', 'categories', 'parsedData'));
    }

    // Handle validation and save data
    public function validateSubmit(Request $request, $id)
    {
        $request->validate([
            'receipt_number' => 'nullable|string|max:255',
            'store_id' => 'required_without:new_store|nullable|exists:stores,id',
            'new_store' => 'required_without:store_id|nullable|string|max:255',
            'transaction_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:lunas,hutang,partial',
            'amount_paid' => 'nullable|required_if:payment_status,partial|numeric|min:0|max:'.$request->total_amount,
            'payment_method' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.category' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.measure' => 'nullable|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $receipt = Receipt::findOrFail($id);

        $storeId = $request->store_id;
        if ($request->filled('new_store')) {
            $store = Store::create(['name' => $request->new_store]);
            $storeId = $store->id;
        }

        // Update receipt with validated data
        $receipt->update([
            'receipt_number' => $request->receipt_number,
            'store_id' => $storeId,
            'transaction_date' => $request->transaction_date,
            'total_amount' => $request->total_amount,
            'payment_status' => $request->payment_status === 'partial' ? 'hutang' : $request->payment_status,
            'status' => 'validated',
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        // Process receipt items and update inventory
        $this->processReceiptItems($receipt, $request->items);

        // Create debt record and any initial payments
        $this->createDebtFromReceipt($receipt, $request->payment_status, $request->amount_paid, $request->payment_method);

        return redirect()->route('admin.receipts.index')
            ->with('success', __('Receipt validated and processed successfully.'));
    }

    public function destroy(\App\Models\Receipt $receipt)
    {
        // 1 & 2: Revert Inventory and Delete Debt only if the receipt was validated
        if ($receipt->status === 'validated') {
            foreach ($receipt->items as $item) {
                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    $product->stock -= $item->quantity;
                    // Ensure stock doesn't go below 0 just in case
                    if ($product->stock < 0) {
                        $product->stock = 0;
                    }
                    $product->save();
                }
            }

            $debt = \App\Models\Debt::where('store_id', $receipt->store_id)
                ->where('amount', $receipt->total_amount)
                ->where('notes', 'like', '%#' . $receipt->id . '%')
                ->first();
                
            if ($debt) {
                // Also delete its payments
                \App\Models\DebtPayment::where('debt_id', $debt->id)->delete();
                $debt->delete();
            }
        }

        // 3. Delete Receipt Items (if DB cascade isn't set up)
        \App\Models\ReceiptItem::where('receipt_id', $receipt->id)->delete();

        // 4. Delete the receipt image file if exists
        if ($receipt->image_path && \Illuminate\Support\Facades\Storage::exists($receipt->image_path)) {
            \Illuminate\Support\Facades\Storage::delete($receipt->image_path);
        }

        // 5. Delete Receipt
        $receipt->delete();

        return redirect()->route('admin.receipts.index')
            ->with('success', __('Receipt completely deleted. Inventory and debts reverted.'));
    }

    // Preprocess image for better OCR results
    private function preprocessImage($imagePath)
    {
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $image = $manager->read($imagePath);

        // Convert to grayscale
        $image->greyscale();

        // Increase contrast (Intervention v3 contrast takes values from -100 to 100)
        $image->contrast(10);

        // Resize if too small (OCR works better on larger images)
        // Intervention v3 uses scale() to maintain aspect ratio
        if ($image->width() < 1000) {
            $image->scale(width: 1000);
        }

        // Save preprocessed image
        $preprocessedPath = tempnam(sys_get_temp_dir(), 'receipt_') . '.png';
        $image->save($preprocessedPath);

        return $preprocessedPath;
    }



    // Process receipt items and update inventory
    private function processReceiptItems($receipt, $items)
    {
        foreach ($items as $itemData) {
            $productName = trim($itemData['name']);
            
            // Find product case-insensitively to prevent duplicates from OCR variations
            $product = Product::whereRaw('LOWER(name) = ?', [strtolower($productName)])->first();
            
            if (!$product) {
                $product = Product::create([
                    'name' => $productName,
                    'sku' => $this->generateSKU($productName),
                    'buy_price' => $itemData['unit_price'],
                    'sell_price' => $itemData['unit_price'] * 1.5, // 50% markup as example
                    'stock' => 0,
                    'category' => $itemData['category'] ?? null,
                ]);
            }

            // Update category if provided and different
            if (!empty($itemData['category']) && $product->category !== $itemData['category']) {
                $product->update(['category' => $itemData['category']]);
            }

            // Update price if it has changed
            if ($product->buy_price != $itemData['unit_price']) {
                $product->update([
                    'buy_price' => $itemData['unit_price'],
                    'sell_price' => $itemData['unit_price'] * 1.5, // maintain 50% markup rule
                ]);
            }

            $measure = isset($itemData['measure']) && $itemData['measure'] > 0 ? (float) $itemData['measure'] : 1;
            
            // Create receipt item
            ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $itemData['quantity'],
                'measure' => $measure,
                'unit_price' => $itemData['unit_price'],
                'subtotal' => $itemData['quantity'] * $itemData['unit_price'] * $measure,
            ]);

            // Update product stock and log movement based on receipt type
            if ($receipt->type == 'pembelian') {
                $product->increment('stock', $itemData['quantity']);
                $newBalance = $product->fresh()->stock;
                
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'receipt_id' => $receipt->id,
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'quantity' => $itemData['quantity'],
                    'balance' => $newBalance,
                    'notes' => 'Restock from Purchase Receipt #' . str_pad($receipt->id, 5, '0', STR_PAD_LEFT),
                ]);
            } else {
                $product->decrement('stock', $itemData['quantity']);
                $newBalance = $product->fresh()->stock;
                
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'receipt_id' => $receipt->id,
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'balance' => $newBalance,
                    'notes' => 'Sale to external store via Receipt #' . str_pad($receipt->id, 5, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }

    // Create debt record from validated receipt
    private function createDebtFromReceipt($receipt, $paymentStatus, $amountPaid = 0, $paymentMethod = 'Cash')
    {
        $paidAmount = 0;
        if ($paymentStatus === 'lunas') {
            $paidAmount = $receipt->total_amount;
        } elseif ($paymentStatus === 'partial') {
            $paidAmount = $amountPaid ?? 0;
        }

        $debt = Debt::create([
            'receipt_id' => $receipt->id,
            'store_id' => $receipt->store_id,
            'amount' => $receipt->total_amount,
            'paid_amount' => $paidAmount,
            'status' => $paymentStatus,
            'notes' => 'Created from receipt #' . $receipt->id,
        ]);

        if ($paidAmount > 0) {
            \App\Models\DebtPayment::create([
                'debt_id' => $debt->id,
                'amount_paid' => $paidAmount,
                'payment_date' => $receipt->transaction_date ?? now(),
                'payment_method' => $paymentMethod ?? 'Cash',
                'notes' => $paymentStatus === 'lunas' ? 'Paid in full on receipt validation' : 'Down payment (DP)',
            ]);
        }
    }

    // Generate SKU from product name
    private function generateSKU($name)
    {
        // Take first 3 letters of each word, uppercase
        $words = preg_split('/[\s\-_]+/', $name);
        $skuParts = array_map(function($word) {
            return strtoupper(substr($word, 0, min(3, strlen($word))));
        }, $words);

        $sku = implode('', $skuParts);

        // Ensure uniqueness by adding timestamp if needed
        $baseSku = $sku;
        $counter = 1;
        while (Product::where('sku', $sku)->exists()) {
            $sku = $baseSku . $counter;
            $counter++;
        }

        return $sku;
    }
}
