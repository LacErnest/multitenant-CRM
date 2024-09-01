import { Injectable } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { ToastrService } from 'ngx-toastr';
import { ErrorMessage } from '../types/error-message.type';
import { HttpErrorResponse } from '@angular/common/http';
@Injectable({
  providedIn: 'root',
})
export class ErrorHandlerService {
  errorMap = {
    404: 'The requested resource could not be found.',
    500: 'An internal server error has occurred. Please try again later.',
    422: 'Invalid request data.',
  };

  constructor(
    private fb: FormBuilder,
    private toastService: ToastrService
  ) {}

  handle(
    error: HttpErrorResponse,
    fb?: FormGroup,
    customMessage?: string
  ): void {
    if (error.status === 422) {
      if (error?.error?.errors) {
        this.displayValidationErrors(error?.error?.errors, fb);
      } else if (error?.message) {
        this.displayValidationErrors(error?.message, fb);
      } else {
        this.displayCommonError(error, customMessage);
      }
    } else {
      this.displayCommonError(error, customMessage);
    }
  }

  private displayValidationErrors(errors: ErrorMessage, fb: FormGroup): void {
    if (!fb || typeof errors === 'string') {
      this.displayFIrstError(errors);
    } else {
      for (const key in errors) {
        const [message] = errors[key];
        const formControl = fb.controls[key];
        if (formControl) {
          formControl.setErrors({ serverError: this.getErrorMessage(message) });
          formControl.markAsDirty();
          formControl.markAsTouched();
        }
      }
    }
  }

  private displayFIrstError(errors: ErrorMessage): void {
    const message =
      typeof errors === 'string' ? errors : Object.values(errors)[0];
    this.toastService.error(this.getErrorMessage(message), 'Error');
  }

  private displayCommonError(error, customMessage?: string): void {
    if (error.status === 422) {
      this.toastService.error(
        customMessage || this.errorMap[error.status],
        'Error'
      );
    } else if (error.message) {
      this.toastService.error(
        customMessage || this.errorMap[error.status] || error.message
      );
    } else {
      this.toastService.error(
        customMessage ||
          this.errorMap[error.status] ||
          'Something has gone wrong.',
        'Error'
      );
    }
  }

  /**
   *
   * @param message
   * @returns string
   */
  private getErrorMessage(message: string | Array<string>): string {
    if (typeof message === 'string') {
      return message;
    }
    return message[0];
  }
}
