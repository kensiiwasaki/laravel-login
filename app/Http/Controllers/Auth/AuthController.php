<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    /**
     *  @return View
     */
    public function showLogin()
    {
        return view('login.login_form');
    }

    /**
     *  @param App\Https\Requests\LoginFormRequest $request
     * @return
     */
    public function login(LoginFormRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // アカウントがロックされていたら弾く
        $user = $this->user->getUserByEmail($credentials['email']);

        if (!is_null($user)) {
            if ($this->user->isAccountLocked($user)) {
                return back()->withErrors([
                    'login_error' => 'アカウントがロックされています。',
                ]);
            }

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                // ログインに成功したらエラーカウントを0に戻す
                $this->user->resetErrorCount($user);

                return redirect()->route('home')->with('login_success','ログイン成功しました！');
            }

            // ログインに失敗したらエラーカウントを1増やす
            $user->error_count = $this->user->addErrorCount($user->error_count);
            // エラーカウントが6以上になったらlocked_flgを1にしてアカウントをロックする
            if ($this->user->lockAccount($user)) {
                return back()->withErrors([
                    'login_error' => 'アカウントがロックされました。解除したい場合は運営者に連絡してください',
                ]);
            }
            $user->save();
        }

        return back()->withErrors([
            'login_error' => 'メールアドレスかパスワードが間違っています。',
        ]);
    }
        /**
     * ユーザーをアプリケーションからログアウトさせる
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login.show')->with('logout', 'ログアウトしました！');
    }
}
