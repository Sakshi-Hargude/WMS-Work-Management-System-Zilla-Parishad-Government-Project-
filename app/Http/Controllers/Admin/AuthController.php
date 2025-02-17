<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function store(LoginRequest $request)
    {
        $request->authenticate('admin');
        $request->session()->regenerate();
        return redirect()->intended(route('admin.home'));
    }

    // Event-category
    public function categorysave(LoginRequest $request)
    {
        $request->authenticate('admin');

        $request->session()->regenerate();

        return redirect('admin.category.create');
    }

    public function categorylist(LoginRequest $request)
    {
        $request->authenticate('admin');
        $request->session()->regenerate();
        return redirect('admin.eventcategory.list');
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
