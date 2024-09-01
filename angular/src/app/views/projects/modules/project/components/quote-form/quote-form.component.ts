import { transition, trigger, useAnimation } from '@angular/animations';
import { HttpParams } from '@angular/common/http';
import {
  Component,
  ElementRef,
  OnDestroy,
  OnInit,
  ViewChild,
  ChangeDetectorRef,
} from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';

import { concat, forkJoin, Observable, of, Subject } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  finalize,
  skip,
  switchMap,
  takeUntil,
  tap,
} from 'rxjs/operators';

import { Helpers } from 'src/app/core/classes/helpers';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';

import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { EntityItemContainerBase } from 'src/app/shared/classes/entity-item/entity-item-container-base';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DeadlineModalComponent } from 'src/app/shared/components/deadline-modal/deadline-modal.component';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { RefusalModalComponent } from 'src/app/shared/components/refusal-modal/refusal-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Quote } from 'src/app/shared/interfaces/entities';
import { LegalEntityChosen } from 'src/app/shared/interfaces/legal-entity-chosen';
import { SharedProjectEntityService } from 'src/app/shared/services/shared-entity.service';
import { SharedService } from 'src/app/shared/services/shared.service';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { dateBeforeValidator } from 'src/app/shared/validators/date-before.validator';

import { OrderStatus } from 'src/app/views/projects/modules/project/enums/order-status.enum';
import {
  getQuoteDownPaymentOptions,
  QuoteDownPayment,
} from 'src/app/views/projects/modules/project/enums/quote-down-payment.enum';
import { QuoteStatus } from 'src/app/views/projects/modules/project/enums/quote-status.enum';
import { DownPaymentOption } from 'src/app/views/projects/modules/project/interfaces/down-payment-option';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { QuoteStatusChangePayload } from 'src/app/views/projects/modules/project/interfaces/quote-status-change-payload';
import {
  ContactSearchEntity,
  CustomerSearchEntity,
  SearchEntity,
} from 'src/app/views/projects/modules/project/interfaces/search-entity';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';

import moment, { Moment } from 'moment';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { VatStatus } from '../../../../../../shared/enums/vat-status.enum';
import { CompanyLegalEntity } from '../../../../../../shared/interfaces/legal-entity';
import {
  FileRestrictions,
  UploadService,
} from '../../../../../../shared/services/upload.service';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { PriceModifierCalculationLogicService } from 'src/app/shared/services/price-modifier-calculatation-logic.service';
import { currencyRegEx } from 'src/app/shared/constants/regex';
import { CurrencyPrefix } from 'src/app/shared/enums/currency-prefix.enum';
import { getCurrencySymbol } from '@angular/common';
import { environment } from 'src/environments/environment';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';

