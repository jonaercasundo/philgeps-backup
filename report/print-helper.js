/**
 * Universal print function for tables
 * @param {string} tableId - ID of the table to print
 * @param {string} title - Report title
 * @param {string} subtitle - Report subtitle (optional)
 * @param {object} options - Additional options (optional)
 */
function printTable(tableId, title, subtitle = '', options = {}) {
    // Default options
    const defaultOptions = {
        showDate: true,
        showHeader: true,
        customStyles: '',
        pageOrientation: 'portrait'
    };
    
    const config = { ...defaultOptions, ...options };
    
    // Store original content
    const originalContent = document.body.innerHTML;
    
    // Get the table
    const table = document.getElementById(tableId);
    if (!table) {
        alert('Table not found!');
        return;
    }
    
    // Create header
    let headerHTML = '';
    if (config.showHeader) {
        headerHTML = `
            <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px;">
                <h1 style="margin: 0 0 10px 0; font-size: 24px;">${title}</h1>
                ${subtitle ? `<h2 style="margin: 0 0 10px 0; font-size: 18px; color: #666;">${subtitle}</h2>` : ''}
                ${config.showDate ? `<p style="margin: 0; font-size: 14px; color: #888;">Generated on: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>` : ''}
            </div>
        `;
    }
    
    // Create print content
    const printContent = `
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            ${headerHTML}
            ${table.outerHTML}
        </div>
    `;
    
    // Replace body content
    document.body.innerHTML = printContent;
    
    // Add print-specific styling
    const style = document.createElement('style');
    style.innerHTML = `
        @media print {
            body { margin: 0; padding: 20px; }
            table { 
                width: 100% !important; 
                border-collapse: collapse !important;
                font-size: 14px;
            }
            th, td { 
                border: 1px solid #000 !important; 
                padding: 12px !important;
                text-align: center !important;
            }
            th { 
                background-color: #f8f9fa !important; 
                font-weight: bold !important;
            }
            tfoot th { 
                background-color: #e9ecef !important; 
                font-weight: bold !important;
            }
            .table-dark { 
                background-color: #343a40 !important; 
                color: white !important;
            }
            ${config.customStyles}
        }
        @page {
            size: ${config.pageOrientation};
            margin: 0.5in;
        }
    `;
    document.head.appendChild(style);
    
    // Print
    window.print();
    
    // Restore original content
    document.body.innerHTML = originalContent;
    
    // Dispatch event to notify that print is complete
    window.dispatchEvent(new CustomEvent('printComplete', { 
        detail: { tableId, title, subtitle } 
    }));
}

/**
 * Quick print function for simple use
 */
function quickPrint(tableId, title) {
    printTable(tableId, title);
}