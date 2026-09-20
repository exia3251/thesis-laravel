<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            ->orderBy('role')
            ->orderBy('full_name')
            ->get()
            ->map(function (User $user) {
                // Appended rather than sent raw, so the view does not have to
                // know where uploads live or how initials are derived.
                return $user->toArray() + [
                    'avatar_url' => $user->avatarUrl(),
                    'initials' => $user->initials(),
                    'avatar_tone' => $user->avatarTone(),
                    'role_label' => $user->roleLabel(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    protected function accountRules(?int $ignoreUserId = null): array
    {
        $emailUnique = 'unique:users,email' . ($ignoreUserId ? ',' . $ignoreUserId . ',user_id' : '');

        return [
            'email'     => ['required', 'email', 'max:150', $emailUnique],
            'password'  => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:8', 'max:32'],
            'full_name' => ['required', 'string', 'min:5', 'max:60', 'regex:/^[A-Za-z]{2,}(\s[A-Za-z]{2,})+$/'],
            'role'      => 'required|in:admin,inventory_staff,accounting,customer',
            'is_active' => 'nullable|boolean',
            'phone'     => ['nullable', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'address'   => 'nullable|string|max:500',
            'avatar'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100'],
        ];
    }

    protected function accountMessages(): array
    {
        return [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Enter a valid email address.',
            'email.unique'      => 'That email address is already in use.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.max'      => 'Password must not exceed 32 characters.',
            'full_name.min'     => 'Full name must be at least 5 characters.',
            'full_name.max'     => 'Full name must not exceed 60 characters.',
            'full_name.regex'   => 'Full name must contain at least two words, each with at least 2 letters.',
            'role.in'           => 'Select a valid role.',
            'phone.regex'       => 'Phone must be a valid Philippine number e.g. 09XXXXXXXXX or +639XXXXXXXXX.',
            'avatar.image'      => 'The photo must be an image file.',
            'avatar.mimes'      => 'Accepted photo formats are JPG, JPEG, PNG and WEBP.',
            'avatar.max'        => 'The photo must be smaller than 2 MB.',
            'avatar.dimensions' => 'That image is too small. Use one at least 100 by 100 pixels.',
        ];
    }

    /**
     * With no super admin tier above them, administrators police each other.
     * The one thing they must not be able to do is leave the system with
     * nobody able to administer it.
     */
    protected function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== User::ROLE_ADMIN || !$user->is_active) {
            return false;
        }

        return User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('user_id', '!=', $user->user_id)
            ->doesntExist();
    }

    /**
     * Stores an uploaded photo and discards whatever it replaces, so one
     * account never accumulates orphaned files in storage.
     */
    protected function storeAvatar(Request $request, User $user): void
    {
        if (!$request->hasFile('avatar')) {
            return;
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        $user->save();
    }

    public function store(Request $request)
    {
        $request->validate($this->accountRules(), $this->accountMessages());

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'password' => $request->password,
                'full_name' => $request->full_name,
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->storeAvatar($request, $user);

            if ($user->role === User::ROLE_CUSTOMER) {
                CustomerProfile::create([
                    'user_id' => $user->user_id,
                    'phone' => $request->phone ?: '',
                    'address' => $request->address ?: '',
                ]);
            }

            ActivityLog::logAction(
                auth()->id(),
                'user_created',
                auth()->user()->full_name . " created {$user->roleLabel()} account: {$user->full_name} ({$user->email})",
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
        $user = User::where('user_id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $request->validate($this->accountRules($id), $this->accountMessages());

        $losingAdminRights = $request->role !== User::ROLE_ADMIN || !$request->boolean('is_active', true);

        if ($losingAdminRights && $this->isLastActiveAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'This is the only active administrator. Promote another account first.',
            ], 422);
        }

        if ($losingAdminRights && (int) $user->user_id === (int) auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove your own administrator access.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user->fill([
                'email' => $request->email,
                'full_name' => $request->full_name,
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->filled('password')) {
                $user->password = $request->password;
            }

            $user->save();

            $this->storeAvatar($request, $user);

            if ($user->role === User::ROLE_CUSTOMER) {
                CustomerProfile::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'phone' => $request->phone ?: '',
                        'address' => $request->address ?: '',
                    ]
                );
            } else {
                CustomerProfile::where('user_id', $user->user_id)->delete();
            }

            ActivityLog::logAction(
                auth()->id(),
                'user_updated',
                auth()->user()->full_name . " updated {$user->roleLabel()} account: {$user->full_name} ({$user->email})",
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
        $user = User::where('user_id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ((int) $user->user_id === (int) auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        if ($this->isLastActiveAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'This is the only active administrator and cannot be deleted.',
            ], 422);
        }

        // sales, stock_transactions and activity_logs all hold restricted foreign
        // keys to users, so deleting an account with any history raises a
        // constraint violation. Such accounts are deactivated, never removed,
        // which also keeps the audit trail intact.
        if ($this->hasHistory($user)) {
            return response()->json([
                'success' => false,
                'message' => 'This account has activity history and cannot be deleted. Deactivate it instead.',
            ], 422);
        }

        $fullName = $user->full_name;
        $email = $user->email;
        $role = $user->roleLabel();

        try {
            DB::transaction(function () use ($user) {
                CustomerProfile::where('user_id', $user->user_id)->delete();
                $user->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This account is still referenced by other records and cannot be deleted. Deactivate it instead.',
            ], 422);
        }

        ActivityLog::logAction(
            auth()->id(),
            'user_deleted',
            auth()->user()->full_name . " deleted {$role} account: {$fullName} ({$email})",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'User account deleted successfully.',
        ]);
    }

    /**
     * Whether the account is referenced by records that block a hard delete.
     */
    protected function hasHistory(User $user): bool
    {
        return $user->sales()->exists()
            || $user->activityLogs()->exists()
            || DB::table('stock_transactions')->where('user_id', $user->user_id)->exists();
    }

    public function getLogs(Request $request)
    {
        $type = $request->get('type', 'all');

        $staffActions   = ['admin_login', 'logout', 'single_session_replaced', 'session_invalidated', 'user_created', 'user_updated', 'user_deleted', 'product_created', 'product_updated', 'product_deleted', 'product_catalog_imported', 'stock_in', 'stock_out', 'sale_created', 'sale_status_updated', 'payment_request_approved', 'payment_request_rejected', 'sales_report_exported', 'inventory_report_exported'];
        $customerActions = ['customer_login', 'logout', 'single_session_replaced', 'session_invalidated', 'customer_registered', 'order_placed', 'payment_request_submitted', 'profile_updated', 'password_changed', 'password_set'];

        $query = ActivityLog::with('user')->orderByDesc('log_id')->limit(200);

        if ($type === 'admin') {
            $query->whereIn('action', $staffActions)
                  ->whereHas('user', fn($q) => $q->whereIn('role', User::STAFF_ROLES));
        } elseif ($type === 'customer') {
            $query->whereIn('action', $customerActions)
                  ->whereHas('user', fn($q) => $q->where('role', User::ROLE_CUSTOMER));
        }

        $logs = $query->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
