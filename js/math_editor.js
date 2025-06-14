// Math Editor for Math Friends Admin Panel
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a page with math editor
    if (document.getElementById('math-editor-container')) {
        initMathEditor();
    }
});

/**
 * Initialize the math formula editor
 */
function initMathEditor() {
    const editorContainer = document.getElementById('math-editor-container');
    const formulaInput = document.getElementById('formula-input');
    const previewArea = document.getElementById('formula-preview');
    const insertButton = document.getElementById('insert-formula');
    const questionTextarea = document.getElementById('question_text');
    
    // Common math symbols
    const commonSymbols = [
        { symbol: '+', description: 'Cộng' },
        { symbol: '-', description: 'Trừ' },
        { symbol: '\\times', description: 'Nhân' },
        { symbol: '\\div', description: 'Chia' },
        { symbol: '=', description: 'Bằng' },
        { symbol: '<', description: 'Nhỏ hơn' },
        { symbol: '>', description: 'Lớn hơn' },
        { symbol: '\\leq', description: 'Nhỏ hơn hoặc bằng' },
        { symbol: '\\geq', description: 'Lớn hơn hoặc bằng' },
        { symbol: '\\neq', description: 'Khác' },
        { symbol: '\\frac{a}{b}', description: 'Phân số' },
        { symbol: '\\sqrt{x}', description: 'Căn bậc hai' },
        { symbol: '\\sqrt[n]{x}', description: 'Căn bậc n' },
        { symbol: 'x^2', description: 'Lũy thừa' },
        { symbol: '\\pi', description: 'Pi' },
        { symbol: '\\sum_{i=1}^{n}', description: 'Tổng' },
        { symbol: '\\prod_{i=1}^{n}', description: 'Tích' }
    ];
    
    // Create quick buttons for common symbols
    const symbolsContainer = document.createElement('div');
    symbolsContainer.className = 'math-symbols-container';
    
    commonSymbols.forEach(item => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-secondary math-symbol-btn';
        button.setAttribute('data-symbol', item.symbol);
        button.setAttribute('title', item.description);
        button.innerHTML = `\\(${item.symbol}\\)`;
        
        button.addEventListener('click', function() {
            const symbol = this.getAttribute('data-symbol');
            insertTextAtCursor(formulaInput, symbol);
            updatePreview();
        });
        
        symbolsContainer.appendChild(button);
    });
    
    // Insert symbols container before the formula input
    editorContainer.insertBefore(symbolsContainer, formulaInput.parentNode);
    
    // Update preview when input changes
    formulaInput.addEventListener('input', updatePreview);
    
    // Insert formula into question text
    insertButton.addEventListener('click', function() {
        const formula = formulaInput.value.trim();
        if (formula) {
            const formulaTag = `$$${formula}$$`;
            insertTextAtCursor(questionTextarea, formulaTag);
            formulaInput.value = '';
            previewArea.innerHTML = '';
        }
    });
    
    // Initialize MathJax for the symbols buttons
    if (typeof MathJax !== 'undefined') {
        MathJax.Hub.Queue(["Typeset", MathJax.Hub, symbolsContainer]);
    }
    
    /**
     * Update the formula preview
     */
    function updatePreview() {
        const formula = formulaInput.value.trim();
        if (formula) {
            previewArea.innerHTML = `$$${formula}$$`;
            
            // Render the formula with MathJax
            if (typeof MathJax !== 'undefined') {
                MathJax.Hub.Queue(["Typeset", MathJax.Hub, previewArea]);
            }
        } else {
            previewArea.innerHTML = '<em>Xem trước công thức</em>';
        }
    }
    
    /**
     * Insert text at cursor position in textarea
     */
    function insertTextAtCursor(textarea, text) {
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const beforeText = textarea.value.substring(0, startPos);
        const afterText = textarea.value.substring(endPos);
        
        textarea.value = beforeText + text + afterText;
        textarea.selectionStart = textarea.selectionEnd = startPos + text.length;
        textarea.focus();
    }
}

/**
 * Process all math formulas in the page
 */
function processMathFormulas() {
    // Find all elements with the .process-math class
    const mathElements = document.querySelectorAll('.process-math');
    
    // Process each element
    mathElements.forEach(element => {
        let content = element.innerHTML;
        
        // Replace $$ ... $$ with \[ ... \] for display mode
        content = content.replace(/\$\$(.*?)\$\$/g, '\\[$1\\]');
        
        // Replace $ ... $ with \( ... \) for inline mode
        content = content.replace(/\$(.*?)\$/g, '\\($1\\)');
        
        element.innerHTML = content;
    });
    
    // Render the formulas with MathJax
    if (typeof MathJax !== 'undefined') {
        MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
    }
}

// Add global function for processing math formulas
window.processMathFormulas = processMathFormulas;