@Component({
  selector: 'oz-finance-quote-form',
  templateUrl: './quote-form.component.html',
  styleUrls: ['./quote-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class QuoteFormComponent
  extends EntityItemContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('refusalModal', { static: false })
  public refusalModal: RefusalModalComponent;
  @ViewChild('deadlineModal', { static: false })
  public deadlineModal: DeadlineModalComponent;
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('upload_file', { static: false }) public upload_file: ElementRef;

  public customerFixed = false;
  public readOnly = false;
  public itemsReadOnly = false;
  public isLoading = false;
  public isCustomerLoading = false;
  public isSalesLoading = false;

  public quoteForm: FormGroup;
  public quote: Quote;
  public quoteStatusEnum = QuoteStatus;
  public orderStatusEnum = OrderStatus;
  public downPaymentType = null;

  public currencyPrefix: string = CurrencyPrefix.USD;

  public customerBillingCountry: number;
  public downPaymentOptions: DownPaymentOption[] = getQuoteDownPaymentOptions();

  public legalEntityCountry: number;
  public taxRate: number;
  private legalEntityChanged: boolean;

  public contactSelect$: ContactSearchEntity[] = [];

  public selectedCustomer: CustomerSearchEntity;
  public customerDirty = false;
  public customerSelect$: Observable<CustomerSearchEntity[]>;
  public customerInput$: Subject<string> = new Subject<string>();
  public customerDefault: SearchEntity[] = [];
  public customerNonVatLiable: boolean;

  public salesSelect$: Observable<SearchEntity[]>;
  public secondSalesSelect$: Observable<SearchEntity[]>;
  public salesInput$: Subject<string> = new Subject<string>();
  public secondSalesInput$: Subject<string> = new Subject<string>();
  public salesDefault: SearchEntity[] = [];
  public secondSalesDefault: SearchEntity[] = [];
  public templates: TemplateModel[] = [];

  private customerCurrency: number;
  private onDestroy = new Subject<void>();
  private vatStatus: number;
  private vatPercentage: number;
  private template_id: string;
  public project: Project;
  public entity: number = TablePreferenceType.PROJECT_QUOTES;

  public constructor(
    protected globalService: GlobalService,
    private fb: FormBuilder,
    private sharedService: SharedService,
    private route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService,
    private projectService: ProjectService,
    private enumService: EnumService,
    private suggestService: SuggestService,
    private sharedProjectEntityService: SharedProjectEntityService,
    private legalEntitiesService: LegalEntitiesService,
    private uploadService: UploadService,
    public priceModifierLogicService: PriceModifierCalculationLogicService,
    private cdr: ChangeDetectorRef
  ) {
    super(globalService, priceModifierLogicService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initQuoteForm();
    this.patchValueQuoteForm();
    this.patchSalesValue();
    this.setQuoteValues();

    this.subscribeToCompanyChange();
    this.initSalesTypeAhead();
    this.initSecondSalesTypeAhead();
    this.initCustomerTypeAhead();
    this.getCompanyTemplates();

    if (this.globalService.getUserRole() === UserRole.PROJECT_MANAGER) {
      this.readOnly = true;
      this.itemsReadOnly = true;
    }
    this.priceModifierLogicService.init(
      this.project?.price_modifiers_calculation_logic
    );
  }

  public ngOnDestroy(): void {
    this.onDestroy.next();
    this.onDestroy.complete();
  }

  public get customerCountry(): number {
    return this.customerBillingCountry ?? this.project?.customer?.country;
  }

  public get customerIsNonVatLiable(): boolean {
    return this.customerNonVatLiable ?? this.project?.customer?.non_vat_liable;
  }

  public download(): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.sharedService.exportProjectQuoteCallback,
        [this.project.id, this.quote.id, this.template_id],
        'Quote: ' + this.quote.number,
        null,
        null,
        this.templates
      )
      .subscribe(
        () => {
          //
        },
        () => {
          //
        }
      );
  }

  public changeStatus(status: QuoteStatus): void {
    const statusLabel = this.enumService.getEnumMap('quotestatus').get(status);
    this.confirmModal
      .openModal(
        'Confirm',
        `Are you sure want to change status to ${statusLabel}? This cannot be undone.`
      )
      .subscribe(result => {
        if (result) {
          switch (status) {
            case QuoteStatus.DECLINED:
              this.refusalModal.openModal().subscribe(
                refusalResult => {
                  this.changeQuoteStatus({
                    status,
                    reason_of_refusal: refusalResult.reason_of_refusal,
                  });
                },
                () => {
                  //
                }
              );
              break;
            case QuoteStatus.ORDERED:
              this.deadlineModal.openModal(moment(this.quote.date)).subscribe(
                deadlineResult => {
                  this.changeQuoteStatus({
                    status,
                    deadline: deadlineResult.deadline,
                  });
                },
                () => {
                  //
                }
              );
              break;
            default:
              this.changeQuoteStatus({ status });
              break;
          }
        }
      });
  }

  public customerChanged(customer: CustomerSearchEntity): void {
    this.customerDirty = true;

    if (customer !== this.selectedCustomer) {
      if (customer.legacy_customer && !this.legalEntityChanged) {
        const { legal_entity_id } =
          this.globalService.currentLegalEntities.find(e => e.local) ??
          this.globalService.currentLegalEntities.find(e => e.default);

        if (legal_entity_id) {
          this.quoteForm.get('legal_entity_id').patchValue(legal_entity_id);
        }
      }

      this.customerBillingCountry = customer.billing_country;
      this.quoteForm.controls.currency_code.patchValue(
        customer.default_currency
      );
      this.customerNonVatLiable = customer.non_vat_liable;

      this.contactSelect$ = Helpers.mapToSelectArray(
        customer.contacts,
        ['id'],
        ['first_name', 'last_name'],
        ' '
      );

      this.salesDefault = [
        {
          id: customer.sales_person_id,
          name: customer.sales_person,
        },
      ];
      this.initSalesTypeAhead();
      const salesPersonControls = customer.sales_person_id
        ? [customer.sales_person_id]
        : null;
      this.quoteForm.controls.sales_person_id.patchValue(salesPersonControls);
      this.quoteForm.controls.contact_id.patchValue(
        customer.primary_contact_id
      );
    }
  }

  public changeCurrency(): void {
    if (this.quoteForm.get('manual_input').value) {
      this.currency = this.quoteForm.get('currency_code').value;
    } else {
      this.currency = this.getDefaultCurrency();
    }
  }

  public showReasonOfRefusal(): boolean {
    return (
      this.quote?.reason_of_refusal &&
      this.quote?.status === this.quoteStatusEnum.DECLINED
    );
  }

  public showActionBtns(): boolean {
    return (
      (!this.project?.order ||
        this.project?.order?.status === this.orderStatusEnum.DRAFT ||
        this.project?.order?.status === this.orderStatusEnum.ACTIVE) &&
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY
    );
  }

  public showDownloadBtn(): boolean {
    return this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER;
  }

  public isLegalEntityDisabled(): boolean {
    const userRole = this.globalService.getUserRole();
    const onlyDefaultEntity =
      userRole === UserRole.PROJECT_MANAGER ||
      userRole === UserRole.PROJECT_MANAGER_RESTRICTED;
    return onlyDefaultEntity;
  }

  public showCancelBtn(): boolean {
    const role = this.globalService.getUserRole();
    const status = this.quote?.status;

    return (
      (role === UserRole.ADMINISTRATOR || role === UserRole.OWNER) &&
      status !== this.quoteStatusEnum.CANCELED
    );
  }

  public toggleManualInput(master: boolean): void {
    if (this.readOnly) {
      return;
    }

    master ? this.toggleQuoteMasterInput() : this.toggleQuoteManualInput();

    if (this.items.length) {
      this.confirmModal
        .openModal(
          'Confirm',
          'Are you sure you want to proceed?. All existed items will be deleted and you will have to add them again.'
        )
        .subscribe(result => {
          if (result) {
            const itemIds = this.items.map(i => i.id);
            this.quote
              ? this.deleteQuoteItems(itemIds)
              : this.removeItemsFromList(itemIds, true);
          } else {
            master
              ? this.toggleQuoteMasterInput()
              : this.toggleQuoteManualInput();
          }
        });
    }
  }

  public submit(): void {
    if (this.quoteForm.valid && !this.isLoading) {
      const value = this.quoteForm.getRawValue();
      this.updateDownPaymentValue(value);
      value.vat_status = this.vatStatus;
      value.vat_percentage = this.vatPercentage;
      if (value.down_payment === null) {
        this.downPaymentType = null;
      }
      value.down_payment_type = this.downPaymentType;
      this.quote
        ? this.editQuote(this.quote.id, value)
        : this.createQuote(value);
    }
  }

  public updateExpiryDate(date: Moment): void {
    const m = moment(date);
    this.quoteForm.controls.expiry_date.patchValue(m.add(30, 'days'));
  }

  public onSelectedEntityChanged(data: LegalEntityChosen): void {
    this.legalEntityCountry = data.country;
    this.legalEntityChanged = data.changed;
    if (!this.vatPercentage && this.legalEntityCountry) {
      this.legalEntitiesService.getCurrentTaxRate(data.id).subscribe(res => {
        this.taxRate = res.tax_rate;
      });
    }
  }

  public removeDoc() {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete the current uploaded document?'
      )
      .subscribe(result => {
        if (result) {
          this.isLoading = true;

          this.sharedService
            .deleteDocument(this.quote.id, this.quote.project_id, 'quotes')
            .pipe(
              finalize(() => {
                this.isLoading = false;
              })
            )
            .subscribe(
              response => {
                this.toastrService.success(
                  'Document deleted successfully.',
                  'Success'
                );
                this.quote.has_media = null;
              },
              err => {
                const msg =
                  err?.message?.tax_number ?? 'Failed to delete the document.';
                this.toastrService.error(msg, 'Error');
              }
            );
        }
      });
  }

  public showRemoveBtn(): boolean {
    return (
      !!this.quote.has_media &&
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY &&
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER_RESTRICTED
    );
  }

  public showUploadBtn(): boolean {
    return (
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY &&
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER_RESTRICTED
    );
  }

  public async uploadFile(files: any): Promise<void> {
    const [file] = files;
    const fileRestrictions: FileRestrictions = {
      size: 10 * 1024 * 1024,
      allowedFileTypes: [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
      ],
    };

    const { file: uploaded }: any = await this.uploadService.readFile(
      file,
      fileRestrictions
    );

    if (uploaded) {
      this.isLoading = true;

      this.sharedService
        .uploadDocument(
          this.quote.id,
          this.quote.project_id,
          uploaded,
          'quotes'
        )
        .pipe(
          finalize(() => {
            this.isLoading = false;
            this.upload_file.nativeElement.value = '';
          })
        )
        .subscribe(
          response => {
            this.toastrService.success(
              'Document uploaded successfully.',
              'Success'
            );
            this.quote.has_media = 'document set';
          },
          err => {
            this.upload_file.nativeElement.value = '';
            const msg =
              err?.message?.tax_number ?? 'Document failed to upload.';
            this.toastrService.error(msg, 'Error');
          }
        );
    }
  }

  public statusColor(): string {
    let color: string;
    switch (this.quote.status) {
      case QuoteStatus.DRAFT:
        color = 'draft-status';
        break;
      case QuoteStatus.SENT:
        color = 'sent-status';
        break;
      case QuoteStatus.DECLINED:
        color = 'declined-status';
        break;
      case QuoteStatus.ORDERED:
        color = 'active-status';
        break;
      case QuoteStatus.INVOICED:
        color = 'invoiced-status';
        break;
      case QuoteStatus.CANCELED:
        color = 'cancelled-status';
        break;
    }

    return color;
  }

  public showDraftButton(): boolean {
    const superUser = this.globalService.userDetails?.super_user;

    return superUser && this.quote?.status !== this.quoteStatusEnum.DRAFT;
  }

  protected createItem(item: EntityItem): void {
    const quoteNeedsUpdate =
      !this.quote.manual_input && this.quoteForm.get('manual_input').value;

    /**
     * NOTE: quote should be updated first, cause otherwise back-end will expect for `service_id`
     */
    if (quoteNeedsUpdate) {
      this.sharedService
        .editProjectQuote(
          this.project.id,
          this.quote.id,
          this.quoteForm.getRawValue()
        )
        .subscribe(() => {
          this.createQuoteItem(item);
        });
    } else {
      this.createQuoteItem(item);
    }

    this.checkDefaultModifierForCreation();
  }

  protected updateItem(item: EntityItem): void {
    this.isLoading = true;

    this.sharedService
      .editQuoteItem(this.project.id, this.quote.id, item, item.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.items.splice(
            this.items.findIndex(i => i.id === response.id),
            1,
            new EntityItem(response)
          );
          this.toastrService.success('Item updated successfully', 'Success');
        },
        error => {
          this.toastrService.error(
            error?.message ??
              'Something went wrong while trying to update this item',
            'Error'
          );
        }
      );
  }

  protected orderItems(items: EntityItem[], index: number): void {
    this.sharedService
      .editQuoteItem(
        this.project.id,
        this.quote.id,
        items[index],
        items[index].id
      )
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        () => {
          this.items = items;
          this.toastrService.success('Item updated successfully', 'Success');
        },
        error => {
          this.toastrService.error(
            error?.message ??
              'Something went wrong while trying to update this item',
            'Error'
          );
        }
      );
  }

  protected deleteItem(item: EntityItem): void {
    this.deleteQuoteItems([item.id]);
  }

  protected createModifier(modifier: EntityPriceModifier): void {
    this.isLoading = true;

    this.sharedService
      .addQuoteModifier(this.project.id, this.quote.id, modifier)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          response.order = this.quote.price_modifiers.length;
          this.quote.price_modifiers.push(response);
          this.updateEntityModifiers(response);

          this.toastrService.success('Modifier added  successfully', 'Success');
        },
        error => {
          this.toastrService.error(
            error?.message ??
              'Something went wrong while trying to add this modifier',
            'Error'
          );
        }
      );
  }

  protected updateModifier(modifier: EntityPriceModifier): void {
    this.isLoading = true;

    this.sharedService
      .editQuoteModifier(this.project.id, this.quote.id, modifier, modifier.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.replacePriceModifier(response);
          this.toastrService.success(
            'Modifier updated successfully',
            'Success'
          );
        },
        error => {
          this.toastrService.error(
            error?.message ??
              'Something went wrong while trying to update this modifier',
            'Error'
          );
        }
      );
  }

  protected deleteModifier(modifier: EntityPriceModifier): void {
    this.isLoading = true;

    this.sharedService
      .deleteQuoteModifier(this.project.id, this.quote.id, modifier.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        () => {
          this.removePriceModifier(modifier.id);
          this.quote.price_modifiers = [...this.modifiers];
          this.toastrService.success(
            'Modifier deleted successfully',
            'Success'
          );
        },
        error => {
          this.toastrService.error(
            error?.message ??
              'Something went wrong while trying to delete this modifier',
            'Error'
          );
        }
      );
  }

  protected editEntityVat(percentage: number) {
    this.taxRate = percentage;
    this.vatPercentage = percentage;

    if (this.quote) {
      this.quote.vat_percentage = percentage;
      this.editQuote(this.quote.id, this.quote);
    }
  }

  protected changeEntityVatStatus(status: number): void {
    this.vatStatus = status;

    if (status === VatStatus.NEVER) {
      this.vatPercentage = null;
    }

    if (status === VatStatus.NEVER) {
      this.quote.vat_percentage = null;
    }

    if (this.quote) {
      this.quote.vat_status = status;

      if (status === VatStatus.NEVER) {
        this.quote.vat_percentage = null;
      }

      this.editQuote(this.quote.id, this.quote);
    }
  }

  protected editEntityDownPayment(percentage: number): void {}

  protected changeEntityDownPaymentStatus(status: number): void {}

  private changeQuoteStatus(status: QuoteStatusChangePayload): void {
    this.isLoading = true;
    this.sharedService
      .changeProjectQuoteStatus(this.project.id, this.quote.id, status)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.quote = response;
          this.itemsReadOnly = this.quote.status > QuoteStatus.DRAFT;
          this.readOnly = true;
          this.patchValueQuoteForm();
          this.toastrService.success(
            'Quote updated successfully',
            'Update successful'
          );
          this.refreshProject();
        },
        error => {
          this.toastrService.error(error?.message.status, 'Update failed');
        }
      );
  }

  private initQuoteForm(): void {
    this.quoteForm = this.fb.group(
      {
        name: new FormControl(undefined, [
          Validators.required,
          Validators.maxLength(192),
        ]),
        legal_entity_id: new FormControl(undefined, Validators.required),
        company: new FormControl(
          this.globalService.getCurrentCompanyObservable().value.name
        ),
        date: new FormControl(undefined, Validators.required),
        expiry_date: new FormControl(undefined, Validators.required),
        contact_id: new FormControl(undefined, Validators.required),
        sales_person_id: new FormControl(undefined, Validators.required),
        second_sales_person_id: new FormControl(undefined),
        reference: new FormControl(undefined, Validators.maxLength(50)),
        currency_code: new FormControl(undefined, Validators.required),
        manual_input: new FormControl(false),
        master: new FormControl(false),
        down_payment: new FormControl(undefined),
        custom_percentage: new FormControl(undefined, [
          Validators.max(100),
          Validators.min(0),
        ]),
        fixed_amount: new FormControl(undefined, [
          Validators.maxLength(10),
          Validators.pattern(currencyRegEx),
        ]),
      },
      { validators: [dateBeforeValidator('date', 'expiry_date')] }
    );

    const firstQuote = this.project?.quotes.rows.data.reduce((min, current) =>
      min.number < current.number ? min : current
    );

    if (
      typeof this.project === 'undefined' ||
      this.project.quotes.rows.count < 1 ||
      (this.quote?.id === firstQuote.id &&
        this.quote?.status === QuoteStatus.DRAFT)
    ) {
      this.quoteForm
        .get('sales_person_id')
        .setValidators([Validators.required]);
      this.quoteForm.get('sales_person_id').updateValueAndValidity();
      this.quoteForm.get('sales_person_id').enable();
    } else {
      this.quoteForm.get('sales_person_id').clearValidators();
      this.quoteForm.get('sales_person_id').updateValueAndValidity();
      this.quoteForm.get('sales_person_id').disable();
      this.quoteForm.get('second_sales_person_id').clearValidators();
      this.quoteForm.get('second_sales_person_id').updateValueAndValidity();
      this.quoteForm.get('second_sales_person_id').disable();
      this.quoteForm.get('contact_id').clearValidators();
      this.quoteForm.get('contact_id').updateValueAndValidity();
      this.quoteForm.get('contact_id').disable();
    }

    if (this.globalService.getUserRole() === UserRole.PROJECT_MANAGER) {
      this.quoteForm.disable();
      this.readOnly = true;
      this.itemsReadOnly = true;
    }
  }

  private patchValueQuoteForm(): void {
    if (this.quote) {
      this.quoteForm.patchValue(this.quote);
      this.displayFixedDownPayment();
      this.displayCustomDownPayment();
    }
  }

  private createQuote(quote: Quote): void {
    this.isLoading = true;

    if (!this.project) {
      this.sharedService.createQuote(quote).subscribe(
        response => {
          this.createQuoteItems(response.id, response.project_id);
        },
        error => {
          this.isLoading = false;
          this.toastrService.error(error?.message, 'Creation failed');
        }
      );
    } else {
      this.sharedService.createProjectQuote(this.project.id, quote).subscribe(
        response => {
          this.createQuoteItems(response.id, this.project.id);
        },
        error => {
          this.isLoading = false;
          this.toastrService.error(error?.message, 'Creation failed');
        }
      );
    }
  }

  private createQuoteItems(quoteID: string, project_id: string): void {
    const itemRequests = this.items.map(item => {
      return this.sharedService
        .addQuoteItem(project_id, quoteID, item)
        .pipe(catchError(err => of({ error: err })));
    });

    const modifierRequests = this.modifiers.map(modifier => {
      return this.sharedService
        .addQuoteModifier(project_id, quoteID, modifier)
        .pipe(catchError(err => of({ error: err })));
    });

    const requests = itemRequests.concat(modifierRequests);

    if (!requests.length) {
      let route;

      if (this.project) {
        route = [`../${quoteID}/edit`];
      } else {
        route = [`../${project_id}/quotes/${quoteID}/edit`];
      }

      this.router.navigate(route, { relativeTo: this.route }).then();
      return;
    }

    forkJoin(requests)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(responses => {
        const errors = responses.filter(r => r.error);

        if (errors.length) {
          this.toastrService.warning(
            'Quote was created but some items could not be created',
            'Creation partial'
          );
        } else {
          this.toastrService.success(
            'Quote created successfully',
            'Creation successful'
          );
        }

        if (this.project) {
          if (this.project.quotes.rows.count > 0) {
            this.router
              .navigate([
                '/' + this.globalService.currentCompany.id + '/quotes',
              ])
              .then();
          } else {
            this.router
              .navigate([`../${this.project.id}/quotes/${quoteID}/edit`], {
                relativeTo: this.route,
              })
              .then();
          }
        } else {
          this.router
            .navigate(['/' + this.globalService.currentCompany.id + '/quotes'])
            .then();
        }
      });
  }

  private deleteQuoteItems(ids: string[]): void {
    this.isLoading = true;

    this.sharedProjectEntityService
      .deleteEntityItems(
        this.project.id,
        ProjectEntityEnum.QUOTES,
        this.quote.id,
        ids
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.removeItemsFromList(ids);

          if (this.entityModifiersShouldBeDeleted) {
            this.deleteEntityModifiers();
          }

          this.toastrService.success('Item deleted successfully', 'Success');
        },
        error => {
          this.toastrService.error(
            error?.message ??
              'Something went wrong while trying to delete this item',
            'Error'
          );
        }
      );
  }

  private editQuote(quoteID: string, quote: Quote): void {
    this.isLoading = true;

    this.sharedService
      .editProjectQuote(this.project.id, quoteID, quote)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.quote = response;
          this.taxRate = response.tax_rate;
          this.patchValueQuoteForm();
          this.toastrService.success(
            'Quote updated successfully',
            'Update successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  private checkDefaultModifierForCreation(): void {
    if (this.entityModifiersShouldBeCreated(this.quote)) {
      this.createModifier(this.modifiers[0]);
    }
  }

  private createQuoteItem(item: EntityItem): void {
    this.isLoading = true;

    this.sharedService
      .addQuoteItem(this.project.id, this.quote.id, item)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.items.push(new EntityItem(response));
          this.toastrService.success('Item added successfully', 'Success');
        },
        error => {
          this.toastrService.error(
            typeof error?.message === 'string'
              ? error?.message
              : 'Something went wrong while trying to add this item',
            'Error'
          );
        }
      );
  }

  private toggleQuoteManualInput(): void {
    this.quoteForm
      .get('manual_input')
      .patchValue(!this.quoteForm.get('manual_input').value);
  }

  private toggleQuoteMasterInput(): void {
    this.quoteForm
      .get('master')
      .patchValue(!this.quoteForm.get('master').value);
  }

  private deleteEntityModifiers(): void {
    const deleteModifiersRequests = this.modifiers.map(m => {
      return this.sharedService.deleteQuoteModifier(
        this.project.id,
        this.quote.id,
        m.id
      );
    });

    forkJoin(deleteModifiersRequests)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.removePriceModifiers();
        this.quote.price_modifiers = [];
      });
  }

  private initSalesTypeAhead(): void {
    let params = new HttpParams();
    params = Helpers.setParam(params, 'type', '3');

    this.salesSelect$ = concat(
      of(this.salesDefault), // default items
      this.salesInput$.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isSalesLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestUsers(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isSalesLoading = false;
            })
          )
        )
      )
    );
  }

  private initSecondSalesTypeAhead(): void {
    let params = new HttpParams();
    params = Helpers.setParam(params, 'type', '3');

    this.secondSalesSelect$ = concat(
      of(this.secondSalesDefault), // default items
      this.secondSalesInput$.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isSalesLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestUsers(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isSalesLoading = false;
            })
          )
        )
      )
    );
  }

  private initCustomerTypeAhead(): void {
    const params = new HttpParams();
    this.customerSelect$ = concat(
      of(this.customerDefault), // default items
      this.customerInput$.pipe(
        filter(v => !!v),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isCustomerLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestCustomer(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isCustomerLoading = false;
            })
          )
        )
      )
    );
  }

  private getResolvedData(): void {
    const { quote } = this.route.snapshot.data;
    this.quote = quote;
    this.project =
      this.route.parent.parent.snapshot.data.project ??
      this.route.parent.parent.parent.snapshot.data.project;
    this.legalEntityCountry = this.quote?.legal_country;

    if (this.project) {
      this.setProjectValues();
      this.priceModifierLogicService.init(
        this.project.price_modifiers_calculation_logic
      );
    }
  }

  private setProjectValues(): void {
    this.selectedCustomer = {
      default_currency: this.quote?.currency_code,
      id: this.project.customer?.id,
      name: this.project.customer?.name,
      sales_person_id: this.quote?.sales_person_id[0],
      non_vat_liable: this.project.customer?.non_vat_liable,
    } as CustomerSearchEntity;

    this.customerDefault = [
      {
        id: this.project.customer?.id,
        name: this.project.customer?.name,
      },
    ];

    this.customerCurrency = this.route.parent.snapshot.data.currency?.currency;
    this.itemsReadOnly = this.quote?.status > QuoteStatus.DRAFT;

    this.legalEntityCountry = this.quote?.legal_country;
    this.taxRate = this.quote?.tax_rate;
    this.customerNonVatLiable = this.project.customer?.non_vat_liable;

    if (this.project.quotes?.rows?.data?.length > 0) {
      this.customerFixed = true;
    }

    this.contactSelect$ = Helpers.mapToSelectArray(
      this.project.customer?.contacts,
      ['id'],
      ['first_name', 'last_name'],
      ' '
    );
  }

  private patchSalesValue(): void {
    if (
      !this.quote &&
      this.globalService.getUserRole() === UserRole.SALES_PERSON
    ) {
      this.quoteForm.controls.sales_person_id.patchValue(
        this.globalService.userDetails.id
      );
      this.salesDefault = [
        {
          id: this.globalService.userDetails.id,
          name: `${this.globalService.userDetails.first_name} ${this.globalService.userDetails.last_name}`,
        },
      ];
    }
  }

  private refreshProject(): void {
    this.isLoading = true;

    this.projectService
      .getProject(this.project.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.projectService.currentProject = response;
        this.project = response;
      });
  }

  private subscribeToCompanyChange(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy), skip(1))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/quotes']).then();
        }
      });
  }

  private setQuoteValues(): void {
    if (this.quote) {
      const role = this.globalService.getUserRole();

      this.salesDefault = this.quote.sales_person_id.map((id, index) => {
        return { id, name: this.quote.sales_person[index] };
      });

      this.secondSalesDefault = this.quote.second_sales_person_id.map(
        (id, index) => {
          return { id, name: this.quote.second_sales_person[index] };
        }
      );

      this.readOnly =
        this.quote.status !== QuoteStatus.DRAFT ||
        role === UserRole.OWNER_READ_ONLY;
      this.itemsReadOnly = role === UserRole.OWNER_READ_ONLY;
      this.updateEnabled = true;

      if (this.quote.manual_input) {
        this.currency = this.quote.currency_code;
      }

      this.items = this.quote.items?.map(i => {
        return new EntityItem(i);
      });

      this.modifiers = this.quote.price_modifiers?.map(m => {
        return new EntityPriceModifier(m);
      });
      this.setPriorityOrder();
      this.sortModifiers();
      this.displayCustomDownPayment();
      this.displayFixedDownPayment();
    } else {
      this.quoteForm.controls.date.patchValue(moment());
      this.updateExpiryDate(moment());

      if (this.project) {
        this.quoteForm.controls.currency_code.patchValue(this.customerCurrency);
        const sales_person_names = this.project.sales_person
          .split(',')
          .map(word => word.trim());
        this.salesDefault = this.project.sales_person_id.map((id, index) => {
          return { id, name: sales_person_names[index] };
        });
        const second_sales_person_names = this.project.second_sales_person
          .split(',')
          .map(word => word.trim());
        this.secondSalesDefault = this.project.second_sales_person_id.map(
          (id, index) => {
            return { id, name: second_sales_person_names[index] };
          }
        );

        this.quoteForm.controls.sales_person_id.patchValue(
          this.project.sales_person_id
        );
        this.quoteForm.controls.second_sales_person_id.patchValue(
          this.project.second_sales_person_id
        );

        this.salesSelect$ = concat(of(this.salesDefault), this.salesSelect$);
        this.secondSalesSelect$ = concat(
          of(this.secondSalesDefault),
          this.secondSalesSelect$
        );
        const contacts = Helpers.mapToSelectArray(
          this.project.customer?.contacts,
          ['id'],
          ['first_name', 'last_name'],
          ' '
        );
        this.quoteForm.controls.contact_id.patchValue(contacts[0].key);
        this.contactSelect$ = contacts;
        this.cdr.detectChanges();
      }
    }
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }

  public downPaymentPercentageChanged(value: number): void {
    if (value === QuoteDownPayment.PERCENTAGE) {
      this.quoteForm.controls.custom_percentage.setValidators([
        Validators.required,
        Validators.min(0),
      ]);
    } else {
      this.quoteForm.controls.custom_percentage.clearValidators();
      this.quoteForm.controls.custom_percentage.patchValue(undefined);
    }

    if (value === QuoteDownPayment.FIXED) {
      this.quoteForm.controls.fixed_amount.setValidators([
        Validators.required,
        Validators.min(0),
      ]);
    } else {
      this.quoteForm.controls.fixed_amount.clearValidators();
      this.quoteForm.controls.fixed_amount.patchValue(undefined);
    }

    this.quoteForm.controls.custom_percentage.updateValueAndValidity();
    this.quoteForm.controls.fixed_amount.updateValueAndValidity();
  }

  private updateDownPaymentValue(value): void {
    if (value.down_payment === QuoteDownPayment.PERCENTAGE) {
      value.down_payment = value.custom_percentage;
      this.downPaymentType = QuoteDownPayment.PERCENTAGE;
    }
    if (value.down_payment === QuoteDownPayment.FIXED) {
      value.down_payment = value.fixed_amount;
      this.downPaymentType = QuoteDownPayment.FIXED;
    }
    delete value.custom_percentage;
    delete value.fixed_amount;
  }

  private displayCustomDownPayment(): void {
    if (
      this.quote.down_payment !== undefined ||
      this.quote.down_payment !== null
    ) {
      const downPaymentIndex = this.downPaymentOptions.findIndex(
        p => p.value === this.quote.down_payment_type
      );
      if (downPaymentIndex === QuoteDownPayment.PERCENTAGE) {
        this.quoteForm.controls.custom_percentage.patchValue(
          this.quote.down_payment
        );
        this.quoteForm.controls.down_payment.patchValue(
          QuoteDownPayment.PERCENTAGE
        );
      }
    }
  }

  private displayFixedDownPayment(): void {
    if (
      this.quote.down_payment !== undefined ||
      this.quote.down_payment !== null
    ) {
      const downPaymentIndex = this.downPaymentOptions.findIndex(
        p => p.value === this.quote.down_payment_type
      );
      if (downPaymentIndex === QuoteDownPayment.FIXED) {
        this.quoteForm.controls.fixed_amount.patchValue(
          this.quote.down_payment
        );
        this.quoteForm.controls.down_payment.patchValue(QuoteDownPayment.FIXED);
        this.setCurrencyPrefix();
      }
    }
  }

  private setCurrencyPrefix(): void {
    const currencyCode =
      this.globalService.getUserRole() === UserRole.ADMINISTRATOR
        ? environment.currency
        : this.globalService.userCurrency;

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(currencyCode),
        'wide'
      ) + ' ';
  }
}
