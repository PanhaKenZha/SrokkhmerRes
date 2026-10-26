<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meal extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['ingredients'=>'array']; }
}
