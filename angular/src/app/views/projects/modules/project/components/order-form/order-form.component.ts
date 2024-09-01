import { HttpParams } from '@angular/common/http';
import {
  Component,
  ElementRef,
  Inject,
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
  skip,
  switchMap,
  takeUntil,
  tap,
} from 'rxjs/operators';
import { Helpers, roundToTwo } from 'src/app/core/classes/helpers';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { EntityItemContainerBase } from 'src/app/shared/classes/entity-item/entity-item-container-base';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Order } from 'src/app/shared/interfaces/entities';
import { SearchEntity } from 'src/app/shared/interfaces/search-entity';
import { SharedProjectEntityService } from 'src/app/shared/services/shared-entity.service';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { OrderStatus } from 'src/app/views/projects/modules/project/enums/order-status.enum';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectService } from 'src/app/views/projects/modules/project/project.service';
import { ProjectOrderService } from 'src/app/views/projects/modules/project/services/project-order.service';
import { CurrencyPipe, DOCUMENT } from '@angular/common';
import { QuoteStatus } from '../../enums/quote-status.enum';
import { VatStatus } from '../../../../../../shared/enums/vat-status.enum';
import {
  FileRestrictions,
  UploadService,
} from '../../../../../../shared/services/upload.service';
import { SharedService } from '../../../../../../shared/services/shared.service';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { PriceModifierCalculationLogicService } from 'src/app/shared/services/price-modifier-calculatation-logic.service';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { TablePreferenceType } from 'src/app/shared/enums/table-preference-type.enum';
import { ErrorHandlerService } from 'src/app/core/services/error-handler.service';

