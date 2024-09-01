import { Component, Input, OnInit } from '@angular/core';
import { DatatableButtonConfig } from '../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../shared/classes/datatable/datatable-menu-config';

@Component({
  selector: 'oz-finance-resource-invoice-list-external',
  templateUrl: './resource-invoice-list-external.component.html',
})
export class ResourceInvoiceListExternalComponent implements OnInit {
  @Input() resource: any;

  isLoading = false;

  buttonConfig = new DatatableButtonConfig({
    export: false,
    import: false,
    delete: false,
    edit: false,
    add: false,
    columns: false,
    filters: false,
  });

  rowMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
    delete: false,
    edit: false,
    view: false,
    invoice: false,
    order: false,
  });

  constructor() {}

  ngOnInit(): void {}
}
