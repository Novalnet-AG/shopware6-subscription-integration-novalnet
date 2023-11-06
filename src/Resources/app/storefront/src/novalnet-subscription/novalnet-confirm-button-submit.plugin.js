import FormSubmitLoaderPlugin from 'src/plugin/forms/form-submit-loader.plugin';

export default class FormSubmitLoaderWithText extends FormSubmitLoaderPlugin {
    static options = {
        confirmButtonText: '...'
    }

    _onFormSubmit() {
		
        this._submitButton.innerText = this.options.confirmButtonText;
        super._onFormSubmit();
    }
}
