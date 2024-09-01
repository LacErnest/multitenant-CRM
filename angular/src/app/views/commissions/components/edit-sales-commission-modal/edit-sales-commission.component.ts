import { Component, Inject, OnInit, Input, Renderer2 } from '@angular/core';
import { Observable, Subject } from 'rxjs';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { DOCUMENT } from '@angular/common';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../shared/animations/browser-animations';
import { CompanySetting } from 'src/app/views/settings/interfaces/company-setting';

@Component({
  selector: 'oz-finance-edit-sales-commission-modal',
  templateUrl: './edit-sales-commission-modal.component.html',
  styleUrls: ['./edit-sales-commission-modal.component.scss'],
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
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class EditSalesCommissionModalComponent {
  @Input() commissionSettings: CompanySetting;

  public showEditSalesCommissionModal = false;
  public invoice: any;
  public commission: any;
  public editSalesCommissionForm: FormGroup;
  private modalSubject: Subject<any>;

  constructor(
    private fb: FormBuilder,
    private renderer: Renderer2,
    @Inject(DOCUMENT) private _document
  ) {}

  public openModal(invoice, commission): Subject<any> {
    if (invoice.details) {
      this.invoice = {
        id: commission.invoice_id,
        number: commission.invoice,
        order_id: commission.order_id,
      };
      this.commission = {
        commission_percentage: commission.commission_percentage,
        sales_person: invoice.sales_person,
        sales_person_id: invoice.sales_person_id,
      };
    } else {
      this.invoice = invoice;
      this.commission = commission;
    }

    this.initEditSalesCommissionForm();
    this.showEditSalesCommissionModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showEditSalesCommissionModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showEditSalesCommissionModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public submit() {
    if (this.editSalesCommissionForm.valid) {
      this.closeModal({
        commission_percentage: this.editSalesCommissionForm.get(
          'commission_percentage'
        )?.value,
        order_id: this.invoice.order_id,
        invoice_id: this.invoice.id,
        sales_person_id: this.commission.sales_person_id,
      });
    }
  }

  public cannotSubmit(): boolean {
    return (
      this.editSalesCommissionForm.invalid ||
      !this.editSalesCommissionForm.dirty
    );
  }

  private initEditSalesCommissionForm(): void {
    this.editSalesCommissionForm = this.fb.group({
      commission_percentage: new FormControl(
        this.commission?.commission_percentage,
        [Validators.max(this.commissionSettings.max_commission_percentage)]
      ),
    });
  }
}
