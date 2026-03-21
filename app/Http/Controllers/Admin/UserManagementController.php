<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('admin.users');
    }

    public function getUsers()
    {
        $users = User::query()
            ->with('customerProfile')
            ->where('role', '!=', 'super_admin')
            ->orderBy('role')
            ->orderBy('full_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username'  => ['required','string','min:3','max:20','regex:/^[A-Za-z][A-Za-z0-9._-]*$/','unique:users,username'],
            'password'  => ['required','string','min:8','max:32'],
            'full_name' => ['required','string','min:5','max:60','regex:/^[A-Za-z]{2,}(\s[A-Za-z]{2,})+$/'],
            'role'      => 'required|in:admin,customer',
            'is_active' => 'nullable|boolean',
            'phone'     => ['nullable','string','regex:/^(09\d{9}|\+639\d{9})$/'],
            'email'     => 'nullable|email|max:100|unique:customer_profiles,email',
            'address'   => 'nullable|string|max:500',
        ], [
            'username.min'       => 'Username must be at least 3 characters.',
            'username.max'       => 'Username must not exceed 20 characters.',
            'username.regex'     => 'Username must start with a letter and may only contain letters, numbers, dots, underscores, or hyphens.',
            'username.unique'    => 'That username is already taken.',
            'email.unique'       => 'That email address is already in use.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.max'       => 'Password must not exceed 32 characters.',
            'full_name.min'      => 'Full name must be at least 5 characters.',
            'full_name.max'      => 'Full name must not exceed 60 characters.',
            'full_name.regex'    => 'Full name must contain at least two words, each with at least 2 letters.',
            'phone.regex'        => 'Phone must be a valid Philippine number e.g. 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'username' => $request->username,
                'password' => $request->password,
                'full_name' => $request->full_name,
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($user->role === 'customer') {
                CustomerProfile::create([
                    'user_id' => $user->user_id,
                    'phone' => $request->phone ?: '',
                    'email' => $request->email,
                    'address' => $request->address ?: '',
                ]);
            }

            ActivityLog::logAction(
                auth()->id(),
                'user_created',
                "" . auth()->user()->full_name . " created {$user->role} account: {$user->full_name} (@{$user->username})",
                $request->ip()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User account created successfully.',
                'data' => $user,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user account.',
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $user = User::where('user_id', $id)
            ->where('role', '!=', 'super_admin')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $request->validate([
            'username'  => ['required','string','min:3','max:20','regex:/^[A-Za-z][A-Za-z0-9._-]*$/','unique:users,username,' . $id . ',user_id'],
            'password'  => ['nullable','string','min:8','max:32'],
            'full_name' => ['required','string','min:5','max:60','regex:/^[A-Za-z]{2,}(\s[A-Za-z]{2,})+$/'],
            'role'      => 'required|in:admin,customer',
            'is_active' => 'nullable|boolean',
            'phone'     => ['nullable','string','regex:/^(09\d{9}|\+639\d{9})$/'],
            'email'     => ['nullable','email','max:100','unique:customer_profiles,email,' . ($user->customerProfile->profile_id ?? 0) . ',profile_id'],
            'address'   => 'nullable|string|max:500',
        ], [
            'username.min'       => 'Username must be at least 3 characters.',
            'username.max'       => 'Username must not exceed 20 characters.',
            'username.regex'     => 'Username must start with a letter and may only contain letters, numbers, dots, underscores, or hyphens.',
            'username.unique'    => 'That username is already taken.',
            'email.unique'       => 'That email address is already in use.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.max'       => 'Password must not exceed 32 characters.',
            'full_name.min'      => 'Full name must be at least 5 characters.',
            'full_name.max'      => 'Full name must not exceed 60 characters.',
            'full_name.regex'    => 'Full name must contain at least two words, each with at least 2 letters.',
            'phone.regex'        => 'Phone must be a valid Philippine number e.g. 09XXXXXXXXX or +639XXXXXXXXX.',
        ]);

        DB::beginTransaction();

        try {
            $user->fill([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->filled('password')) {
                $user->password = $request->password;
            }

            $user->save();

            if ($user->role === 'customer') {
                CustomerProfile::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'phone' => $request->phone ?: '',
                        'email' => $request->email,
                        'address' => $request->address ?: '',
                    ]
                );
            } else {
                CustomerProfile::where('user_id', $user->user_id)->delete();
            }

            ActivityLog::logAction(
                auth()->id(),
                'user_updated',
                "" . auth()->user()->full_name . " updated {$user->role} account: {$user->full_name} (@{$user->username})",
                $request->ip()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User account updated successfully.',
                'data' => $user,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user account.',
            ], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $user = User::where('user_id', $id)
            ->where('role', '!=', 'super_admin')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $username = $user->username;
        $role = $user->role;

        CustomerProfile::where('user_id', $user->user_id)->delete();
        $user->delete();

        ActivityLog::logAction(
            auth()->id(),
            'user_deleted',
            "" . auth()->user()->full_name . " deleted {$role} account: {$fullName} (@{$username})",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'User account deleted successfully.',
        ]);
    }

    public function getLogs(Request $request)
    {
        $type = $request->get('type', 'all');

        $adminActions   = ['admin_login', 'logout', 'single_session_replaced', 'session_invalidated', 'user_created', 'user_updated', 'user_deleted', 'product_created', 'product_updated', 'product_deleted', 'product_catalog_imported', 'stock_in', 'stock_out', 'sale_created', 'sale_status_updated', 'payment_request_approved', 'payment_request_rejected'];
        $customerActions = ['customer_login', 'logout', 'single_session_replaced', 'session_invalidated', 'customer_registered', 'order_placed', 'payment_request_submitted', 'profile_updated', 'password_changed'];

        $query = ActivityLog::with('user')->orderByDesc('log_id')->limit(200);

        if ($type === 'admin') {
            $query->whereIn('action', $adminActions)
                  ->whereHas('user', fn($q) => $q->whereIn('role', ['admin', 'super_admin']));
        } elseif ($type === 'customer') {
            $query->whereIn('action', $customerActions)
                  ->whereHas('user', fn($q) => $q->where('role', 'customer'));
        }

        $logs = $query->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}