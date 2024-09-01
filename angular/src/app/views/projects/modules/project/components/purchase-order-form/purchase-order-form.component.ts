import { transition, trigger, useAnimation } from '@angular/animations';
import { HttpParams } from '@angular/common/http';
import {
  Component,
  ElementRef,
  OnDestroy,
  OnInit,
  ViewChild,
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
  map,
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
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { RatingModalComponent } from 'src/app/shared/components/rating-modal/rating-modal.component';
import { RefusalModalComponent } from 'src/app/shared/components/refusal-modal/refusal-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { LegalEntityChosen } from 'src/app/shared/interfaces/legal-entity-chosen';
import { Resource } from 'src/app/shared/interfaces/resource';
import { SearchEntity } from 'src/app/shared/interfaces/search-entity';
import { SharedProjectEntityService } from 'src/app/shared/services/shared-entity.service';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { dateBeforeValidator } from 'src/app/shared/validators/date-before.validator';

import { PaidDateModalComponent } from 'src/app/views/projects/modules/project/components/paid-date-modal/paid-date-modal.component';
import { OrderStatus } from 'src/app/views/projects/modules/project/enums/order-status.enum';
import { PurchaseOrderStatus } from 'src/app/shared/enums/purchase-order-status.enum';
import { PurchaseOrder } from 'src/app/shared/interfaces/entities';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ResourceSearchEntity } from 'src/app/views/projects/modules/project/interfaces/search-entity';
import { ProjectPurchaseOrderService } from 'src/app/views/projects/modules/project/services/project-purchase-order.service';
import * as moment from 'moment';
import { ResourceStatus } from 'src/app/views/resources/enums/resource-status.enum';
import { ResourcesService } from 'src/app/views/resources/resources.service';
import { Company } from 'src/app/shared/interfaces/company';
import { LegalEntity } from 'src/app/shared/interfaces/legal-entity';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { VatStatus } from '../../../../../../shared/enums/vat-status.enum';
import { QuoteStatus } from '../../enums/quote-status.enum';
import { SharedService } from '../../../../../../shared/services/shared.service';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { PriceModifierCalculationLogicService } from 'src/app/shared/services/price-modifier-calculatation-logic.service';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';

