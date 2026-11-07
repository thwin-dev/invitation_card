/**
 * Offline POS Manager using browser IndexedDB
 * Handles Catalog Caching, Offline Sales Capture, Direct Receipt Printing, and Background Auto-Sync
 */

const DB_NAME = 'pos_offline_db';
const DB_VERSION = 2;

class OfflinePosManager {
    constructor() {
        this.db = null;
        this.isOnline = navigator.onLine;
        this.syncing = false;
        this.listeners = [];
        this.init();
    }

    async init() {
        try {
            this.db = await this.openDb();
            this.bindNetworkListeners();
            if (this.isOnline) {
                this.syncCatalog();
                this.syncPendingSales();
                this.warmUpManagementCache();
            }
        } catch (e) {
            console.error('[OfflinePOS] Init error:', e);
        }
    }

    async warmUpManagementCache() {
        if (!('caches' in window) || !navigator.onLine) return;

        setTimeout(async () => {
            const routes = [
                '/management',
                '/management/sales/create',
                '/management/sales',
                '/management/customers',
                '/management/products',
                '/management/purchases',
                '/management/expenses',
                '/management/reports/financial',
                '/management/stock'
            ];

            try {
                const cache = await caches.open('pos-pwa-cache-v3');

                // 1. Cache all active stylesheets, icons, and scripts
                document.querySelectorAll('link[rel="stylesheet"], script[src]').forEach(el => {
                    const src = el.href || el.src;
                    if (src && src.startsWith(window.location.origin)) {
                        fetch(src).then(res => {
                            if (res && res.status === 200) cache.put(src, res);
                        }).catch(() => {});
                    }
                });

                // 2. Pre-cache key management routes
                for (const path of routes) {
                    try {
                        const existing = await cache.match(path);
                        if (!existing) {
                            const res = await fetch(path, {
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                                }
                            });
                            if (res && res.status === 200) {
                                await cache.put(path, res.clone());
                            }
                        }
                    } catch (err) {
                        // Silently ignore network hiccup during background warm-up
                    }
                }
            } catch (e) {
                console.debug('[OfflinePOS] Pre-cache warm-up note:', e);
            }
        }, 1000);
    }

    openDb() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains('products')) {
                    const prodStore = db.createObjectStore('products', { keyPath: 'id' });
                    prodStore.createIndex('code', 'code', { unique: false });
                    prodStore.createIndex('category_id', 'category_id', { unique: false });
                }
                if (!db.objectStoreNames.contains('categories')) {
                    db.createObjectStore('categories', { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains('customers')) {
                    db.createObjectStore('customers', { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains('pending_sales')) {
                    const salesStore = db.createObjectStore('pending_sales', { keyPath: 'client_transaction_id' });
                    salesStore.createIndex('offline_created_at', 'offline_created_at', { unique: false });
                }
                if (!db.objectStoreNames.contains('held_sales')) {
                    const heldStore = db.createObjectStore('held_sales', { keyPath: 'id' });
                    heldStore.createIndex('held_at', 'held_at', { unique: false });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    bindNetworkListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.notifyStateChange();
            this.syncCatalog();
            this.syncPendingSales();
            this.warmUpManagementCache();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.notifyStateChange();
        });
    }

    notifyStateChange() {
        window.dispatchEvent(new CustomEvent('pos-network-status', {
            detail: { isOnline: this.isOnline, syncing: this.syncing }
        }));
    }

    async syncCatalog() {
        if (!this.isOnline || !window.axios) return false;

        try {
            const response = await window.axios.get('/management/sales/offline-catalog');
            if (response.data && response.data.success) {
                const db = await this.getDb();
                const tx = db.transaction(['products', 'categories', 'customers'], 'readwrite');

                // Clear & populate products
                const prodStore = tx.objectStore('products');
                await this.clearStore(prodStore);
                for (const p of response.data.products || []) {
                    prodStore.put(p);
                }

                // Clear & populate categories
                const catStore = tx.objectStore('categories');
                await this.clearStore(catStore);
                for (const c of response.data.categories || []) {
                    catStore.put(c);
                }

                // Clear & populate customers
                const custStore = tx.objectStore('customers');
                await this.clearStore(custStore);
                for (const cust of response.data.customers || []) {
                    custStore.put(cust);
                }

                localStorage.setItem('pos_catalog_last_synced', new Date().toISOString());
                window.dispatchEvent(new CustomEvent('pos-catalog-synced', {
                    detail: {
                        productCount: (response.data.products || []).length,
                        customerCount: (response.data.customers || []).length,
                    }
                }));
                return response.data;
            }
            return false;
        } catch (e) {
            console.warn('[OfflinePOS] Catalog sync failed (offline or unauthorized):', e.message);
            return false;
        }
    }

    clearStore(store) {
        return new Promise((resolve, reject) => {
            const req = store.clear();
            req.onsuccess = () => resolve();
            req.onerror = () => reject(req.error);
        });
    }

    async getDb() {
        if (!this.db) {
            this.db = await this.openDb();
        }
        return this.db;
    }

    async getAll(storeName) {
        const db = await this.getDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async getPendingSalesCount() {
        const list = await this.getAll('pending_sales');
        return list.length;
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    generateOfflineVoucherCode() {
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const y = now.getFullYear();
        const rand = Math.floor(1000 + Math.random() * 9000);
        return `OFF-VOU-${d}${m}${y}-${rand}`;
    }

    async saveOfflineSale(saleData) {
        const db = await this.getDb();
        const transactionId = this.generateUUID();
        const voucherCode = this.generateOfflineVoucherCode();

        const offlineSale = {
            client_transaction_id: transactionId,
            voucher_code: voucherCode,
            branch_id: saleData.branch_id || null,
            branch_name_snapshot: saleData.branch_name_snapshot || '',
            branch_phone_snapshot: saleData.branch_phone_snapshot || '',
            branch_address_snapshot: saleData.branch_address_snapshot || '',
            currency_code_snapshot: saleData.currency_code_snapshot || 'MMK',
            receipt_header_snapshot: saleData.receipt_header_snapshot || '',
            receipt_footer_snapshot: saleData.receipt_footer_snapshot || '',
            customer_id: saleData.customerId ? parseInt(saleData.customerId) : null,
            customer_name: saleData.customerName || 'Walk-in Customer',
            sale_date: saleData.saleDate || new Date().toISOString().slice(0, 10),
            offline_created_at: new Date().toISOString(),
            paid_amount: parseFloat(saleData.paidAmount || 0),
            total_price: parseFloat(saleData.totalPrice || 0),
            subtotal: parseFloat(saleData.subtotal ?? saleData.totalPrice ?? 0),
            discount_amount: parseFloat(saleData.discountAmount || 0),
            credit_amount: parseFloat(saleData.creditAmount || 0),
            repayment_date: saleData.repaymentDate || null,
            items: saleData.items || [],
            synced: false,
        };

        const tx = db.transaction(['pending_sales', 'products', 'customers'], 'readwrite');
        const salesStore = tx.objectStore('pending_sales');
        const prodStore = tx.objectStore('products');
        const custStore = tx.objectStore('customers');

        salesStore.put(offlineSale);

        // Deduct local stock in products cache
        for (const item of offlineSale.items) {
            const req = prodStore.get(item.unit_id);
            req.onsuccess = () => {
                const prod = req.result;
                if (prod) {
                    prod.stock = Math.max(0, (prod.stock || 0) - item.qty);
                    prodStore.put(prod);
                }
            };
        }

        // Update local customer debt on credit sale
        if (offlineSale.credit_amount > 0 && offlineSale.customer_id) {
            const custReq = custStore.get(offlineSale.customer_id);
            custReq.onsuccess = () => {
                const customer = custReq.result;
                if (customer) {
                    customer.debt = (customer.debt || 0) + offlineSale.credit_amount;
                    customer.available_credit = Math.max(0, (customer.credit_limit || 0) - customer.debt);
                    custStore.put(customer);
                }
            };
        }

        window.dispatchEvent(new CustomEvent('pending-sales-updated'));
        window.dispatchEvent(new CustomEvent('offline-stock-updated'));
        window.dispatchEvent(new CustomEvent('offline-customer-debt-updated'));
        return offlineSale;
    }

    async deletePendingSale(transactionId) {
        const db = await this.getDb();
        const tx = db.transaction('pending_sales', 'readwrite');
        tx.objectStore('pending_sales').delete(transactionId);
        window.dispatchEvent(new CustomEvent('pending-sales-updated'));
    }

    async saveHeldSale(heldData) {
        const db = await this.getDb();
        const id = heldData.id || this.generateUUID();
        const heldRecord = {
            id: id,
            customer_id: heldData.customerId ? parseInt(heldData.customerId) : null,
            customer_name: heldData.customerName || 'Walk-in Customer',
            sale_date: heldData.saleDate || new Date().toISOString().slice(0, 10),
            total_quantity: heldData.items.reduce((s, it) => s + (parseInt(it.qty) || 0), 0),
            total_price: parseFloat(heldData.totalPrice || 0),
            items: heldData.items || [],
            held_at: new Date().toISOString(),
        };

        const tx = db.transaction('held_sales', 'readwrite');
        tx.objectStore('held_sales').put(heldRecord);
        window.dispatchEvent(new CustomEvent('offline-held-sales-updated'));
        return heldRecord;
    }

    async deleteHeldSale(id) {
        const db = await this.getDb();
        const tx = db.transaction('held_sales', 'readwrite');
        tx.objectStore('held_sales').delete(id);
        window.dispatchEvent(new CustomEvent('offline-held-sales-updated'));
    }

    async getHeldSales() {
        return await this.getAll('held_sales');
    }

    async syncPendingSales() {
        if (!navigator.onLine || this.syncing || !window.axios) return;

        const pending = await this.getAll('pending_sales');
        if (pending.length === 0) return;

        this.syncing = true;
        this.notifyStateChange();

        try {
            const payload = {
                sales: pending.map(s => ({
                    client_transaction_id: s.client_transaction_id,
                    voucher_code: s.voucher_code || null,
                    branch_id: s.branch_id || null,
                    customer_id: s.customer_id && parseInt(s.customer_id) > 0 ? parseInt(s.customer_id) : null,
                    sale_date: s.sale_date || new Date().toISOString().slice(0, 10),
                    offline_created_at: s.offline_created_at || new Date().toISOString(),
                    paid_amount: parseFloat(s.paid_amount || 0),
                    repayment_date: s.repayment_date || null,
                    items: (s.items || []).map(it => ({
                        unit_id: parseInt(it.unit_id),
                        qty: parseInt(it.qty) || 1,
                        price: parseFloat(it.price) || 0,
                        discount_rule_id: it.discount_rule_id || null,
                        discount_rule_version: it.discount_rule_version || null,
                        discount_amount: it.discount_amount || 0,
                    })),
                }))
            };
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
            if (csrfMeta && csrfMeta.content) {
                headers['X-CSRF-TOKEN'] = csrfMeta.content;
            }

            const response = await window.axios.post('/management/sales/sync-offline', payload, { headers });

            if (response.data) {
                const synced = response.data.synced_ids || [];
                const failed = response.data.failed || [];

                if (synced.length > 0) {
                    const db = await this.getDb();
                    const tx = db.transaction('pending_sales', 'readwrite');
                    const store = tx.objectStore('pending_sales');

                    for (const id of synced) {
                        store.delete(id);
                    }
                }

                if (window.Livewire) {
                    if (synced.length > 0 && failed.length === 0) {
                        window.Livewire.dispatch('toast', {
                            message: `${synced.length} offline ${synced.length === 1 ? 'sale' : 'sales'} synchronized with server.`,
                            type: 'success',
                            title: 'Sync Complete'
                        });
                    } else if (synced.length > 0 && failed.length > 0) {
                        window.Livewire.dispatch('toast', {
                            message: `${synced.length} synced, ${failed.length} failed: ${failed[0].error}`,
                            type: 'warning',
                            title: 'Partial Sync'
                        });
                    } else if (failed.length > 0) {
                        window.Livewire.dispatch('toast', {
                            message: `Sync failed: ${failed[0].error}`,
                            type: 'error',
                            title: 'Sync Error'
                        });
                    }
                }
            }
        } catch (e) {
            console.error('[OfflinePOS] Auto-sync error:', e);
            const errMsg = e.response?.data?.message || e.message || 'Failed to sync with server.';
            if (window.Livewire) {
                window.Livewire.dispatch('toast', {
                    message: `Sync failed: ${errMsg}`,
                    type: 'error',
                    title: 'Sync Error'
                });
            }
        } finally {
            this.syncing = false;
            this.notifyStateChange();
            window.dispatchEvent(new CustomEvent('pending-sales-updated'));
        }
    }

    printOfflineReceipt(sale) {
        const printWindow = window.open('', '_blank', 'width=400,height=600');
        if (!printWindow) {
            window.print();
            return;
        }

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[char]);
        const currency = escapeHtml(sale.currency_code_snapshot || 'MMK');
        const branchName = escapeHtml(sale.branch_name_snapshot || 'Sale POS');
        const branchAddress = escapeHtml(sale.branch_address_snapshot || '').replace(/\n/g, '<br>');
        const branchPhone = escapeHtml(sale.branch_phone_snapshot || '');
        const receiptHeader = escapeHtml(sale.receipt_header_snapshot || '').replace(/\n/g, '<br>');
        const receiptFooter = escapeHtml(sale.receipt_footer_snapshot || '').replace(/\n/g, '<br>');

        const itemsHtml = sale.items.map(it => `
            <tr>
                <td style="padding:4px 0;">${escapeHtml(it.product || it.name)} (${escapeHtml(it.unit || '')})${it.discount_name ? `<br><small>${escapeHtml(it.discount_name)}: −${Number(it.discount_amount || 0).toFixed(2)} ${currency}</small>` : ''}</td>
                <td style="text-align:center;padding:4px 0;">${it.qty}</td>
                <td style="text-align:right;padding:4px 0;font-family:monospace;">${Number(it.qty * it.price - (it.discount_amount || 0)).toLocaleString(undefined, {minimumFractionDigits:2})} ${currency}</td>
            </tr>
        `).join('');

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Voucher ${sale.voucher_code}</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 12px; margin: 0; padding: 15px; color: #111; }
                    .header { text-align: center; border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin-bottom: 10px; }
                    .badge { display: inline-block; background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; margin-top: 4px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                    th { border-bottom: 1px solid #ddd; padding: 4px 0; text-align: left; }
                    .totals { margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 6px; }
                    .row { display: flex; justify-content: space-between; margin-bottom: 4px; }
                    .bold { font-weight: bold; }
                    .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #666; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h3 style="margin:0;font-size:16px;">${branchName}</h3>
                    ${branchAddress ? `<p style="margin:2px 0;color:#555;">${branchAddress}</p>` : ''}
                    ${branchPhone ? `<p style="margin:2px 0;color:#555;">${branchPhone}</p>` : ''}
                    ${receiptHeader ? `<p style="margin:4px 0;color:#555;">${receiptHeader}</p>` : ''}
                    <p style="margin:2px 0;color:#555;">Offline Sales Voucher</p>
                    <span class="badge">OFFLINE RECEIPT</span>
                    <p style="margin:4px 0;font-family:monospace;font-size:11px;">${escapeHtml(sale.voucher_code)}</p>
                    <p style="margin:2px 0;color:#666;font-size:10px;">${new Date(sale.offline_created_at).toLocaleString()}</p>
                </div>
                <div style="margin-bottom:8px;font-size:11px;">
                    <div><strong>Customer:</strong> ${escapeHtml(sale.customer_name || 'Walk-in Customer')}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align:center;">Qty</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="totals">
                    <div class="row"><span>Subtotal:</span><span>${Number(sale.subtotal ?? sale.total_price).toFixed(2)} ${currency}</span></div>
                    <div class="row"><span>Item discounts:</span><span>−${Number(sale.discount_amount || 0).toFixed(2)} ${currency}</span></div>
                    <div class="row bold" style="font-size:13px;">
                        <span>Total:</span>
                            <span style="font-family:monospace;">${Number(sale.total_price).toLocaleString(undefined, {minimumFractionDigits:2})} ${currency}</span>
                    </div>
                    <div class="row">
                        <span>Paid Amount:</span>
                        <span style="font-family:monospace;">${Number(sale.paid_amount).toLocaleString(undefined, {minimumFractionDigits:2})} ${currency}</span>
                    </div>
                    ${sale.credit_amount > 0 ? `
                        <div class="row" style="color:#b91c1c;font-weight:bold;">
                            <span>Credit (Payable):</span>
                            <span style="font-family:monospace;">${Number(sale.credit_amount).toLocaleString(undefined, {minimumFractionDigits:2})} ${currency}</span>
                        </div>
                    ` : ''}
                </div>
                <div class="footer">
                    <p>${receiptFooter || 'Thank you for your purchase!'}</p>
                </div>
                <script>
                    window.onload = function() { window.print(); }
                </script>
            </body>
            </html>
        `;

        printWindow.document.write(html);
        printWindow.document.close();
    }
}

export function posSaleScreen(initialDate = '', mainBranch = true, receipt = {}) {
    return {
        isOnline: navigator.onLine,
        isMainBranch: Boolean(mainBranch),
        branchReceipt: receipt || {},
        isSyncing: false,
        pendingCount: 0,
        showOfflineModal: false,
        pendingSales: [],
        offlineCart: {},
        offlinePaidAmount: '0',
        offlineSearch: '',
        offlineCategory: '',
        offlineCustomerId: '',
        offlineSaleDate: initialDate || new Date().toISOString().slice(0, 10),

        offlineCustomers: [],
        offlineProducts: [],
        offlineRepaymentDate: '',
        offlineHeldSales: [],
        offlineHeldSaleId: null,

        async init() {
            this.updatePendingCount();
            this.updateOfflineHeldSales();
            this.loadOfflineProducts();
            this.loadOfflineCustomers();

            window.addEventListener('pos-network-status', (e) => {
                this.isOnline = e.detail.isOnline;
                this.isSyncing = e.detail.syncing;
            });
            window.addEventListener('pos-branch-changed', (e) => {
                this.isMainBranch = Boolean(e.detail.isMain);
                this.branchReceipt = e.detail.receipt || {};
            });
            window.addEventListener('pos-catalog-synced', () => {
                this.loadOfflineProducts();
                this.loadOfflineCustomers();
            });
            window.addEventListener('pending-sales-updated', () => {
                this.updatePendingCount();
            });
            window.addEventListener('offline-held-sales-updated', () => {
                this.updateOfflineHeldSales();
            });
            window.addEventListener('offline-stock-updated', () => {
                this.loadOfflineProducts();
            });
            window.addEventListener('offline-customer-debt-updated', () => {
                this.loadOfflineCustomers();
            });
            window.addEventListener('online', async () => {
                this.isOnline = true;
                this.updatePendingCount();
                this.updateOfflineHeldSales();
                this.loadOfflineProducts();
                this.loadOfflineCustomers();
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.updateOfflineHeldSales();
                this.loadOfflineProducts();
                this.loadOfflineCustomers();
            });
        },

        async loadOfflineProducts() {
            if (window.OfflinePos) {
                this.offlineProducts = await window.OfflinePos.getAll('products') || [];
                this.$nextTick(() => window.lucide?.createIcons());
            }
        },

        async loadOfflineCustomers() {
            if (window.OfflinePos) {
                this.offlineCustomers = await window.OfflinePos.getAll('customers') || [];
            }
        },

        getOfflineStock(unitId) {
            const p = this.offlineProducts.find(item => Number(item.id) === Number(unitId));
            return p ? (p.stock ?? 0) : 0;
        },

        getSelectedOfflineCustomer() {
            if (!this.offlineCustomerId) return null;
            return this.offlineCustomers.find(c => String(c.id) === String(this.offlineCustomerId)) || null;
        },

        async refreshCatalogNow() {
            if (!this.isOnline) {
                this.showOfflineToast('Cannot refresh catalog while offline. Connect to internet first.', 'error');
                return;
            }
            if (window.OfflinePos) {
                const res = await window.OfflinePos.syncCatalog();
                if (res && res.success) {
                    await this.loadOfflineProducts();
                    await this.loadOfflineCustomers();
                    this.showOfflineToast(`${res.products.length} products & ${res.customers.length} customers cached for offline.`, 'success');
                }
            }
        },

        handleOfflineSearchEnter() {
            if (!this.canUseOfflineSales()) return;
            if (!this.offlineSearch || !this.offlineSearch.trim()) return;
            const q = this.offlineSearch.trim().toLowerCase();

            // 1. Exact barcode / code match first
            let match = this.offlineProducts.find(p => (p.code && p.code.toLowerCase() === q));

            // 2. Exact product name or single substring match
            if (!match) {
                const matches = this.offlineProducts.filter(p =>
                    (p.product_name && p.product_name.toLowerCase().includes(q)) ||
                    (p.unit_name && p.unit_name.toLowerCase().includes(q))
                );
                if (matches.length === 1) {
                    match = matches[0];
                }
            }

            if (match) {
                const curStock = this.getOfflineStock(match.id);
                if (curStock <= 0) {
                    this.showOfflineToast(`"${match.product_name}" is out of stock (Offline).`, 'error');
                    return;
                }
                this.addToCart({
                    unit_id: match.id,
                    product: match.product_name,
                    unit: match.unit_name,
                    code: match.code || '',
                    stock: curStock,
                    price: match.price || 0
                });
                this.offlineSearch = '';
                this.showOfflineToast(`Added: ${match.product_name} (${match.unit_name})`, 'success');
            } else {
                this.showOfflineToast(`No matching product found for: "${this.offlineSearch}"`, 'error');
            }
        },

        async updatePendingCount() {
            if (window.OfflinePos) {
                this.pendingCount = await window.OfflinePos.getPendingSalesCount();
            }
        },

        async updateOfflineHeldSales() {
            if (window.OfflinePos) {
                this.offlineHeldSales = await window.OfflinePos.getHeldSales() || [];
                this.$nextTick(() => window.lucide?.createIcons());
            }
        },

        async openOfflineModal() {
            if (window.OfflinePos) {
                this.pendingSales = await window.OfflinePos.getAll('pending_sales');
                this.showOfflineModal = true;
            }
        },

        async syncNow() {
            if (window.OfflinePos) {
                await window.OfflinePos.syncPendingSales();
                this.pendingSales = await window.OfflinePos.getAll('pending_sales');
                this.updatePendingCount();
            }
        },

        async deleteOfflineSale(transactionId) {
            if (window.OfflinePos) {
                await window.OfflinePos.deletePendingSale(transactionId);
                this.pendingSales = await window.OfflinePos.getAll('pending_sales');
                this.updatePendingCount();
                this.showOfflineToast('Offline sale discarded from queue.', 'success');
            }
        },

        printOffline(sale) {
            if (window.OfflinePos) {
                window.OfflinePos.printOfflineReceipt(sale);
            }
        },

        addToCart(unit) {
            if (this.isOnline) {
                if (this.$wire) {
                    this.$wire.addUnit(unit.unit_id);
                }
            } else {
                if (!this.canUseOfflineSales()) return;
                const availableStock = this.getOfflineStock(unit.unit_id);
                if (availableStock <= 0) {
                    this.showOfflineToast(`"${unit.product}" is out of stock (Offline).`, 'error');
                    return;
                }

                if (!this.offlineCart[unit.unit_id]) {
                    this.offlineCart[unit.unit_id] = { ...unit, stock: availableStock, qty: 1, discount_rule_id: '' };
                } else {
                    if (this.offlineCart[unit.unit_id].qty >= availableStock) {
                        this.showOfflineToast(`Cannot add more than remaining offline stock (${availableStock}).`, 'error');
                        return;
                    }
                    this.offlineCart[unit.unit_id].qty += 1;
                }
            }
        },

        getOfflineDiscountRules(line) {
            return this.offlineProducts.find(p => Number(p.id) === Number(line.unit_id))?.discount_rules || [];
        },

        getOfflineAutoDiscountRule(line) {
            const gross = Math.round(Number(line.price) * 100) * Number(line.qty);
            return this.getOfflineDiscountRules(line).map(rule => {
                const rate = Math.round(Number(rule.value) * 100);
                const amount = rule.type === 'percentage' ? Math.floor((gross * rate + 5000) / 10000) : rate * Number(line.qty);
                return { rule, amount };
            }).filter(candidate => candidate.amount > 0 && candidate.amount <= gross)
                .sort((a, b) => b.amount - a.amount || Number(a.rule.id) - Number(b.rule.id))[0]?.rule || null;
        },

        getOfflineLineDiscount(line) {
            const rule = this.getOfflineAutoDiscountRule(line);
            if (!rule) return 0;
            const gross = Math.round(Number(line.price) * 100) * Number(line.qty);
            const rate = Math.round(Number(rule.value) * 100);
            return (rule.type === 'percentage' ? Math.floor((gross * rate + 5000) / 10000) : rate * Number(line.qty)) / 100;
        },

        getOfflineGross() {
            return Object.values(this.offlineCart).reduce((sum, it) => sum + Math.round(Number(it.price) * 100) * Number(it.qty), 0) / 100;
        },

        getOfflineDiscount() {
            return Object.values(this.offlineCart).reduce((sum, it) => sum + Math.round(this.getOfflineLineDiscount(it) * 100), 0) / 100;
        },

        getOfflineSubtotal() {
            return Math.round((this.getOfflineGross() - this.getOfflineDiscount()) * 100) / 100;
        },

        validateOfflineDiscounts() {
            for (const line of Object.values(this.offlineCart)) {
                if (this.getOfflineLineDiscount(line) > Math.round(Number(line.price) * 100) * Number(line.qty) / 100) {
                    this.showOfflineToast('An eligible discount exceeds this item amount.');
                    return false;
                }
            }
            return true;
        },

        serializeOfflineItem(line) {
            const rule = this.getOfflineAutoDiscountRule(line);
            return {
                unit_id: Number(line.unit_id), product: line.product, unit: line.unit, code: line.code,
                stock: line.stock, qty: Number(line.qty), price: Number(line.price),
                discount_rule_id: rule?.id || null, discount_rule_version: rule?.version || null,
                discount_name: rule?.name || null, discount_type: rule?.type || null,
                discount_value: rule?.value || 0, discount_amount: this.getOfflineLineDiscount(line)
            };
        },

        getOfflineCredit() {
            const total = this.getOfflineSubtotal();
            const paid = parseFloat(this.offlinePaidAmount || 0);
            return Math.max(0, total - paid);
        },

        getOfflineChange() {
            const total = this.getOfflineSubtotal();
            const paid = parseFloat(this.offlinePaidAmount || 0);
            return Math.max(0, paid - total);
        },

        payFullOffline() {
            this.offlinePaidAmount = String(this.getOfflineSubtotal());
        },

        resetOfflineCart() {
            this.offlineCart = {};
            this.offlinePaidAmount = '0';
            this.offlineRepaymentDate = '';
            this.offlineHeldSaleId = null;
        },

        async holdSaleOffline() {
            if (!this.canUseOfflineSales()) return;
            const cartKeys = Object.keys(this.offlineCart);
            if (cartKeys.length === 0) {
                this.showOfflineToast('Cart is empty. Please add items to hold.');
                return;
            }

            if (!this.validateOfflineDiscounts()) return;
            const items = cartKeys.map(k => this.serializeOfflineItem(this.offlineCart[k]));

            const heldData = {
                id: this.offlineHeldSaleId || null,
                customerId: this.offlineCustomerId || null,
                customerName: this.getSelectedCustomerName(),
                saleDate: this.offlineSaleDate || new Date().toISOString().slice(0, 10),
                totalPrice: this.getOfflineSubtotal(),
                items: items
            };

            await window.OfflinePos.saveHeldSale(heldData);
            this.resetOfflineCart();
            await this.updateOfflineHeldSales();
            this.showOfflineToast('Order held successfully (Offline).', 'success');
        },

        resumeOfflineHeldSale(held) {
            if (!this.canUseOfflineSales()) return;
            this.offlineHeldSaleId = held.id;
            this.offlineCustomerId = held.customer_id ? String(held.customer_id) : '';
            this.offlineSaleDate = held.sale_date;
            this.offlineCart = {};
            for (const item of held.items) {
                this.offlineCart[item.unit_id] = {
                    unit_id: item.unit_id,
                    product: item.product,
                    unit: item.unit,
                    code: item.code || '',
                    stock: item.stock || 999,
                    price: item.price,
                    discount_rule_id: item.discount_rule_id || '',
                    qty: item.qty
                };
            }
            this.showOfflineToast('Held order resumed.', 'success');
        },

        async deleteOfflineHeldSale(id) {
            if (window.OfflinePos) {
                await window.OfflinePos.deleteHeldSale(id);
                if (this.offlineHeldSaleId === id) {
                    this.resetOfflineCart();
                }
                await this.updateOfflineHeldSales();
                this.showOfflineToast('Held order deleted.', 'success');
            }
        },

        showOfflineToast(message, type = 'error') {
            if (window.Livewire) {
                window.Livewire.dispatch('toast', {
                    message: message,
                    type: type,
                    title: type === 'error' ? 'Validation Error' : 'Success'
                });
            } else {
                alert(message);
            }
        },

        canUseOfflineSales() {
            if (this.isMainBranch) return true;
            this.showOfflineToast('Offline selling is currently available from the Main shop only. Reconnect before selling from this location.', 'error');
            return false;
        },

        async handleOfflineComplete() {
            if (!this.canUseOfflineSales()) return;
            const cartKeys = Object.keys(this.offlineCart);
            if (cartKeys.length === 0) {
                this.showOfflineToast('Cart is empty. Please add items to cart.');
                return;
            }

            // Check stock availability again right before completing
            for (const k of cartKeys) {
                const item = this.offlineCart[k];
                const avail = this.getOfflineStock(item.unit_id);
                if (item.qty > avail) {
                    this.showOfflineToast(`"${item.product}" only has ${avail} items left in stock.`, 'error');
                    return;
                }
            }

            const totalPrice = this.getOfflineSubtotal();
            const paidAmount = parseFloat(this.offlinePaidAmount || 0);

            if (isNaN(paidAmount) || paidAmount < 0) {
                this.showOfflineToast('Please enter a valid paid amount.');
                return;
            }

            // Walk-in sales MUST be paid in full
            if (!this.offlineCustomerId) {
                if (paidAmount < totalPrice) {
                    this.showOfflineToast('Walk-in sales must be paid in full.');
                    return;
                }
            } else {
                // Registered customer credit validations
                const customer = this.offlineCustomers.find(c => String(c.id) === String(this.offlineCustomerId));
                const creditAmount = Math.max(0, totalPrice - paidAmount);

                if (creditAmount > 0) {
                    if (customer && !customer.allow_credit) {
                        this.showOfflineToast('This customer is not allowed to buy on credit.');
                        return;
                    }

                    if (customer && customer.credit_limit > 0 && ((customer.debt || 0) + creditAmount) > customer.credit_limit) {
                        this.showOfflineToast('Sale exceeds this customer\'s available credit limit.');
                        return;
                    }

                    if (!this.offlineRepaymentDate) {
                        this.showOfflineToast('Repayment date is required when there is remaining credit.');
                        return;
                    }
                }
            }

            if (!this.validateOfflineDiscounts()) return;
            const items = cartKeys.map(k => this.serializeOfflineItem(this.offlineCart[k]));

            const finalPaid = Math.min(paidAmount, totalPrice);
            const finalCredit = Math.max(0, totalPrice - finalPaid);

            const salePayload = {
                customerId: this.offlineCustomerId || null,
                customerName: this.getSelectedCustomerName(),
                saleDate: this.offlineSaleDate || new Date().toISOString().slice(0, 10),
                paidAmount: paidAmount,
                totalPrice: totalPrice,
                subtotal: this.getOfflineGross(),
                discountAmount: this.getOfflineDiscount(),
                creditAmount: finalCredit,
                repaymentDate: finalCredit > 0 ? this.offlineRepaymentDate : null,
                ...this.branchReceipt,
                items: items
            };

            const savedSale = await window.OfflinePos.saveOfflineSale(salePayload);
            window.OfflinePos.printOfflineReceipt(savedSale);

            if (this.offlineHeldSaleId) {
                await window.OfflinePos.deleteHeldSale(this.offlineHeldSaleId);
                this.offlineHeldSaleId = null;
                await this.updateOfflineHeldSales();
            }

            this.resetOfflineCart();
            await this.updatePendingCount();
            await this.loadOfflineProducts();
            await this.loadOfflineCustomers();

            if (window.Livewire) {
                window.Livewire.dispatch('toast', {
                    message: 'Offline sale completed and voucher printed.',
                    type: 'success',
                    title: 'Sale Completed'
                });
            }
        },

        getSelectedCustomerName() {
            if (!this.isOnline && this.offlineCustomerId) {
                const c = this.getSelectedOfflineCustomer();
                if (c) return c.name;
            }
            const select = document.querySelector('select[name="pos_customer_select"]');
            if (select && select.selectedIndex > 0) {
                return select.options[select.selectedIndex].text;
            }
            return 'Walk-in Customer';
        }
    };
}

export function posSaleTable() {
    return {
        offlinePendingSales: [],
        async init() {
            this.loadOfflinePending();
            window.addEventListener('pending-sales-updated', () => this.loadOfflinePending());
            window.addEventListener('pos-network-status', () => this.loadOfflinePending());
        },
        async loadOfflinePending() {
            if (window.OfflinePos) {
                this.offlinePendingSales = await window.OfflinePos.getAll('pending_sales') || [];
                this.$nextTick(() => window.lucide?.createIcons());
            }
        },
        printOfflineReceipt(sale) {
            if (window.OfflinePos) {
                window.OfflinePos.printOfflineReceipt(sale);
            }
        }
    };
}

window.posSaleScreen = posSaleScreen;
window.posSaleTable = posSaleTable;
window.OfflinePos = new OfflinePosManager();
export default window.OfflinePos;
