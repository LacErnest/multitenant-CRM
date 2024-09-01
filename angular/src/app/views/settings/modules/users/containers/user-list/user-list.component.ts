import { Component, OnDestroy, OnInit } from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { TablePreferencesService } from '../../../../../../shared/services/table-preferences.service';
import { DatatableContainerBase } from '../../../../../../shared/classes/datatable/datatable-container-base';
import { filter, finalize, skip } from 'rxjs/operators';
import { UsersService } from '../../users.service';
import { ToastrService } from 'ngx-toastr';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { Subscription } from 'rxjs';
import { DatatableMenuConfig } from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { MenuOption } from 'src/app/shared/interfaces/table-menu-option';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-user-list',
  templateUrl: './user-list.component.html',
  styleUrls: ['./user-list.component.scss'],
})
export class UserListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  users: { data: any[]; count: number };
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    export: false,
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    export: false,
    delete: false,
  });
  protected table = 'users';
  private navigationSub: Subscription;
  private companySub: Subscription;
  public menuOptions: MenuOption[] = [
    {
      title: 'Disable user',
      icon: 'lock',
      visible: (row: any) => !this.isOwnerReadOnly() && !row.disabled_at,
      onAction: user => this.toggleUsersStatus(user),
    },
    {
      title: 'Enable user',
      icon: 'lock-open',
      visible: (row: any) => !this.isOwnerReadOnly() && !!row.disabled_at,
      onAction: user => this.toggleUsersStatus(user),
    },
  ];

  constructor(
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private router: Router,
    private toastService: ToastrService,
    private usersService: UsersService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  ngOnInit(): void {
    super.ngOnInit();
    this.getResolvedData();
    this.initSubscriptions();

    const userRole = this.globalService.getUserRole();
    this.buttonConfig.add =
      this.buttonConfig.delete =
      this.buttonConfig.edit =
        userRole === 0 || userRole === 1;
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  getData(): void {
    this.isLoading = true;
    this.usersService
      .getUsers(this.params)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(response => {
        this.users = response;
      });
  }

  addUser(): void {
    this.router.navigate(['create'], { relativeTo: this.route });
  }

  editUser(id: string): void {
    this.router.navigate([id + '/edit'], { relativeTo: this.route });
  }

  deleteUsers(users: any): void {
    this.isLoading = true;
    this.usersService
      .deleteUsers(users.map(u => u.id.toString()))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(_ => {
        this.users.data = this.users.data.filter(u => !users.includes(u));
        const msgBeginning = users.length > 1 ? 'Users have' : 'User has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  toggleUsersStatus(user: any): void {
    this.isLoading = true;
    this.usersService
      .toggleUserStatus(user.id.toString())
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(_user => {
        this.users.data = this.users.data.map(u => {
          if (u.id === _user.id) {
            u.disabled_at = _user.disabled_at;
          }
          return u;
        });
        const statusMsg = !_user.disabled_at ? 'Activated' : 'Deactivated';
        this.toastService.success(
          `User has been ${statusMsg} successfully`,
          'Success'
        );
      });
  }

  private getResolvedData(): void {
    const { table_preferences, users } = this.route.snapshot.data;
    this.users = users;
    this.preferences = table_preferences;
  }

  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/settings/users']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
