<?php
namespace Tests\Feature;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BannerTest extends TestCase {
    use RefreshDatabase;
    private function owner(): void {$this->actingAs(User::factory()->create(['role'=>'owner']));}

    public function test_home_page_shows_active_banners_in_order(): void {
        Banner::query()->update(['is_active'=>false]);
        Banner::create(['title'=>'Second banner','image'=>'/images/banners/srok-khmer-heritage.jpg','sort_order'=>2,'is_active'=>true]);
        Banner::create(['title'=>'First banner','image'=>'/images/banners/srok-khmer-table.jpg','link_url'=>'/#menu','button_label'=>'Order now','sort_order'=>1,'is_active'=>true]);
        Banner::create(['title'=>'Hidden banner','image'=>'/images/banners/srok-khmer-table.jpg','sort_order'=>0,'is_active'=>false]);
        $this->get('/')->assertOk()->assertSeeInOrder(['First banner','Second banner'])->assertSee('Order now')->assertDontSee('Hidden banner');
    }

    public function test_owner_uploads_edits_reorders_and_deletes_banners(): void {
        $this->owner();
        $this->get('/staff/banners')->assertOk()->assertSee('Home banners');
        $this->post('/staff/banners',['title'=>'New dishes','image_file'=>UploadedFile::fake()->image('banner.png',1800,700),'link_url'=>'/fast-food','button_label'=>'Try them','is_active'=>1])->assertRedirect('/staff/banners');
        $banner=Banner::where('title','New dishes')->firstOrFail();
        $this->assertStringStartsWith('/uploads/banners/',$banner->image);
        $this->assertSame([1800,700],[$banner->image_width,$banner->image_height]);
        $file=public_path(ltrim($banner->image,'/'));$this->assertFileExists($file);
        $this->get('/staff/banners/'.$banner->id.'/edit')->assertOk()->assertSee('Edit banner');
        $this->patch('/staff/banners/'.$banner->id,['title'=>'New dishes this week','link_url'=>'/fast-food','is_active'=>0])->assertRedirect();
        $this->assertFalse($banner->fresh()->is_active);$this->assertSame($banner->image,$banner->fresh()->image,'Saving without a new image keeps the old one.');
        $last=Banner::orderByDesc('sort_order')->first();$this->assertTrue($last->is($banner));
        $this->patch('/staff/banners/'.$banner->id.'/move',['direction'=>'up'])->assertRedirect();
        $this->assertSame($banner->id,Banner::orderByDesc('sort_order')->skip(1)->first()->id);
        $this->delete('/staff/banners/'.$banner->id)->assertRedirect('/staff/banners');
        $this->assertModelMissing($banner);$this->assertFileDoesNotExist($file);
    }

    public function test_banner_input_is_validated(): void {
        $this->owner();
        $this->post('/staff/banners',['title'=>'No image'])->assertSessionHasErrors('image_file');
        $this->post('/staff/banners',['title'=>'Tall','image_file'=>UploadedFile::fake()->image('tall.jpg',600,900)])->assertSessionHasErrors('image_file');
        $this->post('/staff/banners',['title'=>'Not image','image_file'=>UploadedFile::fake()->create('evil.php',10,'text/plain')])->assertSessionHasErrors('image_file');
        $this->post('/staff/banners',['title'=>'Bad link','image_url'=>'https://example.com/b.jpg','link_url'=>'javascript:alert(1)'])->assertSessionHasErrors('link_url');
        $this->post('/staff/banners',['title'=>'Bad link','image_url'=>'https://example.com/b.jpg','link_url'=>'//evil.example'])->assertSessionHasErrors('link_url');
        $this->post('/staff/banners',['title'=>'Plain http','image_url'=>'http://example.com/b.jpg'])->assertSessionHasErrors('image_url');
        $this->post('/staff/banners',['title'=>'Remote','image_url'=>'https://example.com/b.jpg','link_url'=>'https://example.com/promo'])->assertRedirect('/staff/banners');
        $this->assertDatabaseHas('banners',['title'=>'Remote','image'=>'https://example.com/b.jpg']);
    }

    public function test_staff_and_customers_cannot_manage_banners(): void {
        $banner=Banner::first();
        foreach(['staff','customer'] as $role){
            $this->actingAs(User::factory()->create(['role'=>$role]));
            $this->get('/staff/banners')->assertForbidden();
            $this->delete('/staff/banners/'.$banner->id)->assertForbidden();
        }
        $this->assertModelExists($banner);
    }
}
