const ApiService = Shopware.Classes.ApiService;

class NovalPaymentSubsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'novalnet-subscription') {
        super(httpClient, loginService, apiEndpoint);
    }
    
    getSubsOrder(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/getOrders`,
                {
					orderId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    pauseSubscription(aboId, cancelReason, customerId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/pauseSubs`,
                {
					aboId,
					cancelReason,
					customerId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    renewalSubscription(aboId, length) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/renewal`,
                {
					aboId,
					length
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    manualExecutionSubscription(aboId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/manualExecution`,
                {
					aboId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    
    dateChange(aboId, nextDate, reason, subOrders) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/dateChange`,
                {
					aboId,
					nextDate,
					reason,
					subOrders
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    cancel(aboId, cancelReason, customerId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/cancel`,
                {
					aboId,
					cancelReason,
					customerId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    active(aboId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/active`,
                {
					aboId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    updateProduct(aboId, productId, quantity) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/updateProduct`,
                {
					aboId,
					productId,
					quantity
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    updateProductQuantity(aboId, quantity) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/updateProductQuantity`,
                {
					aboId,
					quantity
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    getNovalnetOrderPaymentName(orderNumber) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/NovalnetOrderPaymentName`,
                {
					orderNumber
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    novalnetDiscount(cycle, discountType, discountValue, product) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/NovalnetProductDiscount`,
                {
					cycle,
					discountType, 
					discountValue, 
					product
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    discountUpdate(cycle, discountType, discountValue, peroidTerm, productId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/updateDiscount`,
                {
					cycle,
					discountType, 
					discountValue,
					peroidTerm, 
					productId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    
    discountDelete(item, productId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/DiscountDelete`,
                {
					item, 
					productId
				},
				{
					headers: this.getBasicHeaders()
				}
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default NovalPaymentSubsService;
