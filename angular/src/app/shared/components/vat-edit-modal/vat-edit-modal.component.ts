import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import { Subject } from 'rxjs';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { DOCUMENT } from '@angular/common';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'oz-finance-vat-edit-modal',
  templateUrl: './vat-edit-modal.component.html',
  styleUrls: ['./vat-edit-modal.component.scss'],
  animations: [
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
export class VatEditModalComponent implements OnInit {
  showVatEditModal = false;
  percentageForm: FormGroup;

  private modalSubject: Subject<any>;
  public settings: any;
  public taxRate;

  constructor(
    private fb: FormBuilder,
    protected route: ActivatedRoute,
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    this.getResolvedData();
  }

  private getResolvedData(): void {
    this.settings = this.route.snapshot.data.settings;
  }

  submit() {
    if (this.percentageForm.valid) {
      this.closeModal(this.percentageForm.controls.percentage.value);
    }
  }

  public openModal(taxRate?: number): Subject<any> {
    this.taxRate = taxRate;
    this.initPercentageForm();
    this.showVatEditModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showVatEditModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showVatEditModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initPercentageForm() {
    this.percentageForm = this.fb.group({
      percentage: new FormControl(
        this.taxRate ?? this.settings?.vat_default_value,
        [
          Validators.min(1),
          Validators.max(this.settings.vat_max_value),
          Validators.required,
        ]
      ),
    });
  }
}
