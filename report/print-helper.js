/**
 * Universal print function for single or multiple tables
 * @param {string|array} tableId - ID of the table(s) to print (string for single, array for multiple)
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
        pageOrientation: 'portrait',
        includeHeaders: true, // For multiple tables, include section headers
        sectionTitles: [] // Titles for each section when printing multiple tables
    };
    
    const config = { ...defaultOptions, ...options };
    
    // Store original content
    const originalContent = document.body.innerHTML;
    
    // Handle single or multiple tables
    const tableIds = Array.isArray(tableId) ? tableId : [tableId];
    
    // Collect all tables
    let tablesHTML = '';
    tableIds.forEach((id, index) => {
        const table = document.getElementById(id);
        if (!table) {
            console.warn(`Table with ID "${id}" not found!`);
            return;
        }
        
        // Add section title if provided
        if (config.includeHeaders && config.sectionTitles[index]) {
            tablesHTML += `
                <h3 style="margin-top: ${index > 0 ? '40px' : '0'}; margin-bottom: 15px; font-size: 18px; color: #333;">
                    ${config.sectionTitles[index]}
                </h3>
            `;
        }
        
        // Add spacing between tables
        if (index > 0) {
            tablesHTML += '<div style="margin-top: 30px;"></div>';
        }
        
        tablesHTML += table.outerHTML;
    });
    
    if (!tablesHTML) {
        alert('No tables found to print!');
        document.body.innerHTML = originalContent;
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
            ${tablesHTML}
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
                font-size: 12px;
                page-break-inside: auto;
            }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            th, td { 
                border: 1px solid #000 !important; 
                padding: 8px !important;
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
            .table-secondary {
                background-color: #e9ecef !important;
                font-weight: bold !important;
            }
            .text-start { text-align: left !important; }
            .text-center { text-align: center !important; }
            .text-end { text-align: right !important; }
            h3 {
                page-break-after: avoid;
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
        detail: { tableId: tableIds, title, subtitle } 
    }));
}

/**
 * Print multiple tables together
 * @param {array} tableIds - Array of table IDs to print
 * @param {string} title - Report title
 * @param {string} subtitle - Report subtitle (optional)
 * @param {array} sectionTitles - Array of section titles for each table (optional)
 * @param {object} options - Additional options (optional)
 */
function printMultipleTables(tableIds, title, subtitle = '', sectionTitles = [], options = {}) {
    const config = {
        ...options,
        includeHeaders: true,
        sectionTitles: sectionTitles
    };
    
    printTable(tableIds, title, subtitle, config);
}

/**
 * Quick print function for simple use (single table)
 */
function quickPrint(tableId, title) {
    printTable(tableId, title);
}