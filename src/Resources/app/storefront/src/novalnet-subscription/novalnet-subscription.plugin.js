import Plugin from 'src/plugin-system/plugin.class';

export default class NovalnetSubsProductForm extends Plugin {
	
	init() {
		const subscriptionData = document.querySelector('.novalnet--subscription-data');
		
		if(subscriptionData)
		{
			var me = this;
			this.novalnetProductdata = JSON.parse(subscriptionData.innerHTML);
			const productRadioButton = document.querySelectorAll('input[name=subsSelection]');
			const multipleSubscription = document.querySelectorAll('input[name=interval]');
			
			// Multiple Subscription Radio button in product detail page.
			multipleSubscription.forEach((el) => {
				el.addEventListener('click', () => {
					me.radionButtonClicked(el);
				});
			});
			
			// Radio button in product detail page. 
			productRadioButton.forEach((el) => {
				el.addEventListener('click', () => {
					me.hideSummary(el);
				});
			});
			
			if (this.novalnetProductdata.multipleSubscription == true && this.novalnetProductdata.multiSubscriptionOptions != null && document.querySelectorAll('input[name=interval]:checked').length == 0)
			{
				if (this.novalnetProductdata.multiSubscriptionOptions.length > 0)
				{
					this._disableSubmitButton();
				}
			}
		}
	}
	
	hideSummary(el) {
		const singleContentBlock = document.querySelector('.singleDeliveryBlock');
		const subsContentBlock   = document.querySelector('.subscriptionBlock');
		
		if(el.value == 'singleDelivery')
		{
			if(el.checked == true) {
				singleContentBlock.classList.remove("nnhide");
				subsContentBlock.classList.add("nnhide");
			}
		} else if (el.value == 'subscription')
		{
			if(el.checked == true) {
				subsContentBlock.classList.remove("nnhide");
				singleContentBlock.classList.add("nnhide");
			}
		}
    }
    
    radionButtonClicked(el) {
		var length = {'dailyDelivery' : 1, 'weeklyDelivery' : 1, 'Every2WeekDelivery' : 2, 'Every3WeekDelivery' : 3, 'Every6WeekDelivery' : 6, 'monthlyDelivery' : 1, 'Every2MonthDelivery' : 2, 'Every3MonthDelivery' : 3, 'Every4MonthDelivery' : 4, 'halfYearlyDelivery' : 6, 'Every9MonthDelivery' : 9, 'yearlyDelivery' : 1};
		var period = {'dailyDelivery' : 'days', 'weeklyDelivery' : 'weeks', 'Every2WeekDelivery' : 'weeks', 'Every3WeekDelivery' : 'weeks', 'Every6WeekDelivery' : 'weeks', 'monthlyDelivery' : 'months', 'Every2MonthDelivery' : 'months', 'Every3MonthDelivery' : 'months', 'Every4MonthDelivery' : 'months', 'halfYearlyDelivery' : 'months', 'Every9MonthDelivery' : 'months', 'yearlyDelivery' : 'years'};
		var currentLength = this.novalnetProductdata.operationalMonth;
		var interval = length[el.value];
		
		if(this.novalnetProductdata.multiSubscriptionOptions != undefined && this.novalnetProductdata.operationalMonth != null && this.novalnetProductdata.operationalMonth != 0)
		{
			var currentLength = this.novalnetProductdata.operationalMonth * length[el.value];
			var currentPeriod = document.querySelector('#' + period[el.value]);
			document.querySelector('.subscriptionPeriod').innerHTML = ' ' + currentLength + ' ' + currentPeriod.value;
		}
		
		var product = JSON.parse(document.querySelector('input[name="redirectParameters"]').value);
		document.querySelector('.SubscriptionLineItemProductId').value = product.productId + '_' + interval + '_' + currentLength;
		
		var button = document.querySelector('#novalnetaddsubscription');
        if (button) {
            button.removeAttribute('disabled');
        }
        
			
		if(this.novalnetProductdata.discountScope =='cycleduration' && this.novalnetProductdata.discountDetails != undefined && this.novalnetProductdata.discountDetails != null) {
			
			let discountDetails = JSON.parse(this.novalnetProductdata.discountDetails);
			var cycleduration = document.querySelector('#cycleduration');
			var currency = document.querySelector('#currency');
			let isMatched = false;
			Object.values(discountDetails).forEach(values => {
				let symbol ='%';
				if(values.period == el.value) {
					isMatched = true;
					if(values.type != 'percentage' ){
						symbol = currency.value;
					}
					
					if(values.discount != 0){	
					  document.querySelector('.subscriptionPeriodDiscount').innerHTML = ' ' + values.discount + ' ' + symbol + ' ' + cycleduration.value;
				    }
				} 
				
				if(isMatched == false) {
					document.querySelector('.subscriptionPeriodDiscount').innerHTML = '';
				}

			});
		}
	}
    
    _disableSubmitButton() {
        var button = document.querySelector('#novalnetaddsubscription');
        if (button) {
            button.setAttribute('disabled', 'disabled');
        }
    }
}
