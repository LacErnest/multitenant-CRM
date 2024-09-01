import { ValidationErrors, ValidatorFn } from '@angular/forms';

export function ConditionalRequiredValidator(
  conditionalExpr: () => boolean
): ValidatorFn {
  return (control): ValidationErrors | null => {
    if (conditionalExpr()) {
      return control.value !== null ? null : { required: true };
    } else {
      return null;
    }
  };
}
