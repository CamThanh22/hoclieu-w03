<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->foreignId('shop_id')->constrained();
            $table->string('region');
            $table->unsignedInteger('fee');
            $table->primary(['shop_id', 'region']);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained();
            $table->string('name');
            $table->string('course');
            $table->string('kind');
        });
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('sku')->unique();
            $table->string('label');
            $table->unsignedInteger('price');
            $table->unsignedInteger('stock');
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('checkout_key')->unique();
            $table->string('region');
            $table->unsignedBigInteger('total');
            $table->timestamps();
        });
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('shop_id')->constrained();
            $table->string('shop_name');
            $table->unsignedInteger('shipping');
            $table->unsignedBigInteger('subtotal');
            $table->string('status')->default('placed_demo');
            $table->unique(['order_id', 'shop_id']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_id')->constrained();
            $table->foreignId('variant_id')->constrained();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
        });
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->timestamps();
        });

        DB::table('shops')->insert([['id'=>1,'name'=>'Nhà sách Hòa Khánh'],['id'=>2,'name'=>'Góc Học Cụ']]);
        foreach ([1 => [15000, 28000], 2 => [12000, 26000]] as $shop => [$local, $other]) {
            DB::table('shipping_rates')->insert([
                ['shop_id'=>$shop,'region'=>'danang','fee'=>$local],
                ['shop_id'=>$shop,'region'=>'other','fee'=>$other],
            ]);
        }
        foreach ([
            [1,'Giáo trình Toán rời rạc','Giáo trình',79000],
            [1,'Bài tập Toán rời rạc','Tham khảo',59000],
            [2,'Vở kẻ ô ghi lời giải','Học cụ',24000],
            [2,'Bộ thẻ ôn tập','Học cụ',32000],
        ] as $index => [$shop,$name,$kind,$price]) {
            $product = DB::table('products')->insertGetId([
                'shop_id'=>$shop,'name'=>$name,'kind'=>$kind,'course'=>'Toán rời rạc',
            ]);
            foreach ([$shop === 1 ? 'Bản mới' : 'Tiêu chuẩn', $shop === 1 ? 'Bản cũ' : 'Bộ lớn'] as $i => $label) {
                DB::table('variants')->insert([
                    'product_id'=>$product,'sku'=>'TRR-'.($index+1).'-'.($i+1),
                    'label'=>$label,'price'=>$i ? $price + ($shop === 1 ? -18000 : 12000) : $price,'stock'=>10,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['discussions','order_items','shop_orders','orders','variants','products','shipping_rates','shops'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
