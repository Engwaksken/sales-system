<div id="expenseReceiptScanner"
     class="expense-receipt-scanner"
     data-expense-receipt-scanner>
    <div class="ers-head">
        <div>
            <div class="ers-title">
                <i class="fas fa-receipt"></i>
                Scan / Upload Receipt
            </div>
            <div class="ers-subtitle">
                Take a photo or upload a receipt. NSales will read it and fill the expense form automatically.
            </div>
        </div>

        <span class="ers-badge">
            <i class="fas fa-wand-magic-sparkles"></i>
            Auto-fill
        </span>
    </div>

    <div class="ers-actions">
        <button type="button"
                class="ers-btn ers-btn-primary"
                data-ers-camera>
            <i class="fas fa-camera"></i>
            Scan Receipt
        </button>

        <button type="button"
                class="ers-btn ers-btn-secondary"
                data-ers-upload>
            <i class="fas fa-image"></i>
            Upload Receipt
        </button>

        <button type="button"
                class="ers-btn ers-btn-ghost hidden"
                data-ers-clear>
            <i class="fas fa-rotate-left"></i>
            Clear
        </button>
    </div>

    <input type="file"
           name="receipt_image"
           accept="image/jpeg,image/png,image/webp"
           capture="environment"
           class="hidden"
           data-ers-camera-input>

    <input type="file"
           accept="image/jpeg,image/png,image/webp"
           class="hidden"
           data-ers-upload-input>

    <div class="ers-progress hidden" data-ers-progress>
        <div class="ers-progress-top">
            <span data-ers-progress-label>
                <i class="fas fa-spinner fa-spin"></i>
                Reading receipt...
            </span>
            <strong data-ers-progress-value>0%</strong>
        </div>
        <div class="ers-progress-track">
            <div class="ers-progress-bar" data-ers-progress-bar></div>
        </div>
        <p>
            Keep this page open while the receipt is being analysed.
        </p>
    </div>

    <div class="ers-result hidden" data-ers-result>
        <div class="ers-preview-wrap">
            <img alt="Selected receipt preview"
                 class="ers-preview"
                 data-ers-preview>
        </div>

        <div class="ers-summary">
            <div class="ers-summary-title">
                <i class="fas fa-circle-check"></i>
                Receipt read successfully
            </div>

            <div class="ers-summary-grid">
                <div>
                    <span>Merchant / Payee</span>
                    <strong data-ers-summary-payee>—</strong>
                </div>
                <div>
                    <span>Date</span>
                    <strong data-ers-summary-date>—</strong>
                </div>
                <div>
                    <span>Total Amount</span>
                    <strong data-ers-summary-amount>—</strong>
                </div>
                <div>
                    <span>Reference</span>
                    <strong data-ers-summary-reference>—</strong>
                </div>
            </div>

            <div class="ers-note">
                <i class="fas fa-circle-info"></i>
                Review the automatically filled fields before saving the expense.
            </div>
        </div>
    </div>

    <details class="ers-raw hidden" data-ers-raw-wrap>
        <summary>
            <i class="fas fa-file-lines"></i>
            View text read from receipt
        </summary>
        <pre data-ers-raw-text></pre>
    </details>

    <div class="ers-message hidden" data-ers-message></div>
</div>

<link rel="stylesheet" href="<?php echo e(asset('css/expense-receipt-scanner.css')); ?>">
<script src="<?php echo e(asset('js/expense-receipt-scanner.js')); ?>" defer></script>
<?php /**PATH /home/kemmytec/nsales/resources/views/expenses/receipt-scanner.blade.php ENDPATH**/ ?>