<?php
namespace App\Http\Controllers;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BannerController extends Controller {
    private function page(?Banner $editing){
        $banners=Banner::orderBy('sort_order')->orderBy('id')->get();
        return view('staff-banners',['banners'=>$banners,'editing'=>$editing,'stats'=>['total'=>$banners->count(),'live'=>$banners->where('is_active',true)->count()]]);
    }
    public function index(){return $this->page(null);}
    public function edit(Banner $banner){return $this->page($banner);}
    public function store(Request $request){
        $banner=new Banner(['sort_order'=>(int)Banner::max('sort_order')+1]);
        $this->save($request,$banner);
        return redirect('/staff/banners')->with('success','Banner added. Active banners show on the home page.');
    }
    public function update(Request $request,Banner $banner){
        $this->save($request,$banner);
        return redirect('/staff/banners')->with('success','Banner saved.');
    }
    public function destroy(Banner $banner){
        $this->deleteFile($banner);
        $banner->delete();
        return redirect('/staff/banners')->with('success','Banner deleted.');
    }
    public function move(Request $request,Banner $banner){
        $direction=$request->validate(['direction'=>'required|in:up,down'])['direction'];
        DB::transaction(function () use ($banner,$direction) {
            $ordered=Banner::orderBy('sort_order')->orderBy('id')->get()->values();
            $index=$ordered->search(fn($item)=>$item->is($banner));
            $swap=$direction==='up'?$index-1:$index+1;
            if($swap<0||$swap>=$ordered->count()) return;
            [$ordered[$index],$ordered[$swap]]=[$ordered[$swap],$ordered[$index]];
            $ordered->each(fn($item,$position)=>$item->sort_order===$position+1?null:$item->forceFill(['sort_order'=>$position+1])->save());
        });
        return back();
    }
    private function save(Request $request,Banner $banner): void {
        $data=$request->validate([
            'title'=>'required|string|max:120',
            'image_file'=>'nullable|file|image|mimes:jpg,jpeg,png,webp|max:6144',
            'image_url'=>'nullable|url:https|max:2000',
            'link_url'=>['nullable','string','max:2000','regex:#^(/(?!/)\S*|https://\S+)$#'],
            'button_label'=>'nullable|string|max:40',
            'is_active'=>'nullable|boolean',
        ],['link_url.regex'=>'Use a site path like /#menu or a full https:// link.']);
        if(!$banner->exists&&!$request->hasFile('image_file')&&empty($data['image_url'])) {
            throw ValidationException::withMessages(['image_file'=>'Upload a banner image or paste an https image link.']);
        }
        $banner->fill(['title'=>$data['title'],'link_url'=>$data['link_url']??null,'button_label'=>$data['button_label']??null,'is_active'=>$request->boolean('is_active')]);
        if($request->hasFile('image_file')) {
            [$path,$width,$height]=$this->storeImage($request->file('image_file'));
            $this->deleteFile($banner);
            $banner->fill(['image'=>$path,'image_width'=>$width,'image_height'=>$height]);
        } elseif(!empty($data['image_url'])&&$data['image_url']!==$banner->image) {
            $this->deleteFile($banner);
            // Remote images are never fetched by the server; the slider reads their shape in the browser.
            $banner->fill(['image'=>$data['image_url'],'image_width'=>null,'image_height'=>null]);
        }
        $banner->save();
    }
    /** Re-encode uploads as JPEG: proves the file is a real image, strips metadata and caps the size. */
    private function storeImage(UploadedFile $file): array {
        $source=@imagecreatefromstring((string)file_get_contents($file->getRealPath()));
        if(!$source) throw ValidationException::withMessages(['image_file'=>'That file could not be read as an image.']);
        $width=imagesx($source);$height=imagesy($source);
        if($width<600||$width/$height<1.2) throw ValidationException::withMessages(['image_file'=>'Use a wide image at least 600px across (landscape banner).']);
        if($width>2400){$newHeight=(int)round($height*2400/$width);$resized=imagecreatetruecolor(2400,$newHeight);imagecopyresampled($resized,$source,0,0,0,0,2400,$newHeight,$width,$height);$source=$resized;$width=2400;$height=$newHeight;}
        $canvas=imagecreatetruecolor($width,$height);imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));imagecopy($canvas,$source,0,0,0,0,$width,$height);
        $directory=public_path('uploads/banners');
        if(!is_dir($directory)) mkdir($directory,0755,true);
        $name=Str::uuid().'.jpg';
        imageinterlace($canvas,true);imagejpeg($canvas,$directory.'/'.$name,85);
        return ['/uploads/banners/'.$name,$width,$height];
    }
    private function deleteFile(Banner $banner): void {
        if($banner->exists&&$banner->isUploaded()) {
            $path=public_path(ltrim($banner->image,'/'));
            if(is_file($path)&&str_starts_with(realpath($path),realpath(public_path('uploads/banners')))) unlink($path);
        }
    }
}
