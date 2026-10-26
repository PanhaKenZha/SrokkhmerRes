<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeamController extends Controller {
    private function like(string $term): string {return '%'.addcslashes($term,'%_\\').'%';}
    public function index(Request $request){
        $listFilters=['q'=>trim((string)$request->query('q','')),'role'=>array_key_exists((string)$request->query('role'),User::ROLES)?(string)$request->query('role'):''];
        $users=User::query()
            ->when($listFilters['q']!=='',fn($query)=>$query->where(fn($match)=>$match->where('name','like',$this->like($listFilters['q']))->orWhere('email','like',$this->like($listFilters['q']))))
            ->when($listFilters['role']!=='',fn($query)=>$query->where('role',$listFilters['role']))
            ->withCount('orders')
            ->orderByRaw("case role when 'admin' then 0 when 'owner' then 1 when 'staff' then 2 else 3 end")->orderBy('name')
            ->paginate(20)->withQueryString();
        $counts=User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total','role');
        return view('staff-team',['users'=>$users,'counts'=>$counts,'listFilters'=>$listFilters,'assignable'=>$request->user()->assignableRoles()]);
    }
    public function store(Request $request){
        $data=$request->validate([
            'name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users,email',
            'phone'=>['nullable','regex:/^\+?[0-9 ()-]{8,20}$/'],'password'=>['required',Password::min(8)],
            'role'=>['required',Rule::in($request->user()->assignableRoles())],
        ]);
        $user=new User(collect($data)->except('role')->all());
        $user->role=$data['role'];
        $user->save();
        return redirect('/staff/team')->with('success',$user->name.' was added as '.$user->roleLabel().'.');
    }
    public function update(Request $request,User $user){
        abort_unless($request->user()->canManageUser($user),403,'You cannot change this account.');
        $data=$request->validate(['role'=>['required',Rule::in($request->user()->assignableRoles())]]);
        if($user->role==='admin'&&$data['role']!=='admin'&&User::where('role','admin')->count()<=1) {
            return back()->withErrors(['role'=>'Keep at least one admin so someone can always manage the team.']);
        }
        $user->role=$data['role'];
        $user->save();
        return back()->with('success',$user->name.' is now '.$user->roleLabel().'.');
    }
}
