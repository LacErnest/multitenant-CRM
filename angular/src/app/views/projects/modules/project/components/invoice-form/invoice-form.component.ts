import { transition, trigger, useAnimation } from '@angular/animations';
import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { forkJoin, of, Subject } from 'rxjs';
import { catchError, finalize, skip, takeUntil } from 'rxjs/operators';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { EntityItemContainerBase } from 'src/app/shared/classes/entity-item/entity-item-container-base';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { LegalEntityChosen } from 'src/app/shared/interfaces/legal-entity-chosen';
import { SharedProjectEntityService } from 'src/app/shared/services/shared-entity.service';
import { dateBeforeValidator } from 'src/app/shared/validators/date-before.validator';
import { InvoiceStatus } from 'src/app/views/projects/modules/project/enums/invoice-status.enum';
import { DateOption } from 'src/app/views/projects/modules/project/interfaces/date-option';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { InvoiceStatusUpdate } from 'src/app/views/projects/modules/project/interfaces/invoice-status-update';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectInvoiceService } from 'src/app/views/projects/modules/project/services/project-invoice.service';
import * as moment from 'moment';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { VatStatus } from '../../../../../../shared/enums/vat-status.enum';
import { DownPaymentStatus } from 'src/app/shared/enums/down-payment-status.enum';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { PriceModifierCalculationLogicService } from 'src/app/shared/services/price-modifier-calculatation-logic.service';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { EmailTemplate } from 'src/app/views/settings/modules/email-management/interfaces/email-template';
import { ErrorHandlerService } from 'src/app/core/services/error-handler.service';

