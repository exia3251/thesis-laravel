<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in.'
            ], 401);
        }

        // Check if user account is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated. Please contact administrator.'
            ], 403);
        }

        // Check specific permissions
        $hasPermission = match($permission) {
            'manage_users' => $user->canManageUsers(),
            'view_logs' => $user->canViewLogs(),
            'backup_database' => $user->canBackupDatabase(),
            'manage_inventory' => $user->canManageInventory(),
            'manage_products' => $user->canManageProducts(),
            'create_sales' => $user->canCreateSales(),
            'view_reports' => $user->canViewReports(),
            'full_dashboard' => $user->canViewFullDashboard(),
            'super_admin' => $user->isSuperAdmin(),
            'admin' => $user->isAdmin(),
            'customer' => $user->isCustomer(),
            default => false
        };

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to perform this action.',
                'required_permission' => $permission,
                'your_role' => $user->role
            ], 403);
        }

        return $next($request);
    }
}
