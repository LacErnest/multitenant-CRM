import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import { getCurrencySymbol } from '@angular/common';
import { HttpParams } from '@angular/common/http';
import {
  Component,
  ElementRef,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { Router } from '@angular/router';
import { SelectionType, SortType } from '@swimlane/ngx-datatable';
import { Moment } from 'moment';
import { ToastrService } from 'ngx-toastr';
import { concat, Observable, of, Subject, timer } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  map,
  switchMap,
  takeUntil,
  tap,
} from 'rxjs/operators';
import { Helpers } from 'src/app/core/classes/helpers';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import {
  alertEnterAnimation,
  alertLeaveAnimation,
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  menuEnterAnimation,
  menuLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableDetailConfig } from 'src/app/shared/classes/datatable/datatable-detail-config';
import {
  DatatableMenuConfig,
  DatatableMenuStyle,
} from 'src/app/shared/classes/datatable/datatable-menu-config';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { DestinationModalComponent } from 'src/app/shared/components/destination-modal/destination-modal.component';
import { floatRegEx, numberOnlyRegEx } from 'src/app/shared/constants/regex';
import { DateFormat } from 'src/app/shared/enums/date.format';
import { FilterType } from 'src/app/shared/enums/filter-type.enum';
import { LegalEntity } from 'src/app/shared/interfaces/legal-entity';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import {
  Column,
  Filter,
  Sort,
} from 'src/app/shared/interfaces/table-preferences';
import { MenuOption } from 'src/app/shared/interfaces/table-menu-option';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { DatatableActionField } from 'src/app/shared/types/datatable-action-field';
import { QuoteStatus } from '../../../views/projects/modules/project/enums/quote-status.enum';
import { greaterThanValidator } from 'src/app/shared/validators/greater-than.validator';
import * as Papa from 'papaparse';
import { PurchaseOrderStatus } from 'src/app/shared/enums/purchase-order-status.enum';
import { InvoiceStatus } from '../../../views/projects/modules/project/enums/invoice-status.enum';
import { environment } from 'src/environments/environment';
import {
  DateFilterValue,
  DecimalFilterValue,
  IntegerFilterValue,
  UuidFilterValue,
  PercentageFilterValue,
} from 'src/app/shared/types/filter-value-types';
import { OrderStatus } from '../../../views/projects/modules/project/enums/order-status.enum';
import { ResourceStatus } from '../../../views/resources/enums/resource-status.enum';
import { UserRole } from '../../enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';
import { AlertType } from '../alert/alert.component';

