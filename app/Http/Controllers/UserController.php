<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return Inertia::render('Users/Index', [
            'users'      => User::with('warehouse')
                                ->orderBy('created_at', 'desc')
                                ->paginate(20),
            'warehouses' => Warehouse::where('is_active', true)
                                     ->orderBy('name')
                                     ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            'role'         => ['required', Rule::in(array_column(Role::cases(), 'value'))],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'                  => ['required', Rule::in(array_column(Role::cases(), 'value'))],
            'warehouse_id'          => ['nullable', 'exists:warehouses,id'],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if($user->id === auth()->id(), 403, 'You cannot delete yourself.');

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