@Component({
  selector: 'oz-finance-order-form',
  templateUrl: './order-form.component.html',
  styleUrls: ['./order-form.component.scss'],
})
export class OrderFormComponent
  extends EntityItemContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('upload_file', { static: false }) public upload_file: ElementRef;

  public readOnly = false;
  public isLoading = false;
  public isProjectManagerLoading = false;
  public project: Project;
  public orderForm: FormGroup;
  public order: Order;
  public orderStatusEnum = OrderStatus;
  public userRoleEnum = UserRole;
  public showOrderActionBtns = false;
  public taxRate: number;
  public showDownloadBtn = false;
  public showExportBtn = false;

  public projectManagerSelect: Observable<SearchEntity[]>;
  public projectManagerInput: Subject<string> = new Subject<string>();
  public templates: TemplateModel[] = [];
  public entity: number = TablePreferenceType.PROJECT_ORDERS;

  private onDestroy$: Subject<void> = new Subject<void>();
  private projectManagerDefault: SearchEntity[] = [];
  private vatStatus: number;
  private vatPercentage: number;
  private template_id: string;
  private sharingOrderAllowed = false;
  private allowedFormats: ExportFormat[] = [
    ExportFormat.PDF,
    ExportFormat.DOCX,
  ];

  public constructor(
    @Inject(DOCUMENT) private document,
    protected globalService: GlobalService,
    private fb: FormBuilder,
    private projectOrderService: ProjectOrderService,
    private route: ActivatedRoute,
    private toastrService: ToastrService,
    private router: Router,
    private enumService: EnumService,
    private suggestService: SuggestService,
    private projectService: ProjectService,
    private sharedProjectEntityService: SharedProjectEntityService,
    private currencyPipe: CurrencyPipe,
    private sharedService: SharedService,
    private uploadService: UploadService,
    private errorHandlerService: ErrorHandlerService,
    public priceModifierLogicService: PriceModifierCalculationLogicService
  ) {
    super(globalService, priceModifierLogicService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initOrderForm();
    this.patchOrderForm();
    this.setOrderValues();

    this.initProjectManagerTypeAhead();
    this.subscribeToCompanyChange();
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
        this.projectOrderService.exportProjectOrderCallback,
        [this.project.id, this.order.id, this.template_id],
        'Order: ' + this.order.number,
        this.allowedFormats,
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

  public export(): void {
    this.template_id = this.templates[0]['id'];
    this.isLoading = true;
    this.projectOrderService
      .exportProjectOrderReport(this.project.id, this.order.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(file);
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }

  createLinkForDownloading(file) {
    const link = this.document.createElement('a');
    this.document.body.appendChild(link);
    link.setAttribute('href', URL.createObjectURL(file));
    link.setAttribute(
      'download',
      'order_report_' + this.getCurrentDate() + '.xlsx'
    );
    link.click();
    this.document.body.removeChild(link);
  }

  getCurrentDate(): string {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return year + '_' + month + '_' + day;
  }

  public submit(): void {
    if (this.orderForm.valid && !this.isLoading) {
      const value = this.orderForm.getRawValue();
      value.vat_status = this.vatStatus;
      value.vat_percentage = this.vatPercentage;

      this.order
        ? this.editOrder(this.order.id, value)
        : this.createOrder(value);
    }
  }

  public changeStatus(status: OrderStatus): void {
    const statusLabel = this.enumService.getEnumMap('orderstatus').get(status);
    const message = `Are you sure want to change status to ${statusLabel}? This cannot be undone.`;

    if (status === OrderStatus.DELIVERED) {
      this.processStatusToDelivered(statusLabel, status);
    } else {
      this.openConfirmModal(message, status);
    }
  }

  public get customerIsNonVatLiable(): boolean {
    return this.project?.customer?.non_vat_liable ?? false;
  }

  protected openConfirmModal(
    message: string,
    status: number,
    showContinueBtn = false
  ): void {
    this.confirmModal
      .openModal('Confirm', message, showContinueBtn)
      .subscribe(result => {
        if (result) {
          if (result === 'continue') {
            this.changeOrderStatus(status, false);
          } else {
            status === OrderStatus.ACTIVE
              ? this.changeOrderStatus(
                  status,
                  true,
                  this.orderForm.getRawValue().date
                )
              : this.changeOrderStatus(status, true);
          }
        }
      });
  }

  protected processStatusToDelivered(
    statusLabel: string,
    status: number
  ): void {
    if (
      this.project?.employees.rows.count === 0 &&
      this.project?.purchase_orders.rows.count === 0
    ) {
      this.confirmModal
        .openModal(
          'Confirm',
          `There are currently no employees or purchase orders assigned to this order. Are you sure you want to change the status to ${statusLabel}?`
        )
        .subscribe(result => {
          if (result) {
            setTimeout(() => {
              this.handleDeliveredStatusWorkFlow(statusLabel, status);
            }, 1000);
          }
        });
    } else {
      this.handleDeliveredStatusWorkFlow(statusLabel, status);
    }
  }

  public handleDeliveredStatusWorkFlow(
    statusLabel: string,
    status: number
  ): void {
    const currencyCode = this.enumService
      .getEnumMap('currencycode')
      .get(this.costCurrency);

    const orderTotal = roundToTwo(this.order?.price_user_currency);
    const invoicesTotal = roundToTwo(this.order?.total_invoices_price);

    const orderPrice = this.currencyPipe.transform(orderTotal, currencyCode);
    const invoicesPrice = this.currencyPipe.transform(
      invoicesTotal,
      currencyCode
    );
    const substrOrderInvoicePrice = this.currencyPipe.transform(
      orderTotal - invoicesTotal,
      currencyCode
    );

    let message = '';
    let showContinueBtn = false;
    const messages = {
      greaterThan: `The total invoiced amount is ${invoicesPrice}, while the value of the order is only ${orderPrice}. Are you sure you want to change status to ${statusLabel}? This cannot be undone.`,
      equalTo: `Are you sure you want to change status to ${statusLabel}? This cannot be undone.`,
      noInvoices: `There are currently no invoices assigned to this order. An invoice will automatically be created. Are you sure you want to change status to ${statusLabel}?`,
      lowerThan: `The total invoiced amount is ${invoicesPrice}, while the value of the order is ${orderPrice}. ${substrOrderInvoicePrice} should still be invoiced. Do you want to create an invoice now?`,
    };

    if (invoicesTotal === 0) {
      message = messages.noInvoices;
    } else {
      if (invoicesTotal === orderTotal) {
        message = messages.equalTo;
      }

      if (invoicesTotal > orderTotal) {
        message = messages.greaterThan;
        showContinueBtn = true;
      }

      if (invoicesTotal < orderTotal) {
        message = messages.lowerThan;
        showContinueBtn = true;
      }
    }

    this.openConfirmModal(message, status, showContinueBtn);
  }

  public changeCurrency(): void {
    if (this.orderForm.get('manual_input').value) {
      this.currency = this.orderForm.get('currency_code').value;
    } else {
      this.currency = this.getDefaultCurrency();
    }
  }

  public toggleManualInput(master: boolean): void {
    if (this.readOnly || master || this.order?.shadow) {
      return;
    }

    this.toggleOrderManualInput();

    if (this.items.length) {
      this.confirmModal
        .openModal(
          'Confirm',
          'Are you sure you want to proceed? All existed items will be deleted and you will have to add them again.'
        )
        .subscribe(result => {
          if (result) {
            const itemIds = this.items.map(i => i.id);
            this.order
              ? this.deleteOrderItems(itemIds)
              : this.removeItemsFromList(itemIds, true);
          } else {
            this.toggleOrderManualInput();
          }
        });
    }
  }

  /**
   * Toggle order shating state
   * @returns
   */
  public toggleShareOrder(): void {
    if (!this.isOrderCanBeToggle()) {
      return;
    }

    const isMaster = !this.orderForm.get('master').value;

    this.orderForm.get('master').patchValue(isMaster);

    this.saveOrderSharingState(isMaster);
  }

  /**
   * Request authorisation for current order sharing
   * @returns
   */
  private refreshSharingPermissions(): void {
    if (!this.readOnly && this.order?.project_id && this.order?.id) {
      this.sharedProjectEntityService
        .checkSharingPermissions(this.order.project_id, this.order.id)
        .subscribe(
          status => {
            this.sharingOrderAllowed = status;
          },
          error => this.errorHandlerService.handle(error)
        );
    }
  }

  /**
   * Process activation or deactivation of order sharing
   * @param isMaster
   * @returns
   */
  private saveOrderSharingState(isMaster: boolean): void {
    this.sharedProjectEntityService
      .shareOrder(this.order.project_id, this.order.id, isMaster)
      .subscribe(
        status => {
          if (status) {
            if (isMaster) {
              this.toastrService.success(
                'Order sharing has been successfully activated',
                'Success'
              );
            } else {
              this.toastrService.success(
                'Order sharing has been successfully deactivated',
                'Success'
              );
            }
          } else {
            this.toastrService.success(
              'Activation or deactivation of order sharing failed',
              'Error'
            );
          }
          this.sharingOrderAllowed = status;
        },
        error => this.errorHandlerService.handle(error)
      );
  }

  /**
   * Check of order sharing state can be updated
   * @returns {boolean}
   */
  public isOrderCanBeToggle(): boolean {
    return !this.readOnly && !this.order.shadow && this.sharingOrderAllowed;
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
            .deleteDocument(this.order.id, this.order.project_id, 'orders')
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
                this.order.has_media = null;
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
      !!this.order.has_media &&
      this.globalService.getUserRole() !== UserRole.SALES_PERSON &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY
    );
  }

  public showUploadBtn(): boolean {
    return (
      this.globalService.getUserRole() !== UserRole.SALES_PERSON &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY
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
          this.order.id,
          this.order.project_id,
          uploaded,
          'orders'
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
            this.order.has_media = 'document set';
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
    switch (this.order.status) {
      case OrderStatus.DRAFT:
        color = 'draft-status';
        break;
      case OrderStatus.ACTIVE:
        color = 'active-status';
        break;
      case OrderStatus.INVOICED:
        color = 'invoiced-status';
        break;
      case OrderStatus.CANCELED:
        color = 'cancelled-status';
        break;
    }

    return color;
  }

  public showDraftButton(): boolean {
    const superUser = this.globalService.userDetails?.super_user;

    return superUser && this.order?.status !== this.orderStatusEnum.DRAFT;
  }

  protected createItem(item: EntityItem): void {
    const orderNeedsUpdate =
      !this.order.manual_input && this.orderForm.get('manual_input').value;

    if (orderNeedsUpdate) {
      this.projectOrderService
        .editProjectOrder(
          this.project.id,
          this.order.id,
          this.orderForm.getRawValue()
        )
        .subscribe(() => this.createOrderItem(item));
    } else {
      this.createOrderItem(item);
    }

    this.checkDefaultModifierForCreation();
  }

  protected updateItem(item: EntityItem): void {
    this.isLoading = true;

    this.projectOrderService
      .editOrderItem(this.project.id, this.order.id, item, item.id)
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
    this.deleteOrderItems([item.id]);
  }

  protected createModifier(modifier: EntityPriceModifier): void {
    this.isLoading = true;

    this.projectOrderService
      .addOrderModifier(this.project.id, this.order.id, modifier)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.order.price_modifiers.push(response);
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

    this.projectOrderService
      .editOrderModifier(this.project.id, this.order.id, modifier, modifier.id)
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

    this.projectOrderService
      .deleteOrderModifier(this.project.id, this.order.id, modifier.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.removePriceModifier(modifier.id);
          this.order.price_modifiers = [...this.modifiers];
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

    this.projectOrderService
      .editOrderItem(
        this.project.id,
        this.order.id,
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

    if (this.order) {
      this.order.vat_percentage = percentage;
      this.editOrder(this.order.id, this.order);
    }
  }

  protected changeEntityVatStatus(status: number): void {
    this.vatStatus = status;

    if (status === VatStatus.NEVER) {
      this.vatPercentage = null;
    }

    if (this.order) {
      this.order.vat_status = status;

      if (status === VatStatus.NEVER) {
        this.order.vat_percentage = null;
      }

      this.editOrder(this.order.id, this.order);
    }
  }

  protected editEntityDownPayment(percentage: number): void {}

  protected changeEntityDownPaymentStatus(status: number): void {}

  private initProjectManagerTypeAhead(): void {
    this.projectManagerSelect = concat(
      of(this.projectManagerDefault), // default items
      this.projectManagerInput.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          this.isProjectManagerLoading = true;
        }),
        switchMap(term =>
          this.suggestService.suggestProjectManagers(term).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              this.isProjectManagerLoading = false;
            })
          )
        )
      )
    );
  }

  private initOrderForm(): void {
    this.orderForm = this.fb.group({
      legal_entity_id: new FormControl(undefined, Validators.required),
      customer: new FormControl(this.project.customer.name),
      company: new FormControl(
        this.globalService.getCurrentCompanyObservable().value.name
      ),
      date: new FormControl(undefined, Validators.required),
      deadline: new FormControl(undefined, Validators.required),
      project_manager_id: new FormControl(undefined, Validators.required),
      status: new FormControl(0, Validators.required),
      reference: new FormControl(undefined, Validators.maxLength(50)),
      currency_code: new FormControl(undefined),
      manual_input: new FormControl(false),
      master: new FormControl(false),
    });
  }

  private patchOrderForm(): void {
    if (this.order) {
      this.orderForm.patchValue(this.order);
    }
    if (this.order.shadow) {
      this.orderForm.controls.master.patchValue(true);
    }
  }

  private createOrder(order): void {
    this.isLoading = true;

    this.projectOrderService
      .createProjectOrder(this.project.id, order)
      .pipe(
        finalize(() => {
          this.isLoading = false;
          this.refreshSharingPermissions();
        })
      )
      .subscribe(
        response => {
          this.createOrderItems(response);
          this.project.order = order;
          this.projectService.currentProject = this.project;
          this.toastrService.success(
            'Order created successfully',
            'Creation successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Creation failed');
        }
      );
  }

  private createOrderItem(item: EntityItem): void {
    this.isLoading = true;

    this.projectOrderService
      .addOrderItem(this.project.id, this.order.id, item)
      .pipe(
        finalize(() => {
          this.isLoading = false;
          this.refreshSharingPermissions();
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

  private createOrderItems(order: Order): void {
    const itemRequests = this.items.map(item => {
      return this.projectOrderService
        .addOrderItem(this.project.id, order.id, item)
        .pipe(catchError(err => of({ error: err })));
    });

    const modifierRequests = this.modifiers.map(modifier => {
      return this.projectOrderService
        .addOrderModifier(this.project.id, order.id, modifier)
        .pipe(catchError(err => of({ error: err })));
    });

    const requests = itemRequests.concat(modifierRequests);

    if (!requests.length) {
      this.router
        .navigate([`../${order.id}/edit`], { relativeTo: this.route })
        .then();
      return;
    }

    forkJoin(requests)
      .pipe(
        finalize(() => {
          this.isLoading = false;
          this.refreshSharingPermissions();
        })
      )
      .subscribe(responses => {
        const errors = responses.filter(r => r.error);

        if (errors.length === 0) {
          this.toastrService.success(
            'Order created successfully',
            'Creation successful'
          );
        } else {
          this.toastrService.warning(
            'Order was created but some items could not be created',
            'Creation partial'
          );
        }

        this.router
          .navigate([`../${order.id}/edit`], { relativeTo: this.route })
          .then();
      });
  }

  private deleteOrderItems(ids: string[]): void {
    this.isLoading = true;

    this.sharedProjectEntityService
      .deleteEntityItems(
        this.project.id,
        ProjectEntityEnum.ORDERS,
        this.order.id,
        ids
      )
      .pipe(
        finalize(() => {
          this.isLoading = false;
          this.refreshSharingPermissions();
        })
      )
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

  private toggleOrderManualInput(): void {
    this.orderForm
      .get('manual_input')
      .patchValue(!this.orderForm.get('manual_input').value);
  }

  private deleteEntityModifiers(): void {
    const deleteModifiersRequests = this.modifiers.map(m => {
      return this.projectOrderService.deleteOrderModifier(
        this.project.id,
        this.order.id,
        m.id
      );
    });

    forkJoin(deleteModifiersRequests)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.removePriceModifiers();
        this.order.price_modifiers = [];
      });
  }

  private checkDefaultModifierForCreation(): void {
    if (this.entityModifiersShouldBeCreated(this.order)) {
      this.createModifier(this.modifiers[0]);
    }
  }

  private changeOrderStatus(
    status: OrderStatus,
    needInvoice: boolean,
    orderDate?: string
  ): void {
    this.projectOrderService
      .changeProjectOrderStatus(
        this.project.id,
        this.order.id,
        status,
        needInvoice,
        orderDate
      )
      .subscribe(
        response => {
          this.order = response;
          this.project.order = response;
          this.projectService.currentProject = this.project;
          this.patchOrderForm();
          this.readOnly = this.order.status > OrderStatus.ACTIVE;

          this.toastrService.success(
            'Order updated successfully',
            'Update successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  private editOrder(orderID: string, order: Order): void {
    this.isLoading = true;

    this.projectOrderService
      .editProjectOrder(this.project.id, orderID, order)
      .pipe(
        finalize(() => {
          this.isLoading = false;
          this.refreshSharingPermissions();
        })
      )
      .subscribe(
        response => {
          this.order = response;
          this.project.order = response;
          this.projectService.currentProject = this.project;
          this.patchOrderForm();
          this.toastrService.success(
            'Order updated successfully',
            'Update successful'
          );
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  public showCancelBtn(): boolean {
    const role = this.globalService.getUserRole();
    const status = this.order?.status;
    const shadow = this.order?.shadow;

    return (
      (role === UserRole.ADMINISTRATOR || role === UserRole.OWNER) &&
      status !== this.orderStatusEnum.CANCELED &&
      !shadow
    );
  }

  public isNotShadow(): boolean {
    return !this.order?.shadow;
  }

  private subscribeToCompanyChange(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate([`/${value.id}/orders`]).then();
        }
      });
  }

  private setOrderValues(): void {
    if (this.order) {
      this.updateEnabled = true;

      if (this.order.manual_input) {
        this.currency = this.order.currency_code;
      }

      this.items = this.order.items?.map(i => {
        return new EntityItem(i);
      });

      this.modifiers = this.order.price_modifiers?.map(m => {
        return new EntityPriceModifier(m);
      });
      this.sortModifiers();
      this.projectManagerDefault = [
        { id: this.order.project_manager_id, name: this.order.project_manager },
      ];
    }
  }

  private getResolvedData(): void {
    this.order = this.route.snapshot.data.order;
    this.project =
      this.route.parent.parent.snapshot.data.project ??
      this.route.parent.parent.parent.snapshot.data.project;
    this.taxRate = this.order
      ? this.order.tax_rate
      : this.globalService.currentCompanyTaxRate;

    const userRole = this.globalService.getUserRole();
    this.readOnly =
      userRole === this.userRoleEnum.SALES_PERSON ||
      this.order.status > OrderStatus.ACTIVE ||
      userRole === this.userRoleEnum.OWNER_READ_ONLY;
    this.showOrderActionBtns =
      this.order &&
      userRole !== this.userRoleEnum.SALES_PERSON &&
      userRole !== this.userRoleEnum.OWNER_READ_ONLY;
    this.showDownloadBtn =
      this.order && userRole !== this.userRoleEnum.SALES_PERSON;
    this.showExportBtn = this.showDownloadBtn;
    if (this.project) {
      this.priceModifierLogicService.init(
        this.project.price_modifiers_calculation_logic
      );
    }
    this.sharingOrderAllowed = !this.order || this.order.total_shadows == 0;
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }

  get showCost(): boolean {
    return this.order?.cost !== null && this.order?.cost !== undefined;
  }

  get showMarkup(): boolean {
    return this.order?.markup !== null && this.order?.markup !== undefined;
  }

  get showPotentialMarkup(): boolean {
    return (
      this.order?.potential_markup !== null &&
      this.order?.potential_markup !== undefined
    );
  }

  get canExportCosts() {
    const userRole = this.globalService.getUserRole();
    return [
      UserRole.ADMINISTRATOR,
      UserRole.OWNER,
      UserRole.SUPER_ADMIN,
      UserRole.ACCOUNTANT,
    ].includes(userRole);
  }
}
