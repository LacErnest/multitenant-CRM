import { Component, Input, OnInit } from '@angular/core';
import { finalize } from 'rxjs/operators';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { ToastrService } from 'ngx-toastr';
import { ContactsService } from '../../modules/contacts/contacts.service';
import { DatatableContainerBase } from '../../../../shared/classes/datatable/datatable-container-base';
import { ActivatedRoute, Router } from '@angular/router';
import { GlobalService } from '../../../../core/services/global.service';
import { TablePreferencesService } from '../../../../shared/services/table-preferences.service';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';
import { RoutingService } from '../../../../core/services/routing.service';
import moment from 'moment';
import { UserRole } from '../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-contact-list',
  templateUrl: './contact-list.component.html',
  styleUrls: ['./contact-list.component.scss'],
})
export class ContactListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @Input() contacts: any[];
  @Input() customerId: number;
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    export: false,
    columns: false,
    filters: false,
    delete: false,
    add: !this.isOwnerReadOnly(),
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    delete: false,
    export: false,
    markAsDefault: !this.isOwnerReadOnly(),
  });
  contactsColumns = [
    { prop: 'first_name', name: 'first name', type: 'string' },
    { prop: 'last_name', name: 'last name', type: 'string' },
    { prop: 'email', name: 'email', type: 'string' },
    { prop: 'phone_number', name: 'phone number', type: 'string' },
    { prop: 'department', name: 'department', type: 'string' },
    { prop: 'primary_contact', name: 'primary contact', type: 'boolean' },
    { prop: 'title', name: 'title', type: 'string' },
  ];

  constructor(
    private contactService: ContactsService,
    private globalService: GlobalService,
    private router: Router,
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private toastService: ToastrService,
    private routingService: RoutingService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  ngOnInit(): void {}

  addContact(): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/customers/${this.customerId}/contacts/create`,
      ])
      .then();
  }

  editContact(contactId: string): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/customers/${this.customerId}/contacts/${contactId}/edit`,
      ])
      .then();
  }

  deleteContacts(contacts: any): void {
    this.isLoading = true;
    this.contactService
      .deleteContacts(
        this.customerId.toString(),
        contacts.map(c => c.id.toString())
      )
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.contacts = this.contacts.filter(c => !contacts.includes(c));
        //this.getData();
        const msgBeginning =
          contacts.length > 1 ? 'Contacts have' : 'Contact has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public markContactAsPrimary(row: any): void {
    this.isLoading = true;

    this.contactService
      .editContact(this.customerId.toString(), row.id, row)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.updatePrimaryEntityInList(row.id);
          this.toastService.success(
            'Contact was successfully marked as primary'
          );
        },
        err => this.toastService.error(err, 'Contact was not marked as primary')
      );
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private updatePrimaryEntityInList(contactId: string): void {
    const prevDefaultIndex = this.contacts.findIndex(
      e => e.primary_contact === true
    );
    const curDefaultIndex = this.contacts.findIndex(e => e.id === contactId);

    this.contacts[prevDefaultIndex].primary_contact = false;
    this.contacts[curDefaultIndex].primary_contact = true;

    this.contacts[prevDefaultIndex].updated_at = moment().format();
    this.contacts[curDefaultIndex].updated_at = moment().format();
  }

  getData() {}
}
