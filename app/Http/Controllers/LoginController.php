<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
    
        $user = User::where('username', $credentials['username'])->first();
    
        // Verificar si el usuario existe
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado.'], 401);
        }
        
        // Verificar si el usuario está inactivo
        if ($user->status == 0) {
            return response()->json(['error' => 'Usuario inactivo. Por favor, contacte a sistemas.'], 401);
        }
    
        // Verificar si el usuario está bloqueado
        if ($user->locked) {
            return response()->json(['error' => 'Usuario bloqueado. Por favor, contacte a sistemas.'], 401);
        }
    
        // Intentar autenticar solo si el usuario no está bloqueado
        if (Auth::attempt($credentials)) {
            if ($credentials['password'] === 'Ferre01@') {
                Auth::logout();
                return response()->json(['changePassword' => true, 'username' => $credentials['username']]);
            }
            // Reiniciar los intentos fallidos si el inicio de sesión es exitoso
            $user->update(['failed_login_attempts' => 0]);
            return response()->json(['changePassword' => false]);
        } else {
            // Incrementar los intentos fallidos si el inicio de sesión falla
            $user->increment('failed_login_attempts');
    
            if ($user->failed_login_attempts >= 4) {
                // Verificar y actualizar el campo locked solo si no está ya bloqueado
                if (!$user->locked) {
                    $user->update(['locked' => true]);
                }
                return response()->json(['error' => 'Usuario bloqueado. Por favor, contacte a sistemas.'], 401);
            }
    
            $attemptsLeft = 4 - $user->failed_login_attempts;
            return response()->json(['error' => "Contraseña incorrecta. Intentos restantes: $attemptsLeft."], 401);
        }
    }    
    
    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'newPassword' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[@$!%*?&]/'],
        ]);

        $user = User::where('username', $request->username)->firstOrFail();
        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['success' => true]);
    }
}
