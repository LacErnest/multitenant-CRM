import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { AuthenticationService } from '../../../../core/services/authentication.service';
import { finalize } from 'rxjs/operators';
import { transition, trigger, useAnimation } from '@angular/animations';
import { AlertType } from '../../../../shared/components/alert/alert.component';
import {
  alertEnterAnimation,
  alertLeaveAnimation,
  displayAnimation,
} from '../../../../shared/animations/browser-animations';

@Component({
  selector: 'oz-finance-recover',
  templateUrl: './recover.component.html',
  styleUrls: ['./recover.component.scss'],
  animations: [
    trigger('alertAnimation', [
      transition(':enter', useAnimation(alertEnterAnimation)),
      transition(':leave', useAnimation(alertLeaveAnimation)),
    ]),
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
  ],
})
export class RecoverComponent implements OnInit {
  isLoading = false;
  recoverForm: FormGroup;

  showMessage = false;
  messageType: AlertType;
  messageTitle: string;
  messageDescription: string;

  constructor(
    private fb: FormBuilder,
    private authenticationService: AuthenticationService
  ) {}

  ngOnInit(): void {
    this.initRecoverForm();
  }

  initRecoverForm() {
    this.recoverForm = this.fb.group({
      email: new FormControl(undefined, [
        Validators.email,
        Validators.maxLength(256),
      ]),
    });
  }

  recover() {
    if (this.recoverForm.valid && !this.isLoading) {
      this.showMessage = false;
      this.isLoading = true;
      this.authenticationService
        .recoverPassword(this.recoverForm.getRawValue())
        .pipe(
          finalize(() => {
            this.isLoading = false;
          })
        )
        .subscribe(() => {
          this.recoverForm.disable();
          this.showMessage = true;
          this.messageType = AlertType.SUCCESS;
          this.messageTitle = 'Email sent';
          this.messageDescription =
            'An email with account recovery instructions has been sent to the provided email-address. Check your inbox and spam folder.';
        });
    }
  }
}
