<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function editPassword()
    {
        return view('account.password');
    }

    /** Any signed-in user can change their own password; the current one is required. */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();

        return redirect()->route('account.password')->with('success', 'Your password has been changed. Use the new one next time you sign in.');
    }
}
