import { Component, Input, OnInit, ViewChild } from '@angular/core';
import { EmployeesService } from '../../employees.service';
import { finalize } from 'rxjs/operators';
import moment from 'moment';
import { Helpers } from '../../../../core/classes/helpers';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import { animate, style, transition, trigger } from '@angular/animations';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { ActivatedRoute } from '@angular/router';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { EmployeeAssignModalComponent } from '../employee-assign-modal/employee-assign-modal.component';
import { ToastrService } from 'ngx-toastr';
import { DatatableDetailConfig } from '../../../../shared/classes/datatable/datatable-detail-config';
import { ConfirmModalComponent } from '../../../../shared/components/confirm-modal/confirm-modal.component';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-employees-assign-list',
  templateUrl: './employees-assign-list.component.html',
  styleUrls: ['./employees-assign-list.component.scss'],
  animations: [
    trigger('filterAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in', style({ opacity: 1 })),
      ]),
    ]),
  ],
})
export class EmployeesAssignListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @ViewChild('employeeAssignModal', { static: false })
  employeeAssignModal: EmployeeAssignModalComponent;
  @ViewChild('confirmModal', { static: false })
  public confirmModal: ConfirmModalComponent;
  @Input() isLoading = false;

  public data: { data: []; count: number };
  public currentYear = moment.utc().year();
  public currentMonth = moment.utc().month() + 1;
  public filterForm: FormGroup;
  filters = [
    { key: 'year', value: 'Year' },
    { key: 'month', value: 'Month' },
  ];
  public months = [
    { key: 1, value: 'January' },
    { key: 2, value: 'February' },
    { key: 3, value: 'March' },
    { key: 4, value: 'April' },
    { key: 5, value: 'May' },
    { key: 6, value: 'June' },
    { key: 7, value: 'July' },
    { key: 8, value: 'August' },
    { key: 9, value: 'September' },
    { key: 10, value: 'October' },
    { key: 11, value: 'November' },
    { key: 12, value: 'December' },
  ];
  public years = [];

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: false,
    filters: false,
    export: false,
    delete: false,
    add: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
    delete: false,
    edit: false,
    employeeAddHours: true,
  });
  public detailConfig: DatatableDetailConfig = new DatatableDetailConfig();

  public preferences = {
    columns: [
      {
        prop: 'name',
        name: 'Name',
        type: 'string',
      },
      {
        prop: 'role',
        name: 'Role',
        type: 'string',
      },
      {
        prop: 'working_hours',
        name: 'Working Hours',
        type: 'integer',
      },
      {
        prop: 'hourly_wage',
        name: 'hourly wage',
        type: 'decimal',
      },
      {
        prop: 'already_assigned',
        name: 'Assigned hours',
        type: 'integer',
      },
      {
        prop: 'details',
        name: 'details',
        type: 'object',
      },
    ],
    all_columns: [],
    filters: [],
    sorts: [],
    default_columns: [
      {
        prop: 'name',
        name: 'Name',
        type: 'string',
      },
      {
        prop: 'role',
        name: 'Role',
        type: 'string',
      },
      {
        prop: 'working_hours',
        name: 'Working Hours',
        type: 'integer',
      },
      {
        prop: 'hourly_wage',
        name: 'hourly wage',
        type: 'decimal',
      },
      {
        prop: 'already_assigned',
        name: 'Assigned hours',
        type: 'integer',
      },
      {
        prop: 'details',
        name: 'details',
        type: 'object',
      },
    ],
    default_filters: [],
  };

  public constructor(
    protected tablePreferencesService: TablePreferencesService,
    protected route: ActivatedRoute,
    private employeesService: EmployeesService,
    private fb: FormBuilder,
    private toastrService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  ngOnInit(): void {
    this.params = Helpers.setParam(
      this.params,
      'year',
      this.currentYear.toString()
    );
    this.params = Helpers.setParam(
      this.params,
      'month',
      this.currentMonth.toString()
    );
    this.fillYears();
    this.initFilterForm();
    this.getData();
  }

  public submit(): void {
    if (!this.isLoading) {
      this.params = Helpers.setParam(
        this.params,
        'year',
        this.filterForm.controls.year.value
      );
      this.params = Helpers.setParam(
        this.params,
        'month',
        this.filterForm.controls.month.value
      );
      this.getData();
    }
  }

  public addHours(row: string): void {
    this.employeeAssignModal.openModal(row).subscribe(
      result => {
        if (this.filterForm.controls.month.value < 10) {
          result['month'] =
            this.filterForm.controls.year.value +
            '-0' +
            this.filterForm.controls.month.value;
        } else {
          result['month'] =
            this.filterForm.controls.year.value +
            '-' +
            this.filterForm.controls.month.value;
        }

        this.employeesService.editEmployeeHours(result).subscribe(
          () => {
            this.toastrService.success(
              null,
              'Employee hours assigned to order'
            );
            this.getData();
          },
          err => {
            const msg =
              err?.message ??
              'Could not assign employee hours. Try again or contact an administrator';
            this.toastrService.error(msg, 'Error');
          }
        );
      },
      () => {
        this.toastrService.error(
          'Could not assign employee hours. Try again or contact an administrator',
          'Error'
        );
      }
    );
  }

  public editHours({ row, detailRow }): void {
    this.employeeAssignModal.openModal(row, detailRow).subscribe(
      result => {
        if (this.filterForm.controls.month.value < 10) {
          result['month'] =
            this.filterForm.controls.year.value +
            '-0' +
            this.filterForm.controls.month.value;
        } else {
          result['month'] =
            this.filterForm.controls.year.value +
            '-' +
            this.filterForm.controls.month.value;
        }

        this.employeesService.editEmployeeHours(result).subscribe(
          () => {
            this.toastrService.success(
              null,
              'Employee hours assigned to order'
            );
            this.getData();
          },
          err => {
            const msg =
              err?.message ??
              'Could not assign employee hours. Try again or contact an administrator';
            this.toastrService.error(msg, 'Error');
          }
        );
      },
      () => {
        this.toastrService.error(
          'Could not assign employee hours. Try again or contact an administrator',
          'Error'
        );
      }
    );
  }

  public deleteHours({ row, detailRow }): void {
    const result = {};
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to remove hours from order?')
      .subscribe(
        confirm => {
          if (confirm) {
            if (this.filterForm.controls.month.value < 10) {
              result['month'] =
                this.filterForm.controls.year.value +
                '-0' +
                this.filterForm.controls.month.value;
            } else {
              result['month'] =
                this.filterForm.controls.year.value +
                '-' +
                this.filterForm.controls.month.value;
            }
            result['order_id'] = detailRow.order_id;
            result['employee_id'] = row.id;

            this.employeesService.deleteEmployeeHours(result).subscribe(
              () => {
                this.toastrService.success(
                  null,
                  'Employee hours removed from order'
                );
                this.getData();
              },
              err => {
                const msg =
                  err?.message ??
                  'Could not remove employee hours. Try again or contact an administrator';
                this.toastrService.error(msg, 'Error');
              }
            );
          }
        },
        () => {}
      );
  }

  getData(): void {
    this.isLoading = true;
    this.employeesService
      .getActiveEmployees(this.params)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.data = response;
      });
  }

  private fillYears(): void {
    this.years = [];
    for (let i = this.currentYear + 1; i >= Number(this.currentYear) - 2; i--) {
      this.years.push(i.toString());
    }
  }

  private initFilterForm() {
    this.filterForm = this.fb.group({
      year: new FormControl(this.currentYear),
      month: new FormControl(this.currentMonth),
    });
  }
}
