<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CleanupOrders extends Command
{
    /**
     * اسم الأمر اللي بيتنفذ في التيرمنال
     *
     * @var string
     */
    protected $signature = 'cleanup:orders';

    /**
     * وصف الأمر
     *
     * @var string
     */
    protected $description = 'Remove invalid orders (empty, unactive products, orphan pivots, or zero totalPrice) after confirmation';

    /**
     * تنفيذ الأمر
     */
    public function handle()
    {
        $this->info('🧹 Starting cleanup process check...');

        // إحصاء قبل التأكيد
        $emptyOrdersCount = Order::doesntHave('products')->count();
        $inactiveOrdersCount = Order::whereHas('products', fn($q) => $q->where('status', 'unactive'))->count();
        $zeroTotalCount = Order::where('totalPrice', 0)->count();
        $orphanPivotCount = DB::table('order_product')
            ->whereNotIn('order_id', Order::pluck('id'))
            ->count();

        if ($emptyOrdersCount === 0 && $inactiveOrdersCount === 0 && $orphanPivotCount === 0 && $zeroTotalCount === 0) {
            $this->info('✅ Database already clean — nothing to delete!');
            return;
        }

        // عرض النتائج
        $this->line("🔎 Found:");
        $this->line("- {$emptyOrdersCount} orders without products");
        $this->line("- {$inactiveOrdersCount} orders with unactive products");
        $this->line("- {$zeroTotalCount} orders with totalPrice = 0");
        $this->line("- {$orphanPivotCount} orphan pivot records");

        // تأكيد المستخدم قبل الحذف
        if (!$this->confirm('⚠️  Are you sure you want to delete these records?')) {
            $this->warn('❌ Cleanup cancelled.');
            return;
        }

        $this->info('🚀 Starting cleanup...');

        //  حذف الأوردرات اللي ملهاش منتجات
        if ($emptyOrdersCount > 0) {
            Order::doesntHave('products')->delete();
            $this->warn("🗑️ Deleted {$emptyOrdersCount} empty orders.");
        }

        //  حذف الأوردرات اللي فيها منتجات غير متاحة
        if ($inactiveOrdersCount > 0) {
            Order::whereHas('products', fn($q) => $q->where('status', 'unactive'))->delete();
            $this->warn("🗑️ Deleted {$inactiveOrdersCount} orders with unactive products.");
        }

        //  حذف الأوردرات اللي totalPrice = 0
        if ($zeroTotalCount > 0) {
            Order::where('totalPrice', 0)->delete();
            $this->warn("💸 Deleted {$zeroTotalCount} orders with totalPrice = 0.");
        }

        //  حذف السجلات اليتيمة من الجدول الوسيط
        if ($orphanPivotCount > 0) {
            DB::table('order_product')
                ->whereNotIn('order_id', Order::pluck('id'))
                ->delete();
            $this->warn("🧽 Deleted {$orphanPivotCount} orphan pivot records.");
        }

        $this->info('🎯 Cleanup complete! Database is now clean and healthy.');
    }
}
