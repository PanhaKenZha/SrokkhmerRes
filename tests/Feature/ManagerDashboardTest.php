<?php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp();
        $this->actingAs(User::factory()->create(['email'=>config('restaurant.staff_email')]));
    }
    private function order(array $attributes=[]): Order {
        return Order::create(array_merge(['reference'=>(string)Str::uuid(),'customer'=>'Sokha','phone'=>'012345678','service'=>'Takeaway','status'=>'New','items'=>[['name'=>'Fish amok','price'=>650,'quantity'=>1]],'total'=>650],$attributes));
    }
    public function test_sales_only_include_fulfilled_orders_and_metrics_ignore_queue_filters(): void {
        $this->order(['status'=>'Completed','total'=>1000]);
        $this->order(['status'=>'Delivered','service'=>'Delivery','total'=>1500]);
        $this->order(['status'=>'Cancelled','total'=>9000]);
        $this->order(['status'=>'Preparing','service'=>'Delivery','total'=>600]);
        $this->order(['status'=>'New','total'=>400]);
        $response=$this->get('/staff?status=New')->assertOk();
        $response->assertViewHas('stats',fn($stats)=>$stats['sales']===2500&&$stats['orders']===5&&$stats['active']===2&&$stats['deliveries']===1&&$stats['completed']===2);
        $response->assertViewHas('orders',fn($orders)=>$orders->total()===1&&$orders->first()->status==='New');
        $response->assertViewHas('chart',fn($chart)=>$chart->count()===7&&$chart->sum('value')===2500);
    }
    public function test_date_service_status_and_customer_filters_combine(): void {
        $target=$this->order(['customer'=>'Dara','service'=>'Delivery','status'=>'Ready']);
        $this->order(['customer'=>'Other guest','service'=>'Delivery','status'=>'Delivered']);
        $this->order(['customer'=>'Dara','service'=>'Takeaway','status'=>'Ready']);
        $this->order(['customer'=>'Dara','service'=>'Delivery','status'=>'Ready','created_at'=>now()->subDays(3)]);
        $this->get('/staff?period=today&status=Active&service=Delivery&q=Dara')->assertOk()->assertViewHas('orders',fn($orders)=>$orders->total()===1&&$orders->first()->id===$target->id);
        $this->get('/staff?period=week&status=Active&service=Delivery&q=Dara')->assertOk()->assertViewHas('orders',fn($orders)=>$orders->total()===2);
        $this->get('/staff?period=all&q=%23'.$target->id)->assertOk()->assertViewHas('orders',fn($orders)=>$orders->total()===1);
        $this->get('/staff?status=NotAStatus')->assertSessionHasErrors('status');
    }
    public function test_pagination_preserves_filters_and_empty_search_has_clear_message(): void {
        foreach(range(1,13) as $index) $this->order(['customer'=>'Guest '.$index]);
        $this->get('/staff?period=all&status=New')->assertOk()->assertViewHas('orders',fn($orders)=>$orders->total()===13&&$orders->count()===12&&str_contains($orders->nextPageUrl(),'status=New'));
        $this->get('/staff?period=all&status=New&page=2')->assertOk()->assertViewHas('orders',fn($orders)=>$orders->count()===1);
        $this->get('/staff?q=Nobody')->assertOk()->assertSee('No orders match these filters.');
    }
}
