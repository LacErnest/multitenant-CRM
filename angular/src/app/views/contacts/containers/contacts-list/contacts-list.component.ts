import { Component, Inject, OnDestroy, OnInit } from '@angular/core';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { Subscription } from 'rxjs';
import { EnumService } from '../../../../core/services/enum.service';
import { GlobalService } from '../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { ToastrService } from 'ngx-toastr';
import { filter, finalize, skip } from 'rxjs/operators';
import { ContactsService } from '../../contacts.service';
import { RoutingService } from '../../../../core/services/routing.service';
import { Helpers } from '../../../../core/classes/helpers';
import { ExportFormat } from '../../../../shared/enums/export.format';
import { DOCUMENT } from '@angular/common';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-contacts-list',
  templateUrl: './contacts-list.component.html',
  styleUrls: ['./contacts-list.component.scss'],
})
export class ContactsListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    delete: false,
    export: this.globalService.canExport(),
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
    delete: false,
  });

  isLoading = false;
  contacts: { data: any; count: number };

  private navigationSub: Subscription;
  private companySub: Subscription;

  constructor(
    private enumService: EnumService,
    private globalService: GlobalService,
    private contactsService: ContactsService,
    protected route: ActivatedRoute,
    private router: Router,
    private routingService: RoutingService,
    protected tablePreferencesService: TablePreferencesService,
    private toastService: ToastrService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/contacts']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  getData(): void {
    this.isLoading = true;
    this.contactsService
      .getContacts(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.contacts = response;
      });
  }

  editContact({ id: contactID, customer_id: customerID }): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/customers/${customerID}/contacts/${contactID}/edit`,
      ])
      .then();
  }

  public exportContacts(): void {
    this.isLoading = true;
    this.contactsService
      .exportContacts()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Contacts');
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }

  private getResolvedData(): void {
    const { contacts, table_preferences } = this.route.snapshot.data;
    this.contacts = contacts;
    this.preferences = table_preferences;
  }

  private setPermissions() {
    if (this.globalService.getUserRole() === 3) {
      this.buttonConfig.add = false;
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view = true;
    }
  }
}
