@extends('layouts.admin')

@section('title', 'Products - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Products</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Everything on sale, and the sizes each one comes in.</p>
        </div>
        <div class="flex gap-3">
            {{-- Bulk Import temporarily disabled
            <button onclick="openImportModal()" class="bg-slate-800 text-white px-4 py-2 rounded hover:bg-slate-900">
                Bulk Import
            </button>
            --}}
            <button id="addProductBtn" onclick="openForm()" class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">
                Add product
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
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)] sm:px-6">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Price</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Stock</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[var(--muted)] sm:px-6">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsBody" class="divide-y divide-[var(--line)] bg-[var(--card)]">
                    <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-[var(--muted)]">Loading products...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="productsPagination"></div>
    </div>

    {{-- Add and edit, in two steps: fill it in, then look at what the shop
         will show before it goes live. --}}
    <div id="productModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">

            <div class="flex items-start justify-between gap-3 border-b border-[var(--line)] px-6 py-5">
                <div>
                    <h3 id="modalTitle" class="text-lg font-bold text-[var(--ink)]">Add product</h3>
                    <p id="modalStep" class="mt-0.5 text-xs text-[var(--muted)]">Step 1 of 2 &middot; the details</p>
                </div>
                <button type="button" onclick="closeForm()" aria-label="Close"
                        class="rounded-lg p-1.5 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="productForm" class="flex-1 space-y-7 overflow-y-auto px-6 py-6">
                <input type="hidden" id="productId">
                <div id="formErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <section>
                    <h4 class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">What it is</h4>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="product_name" class="block text-sm font-medium text-[var(--ink)]">Product name</label>
                            <input type="text" id="product_name" required maxlength="200"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <p id="error_product_name" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="brand" class="block text-sm font-medium text-[var(--ink)]">Brand</label>
                            <input type="text" id="brand" required maxlength="100" list="brandOptions"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <datalist id="brandOptions"></datalist>
                            <p id="error_brand" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="oil_type" class="block text-sm font-medium text-[var(--ink)]">Product type</label>
                            <select id="oil_type" required
                                    class="mt-1.5 block w-full rounded-xl border border-[var(--line)] bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <option value="Synthetic">Synthetic</option>
                                <option value="Semi-Synthetic">Semi-Synthetic</option>
                                <option value="Mineral">Mineral</option>
                                <option value="Coolant">Coolant</option>
                                <option value="Other">Other</option>
                            </select>
                            <p id="error_oil_type" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="viscosity_grade" class="block text-sm font-medium text-[var(--ink)]">
                                Viscosity grade <span class="font-normal text-[var(--muted)]">(optional)</span>
                            </label>
                            <input type="text" id="viscosity_grade" maxlength="20" placeholder="5W-30"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <p class="mt-1 text-xs text-[var(--muted)]">Leave empty for coolants and fluids that do not carry one.</p>
                            <p id="error_viscosity_grade" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                    </div>
                </section>

                {{-- The shop shows one card per product line and asks for the
                     pack on the product page, so this is what decides whether
                     a new bottle joins an existing page or opens its own. --}}
                <section class="border-t border-[var(--line)] pt-6">
                    <h4 class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">How it is sold</h4>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-[var(--line)] px-4 py-3 transition hover:border-[var(--primary)]">
                            <input type="radio" name="sold_as" value="own" checked onchange="soldAsChanged()" class="mt-1 accent-[var(--primary)]">
                            <span>
                                <span class="block text-sm font-semibold text-[var(--ink)]">On its own</span>
                                <span class="block text-xs leading-5 text-[var(--muted)]">Its own card in the shop, its own page.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-[var(--line)] px-4 py-3 transition hover:border-[var(--primary)]">
                            <input type="radio" name="sold_as" value="pack" onchange="soldAsChanged()" class="mt-1 accent-[var(--primary)]">
                            <span>
                                <span class="block text-sm font-semibold text-[var(--ink)]">Another size of an existing product</span>
                                <span class="block text-xs leading-5 text-[var(--muted)]">Shares one page; the shopper picks the size there.</span>
                            </span>
                        </label>
                    </div>

                    <div id="lineChooser" class="mt-4 hidden">
                        <label for="product_line" class="block text-sm font-medium text-[var(--ink)]">Which product is this a size of?</label>
                        <select id="product_line" onchange="lineChosen()"
                                class="mt-1.5 block w-full rounded-xl border border-[var(--line)] bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]"></select>
                        <p id="lineNote" class="mt-1.5 hidden text-xs leading-5 text-[var(--primary)]"></p>
                        <p id="error_product_line" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="unit" class="block text-sm font-medium text-[var(--ink)]">Pack size</label>
                            <input type="text" id="unit" maxlength="50" value="1 Liter" list="unitOptions"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <datalist id="unitOptions">
                                <option value="1 Liter"></option>
                                <option value="4 Liters"></option>
                                <option value="5 Liters"></option>
                                <option value="20 Liters"></option>
                                <option value="200 Liters"></option>
                            </datalist>
                            <p id="error_unit" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-[var(--ink)]">Price (PHP)</label>
                            <input type="number" id="price" min="0" step="1" required
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <p id="error_price" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label for="reorder_level" class="block text-sm font-medium text-[var(--ink)]">Reorder level</label>
                            <input type="number" id="reorder_level" min="0" value="10"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <p id="error_reorder_level" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-[var(--line)] pt-6">
                    <h4 class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">What it says on the page</h4>

                    <div class="mt-3">
                        <label for="description" class="block text-sm font-medium text-[var(--ink)]">Description</label>
                        <textarea id="description" rows="5" maxlength="1000"
                                  class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]"></textarea>
                        <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs leading-5 text-[var(--muted)]">Use the manufacturer's own words where you can. <span id="descriptionCount"></span></p>
                        </div>
                        <p id="error_description" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>

                    <div class="mt-4">
                        <label for="source_url" class="block text-sm font-medium text-[var(--ink)]">
                            Manufacturer's page <span class="font-normal text-[var(--muted)]">(optional)</span>
                        </label>
                        <input type="url" id="source_url" maxlength="255" placeholder="https://canroyallubricant.com/product/..."
                               class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                        <p class="mt-1 text-xs leading-5 text-[var(--muted)]">
                            Shown at the foot of the product page as "published by the manufacturer", with a link. It is what lets a
                            customer check the claims against the source.
                        </p>
                        <p id="error_source_url" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                </section>

                {{-- The shop never crops or stretches a product shot: it fits
                     the whole picture into the box on white. So this shows the
                     real box rather than offering a crop that would not be
                     used, and says when a picture is too small to hold up. --}}
                <section class="border-t border-[var(--line)] pt-6">
                    <h4 class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Pictures</h4>

                    <div class="mt-3 grid gap-5 sm:grid-cols-[1fr_17rem]">
                        <div>
                            <label class="inline-flex cursor-pointer items-center rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                                Choose main picture
                                <input type="file" id="image" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="pickImage(event, 1)">
                            </label>
                            <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                                JPG, PNG or WEBP. Large photographs are shrunk here before they are sent, so a phone picture is fine.
                            </p>
                            <p id="imageNote" class="mt-1.5 hidden text-xs font-semibold text-[var(--primary)]"></p>
                            <p id="imageWarning" class="mt-1.5 hidden text-xs font-semibold text-amber-700"></p>
                            <p id="error_image" class="mt-1 hidden text-xs text-red-600"></p>

                            <div class="mt-5">
                                <label class="inline-flex cursor-pointer items-center rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                                    Choose second picture
                                    <input type="file" id="image_2" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="pickImage(event, 2)">
                                </label>
                                <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                                    Optional. Shown as a thumbnail on the product page &mdash; usually the box or a group shot.
                                </p>
                                <div id="image2Wrap" class="mt-3 hidden">
                                    <img id="image2Preview" src="" alt="" class="h-16 w-20 rounded-lg border border-[var(--line)] bg-white object-contain">
                                </div>
                                <p id="error_image_2" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">In the shop</p>
                            <div class="mt-2 overflow-hidden rounded-[1.25rem] border border-[var(--line)] shadow-sm">
                                <div id="imageFitBox" class="flex h-64 items-center justify-center bg-white p-3">
                                    <span class="rounded-full bg-[var(--surface)] px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No image</span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                                The whole picture is fitted into this box on white. Nothing is cropped or stretched, so the shape of
                                the photograph decides how much white sits around it.
                            </p>
                        </div>
                    </div>
                </section>
            </form>

            {{-- Step two: the shop's own card, built from what was typed. --}}
            <div id="productReview" class="hidden flex-1 space-y-5 overflow-y-auto px-6 py-6">
                <div class="rounded-xl border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-3 text-sm text-[var(--primary)]">
                    <span id="reviewLead" class="font-semibold"></span>
                </div>

                <div class="grid gap-6 sm:grid-cols-[19rem_1fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">The card shoppers see</p>
                        <div id="reviewCard" class="mt-2"></div>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Everything being saved</p>
                        <dl id="reviewFields" class="mt-2 divide-y divide-[var(--line)] overflow-hidden rounded-xl border border-[var(--line)] text-sm"></dl>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--line)] px-6 py-4">
                <span id="reviewQuestion" class="mr-auto hidden text-sm font-semibold text-[var(--ink)]">Add this product?</span>
                <button type="button" id="btnCancel" onclick="closeForm()"
                        class="rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                <button type="button" id="btnBack" onclick="backToForm()"
                        class="hidden rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">No, keep editing</button>
                <button type="button" id="btnReview" onclick="reviewProduct()"
                        class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Preview</button>
                <button type="button" id="btnConfirm" onclick="submitProduct()"
                        class="hidden rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-50">Yes, add it</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
        let products = [];
        let messageTimeout;

        /* Pictures are shrunk in the browser before they are sent. A phone
           photograph is several megabytes and the server refuses anything
           over two, so without this an ordinary photo simply bounced. */
        const IMAGE_MAX_EDGE = 1200;
        const IMAGE_SOFT_EDGE = 500;

        const FIELDS = ['product_name', 'brand', 'oil_type', 'viscosity_grade', 'unit', 'price',
                        'reorder_level', 'description', 'source_url', 'product_line', 'image', 'image_2'];

        let productLines = [];
        let pendingImage = null;
        let pendingImage2 = null;
        let existingImageUrl = null;

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

            pendingImage = null;
            pendingImage2 = null;
            existingImageUrl = null;

            document.querySelector('input[name="sold_as"][value="own"]').checked = true;
            soldAsChanged();

            ['imageNote', 'imageWarning', 'image2Wrap', 'lineNote'].forEach(id =>
                document.getElementById(id).classList.add('hidden'));

            drawFitBox(null);
            backToForm();
            clearFormErrors();
        }

        function clearFormErrors() {
            const errorBox = document.getElementById('formErrors');
            errorBox.classList.add('hidden');
            errorBox.innerHTML = '';

            FIELDS.forEach((field) => {
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
            const modal = document.getElementById('productModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
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
            const modal = document.getElementById('productModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            resetForm();
            loadLines();

            document.getElementById('modalTitle').textContent = product ? 'Edit product' : 'Add product';
            document.getElementById('btnConfirm').textContent = product ? 'Yes, save it' : 'Yes, add it';

            if (!product) return;

            document.getElementById('productId').value = product.product_id;
            document.getElementById('product_name').value = product.product_name;
            document.getElementById('brand').value = product.brand;
            document.getElementById('oil_type').value = product.oil_type;
            document.getElementById('viscosity_grade').value = product.viscosity_grade || '';
            document.getElementById('unit').value = product.unit || '1 Liter';
            document.getElementById('price').value = parseInt(product.price);
            document.getElementById('reorder_level').value = product.reorder_level;
            document.getElementById('description').value = product.description || '';
            document.getElementById('source_url').value = product.source_url
                || (product.specifications && product.specifications.source) || '';

            existingImageUrl = product.image_url || null;
            drawFitBox(existingImageUrl);

            if (product.image_2_url) {
                document.getElementById('image2Preview').src = product.image_2_url;
                document.getElementById('image2Wrap').classList.remove('hidden');
            }
        }

        /* ------------------------------------------------ product lines --- */

        async function loadLines() {
            try {
                const response = await fetch('/admin-api/products/lines', { headers: { Accept: 'application/json' } });
                const data = await response.json();
                productLines = data.data || [];
            } catch (error) {
                productLines = [];
            }

            document.getElementById('product_line').innerHTML =
                '<option value="">Choose a product...</option>'
                + productLines.map(line => `<option value="${escapeHtml(line.product_line)}">`
                    + `${escapeHtml(line.name)} (${line.packs.map(p => escapeHtml(p.unit)).join(', ')})</option>`).join('');

            const brands = [...new Set(productLines.map(line => line.brand))];
            document.getElementById('brandOptions').innerHTML =
                brands.map(b => `<option value="${escapeHtml(b)}"></option>`).join('');
        }

        function soldAs() {
            return document.querySelector('input[name="sold_as"]:checked').value;
        }

        function soldAsChanged() {
            const joining = soldAs() === 'pack';
            document.getElementById('lineChooser').classList.toggle('hidden', !joining);

            if (!joining) {
                document.getElementById('product_line').value = '';
                document.getElementById('lineNote').classList.add('hidden');
            }
        }

        /**
         * Joining a line copies everything the sizes share.
         *
         * The three Patrol 5W30 bottles carry one name, one description and
         * one picture between them -- only the pack size and the price
         * differ. Typing those again by hand is how they drift apart.
         */
        function lineChosen() {
            const line = productLines.find(l => l.product_line === document.getElementById('product_line').value);
            const note = document.getElementById('lineNote');

            if (!line) {
                note.classList.add('hidden');
                return;
            }

            document.getElementById('product_name').value = line.name;
            document.getElementById('brand').value = line.brand;
            document.getElementById('oil_type').value = line.oil_type;
            document.getElementById('viscosity_grade').value = line.viscosity_grade || '';
            document.getElementById('description').value = line.description || '';
            document.getElementById('source_url').value = line.source_url || '';

            if (line.image_url && !pendingImage) {
                existingImageUrl = line.image_url;
                drawFitBox(line.image_url);
            }

            note.textContent = `Copied from the ${line.packs.length} size${line.packs.length === 1 ? '' : 's'} already on sale `
                + `(${line.packs.map(p => p.unit).join(', ')}). Set the new size and its price below.`;
            note.classList.remove('hidden');
        }

        /* ------------------------------------------------------ pictures --- */

        /**
         * Fitted, never cropped, on white.
         *
         * The shop draws product shots with object-contain, so the whole
         * picture is always shown and the shape of it decides how much white
         * sits around it. Flattening onto white here as well means a
         * transparent PNG does not arrive with a black background.
         */
        function fitImage(file) {
            return new Promise((resolve, reject) => {
                const url = URL.createObjectURL(file);
                const image = new Image();

                image.onload = () => {
                    URL.revokeObjectURL(url);

                    const longest = Math.max(image.naturalWidth, image.naturalHeight);

                    if (!longest) {
                        reject(new Error('That file is not an image we can read.'));
                        return;
                    }

                    const scale = Math.min(1, IMAGE_MAX_EDGE / longest);
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(image.naturalWidth * scale);
                    canvas.height = Math.round(image.naturalHeight * scale);

                    const context = canvas.getContext('2d');
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                    context.drawImage(image, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob(
                        (blob) => blob
                            ? resolve({ blob, width: canvas.width, height: canvas.height,
                                        shortest: Math.min(image.naturalWidth, image.naturalHeight) })
                            : reject(new Error('That image could not be read.')),
                        'image/jpeg',
                        0.88
                    );
                };

                image.onerror = () => {
                    URL.revokeObjectURL(url);
                    reject(new Error('That file is not an image we can read.'));
                };

                image.src = url;
            });
        }

        async function pickImage(event, slot) {
            const file = event.target.files[0];
            event.target.value = '';
            clearFormErrors();

            if (!file) return;

            try {
                const fitted = await fitImage(file);
                const prepared = new File([fitted.blob], `product-${slot}.jpg`, { type: 'image/jpeg' });

                if (slot === 2) {
                    pendingImage2 = prepared;
                    document.getElementById('image2Preview').src = URL.createObjectURL(fitted.blob);
                    document.getElementById('image2Wrap').classList.remove('hidden');
                    return;
                }

                pendingImage = prepared;
                drawFitBox(URL.createObjectURL(fitted.blob));

                const note = document.getElementById('imageNote');
                note.textContent = `Ready to save: ${fitted.width} x ${fitted.height}, `
                    + `${Math.round(prepared.size / 1024)} KB (from ${Math.round(file.size / 1024)} KB).`;
                note.classList.remove('hidden');

                // Small pictures are not refused -- they are stretched to fill
                // a 420 pixel box on the product page, and that is what looks
                // wrong, so it is said here rather than discovered later.
                const warning = document.getElementById('imageWarning');
                warning.classList.toggle('hidden', fitted.shortest >= IMAGE_SOFT_EDGE);
                warning.textContent = `This picture is ${fitted.shortest}px on its short side. `
                    + `Under ${IMAGE_SOFT_EDGE}px it will look soft on the product page.`;
            } catch (error) {
                const field = document.getElementById(slot === 2 ? 'error_image_2' : 'error_image');
                field.textContent = error.message;
                field.classList.remove('hidden');
            }
        }

        function drawFitBox(url) {
            document.getElementById('imageFitBox').innerHTML = url
                ? `<img src="${url}" alt="" class="h-full w-full object-contain">`
                : '<span class="rounded-full bg-[var(--surface)] px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No image</span>';
        }

        /* -------------------------------------------------- the review --- */

        function formValues() {
            const value = (id) => document.getElementById(id).value.trim();

            return {
                product_name: value('product_name'),
                brand: value('brand'),
                oil_type: value('oil_type'),
                viscosity_grade: value('viscosity_grade'),
                unit: value('unit') || '1 Liter',
                price: value('price'),
                reorder_level: value('reorder_level'),
                description: value('description'),
                source_url: value('source_url'),
                product_line: soldAs() === 'pack' ? value('product_line') : '',
            };
        }

        /** The shop's own card, drawn from what was typed into the form. */
        function reviewProduct() {
            clearFormErrors();

            const form = document.getElementById('productForm');
            if (!form.reportValidity()) return;

            const v = formValues();

            if (soldAs() === 'pack' && !v.product_line) {
                showFormErrors({ product_line: ['Choose the product this is a size of.'] });
                return;
            }

            const line = productLines.find(l => l.product_line === v.product_line);
            const packs = line
                ? [...line.packs.map(p => ({ unit: p.unit, price: p.price })), { unit: v.unit, price: Number(v.price), isNew: true }]
                    .sort((a, b) => a.price - b.price)
                : [{ unit: v.unit, price: Number(v.price), isNew: true }];

            const from = Math.min(...packs.map(p => p.price));
            const imageUrl = pendingImage ? URL.createObjectURL(pendingImage) : existingImageUrl;

            document.getElementById('reviewLead').textContent = line
                ? `This joins "${line.name}" as a ${v.unit} pack. The page will offer ${packs.length} sizes.`
                : 'This becomes a product of its own, with its own card and page.';

            document.getElementById('reviewCard').innerHTML = `
                <article class="flex flex-col overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
                    ${imageUrl
                        ? `<img src="${imageUrl}" alt="" class="h-64 w-full bg-white object-contain p-3">`
                        : `<div class="flex h-64 items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))]"><span class="rounded-full bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Image</span></div>`}
                    <div class="flex flex-1 flex-col p-5">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h3 class="mb-1 line-clamp-2 text-lg font-black leading-snug text-[var(--ink)]">${escapeHtml(v.product_name)}</h3>
                                <p class="truncate text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">${escapeHtml(v.brand)}</p>
                            </div>
                            <span class="flex-shrink-0 rounded-full bg-[var(--primary-soft)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-[var(--primary)]">${escapeHtml(v.oil_type)}</span>
                        </div>
                        <p class="mb-3 truncate text-sm text-[var(--muted)]">${escapeHtml(v.viscosity_grade || 'Standard')}</p>
                        <div class="mb-3 flex flex-wrap gap-1.5">
                            ${packs.map(p => `<span class="rounded-md border px-2 py-0.5 text-[11px] font-semibold ${p.isNew
                                ? 'border-[var(--primary)] bg-[var(--primary)] text-white'
                                : 'border-[var(--primary-soft)] bg-[var(--primary-soft)] text-[var(--primary)]'}">${escapeHtml(p.unit)}</span>`).join('')}
                        </div>
                        <div class="mt-auto">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-2xl font-black text-[var(--primary)]">
                                    ${packs.length > 1 ? '<span class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--muted)]">from</span> ' : ''}PHP ${Number(from).toLocaleString()}
                                </span>
                                <span class="rounded-full bg-[#f3f6f8] px-3 py-1 text-xs font-semibold text-[var(--muted)]">Stock: 0</span>
                            </div>
                            <div class="h-px bg-[var(--line)]"></div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-xs font-bold uppercase tracking-[0.25em] text-[var(--muted)]">View Details</span>
                                <span class="rounded-xl bg-[var(--primary)] px-4 py-2 text-sm font-bold text-white">${packs.length > 1 ? 'Choose Size' : 'Add to Cart'}</span>
                            </div>
                        </div>
                    </div>
                </article>
                <p class="mt-2 text-xs leading-5 text-[var(--muted)]">Stock reads zero because a new product starts empty. Add stock from Inventory.</p>`;

            const rows = [
                ['Name', v.product_name],
                ['Brand', v.brand],
                ['Type', v.oil_type],
                ['Viscosity grade', v.viscosity_grade || 'none'],
                ['Pack size', v.unit],
                ['Price', 'PHP ' + Number(v.price).toLocaleString()],
                ['Reorder level', v.reorder_level || '10'],
                ['Sold as', line ? `a size of ${line.name}` : 'a product of its own'],
                ['Description', v.description ? v.description.slice(0, 120) + (v.description.length > 120 ? '...' : '') : 'none'],
                ['Manufacturer link', v.source_url || 'none'],
                ['Main picture', pendingImage ? 'new upload' : (existingImageUrl ? 'unchanged' : 'none')],
                ['Second picture', pendingImage2 ? 'new upload' : 'unchanged or none'],
            ];

            document.getElementById('reviewFields').innerHTML = rows.map(([label, val]) => `
                <div class="flex items-start justify-between gap-4 px-4 py-2.5">
                    <dt class="text-[var(--muted)]">${escapeHtml(label)}</dt>
                    <dd class="text-right font-medium text-[var(--ink)]">${escapeHtml(String(val))}</dd>
                </div>`).join('');

            document.getElementById('productForm').classList.add('hidden');
            document.getElementById('productReview').classList.remove('hidden');
            document.getElementById('modalStep').textContent = 'Step 2 of 2 - how it will look';
            document.getElementById('btnReview').classList.add('hidden');
            document.getElementById('btnCancel').classList.add('hidden');
            document.getElementById('btnBack').classList.remove('hidden');
            document.getElementById('btnConfirm').classList.remove('hidden');
            document.getElementById('reviewQuestion').classList.remove('hidden');
        }

        function backToForm() {
            document.getElementById('productForm').classList.remove('hidden');
            document.getElementById('productReview').classList.add('hidden');
            document.getElementById('modalStep').textContent = 'Step 1 of 2 - the details';
            document.getElementById('btnReview').classList.remove('hidden');
            document.getElementById('btnCancel').classList.remove('hidden');
            document.getElementById('btnBack').classList.add('hidden');
            document.getElementById('btnConfirm').classList.add('hidden');
            document.getElementById('reviewQuestion').classList.add('hidden');
        }

        let showArchived = false;

        function renderProducts() {
            const tbody = document.getElementById('productsBody');

            // The server has already searched the whole catalogue. Filtering
            // again here only hid rows it had matched on viscosity grade.
            tbody.innerHTML = products.length
                ? products.map((product) => {
                    const stock = product.inventory ? product.inventory.quantity : 0;
                    const low = stock > 0 && stock <= product.reorder_level;

                    return `
                    <tr class="transition hover:bg-[var(--surface)]">
                        <td class="px-5 py-3 sm:px-6">
                            <div class="flex items-center gap-3">
                                ${product.image_url
                                    ? `<img src="${product.image_url}" alt="" class="h-12 w-12 shrink-0 rounded-lg border border-[var(--line)] bg-white object-contain p-1">`
                                    : '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-[var(--line)] bg-[var(--surface)] text-[9px] font-semibold uppercase tracking-wider text-[var(--muted)]">No<br>image</div>'}
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-[var(--ink)]" title="${escapeHtml(product.product_name)}">${escapeHtml(product.product_name)}</div>
                                    <div class="text-xs text-[var(--muted)]">${escapeHtml(product.brand)}${product.unit ? ' &middot; ' + escapeHtml(product.unit) : ''}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full bg-[var(--primary-soft)] px-2.5 py-1 text-xs font-bold text-[var(--primary)]">${escapeHtml(product.oil_type)}</span>
                            ${product.viscosity_grade ? `<div class="mt-1 text-xs text-[var(--muted)]">${escapeHtml(product.viscosity_grade)}</div>` : ''}
                        </td>
                        <td class="px-5 py-3 text-sm font-semibold text-[var(--ink)]">PHP ${Number(product.price).toLocaleString()}</td>
                        <td class="px-5 py-3">
                            <span class="text-sm font-bold ${stock <= 0 ? 'text-red-700' : (low ? 'text-amber-700' : 'text-[var(--ink)]')}">${stock}</span>
                            <div class="text-xs text-[var(--muted)]">reorder at ${product.reorder_level}</div>
                        </td>
                        <td class="px-5 py-3 text-right sm:px-6">
                            <div class="inline-flex gap-2">
                                ${showArchived
                                    ? `<button type="button" onclick="restoreProduct(${product.product_id})" class="rounded-lg border border-[var(--line)] px-2.5 py-1.5 text-xs font-bold text-[var(--primary)] transition hover:border-[var(--primary)]">Restore</button>`
                                    : `<button type="button" onclick="editProduct(${product.product_id})" class="rounded-lg border border-[var(--line)] px-2.5 py-1.5 text-xs font-bold text-[var(--primary)] transition hover:border-[var(--primary)]">Edit</button>
                                       <button type="button" onclick="archiveProduct(${product.product_id})" class="rounded-lg border border-[var(--line)] px-2.5 py-1.5 text-xs font-bold text-red-700 transition hover:border-red-300">Archive</button>`}
                            </div>
                        </td>
                    </tr>`;
                }).join('')
                : `<tr><td colspan="5" class="px-6 py-10 text-center text-sm text-[var(--muted)]">${showArchived ? 'Nothing archived.' : 'Nothing matches that.'}</td></tr>`;
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

            const sure = await askToConfirm({
                title: `Archive ${name}?`,
                body: 'It disappears from the shop and from this catalogue. Its past orders stay intact, and you can restore it from the Archived tab whenever you like.',
                confirm: 'Archive it',
                cancel: 'Leave it on sale',
            });

            if (!sure) return;

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

        async function submitProduct() {
            const confirmButton = document.getElementById('btnConfirm');
            confirmButton.disabled = true;

            const productId = document.getElementById('productId').value;
            const values = formValues();
            const body = new FormData();

            Object.entries(values).forEach(([field, value]) => body.append(field, value));

            if (pendingImage) body.append('image', pendingImage);
            if (pendingImage2) body.append('image_2', pendingImage2);
            if (productId) body.append('_method', 'PUT');

            try {
                const response = await fetch(productId ? `/admin-api/products/${productId}` : '/admin-api/products', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });

                const data = await response.json();

                if (!response.ok) {
                    // Back to the form, because that is where the fields are.
                    backToForm();
                    showFormErrors(data.errors || { form: [data.message || 'Could not save.'] });
                    return;
                }

                showMessage(data.message || 'Saved.');
                closeForm();
                loadProducts();
            } catch (error) {
                backToForm();
                showFormErrors({ form: ['Could not save just now. Try again.'] });
            } finally {
                confirmButton.disabled = false;
            }
        }

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
