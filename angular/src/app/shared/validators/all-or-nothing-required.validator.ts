import {
  AbstractControl,
  FormGroup,
  ValidationErrors,
  ValidatorFn,
} from '@angular/forms';

/* If one control has a value, all others must have one as well */
export function AllOrNothingRequiredValidator(
  controlPaths: string[],
  allowZero = false
): ValidatorFn {
  return (form: FormGroup): ValidationErrors | null => {
    const valueControls: AbstractControl[] = [];

    for (const controlPath of controlPaths) {
      const control = form.get(controlPath);

      if (control) {
        if (control.value || (allowZero && control.value === 0)) {
          valueControls.push(control);
        }
      } else {
        throw new Error('Control with path: ' + controlPath + ' not found.');
      }
    }

    if (
      valueControls.length > 0 &&
      valueControls.length !== controlPaths.length
    ) {
      return { ['required_' + controlPaths.join('_')]: true };
    } else {
      return null;
    }
  };
}