@Component({
  selector: 'oz-finance-purchase-order-form',
  templateUrl: './purchase-order-form.component.html',
  styleUrls: ['./purchase-order-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class PurchaseOrderFormComponent
  extends EntityItemContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('ratingModal', { static: false })
  public ratingModal: RatingModalComponent;
  @ViewChild('refusalModal', { static: false })
  public refusalModal: RefusalModalComponent;
  @ViewChild('paidModal', { static: false })
  public paidModal: PaidDateModalComponent;
  @ViewChild('upload_file', { static: false }) public upload_file: ElementRef;

  public readOnly = false;
  public isLoading = false;
  public isResourceLoading = false;
  public showPenalties = true;

  public purchaseOrderForm: FormGroup;
  public purchaseOrder: PurchaseOrder;
  public project: Project;
  public purchaseOrderStatus = PurchaseOrderStatus;
  public orderStatus = OrderStatus;

  public legalEntityCountry: number;
  public taxRate: number;

  public resourceSelect$: Observable<ResourceSearchEntity[]>;
  public resourceSelect: Observable<SearchEntity[]>;
  public resourceInput: Subject<string> = new Subject<string>();
  public selectedResource: ResourceSearchEntity;
  public templates: TemplateModel[] = [];
  public resourceNonVatLiable: boolean;
  public entity: TablePreferenceType.PROJECT_PURCHASE_ORDERS;

  private onDestroy$: Subject<void> = new Subject<void>();
  private resourceDefault: ResourceSearchEntity[] = [];
  private resourceSuggestions: ResourceSearchEntity[];
  private vatStatus: number;
  private vatPercentage: number;
  private template_id: string;

  public constructor(
    protected globalService: GlobalService,
    private fb: FormBuilder,
    private projectPurchaseOrderService: ProjectPurchaseOrderService,
    private route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService,
    private enumService: EnumService,
    private suggestService: SuggestService,
    private sharedProjectEntityService: SharedProjectEntityService,
    private resourcesService: ResourcesService,
    private legalEntitiesService: LegalEntitiesService,
    private sharedService: SharedService,
    public priceModifierLogicService: PriceModifierCalculationLogicService
  ) {
    super(globalService, priceModifierLogicService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initPurchaseOrderForm();
    this.patchValuePurchaseOrderForm();
    this.setPurchaseOrderValues();
    this.subscribeToCompanyChange();
    this.initResourceTypeAhead();
    this.getCompanyTemplates();
  }

  public ngOnDestroy(): void {
    this.onDestroy$?.next();
    this.onDestroy$?.complete();
  }

  public download(): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.projectPurchaseOrderService.exportProjectPurchaseOrderCallback,
        [this.project.id, this.purchaseOrder.id, this.template_id],
        'Purchase order: ' + this.purchaseOrder.number,
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

  public showCancelBtn(): boolean {
    const role = this.globalService.getUserRole();
    const status = this.purchaseOrder?.status;

    return (
      (role === UserRole.ADMINISTRATOR || role === UserRole.OWNER) &&
      status !== this.purchaseOrderStatus.CANCELED
    );
  }

  public itemsReadonly(): boolean {
    return this.readOnly || !this.purchaseOrderForm.get('resource_id').value;
  }

  public setOrderAsSubmitted(status: PurchaseOrderStatus): void {
    const statusLabel = this.enumService
      .getEnumMap('purchaseorderstatus')
      .get(status);

    this.confirmModal
      .openModal(
        'Confirm',
        `Are you sure want to change status to ${statusLabel}? This cannot be undone.`
      )
      .subscribe(result => {
        if (result) {
          this.changePurchaseOrderStatus(this.purchaseOrder.id, { status });
        }
      });
  }

  public setOrderAsCanceled(status: PurchaseOrderStatus): void {
    const statusLabel = this.enumService
      .getEnumMap('purchaseorderstatus')
      .get(status);

    this.confirmModal
      .openModal(
        'Confirm',
        `Are you sure want to change status to ${statusLabel}? This cannot be undone.`
      )
      .subscribe(result => {
        if (result) {
          this.changePurchaseOrderStatus(this.purchaseOrder.id, { status });
        }
      });
  }

  public setPurchaseOrderAsDraft(status: PurchaseOrderStatus): void {
    const statusLabel = this.enumService
      .getEnumMap('purchaseorderstatus')
      .get(status);

    this.confirmModal
      .openModal(
        'Confirm',
        `Are you sure want to change status to ${statusLabel}? This cannot be undone.`
      )
      .subscribe(result => {
        if (result) {
          this.changePurchaseOrderStatus(this.purchaseOrder.id, { status });
        }
      });
  }

  public async uploadFile(files: any): Promise<void> {
    const [file] = files;
    const { file: uploaded }: any = await this.readFile(file);

    if (uploaded) {
      this.isLoading = true;

      this.projectPurchaseOrderService
        .uploadPurchaseOrderInvoice(
          this.purchaseOrder.resource_id,
          this.purchaseOrder.id,
          uploaded
        )
        .pipe(
          finalize(() => {
            this.isLoading = false;
            this.upload_file.nativeElement.value = '';
          })
        )
        .subscribe(
          response => {
            this.purchaseOrder = response;
            this.toastrService.success(
              'Invoice uploaded successfully.',
              'Success'
            );
          },
          err => {
            this.upload_file.nativeElement.value = '';
            const msg = err?.message?.tax_number ?? 'Invoice failed to upload.';
            this.toastrService.error(msg, 'Error');
          }
        );
    }
  }

  public setOrderAsPaid(): void {
    this.paidModal
      .openModal(moment.utc().startOf('day'))
      .subscribe(({ pay_date }) => {
        if (pay_date) {
          this.changePurchaseOrderStatus(this.purchaseOrder.id, {
            status: PurchaseOrderStatus.PAID,
            pay_date,
          });
        }
      });
  }

  public setOrderAsAuthorized(): void {
    this.ratingModal.openModal().subscribe(result => {
      this.changePurchaseOrderStatus(this.purchaseOrder.id, {
        status: PurchaseOrderStatus.AUTHORISED,
        ...result,
      });
    });
  }

  public setOrderAsRejected(): void {
    this.refusalModal.openModal().subscribe(({ reason_of_refusal }) => {
      this.changePurchaseOrderStatus(this.purchaseOrder.id, {
        status: PurchaseOrderStatus.REJECTED,
        reason_of_rejection: reason_of_refusal,
      });
    });
  }

  public canBeSubmitted(): boolean {
    const role = this.globalService.getUserRole();
    const isLegalEntitySet: boolean =
      this.purchaseOrder?.legal_entity_id !== null;
    let isPaymentTermsOk = true;

    if (
      (role === UserRole.PROJECT_MANAGER ||
        role === UserRole.ACCOUNTANT ||
        role === UserRole.PROJECT_MANAGER_RESTRICTED) &&
      this.purchaseOrder?.payment_terms < 30
    ) {
      isPaymentTermsOk = false;
    }

    return isLegalEntitySet && isPaymentTermsOk;
  }

  public showUploadInvoiceBtn(): boolean {
    const allowedRoles = [
      UserRole.ADMINISTRATOR,
      UserRole.OWNER,
      UserRole.ACCOUNTANT,
      UserRole.PROJECT_MANAGER,
      UserRole.PROJECT_MANAGER_RESTRICTED,
    ];
    const uploadAllowed = allowedRoles.includes(
      this.globalService.getUserRole()
    );

    return (
      uploadAllowed &&
      (this.purchaseOrder?.status === this.purchaseOrderStatus.AUTHORISED ||
        this.purchaseOrder?.status === this.purchaseOrderStatus.BILLED) &&
      !this.purchaseOrder?.invoice_authorised
    );
  }

  public submit(): void {
    if (this.purchaseOrderForm.valid && !this.isLoading) {
      const value = this.purchaseOrderForm.getRawValue();
      value.penalty = this.penalty;
      value.vat_status = this.vatStatus;
      value.vat_percentage = this.vatPercentage;

      this.purchaseOrder
        ? this.editPurchaseOrder(this.purchaseOrder.id, value)
        : this.createPurchaseOrder(value);
    }
  }

  public updateDeliveryDate(event): void {
    const m = moment(event.value);
    this.purchaseOrderForm.controls.delivery_date.patchValue(m.add(30, 'days'));
  }

  public checkIfReadOnly(): void {
    this.readOnly =
      this.purchaseOrder.status !== PurchaseOrderStatus.DRAFT ||
      this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  public changeCurrency(): void {
    if (this.purchaseOrderForm.get('manual_input').value) {
      this.currency = this.purchaseOrderForm.get('currency_code').value;
    } else {
      this.currency =
        this.selectedResource?.default_currency || this.getDefaultCurrency();
      this.purchaseOrderForm
        .get('currency_code')
        .patchValue(this.selectedResource?.default_currency);
    }
  }

  public resourceChanged(resourceID: string): void {
    if (!resourceID) {
      return;
    }

    this.selectedResource = this.resourceSuggestions.find(
      r => r.id === resourceID
    );
    this.purchaseOrderForm
      .get('currency_code')
      .patchValue(this.selectedResource.default_currency);

    this.currency = this.selectedResource.default_currency;
    this.resourceNonVatLiable = this.selectedResource.non_vat_liable;
  }

  public toggleManualInput(): void {
    if (this.readOnly) {
      return;
    }

    this.togglePurchaseOrderManualInput();

    if (this.items.length) {
      this.confirmModal
        .openModal(
          'Confirm',
          'Are you sure you want to proceed? All existed items will be deleted and you will have to add them again.'
        )
        .subscribe(result => {
          if (result) {
            const itemIds = this.items.map(i => i.id);
            this.purchaseOrder
              ? this.deletePurchaseOrdersItems(itemIds)
              : this.removeItemsFromList(itemIds, true);
          } else {
            this.togglePurchaseOrderManualInput();
          }
        });
    }
  }

  public onSelectedEntityChanged(data: LegalEntityChosen): void {
    this.legalEntityCountry = data.country;

    if (!this.vatPercentage && this.legalEntityCountry) {
      this.legalEntitiesService.getCurrentTaxRate(data.id).subscribe(res => {
        this.taxRate = res.tax_rate;
      });
    }
  }

  public penaltyPercentage(): number {
    return this.purchaseOrder?.penalty;
  }

  public penaltyType(): number {
    return this.purchaseOrder?.penalty_type;
  }

  public penaltyReason(): string {
    return this.purchaseOrder?.reason_of_penalty;
  }

  public showActionBtns(): boolean {
    return this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY;
  }

  public statusColor(): string {
    let color: string;
    switch (this.purchaseOrder.status) {
      case PurchaseOrderStatus.DRAFT:
        color = 'draft-status';
        break;
      case PurchaseOrderStatus.SUBMITTED:
        color = 'sent-status';
        break;
      case PurchaseOrderStatus.REJECTED:
        color = 'declined-status';
        break;
      case PurchaseOrderStatus.AUTHORISED:
        color = 'active-status';
        break;
      case PurchaseOrderStatus.BILLED:
        color = 'invoiced-status';
        break;
      case PurchaseOrderStatus.PAID:
        color = 'paid-status';
        break;
      case PurchaseOrderStatus.CANCELED:
        color = 'cancelled-status';
        break;
      case PurchaseOrderStatus.COMPLETED:
        color = 'completed-status';
        break;
    }

    return color;
  }

  public showDraftButton(): boolean {
    const superUser = this.globalService.userDetails?.super_user;

    return (
      superUser && this.purchaseOrder?.status !== this.purchaseOrderStatus.DRAFT
    );
  }

  public get resourceIsNonVatLiable(): boolean {
    return this.resourceNonVatLiable ?? this.selectedResource?.non_vat_liable;
  }

  protected createItem(item: EntityItem): void {
    const purchaseOrderNeedsUpdate =
      !this.purchaseOrder.manual_input &&
      this.purchaseOrderForm.get('manual_input').value;

    if (purchaseOrderNeedsUpdate) {
      this.projectPurchaseOrderService
        .editProjectPurchaseOrder(
          this.project.id,
          this.purchaseOrder.id,
          this.purchaseOrderForm.getRawValue()
        )
        .subscribe(() => {
          this.createPurchaseOrderItem(item);
        });
    } else {
      this.createPurchaseOrderItem(item);
    }
  }

  protected updateItem(item: EntityItem): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .editPurchaseOrderItem(
        this.project.id,
        this.purchaseOrder.id,
        item,
        item.id
      )
      .pipe(finalize(() => (this.isLoading = false)))
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
    this.deletePurchaseOrdersItems([item.id]);
  }

  protected createModifier(modifier: EntityPriceModifier): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .addPurchaseOrderModifier(
        this.project.id,
        this.purchaseOrder.id,
        modifier
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.purchaseOrder.price_modifiers.push(response);
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

    this.projectPurchaseOrderService
      .editPurchaseOrderModifier(
        this.project.id,
        this.purchaseOrder.id,
        modifier,
        modifier.id
      )
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

    this.projectPurchaseOrderService
      .deletePurchaseOrderModifier(
        this.project.id,
        this.purchaseOrder.id,
        modifier.id
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.removePriceModifier(modifier.id);
          this.purchaseOrder.price_modifiers = [...this.modifiers];
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

    this.projectPurchaseOrderService
      .editPurchaseOrderItem(
        this.project.id,
        this.purchaseOrder.id,
        items[index],
        items[index].id
      )
      .pipe(finalize(() => (this.isLoading = false)))
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
    this.taxRate = percentage;
    this.vatPercentage = percentage;

    if (this.purchaseOrder) {
      this.purchaseOrder.vat_percentage = percentage;
      this.editPurchaseOrder(this.purchaseOrder.id, this.purchaseOrder);
    }
  }

  protected changeEntityVatStatus(status: number): void {
    this.vatStatus = status;

    if (status === VatStatus.NEVER) {
      this.vatPercentage = null;
    }

    if (this.purchaseOrder) {
      this.purchaseOrder.vat_status = status;

      if (status === VatStatus.NEVER) {
        this.purchaseOrder.vat_percentage = null;
      }

      this.editPurchaseOrder(this.purchaseOrder.id, this.purchaseOrder);
    }
  }

  protected editEntityDownPayment(percentage: number): void {}

  protected changeEntityDownPaymentStatus(status: number): void {}

  private createPurchaseOrderItem(item: EntityItem): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .addPurchaseOrderItem(this.project.id, this.purchaseOrder.id, item)
      .pipe(finalize(() => (this.isLoading = false)))
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

  private initPurchaseOrderForm(): void {
    this.purchaseOrderForm = this.fb.group(
      {
        legal_entity_id: new FormControl(undefined),
        company: new FormControl(
          this.globalService.getCurrentCompanyObservable().value.name
        ),
        date: new FormControl(undefined, Validators.required),
        delivery_date: new FormControl(undefined, Validators.required),
        payment_terms: new FormControl(30, [
          Validators.required,
          Validators.min(1),
          Validators.max(255),
        ]),
        resource_id: new FormControl(undefined, Validators.required),
        status: new FormControl(0, Validators.required),
        reference: new FormControl(undefined, Validators.maxLength(50)),
        currency_code: new FormControl(
          (<Company>this.globalService.currentCompany)?.currency,
          Validators.required
        ),
        manual_input: new FormControl(false),
        created_by: new FormControl(undefined),
        authorised_by: new FormControl(undefined),
        processed_by: new FormControl(undefined),
      },
      { validators: [dateBeforeValidator('date', 'delivery_date')] }
    );
  }

  private initResourceTypeAhead(): void {
    let params = new HttpParams();
    params = Helpers.setParam(
      params,
      'status',
      ResourceStatus.ACTIVE.toString()
    );

    this.resourceSelect$ = concat(
      of(this.resourceDefault), // default items
      this.resourceInput.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isResourceLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestResources(term, params).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isResourceLoading = false;
            })
          )
        )
      )
    ).pipe(
      tap(suggestions => {
        this.resourceSuggestions = suggestions;
      })
    );
  }

  private patchValuePurchaseOrderForm(): void {
    if (this.purchaseOrder) {
      this.purchaseOrderForm.patchValue(this.purchaseOrder);
      this.vatPercentage = this.purchaseOrder.tax_rate;
    }

    if (this.purchaseOrderForm.get('manual_input').value) {
      this.currency = this.purchaseOrderForm.get('currency_code').value;
    }

    if (this.project?.purchase_order_project && !this.purchaseOrder) {
      this.purchaseOrderForm
        .get('resource_id')
        .patchValue(this.resourceDefault[0].id);
    }
  }

  private createPurchaseOrder(purchaseOrder: PurchaseOrder): void {
    this.isLoading = true;

    if (!this.project) {
      this.sharedService.createPurchaseOrder(purchaseOrder).subscribe(
        response => {
          this.createPurchaseOrderItems(response.id, response.project_id);
        },
        error => {
          this.isLoading = false;
          this.toastrService.error(error?.message, 'Creation failed');
        }
      );
    } else {
      this.projectPurchaseOrderService
        .createProjectPurchaseOrder(this.project.id, purchaseOrder)
        .pipe(finalize(() => (this.isLoading = false)))
        .subscribe(
          response => {
            // etc....
            this.createPurchaseOrderItems(response.id, response.project_id);
          },
          error => {
            const msg = error?.message?.tax_number?.[0] || error?.message;
            this.toastrService.error(msg, 'Creation failed', {
              timeOut: 10000,
            });
          }
        );
    }
  }

  private createPurchaseOrderItems(
    purchaseOrderID: string,
    projectId: string
  ): void {
    const itemRequests = this.items.map(item => {
      return this.projectPurchaseOrderService
        .addPurchaseOrderItem(projectId, purchaseOrderID, item)
        .pipe(catchError(err => of({ error: err })));
    });

    const modifierRequests = this.modifiers.map(modifier => {
      return this.projectPurchaseOrderService
        .addPurchaseOrderModifier(projectId, purchaseOrderID, modifier)
        .pipe(catchError(err => of({ error: err })));
    });

    const requests = itemRequests.concat(modifierRequests);

    if (!requests.length) {
      this.router
        .navigate([`../${purchaseOrderID}/edit`], { relativeTo: this.route })
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
            'Purchase order created successfully',
            'Creation successful'
          );
        } else {
          this.toastrService.warning(
            'Purchase order was created but some items could not be created',
            'Creation partial'
          );
        }
        this.router
          .navigate([`../${purchaseOrderID}/edit`], { relativeTo: this.route })
          .then();
      });
  }

  private deletePurchaseOrdersItems(ids: string[]): void {
    this.isLoading = true;

    this.sharedProjectEntityService
      .deleteEntityItems(
        this.project.id,
        ProjectEntityEnum.PURCHASE_ORDERS,
        this.purchaseOrder.id,
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

  private editPurchaseOrder(
    purchaseOrderID: string,
    purchaseOrder: PurchaseOrder
  ): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .editProjectPurchaseOrder(this.project.id, purchaseOrderID, purchaseOrder)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.purchaseOrder = response;
          this.taxRate = response.tax_rate;
          this.patchValuePurchaseOrderForm();
          this.toastrService.success(
            'Purchase order updated successfully',
            'Update successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  private togglePurchaseOrderManualInput(): void {
    this.purchaseOrderForm
      .get('manual_input')
      .patchValue(!this.purchaseOrderForm.get('manual_input').value);
  }

  private deleteEntityModifiers(): void {
    const deleteModifiersRequests = this.modifiers.map(m => {
      return this.projectPurchaseOrderService.deletePurchaseOrderModifier(
        this.project.id,
        this.purchaseOrder.id,
        m.id
      );
    });

    forkJoin(deleteModifiersRequests)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.removePriceModifiers();
        this.purchaseOrder.price_modifiers = [];
      });
  }

  private changePurchaseOrderStatus(
    purchaseOrderID: string,
    status: any
  ): void {
    this.isLoading = true;

    this.projectPurchaseOrderService
      .editProjectPurchaseOrderStatus(this.project.id, purchaseOrderID, status)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.purchaseOrder = response;
          this.patchValuePurchaseOrderForm();
          this.checkIfReadOnly();

          this.penalty = this.purchaseOrder.penalty;
          this.showPenalties =
            this.purchaseOrder.status === PurchaseOrderStatus.SUBMITTED;

          this.toastrService.success(
            'Purchase order updated successfully',
            'Update successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  private subscribeToCompanyChange(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const navigateToDashboard = value?.id === 'all';
        this.router
          .navigate([
            navigateToDashboard ? '/' : `/${value.id}/purchase_orders`,
          ])
          .then();
      });
  }

  private setPurchaseOrderValues(): void {
    if (this.purchaseOrder) {
      this.updateEnabled = true;

      const {
        currency_code,
        items,
        penalty,
        price_modifiers,
        resource,
        resource_country,
        resource_id,
        status,
        resource_non_vat_liable,
      } = this.purchaseOrder;

      this.resourceDefault = [
        {
          id: resource_id,
          name: resource,
          country: resource_country,
          default_currency: currency_code,
          non_vat_liable: resource_non_vat_liable,
        },
      ];

      this.items = items?.map(i => {
        return new EntityItem(i);
      });

      this.modifiers = price_modifiers?.map(m => {
        return new EntityPriceModifier(m);
      });
      this.sortModifiers();
      this.penalty = penalty;
      this.showPenalties = status === PurchaseOrderStatus.AUTHORISED;
      this.checkIfReadOnly();
    }
  }

  private getResolvedData(): void {
    const { purchaseOrder } = this.route.snapshot.data;

    this.purchaseOrder = purchaseOrder;
    this.project =
      this.route.parent.parent.snapshot.data.project ??
      this.route.parent.parent.parent.snapshot.data.project;
    this.currency = this.purchaseOrder?.currency_code;

    this.legalEntityCountry = this.purchaseOrder?.legal_country;
    this.taxRate = this.purchaseOrder?.tax_rate;
    this.resourceNonVatLiable = this.purchaseOrder?.resource_non_vat_liable;

    if (this.project?.purchase_order_project && !this.purchaseOrder) {
      this.resourceDefault = [
        {
          id: this.project.resource?.id,
          name: this.project.resource?.name,
          country: this.project.resource?.country,
          default_currency: this.project.resource?.currency,
          non_vat_liable: this.project.resource?.non_vat_liable,
        },
      ];
      this.resourceNonVatLiable = this.project.resource?.non_vat_liable;
    }
  }

  private readFile(file): Promise<{ file: string | ArrayBuffer } | boolean> {
    return new Promise(resolve => {
      const reader = new FileReader();
      reader.onload = (): void => {
        this.checkFileRestrictions(file).then(
          approved => {
            if (approved) {
              resolve({ file: reader.result });
            } else {
              resolve(false);
            }
          },
          () => {
            resolve(false);
          }
        );
      };

      try {
        reader.readAsDataURL(file);
      } catch (exception) {
        resolve(false);
      }
    });
  }

  private checkFileRestrictions(file: File): Promise<boolean> {
    const maxSize = 10 * 1024 * 1024;

    return new Promise(resolve => {
      if (file.size > maxSize) {
        this.toastrService.error(
          'Sorry, this file is too big. 10 Mb is the limit.',
          'Uploading error'
        );
        resolve(false);
      }
      if (file.type === 'application/pdf') {
        resolve(true);
      }
    });
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }
}
