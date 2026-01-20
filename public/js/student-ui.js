/**
 * Student UI Helper Functions
 * Minimal utilities for toasts and UI enhancements
 */

// ============================================
// SAFETY GUARD: Only run on student pages
// ============================================
if (!document.body.classList.contains('student-ui')) {
    // Clean up any accidental backdrops/overlays
    document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop, .sidebar-overlay').forEach(el => el.remove());
    document.body.classList.remove('modal-open', 'offcanvas-open', 'sidebar-open');
    
    // Stop execution - this file should not run on non-student pages
    console.log('[Student UI] Disabled on non-student pages');
    if (!document.body.classList.contains('student-ui')) {
    document.querySelectorAll('.offcanvas-backdrop,.modal-backdrop,.sidebar-overlay').forEach(el => el.remove());
    document.body.classList.remove('modal-open','offcanvas-open','sidebar-open');
    // Stop safely without errors
    return;
    }

}

// Toast notification system
const StudentUI = {
    // Show toast notification
    showToast(message, type = 'info', duration = 3000) {
        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-modern toast-${type}`;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.25rem;">
                    ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}
                </span>
                <span style="flex: 1;">${message}</span>
            </div>
        `;

        container.appendChild(toast);

        // Auto remove after duration
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    // Show success toast
    success(message, duration) {
        this.showToast(message, 'success', duration);
    },

    // Show error toast
    error(message, duration) {
        this.showToast(message, 'error', duration);
    },

    // Show info toast
    info(message, duration) {
        this.showToast(message, 'info', duration);
    },

    // Confirm dialog with custom styling
    confirm(message, title = 'تأكيد') {
        return window.confirm(`${title}\n\n${message}`);
    },

    // Format time helper
    formatTime(seconds) {
        if (seconds < 0) return '00:00';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    },

    // Scroll to element smoothly
    scrollTo(element) {
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    [dir="rtl"] @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(-100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Make available globally
window.StudentUI = StudentUI;
