import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
  Renderer2,
} from '@angular/core';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { numberOnlyRegEx } from '../../constants/regex';
import { timer } from 'rxjs';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-two-factor-activation-modal',
  templateUrl: './two-factor-activation-modal.component.html',
  styleUrls: ['./two-factor-activation-modal.component.scss'],
  animations: [
    trigger('displayAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('500ms ease-in', style({ opacity: 1 })),
      ]),
    ]),
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('300ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('200ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('modalAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(1rem)' }),
        animate(
          '300ms ease-in',
          style({ opacity: 1, transform: 'translateY(0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translateY(0)' }),
        animate(
          '200ms ease-out',
          style({ opacity: 0, transform: 'translateY(1rem)' })
        ),
      ]),
    ]),
  ],
})
export class TwoFactorActivationModalComponent implements OnInit {
  @Input() secret: string;
  @Input() qrData: string;

  showActivationModal = false;
  modalDisplay: 'secret' | 'token' = 'secret';
  twoFactorActivationForm: FormGroup;

  showCopyMessage = false;

  @Output() modalClosed = new EventEmitter<any>();
  @Output() modalDismissed = new EventEmitter<any>();

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    this.initTwoFactorActivationForm();
  }

  initTwoFactorActivationForm() {
    this.twoFactorActivationForm = this.fb.group({
      token: new FormControl(undefined, [
        Validators.required,
        Validators.pattern(numberOnlyRegEx),
        Validators.minLength(6),
        Validators.maxLength(6),
      ]),
    });
  }

  copySecretToClipboard() {
    navigator.clipboard.writeText(this.secret).then(() => {
      this.showCopyMessage = true;
      timer(2500).subscribe(() => {
        this.showCopyMessage = false;
      });
    });
  }

  submit() {
    if (this.twoFactorActivationForm.valid) {
      this.closeModal(this.twoFactorActivationForm.getRawValue());
    }
  }

  public openModal() {
    this.showActivationModal = true;
    this.twoFactorActivationForm.reset();
    this.renderer.addClass(this._document.body, 'modal-opened');
  }

  closeModal(value?: any) {
    this.modalClosed.emit(value);
    this.showActivationModal = false;
    this.modalDisplay = 'secret';
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal(value?: any) {
    this.modalDismissed.emit(value);
    this.showActivationModal = false;
    this.modalDisplay = 'secret';
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  goToTokenStep() {
    this.modalDisplay = 'token';
  }
}
