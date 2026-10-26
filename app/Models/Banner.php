<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Banner extends Model {
    protected $guarded=['id'];
    protected function casts(): array {return ['is_active'=>'boolean'];}
    public function scopeLive($query){return $query->where('is_active',true)->orderBy('sort_order')->orderBy('id');}
    /** Width / height, used so the slider keeps each banner's real shape. */
    public function ratio(): float {return $this->image_width&&$this->image_height?round($this->image_width/$this->image_height,4):2.4;}
    public function isUploaded(): bool {return str_starts_with($this->image,'/uploads/banners/');}
}