@Component({
  selector: 'oz-finance-invoice-form',
  templateUrl: './invoice-form.component.html',
  styleUrls: ['./invoice-form.component.scss'],
  animations: [
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class InvoiceFormComponent
  extends EntityItemContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;

  public readOnly = false;
  public isLoading = false;
  public userRole: number;
  public userRoleEnum = UserRole;
  public invoiceStatusEnum = InvoiceStatus;
  public showInvoiceActionBtns = false;
  public invoiceStatusChangeDisabled = false;
  public onPartialPay: Subject<void> = new Subject<void>();
  public onPartialPayModalToogle: Subject<boolean> = new Subject<boolean>();
  public onActiveModalToogle: Subject<string> = new Subject<string>();
  public onEmailTemplateSelect: Subject<boolean> = new Subject<boolean>();
  public activeModal: string;
  public dateOption: 0 | 30 | 45 | 60 | 'custom' = 0;
  public entity: number = TablePreferenceType.PROJECT_INVOICES;
  public dateOptions: DateOption[] = [
    { key: 0, value: 'Immediate' },
    { key: 30, value: '30 days' },
    { key: 45, value: '45 days' },
    { key: 60, value: '60 days' },
    { key: 'custom', value: 'Custom' },
  ];

  public legalEntityCountry: number;
  public taxRate: number;
  public downPayment: number;

  public invoiceForm: FormGroup;
  public invoice: Invoice;
  public project: Project;

  private onDestroy$: Subject<void> = new Subject<void>();
  private vatStatus: number;
  private vatPercentage: number;
  private downPaymentStatus: number = DownPaymentStatus.NEVER;
  private downPaymentPercentage: number;
  private openTooglePaymentModalEventSubscription: any;
  private globalModalStateSubscription: any;
  public paymentModalStatus = false;
  public invoicePaymentRefreshSubject: Subject<boolean> =
    new Subject<boolean>();
  public emailTemplate: EmailTemplate;

  public constructor(
    protected globalService: GlobalService,
    private fb: FormBuilder,
    private projectInvoiceService: ProjectInvoiceService,
    private route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService,
    private enumService: EnumService,
    private sharedProjectEntityService: SharedProjectEntityService,
    private legalEntitiesService: LegalEntitiesService,
    public priceModifierLogicService: PriceModifierCalculationLogicService,
    private errorHandlerService: ErrorHandlerService
  ) {
    super(globalService, priceModifierLogicService);
  }
  public ngOnInit(): void {
    this.getResolvedData();
    this.initInvoiceForm();
    this.invoiceForm.get('master').patchValue(this.project.order.master);
    this.patchValueInvoiceForm();
    this.setInvoiceValues();
    this.subscribeToCompanyChange();
    this.eventsSubscribe();
    this.checkIfDisabledControl();
    this.setInvoicePaymentTerms();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
    this.openTooglePaymentModalEventSubscription.unsubscribe();
    this.globalModalStateSubscription.unsubscribe();
  }

  public invoiceStatusUpdated(updateData: InvoiceStatusUpdate): void {
    this.invoice.status = updateData.status;
    this.invoice.pay_date = updateData.pay_date;
    this.patchValueInvoiceForm();
    this.checkReadOnlyMode();
    if (this.invoice.status === InvoiceStatus.DRAFT) {
      this.invoicePaymentRefreshSubject.next(true);
    }
    if (this.invoice.status === InvoiceStatus.AUTHORISED) {
      this.loadInvoiceTemplate();
    }
  }

  public invoiceUpdated(invoice: Invoice): void {
    this.invoice = invoice;

    this.patchValueInvoiceForm();
    this.checkReadOnlyMode();
  }

  public submit(): void {
    if (this.invoiceForm.valid && !this.isLoading) {
      const value = this.invoiceForm.getRawValue();
      value.vat_status = this.vatStatus;
      value.vat_percentage = this.vatPercentage;
      value.down_payment_status = this.downPaymentStatus;
      value.down_payment = this.downPaymentPercentage;

      this.invoice
        ? this.editInvoice(this.invoice.id, value)
        : this.createInvoice(value);
    }
  }

  public updateDueDate(event): void {
    const m = moment(event.value);
    if (this.dateOption !== 'custom') {
      this.invoiceForm.controls.due_date.patchValue(
        m.add(this.dateOption, 'days')
      );
    }
  }

  public dateOptionChanged(event): void {
    if (event === 'custom') {
      this.invoiceForm.controls.due_date.patchValue(undefined);
    } else if (this.invoiceForm.controls.date.value) {
      this.invoiceForm.controls.due_date.patchValue(
        moment(this.invoiceForm.controls.date.value).add(event, 'days')
      );
    }
  }

  public changeCurrency(): void {
    if (this.invoiceForm.get('manual_input').value) {
      this.currency = this.invoiceForm.get('currency_code').value;
    } else {
      this.currency = this.getDefaultCurrency();
    }
  }

  public toggleManualInput(master: boolean): void {
    if (this.readOnly) {
      return;
    }

    master ? this.toggleInvoiceMasterInput() : this.toggleInvoiceManualInput();

    if (this.items.length) {
      this.confirmModal
        .openModal(
          'Confirm',
          'Are you sure you want to proceed?. All existed items will be deleted and you will have to add them again.'
        )
        .subscribe(result => {
          if (result) {
            const itemIds = this.items.map(i => i.id);
            this.invoice
              ? this.deleteInvoiceItems(itemIds)
              : this.removeItemsFromList(itemIds, true);
          } else {
            master
              ? this.toggleInvoiceMasterInput()
              : this.toggleInvoiceManualInput();
          }
        });
    }
  }

  public toggleEligibleForEarnout(): void {
    if (this.readOnly) {
      return;
    }
    this.invoiceForm
      .get('eligible_for_earnout')
      .patchValue(!this.invoiceForm.get('eligible_for_earnout').value);
  }

  public get customerIsNonVatLiable(): boolean {
    return this.project?.customer?.non_vat_liable ?? false;
  }

  public statusColor(): string {
    let color: string;
    switch (this.invoice.status) {
      case InvoiceStatus.DRAFT:
        color = 'draft-status';
        break;
      case InvoiceStatus.APPROVAL:
        color = 'approval-status';
        break;
      case InvoiceStatus.SUBMITTED:
        color = 'sent-status';
        break;
      case InvoiceStatus.REFUSED:
        color = 'declined-status';
        break;
      case InvoiceStatus.AUTHORISED:
        color = 'active-status';
        break;
      case InvoiceStatus.PAID:
        color = 'paid-status';
        break;
      case InvoiceStatus.UNPAID:
        color = 'invoiced-status';
        break;
      case InvoiceStatus.CANCELED:
        color = 'cancelled-status';
        break;
      case InvoiceStatus.PARTIAL_PAID:
        color = 'partial-paid-status';
        break;
    }

    return color;
  }

  protected createItem(item: EntityItem): void {
    const invoiceNeedsUpdate =
      !this.invoice.manual_input && this.invoiceForm.get('manual_input').value;

    if (invoiceNeedsUpdate) {
      this.projectInvoiceService
        .editProjectInvoice(
          this.project.id,
          this.invoice.id,
          this.invoiceForm.getRawValue()
        )
        .subscribe(() => {
          this.createInvoiceItem(item);
        });
    } else {
      this.createInvoiceItem(item);
    }

    this.checkDefaultModifierForCreation();
  }

  public onSelectedEntityChanged(data: LegalEntityChosen): void {
    this.legalEntityCountry = data.country;

    if (!this.vatPercentage && this.legalEntityCountry) {
      this.legalEntitiesService.getCurrentTaxRate(data.id).subscribe(res => {
        this.taxRate = res.tax_rate;
      });
    }
  }

  public checkIfLegacyCustomer(): boolean {
    if (this.project.customer.legacy_customer) {
      if (!this.invoice?.legal_entity_id) {
        return true;
      }
    }

    return false;
  }

  protected updateItem(item: EntityItem): void {
    this.isLoading = true;

    this.projectInvoiceService
      .editInvoiceItem(this.project.id, this.invoice.id, item, item.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.items.splice(
            this.items.findIndex(i => i.id === item.id),
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

  protected deleteItem(item: EntityItem): void {
    this.deleteInvoiceItems([item.id]);
  }

  protected createModifier(modifier: EntityPriceModifier): void {
    this.isLoading = true;

    this.projectInvoiceService
      .addInvoiceModifier(this.project.id, this.invoice.id, modifier)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.invoice.price_modifiers.push(response);
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

    this.projectInvoiceService
      .editInvoiceModifier(
        this.project.id,
        this.invoice.id,
        modifier,
        modifier.id
      )
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
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

    this.projectInvoiceService
      .deleteInvoiceModifier(this.project.id, this.invoice.id, modifier.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.removePriceModifier(modifier.id);
          this.invoice.price_modifiers = [...this.modifiers];

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

  protected orderItems(items: EntityItem[], index: number): void {
    this.isLoading = true;

    this.projectInvoiceService
      .editInvoiceItem(
        this.project.id,
        this.invoice.id,
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

  protected editEntityVat(percentage: number): void {
    this.vatPercentage = percentage;
    this.taxRate = percentage;

    if (this.invoice) {
      this.invoice.vat_percentage = percentage;
      this.editInvoice(this.invoice.id, this.invoice);
    }
  }

  protected changeEntityVatStatus(status: number): void {
    this.vatStatus = status;

    if (status === VatStatus.NEVER) {
      this.vatPercentage = null;
    }

    if (this.invoice) {
      this.invoice.vat_status = status;

      if (status === VatStatus.NEVER) {
        this.invoice.vat_percentage = null;
      }

      this.editInvoice(this.invoice.id, this.invoice);
    }
  }

  protected editEntityDownPayment(percentage: number): void {
    this.downPaymentPercentage = percentage;
    this.downPayment = percentage;

    if (this.invoice) {
      this.invoice.down_payment = percentage;
      this.editInvoice(this.invoice.id, this.invoice);
    }
  }

  protected changeEntityDownPaymentStatus(status: number): void {
    this.downPaymentStatus = status;

    if (status === DownPaymentStatus.NEVER) {
      this.downPaymentPercentage = null;
    }

    if (this.invoice) {
      this.invoice.down_payment_status = status;

      if (status === DownPaymentStatus.NEVER) {
        this.invoice.down_payment = null;
      }

      this.editInvoice(this.invoice.id, this.invoice);
    }
  }

  private initInvoiceForm(): void {
    this.invoiceForm = this.fb.group(
      {
        legal_entity_id: new FormControl(undefined, Validators.required),
        customer: new FormControl(this.project.customer.name),
        company: new FormControl(
          this.globalService.getCurrentCompanyObservable().value.name
        ),
        date: new FormControl(undefined, Validators.required),
        due_date: new FormControl(undefined, Validators.required),
        paid_date: new FormControl(undefined),
        status: new FormControl(0, Validators.required),
        reference: new FormControl(undefined, Validators.maxLength(50)),
        currency_code: new FormControl(undefined),
        manual_input: new FormControl(false),
        master: new FormControl(false),
        payment_terms: new FormControl(null),
        email_template_id: new FormControl(null),
        send_client_reminders: new FormControl(null),
        eligible_for_earnout: new FormControl(
          !this.project.order.intra_company
        ),
        customer_has_been_notified: new FormControl(
          !!this.invoice?.customer_notified_at
        ),
      },
      {
        validators: [
          dateBeforeValidator('date', 'due_date'),
          dateBeforeValidator('date', 'paid_date'),
        ],
      }
    );
  }

  private patchValueInvoiceForm(): void {
    if (this.invoice) {
      this.invoiceForm.patchValue(this.invoice);
      this.downPaymentPercentage = this.downPayment;
      this.vatPercentage = this.taxRate;
    }
  }

  private checkReadOnlyMode(): void {
    const isUserWithNoEditRights = [
      UserRole.SALES_PERSON,
      UserRole.PROJECT_MANAGER,
      UserRole.OWNER_READ_ONLY,
      UserRole.PROJECT_MANAGER_RESTRICTED,
    ].includes(this.userRole);

    this.invoiceStatusChangeDisabled =
      isUserWithNoEditRights || !this.invoice?.legal_entity_id;

    this.readOnly =
      isUserWithNoEditRights || this.invoice?.status >= InvoiceStatus.SUBMITTED;
  }

  private createInvoice(invoice: Invoice): void {
    this.isLoading = true;

    this.projectInvoiceService
      .createProjectInvoice(this.project.id, invoice)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.createInvoiceItems(response.id);
        },
        error => {
          const msg = error?.message?.tax_number || error?.message;
          this.toastrService.error(msg, 'Creation failed');
        }
      );
  }

  private createInvoiceItem(item: EntityItem): void {
    this.isLoading = true;

    this.projectInvoiceService
      .addInvoiceItem(this.project.id, this.invoice.id, item)
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

  private createInvoiceItems(invoiceID: string): void {
    const itemRequests = this.items.map(item => {
      return this.projectInvoiceService
        .addInvoiceItem(this.project.id, invoiceID, item)
        .pipe(catchError(err => of({ error: err })));
    });

    const modifierRequests = this.modifiers.map(modifier => {
      return this.projectInvoiceService
        .addInvoiceModifier(this.project.id, invoiceID, modifier)
        .pipe(catchError(err => of({ error: err })));
    });

    const requests = itemRequests.concat(modifierRequests);

    if (!requests.length) {
      this.router
        .navigate([`../${invoiceID}/edit`], { relativeTo: this.route })
        .then();
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

        if (errors.length === 0) {
          this.toastrService.success(
            'Invoice created successfully',
            'Creation successful'
          );
        } else {
          this.toastrService.warning(
            'Invoice was created but some items could not be created',
            'Creation partial'
          );
        }

        this.router
          .navigate([`../${invoiceID}/edit`], { relativeTo: this.route })
          .then();
      });
  }

  private deleteInvoiceItems(ids: string[]): void {
    this.isLoading = true;

    this.sharedProjectEntityService
      .deleteEntityItems(
        this.project.id,
        ProjectEntityEnum.INVOICES,
        this.invoice.id,
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

  private editInvoice(invoiceID: string, invoice: Invoice): void {
    this.isLoading = true;
    invoice.legal_entity_id = this.invoiceForm.get('legal_entity_id').value;
    this.projectInvoiceService
      .editProjectInvoice(this.project.id, invoiceID, invoice)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.invoice = response;
          this.taxRate = response.tax_rate;
          this.downPayment = response.down_payment ?? 0;
          this.patchValueInvoiceForm();
          this.checkReadOnlyMode();
          this.setInvoiceValues();
          this.toastrService.success(
            'Invoice updated successfully',
            'Update successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  private toggleInvoiceManualInput(): void {
    this.invoiceForm
      .get('manual_input')
      .patchValue(!this.invoiceForm.get('manual_input').value);
  }

  private toggleInvoiceMasterInput(): void {
    this.invoiceForm
      .get('master')
      .patchValue(!this.invoiceForm.get('master').value);
  }

  private deleteEntityModifiers(): void {
    const deleteModifiersRequests = this.modifiers.map(m => {
      return this.projectInvoiceService.deleteInvoiceModifier(
        this.project.id,
        this.invoice.id,
        m.id
      );
    });

    forkJoin(deleteModifiersRequests)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.removePriceModifiers();
        this.invoice.price_modifiers = [];
      });
  }

  private checkDefaultModifierForCreation(): void {
    if (this.entityModifiersShouldBeCreated(this.invoice)) {
      this.createModifier(this.modifiers[0]);
    }
  }

  private determineDateOption(): void {
    const thirty = moment(this.invoice.date).add(30, 'days');
    const fourtyFive = moment(this.invoice.date).add(45, 'days');
    const sixty = moment(this.invoice.date).add(60, 'days');

    switch (moment(this.invoice.due_date).toISOString()) {
      case moment(this.invoice.date).toISOString():
        this.dateOption = 0;
        break;
      case thirty.toISOString():
        this.dateOption = 30;
        break;
      case fourtyFive.toISOString():
        this.dateOption = 45;
        break;
      case sixty.toISOString():
        this.dateOption = 60;
        break;
      default:
        this.dateOption = 'custom';
        break;
    }
  }

  private subscribeToCompanyChange(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const navigateToDashboard = value?.id === 'all';
        this.router
          .navigate([navigateToDashboard ? '/' : `/${value.id}/invoices`])
          .then();
      });
  }

  private getResolvedData(): void {
    const { invoice, emailTemplate } = this.route.snapshot.data;
    this.invoice = invoice;
    this.project =
      this.route.parent.parent.snapshot.data.project ??
      this.route.parent.parent.parent.snapshot.data.project;
    this.userRole = this.globalService.getUserRole();
    this.legalEntityCountry = this.invoice?.legal_country;
    this.taxRate = this.invoice
      ? this.invoice.tax_rate
      : this.globalService.currentCompanyTaxRate;
    this.downPayment = this.invoice?.down_payment ?? 0;
    this.entity = TablePreferenceType.INVOICES;
    this.checkReadOnlyMode();
    this.showInvoiceActionBtns =
      this.invoice &&
      this.userRole !== this.userRoleEnum.SALES_PERSON &&
      this.userRole !== this.userRoleEnum.PROJECT_MANAGER &&
      this.userRole !== this.userRoleEnum.PROJECT_MANAGER_RESTRICTED;

    if (this.project) {
      this.priceModifierLogicService.init(
        this.project.price_modifiers_calculation_logic
      );
    }
    this.emailTemplate = emailTemplate;
  }

  private setInvoiceValues(): void {
    if (this.invoice) {
      this.updateEnabled = true;
      this.determineDateOption();

      if (this.invoice.manual_input) {
        this.currency = this.invoice.currency_code;
      }

      this.items = this.invoice.items?.map(i => {
        return new EntityItem(i);
      });

      this.modifiers = this.invoice.price_modifiers?.map(m => {
        return new EntityPriceModifier(m);
      });
      this.sortModifiers();
    } else {
      this.invoiceForm.controls.currency_code.patchValue(
        this.route.parent.snapshot.data.currency?.currency
      );
    }
  }

  private eventsSubscribe(): void {
    this.openTooglePaymentModalEventSubscription = this.onPartialPayModalToogle
      .asObservable()
      .subscribe((status: boolean) => {
        this.paymentModalStatus = status;
      });
    this.globalModalStateSubscription = this.onActiveModalToogle
      .asObservable()
      .subscribe((modal: string) => {
        this.activeModal = modal;
      });
  }

  private checkIfDisabledControl(): void {
    if (!this.invoice?.legal_entity_id) {
      this.invoiceForm.get('legal_entity_id').enable();
    }
  }

  private setInvoicePaymentTerms(): void {
    if (this.invoice?.payment_terms) {
      this.invoiceForm
        .get('payment_terms')
        .setValue(moment(this.invoice.payment_terms).format('YYYY-MM-DD'));
    } else if (
      this.project.customer.payment_due_date &&
      this.project.customer.payment_due_date > 0
    ) {
      const currentDate = moment();
      const futureDate = currentDate.add(
        this.project.customer.payment_due_date,
        'days'
      );
      this.invoiceForm
        .get('payment_terms')
        .setValue(futureDate.format('YYYY-MM-DD'));
    }
  }

  canToggleEarnoutEligibility(): boolean {
    const allowedRoles = [
      this.userRoleEnum.ACCOUNTANT,
      this.userRoleEnum.ADMINISTRATOR,
      this.userRoleEnum.OWNER,
    ];
    return allowedRoles.includes(this.userRole);
  }

  canToggleRemindersDisabled(): boolean {
    return this.invoice?.status === InvoiceStatus.SUBMITTED;
  }

  public handleEmailTemplateUpdate(emailTemplate: EmailTemplate): void {
    this.emailTemplate = emailTemplate;
    this.invoice.email_template_id = this.emailTemplate.id;
    this.invoiceForm.controls.email_template_id.patchValue(
      this.emailTemplate.id
    );
  }

  get canSelectEmailTemplate(): boolean {
    return !this.readOnly && this.invoice?.status == InvoiceStatus.AUTHORISED;
  }

  get isEmailTemplateVisible(): boolean {
    const authorisedStatus = [
      InvoiceStatus.AUTHORISED,
      InvoiceStatus.SUBMITTED,
      InvoiceStatus.PAID,
      InvoiceStatus.PARTIAL_PAID,
    ];
    return (
      !this.invoice?.email_template_globally_disabled &&
      authorisedStatus.includes(this.invoice?.status)
    );
  }

  public onRequireEmailTemplate(status: boolean): void {
    if (status) {
      this.onEmailTemplateSelect.next(true);
    }
  }

  public toggleSendingClientRemindersStatus(): void {
    this.projectInvoiceService
      .toggleSendingClientRemindersStatus(this.project.id, this.invoice.id)
      .subscribe(
        status => {
          this.invoiceForm.controls.send_client_reminders.patchValue(status);
          this.invoice.send_client_reminders = status;
          const message = status ? 'enabled' : 'disabled';
          this.toastrService.success(
            `Sending client reminders has been successfully ${message}`,
            'Update successful'
          );
        },
        err => this.errorHandlerService.handle(err)
      );
  }

  public loadInvoiceTemplate(): void {
    this.projectInvoiceService
      .getProjectEmailTemplate(this.project.id, this.invoice.id)
      .subscribe(
        template => {
          this.emailTemplate = template;
          this.invoice.email_template_id = template.id;
          this.patchValueInvoiceForm();
        },
        err => this.errorHandlerService.handle(err)
      );
  }
}
