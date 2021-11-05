<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
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

        $user = User::where('email', '=', $credentials['email'])->first();

        if (!is_null($user)) {
            if ($user->locked_flg === 1) {
                return back()->withErrors([
                    'login_error' => 'アカウントがロックされています。',
                ]);
            }

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                // ログインに成功したらエラーカウントを0に戻す
                if ($user->error_count > 0) {
                    $user->error_count = 0;
                    $user->save();
                }
                return redirect()->route('home')->with('login_success','ログイン成功しました！');
            }

            // ログインに失敗したらエラーカウントを1増やす
            $user->error_count = $user->error_count + 1;
            // エラーカウントが6以上になったらlocked_flgを1にしてアカウントをロックする
            if ($user->error_count > 5) {
                $user->locked_flg = 1;
                $user->save();
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
