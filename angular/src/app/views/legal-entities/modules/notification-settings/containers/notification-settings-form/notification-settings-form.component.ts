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
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  finalize,
  map,
  switchMap,
  tap,
} from 'rxjs/operators';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { integerValidator } from 'src/app/shared/validators/integer-validator';
import { numberValidator } from 'src/app/shared/validators/number.validator';
import { NotificationSettingsService } from 'src/app/views/legal-entities/modules/notification-settings/notification-settings.service';
import { controlHasErrors } from 'src/app/core/classes/helpers';
import { ContactSearchEntity } from 'src/app/views/projects/modules/project/interfaces/search-entity';
import { Observable, Subject, concat, of } from 'rxjs';
import { CompanyLegalEntitiesService } from 'src/app/views/settings/modules/company-legal-entities/company-legal-entities.service';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { HttpParams } from '@angular/common/http';
import { LegalEntityNotificationSettings } from '../../interfaces/notification-settings';

@Component({
  selector: 'oz-finance-notification-settings-form',
  templateUrl: './notification-settings-form.component.html',
  styleUrls: ['./notification-settings-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class NotificationSettingsFormComponent implements OnInit {
  public isLoading = false;
  public settingsForm: FormGroup;
  public contactSelect$: Observable<ContactSearchEntity[]>;
  public contactInput$: Subject<string> = new Subject<string>();
  public contactLoading = false;
  public notificationSettings: LegalEntityNotificationSettings;
  private defaultContacts: any[] = [];

  public constructor(
    private fb: FormBuilder,
    private notificationSettingsService: NotificationSettingsService,
    private suggestService: SuggestService,
    private route: ActivatedRoute,
    private router: Router,
    private toast: ToastrService
  ) {}

  public ngOnInit(): void {
    this.initSettingsForm();
    this.getResolvedData();
    this.initContactTypeAhead();
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
    this.notificationSettings?.legal_entity_id ? this.update() : this.create();
  }

  public create(): void {
    this.isLoading = true;

    this.notificationSettingsService
      .createSettings(this.settingsForm.value)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.settingsForm.patchValue(response);
          this.settingsForm.markAsPristine();
          this.notificationSettings = response;
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

  public update(): void {
    this.isLoading = true;

    this.notificationSettingsService
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
      enable_submited_invoice_notification: new FormControl(false, [
        Validators.required,
      ]),
      notification_contacts: new FormControl('', []),
      notification_footer: new FormControl('', [Validators.required]),
    });
  }

  private getResolvedData = () => {
    const settings = this.route.snapshot.data.settings;
    this.notificationSettings = settings;
    this.settingsForm.patchValue(settings);
    if (settings.notification_contacts) {
      this.defaultContacts = settings.notification_contacts.map((id, index) => {
        return { id, name: settings.notification_contacts_names[index] };
      });
    }
  };

  toggleSetting(setting: string): void {
    this.settingsForm
      .get(setting)
      .patchValue(!this.settingsForm.get(setting).value);
    this.settingsForm.get(setting).markAsDirty();
    if (!this.settingsForm.get(setting).value) {
      this.settingsForm.get('notification_contacts').disable();
      this.settingsForm.get('notification_footer').disable();
    } else {
      this.settingsForm.get('notification_contacts').enable();
      this.settingsForm.get('notification_footer').enable();
    }
  }

  private initContactTypeAhead(): void {
    this.contactSelect$ = concat(
      of(this.defaultContacts), // default items
      (this.contactSelect$ = this.contactInput$.pipe(
        filter(l => !!l),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => (this.contactLoading = true)),
        switchMap(term => {
          return this.suggestService
            .suggestContact(term, new HttpParams())
            .pipe(
              map(result => result),
              catchError(() => of([])), // empty list on error
              tap(() => (this.contactLoading = false))
            );
        })
      ))
    );
  }
}