@Component({
  selector: 'oz-finance-datatable',
  templateUrl: './datatable.component.html',
  styleUrls: ['./datatable.component.scss'],
  animations: [
    trigger('slideOverAnimation', [
      transition(':enter', [
        style({ transform: 'translateX(100%)' }),
        animate('500ms ease-in-out', style({ transform: 'translateX(0)' })),
      ]),
      transition(':leave', [
        style({ transform: 'translateX(0)' }),
        animate('500ms ease-in-out', style({ transform: 'translateX(100%)' })),
      ]),
    ]),
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
    trigger('detailAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
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
export class DatatableComponent implements OnInit, OnDestroy, OnChanges {
  @Input() public title: string;
  @Input() public currency: number;
  @Input() public isLoading = false;

  @Input() public showMessage = false;
  @Input() public messageType: AlertType;
  @Input() public messageTitle: string;
  @Input() public messageDescription: string;

  @Input() public isFilterable = true;
  @Input() public isColumnCustomizable = true;

  @Input() public allColumns: Column[] = [];
  @Input() public columns: Column[] = [];
  @Input() public defaultColumns: Column[] = [];
  @Input() public rows: any[] = [];

  @Input() public filters: Filter[] = [];
  @Input() public defaultFilters: Filter[] = [];
  @Input() public sorts: Sort[];
  @Input() public page = 0;
  @Input() public pageSize = 10;
  @Input() public count = 0;

  @Input() public disablePaging = false;
  @Input() public project: Project;

  @Input() public sortable = true;
  @Input() public showDetailRow = false;
  @Input() public buttonConfig: DatatableButtonConfig =
    new DatatableButtonConfig();
  @Input() public rowMenuConfig: DatatableMenuConfig =
    new DatatableMenuConfig();
  @Input() public detailConfig: DatatableDetailConfig =
    new DatatableDetailConfig();
  @Input() public datatableType: string = null;
  @Input() public selectionType: SelectionType = SelectionType.multi;
  @Input() public projectEntity: ProjectEntityEnum;

  @Input() public currencyOverride: number;
  @Input() public minTableHeight = false;
  @Input() public reorderable = true;
  @Input() public isIndividualEdit = false;
  @Input() public isIndividualDeletion = false;
  @Input() public isIndividualDownload = false;
  @Input() public isExternalAccess = false;
  @Input() public isCompanyLegal = false;
  @Input() public isAssignEmployee = false;
  @Input() public isAssignCommission = false;
  @Input() public menuOptions: MenuOption[] = [];
  @Input() public displayHeader = true;
  @Input() public className = '';

  @Output() public columnsUpdated = new EventEmitter<any>();
  @Output() public sortsUpdated = new EventEmitter<any>();
  @Output() public columnsAndSortsUpdated = new EventEmitter<{
    columns: any[];
    sorts: any[];
  }>();
  @Output() public filtersUpdated = new EventEmitter<any>();
  @Output() public pageUpdated = new EventEmitter<number>();
  @Output() public selectionDeleted = new EventEmitter<any>();
  @Output() public selectionTemplateClicked = new EventEmitter<any>();
  @Output() public addClicked = new EventEmitter<any>();
  @Output() public editClicked = new EventEmitter<any>();
  @Output() public cloneClicked = new EventEmitter<any>();
  @Output() public downloadClicked = new EventEmitter<any>();
  @Output() public cancelClicked = new EventEmitter<any>();
  @Output() public downloadDetailClicked = new EventEmitter<any>();
  @Output() public invoiceClicked = new EventEmitter<string>();
  @Output() public orderClicked = new EventEmitter<{
    id: string;
    date: string;
  }>();
  @Output() public redirectClicked = new EventEmitter<any>();
  @Output() public fileSuccessfullyUploaded = new EventEmitter<any>();
  @Output() public invoiceUploaded = new EventEmitter<any>();
  @Output() public viewDetailPageClicked = new EventEmitter<any>();
  @Output() public markAsDefaultClicked = new EventEmitter<LegalEntity>();
  @Output() public exportClicked = new EventEmitter<any>();
  @Output() public markAsLocalClicked = new EventEmitter<LegalEntity>();
  @Output() public addHours = new EventEmitter<any>();
  @Output() public editHours = new EventEmitter<{
    row: string;
    detailRow: string;
  }>();
  @Output() public deleteHours = new EventEmitter<{
    row: string;
    detailRow: string;
  }>();
  @Output() public addCommissions = new EventEmitter<any>();
  @Output() public editCommissions = new EventEmitter<{
    row: string;
    detailRow: string;
  }>();
  @Output() public payCommissions = new EventEmitter<{
    row: string;
    detailRow: string;
  }>();
  @Output() public unPayCommissions = new EventEmitter<{
    row: string;
    detailRow: string;
  }>();
  @Output() public deleteCommissions = new EventEmitter<{
    row: string;
    detailRow: string;
  }>();
  @Output() public refreshClicked = new EventEmitter<any>();

  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @ViewChild('destinationModal', { static: false })
  public destinationModal: DestinationModalComponent;
  @ViewChild('table', { static: false }) public table: any;
  @ViewChild('actionRow', { static: false })
  public actionRow: ElementRef<HTMLElement>;

  // TODO: refactor properties and methods
  public dateFormats = DateFormat;
  public currencyPrefix = '';

  public dateModels = new Map<string, Moment[]>();
  public typeAheads = new Map<
    string,
    {
      input: Subject<any>;
      select: Observable<any>;
      loading: boolean;
      default?: any;
    }
  >();

  public sortType: SortType = SortType.multi;
  public selection: any[] = [];

  public showFiltersSlideOver = false;
  public showColumnsSlideOver = false;
  public showColumnsError = false;

  public filtersForm: FormGroup;
  public columnsForm: FormGroup;

  public hiddenColumns: Column[] = [];

  public invisibleFilterColumns: Column[] = [];

  public messages = {
    emptyMessage: `
      <img src="/assets/no_data.svg">
      <span class="text-xl">No results</span>
    `,
  };

  private eventDebouncer = new Subject<any>();
  private onDestroy$: Subject<void> = new Subject<void>();
  lastDataTablePage$: Observable<number>;

  public constructor(
    private globalService: GlobalService,
    private enumService: EnumService,
    private fb: FormBuilder,
    private toastService: ToastrService,
    private suggestService: SuggestService,
    private routingService: RoutingService,
    private router: Router,
    private appStateService: AppStateService
  ) {
    this.eventDebouncer
      .pipe(debounceTime(800), takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.sortsUpdated.emit(value);
      });
  }

  public ngOnInit(): void {
    this.buildColumnsForm(this.columns);
    this.buildHiddedColumns();
    this.buildInvisibleFilteringColumns();
    this.buildFiltersForm(this.filters);

    this.setCurrencyAndPrefix();
    if (typeof this.project !== 'undefined') {
      this.filtersUpdated.emit([]);
    }
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes.columns || changes.allColumns) {
      this.buildHiddedColumns();
      this.buildInvisibleFilteringColumns();
      this.buildColumnsForm(this.columns);
      this.buildFiltersForm(this._filterable_columns);
      this.checkIfShowDetailsRow();
    }
    if (changes.filters) {
      this.buildFiltersForm(this.filters);
    }
    if (changes.currency || changes.currencyOverride) {
      this.setCurrencyAndPrefix();
    }
  }

  private buildHiddedColumns(): void {
    this.hiddenColumns = this.allColumns.filter(
      column => column?.hidden === true
    );
  }

  private buildInvisibleFilteringColumns(): void {
    this.invisibleFilterColumns = this.allColumns.filter(
      column => column?.filterable === 'invisible'
    );
  }

  checkIfShowDetailsRow() {
    this.showDetailRow = this.columns?.some(f => f.prop === 'details');
  }

  reordered({ prevValue, newValue }) {
    let oldIndex = prevValue - 1;
    if (this.datatableType === 'invoices') {
      const tableWithHiddenColumn = this.columns.find(
        c => c.prop === 'details'
      );
      if (tableWithHiddenColumn) {
        oldIndex = prevValue - 2;
      }
    }
    const newIndex = newValue - 1;

    const [elementToMove] = this.columns.splice(oldIndex, 1);
    this.columns.splice(newIndex, 0, elementToMove);
    this.columnsUpdated.emit(this.columns);
  }

  selected(event) {
    this.selection = event.selected;
  }

  sorted(event) {
    const lastSort = event.sorts.pop();
    const singleSort = lastSort ? [lastSort] : [];
    this.eventDebouncer.next(singleSort);
  }

  paged(event) {
    this.pageUpdated.emit(event.offset);
  }

  toggleExpandRow(event) {
    this.table.rowDetail.toggleExpandRow(event);
  }

  showHideFilters() {
    this.toggleFilters();
    this.rebuildForms();
  }

  toggleFilters() {
    this.showFiltersSlideOver = !this.showFiltersSlideOver;
    if (this.showFiltersSlideOver && this.showColumnsSlideOver) {
      this.toggleColumns();
    }
  }

  resetFilters(): void {
    this.buildFiltersForm(this.defaultFilters);
    this.updateFilters();
  }

  updateFilters() {
    if (this.filtersForm.valid && !this.isLoading) {
      const filterObj = this.filtersForm.getRawValue();
      Helpers.removeEmpty(filterObj);

      const filterEntriesArr = Object.entries(filterObj);
      const filtersArr = [];

      for (const [key, value] of filterEntriesArr) {
        const column = this.allColumns.find(c => c.prop === key);
        const type = column?.type;
        const cast = column?.cast;
        const check_on = column?.check_on;

        const filter_value = Array.isArray(value) ? value : [value];
        if (value) {
          const filter_prop = { prop: key, type, value: filter_value };
          if (cast) {
            filter_prop['cast'] = cast;
          }
          if (check_on !== undefined || check_on !== null) {
            filter_prop['check_on'] = check_on;
          }
          filtersArr.push(filter_prop);
        }
      }

      this.dateModels.forEach((value, key) => {
        if (value?.length > 0) {
          filtersArr.push({ prop: key, value, type: 'date' });
        }
      });

      this.toggleFilters();
      this.filtersUpdated.emit(filtersArr);
    }
  }

  cancelFilters() {
    this.toggleFilters();
    if (this.filtersForm.dirty) {
      this.buildFiltersForm(this.filters);
    }
  }

  showHideColumns() {
    this.toggleColumns();
    this.rebuildForms();
  }

  toggleColumns() {
    this.showColumnsSlideOver = !this.showColumnsSlideOver;
    if (this.showFiltersSlideOver && this.showColumnsSlideOver) {
      this.toggleFilters();
    }
  }

  public resetColumns(): void {
    this.buildColumnsForm(this.defaultColumns);
    this.updateColumns();
  }

  public get _columns(): Column[] {
    const hiddenColumns = this.hiddenColumns.map(column => column.prop);
    const invisibleFilter = this.invisibleFilterColumns.map(
      column => column.prop
    );
    return this.columns.filter(col => {
      return (
        !hiddenColumns.includes(col.prop) && !invisibleFilter.includes(col.prop)
      );
    });
  }

  public get _filterable_columns(): Column[] {
    const activeFiltersColumns = this.allColumns.filter(
      c =>
        c?.prop &&
        this.filters?.find(f => f.prop === c.prop && f.type === c.type)
    );
    const filteredColumns = this.invisibleFilterColumns.concat(this._columns);
    activeFiltersColumns.forEach(activeColumn => {
      const existingMatch = filteredColumns.find(
        column =>
          column.prop === activeColumn.prop && column.type === activeColumn.type
      );
      if (!existingMatch) {
        filteredColumns.push(activeColumn);
      }
    });
    return filteredColumns;
  }

  public updateColumns(): void {
    const selectedCols = this.columnsForm.getRawValue();
    const cols = this.allColumns.filter(c => selectedCols[c.prop]);

    if (cols.length > 0) {
      let updateColsAndSorts = false;

      this.sorts?.forEach((s, index) => {
        const columnForSortPresent = cols.find(c => c.prop === s.prop);

        if (!columnForSortPresent) {
          updateColsAndSorts = true;
          this.sorts.splice(index, 1);
        }
      });

      this.filters?.forEach(f => {
        if (
          f.type === FilterType.UUID &&
          !Array.isArray(f.value) &&
          f.value['name']
        ) {
          f.value = [f.value['id']];
        } else if (f.type === FilterType.UUID && Array.isArray(f.value)) {
          f.value = Array.from(f.value as any[]).map(item => item.id);
        }
      });
      if (updateColsAndSorts) {
        this.columnsAndSortsUpdated.emit({ columns: cols, sorts: this.sorts });
      } else {
        this.columnsUpdated.emit(cols);
      }

      this.toggleColumns();
    } else {
      this.showColumnsError = true;

      timer(5000).subscribe(time => {
        this.showColumnsError = false;
      });
    }
  }

  cancelColumns() {
    this.toggleColumns();
    if (this.columnsForm.touched) {
      this.buildColumnsForm(this.columns);
    }
  }

  clearFilter(name: string, type?: string, field?: string) {
    if (type === 'decimal' || type === 'integer' || type === 'percentage') {
      this.filtersForm.get(name).patchValue({ [field]: null });
    } else {
      this.filtersForm.get(name).setValue(undefined);
    }
    this.filtersForm.controls[name].markAsDirty();
    this.filtersForm.markAsDirty();
  }

  export() {
    this.exportClicked.emit();
  }

  /**
   * Relead data table
   */
  refresh(): void {
    this.refreshClicked.emit();
  }

  add() {
    this.addClicked.emit();
  }

  edit(row: any) {
    if (this.datatableType) {
      this.editClicked.emit(row);
      return;
    }
    this.editClicked.emit(row.id);
  }

  invoice(id: string) {
    this.invoiceClicked.emit(id);
  }

  order(event, id: string, date: string) {
    event.target.closest('datatable-body-cell').blur();
    this.orderClicked.emit({ id, date });
  }

  clone(id: string, projectID: string) {
    const isQuoteList = this.projectEntity === ProjectEntityEnum.QUOTES;
    if (this.selection[0].purchase_order_project) {
      this.cloneClicked.emit({ id, projectID, destination: 'current' });
    } else {
      this.destinationModal.openModal(isQuoteList).subscribe(result => {
        this.cloneClicked.emit({ id, projectID, destination: result });
      });
    }
  }

  delete(event?) {
    if (event) {
      /**
       * NOTE: to avoid ExpressionChangedAfterItHasBeenCheckedError
       */
      event.target.closest('datatable-body-cell').blur();
    }

    this.confirmModal
      .openModal(
        'Confirm',
        'Are you sure you want to delete ' +
          (this.selection.length > 1 ? ' these items?' : 'this item?')
      )
      .subscribe(
        result => {
          if (result) {
            this.selectionDeleted.emit(this.selection);
            this.selection = [];
          }
        },
        () => {}
      );
  }

  download(row: any) {
    this.downloadClicked.emit(row);
  }

  downloadDetail(row: any, detailRow: any) {
    this.downloadDetailClicked.emit({ row, detailRow });
  }

  public cancel(event, row: any): void {
    event.target.closest('datatable-body-cell').blur();

    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to cancel this document?')
      .subscribe(
        result => {
          if (result) {
            this.cancelClicked.emit(row);
          }
        },
        () => {}
      );
  }

  public markAsDefault(row: LegalEntity): void {
    this.actionRow.nativeElement.click();
    this.markAsDefaultClicked.emit(row);
  }

  public markAsLocal(row: LegalEntity): void {
    this.actionRow.nativeElement.click();
    this.markAsLocalClicked.emit(row);
  }

  public addEmployeeHours(row: any): void {
    this.actionRow.nativeElement.click();
    this.addHours.emit(row);
  }

  public addSalesCommission(row: any): void {
    this.actionRow.nativeElement.click();
    this.addCommissions.emit(row);
  }

  public editEmployeeHours(row: any, detailRow: any): void {
    this.actionRow.nativeElement.click();
    this.editHours.emit({ row, detailRow });
  }

  public editSalesCommission(row: any, detailRow: any): void {
    //this.actionRow.nativeElement.click();
    this.editCommissions.emit({ row, detailRow });
  }

  public deleteEmployeeHours(row: any, detailRow: any): void {
    this.actionRow.nativeElement.click();
    this.deleteHours.emit({ row, detailRow });
  }

  public deleteSalesCommission(row: any, detailRow: any): void {
    //this.actionRow.nativeElement.click();
    this.deleteCommissions.emit({ row, detailRow });
  }

  public paySalesCommission(row: any, detailRow: any): void {
    //this.actionRow.nativeElement.click();
    this.payCommissions.emit({ row, detailRow });
  }

  public unPaySalesCommission(row: any, detailRow: any): void {
    //this.actionRow.nativeElement.click();
    this.unPayCommissions.emit({ row, detailRow });
  }

  public viewTemplateEntities(row: any): void {
    this.actionRow.nativeElement.click();
    this.selectionTemplateClicked.emit(row);
  }

  public showTemplateEntitiesOption(row: any): boolean {
    return this.rowMenuConfig.viewTemplate;
  }

  public redirect(col: Column, row: any): void {
    if (col.no_redirect) {
      return;
    }

    let command = [];
    switch (col.model) {
      case 'user':
        command = [
          `${this.globalService.currentCompany.id}/settings/users/${row[col.prop]}/edit`,
        ];
        break;
      case 'resource':
        if (this.selection[0].is_contractor) {
          command = [
            `${this.globalService.currentCompany.id}/employees/${row[col.prop]}/edit`,
          ];
        } else {
          command = [
            `${this.globalService.currentCompany.id}/resources/${row[col.prop]}/edit`,
          ];
        }
        break;
      case 'employee':
        command = [
          `${this.globalService.currentCompany.id}/employees/${row[col.prop]}/edit`,
        ];
        break;
      case 'customer':
        command = [
          `${this.globalService.currentCompany.id}/customers/${row[col.prop]}/edit`,
        ];
        break;
      case 'contact': {
        const customerId = row.customer_id ?? row.id;
        command = [
          `${this.globalService.currentCompany.id}/customers/${customerId}/contacts/${row[col.prop]}/edit`,
        ];
        break;
      }
      case 'project':
        command = [
          this.globalService.currentCompany.id +
            '/projects/' +
            row[col.prop] +
            '/edit',
        ];
        break;
      case 'quote':
        command = [
          `${this.globalService.currentCompany.id}/projects/${row.project_id}/quotes/${row[col.prop]}/edit`,
        ];
        break;
      case 'order':
        command = [
          `${this.globalService.currentCompany.id}/projects/${row.project_id}/orders/${row[col.prop]}/edit`,
        ];
        break;
      case 'invoice':
        command = [
          `${this.globalService.currentCompany.id}/projects/${row.project_id}/invoices/${row[col.prop]}/edit`,
        ];
        break;
      case 'purchase_order':
        command = [
          `${this.globalService.currentCompany.id}/projects/${row.project_id}/purchase_orders/${row[col.prop]}/edit`,
        ];
        break;
    }
    this.routingService.setNext();
    this.router.navigate(command).then();
  }

  public redirectIndividual(col: Column, row: any): void {
    if (col.no_redirect) {
      return;
    }

    let command = [];
    switch (col.model) {
      case 'user':
        command = [
          `${this.globalService.currentCompany.id}/settings/users/${row}/edit`,
        ];
        break;
    }
    this.routingService.setNext();
    this.router.navigate(command).then();
  }

  public viewDetailPage(row: any): void {
    this.viewDetailPageClicked.emit(row);
  }

  async uploadInvoice(files: any, row: any) {
    const [file] = files;
    const { file: uploaded } = await this.readFile(file, ['application/pdf']);
    if (uploaded) {
      this.actionRow.nativeElement.click();
      this.invoiceUploaded.emit({ uploaded, row });
    }
  }

  async uploadFile(files: any) {
    const [file] = files;
    const { file: uploaded } = await this.readFile(file, [
      'text/csv',
      'text/plain',
    ]);
    if (uploaded) {
      this.fileSuccessfullyUploaded.emit(uploaded);
    }
  }

  readFile(file, types: string[]): Promise<any> {
    return new Promise(resolve => {
      const reader = new FileReader();
      reader.onload = () => {
        this.checkFileRestrictions(file, types).then(
          approved => {
            if (approved) {
              resolve({ filename: file.name, file: reader.result });
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

  checkFileRestrictions(file: File, acceptedTypes: string[]) {
    const maxSize = 10 * 1024 * 1024;

    return new Promise(resolve => {
      if (file.size > maxSize) {
        this.toastService.error(
          'Sorry, this file is too big. 10 Mb is the limit.',
          'Uploading error'
        );
        resolve(false);
      }
      if (acceptedTypes.includes(file.type)) {
        resolve(true);
      } else if (acceptedTypes.includes('text/csv')) {
        Papa.parse(file, {
          complete: results => {
            if (results.errors.length > 0) {
              resolve(false);
              this.toastService.error(
                'Could not parse CSV, check the formatting of your file',
                'Parsing error'
              );
            } else {
              resolve(true);
            }
          },
        });
      } else {
        resolve(false);
      }
    });
  }

  toggleColumn(col) {
    if (!this.columnsForm.get(col.prop).disabled) {
      const value = this.columnsForm.get(col.prop).value;
      this.columnsForm.get(col.prop).setValue(!value);
      if (col.children) {
        col.children.forEach(childColumn => {
          this.columnsForm.get(childColumn).setValue(!value);
        });
      }
      this.columnsForm.markAsDirty();
    }
  }

  closeFiltersSlider() {
    const changedField = Object.keys(this.filtersForm.controls).find(
      field => this.filtersForm.controls[field].dirty
    );
    if (changedField) {
      const isDecimalTypeChanged =
        this.columns?.find(f => f.prop === changedField).type === 'decimal';
      let oldValue;

      if (isDecimalTypeChanged) {
        oldValue = this.filters?.find(f => f.prop === changedField)?.value[0];
      } else {
        oldValue = this.filters?.find(f => f.prop === changedField)?.value;
      }

      const newValue = this.filtersForm.controls[changedField]?.value;

      if (oldValue || newValue) {
        this.checkEquality(oldValue, newValue);
      } else {
        this.showFiltersSlideOver = false;
      }
    } else {
      this.showFiltersSlideOver = false;
    }
  }

  checkEquality(oldValue, newValue) {
    const isArray = Array.isArray(newValue);
    let equalValues;
    if (isArray) {
      equalValues = this.checkArraysEquality(oldValue, newValue);
    } else {
      equalValues = this.checkObjectsEquality(oldValue, newValue);
    }

    if (equalValues) {
      this.showFiltersSlideOver = false;
    } else {
      this.confirmModal
        .openModal(
          'Confirm',
          'You have unsaved filters, are you sure you want to proceed?'
        )
        .subscribe(closeModal => (this.showFiltersSlideOver = !closeModal));
    }
  }

  checkArraysEquality(oldArr, newArr) {
    return (
      Array.isArray(oldArr) &&
      Array.isArray(newArr) &&
      oldArr.length === newArr.length &&
      oldArr.every((v, i) => v.toLowerCase() === newArr[i].toLowerCase())
    );
  }

  checkObjectsEquality(oldObj, newObj) {
    if (!oldObj || !newObj) {
      return false;
    }
    return (
      Object.entries(oldObj).toString() === Object.entries(newObj).toString()
    );
  }

  onDateChange($event, prop) {
    this.dateModels.set(prop, $event);
  }

  public showActionCell(row: any): boolean {
    /**
     * NOTE: here it's assumed that only edit/deletion could be possible from the drop-down menu
     */
    if (this.isIndividualEdit && this.isIndividualDeletion) {
      return DatatableComponent.isActionPermissionSetByFront(
        row,
        'is_edit_allowed'
      )
        ? row['is_edit_allowed'] || row['is_deletion_allowed']
        : true;
    }

    if (!this.isIndividualEdit && this.isIndividualDeletion) {
      if (this.isCompanyLegal) {
        if (row.default && row.local) {
          return false;
        }
      } else {
        return DatatableComponent.isActionPermissionSetByFront(
          row,
          'is_deletion_allowed'
        )
          ? row['is_deletion_allowed']
          : true;
      }
    }

    return true;
  }

  public showDetailsActionCell(row: any): boolean {
    return (
      this.datatableType !== 'resource_purchase_orders' ||
      this.showDownloadOption(row)
    );
  }

  public showEditOrViewOption(row: any): boolean {
    if (this.isIndividualEdit) {
      return DatatableComponent.isActionPermissionSetByFront(
        row,
        'is_edit_allowed'
      )
        ? row['is_edit_allowed'] || this.rowMenuConfig.view
        : true;
    }

    return this.rowMenuConfig.edit || this.rowMenuConfig.view;
  }

  public showDeleteOption(row: any): boolean {
    if (this.isIndividualDeletion) {
      if (this.isCompanyLegal) {
        if (row.default) {
          return false;
        }
      } else {
        return DatatableComponent.isActionPermissionSetByFront(
          row,
          'is_deletion_allowed'
        )
          ? row['is_deletion_allowed']
          : !row['deleted_at'] && this.rowMenuConfig.delete;
      }
    }

    return this.rowMenuConfig.delete;
  }

  public showEditIcon(row: any): boolean {
    if (this.isIndividualEdit) {
      return row['is_edit_allowed'] && !row['deleted_at'];
    }

    return this.rowMenuConfig.edit;
  }

  private static isActionPermissionSetByFront(
    row: any,
    field: DatatableActionField
  ): boolean {
    return field in row;
  }

  public showDownloadOption(row: any): boolean {
    if (this.isIndividualDownload) {
      /**
       * NOTE: entity may NOT have `download` property but still have `isIndividualDownload: true`
       * (this happens for tables with collapsed details (resource invoices e.g.))
       */
      return DatatableComponent.isActionPermissionSetByFront(row, 'download')
        ? row['download']
        : true;
    }

    return this.rowMenuConfig.export;
  }

  public showUploadInvoiceOption(row: any): boolean {
    return (
      this.rowMenuConfig.uploadInvoice &&
      row.status >= PurchaseOrderStatus.AUTHORISED
    );
  }

  public showCancelOption(row: any): boolean {
    if (this.rowMenuConfig.cancel) {
      if (this.projectEntity === ProjectEntityEnum.QUOTES) {
        return row.status !== QuoteStatus.CANCELED;
      }

      if (this.projectEntity === ProjectEntityEnum.INVOICES) {
        return row.status !== InvoiceStatus.CANCELED;
      }

      if (this.projectEntity === ProjectEntityEnum.RESOURCE_INVOICES) {
        return row.status !== InvoiceStatus.CANCELED;
      }

      if (this.projectEntity === ProjectEntityEnum.PURCHASE_ORDERS) {
        return row.status !== PurchaseOrderStatus.CANCELED;
      }
    }

    return false;
  }

  public showMarkAsDefaultOption(row: any): boolean {
    return (
      this.rowMenuConfig.markAsDefault && !row.default && !row.primary_contact
    );
  }

  public showMarkAsLocalOption(row: any): boolean {
    return this.rowMenuConfig.markAsLocal && !row.local;
  }

  public showAddHoursOption(row: any): boolean {
    return this.rowMenuConfig.employeeAddHours;
  }

  public isDropdownMenu(): boolean {
    return this.rowMenuConfig.style === DatatableMenuStyle.DROPDOWN;
  }

  public isInlineMenu(): boolean {
    return this.rowMenuConfig.style === DatatableMenuStyle.INLINE;
  }

  public showAddCommissionOption(row: any): boolean {
    return this.rowMenuConfig.addCommission;
  }

  public getCurrency(row: any): number {
    if (this.isExternalAccess) {
      return row.currency_code;
    } else if (this.datatableType === 'employee_history') {
      return row.default_currency;
    } else {
      return this.currency || 1;
    }
  }

  public colorStatus(row: any, entity: string) {
    let color: string;
    switch (entity) {
      case 'Orders':
        switch (row.status) {
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
        break;
      case 'Quotes':
        switch (row.status) {
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
        break;
      case 'Purchase Orders':
        switch (row.status) {
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
        break;
      case 'Invoices':
      case 'Resource Invoices':
        switch (row.status) {
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
        break;
      case 'Customers':
        switch (row.status) {
          case ResourceStatus.ACTIVE:
            color = 'active-status';
            break;
          case ResourceStatus.POTENTIAL:
            color = 'invoiced-status';
            break;
          case ResourceStatus.INACTIVE:
            color = 'draft-status';
            break;
          case ResourceStatus.ARCHIVED:
            color = 'declined-status';
            break;
        }
        break;
      case 'Employees':
      case 'Resources':
        switch (row.status) {
          case ResourceStatus.ACTIVE:
            color = 'active-status';
            break;
          case ResourceStatus.POTENTIAL:
            color = 'invoiced-status';
            break;
          case ResourceStatus.INACTIVE:
            color = 'declined-status';
            break;
          case ResourceStatus.ARCHIVED:
            color = 'draft-status';
            break;
        }
        break;
      case 'Commissions InvDetails':
        switch (row.invoice_status) {
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
        break;
      case 'Commissions OrDetails':
        switch (row.order_status) {
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
        break;
      default:
        color = '';
        break;
    }

    return color;
  }

  public isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private setCurrencyAndPrefix(): void {
    if (this.currencyOverride) {
      this.currency = this.currencyOverride;
    } else {
      this.currency =
        this.globalService.getUserRole() === 0
          ? environment.currency
          : this.globalService.userCurrency;
    }

    this.currencyPrefix =
      getCurrencySymbol(
        this.enumService.getEnumMap('currencycode').get(this.currency),
        'wide'
      ) + ' ';
  }

  private buildFiltersForm(filters = []): void {
    filters = filters ?? [];
    this.filtersForm = this.fb.group({});
    this.typeAheads.clear();
    this.dateModels.clear();
    const filterColumns = filters.map(filter => filter.prop);
    this.invisibleFilterColumns.forEach(column => {
      if (!filterColumns.includes(column.prop)) {
        filters.push({
          prop: column.prop,
          value: [],
        });
      }
    });
    if (filters) {
      for (const col of this._filterable_columns) {
        this.checkIfRedirectRestricted(col);
        this.buildFilterControls(filters, col);
      }
    }
  }

  private getColumnIndexByName(name: string): number {
    return this.allColumns.findIndex(c => c.name === name);
  }

  private buildColumnsForm(columns = []): void {
    this.columnsForm = this.fb.group({});
    if (this.allColumns && this.columns) {
      for (const col of this.allColumns) {
        if (col.hidden) {
          continue;
        }
        this.columnsForm.addControl(
          col.prop,
          new FormControl(!!columns?.find(c => (c?.prop || c) === col.prop))
        );

        if (col.children) {
          for (const childCold of col.children) {
            if (this.columnsForm.get(childCold)) {
              this.columnsForm
                .get(childCold)
                .setValue(this.columnsForm.get(col.prop).value);
            }
          }
        }

        if (col.parent && this.columnsForm.get(col.parent)) {
          this.columnsForm
            .get(col.prop)
            .setValue(this.columnsForm.get(col.parent).value);
        }
      }
    }
  }

  private rebuildForms() {
    if (this.filtersForm.dirty) {
      this.buildFiltersForm();
    }
    if (this.columnsForm.dirty) {
      this.buildColumnsForm();
    }
  }

  private initTypeAhead(key: string, model: string, prop: string, def?: any) {
    const typeAhead = this.typeAheads.get(key);
    typeAhead.select = concat(
      of(def ?? []), // default items
      typeAhead.input.pipe(
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          typeAhead.loading = true;
        }),
        switchMap(term =>
          this.getSuggestObservable(term, model, prop).pipe(
            catchError(() => of([])), // empty list on error
            tap(() => {
              typeAhead.loading = false;
            })
          )
        ),
        map(result => {
          if (Array.isArray(result)) {
            return result; // Return the array as is
          } else if (typeof result === 'object' && result !== null) {
            const keys = Object.keys(result);
            if (keys.length > 0 && Array.isArray(result[keys[0]])) {
              return result[keys[0]]; // Return the value of the first object key
            }
          }
          return [];
        })
      )
    );
  }

  public isSimpleColumn(col: Column): boolean {
    return !col.parent;
  }

  private getSuggestObservable(
    term: string,
    model: string,
    prop: string
  ): Observable<any> {
    let params = new HttpParams();

    switch (model) {
      case 'resource':
        if (prop === 'project_manager_id') {
          params = Helpers.setParam(params, 'type', '2');
        }
        return this.suggestService.suggestResources(term, params);
      case 'user':
        params = Helpers.setParam(params, 'type', '3');
        return this.suggestService.suggestUsers(term, params);
      case 'project':
        return this.suggestService.suggestProject(term, params);
      case 'customer':
        return this.suggestService.suggestCustomer(term, params);
      case 'contact':
        return this.suggestService.suggestContact(term, params);
      case 'employee':
        return this.suggestService.suggestProjectManagers(term, params);
      case 'legal_entity':
        return this.suggestService.suggestLegalEntities(term, params);
      default:
        throw new Error('Suggest for model: ' + model + ' not found.');
    }
  }

  private checkIfRedirectRestricted(col) {
    const role = this.globalService.getUserRole();
    switch (col.model) {
      case 'user':
        col.no_redirect = role === 2 || role === 3 || role === 4 || role === 5;
        break;
      case 'resource':
        col.no_redirect = role === 3;
        break;
      case 'employee':
        col.no_redirect = role === 3 || role === 4;
        break;
      case 'customer':
      case 'contact':
      case 'quote':
        col.no_redirect = role === 4 || role === 5;
        break;
      case 'project':
      case 'order':
      case 'invoice':
        col.no_redirect = role === 5;
        break;
    }
  }

  private buildFilterControls(filters = [], col: Column): void {
    const filter = filters?.find(f => f?.prop && f.prop === col.prop);
    let decimal;
    let integer;
    let percentage;
    const date = filter?.value as DateFilterValue;
    let uuid = [];

    switch (col.type) {
      case FilterType.DECIMAL:
        if (filter?.value) {
          decimal = filter?.value[0] as DecimalFilterValue;
        }
        if (col.format !== 'number') {
          this.filtersForm.addControl(
            col.prop,
            this.fb.group(
              {
                min: new FormControl(decimal ? decimal?.min : undefined),
                max: new FormControl(decimal ? decimal?.max : undefined),
              },
              {
                validators: [greaterThanValidator('min', 'max')],
              }
            )
          );
        } else {
          this.filtersForm.addControl(
            col.prop,
            this.fb.group(
              {
                min: new FormControl(
                  decimal ? decimal?.min : undefined,
                  Validators.pattern(floatRegEx)
                ),
                max: new FormControl(
                  decimal ? decimal?.max : undefined,
                  Validators.pattern(floatRegEx)
                ),
              },
              {
                validators: [greaterThanValidator('min', 'max')],
              }
            )
          );
        }

        break;
      case FilterType.INTEGER:
        if (filter?.value) {
          integer = filter?.value[0] as IntegerFilterValue;
        }

        this.filtersForm.addControl(
          col.prop,
          this.fb.group(
            {
              from: new FormControl(
                integer ? integer.from : undefined,
                Validators.pattern(numberOnlyRegEx)
              ),
              to: new FormControl(
                integer ? integer.to : undefined,
                Validators.pattern(numberOnlyRegEx)
              ),
            },
            {
              validators: [greaterThanValidator('from', 'to')],
            }
          )
        );
        break;
      case FilterType.PERCENTAGE:
        if (filter?.value) {
          percentage = filter?.value[0] as PercentageFilterValue;
        }

        this.filtersForm.addControl(
          col.prop,
          this.fb.group(
            {
              from: new FormControl(
                percentage ? percentage.from : undefined,
                Validators.pattern(numberOnlyRegEx)
              ),
              to: new FormControl(
                percentage ? percentage.to : undefined,
                Validators.pattern(numberOnlyRegEx)
              ),
            },
            {
              validators: [greaterThanValidator('from', 'to')],
            }
          )
        );
        break;
      case FilterType.DATE:
        this.dateModels.set(col.prop, date ?? []);
        break;
      case FilterType.UUID:
        switch (col.model) {
          case 'quote':
          case 'invoice':
          case 'order':
          case 'purchase_order':
          case 'project_resource':
            this.filtersForm.addControl(
              col.prop,
              new FormControl(filter?.value)
            );
            col.uuid_type = 'string';
            break;
          default:
            if (filter?.value && Array.isArray(filter?.value)) {
              uuid = filter?.value.map(item => item as UuidFilterValue);
            } else if (filter?.value) {
              uuid.push(filter?.value as UuidFilterValue);
            }
            this.filtersForm.addControl(
              col.prop,
              new FormControl(uuid?.map(item => item.id))
            );
            col.uuid_type = 'suggest';

            this.typeAheads.set(col.name, {
              input: new Subject<any>(),
              select: new Observable<any>(),
              loading: false,
            });

            this.initTypeAhead(col.name, col.model, col.prop, uuid);
            break;
        }
        break;
      default:
        this.filtersForm.addControl(col.prop, new FormControl(filter?.value));
        break;
    }
  }

  forcingCurrencyUppercase(title: string): string {
    const regex = /\b(Eur|Usd)\b/g;
    return title.replace(regex, match => match.toUpperCase());
  }

  getViewTitle(): string {
    const lowercaseTitle = this.title.toLowerCase();
    return `View ${lowercaseTitle.endsWith('ies') ? lowercaseTitle.slice(0, -3) + 'y' : lowercaseTitle.slice(0, -1)}`;
  }
}
