export const handlePrintInvoice = ({ invoiceNo = null, orderNo = null }) => {
    const number = invoiceNo || orderNo;
    if (!number) return;

    const printUrl = `/api/invoices/print/${number}`;

    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.src = printUrl;

    iframe.onload = () => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();

        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);
    };

    document.body.appendChild(iframe);
};