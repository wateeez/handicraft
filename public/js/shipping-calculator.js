/**
 * Shipping Calculator Module
 * 
 * AJAX-based shipping charge calculator for e-commerce application.
 * Calculates shipping costs based on product dimensions and weight.
 * 
 * Features:
 * - Real-time shipping calculation via Fetch API
 * - Support for both single product and cart calculations
 * - Volumetric weight comparison
 * - Error handling and validation feedback
 * - Loading states and user feedback
 * 
 * @author Development Team
 * @version 1.0.0
 */

(function (window) {
    'use strict';

    /**
     * ShippingCalculator Class
     * Handles all shipping calculation operations via AJAX
     */
    class ShippingCalculator {
        /**
         * Initialize the shipping calculator
         * @param {Object} options Configuration options
         */
        constructor(options = {}) {
            this.baseUrl = options.baseUrl || '/api/shipping';
            this.onCalculate = options.onCalculate || null;
            this.onError = options.onError || null;
            this.onLoading = options.onLoading || null;
            this.csrfToken = this.getCsrfToken();
        }

        /**
         * Get CSRF token from meta tag
         * @returns {string|null}
         */
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        }

        /**
         * Get default headers for API requests
         * @returns {Object}
         */
        getHeaders() {
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            if (this.csrfToken) {
                headers['X-CSRF-TOKEN'] = this.csrfToken;
            }

            return headers;
        }

        /**
         * Calculate shipping charge for a single product
         * 
         * @param {Object} productData Product dimensions and weight
         * @param {number} productData.length Length in centimeters
         * @param {number} productData.width Width in centimeters
         * @param {number} productData.height Height in centimeters
         * @param {number} productData.actual_weight Weight in kilograms
         * @param {number} [productData.shipping_method_id] Optional shipping method ID
         * @returns {Promise<Object>} Shipping calculation result
         * 
         * @example
         * const calculator = new ShippingCalculator();
         * const result = await calculator.calculate({
         *     length: 30,
         *     width: 20,
         *     height: 15,
         *     actual_weight: 2.5,
         *     shipping_method_id: 1
         * });
         */
        async calculate(productData) {
            this.triggerLoading(true);

            try {
                const response = await fetch(`${this.baseUrl}/calculate`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        length: parseFloat(productData.length),
                        width: parseFloat(productData.width),
                        height: parseFloat(productData.height),
                        actual_weight: parseFloat(productData.actual_weight),
                        shipping_method_id: productData.shipping_method_id || null
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new ShippingCalculationError(
                        data.message || 'Calculation failed',
                        data.errors || {},
                        response.status
                    );
                }

                this.triggerCalculate(data);
                return data;

            } catch (error) {
                this.triggerError(error);
                throw error;
            } finally {
                this.triggerLoading(false);
            }
        }

        /**
         * Calculate shipping for multiple items (cart)
         * 
         * @param {Array<Object>} items Array of products
         * @param {number} [shippingMethodId] Optional shipping method ID
         * @returns {Promise<Object>} Shipping calculation result
         * 
         * @example
         * const result = await calculator.calculateCart([
         *     { length: 30, width: 20, height: 15, actual_weight: 2.5, quantity: 2 },
         *     { length: 10, width: 10, height: 10, actual_weight: 0.5, quantity: 1 }
         * ], 1);
         */
        async calculateCart(items, shippingMethodId = null) {
            this.triggerLoading(true);

            try {
                const response = await fetch(`${this.baseUrl}/calculate-cart`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        items: items.map(item => ({
                            length: parseFloat(item.length),
                            width: parseFloat(item.width),
                            height: parseFloat(item.height),
                            actual_weight: parseFloat(item.actual_weight),
                            quantity: parseInt(item.quantity) || 1
                        })),
                        shipping_method_id: shippingMethodId
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new ShippingCalculationError(
                        data.message || 'Cart calculation failed',
                        data.errors || {},
                        response.status
                    );
                }

                this.triggerCalculate(data);
                return data;

            } catch (error) {
                this.triggerError(error);
                throw error;
            } finally {
                this.triggerLoading(false);
            }
        }

        /**
         * Get available shipping methods
         * @returns {Promise<Object>} Shipping methods with rates
         */
        async getMethods() {
            try {
                const response = await fetch(`${this.baseUrl}/methods`, {
                    method: 'GET',
                    headers: this.getHeaders()
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new ShippingCalculationError(
                        data.message || 'Failed to fetch shipping methods',
                        {},
                        response.status
                    );
                }

                return data;

            } catch (error) {
                this.triggerError(error);
                throw error;
            }
        }

        /**
         * Get quick quote (volumetric weight comparison)
         * 
         * @param {Object} dimensions Product dimensions
         * @returns {Promise<Object>} Quick quote result
         */
        async getQuickQuote(dimensions) {
            try {
                const params = new URLSearchParams({
                    length: dimensions.length,
                    width: dimensions.width,
                    height: dimensions.height,
                    actual_weight: dimensions.actual_weight
                });

                const response = await fetch(`${this.baseUrl}/quick-quote?${params}`, {
                    method: 'GET',
                    headers: this.getHeaders()
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new ShippingCalculationError(
                        data.message || 'Quick quote failed',
                        {},
                        response.status
                    );
                }

                return data;

            } catch (error) {
                this.triggerError(error);
                throw error;
            }
        }

        /**
         * Trigger onCalculate callback
         * @param {Object} data Calculation result
         */
        triggerCalculate(data) {
            if (typeof this.onCalculate === 'function') {
                this.onCalculate(data);
            }
        }

        /**
         * Trigger onError callback
         * @param {Error} error Error object
         */
        triggerError(error) {
            if (typeof this.onError === 'function') {
                this.onError(error);
            }
        }

        /**
         * Trigger onLoading callback
         * @param {boolean} isLoading Loading state
         */
        triggerLoading(isLoading) {
            if (typeof this.onLoading === 'function') {
                this.onLoading(isLoading);
            }
        }
    }

    /**
     * Custom error class for shipping calculation errors
     */
    class ShippingCalculationError extends Error {
        constructor(message, errors = {}, statusCode = 500) {
            super(message);
            this.name = 'ShippingCalculationError';
            this.errors = errors;
            this.statusCode = statusCode;
        }

        /**
         * Get validation errors as a formatted string
         * @returns {string}
         */
        getFormattedErrors() {
            if (!this.errors || Object.keys(this.errors).length === 0) {
                return this.message;
            }

            return Object.entries(this.errors)
                .map(([field, messages]) => {
                    const msgList = Array.isArray(messages) ? messages : [messages];
                    return `${field}: ${msgList.join(', ')}`;
                })
                .join('\n');
        }
    }

    /**
     * Shipping Calculator Form Handler
     * Automatically binds to forms with shipping calculator functionality
     */
    class ShippingCalculatorForm {
        /**
         * Initialize form handler
         * @param {string|HTMLElement} formSelector Form element or CSS selector
         * @param {Object} options Configuration options
         */
        constructor(formSelector, options = {}) {
            this.form = typeof formSelector === 'string'
                ? document.querySelector(formSelector)
                : formSelector;

            if (!this.form) {
                console.error('Shipping calculator form not found');
                return;
            }

            this.options = {
                lengthField: options.lengthField || '[name="length"]',
                widthField: options.widthField || '[name="width"]',
                heightField: options.heightField || '[name="height"]',
                weightField: options.weightField || '[name="actual_weight"]',
                methodField: options.methodField || '[name="shipping_method_id"]',
                resultContainer: options.resultContainer || '.shipping-result',
                submitButton: options.submitButton || '[type="submit"]',
                autoCalculate: options.autoCalculate || false,
                debounceDelay: options.debounceDelay || 500,
                ...options
            };

            this.calculator = new ShippingCalculator({
                baseUrl: options.baseUrl || '/api/shipping',
                onCalculate: this.handleCalculateSuccess.bind(this),
                onError: this.handleCalculateError.bind(this),
                onLoading: this.handleLoading.bind(this)
            });

            this.init();
        }

        /**
         * Initialize form bindings
         */
        init() {
            // Bind form submit
            this.form.addEventListener('submit', this.handleSubmit.bind(this));

            // Auto-calculate on input change if enabled
            if (this.options.autoCalculate) {
                const fields = [
                    this.options.lengthField,
                    this.options.widthField,
                    this.options.heightField,
                    this.options.weightField
                ];

                fields.forEach(selector => {
                    const field = this.form.querySelector(selector);
                    if (field) {
                        field.addEventListener('input', this.debounce(() => {
                            this.submitForm();
                        }, this.options.debounceDelay));
                    }
                });
            }
        }

        /**
         * Handle form submission
         * @param {Event} e Submit event
         */
        async handleSubmit(e) {
            e.preventDefault();
            await this.submitForm();
        }

        /**
         * Submit the form and calculate shipping
         */
        async submitForm() {
            const formData = this.getFormData();

            // Validate required fields
            if (!this.validateFormData(formData)) {
                return;
            }

            try {
                await this.calculator.calculate(formData);
            } catch (error) {
                console.error('Shipping calculation failed:', error);
            }
        }

        /**
         * Get form data as object
         * @returns {Object}
         */
        getFormData() {
            const getValue = (selector) => {
                const field = this.form.querySelector(selector);
                return field ? field.value : null;
            };

            return {
                length: getValue(this.options.lengthField),
                width: getValue(this.options.widthField),
                height: getValue(this.options.heightField),
                actual_weight: getValue(this.options.weightField),
                shipping_method_id: getValue(this.options.methodField)
            };
        }

        /**
         * Validate form data
         * @param {Object} data Form data
         * @returns {boolean}
         */
        validateFormData(data) {
            const errors = [];

            if (!data.length || parseFloat(data.length) <= 0) {
                errors.push('Length is required and must be greater than 0');
            }
            if (!data.width || parseFloat(data.width) <= 0) {
                errors.push('Width is required and must be greater than 0');
            }
            if (!data.height || parseFloat(data.height) <= 0) {
                errors.push('Height is required and must be greater than 0');
            }
            if (!data.actual_weight || parseFloat(data.actual_weight) <= 0) {
                errors.push('Weight is required and must be greater than 0');
            }

            if (errors.length > 0) {
                this.displayErrors(errors);
                return false;
            }

            return true;
        }

        /**
         * Handle successful calculation
         * @param {Object} response API response
         */
        handleCalculateSuccess(response) {
            const container = document.querySelector(this.options.resultContainer);
            if (!container) return;

            const data = response.data;
            container.innerHTML = this.renderResult(data);
            container.classList.remove('error');
            container.classList.add('success');
        }

        /**
         * Handle calculation error
         * @param {Error} error Error object
         */
        handleCalculateError(error) {
            const container = document.querySelector(this.options.resultContainer);
            if (!container) return;

            const message = error instanceof ShippingCalculationError
                ? error.getFormattedErrors()
                : error.message;

            container.innerHTML = `
                <div class="shipping-error">
                    <strong>Error:</strong> ${this.escapeHtml(message)}
                </div>
            `;
            container.classList.remove('success');
            container.classList.add('error');
        }

        /**
         * Handle loading state
         * @param {boolean} isLoading Loading state
         */
        handleLoading(isLoading) {
            const button = this.form.querySelector(this.options.submitButton);
            if (button) {
                button.disabled = isLoading;
                button.classList.toggle('loading', isLoading);
            }

            const container = document.querySelector(this.options.resultContainer);
            if (container && isLoading) {
                container.innerHTML = '<div class="shipping-loading">Calculating...</div>';
            }
        }

        /**
         * Display validation errors
         * @param {Array<string>} errors Error messages
         */
        displayErrors(errors) {
            const container = document.querySelector(this.options.resultContainer);
            if (!container) return;

            container.innerHTML = `
                <div class="shipping-error">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        ${errors.map(err => `<li>${this.escapeHtml(err)}</li>`).join('')}
                    </ul>
                </div>
            `;
            container.classList.remove('success');
            container.classList.add('error');
        }

        /**
         * Render calculation result HTML
         * @param {Object} data Calculation data
         * @returns {string} HTML string
         */
        renderResult(data) {
            if (!data.shipping_charge) {
                return `
                    <div class="shipping-no-rate">
                        <p>Unable to calculate shipping. Please contact support.</p>
                    </div>
                `;
            }

            const weightTypeLabel = data.weight_type === 'volumetric'
                ? 'Volumetric Weight (higher)'
                : 'Actual Weight (higher)';

            return `
                <div class="shipping-result-content">
                    <div class="shipping-charge">
                        <span class="label">Shipping Charge:</span>
                        <span class="value">$${data.shipping_charge.toFixed(2)}</span>
                    </div>
                    <div class="shipping-details">
                        <div class="detail-row">
                            <span class="label">Weight Used:</span>
                            <span class="value">${data.weight_used} kg (${weightTypeLabel})</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Actual Weight:</span>
                            <span class="value">${data.actual_weight} kg</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Volumetric Weight:</span>
                            <span class="value">${data.volumetric_weight} kg</span>
                        </div>
                        ${data.shipping_method ? `
                            <div class="detail-row">
                                <span class="label">Shipping Method:</span>
                                <span class="value">${this.escapeHtml(data.shipping_method.name)}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Estimated Delivery:</span>
                                <span class="value">${this.escapeHtml(data.shipping_method.estimated_days || 'N/A')}</span>
                            </div>
                        ` : ''}
                        ${data.rate_tier ? `
                            <div class="detail-row">
                                <span class="label">Rate Tier:</span>
                                <span class="value">${data.rate_tier.min_weight} - ${data.rate_tier.max_weight} kg @ $${data.rate_tier.price_per_kg}/kg</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        /**
         * Escape HTML to prevent XSS
         * @param {string} text Text to escape
         * @returns {string}
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        /**
         * Debounce function for auto-calculate
         * @param {Function} func Function to debounce
         * @param {number} wait Wait time in milliseconds
         * @returns {Function}
         */
        debounce(func, wait) {
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
    }

    // Expose to global scope
    window.ShippingCalculator = ShippingCalculator;
    window.ShippingCalculatorForm = ShippingCalculatorForm;
    window.ShippingCalculationError = ShippingCalculationError;

})(window);
