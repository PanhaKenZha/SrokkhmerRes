<?php
namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;

class ManagerDashboard {
    public const STATUSES=['New','Preparing','Ready','Out for delivery','Delivered','Completed','Cancelled'];

    public function data(Request $request): array {
        $filters=$request->validate([
            'period'=>'nullable|in:today,week,month,all',
            'status'=>'nullable|in:Active,'.implode(',',self::STATUSES),
            'service'=>'nullable|in:Dine in,Takeaway,Delivery',
            'q'=>'nullable|string|max:80',
        ]);
        $period=$filters['period']??'today';
        $periodQuery=Order::query();
        $start=match($period){'today'=>now()->startOfDay(),'week'=>now()->subDays(6)->startOfDay(),'month'=>now()->startOfMonth(),default=>null};
        if($start) $periodQuery->whereBetween('created_at',[$start,now()->endOfDay()]);
        $statusCounts=(clone $periodQuery)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count','status');
        $active=['New','Preparing','Ready','Out for delivery'];
        $stats=[
            'orders'=>(clone $periodQuery)->count(),
            'active'=>$statusCounts->only($active)->sum(),
            'sales'=>(int)(clone $periodQuery)->whereIn('status',['Completed','Delivered'])->sum('total'),
            'deliveries'=>(clone $periodQuery)->where('service','Delivery')->whereIn('status',$active)->count(),
            'completed'=>$statusCounts->only(['Completed','Delivered'])->sum(),
        ];
        $query=clone $periodQuery;
        if(!empty($filters['status'])) {
            $filters['status']==='Active' ? $query->whereIn('status',$active) : $query->where('status',$filters['status']);
        }
        if(!empty($filters['service'])) $query->where('service',$filters['service']);
        if(!empty($filters['q'])) {
            $term=trim($filters['q']);
            $query->where(function($q) use($term){
                $q->where('customer','like','%'.$term.'%')->orWhere('phone','like','%'.$term.'%');
                if(ctype_digit(ltrim($term,'#'))) $q->orWhere('id',(int)ltrim($term,'#'));
            });
        }
        $serviceCounts=(clone $periodQuery)->selectRaw('service, COUNT(*) as count')->groupBy('service')->pluck('count','service');
        $weeklyOrders=Order::whereBetween('created_at',[now()->subDays(6)->startOfDay(),now()->endOfDay()])->whereIn('status',['Completed','Delivered'])->get(['created_at','total']);
        $chart=collect(range(6,0))->map(function($days) use($weeklyOrders){
            $date=now()->subDays($days);
            return ['label'=>$date->format('D'),'date'=>$date->format('d M'),'value'=>$weeklyOrders->filter(fn($order)=>$order->created_at->isSameDay($date))->sum('total')];
        });
        return [
            'orders'=>$query->with('restaurant')->latest()->orderByDesc('id')->paginate(12)->withQueryString(),
            'filters'=>$filters,'period'=>$period,'stats'=>$stats,'statusCounts'=>$statusCounts,
            'serviceCounts'=>$serviceCounts,'chart'=>$chart,'chartMax'=>max(1,$chart->max('value')),
            'statuses'=>self::STATUSES,
            'periodLabel'=>match($period){'today'=>'Today','week'=>'Last 7 days','month'=>'This month',default=>'All time'},
        ];
    }
}
