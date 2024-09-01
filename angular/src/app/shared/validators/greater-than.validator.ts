import { FormGroup, ValidationErrors, ValidatorFn } from '@angular/forms';

export function greaterThanValidator(
  lesserControlPath: string,
  greaterControlPath: string
): ValidatorFn {
  return (form: FormGroup): ValidationErrors | null => {
    const lesser = form.get(lesserControlPath);
    const greater = form.get(greaterControlPath);

    if (!lesser) {
      throw new Error(
        'No control found at path: ' +
          lesserControlPath +
          '. Check if the path is correct.'
      );
    }
    if (!greater) {
      throw new Error(
        'No control found at path: ' +
          greaterControlPath +
          '. Check if the path is correct.'
      );
    }

    try {
      const lesserValue = lesser.value as number;
      const greaterValue = greater.value as number;
      if (lesserValue && greaterValue && lesserValue > greaterValue) {
        return { greater_than: true };
      } else {
        return null;
      }
    } catch (error) {
      console.error(error);
    }
  };
}
