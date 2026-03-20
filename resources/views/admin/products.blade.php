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
                <div class="flex gap-3">
                    <button onclick="openImportModal()" class="bg-slate-800 text-white px-4 py-2 rounded hover:bg-slate-900">
                        Bulk Import
                    </button>
                    <button onclick="openForm()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add Product
                    </button>
                </div>
            </div>

            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

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
            <div id="formErrors" class="hidden mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            <form id="productForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" id="productId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" id="product_name" required class="mt-1 block w-full rounded-md border px-3 py-2">
                    <p id="error_product_name" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Brand</label>
                    <input type="text" id="brand" required class="mt-1 block w-full rounded-md border px-3 py-2">
                    <p id="error_brand" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Product Type</label>
                    <select id="oil_type" required class="mt-1 block w-full rounded-md border px-3 py-2">
                        <option value="Synthetic">Synthetic</option>
                        <option value="Semi-Synthetic">Semi-Synthetic</option>
                        <option value="Mineral">Mineral</option>
                        <option value="Coolant">Coolant</option>
                        <option value="Other">Other</option>
                    </select>
                    <p id="error_oil_type" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Viscosity Grade</label>
                    <input type="text" id="viscosity_grade" class="mt-1 block w-full rounded-md border px-3 py-2" placeholder="5W-40">
                    <p id="error_viscosity_grade" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit</label>
                    <input type="text" id="unit" class="mt-1 block w-full rounded-md border px-3 py-2" value="1 Liter">
                    <p id="error_unit" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" id="price" min="0" step="1" required class="mt-1 block w-full rounded-md border px-3 py-2">
                    <p id="error_price" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reorder Level</label>
                    <input type="number" id="reorder_level" min="0" class="mt-1 block w-full rounded-md border px-3 py-2" value="10">
                    <p id="error_reorder_level" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" rows="4" class="mt-1 block w-full resize-none rounded-md border px-3 py-2"></textarea>
                    <p id="error_description" class="mt-1 hidden text-sm text-red-600"></p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Product Image</label>
                    <input type="file" id="image" accept="image/*" class="mt-1 block w-full rounded-md border px-3 py-2">
                    <p class="mt-1 text-xs text-gray-500">Accepted: JPG, JPEG, PNG, WEBP. Max file size: 2 MB.</p>
                    <p id="error_image" class="mt-1 hidden text-sm text-red-600"></p>
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

    <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold">Bulk Import Product List</h3>
                    <p class="text-sm text-gray-500">Paste brand headings with product lines. Optional format: `Product Name | Price | Stock | Reorder`</p>
                </div>
                <button type="button" onclick="closeImportModal()" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>

            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <p class="font-semibold">Example:</p>
                <pre class="mt-2 whitespace-pre-wrap text-xs">CANROYAL PRODUCTS
Fully Synthetic Gasoline/Diesel Engine Oil SAE 5W30 API SN/CJ-4 1L | 450 | 20 | 5
Diesel Engine Oil SAE 15W40 API CI4/SJ 5L | 1500 | 8 | 3</pre>
            </div>

            <textarea id="catalog_text" rows="16" class="w-full resize-none rounded-lg border border-slate-300 px-4 py-3 font-mono text-sm" placeholder="Paste your brand headings and product list here..."></textarea>
            <div id="importSummary" class="hidden mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="closeImportModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                <button type="button" onclick="submitCatalogImport()" class="bg-slate-800 text-white px-4 py-2 rounded hover:bg-slate-900">Import Catalog</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let products = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Catalog Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function resetForm() {
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('unit').value = '1 Liter';
            document.getElementById('reorder_level').value = '10';
            document.getElementById('imagePreview').src = '';
            document.getElementById('imagePreviewWrapper').classList.add('hidden');
            clearFormErrors();
        }

        function clearFormErrors() {
            const errorBox = document.getElementById('formErrors');
            errorBox.classList.add('hidden');
            errorBox.innerHTML = '';

            ['product_name', 'brand', 'oil_type', 'viscosity_grade', 'unit', 'price', 'reorder_level', 'description', 'image'].forEach((field) => {
                const errorText = document.getElementById(`error_${field}`);
                const input = document.getElementById(field);

                if (errorText) {
                    errorText.classList.add('hidden');
                    errorText.textContent = '';
                }

                if (input) {
                    input.classList.remove('border-red-500', 'ring-2', 'ring-red-100');
                }
            });
        }

        function showFormErrors(errors = {}) {
            clearFormErrors();

            const entries = Object.entries(errors);
            if (!entries.length) {
                return;
            }

            const errorBox = document.getElementById('formErrors');
            errorBox.innerHTML = entries.map(([, messages]) => `<div>${messages[0]}</div>`).join('');
            errorBox.classList.remove('hidden');

            entries.forEach(([field, messages]) => {
                const errorText = document.getElementById(`error_${field}`);
                const input = document.getElementById(field);

                if (errorText) {
                    errorText.textContent = messages[0];
                    errorText.classList.remove('hidden');
                }

                if (input) {
                    input.classList.add('border-red-500', 'ring-2', 'ring-red-100');
                }
            });
        }

        function closeForm() {
            document.getElementById('productModal').classList.add('hidden');
            resetForm();
        }

        function openImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.add('hidden');
            document.getElementById('importSummary').classList.add('hidden');
            document.getElementById('importSummary').innerHTML = '';
            document.getElementById('catalog_text').value = '';
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
            if (!response.ok && data.errors) {
                showFormErrors(data.errors);
            } else {
                clearFormErrors();
            }

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

        async function submitCatalogImport() {
            const catalogText = document.getElementById('catalog_text').value.trim();

            if (!catalogText) {
                showMessage('Paste a product list before importing.', 'error');
                return;
            }

            const response = await fetch('/admin-api/products/import', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ catalog_text: catalogText })
            });

            const data = await response.json();
            showMessage(data.message || 'Catalog import completed.', response.ok ? 'success' : 'error');

            if (!response.ok) {
                return;
            }

            const summary = document.getElementById('importSummary');
            const skipped = data.data?.skipped || [];
            summary.innerHTML = `
                <div>Added: <strong>${data.data?.imported ?? 0}</strong></div>
                <div>Updated: <strong>${data.data?.updated ?? 0}</strong></div>
                ${skipped.length ? `<div class="mt-2"><strong>Skipped:</strong><br>${skipped.join('<br>')}</div>` : ''}
            `;
            summary.classList.remove('hidden');

            loadProducts();
        }

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
