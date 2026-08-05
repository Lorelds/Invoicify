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
            'receipt_images' => $request->action === 'scan' ? 'required|array|min:1' : 'nullable|array',
            'receipt_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'store_id' => 'nullable|exists:stores,id',
            'type' => 'required|in:pembelian,penjualan',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $uploadedReceipts = [];
        $apiErrors = [];
        
        // Handle fully manual entry with no image uploaded
        if ($request->action === 'manual' && !$request->hasFile('receipt_images')) {
            $receipt = Receipt::create([
                'store_id' => $request->store_id,
                'image_path' => null,
                'raw_text' => '{}',
                'receipt_number' => null,
                'store_name' => null,
                'transaction_date' => date('Y-m-d'),
                'total_amount' => 0,
                'status' => 'pending',
                'type' => $request->type,
                'payment_status' => 'hutang',
            ]);
            $uploadedReceipts[] = $receipt;
        } else {
            $apiKey = config('services.gemini.key');
            
            foreach ($request->file('receipt_images') as $file) {
                // Store the uploaded image
                $imagePath = $file->store('receipts', 'public');
                $fullImagePath = Storage::disk('public')->path($imagePath);

                $rawText = "{}";
                $parsedData = [
                    'receipt_number' => null,
                    'store_name' => null,
                    'transaction_date' => null,
                    'total_amount' => 0,
                    'items' => []
                ];
                
                // Only run AI if the action is scan
                if ($request->action === 'scan') {
                    $base64Image = base64_encode(file_get_contents($fullImagePath));
                    $mimeType = mime_content_type($fullImagePath);

                    $prompt = "Extract this Indonesian hardware store receipt into JSON.
            FIELDS TO EXTRACT:
            - 'receipt_number' (string)
            - 'store_name' (string)
            - 'transaction_date' (YYYY-MM-DD)
            - 'total_amount' (number)
            - 'items' (array of objects):
              * 'name' (string). EXACT name on receipt. 
                 RULE: If the name contains the standalone letter 'R' (e.g., 'Paku R', 'Paku 5 R'), change 'R' to 'Raja' (e.g., 'Paku Raja', 'Paku 5 Raja'). 
                 NEGATIVE RULE: If it does NOT contain 'R' (e.g., 'Paku 5', 'Paku'), leave it EXACTLY as 'Paku 5' or 'Paku'. Do NOT add 'Raja'.
                 RULE: If the name contains 'GW' (e.g., 'GW 030'), change 'GW' to 'GLV' (e.g., 'GLV 030').
                 RULE: If the name contains 'Bendrat' or '@20' or '@25' or 'C 20g', you MUST format the name EXACTLY as 'Bendrat @ 20 kg' or 'Bendrat @ 25 kg' depending on the number. NEVER leave it as just '@20g'.
              * 'category' (string). Guess (e.g., Paku, Kayu, Besi, Semen, Cat, Pipa, Kawat).
              * 'quantity' (number). Raw number from the quantity column.
              * 'measure' (number). YOU MUST LOOK AT THE NAME AND SET THIS NUMBER:
                 - 'Paku Seng': 20
                 - 'Paku' (but not Seng): 30
                 - 'Begel' or 'Cornice': 20
                 - 'GLV': ALWAYS 50 (ignore other numbers in name)
                 - 'Karpet' or 'Seng': 50
                 - 'Kawat' (size 8, 10, 12, 14, 16): 50
                 - 'Kawat' (size 18, 20): 25
                 - 'Bendrat': STRICTLY 20 (if name has 20) or 25 (if name has 25).
                 - 'Fiber Gel': Extract meters from cm (e.g., 180=1.8, 210=2.1, 240=2.4, 300=3)
                 - 'Board adimas', 'Shica', 'Bondex', 'UPVC', 'PVC', 'Spandek': Extract the length number from the name (e.g., 3m = 3, 4m = 4)
                 - 'Hollow', 'Kanal', 'Nok', 'Wuwung', 'Talang Kotak', 'Wermes', 'Pagar', 'Stall', 'Semen', 'Genteng', 'Asbes', 'Gelombang', 'Gel', 'Reng', 'Lisplang': ALWAYS 1 (ignore length/kg in name)
                 - ALL OTHER ITEMS: if it has length (e.g., '3m', '4m') extract the number, otherwise 1
              * 'subtotal' (number). The total price for this row. IMPORTANT: Remove all dots and commas (e.g., '15.000' becomes 15000).
              * 'unit_price' (number). The written price per item. Remove dots/commas.
                 CRITICAL RULE FOR BRACKETS \"}\" : If multiple items share ONE price via a bracket, YOU MUST COPY THAT EXACT UNIT PRICE TO EVERY SINGLE ITEM IN THE BRACKET! Never leave it 0.

            Return ONLY valid JSON. No markdown, no explanations.";

                    $response = Http::timeout(120)->connectTimeout(30)->withHeaders([
                        'Content-Type' => 'application/json',
                    ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=' . $apiKey, [
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

                    if ($response->successful()) {
                        $responseData = $response->json();
                        $content = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                        
                        // Clean up any markdown code blocks
                        $content = preg_replace('/```json\s*/', '', $content);
                        $content = preg_replace('/```\s*/', '', $content);
                        
                        $aiData = json_decode($content, true);
                        if (is_array($aiData)) {
                            $parsedData = array_merge($parsedData, $aiData);
                            $rawText = json_encode($parsedData, JSON_PRETTY_PRINT);
                            
                            session()->put('ai_receipt_items_' . $imagePath, $parsedData['items'] ?? []);
                        }
                    } else {
                        $errorBody = $response->json();
                        $apiError = $errorBody['error']['message'] ?? 'The AI model is currently unavailable or busy. Please enter items manually.';
                        \Log::error('Gemini API Error: ' . $response->body());
                        $apiErrors[] = $apiError;
                    }
                }

                $calculatedTotal = 0;
                if (!empty($parsedData['items']) && is_array($parsedData['items'])) {
                    foreach ($parsedData['items'] as &$item) {
                        $qty = isset($item['quantity']) && $item['quantity'] > 0 ? (float)$item['quantity'] : 1;
                        $measure = isset($item['measure']) && $item['measure'] > 0 ? (float)$item['measure'] : 1;
                        $price = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                        $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : 0;

                        if ($subtotal > 0 && $price == 0) {
                             // AI only extracted subtotal, we calculate price
                             $price = $subtotal / ($qty * $measure);
                             $item['unit_price'] = round($price, 2);
                        } else {
                             // Force subtotal to be mathematically perfect based on the unit price.
                             // This fixes bracket issues where AI gives the grand total of the bracket to one row.
                             $item['subtotal'] = round($qty * $measure * $price, 2);
                        }

                        $calculatedTotal += ($qty * $measure * $price);
                    }
                    unset($item);
                    
                    // Re-encode rawText and update session with mathematically perfect items
                    if ($request->action === 'scan') {
                        $rawText = json_encode($parsedData, JSON_PRETTY_PRINT);
                        session()->put('ai_receipt_items_' . $imagePath, $parsedData['items']);
                    }
                }
                $totalAmount = $calculatedTotal > 0 ? $calculatedTotal : ($parsedData['total_amount'] ?? 0.00);

                // Determine the correct Store ID dynamically based on AI's extraction if not manually selected
                $storeId = $request->store_id;
                
                if (empty($storeId) && !empty($parsedData['store_name'])) {
                    $scannedStoreName = trim($parsedData['store_name']);
                    $allStores = \App\Models\Store::all();
                    $bestMatch = null;
                    $highestSimilarity = 0;
                    
                    foreach ($allStores as $s) {
                        $storeNameDb = strtolower($s->name);
                        $storeNameScan = strtolower($scannedStoreName);
                        
                        // 1. Direct or partial match
                        if ($storeNameDb === $storeNameScan || strpos($storeNameDb, $storeNameScan) !== false || strpos($storeNameScan, $storeNameDb) !== false) {
                            $bestMatch = $s;
                            $highestSimilarity = 100;
                            break;
                        }
                        
                        // 2. Fuzzy match to handle typos
                        similar_text($storeNameDb, $storeNameScan, $percent);
                        if ($percent > $highestSimilarity) {
                            $highestSimilarity = $percent;
                            $bestMatch = $s;
                        }
                    }
                    
                    // If similarity is above 75%, it's highly likely a match with a typo
                    if ($bestMatch && $highestSimilarity >= 75) {
                        $storeId = $bestMatch->id;
                        $parsedData['store_name'] = $bestMatch->name; // Normalize the parsed data name
                    } else {
                        // Create a new store if it's completely unmatched
                        $newStore = \App\Models\Store::create([
                            'name' => $scannedStoreName
                        ]);
                        $storeId = $newStore->id;
                    }
                    
                    // Update rawText with normalized name if matched
                    $rawText = json_encode($parsedData, JSON_PRETTY_PRINT);
                }

                // Create receipt record
                $receipt = Receipt::create([
                    'store_id' => $storeId,
                    'image_path' => $imagePath,
                    'raw_text' => $rawText,
                    'receipt_number' => $parsedData['receipt_number'] ?? null,
                    'store_name' => $parsedData['store_name'] ?? null,
                    'transaction_date' => $parsedData['transaction_date'] ?? date('Y-m-d'),
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'type' => $request->type,
                    'payment_status' => 'hutang',
                ]);
                
                $uploadedReceipts[] = $receipt;
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'receipt_id' => count($uploadedReceipts) === 1 ? $uploadedReceipts[0]->id : null,
                'api_errors' => $apiErrors
            ]);
        }

        if (count($uploadedReceipts) === 1) {
            $receipt = $uploadedReceipts[0];
            if (!empty($apiErrors)) {
                return redirect()->route('admin.receipts.validate', $receipt->id)
                    ->withErrors(['ai_error' => 'AI Extraction Failed: ' . $apiErrors[0]]);
            }
            return redirect()->route('admin.receipts.validate', $receipt->id)
                ->with('success', __('Receipt uploaded successfully. Please validate the extracted data.'));
        }

        // Multiple receipts uploaded
        $message = count($uploadedReceipts) . ' ' . __('receipts uploaded successfully.');
        if (!empty($apiErrors)) {
            $message .= ' ' . __('However, some encountered AI extraction errors.');
        }

        return redirect()->route('admin.receipts.index')
            ->with('success', $message . ' ' . __('Please validate them from the list below.'));
    }

    // Show validation form
    public function validate($id)
    {
        $receipt = Receipt::with('store')->findOrFail($id);
        $stores = Store::all();
        $categories = Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');
        $productNames = Product::select('name')->distinct()->pluck('name');
        
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

        return view('admin.receipts.validate', compact('receipt', 'stores', 'categories', 'productNames', 'parsedData'));
    }

    // Handle validation and save data
    public function validateSubmit(Request $request, $id)
    {
        $receipt = Receipt::findOrFail($id);

        if ($request->action === 'draft') {
            $storeId = $request->store_id;
            if ($request->filled('new_store')) {
                $store = Store::firstOrCreate(['name' => $request->new_store]);
                $storeId = $store->id;
            }
            
            $draftData = [
                'receipt_number' => $request->receipt_number,
                'store_name' => $storeId ? Store::find($storeId)->name : $request->new_store,
                'transaction_date' => $request->transaction_date,
                'total_amount' => $request->total_amount,
                'items' => $request->items ?? []
            ];
            
            $receipt->update([
                'raw_text' => json_encode($draftData),
                'store_id' => $storeId,
                'receipt_number' => $request->receipt_number,
                'transaction_date' => $request->transaction_date,
                'total_amount' => $request->total_amount ?? 0,
            ]);
            
            session()->forget('ai_receipt_items_' . $receipt->image_path);
            
            return redirect()->route('admin.receipts.index')
                ->with('success', __('Draft saved successfully. You can resume validation later.'));
        }

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

    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);
        
        $receipt = Receipt::findOrFail($id);
        
        $imagePath = $request->file('receipt_image')->store('receipts', 'public');
        
        $receipt->update([
            'image_path' => $imagePath
        ]);
        
        return redirect()->route('admin.receipts.validate', $receipt->id)
            ->with('success', __('Gambar berhasil ditambahkan.'));
    }

    public function destroy(\App\Models\Receipt $receipt)
    {
        // 1 & 2: Revert Inventory and Delete Debt only if the receipt was validated
        if ($receipt->status === 'validated') {
            foreach ($receipt->items as $item) {
                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    // Revert the stock based on receipt type
                    if ($receipt->type === 'pembelian') {
                        // Originally added stock, so we remove it
                        $product->stock -= $item->quantity;
                    } else {
                        // Originally removed stock (sale), so we add it back
                        $product->stock += $item->quantity;
                    }

                    // Ensure stock doesn't go below 0
                    if ($product->stock < 0) {
                        $product->stock = 0;
                    }
                    $product->save();

                    // Log this reversal in the Stock History
                    \App\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => $receipt->type === 'pembelian' ? 'out' : 'in',
                        'quantity' => $item->quantity,
                        'balance' => $product->stock,
                        'notes' => 'Stock reverted due to deleted Receipt #' . $receipt->receipt_number,
                    ]);
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
}
