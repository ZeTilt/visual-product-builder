/**
 * Visual Product Builder - Configurator
 * Modern Glassmorphism UI with smooth animations
 */
(function() {
    'use strict';

    // State
    const state = {
        elements: [],
        history: [],
        basePrice: 0,
        limit: 10,
        productId: 0
    };

    // DOM Elements
    let container, preview, undoBtn, resetBtn, addToCartBtn;
    let countDisplay, totalPriceDisplay, cartPriceDisplay;
    let configInput, imageInput;

    /**
     * Initialize configurator
     */
    function init() {
        container = document.querySelector('.vpb-configurator');
        if (!container) return;

        // Get settings from data attributes
        state.productId = parseInt(container.dataset.productId) || 0;
        state.limit = parseInt(container.dataset.limit) || 10;
        state.basePrice = parseFloat(container.dataset.basePrice) || 0;

        // Cache DOM elements
        preview = document.getElementById('vpb-preview');
        undoBtn = document.getElementById('vpb-undo');
        resetBtn = document.getElementById('vpb-reset');
        addToCartBtn = container.querySelector('.vpb-add-to-cart');
        countDisplay = document.getElementById('vpb-count');
        totalPriceDisplay = document.getElementById('vpb-total-price');
        cartPriceDisplay = document.getElementById('vpb-cart-price');
        configInput = document.getElementById('vpb-configuration-input');
        imageInput = document.getElementById('vpb-image-input');

        // Load saved state from localStorage
        loadFromStorage();

        // Bind events
        bindEvents();

        // Initial render
        render();

        // Add entrance animation to elements grid
        animateElementsEntrance();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Element selection with animation
        container.querySelectorAll('.vpb-element-btn').forEach(btn => {
            btn.addEventListener('click', (e) => addElement(btn, e));
        });

        // Tab switching (category)
        container.querySelectorAll('.vpb-tab').forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab));
        });

        // Color filter switching
        container.querySelectorAll('.vpb-color-tab').forEach(tab => {
            tab.addEventListener('click', () => filterByColor(tab));
        });

        // Collection filter switching
        container.querySelectorAll('.vpb-collection-tab').forEach(tab => {
            tab.addEventListener('click', () => filterByCollection(tab));
        });

        // Controls
        undoBtn.addEventListener('click', undoLast);
        resetBtn.addEventListener('click', confirmReset);

        // Form submission
        const form = document.getElementById('vpb-add-to-cart-form');
        form.addEventListener('submit', handleSubmit);

        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboard);
    }

    /**
     * Handle keyboard shortcuts
     */
    function handleKeyboard(e) {
        // Ctrl+Z for undo
        if (e.ctrlKey && e.key === 'z') {
            e.preventDefault();
            undoLast();
        }
    }

    /**
     * Animate elements grid entrance
     */
    function animateElementsEntrance() {
        const elements = container.querySelectorAll('.vpb-element-btn');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px) scale(0.9)';
            setTimeout(() => {
                el.style.transition = 'all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0) scale(1)';
            }, 50 + (index * 30));
        });
    }

    /**
     * Add element to design with fly animation
     */
    function addElement(btn, event) {
        if (state.elements.length >= state.limit) {
            // Shake animation on limit reached
            shakeElement(btn);
            shakeElement(container.querySelector('.vpb-counter'));
            showToast(vpbData?.i18n?.limitReached || 'Limite atteinte !', 'warning');
            return;
        }

        // Fly animation
        animateFlyToPreview(btn);

        // Visual feedback on button
        btn.classList.add('vpb-burst');
        setTimeout(() => btn.classList.remove('vpb-burst'), 300);

        // Save current state for undo
        state.history.push([...state.elements]);

        // Add element
        const svgUrl = btn.dataset.svg;
        const isSvg = svgUrl && svgUrl.toLowerCase().endsWith('.svg');
        const element = {
            id: parseInt(btn.dataset.id),
            name: btn.dataset.name,
            color: btn.dataset.color,
            colorHex: btn.dataset.colorHex || '#4F9ED9',
            price: parseFloat(btn.dataset.price),
            svg: svgUrl,
            isSvg: isSvg
        };

        state.elements.push(element);

        // Update UI with slight delay for animation
        setTimeout(() => {
            render();
            saveToStorage();
        }, 200);

        showToast(vpbData?.i18n?.elementAdded || 'Élément ajouté !', 'success');

        // Warn if approaching limit
        if (state.elements.length === state.limit - 1) {
            setTimeout(() => {
                showToast('Plus qu\'un élément disponible', 'warning');
            }, 500);
        }
    }

    /**
     * Animate element flying to preview
     */
    function animateFlyToPreview(btn) {
        const img = btn.querySelector('img');
        if (!img) return;

        const clone = img.cloneNode(true);
        const btnRect = btn.getBoundingClientRect();
        const previewRect = preview.getBoundingClientRect();

        // Style the flying clone
        clone.style.cssText = `
            position: fixed;
            left: ${btnRect.left + btnRect.width / 2 - 25}px;
            top: ${btnRect.top + btnRect.height / 2 - 25}px;
            width: 50px;
            height: 50px;
            z-index: 10000;
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            filter: drop-shadow(0 10px 20px rgba(99, 102, 241, 0.4));
        `;

        document.body.appendChild(clone);

        // Trigger fly animation
        requestAnimationFrame(() => {
            clone.style.left = `${previewRect.left + previewRect.width / 2 - 35}px`;
            clone.style.top = `${previewRect.top + previewRect.height / 2 - 35}px`;
            clone.style.transform = 'scale(0.8)';
            clone.style.opacity = '0.5';
        });

        // Remove clone after animation
        setTimeout(() => {
            clone.style.opacity = '0';
            clone.style.transform = 'scale(0)';
            setTimeout(() => clone.remove(), 200);
        }, 400);
    }

    /**
     * Shake animation for element
     */
    function shakeElement(el) {
        if (!el) return;
        el.classList.add('vpb-shake');
        setTimeout(() => el.classList.remove('vpb-shake'), 600);
    }

    /**
     * Undo last action
     */
    function undoLast() {
        if (state.history.length === 0) return;

        state.elements = state.history.pop();
        render();
        saveToStorage();
        showToast(vpbData?.i18n?.undone || 'Action annulée', 'info');
    }

    /**
     * Confirm and reset design
     */
    function confirmReset() {
        if (state.elements.length === 0) return;

        showConfirm(vpbData?.i18n?.confirmReset || 'Voulez-vous vraiment recommencer ?', () => {
            // Animate out
            preview.querySelectorAll('.vpb-preview-element').forEach((el, i) => {
                el.style.transition = 'all 0.3s ease';
                el.style.transitionDelay = `${i * 50}ms`;
                el.style.opacity = '0';
                el.style.transform = 'scale(0) rotate(180deg)';
            });

            setTimeout(() => {
                state.elements = [];
                state.history = [];
                render();
                saveToStorage();
                showToast('Design réinitialisé', 'info');
            }, 300);
        });
    }

    /**
     * Switch category tab
     */
    function switchTab(tab) {
        const category = tab.dataset.category;

        // Update tab styles
        container.querySelectorAll('.vpb-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Show/hide grids with animation
        container.querySelectorAll('.vpb-elements-grid').forEach(grid => {
            if (grid.dataset.category === category) {
                grid.style.display = '';
                // Re-animate elements
                grid.querySelectorAll('.vpb-element-btn').forEach((el, i) => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        el.style.transition = 'all 0.3s ease';
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, i * 20);
                });
            } else {
                grid.style.display = 'none';
            }
        });
    }

    /**
     * Filter elements by color
     */
    function filterByColor(tab) {
        const color = tab.dataset.color;

        // Update tab styles with animation
        container.querySelectorAll('.vpb-color-tab').forEach(t => {
            t.classList.remove('active');
        });
        tab.classList.add('active');

        // Apply combined filters
        applyFilters();
    }

    /**
     * Filter elements by collection
     */
    function filterByCollection(tab) {
        const collection = tab.dataset.collection;

        // Update tab styles with animation
        container.querySelectorAll('.vpb-collection-tab').forEach(t => {
            t.classList.remove('active');
        });
        tab.classList.add('active');

        // Apply combined filters
        applyFilters();
    }

    /**
     * Apply combined filters (color + collection)
     */
    function applyFilters() {
        const activeColorTab = container.querySelector('.vpb-color-tab.active');
        const activeCollectionTab = container.querySelector('.vpb-collection-tab.active');

        const color = activeColorTab ? activeColorTab.dataset.color : 'all';
        const collection = activeCollectionTab ? activeCollectionTab.dataset.collection : 'all';

        // Show/hide elements with staggered animation
        let visibleIndex = 0;
        container.querySelectorAll('.vpb-element-btn').forEach(btn => {
            const matchesColor = color === 'all' || btn.dataset.color === color;
            const matchesCollection = collection === 'all' || btn.dataset.collection === collection;
            const matches = matchesColor && matchesCollection;

            if (matches) {
                btn.classList.remove('hidden');
                btn.style.opacity = '0';
                btn.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    btn.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
                    btn.style.opacity = '1';
                    btn.style.transform = 'scale(1)';
                }, visibleIndex * 30);
                visibleIndex++;
            } else {
                btn.style.opacity = '0';
                btn.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    btn.classList.add('hidden');
                }, 200);
            }
        });
    }

    /**
     * Render the preview and update UI
     */
    function render() {
        renderPreview();
        updateCounter();
        updatePrice();
        updateControls();
        updateFormData();
    }

    /**
     * Render preview area
     */
    function renderPreview() {
        const placeholder = preview.querySelector('.vpb-preview-placeholder');

        // Remove existing elements
        preview.querySelectorAll('.vpb-preview-element').forEach(el => el.remove());

        if (state.elements.length === 0) {
            if (placeholder) {
                placeholder.style.display = '';
                placeholder.style.opacity = '0';
                setTimeout(() => {
                    placeholder.style.transition = 'opacity 0.3s ease';
                    placeholder.style.opacity = '1';
                }, 10);
            }
            return;
        }

        if (placeholder) placeholder.style.display = 'none';

        // Add elements with staggered animation
        state.elements.forEach((element, index) => {
            const div = document.createElement('div');
            div.className = 'vpb-preview-element';
            div.style.opacity = '0';
            div.setAttribute('data-is-svg', element.isSvg ? 'true' : 'false');

            // Only apply colored background for SVG elements
            if (element.isSvg) {
                div.style.backgroundColor = element.colorHex || '#4F9ED9';
            }

            div.innerHTML = `<img src="${element.svg}" alt="${element.name}">`;
            preview.appendChild(div);

            // Staggered pop-in animation
            setTimeout(() => {
                div.style.opacity = '1';
            }, index * 50);
        });
    }

    /**
     * Update element counter with animation
     */
    function updateCounter() {
        const count = state.elements.length;
        const oldCount = parseInt(countDisplay.textContent) || 0;

        // Animate count change
        if (count !== oldCount) {
            countDisplay.style.transform = 'scale(1.3)';
            setTimeout(() => {
                countDisplay.textContent = count;
                countDisplay.style.transform = 'scale(1)';
            }, 150);
        }

        // Update counter color
        const counter = countDisplay.parentElement;
        counter.classList.remove('vpb-counter-warning', 'vpb-counter-danger');

        if (count >= state.limit) {
            counter.classList.add('vpb-counter-danger');
        } else if (count >= state.limit - 2) {
            counter.classList.add('vpb-counter-warning');
        }
    }

    /**
     * Update price displays with animation
     */
    function updatePrice() {
        const elementsPrice = state.elements.reduce((sum, el) => sum + el.price, 0);
        const totalPrice = state.basePrice + elementsPrice;

        // Format price
        const formatted = formatPrice(totalPrice);

        // Animate price change
        [totalPriceDisplay, cartPriceDisplay].forEach(display => {
            if (display.innerHTML !== formatted) {
                display.style.transform = 'scale(1.1)';
                display.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                    display.innerHTML = formatted;
                    display.style.transform = 'scale(1)';
                }, 100);
            }
        });
    }

    /**
     * Format price (basic implementation)
     */
    function formatPrice(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    }

    /**
     * Update control buttons state
     */
    function updateControls() {
        undoBtn.disabled = state.history.length === 0;
        resetBtn.disabled = state.elements.length === 0;

        // Add to cart button
        const wasDisabled = addToCartBtn.disabled;
        addToCartBtn.disabled = state.elements.length === 0;

        // Animate button when becoming enabled
        if (wasDisabled && !addToCartBtn.disabled) {
            addToCartBtn.style.transform = 'scale(1.05)';
            setTimeout(() => {
                addToCartBtn.style.transform = 'scale(1)';
            }, 200);
        }
    }

    /**
     * Update form data for submission
     */
    function updateFormData() {
        const config = {
            elements: state.elements.map(el => ({
                id: el.id,
                name: el.name,
                color: el.color,
                colorHex: el.colorHex,
                isSvg: el.isSvg
            }))
        };
        configInput.value = JSON.stringify(config);
    }

    /**
     * Handle form submission
     */
    async function handleSubmit(e) {
        if (state.elements.length === 0) {
            e.preventDefault();
            return;
        }

        // Show loading state
        addToCartBtn.classList.add('vpb-loading');
        addToCartBtn.disabled = true;

        // Generate image before submit
        try {
            const imageData = await generateImage();
            imageInput.value = imageData;
        } catch (error) {
            console.error('Failed to generate image:', error);
            // Continue without image
        }

        // Clear storage after successful add
        clearStorage();

        // Success animation
        showToast('Ajouté au panier !', 'success');
    }

    /**
     * Generate PNG image from preview
     */
    async function generateImage() {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Set canvas size
            const elementWidth = 60;
            const padding = 30;
            const gap = 5;
            const width = (state.elements.length * (elementWidth + gap)) + (padding * 2);
            const height = elementWidth + (padding * 2);

            canvas.width = Math.min(width, 1200);
            canvas.height = Math.min(height, 800);

            // Gradient background
            const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
            gradient.addColorStop(0, '#e0e7ff');
            gradient.addColorStop(1, '#fce7f3');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Load and draw each element
            const loadPromises = state.elements.map((element, index) => {
                return new Promise((resolveImg) => {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => {
                        const x = padding + (index * (elementWidth + gap));
                        const y = padding;
                        ctx.drawImage(img, x, y, elementWidth, elementWidth);
                        resolveImg();
                    };
                    img.onerror = () => resolveImg();
                    img.src = element.svg;
                });
            });

            Promise.all(loadPromises).then(() => {
                resolve(canvas.toDataURL('image/png', 0.9));
            }).catch(reject);
        });
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('vpb-toast-container');
        const toast = document.createElement('div');
        toast.className = `vpb-toast vpb-toast-${type}`;
        toast.textContent = message;

        toastContainer.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });
        });

        // Remove after delay
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    /**
     * Show confirmation dialog
     */
    function showConfirm(message, onConfirm) {
        const overlay = document.createElement('div');
        overlay.className = 'vpb-confirm-overlay';
        overlay.innerHTML = `
            <div class="vpb-confirm-dialog">
                <p>${message}</p>
                <div class="vpb-confirm-actions">
                    <button type="button" class="vpb-btn vpb-btn-secondary vpb-confirm-cancel">Annuler</button>
                    <button type="button" class="vpb-btn vpb-btn-primary vpb-confirm-ok">Confirmer</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Focus first button
        overlay.querySelector('.vpb-confirm-cancel').focus();

        const closeDialog = () => {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        };

        overlay.querySelector('.vpb-confirm-cancel').addEventListener('click', closeDialog);

        overlay.querySelector('.vpb-confirm-ok').addEventListener('click', () => {
            closeDialog();
            onConfirm();
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeDialog();
        });

        // ESC to close
        const handleEsc = (e) => {
            if (e.key === 'Escape') {
                closeDialog();
                document.removeEventListener('keydown', handleEsc);
            }
        };
        document.addEventListener('keydown', handleEsc);
    }

    /**
     * Save state to localStorage
     */
    function saveToStorage() {
        const key = `vpb_design_${state.productId}`;
        const data = {
            elements: state.elements,
            timestamp: Date.now()
        };
        try {
            localStorage.setItem(key, JSON.stringify(data));
        } catch (e) {
            console.warn('Failed to save to localStorage:', e);
        }
    }

    /**
     * Load state from localStorage
     */
    function loadFromStorage() {
        const key = `vpb_design_${state.productId}`;
        try {
            const saved = localStorage.getItem(key);
            if (saved) {
                const data = JSON.parse(saved);
                // Only restore if less than 1 hour old
                if (Date.now() - data.timestamp < 3600000) {
                    state.elements = data.elements || [];
                }
            }
        } catch (e) {
            console.warn('Failed to load from localStorage:', e);
        }
    }

    /**
     * Clear storage
     */
    function clearStorage() {
        const key = `vpb_design_${state.productId}`;
        try {
            localStorage.removeItem(key);
        } catch (e) {
            // Ignore
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
