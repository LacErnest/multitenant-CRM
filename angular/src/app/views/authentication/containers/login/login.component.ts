/* tslint:disable:max-line-length */
import { Component, OnInit, ViewChild } from '@angular/core';
import {
  TwoFactorActivateData,
  AuthUserResponse,
} from 'src/app/core/interfaces/authorization';
import { User } from 'src/app/core/interfaces/user';
import { AuthenticationService } from 'src/app/core/services/authentication.service';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { transition, trigger, useAnimation } from '@angular/animations';
import { finalize } from 'rxjs/operators';
import { LoginResponse } from 'src/app/core/types/login-response';
import { numberOnlyRegEx } from 'src/app/shared/constants/regex';
import { TwoFactorActivationModalComponent } from 'src/app/shared/components/two-factor-activation-modal/two-factor-activation-modal.component';
import { AlertType } from 'src/app/shared/components/alert/alert.component';
import { timer } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  alertEnterAnimation,
  alertLeaveAnimation,
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { ToastrService } from 'ngx-toastr';
import { LoginFormDisplay } from 'src/app/views/authentication/types/login-form-display';
import { validate as uuidValidate } from 'uuid';

@Component({
  selector: 'oz-finance-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
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
export class LoginComponent implements OnInit {
  @ViewChild('twoFactorActivationModal', { static: false })
  public twoFactorActivationModal: TwoFactorActivationModalComponent;

  public display: LoginFormDisplay = 'login';
  public isLoading = false;

  public loginForm: FormGroup;
  public tokenForm: FormGroup;

  public secret: string;
  public qr: string;

  public showMessage = false;
  public messageType: AlertType;
  public messageTitle: string;
  public messageDescription: string;

  public remember = !!localStorage.getItem('rb_login');
  public showPassword = false;

  private returnURL: string;

  constructor(
    private authenticationService: AuthenticationService,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private router: Router,
    private toastrService: ToastrService,
    private route: ActivatedRoute
  ) {}

  public ngOnInit(): void {
    this.initLoginForm();
    this.initTokenForm();
    this.returnURL = this.route.snapshot.queryParamMap.get('returnURL');
  }

  public login(): void {
    this.showMessage = false;

    if (this.loginForm.valid && !this.isLoading) {
      this.isLoading = true;

      this.authenticationService
        .login(this.loginForm.getRawValue())
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          response => {
            this.handleLogin(response);
          },
          error => {
            this.showLoginFailAlert(error);
            this.navigateToReturnURL();
          }
        );
    }
  }

  public verifyToken(): void {
    if (this.tokenForm.valid && !this.isLoading) {
      this.isLoading = true;

      const loginData = this.loginForm.getRawValue();
      loginData.token = this.tokenForm.controls.token.value;
      loginData.trust_device = !!this.tokenForm.controls.trust_device.value;

      this.authenticationService
        .login(loginData)
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(
          (response: AuthUserResponse) => {
            this.authenticateUser(response);
          },
          error => {
            this.showLoginFailAlert(error);
          }
        );
    }
  }

  // TODO: refactor `TwoFactorActivationModalComponent`
  public modalClosed(value: TwoFactorActivateData): void {
    if (value?.token) {
      this.activate2FA(value);
    }
  }

  public modalDismissed(): void {
    if (this.globalService.isLoggedIn) {
      this.isLoading = true;

      this.authenticationService
        .logout()
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(() => {
          this.globalService.userDetails = undefined;
          this.globalService.isLoggedIn = false;
        });
    }
  }

  public backToLogin(): void {
    this.initLoginForm();
    this.display = 'login';
  }

  private initLoginForm(): void {
    this.loginForm = this.fb.group({
      email: new FormControl(
        this.remember ? localStorage.getItem('rb_login') : undefined,
        [Validators.required, Validators.maxLength(256), Validators.email]
      ),
      password: new FormControl(undefined, [
        Validators.required,
        Validators.minLength(8),
        Validators.maxLength(256),
      ]),
    });
  }

  private initTokenForm(): void {
    this.tokenForm = this.fb.group({
      token: new FormControl(undefined, [
        Validators.required,
        Validators.pattern(numberOnlyRegEx),
        Validators.minLength(6),
        Validators.maxLength(6),
      ]),
      trust_device: new FormControl(undefined, []),
    });
  }

  private handleLogin(loginResponse: LoginResponse): void {
    this.setRemember();

    if (loginResponse['message']) {
      this.display = 'token';
      return;
    }

    if (loginResponse['user']?.google2fa) {
      this.authenticateUser(loginResponse as AuthUserResponse);
    } else {
      this.set2FA(loginResponse as AuthUserResponse);
    }
  }

  private authenticateUser(userData: AuthUserResponse): void {
    this.globalService.userDetails = userData.user;
    localStorage.setItem('access_token', userData.access_token);
    this.globalService.isLoggedIn = true;

    this.setCompanies(userData.user);
    this.navigateAfterLogin();
  }

  private set2FA(userData: AuthUserResponse): void {
    this.globalService.userDetails = userData.user;
    localStorage.setItem('access_token', userData.access_token);
    this.globalService.isLoggedIn = true;
    this.get2FactorSecret();
  }

  private navigateAfterLogin(): void {
    const previous = this.route.snapshot.queryParamMap.get('returnURL');

    if (previous && !previous.includes('/auth')) {
      const companyID = previous?.split('/')[1];

      if (
        uuidValidate(companyID) &&
        companyID !== this.globalService.currentCompany.id
      ) {
        this.globalService.currentCompany = this.globalService.companies.find(
          c => c.id === companyID
        );
      }

      this.router.navigate([previous]).then();
    } else {
      this.router.navigate(['/dashboard']).then();
    }
  }

  private get2FactorSecret(): void {
    this.isLoading = true;

    this.authenticationService
      .get2FASecret()
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.secret = response.key;
        this.qr =
          `otpauth://totp/OZManagementTool:${this.loginForm.controls.email.value}
        ?secret=${this.secret}
        &issuer=OZManagementTool`.replace(/\n\s+/g, '');
        this.openTwoFactorActivationModal();
      });
  }

  private openTwoFactorActivationModal(): void {
    this.twoFactorActivationModal.openModal();
  }

  private activate2FA(token: TwoFactorActivateData): void {
    this.isLoading = true;

    this.authenticationService
      .activate2FA(token)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        () => {
          const details = this.globalService.userDetails;
          details.google2fa = true;
          this.globalService.userDetails = details;

          this.setCompanies(details);
          this.toastrService.success(
            'Two factor authentication was successfully activated on your account',
            '2FA Activated'
          );
          this.navigateAfterLogin();
        },
        error => {
          this.messageTitle = '2FA Activation failed';
          this.messageType = AlertType.ERROR;
          this.messageDescription = error.error?.message;
          this.showMessage = true;

          timer(5000).subscribe(() => {
            this.showMessage = false;
          });
        }
      );
  }

  private setRemember(): void {
    if (this.remember) {
      localStorage.setItem('rb_login', this.loginForm.value.email);
    } else {
      localStorage.removeItem('rb_login');
    }
  }

  private showLoginFailAlert(error): void {
    this.messageTitle = 'Login failed';
    this.messageType = AlertType.ERROR;
    this.messageDescription = error.message ?? error.error?.message;
    this.showMessage = true;

    timer(5000).subscribe(() => {
      this.showMessage = false;
    });
  }

  private setCompanies(user: User): void {
    this.authenticationService.setCompanies(user);
  }

  private navigateToReturnURL(): void {
    if (this.returnURL) {
      this.router.navigateByUrl(this.returnURL).then();
    }
  }
}
