import { Component, Inject, Input, OnInit, Renderer2 } from '@angular/core';
import { transition, trigger, useAnimation } from '@angular/animations';
import { Helpers } from 'src/app/core/classes/helpers';
import { CurrencyPrefix } from 'src/app/shared/enums/currency-prefix.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Service } from 'src/app/shared/interfaces/service';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalContainerEnterAnimation,
  modalContainerLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from '../../animations/browser-animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { EntityItem } from '../../classes/entity-item/entity.item';
import { concat, Observable, of, Subject } from 'rxjs';
import { GlobalService } from 'src/app/core/services/global.service';
import { SuggestService } from '../../services/suggest.service';
import { EnumService } from 'src/app/core/services/enum.service';
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { environment } from 'src/environments/environment';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  switchMap,
  tap,
} from 'rxjs/operators';
import { HttpParams } from '@angular/common/http';
import { currencyRegEx } from '../../constants/regex';
import { AllCompanies, Company } from '../../interfaces/company';
import { SearchEntity } from '../../interfaces/search-entity';

@Component({
  selector: 'oz-finance-entity-item-modal',
  templateUrl: './entity-item-modal.component.html',
  styleUrls: ['./entity-item-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', useAnimation(modalContainerEnterAnimation)),
      transition(':leave', useAnimation(modalContainerLeaveAnimation)),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', useAnimation(modalBackdropEnterAnimation)),
      transition(':leave', useAnimation(modalBackdropLeaveAnimation)),
    ]),
    trigger('modalAnimation', [
      transition(':enter', useAnimation(modalEnterAnimation)),
      transition(':leave', useAnimation(modalLeaveAnimation)),
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
export class EntityItemModalComponent implements OnInit {
  public showEntityItemModal = false;
  public itemForm: FormGroup;
  public item: EntityItem;
  public currencyPrefix: string = CurrencyPrefix.USD;
  public isManualInput: boolean;
  public resourceCurrency: number;
  public isMasterInput = false;

  public selectedService: Service;

  public isServiceLoading = false;
  public serviceSelect$: Observable<Service[]>;
  public serviceInput$: Subject<string> = new Subject<string>();
  public serviceDefault: Service[] = [];
  public companies: AllCompanies[];

  private modalSubject: Subject<EntityItem>;
  private resourceId: string;

  public companySelect$: Observable<SearchEntity[]>;
  public companyInput$: Subject<string> = new Subject<string>();
  public companyDefault: SearchEntity[] = [];
  public selectedCompany: SearchEntity;
  public isCompanyLoading = false;

  public constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private suggestService: SuggestService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public getCompanies = () => {
    this.companyDefault = this.globalService.companies
      .filter(company => company.id.toLocaleLowerCase() != 'all')
      .map(company =>
        Object.assign({}, { id: company.id, name: company.name })
      );
    const currentCompany = this.globalService.currentCompany;
    this.selectedCompany = { id: currentCompany.id, name: currentCompany.name };
  };

  public companyChanged(company: SearchEntity): void {
    this.itemForm.controls.service_name.reset(undefined);
    this.itemForm.controls.description.reset(undefined);
    this.itemForm.controls.quantity.reset(1);
    this.itemForm.controls.unit.reset(undefined);
    this.itemForm.controls.unit_price.reset(undefined);
    this.selectedCompany = company;
    this.itemForm.controls.company_id.patchValue(company.id);
  }

  public isFormValid(): boolean {
    if (this.isManualInput) {
      return this.itemForm.valid;
    }

    return this.selectedService && this.itemForm.valid;
  }

  public submitItemForm(): void {
    if (!this.isManualInput && !this.selectedService) {
      return;
    }

    if (this.itemForm.valid) {
      const val = new EntityItem({
        ...this.item,
        ...this.itemForm.getRawValue(),
        company_name: this.selectedCompany?.name,
      });

      if (!this.isManualInput) {
        val.service_id = this.selectedService.id;
        val.service_name = this.selectedService.name;
      }

      this.closeModal(val);
    }
  }

  public serviceSelected(event: Service): void {
    this.itemForm.controls.unit.patchValue(event?.price_unit);
    this.itemForm.controls.unit_price.patchValue(event?.price);
  }

  public openModal(
    services: Service[],
    item: EntityItem,
    isManualInput: boolean,
    isMasterInput: boolean,
    resourceId?: string,
    resourceCurrency?: number
  ): Subject<EntityItem> {
    this.item = item;
    this.isManualInput = isManualInput;
    this.resourceId = resourceId;
    this.resourceCurrency = resourceCurrency;
    this.isMasterInput = isMasterInput;

    if (item) {
      this.selectedService = {
        id: item.service_id,
        name: item.service_name,
        price: item.unit_price,
        price_unit: item.unit,
      };
      this.serviceDefault = [this.selectedService];
    } else {
      this.serviceDefault = [];
      this.selectedService = undefined;
    }

    this.renderer.addClass(this._document.body, 'modal-opened');
    this.initItemForm();

    if (this.isMasterInput) {
      this.getCompanies();
      this.setSelectedCompay();
      this.initCompanyTypeAhead();
    }

    if (!item) {
      this.setDefaultCompany();
    }
    this.patchValueItemForm();
    this.setCurrencyPrefix();

    if (this.isMasterInput) {
      this.itemForm.get('company_id').setValidators(Validators.required);
    } else {
      this.itemForm.get('company_id').clearValidators();
      this.itemForm.get('company_id').patchValue(null);
    }

    if (this.isManualInput) {
      this.itemForm.get('service_name').setValidators(Validators.required);
    } else {
      this.itemForm.get('service_name').clearValidators();
      this.initServiceTypeAhead();
    }

    this.itemForm.get('service_name').updateValueAndValidity();
    this.showEntityItemModal = true;

    this.modalSubject = new Subject<EntityItem>();
    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showEntityItemModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private closeModal(value?: EntityItem): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showEntityItemModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initItemForm(): void {
    this.itemForm = this.fb.group({
      description: new FormControl(undefined, Validators.maxLength(250)),
      quantity: new FormControl(1, [Validators.required, Validators.min(0)]),
      unit: new FormControl(undefined, Validators.required),
      unit_price: new FormControl(undefined, [
        Validators.required,
        Validators.min(0),
        Validators.pattern(currencyRegEx),
      ]),
      service_name: new FormControl(undefined),
      company_id: new FormControl(undefined),
      exclude_from_price_modifiers: new FormControl(false, []),
    });
  }

  private patchValueItemForm(): void {
    if (this.item) {
      this.itemForm.patchValue(this.item);
      if (this.isMasterInput && !this.item.company_id) {
        const company = this.globalService.currentCompany;
        this.itemForm.controls.company_id.patchValue(company);
      }
    }
  }

  private setCurrencyPrefix(): void {
    let currencyCode;

    if (this.resourceCurrency) {
      currencyCode = this.resourceCurrency;
    } else {
      currencyCode =
        this.globalService.getUserRole() === UserRole.ADMINISTRATOR
          ? environment.currency
          : this.globalService.userCurrency;
    }

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }

  private initServiceTypeAhead(): void {
    this.serviceSelect$ = concat(
      of(this.serviceDefault), // default items
      this.serviceInput$.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isServiceLoading = true;
        }),
        switchMap(term => {
          let params = new HttpParams();

          if (this.resourceId) {
            params = Helpers.setParam(params, 'resource', this.resourceId);
          }

          if (this.selectedCompany) {
            params = Helpers.setParam(
              params,
              'company',
              this.selectedCompany.id
            );
          }

          return this.suggestService.suggestService(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isServiceLoading = false;
            })
          );
        })
      )
    );
  }

  private setDefaultCompany(): void {
    const currentCompany = this.globalService.currentCompany;
    this.selectedCompany = {
      id: currentCompany.id,
      name: currentCompany.name,
    };
    this.itemForm.controls.company_id.patchValue(currentCompany.id);
  }

  public toggleExcludeFromPriceModifiers(): void {
    this.itemForm
      .get('exclude_from_price_modifiers')
      .patchValue(!this.itemForm.get('exclude_from_price_modifiers').value);
  }

  private initCompanyTypeAhead(): void {
    const params = new HttpParams();
    this.companySelect$ = concat(
      of(this.companyDefault), // default items
      this.companyInput$.pipe(
        filter(v => !!v),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isCompanyLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestCompanies(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isCompanyLoading = false;
            })
          )
        )
      )
    );
  }

  private setSelectedCompay(): void {
    if (this.item) {
      this.selectedCompany = {
        id: this.item.company_id,
        name: this.item.company_name,
      };
      const isInCompanies = this.companyDefault.find(
        c => c.id === this.selectedCompany.id
      );
      if (!isInCompanies) {
        this.companyDefault.unshift(this.selectedCompany);
      }
    }
  }
}
