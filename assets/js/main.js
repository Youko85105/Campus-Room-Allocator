/**
 * Main JavaScript
 * Campus Room Allocation System
 * Enhanced with animations and interactions
 */

// ==================== GLOBAL UTILITIES ====================

// Show loading overlay
function showLoading(message = 'Loading...') {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'spinner-overlay';
    overlay.innerHTML = `
        <div class="text-center">
            <div class="spinner spinner-large mb-3"></div>
            <p class="text-white">${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 300);
    }
}

// Confirm dialog with custom styling
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Format date nicely
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==================== FORM ENHANCEMENTS ====================

// Auto-save form data to localStorage
class FormAutoSave {
    constructor(formId, storageKey) {
        this.form = document.getElementById(formId);
        this.storageKey = storageKey;
        
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        // Load saved data
        this.loadFormData();
        
        // Save on input
        this.form.addEventListener('input', () => {
            this.saveFormData();
        });
        
        // Clear on submit
        this.form.addEventListener('submit', () => {
            this.clearFormData();
        });
    }
    
    saveFormData() {
        const formData = new FormData(this.form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        localStorage.setItem(this.storageKey, JSON.stringify(data));
    }
    
    loadFormData() {
        const savedData = localStorage.getItem(this.storageKey);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = this.form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key];
                }
            });
        }
    }
    
    clearFormData() {
        localStorage.removeItem(this.storageKey);
    }
}

// ==================== REAL-TIME VALIDATION ====================

class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });
    }
    
    validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        
        // Clear previous errors
        this.clearError(field);
        
        // Required check
        if (required && !value) {
            this.showError(field, 'This field is required');
            return false;
        }
        
        // Email validation
        if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showError(field, 'Please enter a valid email');
                return false;
            }
        }
        
        // Password strength
        if (type === 'password' && field.id === 'password' && value) {
            if (value.length < 6) {
                this.showError(field, 'Password must be at least 6 characters');
                return false;
            }
        }
        
        // Confirm password match
        if (field.id === 'confirm_password' && value) {
            const password = document.getElementById('password');
            if (password && value !== password.value) {
                this.showError(field, 'Passwords do not match');
                return false;
            }
        }
        
        return true;
    }
    
    showError(field, message) {
        field.classList.add('is-invalid');
        field.classList.add('shake');
        
        let errorDiv = field.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => field.classList.remove('shake'), 500);
    }
    
    clearError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
            errorDiv.style.display = 'none';
        }
    }
}

// ==================== SEARCH FUNCTIONALITY ====================

class LiveSearch {
    constructor(searchInputId, targetTableId) {
        this.searchInput = document.getElementById(searchInputId);
        this.targetTable = document.getElementById(targetTableId);
        
        if (this.searchInput && this.targetTable) {
            this.init();
        }
    }
    
    init() {
        const debouncedSearch = debounce(() => this.performSearch(), 300);
        this.searchInput.addEventListener('input', debouncedSearch);
    }
    
    performSearch() {
        const searchTerm = this.searchInput.value.toLowerCase();
        const rows = this.targetTable.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const text = row.textContent.toLowerCase();
            
            if (text.includes(searchTerm)) {
                row.style.display = '';
                row.classList.add('fade-in');
            } else {
                row.style.display = 'none';
            }
        }
    }
}

// ==================== DATA TABLE ENHANCEMENTS ====================

class DataTableEnhancer {
    constructor(tableId) {
        this.table = document.getElementById(tableId);
        if (this.table) {
            this.init();
        }
    }
    
    init() {
        this.addRowHoverEffects();
        this.addSortableHeaders();
    }
    
    addRowHoverEffects() {
        const rows = this.table.getElementsByTagName('tr');
        for (let row of rows) {
            row.classList.add('hover-lift');
        }
    }
    
    addSortableHeaders() {
        const headers = this.table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => this.sortTable(index));
        });
    }
    
    sortTable(columnIndex) {
        const rows = Array.from(this.table.querySelectorAll('tbody tr'));
        const isAscending = this.table.dataset.sortOrder !== 'asc';
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return isAscending ? aValue - bValue : bValue - aValue;
            }
            
            return isAscending ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        });
        
        const tbody = this.table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
        
        this.table.dataset.sortOrder = isAscending ? 'asc' : 'desc';
    }
}

// ==================== SMOOTH SCROLL ====================

function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// ==================== COPY TO CLIPBOARD ====================

function copyToClipboard(text, successMessage = 'Copied to clipboard!') {
    navigator.clipboard.writeText(text).then(() => {
        toast.success(successMessage);
    }).catch(() => {
        toast.error('Failed to copy');
    });
}

// ==================== DARK MODE TOGGLE ====================

class DarkModeToggle {
    constructor() {
        this.darkMode = localStorage.getItem('darkMode') === 'enabled';
        this.init();
    }
    
    init() {
        if (this.darkMode) {
            document.body.classList.add('dark-mode');
        }
        
        // Create toggle button if it doesn't exist
        this.createToggleButton();
    }
    
    createToggleButton() {
        const button = document.createElement('button');
        button.id = 'dark-mode-toggle';
        button.className = 'btn btn-sm position-fixed bottom-0 end-0 m-3';
        button.style.cssText = 'z-index: 1000; border-radius: 50%; width: 50px; height: 50px;';
        button.innerHTML = '<i class="fas fa-moon"></i>';
        button.onclick = () => this.toggle();
        
        // Add to body if not already present
        if (!document.getElementById('dark-mode-toggle')) {
            document.body.appendChild(button);
        }
    }
    
    toggle() {
        this.darkMode = !this.darkMode;
        
        if (this.darkMode) {
            document.body.classList.add('dark-mode');
            localStorage.setItem('darkMode', 'enabled');
        } else {
            document.body.classList.remove('dark-mode');
            localStorage.setItem('darkMode', 'disabled');
        }
        
        this.updateButton();
    }
    
    updateButton() {
        const button = document.getElementById('dark-mode-toggle');
        if (button) {
            button.innerHTML = this.darkMode ? 
                '<i class="fas fa-sun"></i>' : 
                '<i class="fas fa-moon"></i>';
        }
    }
}

// ==================== AUTO-INITIALIZE ON PAGE LOAD ====================

document.addEventListener('DOMContentLoaded', function() {
    // Add page entrance animation
    document.body.classList.add('page-enter');
    
    // Initialize validators for common forms
    new FormValidator('loginForm');
    new FormValidator('registerForm');
    new FormValidator('roomForm');
    
    // Initialize data table enhancements
    const tables = document.querySelectorAll('table');
    tables.forEach((table, index) => {
        if (table.id) {
            new DataTableEnhancer(table.id);
        }
    });
    
    // Add smooth scroll to all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href').substring(1);
            smoothScrollTo(target);
        });
    });
    
    // Convert alerts to toasts if PHP messages exist
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const message = alert.textContent.trim();
        const type = alert.classList.contains('alert-success') ? 'success' :
                     alert.classList.contains('alert-danger') ? 'error' :
                     alert.classList.contains('alert-warning') ? 'warning' : 'info';
        
        // Show toast
        setTimeout(() => {
            toast.show(message, type);
        }, 100);
    });
    
    console.log('🚀 Campus Room Allocation System - Enhanced UI Loaded!');
});

// ==================== EXPORT FOR GLOBAL USE ====================

window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.confirmAction = confirmAction;
window.formatDate = formatDate;
window.formatNumber = formatNumber;
window.smoothScrollTo = smoothScrollTo;
window.copyToClipboard = copyToClipboard;