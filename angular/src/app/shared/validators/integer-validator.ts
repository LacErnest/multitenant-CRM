import { FormControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export const integerValidator: ValidatorFn = (
  control: FormControl
): ValidationErrors | null => {
  if (typeof control.value === 'string') {
    const convertedValue = Number(control.value);

    return Number.isInteger(convertedValue) ? null : { integer: true };
  }

  return Number.isInteger(control.value) ? null : { integer: true };
};
