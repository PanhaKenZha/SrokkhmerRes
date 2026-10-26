<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['items'=>'array']; }
    public function restaurant(){return $this->belongsTo(Restaurant::class);}
    public function user(){return $this->belongsTo(User::class);}
}
