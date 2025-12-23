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
        productId: 0,
        dragIndex: null,
        editCartKey: null // Cart item key when editing from cart
    };

    /**
     * Detect if device is touch-enabled
     * Drag & drop doesn't work reliably on touch devices
     */
    function isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    // DOM Elements
    let container, preview, undoBtn, resetBtn, addToCartBtn;
    let countDisplay, totalPriceDisplay, cartPriceDisplay;
    let configInput, imageInput;

    /**
     * Initialize configurator
     */
    async function init() {
        container = document.querySelector('.vpb-configurator');
        if (!container) return;

        // Get settings from data attributes
        state.productId = parseInt(container.dataset.productId) || 0;
        state.limit = parseInt(container.dataset.limit) || 10;
        state.basePrice = parseFloat(container.dataset.basePrice) || 0;
        state.editCartKey = container.dataset.editCartKey || null;

        console.log('[VPB] Init - productId:', state.productId, 'editCartKey:', state.editCartKey);

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

        // Load saved state: from cart if editing, otherwise from localStorage
        // IMPORTANT: await to ensure data is loaded before render()
        if (state.editCartKey) {
            await loadFromCart(state.editCartKey);
        } else {
            loadFromStorage();
        }

        // Bind events
        bindEvents();

        // Initial render (after data is loaded)
        render();

        // Add entrance animation to elements grid
        animateElementsEntrance();

        // Check if we just added to cart (show success message after redirect)
        checkPostAddToCart();
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

        // Collection filter switching (tabs)
        container.querySelectorAll('.vpb-collection-tab').forEach(tab => {
            tab.addEventListener('click', () => filterByCollection(tab));
        });

        // Collection filter switching (dropdown)
        const collectionDropdown = document.getElementById('vpb-collection-select');
        if (collectionDropdown) {
            collectionDropdown.addEventListener('change', () => filterByCollectionDropdown(collectionDropdown));
        }

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
     * Filter elements by collection
     */
    function filterByCollection(tab) {
        const collection = tab.dataset.collection;

        // Update tab styles with animation
        container.querySelectorAll('.vpb-collection-tab').forEach(t => {
            t.classList.remove('active');
        });
        tab.classList.add('active');

        // Show/hide elements with staggered animation
        let visibleIndex = 0;
        container.querySelectorAll('.vpb-element-btn').forEach(btn => {
            const matches = collection === 'all' || btn.dataset.collection === collection;

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
     * Filter elements by collection (dropdown version)
     */
    function filterByCollectionDropdown(dropdown) {
        const collection = dropdown.value;

        // Show/hide elements with staggered animation
        let visibleIndex = 0;
        container.querySelectorAll('.vpb-element-btn').forEach(btn => {
            const matches = collection === 'all' || btn.dataset.collection === collection;

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
            div.setAttribute('data-index', index);

            // Drag & drop is a PRO feature (disabled on touch devices)
            const canDrag = vpbData?.features?.dragDrop === true && !isTouchDevice();
            div.draggable = canDrag;

            // Only apply colored background for SVG elements
            if (element.isSvg) {
                div.style.backgroundColor = element.colorHex || '#4F9ED9';
            }

            // Use DOM manipulation instead of innerHTML for security
            const img = document.createElement('img');
            img.src = element.svg;
            img.alt = element.name;
            img.draggable = false;
            div.appendChild(img);

            // Drag events (PRO feature only)
            if (canDrag) {
                div.addEventListener('dragstart', handleDragStart);
                div.addEventListener('dragend', handleDragEnd);
                div.addEventListener('dragover', handleDragOver);
                div.addEventListener('drop', handleDrop);
                div.addEventListener('dragenter', handleDragEnter);
                div.addEventListener('dragleave', handleDragLeave);
            }

            preview.appendChild(div);

            // Staggered pop-in animation
            setTimeout(() => {
                div.style.opacity = '1';
            }, index * 50);
        });
    }

    /**
     * Handle drag start
     */
    function handleDragStart(e) {
        const index = parseInt(e.currentTarget.dataset.index);
        state.dragIndex = index;
        e.currentTarget.classList.add('vpb-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', index);
    }

    /**
     * Handle drag end
     */
    function handleDragEnd(e) {
        e.currentTarget.classList.remove('vpb-dragging');
        state.dragIndex = null;

        // Remove all drag-over classes
        preview.querySelectorAll('.vpb-drag-over').forEach(el => {
            el.classList.remove('vpb-drag-over');
        });
    }

    /**
     * Handle drag over
     */
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    /**
     * Handle drag enter
     */
    function handleDragEnter(e) {
        e.preventDefault();
        const target = e.currentTarget;
        const targetIndex = parseInt(target.dataset.index);

        if (state.dragIndex !== null && targetIndex !== state.dragIndex) {
            target.classList.add('vpb-drag-over');
        }
    }

    /**
     * Handle drag leave
     */
    function handleDragLeave(e) {
        e.currentTarget.classList.remove('vpb-drag-over');
    }

    /**
     * Handle drop
     */
    function handleDrop(e) {
        e.preventDefault();
        const target = e.currentTarget;
        target.classList.remove('vpb-drag-over');

        const fromIndex = state.dragIndex;
        const toIndex = parseInt(target.dataset.index);

        if (fromIndex === null || fromIndex === toIndex) return;

        // Save history for undo
        state.history.push([...state.elements]);

        // Reorder elements
        const [movedElement] = state.elements.splice(fromIndex, 1);
        state.elements.splice(toIndex, 0, movedElement);

        // Re-render
        render();
        saveToStorage();
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
        // Always prevent default to handle async image generation
        e.preventDefault();

        if (state.elements.length === 0) {
            return;
        }

        // Prevent double submission
        if (addToCartBtn.classList.contains('vpb-loading')) {
            return;
        }

        // Show loading state with visual feedback
        addToCartBtn.classList.add('vpb-loading');
        addToCartBtn.disabled = true;
        addToCartBtn.innerHTML = '<span class="vpb-spinner"></span> ' + (vpbData?.i18n?.adding || 'Ajout en cours...');
        addToCartBtn.setAttribute('aria-busy', 'true');

        // Generate image before submit
        try {
            const imageData = await generateImage();
            imageInput.value = imageData;
        } catch (error) {
            console.error('Failed to generate image:', error);
            // Continue without image
        }

        // Store flag to show success message after page reload
        try {
            sessionStorage.setItem('vpb_added_to_cart', 'true');
        } catch (err) {
            // Ignore storage errors
        }

        // Note: We intentionally do NOT clear storage here.
        // This allows users to return to the product page and modify their design
        // or add another item with the same design. Users can use "Reset" to clear.

        // Now submit the form programmatically (after image is ready)
        const form = document.getElementById('vpb-add-to-cart-form');
        form.submit();
    }

    /**
     * Check for post-add-to-cart redirect and show success message
     */
    function checkPostAddToCart() {
        try {
            if (sessionStorage.getItem('vpb_added_to_cart') === 'true') {
                sessionStorage.removeItem('vpb_added_to_cart');
                // Small delay to ensure page is fully loaded
                setTimeout(() => {
                    showToast(vpbData?.i18n?.addedToCart || 'Ajouté au panier !', 'success');
                }, 300);
            }
        } catch (err) {
            // Ignore storage errors
        }
    }

    /**
     * Generate PNG image from preview
     */
    async function generateImage() {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Filter elements with valid SVG URLs
            const validElements = state.elements.filter(el => {
                if (!el.svg) {
                    console.warn('Element missing svg:', el);
                    return false;
                }
                return true;
            });

            if (validElements.length === 0) {
                reject(new Error('No valid elements to render'));
                return;
            }

            // Set canvas size
            const elementWidth = 60;
            const padding = 30;
            const gap = 5;
            const width = (validElements.length * (elementWidth + gap)) + (padding * 2);
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
            const loadPromises = validElements.map((element, index) => {
                return new Promise((resolveImg) => {
                    const img = new Image();
                    // Only set crossOrigin for external URLs to avoid CORS issues with same-origin
                    try {
                        const imgUrl = new URL(element.svg, window.location.origin);
                        if (imgUrl.origin !== window.location.origin) {
                            img.crossOrigin = 'anonymous';
                        }
                    } catch (e) {
                        // If URL parsing fails, don't set crossOrigin
                    }
                    img.onload = () => {
                        const x = padding + (index * (elementWidth + gap));
                        const y = padding;
                        ctx.drawImage(img, x, y, elementWidth, elementWidth);
                        resolveImg();
                    };
                    img.onerror = (err) => {
                        console.warn('Failed to load image:', element.svg, err);
                        resolveImg(); // Continue anyway
                    };
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
        // Use DOM manipulation instead of innerHTML for security
        const dialog = document.createElement('div');
        dialog.className = 'vpb-confirm-dialog';

        const p = document.createElement('p');
        p.textContent = message;
        dialog.appendChild(p);

        const actions = document.createElement('div');
        actions.className = 'vpb-confirm-actions';

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'vpb-btn vpb-btn-secondary vpb-confirm-cancel';
        cancelBtn.textContent = vpbData?.i18n?.cancel || 'Annuler';
        actions.appendChild(cancelBtn);

        const okBtn = document.createElement('button');
        okBtn.type = 'button';
        okBtn.className = 'vpb-btn vpb-btn-primary vpb-confirm-ok';
        okBtn.textContent = vpbData?.i18n?.confirm || 'Confirmer';
        actions.appendChild(okBtn);

        dialog.appendChild(actions);
        overlay.appendChild(dialog);

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
     * Load configuration from cart item
     * Returns a Promise so init() can await it
     */
    async function loadFromCart(cartItemKey) {
        console.log('[VPB] Loading from cart, key:', cartItemKey);

        if (!vpbData || !vpbData.ajaxUrl) {
            console.warn('[VPB] AJAX URL not available');
            loadFromStorage();
            return;
        }

        try {
            const url = vpbData.ajaxUrl + '?action=vpb_get_cart_config&cart_item_key=' + encodeURIComponent(cartItemKey);
            console.log('[VPB] Fetching:', url);

            const response = await fetch(url);
            const data = await response.json();
            console.log('[VPB] Response:', data);

            if (data.success && data.data && data.data.elements) {
                state.elements = data.data.elements;
                console.log('[VPB] Loaded', state.elements.length, 'elements from cart');
                // Don't call render() here - init() will do it after await
                showToast(vpbData.i18n?.loadedFromCart || 'Design loaded from cart');
            } else {
                console.warn('[VPB] Cart item not found or no elements, falling back to localStorage');
                loadFromStorage();
            }
        } catch (error) {
            console.error('[VPB] Failed to load from cart:', error);
            loadFromStorage();
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
