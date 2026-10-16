<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gadget;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Everything the admin dashboard shows, in one request.
 */
class DashboardController extends Controller
{
    /** Gadgets at or below this many units show up in "Low Stock". */
    private const LOW_STOCK_THRESHOLD = 5;

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'revenue' => (float) Purchase::active()->sum(DB::raw('quantity * unit_price')),
                'total_purchases' => Purchase::active()->count(),
                'pending_reviews' => Review::pending()->count(),
                'total_gadgets' => Gadget::active()->count(),
                'total_customers' => User::where('role', 'customer')->count(),
                'average_rating' => round((float) Review::approved()->avg('rating'), 1),
            ],
            'top_selling' => $this->topSelling(),
            'monthly_sales' => $this->monthlySales(),
            'low_stock' => $this->lowStock(),
        ]);
    }

    /**
     * The 5 gadgets with the most units sold.
     */
    private function topSelling(): array
    {
        return Gadget::select('id', 'brand', 'gadget_name')
            ->withSum(['purchases as total_sold' => fn (Builder $q) => $q->where('archived', false)], 'quantity')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->filter(fn (Gadget $gadget) => $gadget->total_sold > 0)
            ->map(fn (Gadget $gadget) => [
                'id' => $gadget->id,
                'brand' => $gadget->brand,
                'gadget_name' => $gadget->gadget_name,
                'total_sold' => (int) $gadget->total_sold,
            ])
            ->values()
            ->all();
    }

    /**
     * Units and revenue per month, for the 6 most recent months with sales.
     */
    private function monthlySales(): array
    {
        return Purchase::active()
            ->selectRaw("DATE_FORMAT(purchase_date, '%Y-%m') as month")
            ->selectRaw('SUM(quantity) as total_qty')
            ->selectRaw('SUM(quantity * unit_price) as revenue')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(6)
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'month' => $row->month,
                'total_qty' => (int) $row->total_qty,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    private function lowStock(): array
    {
        return Inventory::with('gadget:id,brand,gadget_name')
            ->where('quantity', '<=', self::LOW_STOCK_THRESHOLD)
            ->whereHas('gadget', fn (Builder $q) => $q->where('archived', false))
            ->orderBy('quantity')
            ->get()
            ->map(fn (Inventory $inventory) => [
                'id' => $inventory->gadget->id,
                'brand' => $inventory->gadget->brand,
                'gadget_name' => $inventory->gadget->gadget_name,
                'stock' => $inventory->quantity,
            ])
            ->all();
    }
}
