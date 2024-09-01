import { FormControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export function numberValidator(allowFalsyValue = false): ValidatorFn {
  // If you want to check for null, do no user type="number", as non numeric values will return null and pass the check
  return (control: FormControl): ValidationErrors | null => {
    if (allowFalsyValue) {
      return window.isNaN(control.value) ? { number: true } : null;
    } else {
      return window.isNaN(control.value) || !control.value
        ? { number: true }
        : null;
    }
  };
}
