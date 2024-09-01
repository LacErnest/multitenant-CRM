import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { AuthenticationService } from '../../../../core/services/authentication.service';
import { HttpParams } from '@angular/common/http';
import { finalize } from 'rxjs/operators';
import { ActivatedRoute, Router } from '@angular/router';
import { passwordMatchValidator } from '../../../../shared/validators/password-match.validator';
import { AlertType } from '../../../../shared/components/alert/alert.component';
import { transition, trigger, useAnimation } from '@angular/animations';
import {
  alertEnterAnimation,
  alertLeaveAnimation,
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../shared/animations/browser-animations';
import { Helpers } from '../../../../core/classes/helpers';
import { GlobalService } from '../../../../core/services/global.service';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'oz-finance-reset',
  templateUrl: './reset.component.html',
  styleUrls: ['./reset.component.scss'],
  animations: [
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('alertAnimation', [
      transition(':enter', useAnimation(alertEnterAnimation)),
      transition(':leave', useAnimation(alertLeaveAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class ResetComponent implements OnInit {
  isLoading = false;

  resetForm: FormGroup;

  email: string;
  token: string;

  showMessage = false;
  messageType: AlertType;
  messageTitle: string;
  messageDescription: string;

  showPassword = false;
  showConfirmPassword = false;

  constructor(
    private fb: FormBuilder,
    private authenticationService: AuthenticationService,
    private route: ActivatedRoute,
    private router: Router,
    private globalService: GlobalService,
    private toastrService: ToastrService
  ) {}

  ngOnInit(): void {
    this.initResetForm();
    this.token = this.route.snapshot.params.reset_token;
    this.email = this.route.snapshot.params.email;
  }

  initResetForm() {
    this.resetForm = this.fb.group(
      {
        password: new FormControl(undefined, [
          Validators.required,
          Validators.minLength(8),
          Validators.maxLength(256),
        ]),
        password_confirmation: new FormControl(undefined, [
          Validators.required,
          Validators.minLength(8),
          Validators.maxLength(256),
        ]),
      },
      {
        validators: [
          passwordMatchValidator('password', 'password_confirmation'),
        ],
      }
    );
  }

  reset() {
    if (this.resetForm.valid && !this.isLoading) {
      this.isLoading = true;

      let params = new HttpParams();
      params = Helpers.setParam(params, 'token', this.token);
      params = Helpers.setParam(params, 'email', this.email);

      this.authenticationService
        .resetPassword(params, this.resetForm.getRawValue())
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          response => {
            this.globalService.userDetails = response.user;
            this.globalService.isLoggedIn = true;
            localStorage.setItem('access_token', response.access_token);
            this.setCompanies(response.user);

            this.router.navigate(['/dashboard']).then();
            this.toastrService.success(response.message, 'Password reset');
          },
          error => {
            this.messageTitle = 'Password reset failed';
            this.messageDescription = error.error?.message ?? error.message;
            this.messageType = AlertType.ERROR;
            this.showMessage = true;
          }
        );
    }
  }

  private setCompanies(user: any) {
    this.authenticationService.setCompanies(user);
  }
}
