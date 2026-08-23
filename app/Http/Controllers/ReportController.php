<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Filtro de Fechas (Default: Inicio de mes hasta hoy)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // 1. VENTAS POR CATEGORÍA (Gráfico de Dona)
        $salesByCategory = DB::table('rest_order_details')
            ->join('rest_orders', 'rest_order_details.order_id', '=', 'rest_orders.id')
            ->join('rest_products', 'rest_order_details.product_id', '=', 'rest_products.id')
            ->join('rest_categories', 'rest_products.category_id', '=', 'rest_categories.id')
            ->select('rest_categories.name', DB::raw('SUM(rest_order_details.quantity * rest_order_details.price) as total'))
            ->where('rest_orders.status', 'completed')
            ->whereDate('rest_orders.created_at', '>=', $startDate)
            ->whereDate('rest_orders.created_at', '<=', $endDate)
            ->groupBy('rest_categories.name')
            ->get();

        $catLabels = $salesByCategory->pluck('name');
        $catValues = $salesByCategory->pluck('total');

        // 2. RENDIMIENTO DE PERSONAL (Gráfico de Barras)
        $salesByWaiter = Order::select('rest_users.name', DB::raw('SUM(rest_orders.total) as total_sales'), DB::raw('COUNT(rest_orders.id) as orders_count'))
            ->join('rest_users', 'rest_orders.user_id', '=', 'rest_users.id')
            ->where('rest_orders.status', 'completed')
            ->whereDate('rest_orders.created_at', '>=', $startDate)
            ->whereDate('rest_orders.created_at', '<=', $endDate)
            ->groupBy('rest_users.name')
            ->orderByDesc('total_sales')
            ->get();

        $waiterLabels = $salesByWaiter->pluck('name');
        $waiterValues = $salesByWaiter->pluck('total_sales');

        // 3. TOP 5 PLATOS MÁS VENDIDOS
        $topProducts = DB::table('rest_order_details')
            ->join('rest_products', 'rest_order_details.product_id', '=', 'rest_products.id')
            ->join('rest_orders', 'rest_order_details.order_id', '=', 'rest_orders.id')
            ->select('rest_products.name', DB::raw('SUM(rest_order_details.quantity) as qty'), DB::raw('SUM(rest_order_details.quantity * rest_order_details.price) as revenue'))
            ->where('rest_orders.status', 'completed')
            ->whereDate('rest_orders.created_at', '>=', $startDate)
            ->whereDate('rest_orders.created_at', '<=', $endDate)
            ->groupBy('rest_products.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        // 4. TOP 5 PLATOS MENOS VENDIDOS (Para tomar acción)
        $worstProducts = DB::table('rest_order_details')
            ->join('rest_products', 'rest_order_details.product_id', '=', 'rest_products.id')
            ->join('rest_orders', 'rest_order_details.order_id', '=', 'rest_orders.id')
            ->select('rest_products.name', DB::raw('SUM(rest_order_details.quantity) as qty'))
            ->where('rest_orders.status', 'completed')
            ->whereDate('rest_orders.created_at', '>=', $startDate)
            ->whereDate('rest_orders.created_at', '<=', $endDate)
            ->groupBy('rest_products.name')
            ->orderBy('qty', 'asc')
            ->limit(5)
            ->get();

        // Moneda
        $currency = \App\Models\Setting::where('key', 'currency_symbol')->value('value') ?? 'S/';

        return view('reports.index', compact(
            'startDate', 'endDate', 
            'catLabels', 'catValues', 
            'waiterLabels', 'waiterValues',
            'topProducts', 'worstProducts', 'salesByWaiter', 'currency'
        ));
    }
}