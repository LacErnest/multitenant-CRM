import { FormGroup, ValidationErrors, ValidatorFn } from '@angular/forms';

export function passwordMatchValidator(
  passwordControlPath: string,
  passwordConfirmControlPath: string
): ValidatorFn {
  return (form: FormGroup): ValidationErrors | null => {
    const password = form.get(passwordControlPath);
    const passwordConfirm = form.get(passwordConfirmControlPath);

    if (!password) {
      throw new Error(
        'No control found at path: ' +
          passwordControlPath +
          '. Check if the path is correct.'
      );
    }
    if (!passwordConfirm) {
      throw new Error(
        'No control found at path: ' +
          passwordConfirmControlPath +
          '. Check if the path is correct.'
      );
    }

    try {
      if (password.value === passwordConfirm.value) {
        return null;
      } else {
        return { password_match: true };
      }
    } catch (error) {
      console.error(error);
    }
  };
}
