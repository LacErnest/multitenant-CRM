import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnDestroy,
  OnInit,
  Output,
  ViewChild,
} from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';

import { Subject, Subscription } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';

import { GlobalService } from 'src/app/core/services/global.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { ServicesService } from 'src/app/shared/services/services.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ServiceModalComponent } from 'src/app/shared/components/service-modal/service-modal.component';
import { DOCUMENT } from '@angular/common';
import {
  EmployeeHistory,
  EmployeeHistoryList,
} from '../../interfaces/employee-history';
import { EmployeesService } from '../../employees.service';
import { TablePreferences } from '../../../../shared/interfaces/table-preferences';
import { EmployeeHistoryModalComponent } from '../../../../shared/components/employee-history-modal/employee-history-modal.component';
import { Service } from '../../../../shared/interfaces/service';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { ErrorHandlerService } from 'src/app/core/services/error-handler.service';

@Component({
  selector: 'oz-finance-employee-histories-list',
  templateUrl: './employee-histories-list.component.html',
  styleUrls: ['./employee-histories-list.component.scss'],
})
export class EmployeeHistoriesListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @Input() public employeeId: string;
  @Input() public employeeCurrency: number;
  @Input() public readonlyMode = false;

  @ViewChild('employeeHistoryModal', { static: false })
  private employeeHistoryModal: EmployeeHistoryModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    delete: !this.isOwnerReadOnly(),
    add: !this.isOwnerReadOnly(),
    export: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
    showMenu: !this.isOwnerReadOnly(),
  });
  public histories: EmployeeHistoryList;
  public refreshNeeded: boolean;

  private onDestroy$: Subject<void> = new Subject<void>();
  private historiesSubscription: Subscription;

  public constructor(
    protected tablePreferencesService: TablePreferencesService,
    protected route: ActivatedRoute,
    private globalService: GlobalService,
    private router: Router,
    private employeeService: EmployeesService,
    private toastrService: ToastrService,
    protected appStateService: AppStateService,
    private errorHandlerService: ErrorHandlerService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
    this.historiesSubscription = this.employeeService
      .getRefreshHistory()
      .subscribe(refresh => {
        this.refreshNeeded = refresh;
        if (this.refreshNeeded) {
          this.getData();
          this.employeeService.setRefreshHistory(false);
        }
      });
  }

  public ngOnInit(): void {
    this.preferences = this.route.snapshot.data.tablePreferences;
    this.getData();

    if (this.readonlyMode === true) {
      this.disabledActionButtons();
    }
  }

  public ngOnDestroy(): void {
    this.historiesSubscription.unsubscribe();
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public disabledActionButtons(): void {
    this.buttonConfig.add = false;
    this.buttonConfig.delete = false;
    this.rowMenuConfig.showMenu = false;
  }

  public addHistory(): void {
    this.employeeHistoryModal
      .openModal(undefined, this.employeeCurrency)
      .subscribe(
        result => {
          this.createEmployeeHistory(result);
        },
        () => {
          this.toastrService.error('Record was not added', 'Error');
        }
      );
  }

  public editHistory(history: EmployeeHistory): void {
    this.employeeHistoryModal
      .openModal(history, history.default_currency)
      .subscribe(
        result => {
          this.editEmployeeHistory(result);
        },
        () => {
          this.toastrService.error('Record was not updated', 'Error');
        }
      );
  }

  public deleteHistory(history: EmployeeHistory): void {
    this.deleteEmployeeHistory(history[0].id);
  }

  protected getData(): void {
    this.getEmployeeHistory();
  }

  private getEmployeeHistory(): void {
    this.isLoading = true;
    this.employeeService
      .getEmployeeHistory(this.employeeId, this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.histories = response;
      });
  }

  private createEmployeeHistory(history: EmployeeHistory): void {
    this.isLoading = true;

    this.employeeService
      .createEmployeeHistory(this.employeeId, history)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        () => {
          this.getData();
          this.toastrService.success(
            'Employee record was successfully added',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error)
      );
  }

  private editEmployeeHistory(history: EmployeeHistory): void {
    this.isLoading = true;

    this.employeeService
      .editEmployeeHistory(this.employeeId, history.id, history)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        () => {
          this.getData();
          this.toastrService.success(
            'Employee record was successfully updated',
            'Success'
          );
        },
        error => this.errorHandlerService.handle(error)
      );
  }

  private deleteEmployeeHistory(historyId: string): void {
    this.isLoading = true;

    this.employeeService
      .deleteEmployeeHistory(this.employeeId, historyId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.getData();

          this.toastrService.success(
            'History was successfully deleted',
            'Success'
          );
        },
        error => {
          this.toastrService.error('History was not deleted', 'Error');
        }
      );
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
