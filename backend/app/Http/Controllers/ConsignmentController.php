<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\Agent;
use App\Models\SalesItem;
use Illuminate\Support\Str;
use App\Models\Consignment;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Models\ConsignmentItem;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ConsignmentReconciliation;

class ConsignmentController extends Controller
{
    /**
     * List consignments, optionally filtered by agent or status.
     */
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'agent_id' => 'nullable|exists:agents,id',
                'status' => 'nullable|in:PENDING_APPROVAL,APPROVED,PARTIALLY_RECONCILED,RECONCILED,REJECTED',
            ]);

            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            $query = Consignment::with(['agent', 'items.product', 'items.brand', 'items.measurement']);

            if (!empty($validated['agent_id'])) {
                $query->where('agent_id', $validated['agent_id']);
            }

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $consignments = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($consignments);
        } catch (\Exception $e) {
            Log::error('Error listing consignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Show a single consignment with full item + reconciliation history.
     */
    public function show($id)
    {
        $consignment = Consignment::with([
            'agent',
            'items.product',
            'items.brand',
            'items.measurement',
            'items.reconciliations',
        ])->find($id);

        if (!$consignment) {
            return response()->json([
                'success' => false,
                'message' => 'Consignment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $consignment,
        ]);
    }

    /**
     * Step 1: Request a consignment (issue request).
     * Status starts as PENDING_APPROVAL. Stock is NOT touched yet.
     * We do check stock is sufficient at request time, as an early sanity check,
     * but the authoritative check happens again at approval time.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'agent_id' => 'required|exists:agents,id',
                'notes' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.brand_id' => 'required|exists:brands,id',
                'items.*.measurement_id' => 'required|exists:measurements,id',
                'items.*.batch_number' => 'required|string',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.quantity_issued' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            do {
                $uuid = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 14));
            } while (str_starts_with($uuid, '0'));
            $consignmentNumber = 'CN' . $uuid;

            $consignment = Consignment::create([
                'consignment_number' => $consignmentNumber,
                'agent_id' => $validated['agent_id'],
                'status' => 'PENDING_APPROVAL',
                'requested_date' => now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                // Early sanity check — not authoritative, re-checked at approval
                $stock = StockBalance::where('product_id', $item['product_id'])
                    ->where('brand_id', $item['brand_id'])
                    ->where('measurement_id', $item['measurement_id'])
                    ->where('batch_number', $item['batch_number'])
                    ->first();

                if (!$stock || $stock->balance < $item['quantity_issued']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for one of the selected items (batch ' . $item['batch_number'] . ').',
                    ], 422);
                }

                ConsignmentItem::create([
                    'consignment_id' => $consignment->id,
                    'product_id' => $item['product_id'],
                    'brand_id' => $item['brand_id'],
                    'measurement_id' => $item['measurement_id'],
                    'batch_number' => $item['batch_number'],
                    'unit_price' => $item['unit_price'],
                    'quantity_issued' => $item['quantity_issued'],
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consignment requested successfully. Awaiting approval.',
                'data' => $consignment->load('items'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating consignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the consignment.',
            ], 500);
        }
    }

    /**
     * Step 2a: Approve a pending consignment.
     * This is the point stock actually leaves the shop — balances are decremented here,
     * with a fresh stock-sufficiency check (stock may have moved since the request was made).
     */
    public function approve(Request $request, $id)
    {
        try {
            $consignment = Consignment::with('items')->find($id);

            if (!$consignment) {
                return response()->json(['success' => false, 'message' => 'Consignment not found'], 404);
            }

            if ($consignment->status !== 'PENDING_APPROVAL') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending consignments can be approved. Current status: ' . $consignment->status,
                ], 422);
            }

            DB::beginTransaction();

            foreach ($consignment->items as $item) {
                $stock = StockBalance::where('product_id', $item->product_id)
                    ->where('brand_id', $item->brand_id)
                    ->where('measurement_id', $item->measurement_id)
                    ->where('batch_number', $item->batch_number)
                    ->first();

                if (!$stock || $stock->balance < $item->quantity_issued) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock to approve this consignment (batch ' . $item->batch_number . '). Stock may have changed since the request was made.',
                    ], 422);
                }

                $stock->decrement('balance', $item->quantity_issued);
            }

            $consignment->update([
                'status' => 'APPROVED',
                'approved_date' => now()->toDateString(),
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consignment approved. Stock issued to agent.',
                'data' => $consignment->fresh('items'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving consignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the consignment.',
            ], 500);
        }
    }

    /**
     * Step 2b: Reject a pending consignment. Stock was never touched, nothing to undo.
     */
    public function reject(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:255',
            ]);

            $consignment = Consignment::find($id);

            if (!$consignment) {
                return response()->json(['success' => false, 'message' => 'Consignment not found'], 404);
            }

            if ($consignment->status !== 'PENDING_APPROVAL') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending consignments can be rejected. Current status: ' . $consignment->status,
                ], 422);
            }

            $consignment->update([
                'status' => 'REJECTED',
                'rejection_reason' => $validated['rejection_reason'],
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consignment rejected.',
                'data' => $consignment,
            ]);
        } catch (\Exception $e) {
            Log::error('Error rejecting consignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the consignment.',
            ], 500);
        }
    }

    /**
     * Step 3: Reconcile — can be called multiple times per consignment (partial check-ins).
     * For each line: validates sold+returned+lost doesn't exceed what's still outstanding,
     * records a reconciliation row, updates running totals, creates a real sale for the
     * sold portion, and returns the returned portion to stock.
     */
    public function reconcile(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.consignment_item_id' => 'required|exists:consignment_items,id',
                'items.*.quantity_sold' => 'required|integer|min:0',
                'items.*.quantity_returned' => 'required|integer|min:0',
                'items.*.quantity_lost' => 'required|integer|min:0',
                'items.*.cash_collected' => 'required|numeric|min:0',
            ]);

            $consignment = Consignment::with('items')->find($id);

            if (!$consignment) {
                return response()->json(['success' => false, 'message' => 'Consignment not found'], 404);
            }

            if (!in_array($consignment->status, ['APPROVED', 'PARTIALLY_RECONCILED'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This consignment cannot be reconciled in its current status: ' . $consignment->status,
                ], 422);
            }

            DB::beginTransaction();

            // Group this reconciliation's sold items into one sale for the agent,
            // so it shows up as a single transaction in sales reports.
            $saleItemsToCreate = [];
            $totalCashForSale = 0;

            foreach ($validated['items'] as $line) {
                $consignmentItem = ConsignmentItem::where('id', $line['consignment_item_id'])
                    ->where('consignment_id', $consignment->id)
                    ->lockForUpdate()
                    ->first();

                if (!$consignmentItem) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'One of the items does not belong to this consignment.',
                    ], 422);
                }

                $outstanding = $consignmentItem->quantity_issued
                    - $consignmentItem->quantity_sold
                    - $consignmentItem->quantity_returned
                    - $consignmentItem->quantity_lost;

                $requestedTotal = $line['quantity_sold'] + $line['quantity_returned'] + $line['quantity_lost'];

                if ($requestedTotal > $outstanding) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Reconciliation for batch {$consignmentItem->batch_number} exceeds what's outstanding ({$outstanding} remaining, {$requestedTotal} submitted).",
                    ], 422);
                }

                // Record this check-in
                ConsignmentReconciliation::create([
                    'consignment_item_id' => $consignmentItem->id,
                    'quantity_sold' => $line['quantity_sold'],
                    'quantity_returned' => $line['quantity_returned'],
                    'quantity_lost' => $line['quantity_lost'],
                    'cash_collected' => $line['cash_collected'],
                    'reconciled_date' => now()->toDateString(),
                    'created_by' => auth()->id(),
                ]);

                // Update running totals
                $consignmentItem->increment('quantity_sold', $line['quantity_sold']);
                $consignmentItem->increment('quantity_returned', $line['quantity_returned']);
                $consignmentItem->increment('quantity_lost', $line['quantity_lost']);

                // Returned stock goes back onto the shelf
                if ($line['quantity_returned'] > 0) {
                    $stock = StockBalance::where('product_id', $consignmentItem->product_id)
                        ->where('brand_id', $consignmentItem->brand_id)
                        ->where('measurement_id', $consignmentItem->measurement_id)
                        ->where('batch_number', $consignmentItem->batch_number)
                        ->first();

                    if ($stock) {
                        $stock->increment('balance', $line['quantity_returned']);
                    }
                }

                // Sold stock becomes a real sale line
                if ($line['quantity_sold'] > 0) {
                    $saleItemsToCreate[] = [
                        'product_id' => $consignmentItem->product_id,
                        'brand_id' => $consignmentItem->brand_id,
                        'measurement_id' => $consignmentItem->measurement_id,
                        'batch_number' => $consignmentItem->batch_number,
                        'quantity' => $line['quantity_sold'],
                        'unit_price' => $consignmentItem->unit_price,
                        'total_price' => $line['quantity_sold'] * $consignmentItem->unit_price,
                    ];
                }

                $totalCashForSale += $line['cash_collected'];
            }

            // Create one sale record for everything sold in this reconciliation visit
            if (!empty($saleItemsToCreate)) {
                do {
                    $uuid = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 14));
                } while (str_starts_with($uuid, '0'));
                $batchNumber = 'SN' . $uuid;

                $totalAmount = collect($saleItemsToCreate)->sum('total_price');

                $sale = Sales::create([
                    'agent_id' => $consignment->agent_id,
                    'customer_name' => $consignment->agent->name ?? 'Agent',
                    'customer_phone' => $consignment->agent->phone_number ?? null,
                    'batch_number' => $batchNumber,
                    'total_amount' => $totalAmount,
                    'discount' => 0,
                    'payment_method' => 'CASH',
                    'notice' => 'Consignment reconciliation — ' . $consignment->consignment_number,
                    'created_by' => auth()->id(),
                ]);

                foreach ($saleItemsToCreate as $saleItem) {
                    SalesItem::create(array_merge($saleItem, [
                        'sale_id' => $sale->id,
                        'created_by' => auth()->id(),
                    ]));
                }
            }

            // Recompute consignment-level status from fresh item totals
            $consignment->refresh();
            $allAccountedFor = $consignment->items->every(function ($item) {
                return ($item->quantity_issued - $item->quantity_sold - $item->quantity_returned - $item->quantity_lost) <= 0;
            });

            $consignment->update([
                'status' => $allAccountedFor ? 'RECONCILED' : 'PARTIALLY_RECONCILED',
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation recorded successfully.',
                'data' => $consignment->fresh(['items.reconciliations']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reconciling consignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reconciling the consignment.',
            ], 500);
        }
    }

    /**
     * Report: everything currently outstanding with agents, grouped by agent.
     */
    public function outstanding(Request $request)
    {
        try {
            $items = ConsignmentItem::with(['product', 'brand', 'measurement', 'consignment.agent'])
                ->whereHas('consignment', function ($q) {
                    $q->whereIn('status', ['APPROVED', 'PARTIALLY_RECONCILED']);
                })
                ->get()
                ->map(function ($item) {
                    $item->quantity_outstanding = $item->quantity_issued
                        - $item->quantity_sold
                        - $item->quantity_returned
                        - $item->quantity_lost;
                    return $item;
                })
                ->filter(fn($item) => $item->quantity_outstanding > 0)
                ->values();

            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Exception $e) {
            Log::error('Error fetching outstanding consignments: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }
}
