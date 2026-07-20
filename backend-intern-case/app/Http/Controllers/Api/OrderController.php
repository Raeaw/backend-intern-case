<?php
// app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')->latest()->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load('items.product');

        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $productIds = collect($validated['items'])->pluck('product_id');

            $products = Product::whereIn('id', $productIds)
                ->active()
                ->get()
                ->keyBy('id');

            // Pastikan semua produk yang diminta memang berstatus active
            foreach ($validated['items'] as $item) {
                if (! $products->has($item['product_id'])) {
                    throw ValidationException::withMessages([
                        'items' => ["Produk dengan ID {$item['product_id']} tidak tersedia atau tidak aktif."],
                    ]);
                }
            }

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $totalPrice = 0;

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);
                $subtotal = $product->price * $item['qty'];
                $totalPrice += $subtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_price' => $totalPrice]);

            return $order;
        });

        $order->load('items.product');

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
