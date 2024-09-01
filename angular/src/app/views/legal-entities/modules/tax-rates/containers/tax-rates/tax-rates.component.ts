import { Component, OnInit } from '@angular/core';
import { RoutingService } from 'src/app/core/services/routing.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { ActivatedRoute, Router } from '@angular/router';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { TaxRate } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate';
import { TaxRatesService } from 'src/app/views/legal-entities/modules/tax-rates/tax-rates.service';
import { TaxRateList } from 'src/app/views/legal-entities/modules/tax-rates/interfaces/tax-rate-list';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-tax-rates',
  templateUrl: './tax-rates.component.html',
  styleUrls: ['./tax-rates.component.scss'],
})
export class TaxRatesComponent
  extends DatatableContainerBase
  implements OnInit
{
  public taxRates: TaxRateList;
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: false,
    filters: false,
    export: false,
    delete: false,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
  });
  protected table = 'taxrates';

  public constructor(
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private router: Router,
    private routingService: RoutingService,
    private toastService: ToastrService,
    private taxRatesService: TaxRatesService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    super.ngOnInit();
    this.getResolvedData();
  }

  public getData(): void {
    this.isLoading = true;

    this.taxRatesService
      .getTaxRates(this.taxRatesService.legalEntityId)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.taxRates = response;
      });
  }

  public addTaxRate(): void {
    this.routingService.setNext();
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  public editTaxRate(id: string): void {
    this.routingService.setNext();
    this.router.navigate([id], { relativeTo: this.route }).then();
  }

  public deleteTaxRate(taxRates: TaxRate[]): void {
    this.isLoading = true;

    this.taxRatesService
      .deleteTaxRate(taxRates[0].id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        this.getData();
        this.toastService.success(
          'TaxRate has been successfully deleted',
          'Success'
        );
      });
  }

  private getResolvedData(): void {
    const { taxRates } = this.route.snapshot.data;
    this.taxRates = taxRates;
    this.preferences = {
      columns: [
        {
          prop: 'tax_rate',
          name: 'VAT rate',
          type: 'integer',
        },
        {
          prop: 'start_date',
          name: 'Start date',
          type: 'date',
        },
        {
          prop: 'end_date',
          name: 'End date',
          type: 'date',
        },
        {
          prop: 'xero_sales_tax_type',
          name: 'Xero sales VAT type',
          type: 'string',
        },
        {
          prop: 'xero_purchase_tax_type',
          name: 'Xero purchase VAT type',
          type: 'string',
        },
      ],
      all_columns: [],
      filters: [],
      sorts: [],
    };
  }
}
