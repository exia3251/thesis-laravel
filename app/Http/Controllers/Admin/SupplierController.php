<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('admin.suppliers');
    }

    public function getSuppliers()
    {
        $suppliers = Supplier::withCount('products')->orderBy('supplier_name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|unique:suppliers,supplier_name|max:255',
            'contact_person' => 'nullable|max:100',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable'
        ]);

        $supplier = Supplier::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Supplier added successfully',
            'data' => $supplier
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Supplier not found'], 404);
        }

        $request->validate([
            'supplier_name' => 'required|max:255|unique:suppliers,supplier_name,'.$id.',supplier_id',
            'contact_person' => 'nullable|max:100',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable'
        ]);

        $supplier->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ]);
    }

    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json(['success' => false, 'message' => 'Supplier not found'], 404);
        }

        if ($supplier->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with existing products'
            ], 400);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ]);
    }
}