import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import { animate, style, transition, trigger } from '@angular/animations';
import {
  ActionType,
  PredictionType,
  StatusType,
  SummaryType,
} from '../../../views/dashboard/types/earnout-summary-type';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { GlobalService } from '../../../core/services/global.service';
import { ActivatedRoute, Router } from '@angular/router';
import { finalize, skip } from 'rxjs/operators';
import moment from 'moment';
import { UserRole } from '../../enums/user-role.enum';
import { Helpers } from '../../../core/classes/helpers';
import { ConfirmModalComponent } from '../confirm-modal/confirm-modal.component';
import { ToastrService } from 'ngx-toastr';
import { FilterOption } from '../../../views/dashboard/containers/analytics/analytics.component';
import { ExportFormat } from '../../enums/export.format';
import { DashboardService } from '../../../views/dashboard/dashboard.service';
import { DownloadService } from '../../services/download.service';

@Component({
  selector: 'oz-finance-earnout-sheet',
  templateUrl: './earnout-sheet.component.html',
  styleUrls: ['./earnout-sheet.component.scss'],
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
  ],
})
export class EarnoutSheetComponent implements OnInit, OnChanges, OnDestroy {
  @Input() summary: SummaryType;
  @Input() status: StatusType;
  @Input() isLoading = false;
  @Input() currencyCode = 'EUR';
  @Input() currency: number;
  @Input() userRole: number;
  @Input() actions: ActionType;
  @Input() errorsMessage = '';
  @Input() responseMessage = '';
  @Input() prediction: PredictionType;
  @Output() filtersChanged = new EventEmitter<{ formValue: any }>();
  @Output() summaryAction = new EventEmitter<{
    formValue: any;
    action: string;
  }>();
  @Output() predictionAsked = new EventEmitter();

  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;

  public dateFormat = 'dd/MM/yyyy';
  public numberFormat = '1.2-2';
  public ownerRole = UserRole.OWNER;
  public quarterOrYear = 'quarter';

  public blocksHandler = {
    orders: {
      base: false,
    },
    legacy_customers: {
      base: false,
    },
    salaries: false,
    rents: false,
    loans: {
      base: false,
    },
    po_without_order: {
      base: false,
    },
  };

  public summaryStatus = {
    not_set: 'Status: Waiting on approval',
    approved: 'Status: Bonuses approved by company owner',
    confirmed: 'Status: Bonuses paid',
    received: 'Status: Bonuses received',
  };

  public currentYear = moment.utc().year();
  public currentQuarter = moment.utc().quarter();
  public currentDate = moment().utc();

  public currentFilter: any;
  public quarters = [1, 2, 3, 4];
  public years = [];

  filters = [
    { key: 'quarter', value: 'Quarter' },
    { key: 'year', value: 'Year' },
  ];

  public filterForm: FormGroup;
  filterOption: FilterOption = 'quarter';

  public companyId: string;
  public quarterDataCanBeShownToOwner = false;
  public actionButton = '';
  public statusText = '';
  public showPrediction = false;

  private companySub: Subscription;

  constructor(
    private fb: FormBuilder,
    private globalService: GlobalService,
    private route: ActivatedRoute,
    private router: Router,
    private toastrService: ToastrService,
    private dashboardService: DashboardService,
    private downloadService: DownloadService
  ) {}

  public ngOnInit(): void {
    this.initFilterForm();
    this.initSummaryAction();

    this.companyId = this.globalService.currentCompany?.id;

    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(() => {
        if (this.globalService.currentCompany?.id !== 'all') {
          this.submit();
        } else {
          this.router.navigate(['/']).then();
          this.toastrService.warning(
            'You need to select a company!',
            'Warning'
          );
        }
      });

    this.route.queryParams.subscribe(f => (this.currentFilter = f));
  }

