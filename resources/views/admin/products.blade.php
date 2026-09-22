@extends('layouts.admin')

@section('title', 'RANEY LUBRICANTS TRADING — Admin')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black text-[var(--ink)] sm:text-3xl">Products</h1>
            <p class="text-[var(--muted)]">Create, update, and remove products from the catalog.</p>
        </div>
        <div class="flex gap-3">
            {{-- Bulk Import temporarily disabled
            <button onclick="openImportModal()" class="bg-slate-800 text-white px-4 py-2 rounded hover:bg-slate-900">
                Bulk Import
            </button>
            --}}
            <button id="addProductBtn" onclick="openForm()" class="bg-[var(--primary)] text-white px-4 py-2 rounded hover:bg-[var(--primary-dark)]">
                Add Product
            </button>
        </div>
    </div>

    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-[var(--line)] flex flex-wrap gap-3 items-center">
            <input type="text" id="productSearch" oninput="searchProducts()" placeholder="Search product, brand, or type..." class="rounded-xl border border-[var(--line)] px-3 py-2 text-sm w-full sm:w-72 focus:outline-none">

            <div class="ml-auto inline-flex rounded-xl border border-[var(--line)] p-1">
                <button type="button" id="tabActive" onclick="setArchivedView(false)"
                        class="rounded-lg px-4 py-1.5 text-sm font-semibold transition">Active</button>
                <button type="button" id="tabArchived" onclick="setArchivedView(true)"
                        class="rounded-lg px-4 py-1.5 text-sm font-semibold transition">Archived</button>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="min-w-full">
                <thead class="bg-[var(--surface)]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Image</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Product Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Brand</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsBody" class="bg-[var(--card)] divide-y divide-[var(--line)]">
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading products...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="productsPagination"></div>
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
            <button type="submit" class="bg-[var(--primary)] text-white px-4 py-2 rounded hover:bg-[var(--primary-dark)]">Save Product</button>
        </div>
    </form>
</div>
    </div>

@endsection

@push('scripts')
<script>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
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

        /* Bulk Import JS temporarily disabled
        function openImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.add('hidden');
            document.getElementById('importSummary').classList.add('hidden');
            document.getElementById('importSummary').innerHTML = '';
            document.getElementById('catalog_text').value = '';
        }
        */

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
            document.getElementById('price').value = parseInt(product.price);
            document.getElementById('reorder_level').value = product.reorder_level;
            document.getElementById('description').value = product.description || '';
            if (product.image_url) {
                document.getElementById('imagePreview').src = product.image_url;
                document.getElementById('imagePreviewWrapper').classList.remove('hidden');
            }
        }

        let showArchived = false;

        function renderProducts() {
            const tbody = document.getElementById('productsBody');

            // The server has already searched the whole catalogue. Filtering
            // again here only hid rows it had matched on viscosity grade.
            tbody.innerHTML = products.length
                ? products.map((product) => `
                    <tr>
                        <td class="px-6 py-4">
                            ${product.image_url
                                ? `<img src="${product.image_url}" alt="${escapeHtml(product.product_name)}" class="h-14 w-14 rounded border object-cover">`
                                : '<div class="h-14 w-14 rounded border bg-gray-100 flex items-center justify-center text-xs text-gray-400">No image</div>'}
                        </td>
                        <td class="px-6 py-4">${escapeHtml(product.product_name)}</td>
                        <td class="px-6 py-4">${escapeHtml(product.brand)}</td>
                        <td class="px-6 py-4">${escapeHtml(product.oil_type)}</td>
                        <td class="px-6 py-4">${escapeHtml(product.unit || '1 Liter')}</td>
                        <td class="px-6 py-4">PHP ${Number(product.price).toFixed(2)}</td>
                        <td class="px-6 py-4">${product.inventory ? product.inventory.quantity : 0}</td>
                        <td class="px-6 py-4 space-x-3 whitespace-nowrap">
                            ${showArchived
                                ? `<button type="button" onclick="restoreProduct(${product.product_id})" class="font-semibold text-[var(--primary)] hover:text-[var(--primary-dark)]">Restore</button>`
                                : `<button type="button" onclick="editProduct(${product.product_id})" class="text-[var(--primary)] hover:text-[var(--primary-dark)]">Edit</button>
                                   <button type="button" onclick="archiveProduct(${product.product_id})" class="text-red-600 hover:text-red-900">Archive</button>`}
                        </td>
                    </tr>
                `).join('')
                : `<tr><td colspan="8" class="px-6 py-4 text-center text-[var(--muted)]">${showArchived ? 'Nothing archived.' : 'No matching products found.'}</td></tr>`;
        }

        async function loadProducts(page = 1) {
            const params = new URLSearchParams({ page });
            const search = document.getElementById('productSearch').value.trim();
            if (search) params.set('search', search);
            if (showArchived) params.set('archived', '1');

            const response = await fetch(`/admin-api/products?${params}`, { headers: { Accept: 'application/json' } });
            if (response.status === 401) { window.location.href = '/admin/login'; return; }

            const data = await response.json();
            products = data.data || [];

            renderProducts();
            renderPagination('productsPagination', data.meta, loadProducts);
        }

        const searchProducts = debounce(() => loadProducts(1));

        function editProduct(productId) {
            const product = products.find((item) => item.product_id === productId);
            if (product) {
                openForm(product);
            }
        }

        async function archiveProduct(productId) {
            const product = products.find((item) => item.product_id === productId);
            const name = product ? product.product_name : 'this product';

            if (!confirm(`Archive ${name}?\n\nIt disappears from the shop and the catalogue, but its past orders stay intact and you can restore it later.`)) {
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
            showMessage(data.message || 'Archive request completed.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadProducts();
            }
        }

        async function restoreProduct(productId) {
            const response = await fetch(`/admin-api/products/${productId}/restore`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            showMessage(data.message || 'Restore request completed.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadProducts();
            }
        }

        function setArchivedView(archived) {
            showArchived = archived;

            const on = 'bg-[var(--primary)] text-white';
            const off = 'text-[var(--muted)] hover:text-[var(--ink)]';

            document.getElementById('tabActive').className = `rounded-lg px-4 py-1.5 text-sm font-semibold transition ${archived ? off : on}`;
            document.getElementById('tabArchived').className = `rounded-lg px-4 py-1.5 text-sm font-semibold transition ${archived ? on : off}`;

            // Adding a product while looking at the archive makes no sense.
            document.getElementById('addProductBtn').classList.toggle('hidden', archived);

            loadProducts(1);
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

        /* submitCatalogImport temporarily disabled
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
        */

        // Paints the toggle and loads the active list.
        setArchivedView(false);
</script>
@endpush
