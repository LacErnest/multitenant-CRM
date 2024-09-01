import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { finalize, skip } from 'rxjs/operators';
import { UsersService } from '../../users.service';
import { EnumService } from '../../../../../../core/services/enum.service';
import { ToastrService } from 'ngx-toastr';
import { GlobalService } from '../../../../../../core/services/global.service';
import { emailRegEx } from '../../../../../../shared/constants/regex';
import { Subscription } from 'rxjs';
import { TablePreferencesService } from '../../../../../../shared/services/table-preferences.service';
import { transition, trigger, useAnimation } from '@angular/animations';
import { ConditionalRequiredValidator } from '../../../../../../shared/validators/conditional-required.validator';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../../../shared/animations/browser-animations';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { CommissionModel } from '../../../../../../shared/enums/commission-model.enum';

@Component({
  selector: 'oz-finance-user-form',
  templateUrl: './user-form.component.html',
  styleUrls: ['./user-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class UserFormComponent implements OnInit, OnDestroy {
  isUserLoading = false;
  isMailLoading = false;

  userForm: FormGroup;
  user: any;
  userRoles: { key: number; value: string }[];
  commissionModels: { key: number; value: string }[];

  salesPersonRole = UserRole.SALES_PERSON;
  leadGenerationModel = CommissionModel.LEAD_GENERATION;
  leadGenerationModelB = CommissionModel.LEAD_GENERATION_B;
  salesSupportModel = CommissionModel.SALES_SUPPORT;
  customModel = CommissionModel.CUSTOM_MODEL_A;
  defaultPercentage = '3';
  salesSupportPercentage: number;
  defaultLeadPercentage = 10;
  defaultLeadBPercentage = 1;
  defaultSecondSalePercentage = 5;

  showMailSettings = false;
  mailForm: FormGroup;
  mailSettings: any;

  private companySub: Subscription;

  constructor(
    private enumService: EnumService,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private route: ActivatedRoute,
    private router: Router,
    private toastService: ToastrService,
    private tablePreferencesService: TablePreferencesService,
    private usersService: UsersService
  ) {}

  ngOnInit(): void {
    this.getEnum();
    this.getResolvedData();
    this.initSettingsForm();
    this.patchValueUserForm();

    if (this.user) {
      this.showMailSettings =
        this.user.id === this.globalService.userDetails.id &&
        this.globalService.getUserRole() === 1;
    }

    if (this.showMailSettings) {
      this.initMailForm();
      this.patchValueMailForm();
    }

    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.tablePreferencesService.removeTablePage(0);
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/settings/users']).then();
        }
      });
  }

  ngOnDestroy() {
    this.companySub?.unsubscribe();
  }

  getEnum(): void {
    this.userRoles = this.enumService.getEnumArray('userrole').filter(r => {
      if (this.globalService.getUserRole() !== 0) {
        return r.key !== 0;
      }
      return r;
    });
    this.commissionModels = this.enumService.getEnumArray('commissionmodel');
  }

  submitForm(): void {
    if (this.userForm.valid && !this.isUserLoading) {
      this.user ? this.updateUser() : this.createUser();
    }
  }

  submitMailForm(): void {
    if (this.mailForm.valid && !this.isMailLoading) {
      this.editMailSettings();
    }
  }

  private getResolvedData(): void {
    this.user = this.route.snapshot.data.user;
    this.mailSettings = this.route.snapshot.data.mailSettings;
    this.salesSupportPercentage =
      this.globalService.getCompanySalesSupportPercentage();
  }

  private createUser(): void {
    this.isUserLoading = true;
    this.usersService
      .createUser(this.userForm.value)
      .pipe(finalize(() => (this.isUserLoading = false)))
      .subscribe(
        response => {
          this.router
            .navigate([
              `/${this.globalService.currentCompany.id}/settings/users/${response.id}/edit`,
            ])
            .then();
          this.toastService.success(
            'User has been successfully created',
            'Success'
          );
        },
        error => {
          if (error.message.email) {
            const [email_error] = error.message.email;
            this.userForm.controls.email.setErrors({ error: email_error });
          }
          this.toastService.error('User has not been created', 'Error');
        }
      );
  }

  private updateUser(): void {
    this.isUserLoading = true;

    this.usersService
      .editUser(this.user.id, this.userForm.value)
      .pipe(finalize(() => (this.isUserLoading = false)))
      .subscribe(
        response => {
          this.user = response;
          this.patchValueUserForm();
          this.toastService.success(
            'User has been successfully updated',
            'Success'
          );
        },
        error => {
          if (error.message.email) {
            const [email_error] = error.message.email;
            this.userForm.controls.email.setErrors({ error: email_error });
          }
          this.toastService.error('User has not been updated', 'Error');
        }
      );
  }

  private editMailSettings(): void {
    this.isMailLoading = true;
    this.usersService
      .editMailSettings(this.mailForm.getRawValue())
      .pipe(
        finalize(() => {
          this.isMailLoading = false;
        })
      )
      .subscribe(
        response => {
          this.mailSettings = response;
          this.patchValueMailForm();
          this.toastService.success(
            'Mail settings have been successfully updated',
            'Success'
          );
        },
        error => {
          this.toastService.error(
            error.message ?? 'Mail settings have not been updated',
            'Error'
          );
        }
      );
  }

  private initSettingsForm(): void {
    this.userForm = this.fb.group({
      first_name: new FormControl('', [
        Validators.required,
        Validators.minLength(1),
        Validators.maxLength(128),
      ]),
      last_name: new FormControl('', [
        Validators.required,
        Validators.minLength(1),
        Validators.maxLength(128),
      ]),
      email: new FormControl('', [
        Validators.required,
        Validators.minLength(5),
        Validators.maxLength(256),
        Validators.pattern(emailRegEx),
      ]),
      role: new FormControl('', Validators.required),
      commission_model: new FormControl(
        '',
        ConditionalRequiredValidator(
          this.roleRequiredCondition.bind(this, this.salesPersonRole)
        )
      ),
      commission_percentage: new FormControl(this.defaultPercentage, [
        ConditionalRequiredValidator(
          this.roleRequiredCondition.bind(this, this.salesPersonRole)
        ),
        Validators.min(0),
        Validators.max(100),
      ]),
      second_sale_commission: new FormControl(0, [
        ConditionalRequiredValidator(
          this.modelRequiredCondition.bind(this, this.leadGenerationModel)
        ),
        Validators.min(0),
        Validators.max(100),
      ]),
    });
  }

  private roleRequiredCondition(role: number): boolean {
    if (this.userForm?.controls.commission_model.value === this.customModel) {
      return false;
    }
    return this.userForm?.controls.role.value === role;
  }

  private modelRequiredCondition(model: number): boolean {
    return this.userForm?.controls.commission_model.value === model;
  }

  public userRoleHandler(event): void {
    if (event.key === this.salesPersonRole) {
      this.userForm.controls.commission_percentage.patchValue(
        this.defaultPercentage
      );
    } else {
      this.userForm.controls.commission_percentage.patchValue(0);
    }
    this.userForm.controls.commission_model.patchValue(0);
    this.userForm.controls.commission_percentage.updateValueAndValidity();
    this.userForm.controls.commission_model.updateValueAndValidity();
  }

  public commissionModelHandler(event): void {
    if (event.key === this.leadGenerationModel) {
      this.userForm.controls.commission_percentage.patchValue(
        this.defaultLeadPercentage
      );
      this.userForm.controls.second_sale_commission.patchValue(
        this.defaultSecondSalePercentage
      );
    } else if (event.key === this.leadGenerationModelB) {
      this.userForm.controls.commission_percentage.patchValue(
        this.defaultLeadBPercentage
      );
      this.userForm.controls.second_sale_commission.patchValue(0);
    } else if (event.key === this.salesSupportModel) {
      this.userForm.controls.commission_percentage.patchValue(
        this.salesSupportPercentage
      );
      this.userForm.controls.second_sale_commission.patchValue(0);
    } else if (event.key === this.customModel) {
      this.userForm.controls.commission_percentage.patchValue(0);
      this.userForm.controls.second_sale_commission.patchValue(0);
    } else {
      this.userForm.controls.commission_percentage.patchValue(
        this.defaultPercentage
      );
      this.userForm.controls.second_sale_commission.patchValue(0);
    }
    this.userForm.controls.commission_percentage.updateValueAndValidity();
    this.userForm.controls.second_sale_commission.updateValueAndValidity();
  }

  public linkCanBeSend(): boolean {
    return !this.isOwnerReadOnly() && this.user && !this.user.password_set;
  }

  public resendActivationLink() {
    this.isUserLoading = true;
    this.usersService
      .resendLink(this.user)
      .pipe(finalize(() => (this.isUserLoading = false)))
      .subscribe(
        response => {
          this.toastService.success(response, 'Success');
        },
        error => {
          this.toastService.error(error, 'Error');
        }
      );
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private patchValueUserForm() {
    if (this.user) {
      this.userForm.patchValue(this.user);

      if (
        this.user.commission_model === this.salesSupportModel &&
        this.salesSupportPercentage !== this.user.commission_percentage
      ) {
        this.globalService.setCompanySalesSupportPercentage =
          this.user.commission_percentage;
        this.salesSupportPercentage = this.user.commission_percentage;
      }

      if (this.isOwnerReadOnly() || this.user.disabled_at) {
        this.userForm.disable();
      }
    }
  }

  private initMailForm(): void {
    this.mailForm = this.fb.group({
      customers: new FormControl(false),
      quotes: new FormControl(false),
      invoices: new FormControl(false),
    });
  }

  private patchValueMailForm() {
    if (this.mailSettings) {
      this.mailForm.patchValue(this.mailSettings);
    }
  }
}
