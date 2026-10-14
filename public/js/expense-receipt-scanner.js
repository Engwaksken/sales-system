(function () {
    'use strict';

    const TESSERACT_URL =
        'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js';

    let tesseractPromise = null;

    function loadTesseract() {
        if (window.Tesseract) {
            return Promise.resolve(window.Tesseract);
        }

        if (tesseractPromise) {
            return tesseractPromise;
        }

        tesseractPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = TESSERACT_URL;
            script.async = true;
            script.crossOrigin = 'anonymous';

            script.onload = () => {
                if (window.Tesseract) {
                    resolve(window.Tesseract);
                } else {
                    reject(new Error('Receipt OCR library could not be started.'));
                }
            };

            script.onerror = () => reject(
                new Error('Receipt OCR library could not be downloaded. Check your internet connection.')
            );

            document.head.appendChild(script);
        });

        return tesseractPromise;
    }

    function cleanText(value) {
        return String(value || '')
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+/g, ' ')
            .trim();
    }

    function linesFrom(text) {
        return String(text || '')
            .split(/\r?\n/)
            .map(cleanText)
            .filter(Boolean);
    }

    function normalizeMoney(value) {
        if (value == null) return null;

        let cleaned = String(value)
            .replace(/[^\d,.\-]/g, '')
            .trim();

        if (!cleaned) return null;

        const commaCount = (cleaned.match(/,/g) || []).length;
        const dotCount = (cleaned.match(/\./g) || []).length;

        if (commaCount > 0 && dotCount === 0) {
            const lastComma = cleaned.lastIndexOf(',');
            const decimals = cleaned.length - lastComma - 1;

            cleaned = decimals === 2
                ? cleaned.replace(/\./g, '').replace(',', '.')
                : cleaned.replace(/,/g, '');
        } else {
            cleaned = cleaned.replace(/,/g, '');
        }

        const number = Number.parseFloat(cleaned);

        return Number.isFinite(number) ? number : null;
    }

    function moneyCandidates(line) {
        const matches = String(line || '').match(
            /(?:UGX|UShs?|Shs?|\$|USD|EUR|GBP)?\s*-?\d[\d,]*(?:\.\d{1,2})?/gi
        ) || [];

        return matches
            .map(normalizeMoney)
            .filter(value => Number.isFinite(value) && value >= 0);
    }

    function extractAmount(lines) {
        const priorityPatterns = [
            /\bgrand\s*total\b/i,
            /\bamount\s*due\b/i,
            /\bnet\s*total\b/i,
            /\btotal\s*amount\b/i,
            /\btotal\b/i,
            /\bamount\b/i,
        ];

        for (const pattern of priorityPatterns) {
            for (let index = lines.length - 1; index >= 0; index--) {
                const line = lines[index];

                if (!pattern.test(line)) continue;

                const values = moneyCandidates(line);

                if (values.length) {
                    return Math.max(...values);
                }

                if (index + 1 < lines.length) {
                    const nextValues = moneyCandidates(lines[index + 1]);
                    if (nextValues.length) return Math.max(...nextValues);
                }
            }
        }

        const all = lines
            .flatMap(moneyCandidates)
            .filter(value => value >= 100);

        return all.length ? Math.max(...all) : null;
    }

    function isoDate(year, month, day) {
        const y = Number(year);
        const m = Number(month);
        const d = Number(day);

        if (!y || !m || !d || m > 12 || d > 31) return '';

        const fullYear = y < 100 ? (y >= 70 ? 1900 + y : 2000 + y) : y;

        return [
            String(fullYear).padStart(4, '0'),
            String(m).padStart(2, '0'),
            String(d).padStart(2, '0'),
        ].join('-');
    }

    function extractDate(text) {
        const source = String(text || '');

        let match = source.match(
            /\b(20\d{2}|19\d{2})[-/.](0?[1-9]|1[0-2])[-/.](0?[1-9]|[12]\d|3[01])\b/
        );

        if (match) {
            return isoDate(match[1], match[2], match[3]);
        }

        match = source.match(
            /\b(0?[1-9]|[12]\d|3[01])[-/.](0?[1-9]|1[0-2])[-/.](\d{2,4})\b/
        );

        if (match) {
            return isoDate(match[3], match[2], match[1]);
        }

        const monthMap = {
            jan: 1, january: 1, feb: 2, february: 2,
            mar: 3, march: 3, apr: 4, april: 4,
            may: 5, jun: 6, june: 6, jul: 7, july: 7,
            aug: 8, august: 8, sep: 9, sept: 9, september: 9,
            oct: 10, october: 10, nov: 11, november: 11,
            dec: 12, december: 12,
        };

        match = source.match(
            /\b(0?[1-9]|[12]\d|3[01])\s+([A-Za-z]{3,9})\s+(20\d{2}|19\d{2})\b/i
        );

        if (match) {
            const month = monthMap[match[2].toLowerCase()];
            if (month) return isoDate(match[3], month, match[1]);
        }

        return '';
    }

    function isMerchantCandidate(line) {
        if (!line || line.length < 3 || line.length > 80) return false;
        if (!/[A-Za-z]/.test(line)) return false;
        if (/^(receipt|tax invoice|invoice|cash sale|duplicate|customer copy)$/i.test(line)) return false;
        if (/^(date|time|tel|phone|tin|vat|total|subtotal|amount|qty|quantity)\b/i.test(line)) return false;
        if (/^\W*\d[\d\s.,:/-]*$/.test(line)) return false;

        return true;
    }

    function extractPayee(lines) {
        const top = lines.slice(0, 10);

        for (const line of top) {
            if (isMerchantCandidate(line)) {
                return line
                    .replace(/\s{2,}/g, ' ')
                    .replace(/[|]+/g, '')
                    .trim();
            }
        }

        return '';
    }

    function extractReference(text) {
        const source = String(text || '');

        const patterns = [
            /\b(?:receipt|rcpt|invoice|inv|transaction|txn|reference|ref)\s*(?:no\.?|number|#|:)?\s*([A-Z0-9][A-Z0-9/_-]{2,})/i,
            /\b(?:no\.?|#)\s*([A-Z0-9][A-Z0-9/_-]{3,})\b/i,
        ];

        for (const pattern of patterns) {
            const match = source.match(pattern);
            if (match) return match[1];
        }

        return '';
    }

    function extractPaymentMethod(text) {
        const source = String(text || '').toLowerCase();

        if (/\b(mtn|airtel|mobile\s*money|momo)\b/.test(source)) return 'Mobile Money';
        if (/\b(visa|mastercard|master card|card|pos)\b/.test(source)) return 'Card';
        if (/\b(bank|transfer|eft|rtgs)\b/.test(source)) return 'Bank Transfer';
        if (/\bcash\b/.test(source)) return 'Cash';

        return '';
    }

    function inferCategory(text) {
        const source = String(text || '').toLowerCase();

        const rules = [
            ['Fuel', /\b(fuel|petrol|diesel|shell|total energies|gas station)\b/],
            ['Transport', /\b(transport|taxi|uber|safeboda|boda|bus|parking)\b/],
            ['Meals', /\b(food|restaurant|cafe|lunch|dinner|breakfast|meal)\b/],
            ['Utilities', /\b(umeme|nwsc|water bill|electricity|power bill)\b/],
            ['Internet', /\b(internet|wifi|data bundle|airtel data|mtn data)\b/],
            ['Airtime', /\b(airtime|recharge)\b/],
            ['Rent', /\b(rent|landlord|lease)\b/],
            ['Office Supplies', /\b(stationery|printing|paper|toner|ink|office supplies)\b/],
            ['Repairs & Maintenance', /\b(repair|maintenance|service charge|spare parts)\b/],
            ['Communication', /\b(telephone|phone bill|communication)\b/],
        ];

        for (const [category, pattern] of rules) {
            if (pattern.test(source)) return category;
        }

        return '';
    }

    function receiptData(text) {
        const lines = linesFrom(text);
        const payee = extractPayee(lines);
        const date = extractDate(text);
        const amount = extractAmount(lines);
        const reference = extractReference(text);
        const paymentMethod = extractPaymentMethod(text);
        const category = inferCategory(text);

        return {
            payee,
            date,
            amount,
            reference,
            paymentMethod,
            category,
            description: [
                payee ? `Receipt from ${payee}` : 'Scanned receipt',
                reference ? `Ref: ${reference}` : '',
            ].filter(Boolean).join(' — '),
        };
    }

    function findForm(root) {
        const inside = root.closest('form');
        if (inside) return inside;

        const selectors = [
            'form[action*="expenses"]',
            'form[id*="expense" i]',
            'form[class*="expense" i]',
        ];

        for (const selector of selectors) {
            const form = document.querySelector(selector);
            if (form) return form;
        }

        const amountField = document.querySelector(
            '[name="amount"],#amount,[name="expense_amount"],#expense_amount'
        );

        return amountField?.closest('form') || null;
    }

    function findField(form, candidates) {
        if (!form) return null;

        for (const value of candidates) {
            const byName = form.querySelector(`[name="${CSS.escape(value)}"]`);
            if (byName) return byName;

            const byId = form.querySelector(`#${CSS.escape(value)}`);
            if (byId) return byId;
        }

        return null;
    }

    function setValue(field, value) {
        if (!field || value === '' || value == null) return false;

        if (field.tagName === 'SELECT') {
            const wanted = cleanText(value).toLowerCase();

            const exact = [...field.options].find(option =>
                cleanText(option.value).toLowerCase() === wanted ||
                cleanText(option.textContent).toLowerCase() === wanted
            );

            const fuzzy = exact || [...field.options].find(option =>
                cleanText(option.textContent).toLowerCase().includes(wanted) ||
                wanted.includes(cleanText(option.textContent).toLowerCase())
            );

            if (!fuzzy) return false;
            field.value = fuzzy.value;
        } else {
            field.value = value;
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));

        return true;
    }

    function fillExpenseForm(form, data) {
        const mapping = {
            date: [
                'expense_date', 'date', 'transaction_date'
            ],
            payee: [
                'payee', 'vendor', 'supplier', 'merchant', 'paid_to'
            ],
            amount: [
                'amount', 'expense_amount', 'total_amount'
            ],
            reference: [
                'reference', 'reference_no', 'receipt_no',
                'receipt_number', 'transaction_reference'
            ],
            paymentMethod: [
                'payment_method', 'method', 'payment_type'
            ],
            category: [
                'category', 'expense_category', 'category_id'
            ],
            description: [
                'description', 'details', 'notes', 'expense_description'
            ],
        };

        const applied = {};

        for (const [key, candidates] of Object.entries(mapping)) {
            const field = findField(form, candidates);
            const value = data[key];

            applied[key] = setValue(
                field,
                key === 'amount' && Number.isFinite(value)
                    ? value.toFixed(2)
                    : value
            );
        }

        return applied;
    }

    function copyFileToReceiptInput(sourceInput, targetInput) {
        if (!sourceInput?.files?.length || !targetInput) return;

        if (sourceInput === targetInput) return;

        try {
            const transfer = new DataTransfer();
            transfer.items.add(sourceInput.files[0]);
            targetInput.files = transfer.files;
        } catch (_) {
            // Some older browsers don't allow assigning FileList.
        }
    }

    function flash(root, message, type) {
        const box = root.querySelector('[data-ers-message]');
        if (!box) return;

        box.classList.remove('hidden', 'is-error', 'is-success');
        box.classList.add(type === 'error' ? 'is-error' : 'is-success');
        box.innerHTML = message;

        window.clearTimeout(box._ersTimer);
        box._ersTimer = window.setTimeout(() => {
            box.style.transition = 'opacity .5s ease';
            box.style.opacity = '0';

            window.setTimeout(() => {
                box.classList.add('hidden');
                box.style.opacity = '1';
            }, 500);
        }, 5000);
    }

    function updateProgress(root, progress, label) {
        const wrap = root.querySelector('[data-ers-progress]');
        const bar = root.querySelector('[data-ers-progress-bar]');
        const value = root.querySelector('[data-ers-progress-value]');
        const labelEl = root.querySelector('[data-ers-progress-label]');

        wrap?.classList.remove('hidden');

        const pct = Math.max(0, Math.min(100, Math.round(progress * 100)));

        if (bar) bar.style.width = `${pct}%`;
        if (value) value.textContent = `${pct}%`;
        if (labelEl && label) {
            labelEl.innerHTML =
                `<i class="fas fa-spinner fa-spin"></i> ${label}`;
        }
    }

    async function processReceipt(root, input) {
        const file = input?.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            flash(root, '<i class="fas fa-triangle-exclamation"></i> Please select a JPG, PNG or WebP receipt image.', 'error');
            return;
        }

        const form = findForm(root);

        if (!form) {
            flash(root, '<i class="fas fa-triangle-exclamation"></i> Expense form could not be found on this page.', 'error');
            return;
        }

        form.enctype = 'multipart/form-data';

        const cameraInput = root.querySelector('[data-ers-camera-input]');
        copyFileToReceiptInput(input, cameraInput);

        const preview = root.querySelector('[data-ers-preview]');
        const resultWrap = root.querySelector('[data-ers-result]');
        const rawWrap = root.querySelector('[data-ers-raw-wrap]');
        const rawText = root.querySelector('[data-ers-raw-text]');
        const clearBtn = root.querySelector('[data-ers-clear]');

        if (preview) {
            preview.src = URL.createObjectURL(file);
        }

        try {
            updateProgress(root, 0.03, 'Preparing receipt scanner...');

            const Tesseract = await loadTesseract();

            const result = await Tesseract.recognize(
                file,
                'eng',
                {
                    logger(message) {
                        if (
                            message.status === 'recognizing text' &&
                            typeof message.progress === 'number'
                        ) {
                            updateProgress(
                                root,
                                message.progress,
                                'Reading receipt text...'
                            );
                        }
                    },
                }
            );

            const text = result?.data?.text || '';

            if (!cleanText(text)) {
                throw new Error(
                    'No readable text was found. Retake the photo with good lighting and keep the receipt flat.'
                );
            }

            const data = receiptData(text);
            const applied = fillExpenseForm(form, data);

            root.querySelector('[data-ers-progress]')?.classList.add('hidden');
            resultWrap?.classList.remove('hidden');
            rawWrap?.classList.remove('hidden');
            clearBtn?.classList.remove('hidden');

            if (rawText) rawText.textContent = text;

            const setText = (selector, value) => {
                const element = root.querySelector(selector);
                if (element) element.textContent = value || '—';
            };

            setText('[data-ers-summary-payee]', data.payee);
            setText('[data-ers-summary-date]', data.date);
            setText(
                '[data-ers-summary-amount]',
                Number.isFinite(data.amount)
                    ? `UGX ${data.amount.toLocaleString('en-UG', { maximumFractionDigits: 2 })}`
                    : '—'
            );
            setText('[data-ers-summary-reference]', data.reference);

            const count = Object.values(applied).filter(Boolean).length;

            flash(
                root,
                `<i class="fas fa-circle-check"></i> Receipt scanned. ${count} expense field${count === 1 ? '' : 's'} filled automatically. Please review before saving.`,
                'success'
            );
        } catch (error) {
            console.error('Expense receipt OCR failed:', error);

            root.querySelector('[data-ers-progress]')?.classList.add('hidden');

            flash(
                root,
                `<i class="fas fa-triangle-exclamation"></i> ${
                    error?.message || 'Receipt could not be read.'
                }`,
                'error'
            );
        }
    }

    function clearScanner(root) {
        const form = findForm(root);
        const cameraInput = root.querySelector('[data-ers-camera-input]');
        const uploadInput = root.querySelector('[data-ers-upload-input]');

        if (cameraInput) cameraInput.value = '';
        if (uploadInput) uploadInput.value = '';

        root.querySelector('[data-ers-result]')?.classList.add('hidden');
        root.querySelector('[data-ers-raw-wrap]')?.classList.add('hidden');
        root.querySelector('[data-ers-progress]')?.classList.add('hidden');
        root.querySelector('[data-ers-clear]')?.classList.add('hidden');

        const message = root.querySelector('[data-ers-message]');
        if (message) message.classList.add('hidden');

        if (form) {
            const receiptField = form.querySelector('[name="receipt_image"]');
            if (receiptField) receiptField.value = '';
        }
    }

    function mount(root) {
        if (!root || root.dataset.ersMounted === '1') return;

        root.dataset.ersMounted = '1';

        const cameraButton = root.querySelector('[data-ers-camera]');
        const uploadButton = root.querySelector('[data-ers-upload]');
        const clearButton = root.querySelector('[data-ers-clear]');
        const cameraInput = root.querySelector('[data-ers-camera-input]');
        const uploadInput = root.querySelector('[data-ers-upload-input]');

        cameraButton?.addEventListener('click', () => cameraInput?.click());
        uploadButton?.addEventListener('click', () => uploadInput?.click());
        clearButton?.addEventListener('click', () => clearScanner(root));

        cameraInput?.addEventListener('change', () => processReceipt(root, cameraInput));
        uploadInput?.addEventListener('change', () => processReceipt(root, uploadInput));
    }

    function boot() {
        document
            .querySelectorAll('[data-expense-receipt-scanner]')
            .forEach(mount);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Supports dynamically rendered expense modals.
    const observer = new MutationObserver(() => boot());
    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });
})();
