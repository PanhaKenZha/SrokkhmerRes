<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MenuItem extends Model {
    public const TAGS=['popular'=>'Popular','new'=>'New','chef'=>"Chef's pick",'spicy'=>'Spicy','vegetarian'=>'Vegetarian','vegan'=>'Vegan','halal'=>'Halal','gluten_free'=>'Gluten free','seafood'=>'Seafood','contains_nuts'=>'Contains nuts'];
    public const CATEGORIES=['Khmer classics','Noodles & rice','Soups & curries','Grill & BBQ','Seafood','Small plates','Salads','Fast food','Chicken','Vegetarian','Desserts','Drinks','Coffee & tea','Breakfast','Kids menu'];
    protected $guarded=['id'];
    protected function casts(): array {return ['is_available'=>'boolean','tags'=>'array'];}
    public function restaurant(){return $this->belongsTo(Restaurant::class);}
    public function tagLabels(): array {return array_values(array_intersect_key(self::TAGS,array_flip($this->tags??[])));}
}
