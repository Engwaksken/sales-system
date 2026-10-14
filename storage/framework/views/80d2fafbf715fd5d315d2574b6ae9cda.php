<script>
(function () {
    const names = {
        branch_id: 'Branch Name',
        category_id: 'Category Name',
        supplier_id: 'Supplier Name',
        product_id: 'Product Name',
        customer_id: 'Customer Name',
        user_id: 'User Name',
        created_by: 'Created By Name',
        updated_by: 'Updated By Name',
        received_by: 'Received By Name',
        sale_id: 'Invoice / Sale',
        order_id: 'Order Number',
        role_id: 'Role Name',
        account_id: 'Account Name',
        journal_id: 'Journal Number'
    };

    function normalize() {
        document.querySelectorAll('select[name]').forEach(select => {
            const raw = select.name.replace(/\[\]$/, '');
            const field = raw.includes('[')
                ? raw.substring(raw.lastIndexOf('[') + 1).replace(']', '')
                : raw;

            const display = names[field];
            if (!display) return;

            const container = select.closest('div');
            const label = container?.querySelector('label');

            if (label && /\bId\b/i.test(label.textContent || '')) {
                label.textContent = display;
            }

            const first = select.options?.[0];
            if (first && /\bId\b/i.test(first.textContent || '')) {
                first.textContent = '-- Select '+display.replace(' Name','')+' --';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', normalize);
    new MutationObserver(normalize)
        .observe(document.documentElement, {childList:true, subtree:true});
})();
</script>
<?php /**PATH /home/kemmytec/nsales/resources/views/partials/relationship-name-labels.blade.php ENDPATH**/ ?>