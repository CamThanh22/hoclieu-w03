<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment('local'), 403);
        foreach (['buyer'=>['buyer',null],'shop1'=>['vendor',1],'shop2'=>['vendor',2],'admin'=>['admin',null]] as $name => [$role,$shop]) {
            DB::table('accounts')->insertOrIgnore([
                'email'=>$name.'@hocphan.test','password'=>Hash::make('HocPhan-W03-demo'),
                'role'=>$role,'shop_id'=>$shop,
            ]);
        }
    }
}
