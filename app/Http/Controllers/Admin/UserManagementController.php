<?php

namespace App\Http\Controllers\Admin;

use App\Support\Search;
use App\Support\PasswordPolicy;
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

    /**
     * How many accounts sit under each role, for the filter buttons.
     *
     * Counted across the whole table rather than the page on screen, or the
     * numbers would change every time somebody turned a page.
     */
    private function roleCounts(bool $archived): array
    {
        $base = fn () => $archived ? User::onlyTrashed() : User::query();

        $counts = ['all' => $base()->count()];

        foreach ([User::ROLE_ADMIN, User::ROLE_INVENTORY_STAFF, User::ROLE_ACCOUNTING, User::ROLE_CUSTOMER] as $role) {
            $counts[$role] = $base()->where('role', $role)->count();
        }

        return $counts;
    }

    public function getUsers(Request $request)
    {
        $request->validate([
            'archived' => 'nullable|boolean',
            'search' => 'nullable|string|max:100',
            'role' => 'nullable|in:all,admin,inventory_staff,accounting,customer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        // Archived accounts are a separate view rather than greyed-out rows,
        // so nobody edits or promotes one by mistake.
        $query = $request->boolean('archived')
            ? User::onlyTrashed()->orderByDesc('deleted_at')
            : User::query()->orderBy('role')->orderBy('full_name');

        $query->with('customerProfile');

        if ($request->filled('role') && $request->input('role') !== 'all') {
            $query->where('role', $request->input('role'));
        }

        /*
         * Searched here rather than in the browser. The list was drawn from
         * every account in one response and filtered on the page, which is
         * fine at forty and not at four thousand: the whole table travelled
         * on every visit, and a search only ever looked at what had already
         * arrived.
         */
        if ($request->filled('search')) {
            $term = trim($request->input('search'));

            Search::apply($query, $term, ['full_name', 'email']);
        }

        return $this->paginated(
            $query->paginate($this->perPage()),
            function (User $user) {
                // Appended rather than sent raw, so the view does not have to
                // know where uploads live or how initials are derived.
                return $user->toArray() + [
                    'avatar_url' => $user->avatarUrl(),
                    'initials' => $user->initials(),
                    'avatar_tone' => $user->avatarTone(),
                    'role_label' => $user->roleLabel(),
                    'archived' => $user->trashed(),
                ];
            },
            ['counts' => $this->roleCounts($request->boolean('archived'))]
        );
    }

    protected function accountRules(?int $ignoreUserId = null): array
    {
        $emailUnique = 'unique:users,email' . ($ignoreUserId ? ',' . $ignoreUserId . ',user_id' : '');

        return [
            'email'     => ['required', 'email', 'max:150', $emailUnique],
            'password'  => PasswordPolicy::rules(confirmed: false, optional: (bool) $ignoreUserId),
            'full_name' => ['required', 'string', 'min:5', 'max:60', 'regex:/^[A-Za-z]{2,}(\s[A-Za-z]{2,})+$/'],
            'role'      => 'required|in:admin,inventory_staff,accounting,customer',
            'is_active' => 'nullable|boolean',
            'phone'     => ['nullable', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'house_street' => 'nullable|string|max:160',
            'barangay'     => 'nullable|string|max:100',
            'city'         => 'nullable|string|max:100',
            'province'     => 'nullable|string|max:100',
            'postal_code'  => ['nullable', 'string', 'regex:/^\d{4}$/'],
            'avatar'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=200,min_height=200'],
        ];
    }

    protected function accountMessages(): array
    {
        return [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Enter a valid email address.',
            'email.unique'      => 'That email address is already in use. If the account was archived, restore it instead of creating a new one.',
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
            'avatar.dimensions' => 'That image is too small. Use one at least 200 by 200 pixels.',
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
                ] + $request->only(['house_street', 'barangay', 'city', 'province', 'postal_code']));
            }

            ActivityLog::logAction(
                auth()->id(),
                'user_created',
                auth()->user()->full_name . " created {$user->roleLabel()} account: {$user->full_name} ({$user->email})"
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
                    ['phone' => $request->phone ?: ''] + $request->only(['house_street', 'barangay', 'city', 'province', 'postal_code'])
                );
            } else {
                CustomerProfile::where('user_id', $user->user_id)->delete();
            }

            ActivityLog::logAction(
                auth()->id(),
                'user_updated',
                auth()->user()->full_name . " updated {$user->roleLabel()} account: {$user->full_name} ({$user->email})"
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

        $fullName = $user->full_name;
        $email = $user->email;
        $role = $user->roleLabel();

        // Archiving rather than removing. Sales, stock movements and the
        // activity log all hold restricted foreign keys to this row, so a
        // real delete failed for any account that had ever done anything --
        // and every account that has signed in once has a log entry.
        DB::transaction(function () use ($user) {
            // Whatever they are doing right now stops here.
            $user->forceFill(['current_session_id' => null])->save();
            $user->cart()->delete();
            $user->delete();
        });

        ActivityLog::logAction(
            auth()->id(),
            'user_archived',
            auth()->user()->full_name . " archived {$role} account: {$fullName} ({$email})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Account archived. Its orders and history are kept, and it can no longer sign in.',
        ]);
    }

    /** Returns an archived account to the active list. */
    public function restore(int $id)
    {
        $user = User::onlyTrashed()->where('user_id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No archived account with that id.',
            ], 404);
        }

        // The email column is unique across archived rows too, so nothing can
        // have taken the address; the profile phone number is a different
        // matter and is left to the profile screen to sort out.
        $user->restore();

        ActivityLog::logAction(
            auth()->id(),
            'user_restored',
            auth()->user()->full_name . " restored {$user->roleLabel()} account: {$user->full_name} ({$user->email})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Account restored.',
        ]);
    }

    public function getLogs(Request $request)
    {
        $type = $request->get('type', 'all');

        $staffActions   = ['admin_login', 'logout', 'single_session_replaced', 'session_invalidated', 'user_created', 'user_updated', 'user_deleted', 'product_created', 'product_updated', 'product_deleted', 'product_catalog_imported', 'stock_in', 'stock_out', 'sale_created', 'sale_status_updated', 'payment_request_approved', 'payment_request_rejected', 'sales_report_exported', 'inventory_report_exported', 'database_backup_created', 'database_backup_downloaded', 'database_backup_deleted', 'user_archived', 'user_restored', 'product_archived', 'product_restored', 'own_account_updated', 'own_password_changed', 'own_password_set', 'chatbot_intent_updated', 'vehicle_spec_updated', 'vehicle_spec_verified'];
        $customerActions = ['customer_login', 'logout', 'single_session_replaced', 'session_invalidated', 'customer_registered', 'order_placed', 'payment_request_submitted', 'profile_updated', 'password_changed', 'password_set'];

        $query = ActivityLog::with('user')->orderByDesc('log_id');

        if ($type === 'admin') {
            $query->whereIn('action', $staffActions)
                  ->whereHas('user', fn($q) => $q->whereIn('role', User::STAFF_ROLES));
        } elseif ($type === 'customer') {
            $query->whereIn('action', $customerActions)
                  ->whereHas('user', fn($q) => $q->where('role', User::ROLE_CUSTOMER));
        }

        return $this->paginated($query->paginate($this->perPage(25)));
    }
}
