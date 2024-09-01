import { transition, trigger, useAnimation } from '@angular/animations';
import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { integerValidator } from 'src/app/shared/validators/integer-validator';
import { numberValidator } from 'src/app/shared/validators/number.validator';
import { DocumentSettingsService } from 'src/app/views/legal-entities/modules/document-settings/document-settings.service';
import { controlHasErrors } from 'src/app/core/classes/helpers';

@Component({
  selector: 'oz-finance-document-settings-form',
  templateUrl: './document-settings-form.component.html',
  styleUrls: ['./document-settings-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class DocumentSettingsFormComponent implements OnInit {
  public isLoading = false;
  public settingsForm: FormGroup;

  public constructor(
    private fb: FormBuilder,
    private documentSettingsService: DocumentSettingsService,
    private route: ActivatedRoute,
    private router: Router,
    private toast: ToastrService
  ) {}

  public ngOnInit(): void {
    this.initSettingsForm();
    this.getResolvedData();
  }

  public get cannotSubmit(): boolean {
    return (
      this.isLoading || this.settingsForm?.invalid || !this.settingsForm?.dirty
    );
  }

  public settingsControlHasErrors(controlName: string): boolean {
    return controlHasErrors(this.settingsForm.controls[controlName]);
  }

  public showRequiredError(controlName: string): boolean {
    return (
      this.settingsForm?.controls[controlName]?.errors?.required &&
      this.settingsForm?.controls[controlName]?.dirty
    );
  }

  public showValidationError(controlName: string): boolean {
    return this.settingsForm.controls[controlName]?.errors?.validationErr;
  }

  public submitForm(): void {
    this.isLoading = true;

    this.documentSettingsService
      .editSettings(this.settingsForm.value)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.settingsForm.patchValue(response);
          this.settingsForm.markAsPristine();
          this.toast.success('Settings were successfully changed.', 'Success');
        },
        error => {
          for (const errKey in error?.message) {
            this.settingsForm.controls[errKey]?.setErrors({
              validationErr: error?.message[errKey][0],
            });
          }

          this.toast.error('Sorry, settings were not changed.', 'Error');
        }
      );
  }

  private initSettingsForm(): void {
    this.settingsForm = this.fb.group({
      quote_number: new FormControl('', [
        numberValidator(true),
        integerValidator,
        Validators.required,
      ]),
      quote_number_format: new FormControl('', Validators.required),
      order_number: new FormControl('', [
        numberValidator(true),
        integerValidator,
        Validators.required,
      ]),
      order_number_format: new FormControl('', Validators.required),
      invoice_number: new FormControl('', [
        numberValidator(true),
        integerValidator,
        Validators.required,
      ]),
      invoice_number_format: new FormControl('', Validators.required),
      purchase_order_number: new FormControl('', [
        numberValidator(true),
        integerValidator,
        Validators.required,
      ]),
      purchase_order_number_format: new FormControl('', Validators.required),
      resource_invoice_number_format: new FormControl('', Validators.required),
      resource_invoice_number: new FormControl('', [
        numberValidator(true),
        integerValidator,
        Validators.required,
      ]),
    });
  }

  private getResolvedData = () => {
    const settings = this.route.snapshot.data.settings;
    this.settingsForm.patchValue(settings);
  };
}
