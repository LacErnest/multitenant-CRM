import { AbstractControl } from '@angular/forms';

export function emailsValidator(
  control: AbstractControl
): { [key: string]: boolean } | null {
  const emails = control.value as string[];
  const valid = !emails.some(
    email => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  );
  return valid ? null : { invalidEmails: false };
}