  public ngOnDestroy(): void {
    if (this.confirmModal && this.confirmModal.showConfirmModal === true) {
      this.confirmModal.closeModal(false);
    }

    this.companySub?.unsubscribe();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes && (changes.status || changes.summary)) {
      this.initSummaryAction();

      if (this.responseMessage) {
        this.toastrService.success(this.responseMessage, 'Success');
        this.responseMessage = '';
      }
    }
    if (changes && changes.errorsMessage && this.errorsMessage) {
      this.toastrService.warning(this.errorsMessage, 'Error');
      this.errorsMessage = '';
    }
  }

  public submit(): void {
    if (!this.isLoading && this.globalService.currentCompany?.id !== 'all') {
      this.filtersChanged.emit({ formValue: this.filterForm.getRawValue() });

      this.checkQuarterDate(this.filterForm.getRawValue());
      this.closeAllBlocks();
      this.showPrediction = false;
    }
  }

  public handleSummaryAction(): void {
    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to ' + this.actionButton + '?'
      )
      .subscribe(result => {
        if (result) {
          this.emitSummaryAction(this.actionButton);
        }
      });
  }

  public emitSummaryAction(action: string): void {
    if (!this.isLoading && this.globalService.currentCompany?.id !== 'all') {
      this.summaryAction.emit({
        formValue: this.filterForm.getRawValue(),
        action: action,
      });
    }
  }

  public resetFilters(): void {
    this.filterOption = 'quarter';
    this.filterForm.patchValue({ year: this.currentYear });
    this.filterForm.patchValue({ quarter: this.currentQuarter });

    this.submit();
  }

  public resetForm() {
    this.filterForm.controls.quarter.reset(undefined);
    if (this.filterOption === 'quarter') {
      this.quarterOrYear = 'quarter';
    } else {
      this.quarterOrYear = 'year';
    }
  }

  public export(): void {
    this.isLoading = true;
    this.dashboardService
      .exportEarnOut(this.filterForm.getRawValue())
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const file = new Blob([response.body], {
            type: response.headers.get('content-type'),
          });
          const fileName = response.headers
            .get('content-disposition')
            .split('filename=')[1]
            .split(';')[0];
          this.downloadService.createLinkForDownload(file, fileName);
        },
        error => {
          this.toastrService.error(error.error?.message, 'Download failed');
        }
      );
  }

  public getPrediction(): void {
    if (!this.isLoading && this.globalService.currentCompany?.id !== 'all') {
      this.predictionAsked.emit();
      this.showPrediction = true;
    }
  }

  public isAdmin() {
    return this.userRole === UserRole.ADMINISTRATOR;
  }

  private initFilterForm(): void {
    const { queryParams } = this.route.snapshot;

    this.currentFilter = queryParams;

    this.fillYears();
    this.filterForm = this.fb.group({
      year: new FormControl(queryParams.year),
      quarter: new FormControl(queryParams.quarter),
    });

    if (Object.keys(queryParams).length) {
      for (const [key, value] of Object.entries(queryParams)) {
        this.filterForm.patchValue({ [key]: value });
      }
    }

    this.checkQuarterDate(queryParams);
  }

  private initSummaryAction(): void {
    if (!this.status) {
      this.actionButton = this.quarterDataCanBeShownToOwner
        ? this.actions.approve
        : '';
    } else {
      if (
        this.status.approved &&
        this.isDate(this.status.approved) &&
        !this.status.confirmed &&
        (this.userRole === UserRole.ADMINISTRATOR ||
          this.userRole === UserRole.ACCOUNTANT)
      ) {
        this.actionButton = this.actions.confirm;
      } else if (
        this.status.confirmed &&
        this.isDate(this.status.confirmed) &&
        !this.status.received &&
        this.userRole === UserRole.OWNER
      ) {
        this.actionButton = this.actions.received;
      } else {
        this.actionButton = '';
      }
    }

    this.initSummaryStatusHeader();
  }

  // added this because, unlike action buttons, we have to show status text for all roles, not for concrete
  protected initSummaryStatusHeader(): void {
    if (!this.status) {
      this.statusText = this.summaryStatus.not_set;
    } else if (this.isDate(this.status.approved) && !this.status.confirmed) {
      this.statusText = this.summaryStatus.approved;
    } else if (this.isDate(this.status.confirmed) && !this.status.received) {
      this.statusText = this.summaryStatus.confirmed;
    } else if (this.isDate(this.status.received)) {
      this.statusText = this.summaryStatus.received;
    }
  }

  private checkQuarterDate(queryParams): void {
    const latestValidQuarterDate = moment()
      .quarter(this.currentQuarter - 1)
      .endOf('quarter')
      .add(19, 'd');

    const queryParamsQuarterDate = moment(queryParams.year)
      .quarter(Number(queryParams.quarter))
      .endOf('quarter')
      .add(19, 'd');

    this.quarterDataCanBeShownToOwner =
      latestValidQuarterDate >= queryParamsQuarterDate &&
      this.currentDate >= queryParamsQuarterDate &&
      this.userRole === UserRole.OWNER;
  }

  private fillYears(): void {
    this.years = [];
    for (let i = this.currentYear; i >= Number(this.currentYear) - 2; i--) {
      this.years.push(i.toString());
    }
  }

  public isDate(val: string): boolean {
    return Helpers.isDate(val);
  }

  protected closeAllBlocks(): void {
    this.blocksHandler.orders.base = false;
    this.blocksHandler.salaries = false;
    this.blocksHandler.rents = false;
    this.blocksHandler.loans.base = false;
    this.blocksHandler.legacy_customers.base = false;
    this.blocksHandler.po_without_order.base = false;
  }
}
