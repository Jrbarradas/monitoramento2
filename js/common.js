/**
 * Funções JavaScript comuns para o sistema de monitoramento
 */

// Debounce function for performance
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

// Throttle function for performance
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Enhanced fetch with retry logic
async function fetchWithRetry(url, options = {}, retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache',
                    ...options.headers
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response;
        } catch (error) {
            if (i === retries - 1) throw error;
            await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
        }
    }
}

// Notification system
class NotificationManager {
    constructor() {
        this.container = this.createContainer();
        this.notifications = new Map();
    }
    
    createContainer() {
        const container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
        return container;
    }
    
    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const id = Date.now() + Math.random();
        
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${this.getBackgroundColor(type)};
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border: 1px solid ${this.getBorderColor(type)};
            transform: translateX(100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: auto;
            cursor: pointer;
            max-width: 400px;
            word-wrap: break-word;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-${this.getIcon(type)}" style="font-size: 1.2rem;"></i>
                <span style="flex: 1;">${message}</span>
                <i class="fas fa-times" style="opacity: 0.7; cursor: pointer;"></i>
            </div>
        `;
        
        this.container.appendChild(notification);
        this.notifications.set(id, notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });
        
        // Auto remove
        setTimeout(() => this.remove(id), duration);
        
        // Click to remove
        notification.addEventListener('click', () => this.remove(id));
        
        return id;
    }
    
    remove(id) {
        const notification = this.notifications.get(id);
        if (notification) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                this.notifications.delete(id);
            }, 300);
        }
    }
    
    getBackgroundColor(type) {
        const colors = {
            success: 'rgba(16, 185, 129, 0.9)',
            error: 'rgba(239, 68, 68, 0.9)',
            warning: 'rgba(245, 158, 11, 0.9)',
            info: 'rgba(59, 130, 246, 0.9)'
        };
        return colors[type] || colors.info;
    }
    
    getBorderColor(type) {
        const colors = {
            success: 'rgba(16, 185, 129, 0.3)',
            error: 'rgba(239, 68, 68, 0.3)',
            warning: 'rgba(245, 158, 11, 0.3)',
            info: 'rgba(59, 130, 246, 0.3)'
        };
        return colors[type] || colors.info;
     }
     
     getIcon(type) {
         const icons = {
             success: 'check-circle',
             error: 'exclamation-circle',
             warning: 'exclamation-triangle',
             info: 'info-circle'
         };
         return icons[type] || icons.info;
     }
 }
 
 // Global notification instance
 const notifications = new NotificationManager();
 
 // Performance monitoring
 class PerformanceMonitor {
     constructor() {
         this.metrics = new Map();
         this.observers = new Map();
     }
     
     startTiming(label) {
         this.metrics.set(label, performance.now());
     }
     
     endTiming(label) {
         const startTime = this.metrics.get(label);
         if (startTime) {
             const duration = performance.now() - startTime;
             console.log(`${label}: ${duration.toFixed(2)}ms`);
             this.metrics.delete(label);
             return duration;
         }
         return 0;
     }
     
     observeElement(element, callback) {
         if ('IntersectionObserver' in window) {
             const observer = new IntersectionObserver((entries) => {
                 entries.forEach(entry => {
                     if (entry.isIntersecting) {
                         callback(entry.target);
                     }
                 });
             }, { threshold: 0.1 });
             
             observer.observe(element);
             this.observers.set(element, observer);
         }
     }
     
     cleanup() {
         this.observers.forEach(observer => observer.disconnect());
         this.observers.clear();
         this.metrics.clear();
     }
 }
 
 // Global performance monitor
 const perfMonitor = new PerformanceMonitor();
 
 // Utility functions
 const utils = {
     // Format numbers with locale
     formatNumber: (num) => new Intl.NumberFormat('pt-BR').format(num),
     
     // Format dates
     formatDate: (date, options = {}) => {
         const defaultOptions = {
             year: 'numeric',
             month: '2-digit',
             day: '2-digit',
             hour: '2-digit',
             minute: '2-digit'
         };
         return new Intl.DateTimeFormat('pt-BR', { ...defaultOptions, ...options }).format(new Date(date));
     },
     
     // Validate IP address
     isValidIP: (ip) => {
         const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
         return ipRegex.test(ip);
     },
     
     // Copy to clipboard
     copyToClipboard: async (text) => {
         try {
             await navigator.clipboard.writeText(text);
             notifications.show('Copiado para a área de transferência!', 'success', 2000);
         } catch (err) {
             console.error('Failed to copy: ', err);
             notifications.show('Erro ao copiar', 'error', 2000);
         }
     },
     
     // Animate element
     animate: (element, animation, duration = 300) => {
         return new Promise(resolve => {
             element.style.animation = `${animation} ${duration}ms ease-in-out`;
             setTimeout(() => {
                 element.style.animation = '';
                 resolve();
             }, duration);
         });
     }
 };
 
 // Global error handler
 window.addEventListener('error', (e) => {
     console.error('Global error:', e.error);
     notifications.show('Ocorreu um erro inesperado', 'error');
 });
 
 // Unhandled promise rejection handler
 window.addEventListener('unhandledrejection', (e) => {
     console.error('Unhandled promise rejection:', e.reason);
     notifications.show('Erro de conexão', 'error');
 });
 
 // Page visibility API for performance
 document.addEventListener('visibilitychange', () => {
     if (document.visibilityState === 'hidden') {
         // Pause expensive operations when page is hidden
         window.dispatchEvent(new CustomEvent('pageHidden'));
     } else {
         // Resume operations when page becomes visible
         window.dispatchEvent(new CustomEvent('pageVisible'));
     }
 });
 
 // Export for use in other scripts
 if (typeof module !== 'undefined' && module.exports) {
     module.exports = { notifications, perfMonitor, utils, debounce, throttle, fetchWithRetry };
 }