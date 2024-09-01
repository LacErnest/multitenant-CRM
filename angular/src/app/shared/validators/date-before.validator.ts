import moment from 'moment';
import { FormGroup, ValidationErrors, ValidatorFn } from '@angular/forms';

export function dateBeforeValidator(
  beforeDatePath: string,
  afterDatePath: string
): ValidatorFn {
  return (form: FormGroup): ValidationErrors | null => {
    const beforeDate = form.get(beforeDatePath);
    const afterDate = form.get(afterDatePath);

    if (!beforeDate) {
      throw new Error(
        'No control found at path: ' +
          beforeDatePath +
          '. Check if the path is correct.'
      );
    }

    if (!afterDate) {
      throw new Error(
        'No control found at path: ' +
          afterDatePath +
          '. Check if the path is correct.'
      );
    }

    try {
      if (
        beforeDate.value &&
        afterDate.value &&
        moment(beforeDate.value).isAfter(moment(afterDate.value))
      ) {
        return { [beforeDatePath + '_' + afterDatePath]: true };
      } else {
        return null;
      }
    } catch (error) {
      console.error(error);
    }
  };
}
