import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
  ViewChild,
} from '@angular/core';
import { TablePreferencesService } from '../../../../../../shared/services/table-preferences.service';
import { ActivatedRoute } from '@angular/router';
import { finalize } from 'rxjs/operators';
import { DatatableContainerBase } from '../../../../../../shared/classes/datatable/datatable-container-base';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { ProjectService } from '../../project.service';
import { ToastrService } from 'ngx-toastr';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { GlobalService } from '../../../../../../core/services/global.service';
import { DatatableDetailConfig } from '../../../../../../shared/classes/datatable/datatable-detail-config';
import { ConfirmModalComponent } from '../../../../../../shared/components/confirm-modal/confirm-modal.component';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { DOCUMENT } from '@angular/common';
import { CommissionsSummary } from 'src/app/views/commissions/interfaces/commissions-summary';
import { AlertType } from 'src/app/shared/components/alert/alert.component';
import {
  CommissionsPaymentLogs,
  IndividualCommissionPayment,
  IndividualCommissionPaymentId,
  TotalOpenAmount,
} from 'src/app/views/commissions/interfaces/commissions-payment-log';
import { FilterOption } from 'src/app/views/dashboard/containers/analytics/analytics.component';
import { PayCommissionModalComponent } from 'src/app/views/commissions/components/pay-commission-modal/pay-commission-modal.component';
import { EditSalesCommissionModalComponent } from 'src/app/views/commissions/components/edit-sales-commission-modal/edit-sales-commission.component';
import { AddSalesCommissionModalComponent } from 'src/app/views/commissions/components/add-sales-commission-modal/add-sales-commission.component';
import { PayIndividualCommissionModalComponent } from 'src/app/views/commissions/components/pay-individual-commission-modal/pay-individual-commission-modal.component';
import { CompanySetting } from 'src/app/views/settings/interfaces/company-setting';
import { CommissionsService } from 'src/app/views/commissions/commissions.service';
import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  menuEnterAnimation,
  menuLeaveAnimation,
  alertEnterAnimation,
  alertLeaveAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { timer } from 'rxjs';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'oz-finance-project-commissions-summary',
  templateUrl: './project-commissions-summary.component.html',
  styleUrls: ['./project-commissions-summary.component.scss'],
  animations: [
    trigger('filterAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in', style({ opacity: 1 })),
      ]),
    ]),
    trigger('collapseAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in-out', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('250ms ease-in-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
    trigger('alertAnimation', [
      transition(':enter', useAnimation(alertEnterAnimation)),
      transition(':leave', useAnimation(alertLeaveAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class ProjectCommissionsSummaryComponent
  extends DatatableContainerBase
  implements OnInit
{
  @Input() isLoading = false;
  @Input() commissionSummary: CommissionsSummary;
  @Input() totalOpenAmount: TotalOpenAmount;
  @Input() paymentLogs: CommissionsPaymentLogs;
  @Input() responseMessage = '';
  @Input() errorsMessage: string;
  @Input() currency: number;
  @Input() userRole: number;

  @Output() filtersChanged = new EventEmitter<{
    formValue: any;
    filterOption: FilterOption;
    expandedQuotes: string[];
  }>();
  @Output() createPaymentLog = new EventEmitter<{
    amount: number;
    sales_person_id: string;
    responseMessage: string;
  }>();
  @Output() confirmPayment = new EventEmitter<{
    id: string;
    responseMessage: string;
  }>();

  @ViewChild('payCommissionModal', { static: false })
  public payCommissionModal: PayCommissionModalComponent;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('editSalesCommissionModal', { static: false })
  public editSalesCommissionModal: EditSalesCommissionModalComponent;
  @ViewChild('addSalesCommissionModal', { static: false })
  public addSalesCommissionModal: AddSalesCommissionModalComponent;
  @ViewChild('payIndividualCommissionModal', { static: false })
  public payIndividualCommissionModal: PayIndividualCommissionModalComponent;

  public commissionSettings: CompanySetting;
  public showMessage = false;
  public messageType: AlertType;
  public messageTitle: string;
  public messageDescription: string;

  project: any;
  buttonConfig = new DatatableButtonConfig({
    filters: false,
    columns: true,
    export: false,
    add: true,
    delete: false,
    refresh: true,
  });
  rowMenuConfig = new DatatableMenuConfig({
    clone: false,
    export: false,
    showMenu: false,
    edit: false,
  });
  public detailConfig: DatatableDetailConfig = new DatatableDetailConfig();

  protected table = 'project_commission';

  constructor(
    protected tablePreferencesService: TablePreferencesService,
    private projectService: ProjectService,
    protected route: ActivatedRoute,
    private toastrService: ToastrService,
    private globalService: GlobalService,
    protected appStateService: AppStateService,
    private commissionsService: CommissionsService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
  }

  public addSalesCommission(): void {
    this.addSalesCommissionModal.openModal().subscribe(result => {
      const { order_id, invoice_id, sales_person_id, ...data } = result;
      this.isLoading = true;
      this.commissionsService
        .createSalesCommissionPercentage(
          order_id,
          invoice_id,
          sales_person_id,
          data
        )
        .subscribe(
          () => {
            this.toastrService.success(null, 'Sales commission created');
            this.refreshTable();
          },
          err => {
            let msg =
              err?.message ??
              'Could not create sales commission. Try again or contact an administrator';
            if (err?.message?.sales_person_id) {
              msg = err?.message?.sales_person_id[0];
            }
            this.toastrService.error(msg, 'Error');
            this.refreshTable();
          }
        );
    });
  }

  public editSalesCommission({ row, detailRow }): void {
    this.editSalesCommissionModal
      .openModal(row, detailRow)
      .subscribe(result => {
        const { order_id, invoice_id, sales_person_id, ...data } = result;
        this.isLoading = true;
        this.commissionsService
          .updateSalesCommissionPercentage(
            order_id,
            invoice_id,
            sales_person_id,
            data
          )
          .subscribe(
            () => {
              this.toastrService.success(null, 'Sales commission updated');
              this.refreshTable();
            },
            err => {
              const msg =
                err?.message ??
                'Could not update sales commission. Try again or contact an administrator';
              this.toastrService.error(msg, 'Error');
              this.refreshTable();
            }
          );
      });
  }

  public payIndividualSalesComission({ row, detailRow }): void {
    this.payIndividualCommissionModal.openModal(row, detailRow).subscribe(
      result => {
        if (result) {
          this.isLoading = true;
          if (result.amount + detailRow.paid_value > detailRow.commission) {
            this.toastrService.error(
              null,
              'The paid value cannot be bigger than commission total value'
            );
            return;
          }
          this.createIndividualCommissionPayment({
            amount: result.amount,
            sales_person_id: row.sales_person_id,
            order_id: detailRow.order_id,
            invoice_id: detailRow.invoice_id,
            total: detailRow.commission,
          });
          this.refreshTable();
        }
      },
      err => {
        const msg =
          err?.message ??
          'Could not update sales commission. Try again or contact an administrator';
        this.toastrService.error(msg, 'Error');
        this.refreshTable();
      }
    );
  }

  public unpayIndividualSalesComission({ row, detailRow }): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to cancel the payment of this commission?'
      )
      .subscribe(confirm => {
        if (confirm) {
          this.isLoading = true;
          // setting commission_percentage to 0 means deleting the sales commission
          this.removeIndividualCommissionPayment({
            sales_person_id: row.sales_person_id,
            order_id: detailRow.order_id,
            invoice_id: detailRow.invoice_id,
          });
          this.refreshTable();
        }
      });
  }

  public deleteSalesCommission({ row, detailRow }): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to delete sales commission?')
      .subscribe(confirm => {
        if (confirm) {
          this.isLoading = true;
          // setting commission_percentage to 0 means deleting the sales commission
          this.commissionsService
            .deleteSalesCommissionPercentageById(
              detailRow.commission_percentage_id
            )
            .subscribe(
              () => {
                this.toastrService.success(null, 'Sales commission deleted');
                this.refreshTable();
              },
              err => {
                const msg =
                  err?.message ??
                  'Could not remove sales commission. Try again or contact an administrator';
                this.toastrService.error(msg, 'Error');
                this.refreshTable();
              }
            );
        }
      });
  }

  getData() {
    this.isLoading = true;
    this.projectService
      .getProject(this.project.id)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.project = response;
        this.rows = response.project_commissions.rows;
      });
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    const readOnlyMode = [
      UserRole.SALES_PERSON,
      UserRole.PROJECT_MANAGER,
      UserRole.OWNER_READ_ONLY,
      UserRole.PROJECT_MANAGER_RESTRICTED,
    ].includes(role);

    const isProjectManager = role === UserRole.PROJECT_MANAGER;
    const isProjectManagerRestricted =
      role === UserRole.PROJECT_MANAGER_RESTRICTED;
    const isCurrentProjectManager = this.projectService.isCurrentProjectManager(
      this.project
    );

    if (role === UserRole.ACCOUNTANT && this.project.exceeds_threshold) {
      this.showTresholdExceedAlert();
    }

    if (role === UserRole.ADMINISTRATOR) {
      this.rowMenuConfig.cancel = true;
    }

    if (role === UserRole.SALES_PERSON) {
      this.rowMenuConfig.showMenu = false;
    }
  }

  private getResolvedData() {
    this.project = this.route.snapshot.parent.data.project;
    this.rows = this.project.project_commissions.rows;
    this.preferences = this.route.snapshot.data.tablePreferences;
    const { settings, summary } = this.route.snapshot.data;
    this.commissionSettings = settings;
    this.commissionSummary = summary;
    this.currency = environment.currency;
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  public createIndividualCommissionPayment({
    amount,
    sales_person_id,
    order_id,
    invoice_id,
    total,
  }): void {
    const data: IndividualCommissionPayment = {
      amount: amount,
      sales_person_id: sales_person_id,
      order_id: order_id,
      invoice_id: invoice_id,
      total: total,
    };
    this.commissionsService
      .createIndividualCommissionPayment(data)
      .pipe(finalize(() => {}))
      .subscribe(
        response => {
          this.responseMessage = response.message;
          this.toastrService.success(null, this.responseMessage);
        },
        error => {
          this.errorsMessage = error.message;
          const msg =
            this.errorsMessage ??
            'Could not mark sales commission as paid. Try again or contact an administrator';
          this.toastrService.error(msg, 'Error');
        }
      );
  }

  public removeIndividualCommissionPayment({
    sales_person_id,
    order_id,
    invoice_id,
  }): void {
    const data: IndividualCommissionPaymentId = {
      sales_person_id: sales_person_id,
      order_id: order_id,
      invoice_id: invoice_id,
    };
    this.commissionsService
      .removeIndividualCommissionPayment(data)
      .pipe(finalize(() => {}))
      .subscribe(
        response => {
          this.responseMessage = response.message;
          this.toastrService.success(null, this.responseMessage);
        },
        error => {
          this.errorsMessage = error.message;
          const msg =
            this.errorsMessage ??
            'Could not mark sales commission as unpaid. Try again or contact an administrator';
          this.toastrService.error(msg, 'Error');
        }
      );
  }

  private refreshTable(): void {
    this.getData();
    this.isLoading = false;
  }

  private showTresholdExceedAlert(): void {
    this.messageTitle = 'Treshold Exceeded';
    this.messageType = AlertType.WARNING;
    this.messageDescription = 'Total Commission for this project exceeded 3%';
    this.showMessage = true;

    timer(5000).subscribe(() => {
      this.showMessage = false;
    });
  }
}
