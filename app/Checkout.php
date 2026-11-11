<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public function catalog()
    {
        return DB::table('variants')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->join('shops', 'shops.id', '=', 'products.shop_id')
            ->select('variants.*', 'products.name', 'products.kind', 'products.shop_id', 'shops.name as shop')
            ->orderBy('variants.id');
    }

    public function quote(array $cart, string $region): array
    {
        $variants = $this->catalog()->whereIn('variants.id', array_keys($cart))->get();
        $rates = DB::table('shipping_rates')->where('region', $region)->pluck('fee', 'shop_id');
        $groups = [];
        foreach ($variants as $variant) {
            $shop = $variant->shop_id;
            $groups[$shop] ??= ['name'=>$variant->shop, 'shipping'=>$rates[$shop], 'subtotal'=>0, 'items'=>[]];
            $quantity = $cart[$variant->id];
            $groups[$shop]['subtotal'] += $quantity * $variant->price;
            $groups[$shop]['items'][] = ['variant'=>$variant, 'quantity'=>$quantity];
        }
        return $groups;
    }

    public function place(array $cart, string $region, string $key, int $accountId): int
    {
        return DB::transaction(function () use ($cart, $region, $key, $accountId) {
            $existing = DB::table('orders')->where('checkout_key', $key)->first();
            if ($existing) {
                return $existing->id;
            }
            $groups = $this->quote($cart, $region);
            if (!$groups) {
                throw ValidationException::withMessages(['cart'=>'Giỏ hàng đang trống.']);
            }
            $total = array_sum(array_map(fn ($group) => $group['subtotal'] + $group['shipping'], $groups));
            $order = DB::table('orders')->insertGetId([
                'checkout_key'=>$key,'account_id'=>$accountId,'region'=>$region,'total'=>$total,'created_at'=>now(),'updated_at'=>now(),
            ]);
            ksort($cart, SORT_NUMERIC);
            foreach ($cart as $id => $quantity) {
                $updated = DB::table('variants')->where('id', $id)
                    ->where('stock', '>=', $quantity)->decrement('stock', $quantity);
                if (!$updated) {
                    throw ValidationException::withMessages(['cart'=>'Một mặt hàng không còn đủ tồn kho. Vui lòng chỉnh lại giỏ.']);
                }
            }
            foreach ($groups as $shop => $group) {
                $suborder = DB::table('shop_orders')->insertGetId([
                    'order_id'=>$order,'shop_id'=>$shop,'shop_name'=>$group['name'],
                    'shipping'=>$group['shipping'],'subtotal'=>$group['subtotal'],
                ]);
                foreach ($group['items'] as ['variant'=>$variant, 'quantity'=>$quantity]) {
                    DB::table('order_items')->insert([
                        'shop_order_id'=>$suborder,'variant_id'=>$variant->id,
                        'name'=>$variant->name.' · '.$variant->label,'price'=>$variant->price,'quantity'=>$quantity,
                    ]);
                }
            }
            return $order;
        }, 3);
    }
}
