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
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:100',
            'role' => 'required|in:admin,customer',
            'is_active' => 'nullable|boolean',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
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
                "Created {$user->role} account: {$user->username}",
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
            'username' => 'required|string|max:50|unique:users,username,' . $id . ',user_id',
            'password' => 'nullable|string|min:6',
            'full_name' => 'required|string|max:100',
            'role' => 'required|in:admin,customer',
            'is_active' => 'nullable|boolean',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
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
                "Updated {$user->role} account: {$user->username}",
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
            "Deleted {$role} account: {$username}",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'User account deleted successfully.',
        ]);
    }

    public function getLogs()
    {
        $logs = ActivityLog::with('user')
            ->orderByDesc('log_id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
