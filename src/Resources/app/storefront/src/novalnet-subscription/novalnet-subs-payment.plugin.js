import Plugin from 'src/plugin-system/plugin.class';

export default class NovalnetSubsPaymentForm extends Plugin {
	
	init() {
		const paymentRadioButton = document.querySelectorAll('input[name="paymentMethodId"]');
		var hideSubmitButton = false;
		// Show/hide the payment form based on the payment selected
		paymentRadioButton.forEach((payment) => {
			if(payment.checked == true)
			{
				hideSubmitButton = true;
			}
		});
		
		if(hideSubmitButton == false)
		{
			this._disableSubmitButton();
		}
	}
	
	_disableSubmitButton() {

		var button = document.querySelector('#confirmOrderForm button');

        if (button) {
            button.setAttribute('disabled', 'disabled');
        }
    }
}
