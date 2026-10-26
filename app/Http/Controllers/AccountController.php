<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller {
    /** Remember where to go after signing in, but only for same-site paths. */
    private function rememberReturn(Request $request): void {
        $return=(string)$request->query('return','');
        if(str_starts_with($return,'/')&&!str_starts_with($return,'//')&&!str_contains($return,'\\')) $request->session()->put('url.intended',$return);
    }
    public function showLogin(Request $request){
        $this->rememberReturn($request);
        return view('login');
    }
    public function showRegister(Request $request){
        $this->rememberReturn($request);
        return view('register');
    }
    public function register(Request $request){
        $data=$request->validate([
            'name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users,email',
            'phone'=>['nullable','regex:/^\+?[0-9 ()-]{8,20}$/'],'password'=>['required','confirmed',Password::min(8)],
        ]);
        $user=User::create($data); // role defaults to customer; it is never taken from the form
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended('/')->with('account_status','Welcome, '.$user->name.'! You can now place orders.');
    }
    public function orders(Request $request){
        return view('account-orders',['orders'=>$request->user()->orders()->with('restaurant')->latest()->paginate(10)]);
    }
}
