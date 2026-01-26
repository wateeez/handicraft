/**
 * Axios-based Shipping Calculator
 * 
 * Alternative implementation using Axios instead of Fetch API
 * For developers who prefer Axios for HTTP requests
 */

class AxiosShippingCalculator {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api/shipping';
        this.onCalculate = options.onCalculate || null;
        this.onError = options.onError || null;
        this.onLoading = options.onLoading || null;

        // Configure Axios defaults
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
            }
        }
    }

    /**
     * Calculate shipping for a single product
     */
    async calculate(productData) {
        this.triggerLoading(true);

        try {
            const response = await axios.post(`${this.baseUrl}/calculate`, {
                length: parseFloat(productData.length),
                width: parseFloat(productData.width),
                height: parseFloat(productData.height),
                actual_weight: parseFloat(productData.actual_weight),
                shipping_method_id: productData.shipping_method_id || null
            });

            this.triggerCalculate(response.data);
            return response.data;

        } catch (error) {
            const errorObj = this.handleAxiosError(error);
            this.triggerError(errorObj);
            throw errorObj;
        } finally {
            this.triggerLoading(false);
        }
    }

    /**
     * Calculate shipping for cart
     */
    async calculateCart(items, shippingMethodId = null) {
        this.triggerLoading(true);

        try {
            const response = await axios.post(`${this.baseUrl}/calculate-cart`, {
                items: items.map(item => ({
                    length: parseFloat(item.length),
                    width: parseFloat(item.width),
                    height: parseFloat(item.height),
                    actual_weight: parseFloat(item.actual_weight),
                    quantity: parseInt(item.quantity) || 1
                })),
                shipping_method_id: shippingMethodId
            });

            this.triggerCalculate(response.data);
            return response.data;

        } catch (error) {
            const errorObj = this.handleAxiosError(error);
            this.triggerError(errorObj);
            throw errorObj;
        } finally {
            this.triggerLoading(false);
        }
    }

    /**
     * Get shipping methods
     */
    async getMethods() {
        try {
            const response = await axios.get(`${this.baseUrl}/methods`);
            return response.data;
        } catch (error) {
            const errorObj = this.handleAxiosError(error);
            this.triggerError(errorObj);
            throw errorObj;
        }
    }

    /**
     * Get quick quote
     */
    async getQuickQuote(dimensions) {
        try {
            const response = await axios.get(`${this.baseUrl}/quick-quote`, {
                params: {
                    length: dimensions.length,
                    width: dimensions.width,
                    height: dimensions.height,
                    actual_weight: dimensions.actual_weight
                }
            });
            return response.data;
        } catch (error) {
            const errorObj = this.handleAxiosError(error);
            this.triggerError(errorObj);
            throw errorObj;
        }
    }

    /**
     * Handle Axios errors
     */
    handleAxiosError(error) {
        if (error.response) {
            // Server responded with error
            return {
                message: error.response.data.message || 'Request failed',
                errors: error.response.data.errors || {},
                statusCode: error.response.status
            };
        } else if (error.request) {
            // Request made but no response
            return {
                message: 'No response from server',
                errors: {},
                statusCode: 0
            };
        } else {
            // Error setting up request
            return {
                message: error.message,
                errors: {},
                statusCode: 0
            };
        }
    }

    triggerCalculate(data) {
        if (typeof this.onCalculate === 'function') {
            this.onCalculate(data);
        }
    }

    triggerError(error) {
        if (typeof this.onError === 'function') {
            this.onError(error);
        }
    }

    triggerLoading(isLoading) {
        if (typeof this.onLoading === 'function') {
            this.onLoading(isLoading);
        }
    }
}

// Expose to global scope
if (typeof window !== 'undefined') {
    window.AxiosShippingCalculator = AxiosShippingCalculator;
}

// Example usage:
/*
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="/js/shipping-calculator-axios.js"></script>

<script>
const calculator = new AxiosShippingCalculator({
    baseUrl: '/api/shipping',
    onCalculate: (data) => console.log('Success:', data),
    onError: (error) => console.error('Error:', error),
    onLoading: (loading) => console.log('Loading:', loading)
});

// Use it
calculator.calculate({
    length: 30,
    width: 20,
    height: 15,
    actual_weight: 2.5
}).then(result => {
    console.log('Shipping charge:', result.data.shipping_charge);
});
</script>
*/
