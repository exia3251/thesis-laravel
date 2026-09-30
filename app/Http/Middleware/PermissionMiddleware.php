<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks one named permission against the signed-in account. Applied per
 * route, behind the broader admin or customer gate.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in.'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated. Please contact an administrator.'
            ], 403);
        }

        $hasPermission = match ($permission) {
            'manage_users'    => $user->canManageUsers(),
            'view_logs'       => $user->canViewLogs(),
            'backup_database' => $user->canBackupDatabase(),
            'manage_chatbot'  => $user->canManageChatbot(),
            'manage_content'  => $user->canManageContent(),
            'manage_inventory' => $user->canManageInventory(),
            'manage_products' => $user->canManageProducts(),
            'view_sales'      => $user->canViewSales(),
            'create_sales'    => $user->canCreateSales(),
            'view_reports'    => $user->canViewReports(),
            'full_dashboard'  => $user->canViewFullDashboard(),
            'admin'           => $user->isAdmin(),
            'staff'           => $user->isStaff(),
            'customer'        => $user->isCustomer(),
            default           => false,
        };

        if ($hasPermission) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to perform this action.',
                'required_permission' => $permission,
                'your_role' => $user->role,
            ], 403);
        }

        // Send a browser somewhere it is allowed to be, rather than showing
        // it a raw JSON error. Guard against bouncing a page onto itself.
        $home = $user->homePath();

        if ($request->path() === ltrim($home, '/')) {
            abort(403, 'You do not have permission to view this page.');
        }

        return redirect($home)->with('error', 'You do not have permission to view that page.');
    }
}
