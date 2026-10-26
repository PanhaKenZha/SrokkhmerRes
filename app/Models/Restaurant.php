<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Restaurant extends Model {
    protected $guarded=['id'];
    protected function casts(): array {return ['is_active'=>'boolean','latitude'=>'float','longitude'=>'float'];}
    public function menuItems(){return $this->hasMany(MenuItem::class);}
}
