import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { AuthenticationService } from '../../../../core/services/authentication.service';
import { ActivatedRoute, Router } from '@angular/router';
import { HttpParams } from '@angular/common/http';
import { finalize } from 'rxjs/operators';
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
  selector: 'oz-finance-set',
  templateUrl: './set.component.html',
  styleUrls: ['./set.component.scss'],
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
export class SetComponent implements OnInit {
  isLoading = false;

  setForm: FormGroup;

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
    this.initSetForm();
    this.token = this.route.snapshot.params.set_token;
    this.email = this.route.snapshot.params.email;
  }

  initSetForm() {
    this.setForm = this.fb.group(
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

  set() {
    this.showMessage = false;
    if (this.setForm.valid && !this.isLoading) {
      this.isLoading = true;

      let params = new HttpParams();
      params = Helpers.setParam(params, 'token', this.token);
      params = Helpers.setParam(params, 'email', this.email);

      this.authenticationService
        .setPassword(params, this.setForm.getRawValue())
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          response => {
            this.router.navigate(['/auth/login']).then();
            this.toastrService.success(response.message, 'Password set');
          },
          error => {
            this.messageTitle = 'Password set failed';
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
