import { Component, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { EntityItemContainerBase } from 'src/app/shared/classes/entity-item/entity-item-container-base';
import { EntityPriceModifier } from 'src/app/shared/classes/entity-item/entity-price-modifier';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Invoice } from 'src/app/shared/interfaces/entities';
import { LegalEntityChosen } from 'src/app/shared/interfaces/legal-entity-chosen';
import { PriceModifierCalculationLogicService } from 'src/app/shared/services/price-modifier-calculatation-logic.service';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { InvoiceStatus } from 'src/app/views/projects/modules/project/enums/invoice-status.enum';
import { InvoiceStatusUpdate } from 'src/app/views/projects/modules/project/interfaces/invoice-status-update';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { ProjectInvoiceService } from 'src/app/views/projects/modules/project/services/project-invoice.service';

@Component({
  selector: 'oz-finance-resource-invoice-form',
  templateUrl: './resource-invoice-form.component.html',
  styleUrls: ['./resource-invoice-form.component.scss'],
})
export class ResourceInvoiceFormComponent
  extends EntityItemContainerBase
  implements OnInit
{
  public resourceInvoice: Invoice;
  public resourceInvoiceForm: FormGroup;
  public invoiceStatusEnum = InvoiceStatus;
  public project: Project;
  public userRole: number;

  public isLoading = false;
  public legalEntityCountry: number;
  public statusChangeDisabled = false;
  public taxRate: number;

  private vatPercentage: number;

  public constructor(
    protected globalService: GlobalService,
    private fb: FormBuilder,
    private projectInvoiceService: ProjectInvoiceService,
    private route: ActivatedRoute,
    private legalEntitiesService: LegalEntitiesService,
    private toastrService: ToastrService,
    public priceModifierLogicService: PriceModifierCalculationLogicService
  ) {
    super(globalService, priceModifierLogicService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initResourceInvoiceForm();
    this.patchValueResourceInvoiceForm();
  }

  public resourceInvoiceStatusUpdated(updateData: InvoiceStatusUpdate): void {
    this.resourceInvoice.status = updateData.status;
    this.resourceInvoice.pay_date = updateData.pay_date;
    if (this.resourceInvoice.status > this.invoiceStatusEnum.DRAFT) {
      this.statusChangeDisabled = [
        UserRole.SALES_PERSON,
        UserRole.HUMAN_RESOURCES,
        UserRole.PROJECT_MANAGER,
      ].includes(this.userRole);
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

  public submit(): void {
    if (this.resourceInvoiceForm.valid && !this.isLoading) {
      this.isLoading = true;

      this.projectInvoiceService
        .editProjectInvoice(
          this.project.id,
          this.resourceInvoice?.id,
          this.resourceInvoiceForm.getRawValue()
        )
        .pipe(finalize(() => (this.isLoading = false)))
        .subscribe(
          response => {
            this.resourceInvoice = response;
            this.taxRate = response.tax_rate;
            this.resourceInvoiceForm.patchValue(response);
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
  }

  public statusColor(): string {
    let color: string;
    switch (this.resourceInvoice.status) {
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
    }

    return color;
  }

  public penaltyPercentage(): number {
    return this.resourceInvoice?.penalty;
  }

  public penaltyReason(): string {
    return this.resourceInvoice?.reason_of_penalty;
  }

  public get resourceIsNonVatLiable(): boolean {
    return this.resourceInvoice?.resource_not_vat_liable;
  }

  protected createItem(item: EntityItem): void {}

  protected updateItem(item: EntityItem): void {}

  protected orderItems(items: EntityItem[], index: number): void {}

  protected deleteItem(item: EntityItem): void {}

  protected createModifier(modifier: EntityPriceModifier): void {}

  protected updateModifier(modifier: EntityPriceModifier): void {}

  protected deleteModifier(modifier: EntityPriceModifier): void {}

  protected editEntityVat(percentage: number) {}

  protected changeEntityVatStatus(status: number): void {}

  protected editEntityDownPayment(percentage: number): void {}

  protected changeEntityDownPaymentStatus(status: number): void {}

  private getResolvedData(): void {
    this.resourceInvoice = this.route.snapshot.data.resourceInvoice;
    this.project =
      this.route.parent.parent.snapshot.data.project ??
      this.route.parent.parent.parent.snapshot.data.project;
    this.userRole = this.globalService.getUserRole();
    this.legalEntityCountry = this.resourceInvoice?.legal_country;
    this.taxRate = this.resourceInvoice?.tax_rate;

    const roles = [
      UserRole.SALES_PERSON,
      UserRole.HUMAN_RESOURCES,
      UserRole.OWNER_READ_ONLY,
    ];
    if (this.resourceInvoice.status > this.invoiceStatusEnum.DRAFT) {
      roles.push(UserRole.PROJECT_MANAGER, UserRole.PROJECT_MANAGER_RESTRICTED);
    }
    this.statusChangeDisabled = roles.includes(this.userRole);

    this.items = this.resourceInvoice.items?.map(i => {
      return new EntityItem(i);
    });

    if (this.project) {
      this.priceModifierLogicService.init(
        this.project.price_modifiers_calculation_logic
      );
    }

    this.modifiers = this.resourceInvoice.price_modifiers?.map(m => {
      return new EntityPriceModifier(m);
    });
    this.sortModifiers();
  }

  private initResourceInvoiceForm(): void {
    this.resourceInvoiceForm = this.fb.group({
      legal_entity_id: new FormControl(undefined, Validators.required),
      company: new FormControl(
        this.globalService.getCurrentCompanyObservable().value.name
      ),
      date: new FormControl(undefined, Validators.required),
      due_date: new FormControl(undefined, Validators.required),
      resource: new FormControl(undefined, Validators.required),
      status: new FormControl(0, Validators.required),
      reference: new FormControl(undefined, Validators.maxLength(50)),
      currency_code: new FormControl(undefined, Validators.required),
      manual_input: new FormControl(false),
    });

    if (
      [
        UserRole.SALES_PERSON,
        UserRole.HUMAN_RESOURCES,
        UserRole.PROJECT_MANAGER,
        UserRole.OWNER_READ_ONLY,
        UserRole.PROJECT_MANAGER_RESTRICTED,
      ].includes(this.userRole)
    ) {
      this.resourceInvoiceForm.disable();
    }
  }

  private patchValueResourceInvoiceForm(): void {
    if (this.resourceInvoice) {
      this.resourceInvoiceForm.patchValue(this.resourceInvoice);

      const isManualInput = this.resourceInvoiceForm.get('manual_input').value;

      if (isManualInput) {
        this.currency = this.resourceInvoiceForm.get('currency_code').value;
      }
    }
  }
}
