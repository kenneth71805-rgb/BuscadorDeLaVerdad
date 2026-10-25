<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        return match ($request->user()->role) {
            Role::Admin => redirect()->route('admin.dashboard'),
            Role::EditorInChief => redirect()->route('editor.dashboard'),
            Role::CategoryHead => redirect()->route('category.dashboard'),
            default => abort(403, 'This account has no staff access.'),
        };
    }
}
