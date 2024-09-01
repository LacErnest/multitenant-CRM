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
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';

import { Subject } from 'rxjs';

import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { currencyRegEx } from 'src/app/shared/constants/regex';
import { CurrencyPrefix } from 'src/app/shared/enums/currency-prefix.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Service } from 'src/app/shared/interfaces/service';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'oz-finance-service-modal',
  templateUrl: './service-modal.component.html',
  styleUrls: ['./service-modal.component.scss'],
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
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class ServiceModalComponent implements OnInit {
  public currencyPrefix: string = CurrencyPrefix.USD;
  public service: Service;
  public showPriceModal = false;
  public priceForm: FormGroup;

  private modalSubject: Subject<Service>;
  private resourceCurrency: number;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public submitPriceForm(): void {
    if (this.priceForm.valid) {
      const val = { ...this.service, ...this.priceForm.getRawValue() };
      this.closeModal(val);
    }
  }

  public openModal(service: Service, currency: number): Subject<Service> {
    this.service = service;
    this.resourceCurrency = currency;

    this.initPriceForm();
    this.patchValuePriceForm();
    this.setCurrencyPrefix();

    this.showPriceModal = true;

    this.modalSubject = new Subject<Service>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showPriceModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private closeModal(value?: Service): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showPriceModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initPriceForm(): void {
    this.priceForm = this.fb.group({
      name: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(2048),
      ]),
      price_unit: new FormControl(undefined, Validators.required),
      price: new FormControl(undefined, [
        Validators.required,
        Validators.min(0),
        Validators.pattern(currencyRegEx),
      ]),
    });
  }

  private patchValuePriceForm(): void {
    if (this.service) {
      this.priceForm.patchValue(this.service);
    }
  }

  private setCurrencyPrefix(): void {
    const isAdmin = this.globalService.getUserRole() === UserRole.ADMINISTRATOR;
    let currencyCode;

    if (this.resourceCurrency) {
      currencyCode = this.resourceCurrency;
    } else {
      currencyCode = isAdmin
        ? environment.currency
        : this.globalService.userCurrency;
    }

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }
}
