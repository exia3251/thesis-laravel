<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Products - Engine Oil Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <div class="fixed inset-y-0 left-0 w-64 bg-gray-800">
            <div class="flex items-center justify-center h-16 bg-gray-900">
                <span class="text-white font-bold text-xl">Admin Panel</span>
            </div>
            <nav class="mt-5">
                <a href="/admin/dashboard" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Dashboard</a>
                <a href="/admin/products" class="flex items-center px-6 py-3 text-gray-100 bg-gray-900">Products</a>
                <a href="/admin/inventory" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Inventory</a>
                <a href="/admin/sales" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Sales</a>
                <a href="/admin/reports" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full text-left px-6 py-3 text-gray-300 hover:bg-gray-700">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Products</h1>
                    <p class="text-gray-600">Create, update, and remove products from the catalog.</p>
                </div>
                <button onclick="openForm()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Add Product
                </button>
            </div>

            <div id="message" class="hidden mb-4 px-4 py-3 rounded"></div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="productModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-bold">Add Product</h3>
                <button type="button" onclick="closeForm()" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>
            <form id="productForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" id="productId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" id="product_name" required class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Brand</label>
                    <input type="text" id="brand" required class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Oil Type</label>
                    <select id="oil_type" required class="mt-1 block w-full rounded-md border px-3 py-2">
                        <option value="Synthetic">Synthetic</option>
                        <option value="Semi-Synthetic">Semi-Synthetic</option>
                        <option value="Mineral">Mineral</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Viscosity Grade</label>
                    <input type="text" id="viscosity_grade" class="mt-1 block w-full rounded-md border px-3 py-2" placeholder="5W-40">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit</label>
                    <input type="text" id="unit" class="mt-1 block w-full rounded-md border px-3 py-2" value="1 Liter">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" id="price" step="0.01" required class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reorder Level</label>
                    <input type="number" id="reorder_level" min="0" class="mt-1 block w-full rounded-md border px-3 py-2" value="10">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" rows="3" class="mt-1 block w-full rounded-md border px-3 py-2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Product Image</label>
                    <input type="file" id="image" accept="image/*" class="mt-1 block w-full rounded-md border px-3 py-2">
                    <div id="imagePreviewWrapper" class="mt-3 hidden">
                        <img id="imagePreview" src="" alt="Product preview" class="h-28 w-28 rounded border object-cover">
                    </div>
                </div>
                <div class="md:col-span-2 flex gap-2 justify-end">
                    <button type="button" onclick="closeForm()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let products = [];

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mb-4 px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
        }

        function resetForm() {
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('unit').value = '1 Liter';
            document.getElementById('reorder_level').value = '10';
            document.getElementById('imagePreview').src = '';
            document.getElementById('imagePreviewWrapper').classList.add('hidden');
        }

        function closeForm() {
            document.getElementById('productModal').classList.add('hidden');
            resetForm();
        }

        function openForm(product = null) {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = product ? 'Edit Product' : 'Add Product';

            if (!product) {
                resetForm();
                return;
            }

            document.getElementById('productId').value = product.product_id;
            document.getElementById('product_name').value = product.product_name;
            document.getElementById('brand').value = product.brand;
            document.getElementById('oil_type').value = product.oil_type;
            document.getElementById('viscosity_grade').value = product.viscosity_grade || '';
            document.getElementById('unit').value = product.unit || '1 Liter';
            document.getElementById('price').value = product.price;
            document.getElementById('reorder_level').value = product.reorder_level;
            document.getElementById('description').value = product.description || '';
            if (product.image_url) {
                document.getElementById('imagePreview').src = product.image_url;
                document.getElementById('imagePreviewWrapper').classList.remove('hidden');
            }
        }

        async function loadProducts() {
            try {
                const response = await fetch('/admin-api/products', { headers: { Accept: 'application/json' } });

                if (response.status === 401) {
                    window.location.href = '/admin/login';
                    return;
                }

                const data = await response.json();
                products = data.data || [];

                const tbody = document.getElementById('productsBody');
                tbody.innerHTML = products.length
                    ? products.map((product) => `
                        <tr>
                            <td class="px-6 py-4">
                                ${product.image_url
                                    ? `<img src="${product.image_url}" alt="${product.product_name}" class="h-14 w-14 rounded border object-cover">`
                                    : '<div class="h-14 w-14 rounded border bg-gray-100 flex items-center justify-center text-xs text-gray-400">No image</div>'}
                            </td>
                            <td class="px-6 py-4">${product.product_name}</td>
                            <td class="px-6 py-4">${product.brand}</td>
                            <td class="px-6 py-4">${product.oil_type}</td>
                            <td class="px-6 py-4">${product.unit || '1 Liter'}</td>
                            <td class="px-6 py-4">PHP ${Number(product.price).toFixed(2)}</td>
                            <td class="px-6 py-4">${product.inventory ? product.inventory.quantity : 0}</td>
                            <td class="px-6 py-4 space-x-3">
                                <button type="button" onclick="editProduct(${product.product_id})" class="text-blue-600 hover:text-blue-900">Edit</button>
                                <button type="button" onclick="deleteProduct(${product.product_id})" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    `).join('')
                    : '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No products available.</td></tr>';
            } catch (error) {
                showMessage('Failed to load products.', 'error');
            }
        }

        function editProduct(productId) {
            const product = products.find((item) => item.product_id === productId);
            if (product) {
                openForm(product);
            }
        }

        async function deleteProduct(productId) {
            if (!confirm('Delete this product?')) {
                return;
            }

            const response = await fetch(`/admin-api/products/${productId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            showMessage(data.message || 'Delete request completed.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadProducts();
            }
        }

        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const productId = document.getElementById('productId').value;
            const formData = new FormData();
            formData.append('product_name', document.getElementById('product_name').value);
            formData.append('brand', document.getElementById('brand').value);
            formData.append('oil_type', document.getElementById('oil_type').value);
            formData.append('viscosity_grade', document.getElementById('viscosity_grade').value);
            formData.append('unit', document.getElementById('unit').value);
            formData.append('price', document.getElementById('price').value);
            formData.append('reorder_level', document.getElementById('reorder_level').value);
            formData.append('description', document.getElementById('description').value);

            const imageFile = document.getElementById('image').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }

            if (productId) {
                formData.append('_method', 'PUT');
            }

            const response = await fetch(productId ? `/admin-api/products/${productId}` : '/admin-api/products', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();
            showMessage(data.message || 'Product saved.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeForm();
                loadProducts();
            }
        });

        document.getElementById('image').addEventListener('change', (event) => {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreviewWrapper').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });

        async function logout() {
            const response = await fetch('/admin/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/admin/login';
            }
        }

        loadProducts();
    </script>
</body>
</html>
