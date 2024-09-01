import { Component, Input, OnInit } from '@angular/core';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { TablePreferencesService } from '../../../../../../shared/services/table-preferences.service';
import { DatatableContainerBase } from '../../../../../../shared/classes/datatable/datatable-container-base';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-credit-notes-list',
  templateUrl: './credit-notes-list.component.html',
  styleUrls: ['./credit-notes-list.component.scss'],
})
export class CreditNotesListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @Input() creditNotes: any;
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    add: false,
    delete: false,
    filters: false,
    columns: false,
    export: false,
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    showMenu: false,
  });

  constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  ngOnInit(): void {}

  getData() {}
}
